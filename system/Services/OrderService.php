<?php
namespace System\Services;

use System\Core\DB;
use System\Services\Payments\PaymentGatewayInterface;

class OrderService
{
    protected PaymentGatewayInterface $gateway;

    public function __construct(PaymentGatewayInterface $gateway)
    {
        $this->gateway = $gateway;
    }

    public function checkout(array $order, array $items, array $customer): array
    {
        return DB::transaction(function ($pdo) use ($order, $items, $customer) {
            $stmt = $pdo->prepare('INSERT INTO orders(order_no,user_id,email,phone,subtotal,discount_total,grand_total,currency,payment_method,status,created_at) VALUES (:order_no,:user_id,:email,:phone,:subtotal,:discount,:grand,:currency,:method,:status,NOW())');
            $stmt->execute([
                'order_no' => $order['order_no'],
                'user_id' => $order['user_id'] ?? null,
                'email' => $customer['email'],
                'phone' => $customer['phone'] ?? null,
                'subtotal' => $order['subtotal'],
                'discount' => $order['discount_total'] ?? 0,
                'grand' => $order['grand_total'],
                'currency' => $order['currency'],
                'method' => $order['payment_method'],
                'status' => 'new',
            ]);
            $orderId = (int) $pdo->lastInsertId();

            $itemStmt = $pdo->prepare('INSERT INTO order_items(order_id,product_id,qty,unit_price,total_price) VALUES (:order,:product,:qty,:price,:total)');
            foreach ($items as $item) {
                $itemStmt->execute([
                    'order' => $orderId,
                    'product' => $item['product_id'],
                    'qty' => $item['qty'],
                    'price' => $item['unit_price'],
                    'total' => $item['total_price'],
                ]);
            }

            $charge = $this->gateway->charge($order['grand_total'], $order['currency'], $order['order_no'], $customer);

            $pdo->prepare('UPDATE orders SET payment_status = :status, status = :order_status, paid_at = NOW() WHERE id = :id')
                ->execute([
                    'status' => $charge['status'] === 'success' ? 'paid' : 'failed',
                    'order_status' => $charge['status'] === 'success' ? 'completed' : 'cancelled',
                    'id' => $orderId,
                ]);

            $pdo->prepare('INSERT INTO payments(order_id, provider, provider_txn_id, amount, currency, status, raw_response_json) VALUES (:order,:provider,:txn,:amount,:currency,:status,:raw)')
                ->execute([
                    'order' => $orderId,
                    'provider' => get_class($this->gateway),
                    'txn' => $charge['txn_id'] ?? null,
                    'amount' => $order['grand_total'],
                    'currency' => $order['currency'],
                    'status' => $charge['status'],
                    'raw' => json_encode($charge['raw_response'] ?? []),
                ]);

            return [
                'order_id' => $orderId,
                'status' => $charge['status'],
                'payment' => $charge,
            ];
        });
    }
}
