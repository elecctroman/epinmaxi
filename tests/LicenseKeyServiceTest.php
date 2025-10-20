<?php
namespace Tests;

use PHPUnit\Framework\TestCase;
use System\Core\Crypto;
use System\Core\DB;
use System\Services\LicenseKeyService;

class LicenseKeyServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::pdo()->exec('DELETE FROM product_keys');
        DB::pdo()->exec('DELETE FROM products');
    }

    public function testAssignKeysReservesAndDecrypts(): void
    {
        $pdo = DB::pdo();
        $pdo->exec("INSERT INTO products (id, type, title, status, stock_policy) VALUES (1, 'epin', 'Test', 'active', 'track_keys')");
        $secret = 'test-secret-1234567890';
        for ($i = 1; $i <= 3; $i++) {
            $code = Crypto::encrypt('KEY-' . $i, $secret);
            $stmt = $pdo->prepare('INSERT INTO product_keys (product_id, code, status) VALUES (1, :code, "unused")');
            $stmt->execute(['code' => $code]);
        }
        $service = new LicenseKeyService($secret);
        $payload = $service->assignKeys(1, 99, 2);
        $this->assertCount(2, $payload);
        $decoded = array_map(function ($value) use ($secret) {
            return Crypto::decrypt(base64_decode($value), $secret);
        }, $payload);
        $this->assertSame(['KEY-1', 'KEY-2'], $decoded);
        $statuses = $pdo->query('SELECT status, order_item_id FROM product_keys ORDER BY id')->fetchAll();
        $this->assertSame('used', $statuses[0]['status']);
        $this->assertSame(99, (int) $statuses[0]['order_item_id']);
        $this->assertSame('used', $statuses[1]['status']);
        $this->assertSame('unused', $statuses[2]['status']);
    }
}
