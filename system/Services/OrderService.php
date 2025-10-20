<?php
namespace System\Services;

use System\Core\DB;
use System\Services\Delivery\DigitalDeliveryService;
use System\Services\Payments\PaymentGatewayInterface;

class OrderService
{
    protected PaymentGatewayInterface $gateway;
    protected DigitalDeliveryService $delivery;

    public function __construct(PaymentGatewayInterface $gateway, DigitalDeliveryService $delivery)
    {
        $this->gateway = $gateway;
        $this->delivery = $delivery;
    }

    public function checkout(array $order, array $items, array $customer, ?array $coupon = null): array
    {
        if (empty($items)) {
            throw new \RuntimeException('Sepet boş.');
        }

        return DB::transaction(function ($pdo) use ($order, $items, $customer, $coupon) {
            $stmt = $pdo->prepare('INSERT INTO orders (order_no,user_id,email,phone,subtotal,discount_total,tax_total,grand_total,currency,payment_method,payment_status,status,ip,user_agent,created_at,coupon_code,coupon_discount) VALUES (:order_no,:user_id,:email,:phone,:subtotal,:discount,:tax,:grand,:currency,:method,:payment_status,:status,:ip,:ua,NOW(),:coupon_code,:coupon_discount)');
            $stmt->execute([
                'order_no' => $order['order_no'],
                'user_id' => $order['user_id'] ?? null,
                'email' => $customer['email'],
                'phone' => $customer['phone'] ?? null,
                'subtotal' => $order['subtotal'],
                'discount' => $order['discount_total'] ?? 0,
                'tax' => $order['tax_total'] ?? 0,
                'grand' => $order['grand_total'],
                'currency' => $order['currency'],
                'method' => $order['payment_method'],
                'payment_status' => 'pending',
                'status' => 'new',
                'ip' => $order['ip'] ?? null,
                'ua' => $order['user_agent'] ?? null,
                'coupon_code' => $coupon['coupon']['code'] ?? null,
                'coupon_discount' => $coupon['discount'] ?? 0,
            ]);
            $orderId = (int) $pdo->lastInsertId();

            $itemStmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, qty, unit_price, total_price, delivery_payload) VALUES (:order,:product,:qty,:unit,:total,:payload)');
            $orderItems = [];
            foreach ($items as $item) {
                $itemStmt->execute([
                    'order' => $orderId,
                    'product' => $item['product_id'],
                    'qty' => $item['qty'],
                    'unit' => $item['unit_price'],
                    'total' => $item['total_price'],
                    'payload' => json_encode([]),
                ]);
                $orderItems[] = [
                    'id' => (int) $pdo->lastInsertId(),
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                ];
            }

            $couponId = $coupon['coupon']['id'] ?? null;
            $reservedCoupon = false;
            if ($couponId) {
                $pdo->prepare('UPDATE coupons SET used_count = used_count + 1 WHERE id = :id')->execute([
                    'id' => $couponId,
                ]);
                $reservedCoupon = true;
            }

            $charge = $this->gateway->charge($order['grand_total'], $order['currency'], $order['order_no'], $customer);
            $status = $charge['status'] ?? 'failed';
            $orderStatus = $status === 'success' ? 'completed' : ($status === 'pending' ? 'processing' : 'cancelled');
            $pdo->prepare('UPDATE orders SET payment_status = :payment_status, status = :status, paid_at = CASE WHEN :paid = "success" THEN NOW() ELSE paid_at END WHERE id = :id')->execute([
                'payment_status' => $status === 'success' ? 'paid' : ($status === 'pending' ? 'pending' : 'failed'),
                'status' => $orderStatus,
                'paid' => $status,
                'id' => $orderId,
            ]);

            $pdo->prepare('INSERT INTO payments (order_id, provider, provider_txn_id, amount, currency, status, raw_response_json) VALUES (:order,:provider,:txn,:amount,:currency,:status,:raw)')->execute([
                'order' => $orderId,
                'provider' => get_class($this->gateway),
                'txn' => $charge['txn_id'] ?? null,
                'amount' => $order['grand_total'],
                'currency' => $order['currency'],
                'status' => $status,
                'raw' => json_encode($charge['raw_response'] ?? []),
            ]);

            $deliveryPayloads = [];
            if ($status === 'success') {
                foreach ($orderItems as $row) {
                    $payload = $this->deliverProduct($row['product_id'], $row['id'], $row['qty']);
                    $deliveryPayloads[$row['id']] = $payload;
                    $pdo->prepare('UPDATE order_items SET delivery_payload = :payload WHERE id = :id')->execute([
                        'payload' => json_encode($payload),
                        'id' => $row['id'],
                    ]);
                }
            }

            if ($status === 'failed' && $reservedCoupon) {
                $pdo->prepare('UPDATE coupons SET used_count = CASE WHEN used_count > 0 THEN used_count - 1 ELSE 0 END WHERE id = :id')->execute([
                    'id' => $couponId,
                ]);
            }

            return [
                'order_id' => $orderId,
                'order_no' => $order['order_no'],
                'status' => $status,
                'payment' => $charge,
                'delivery' => $deliveryPayloads,
            ];
        });
    }

    protected function deliverProduct(int $productId, int $orderItemId, int $quantity): array
    {
        $product = DB::query('SELECT id, type FROM products WHERE id = :id', ['id' => $productId])->fetch();
        if (!$product) {
            throw new \RuntimeException('Ürün bulunamadı.');
        }
        if ($product['type'] === 'account') {
            return $this->delivery->reserveAccounts($productId, $orderItemId, $quantity);
        }
        return $this->delivery->reserveKeys($productId, $orderItemId, $quantity);
    }
}
