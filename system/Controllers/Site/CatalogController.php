<?php
namespace System\Controllers\Site;

use PDO;
use System\Core\Controller;
use System\Core\DB;

class CatalogController extends Controller
{
    public function category(string $slug): void
    {
        $category = cache_remember('category_' . $slug, 300, function () use ($slug) {
            return DB::query('SELECT * FROM categories WHERE slug = :slug LIMIT 1', ['slug' => $slug])->fetch(PDO::FETCH_ASSOC);
        });
        if (!$category) {
            http_response_code(404);
            echo 'Kategori bulunamadı';
            return;
        }
        $filters = $this->buildFilters();
        $query = 'SELECT p.* FROM products p WHERE p.status = "active" AND p.category_id = :cat';
        $params = ['cat' => $category['id']];
        [$query, $params] = $this->applyFilters($query, $params, $filters);
        $query .= $this->applySort($_GET['sirala'] ?? 'populer');
        $products = DB::query($query, $params)->fetchAll(PDO::FETCH_ASSOC);
        $this->view('site/category', [
            'category' => $category,
            'products' => $this->decorateProducts($products),
            'filters' => $filters,
            'title' => $category['name'] . ' | ' . setting('app.name', 'E-PIN Premium'),
        ]);
    }

    public function product(string $slug): void
    {
        $product = cache_remember('product_' . $slug, 180, function () use ($slug) {
            return DB::query('SELECT p.*, c.name AS category_name, c.slug AS category_slug FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.slug = :slug AND p.status = "active" LIMIT 1', ['slug' => $slug])->fetch(PDO::FETCH_ASSOC);
        });
        if (!$product) {
            http_response_code(404);
            echo 'Ürün bulunamadı';
            return;
        }
        $available = $this->countStock((int) $product['id'], $product['stock_policy'], $product['type']);
        $related = DB::query('SELECT id, title, slug, price, sale_price, currency FROM products WHERE status = "active" AND category_id = :cat AND id != :id ORDER BY created_at DESC LIMIT 4', [
            'cat' => $product['category_id'],
            'id' => $product['id'],
        ])->fetchAll(PDO::FETCH_ASSOC);
        $this->view('site/product', [
            'product' => $product,
            'available' => $available,
            'related' => $related,
            'title' => $product['title'] . ' - ' . setting('app.name', 'E-PIN Premium'),
        ]);
    }

    protected function buildFilters(): array
    {
        return [
            'type' => $_GET['tur'] ?? null,
            'min' => isset($_GET['min']) ? (float) $_GET['min'] : null,
            'max' => isset($_GET['max']) ? (float) $_GET['max'] : null,
            'tag' => $_GET['etiket'] ?? null,
        ];
    }

    protected function applyFilters(string $query, array $params, array $filters): array
    {
        if (!empty($filters['type'])) {
            $query .= ' AND p.type = :type';
            $params['type'] = $filters['type'];
        }
        if ($filters['min'] !== null) {
            $query .= ' AND p.price >= :min';
            $params['min'] = $filters['min'];
        }
        if ($filters['max'] !== null) {
            $query .= ' AND p.price <= :max';
            $params['max'] = $filters['max'];
        }
        if (!empty($filters['tag'])) {
            $query .= ' AND JSON_CONTAINS(p.tags, JSON_QUOTE(:tag))';
            $params['tag'] = $filters['tag'];
        }
        return [$query, $params];
    }

    protected function applySort(string $sort): string
    {
        return match ($sort) {
            'fiyat-artan' => ' ORDER BY p.price ASC',
            'fiyat-azalan' => ' ORDER BY p.price DESC',
            'yeni' => ' ORDER BY p.created_at DESC',
            default => ' ORDER BY p.created_at DESC',
        };
    }

    protected function decorateProducts(array $products): array
    {
        foreach ($products as &$product) {
            $product['available'] = $this->countStock((int) $product['id'], $product['stock_policy'], $product['type']);
        }
        return $products;
    }

    protected function countStock(int $productId, string $policy, string $type): int
    {
        if ($policy === 'unlimited') {
            return 9999;
        }
        if ($type === 'account') {
            return (int) DB::query('SELECT COUNT(*) FROM product_accounts WHERE product_id = :id AND status = "unused"', ['id' => $productId])->fetchColumn();
        }
        return (int) DB::query('SELECT COUNT(*) FROM product_keys WHERE product_id = :id AND status = "unused"', ['id' => $productId])->fetchColumn();
    }
}
