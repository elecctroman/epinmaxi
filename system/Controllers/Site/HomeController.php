<?php
namespace System\Controllers\Site;

use System\Core\Controller;
use System\Core\DB;

class HomeController extends Controller
{
    public function index(): void
    {
        try {
            $featured = DB::query('SELECT title, slug, price, currency, description FROM products WHERE status = :status ORDER BY created_at DESC LIMIT 6', [
                'status' => 'active'
            ])->fetchAll();
        } catch (\Throwable $e) {
            $featured = [];
        }

        $this->view('site/home', [
            'featured' => $featured,
        ]);
    }
}
