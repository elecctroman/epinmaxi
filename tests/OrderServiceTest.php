<?php
namespace Tests;

use PHPUnit\Framework\TestCase;
use System\Core\Crypto;
use System\Core\DB;
use System\Services\Delivery\DigitalDeliveryService;
use System\Services\OrderService;
use System\Services\Payments\PaymentGatewayInterface;

class OrderServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        foreach (['order_items','orders','payments','product_keys','products'] as $table) {
            DB::pdo()->exec('DELETE FROM ' . $table);
        }
    }

    public function testCheckoutCreatesOrderAndDeliversKeys(): void
    {
        $pdo = DB::pdo();
        $pdo->exec("INSERT INTO products (id, type, title, status, stock_policy, currency, price) VALUES (1, 'epin', 'Valorant', 'active', 'track_keys', 'TRY', 100)");
        $secret = 'unit-test-key-32chars-secret!';
        $code = Crypto::encrypt('DELIVERY-1', $secret);
        $pdo->prepare('INSERT INTO product_keys (product_id, code, status) VALUES (1, :code, "unused")')->execute(['code' => $code]);
        $gateway = new class implements PaymentGatewayInterface {
            public function charge($amount, $currency, $orderNo, $customer): array
            {
                return ['status' => 'success', 'txn_id' => 'TXN123', 'raw_response' => ['ok' => true]];
            }
        };
        $delivery = new DigitalDeliveryService($secret);
        $service = new OrderService($gateway, $delivery);
        $result = $service->checkout([
            'order_no' => 'ORDTEST',
            'user_id' => null,
            'subtotal' => 100,
            'discount_total' => 0,
            'grand_total' => 100,
            'currency' => 'TRY',
            'payment_method' => 'mock',
            'tax_total' => 0,
            'ip' => '127.0.0.1',
            'user_agent' => 'phpunit',
        ], [
            ['product_id' => 1, 'qty' => 1, 'unit_price' => 100, 'total_price' => 100],
        ], [
            'email' => 'buyer@example.com',
            'phone' => null,
            'name' => 'Unit Test',
        ]);
        $this->assertSame('success', $result['status']);
        $orderRow = $pdo->query("SELECT payment_status, status, grand_total FROM orders WHERE order_no = 'ORDTEST'")->fetch();
        $this->assertSame('paid', $orderRow['payment_status']);
        $this->assertSame('completed', $orderRow['status']);
        $this->assertSame(100.0, (float) $orderRow['grand_total']);
        $payload = $pdo->query('SELECT delivery_payload FROM order_items WHERE order_id = (SELECT id FROM orders WHERE order_no = "ORDTEST")')->fetchColumn();
        $this->assertNotEmpty($payload);
        $decoded = json_decode($payload, true);
        $this->assertSame('DELIVERY-1', $decoded[0]);
        $status = $pdo->query('SELECT status FROM product_keys WHERE product_id = 1')->fetchColumn();
        $this->assertSame('used', $status);
    }
}
