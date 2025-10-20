<?php
namespace System\Controllers\Admin;

use System\Core\DB;

class DashboardController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(): void
    {
        $this->enforce('view-dashboard');
        $summary = DB::query('SELECT COUNT(*) AS orders, SUM(grand_total) AS revenue, SUM(discount_total) AS discounts, SUM(tax_total) AS taxes FROM orders')->fetch();
        $pendingTickets = DB::query('SELECT COUNT(*) FROM tickets WHERE status = "open"')->fetchColumn();
        $stockAlerts = DB::query('SELECT p.title, p.slug, p.type, COUNT(k.id) AS remaining FROM products p LEFT JOIN product_keys k ON k.product_id = p.id AND k.status = "unused" WHERE p.stock_policy = "track_keys" GROUP BY p.id HAVING remaining < 5 ORDER BY remaining ASC LIMIT 5')->fetchAll();
        $recentOrders = DB::query('SELECT id, order_no, email, grand_total, currency, status, created_at FROM orders ORDER BY created_at DESC LIMIT 8')->fetchAll();
        $chartData = DB::query('SELECT DATE(created_at) AS day, SUM(grand_total) AS total FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY) GROUP BY day ORDER BY day ASC')->fetchAll();

        $this->render('dashboard', [
            'title' => 'Gösterge Paneli',
            'summary' => $summary,
            'pendingTickets' => $pendingTickets,
            'stockAlerts' => $stockAlerts,
            'recentOrders' => $recentOrders,
            'chartData' => $chartData,
        ]);
    }
}
