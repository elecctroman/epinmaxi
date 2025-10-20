<?php
namespace System\Services\Admin;

use System\Core\DB;

class ReportService
{
    public static function collect(string $from, string $to): array
    {
        $summary = DB::query('SELECT COUNT(*) AS orders, SUM(grand_total) AS revenue, SUM(discount_total) AS discounts, SUM(tax_total) AS taxes FROM orders WHERE created_at BETWEEN :from AND :to', [
            'from' => $from,
            'to' => $to,
        ])->fetch();

        $payments = DB::query('SELECT payment_status, COUNT(*) AS total FROM orders WHERE created_at BETWEEN :from AND :to GROUP BY payment_status', [
            'from' => $from,
            'to' => $to,
        ])->fetchAll();

        $topProducts = DB::query('SELECT p.title, SUM(oi.qty) AS quantity, SUM(oi.total_price) AS revenue FROM order_items oi INNER JOIN products p ON p.id = oi.product_id INNER JOIN orders o ON o.id = oi.order_id WHERE o.created_at BETWEEN :from AND :to GROUP BY p.id ORDER BY revenue DESC LIMIT 10', [
            'from' => $from,
            'to' => $to,
        ])->fetchAll();

        $couponPerformance = DB::query('SELECT coupon_code, COUNT(*) AS uses, SUM(coupon_discount) AS discount_total FROM orders WHERE coupon_code IS NOT NULL AND created_at BETWEEN :from AND :to GROUP BY coupon_code ORDER BY uses DESC', [
            'from' => $from,
            'to' => $to,
        ])->fetchAll();

        $wallet = DB::query('SELECT type, SUM(amount) AS amount FROM wallet_transactions WHERE created_at BETWEEN :from AND :to GROUP BY type', [
            'from' => $from,
            'to' => $to,
        ])->fetchAll();

        return [
            'summary' => $summary,
            'payments' => $payments,
            'top_products' => $topProducts,
            'coupons' => $couponPerformance,
            'wallet' => $wallet,
        ];
    }
}
