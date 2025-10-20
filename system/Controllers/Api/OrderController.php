<?php
namespace System\Controllers\Api;

use PDO;
use System\Core\DB;
use System\Helpers\Response;

class OrderController extends ApiController
{
    public function show(string $orderNo): void
    {
        $this->authenticate();
        $subject = setting('api.token_subject');
        $conditions = 'order_no = :order_no';
        $params = ['order_no' => $orderNo];
        if ($subject) {
            if (str_contains($subject, '@')) {
                $conditions .= ' AND email = :subject_email';
                $params['subject_email'] = $subject;
            } elseif (ctype_digit($subject)) {
                $conditions .= ' AND user_id = :subject_user';
                $params['subject_user'] = (int) $subject;
            }
        }
        $order = DB::query('SELECT * FROM orders WHERE ' . $conditions . ' LIMIT 1', $params)->fetch(PDO::FETCH_ASSOC);
        if (!$order) {
            Response::json(['message' => 'Sipariş bulunamadı.'], 404);
            return;
        }
        $items = DB::query('SELECT product_id, qty, unit_price, total_price FROM order_items WHERE order_id = :order', ['order' => $order['id']])->fetchAll(PDO::FETCH_ASSOC);
        $payments = DB::query('SELECT provider, amount, currency, status, created_at FROM payments WHERE order_id = :order ORDER BY created_at DESC', ['order' => $order['id']])->fetchAll(PDO::FETCH_ASSOC);
        Response::json([
            'data' => [
                'order' => $order,
                'items' => $items,
                'payments' => $payments,
            ],
        ]);
    }
}
