<?php
namespace System\Controllers\Site;

use PDO;
use System\Core\Auth;
use System\Core\Controller;
use System\Core\DB;

class HomeController extends Controller
{
    public function index(): void
    {
        $categories = cache_remember('home_categories', 180, function () {
            return DB::query('SELECT id, name, slug FROM categories ORDER BY sort ASC LIMIT 8')->fetchAll(PDO::FETCH_ASSOC);
        });
        $featured = cache_remember('home_featured', 120, function () {
            return DB::query('SELECT p.*, COALESCE(JSON_ARRAY_LENGTH(p.gallery),0) AS image_count FROM products p WHERE p.status = "active" ORDER BY p.created_at DESC LIMIT 6')->fetchAll(PDO::FETCH_ASSOC);
        });
        $latestOrders = [];
        if (Auth::check()) {
            $latestOrders = DB::query('SELECT order_no, grand_total, currency, status, created_at FROM orders WHERE user_id = :uid ORDER BY created_at DESC LIMIT 5', ['uid' => Auth::id()])->fetchAll(PDO::FETCH_ASSOC);
        }
        $this->view('site/home', [
            'categories' => $categories,
            'featured' => $featured,
            'latestOrders' => $latestOrders,
            'title' => setting('seo.meta_title', 'E-PIN Premium'),
            'meta_description' => setting('seo.meta_description', ''),
        ]);
    }

    public function search(): void
    {
        $query = trim($_GET['q'] ?? '');
        $payload = [];
        if ($query !== '') {
            if (!rate_limit('search:' . ($_SERVER['REMOTE_ADDR'] ?? 'cli'), 20, 60)) {
                json_response(['data' => [], 'message' => 'Çok fazla arama denemesi.'], 429);
                return;
            }
            $stmt = DB::query('SELECT id, title, slug, price, sale_price, currency FROM products WHERE status = "active" AND (title LIKE :q OR JSON_CONTAINS(tags, JSON_QUOTE(:plain))) ORDER BY created_at DESC LIMIT 10', [
                'q' => '%' . $query . '%',
                'plain' => $query,
            ]);
            $payload = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        json_response(['data' => $payload]);
    }
}
