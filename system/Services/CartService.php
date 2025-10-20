<?php
namespace System\Services;

use PDO;
use System\Core\Auth;
use System\Core\DB;
use System\Helpers\Flash;

class CartService
{
    protected const SESSION_KEY = 'cart_items';

    public function all(): array
    {
        $items = $_SESSION[self::SESSION_KEY] ?? [];
        $productIds = array_column($items, 'product_id');
        if (empty($productIds)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $stmt = DB::query('SELECT * FROM products WHERE id IN (' . $placeholders . ') AND status = "active"', $productIds);
        $products = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $product) {
            $products[$product['id']] = $product;
        }
        $result = [];
        foreach ($items as $row) {
            if (!isset($products[$row['product_id']])) {
                continue;
            }
            $product = $products[$row['product_id']];
            $available = $this->availableQuantity($product['id'], $product['stock_policy']);
            $qty = min($row['qty'], $available > 0 ? $available : $row['qty']);
            $unit = $product['sale_price'] !== null ? (float) $product['sale_price'] : (float) $product['price'];
            $result[] = [
                'product_id' => (int) $product['id'],
                'qty' => $qty,
                'unit_price' => $unit,
                'total_price' => $unit * $qty,
                'title' => $product['title'],
                'slug' => $product['slug'],
                'type' => $product['type'],
                'currency' => $product['currency'],
                'cover_image' => $product['cover_image'],
                'available' => $available,
            ];
        }
        return $result;
    }

    public function add(int $productId, int $qty = 1): void
    {
        $product = DB::query('SELECT * FROM products WHERE id = :id AND status = "active" LIMIT 1', ['id' => $productId])->fetch(PDO::FETCH_ASSOC);
        if (!$product) {
            throw new \RuntimeException('Ürün bulunamadı.');
        }
        $available = $this->availableQuantity($product['id'], $product['stock_policy']);
        if ($available > 0 && $qty > $available) {
            $qty = $available;
        }
        if ($product['stock_policy'] === 'track_keys' && $available <= 0) {
            throw new \RuntimeException('Stok tükendi.');
        }
        $items = $_SESSION[self::SESSION_KEY] ?? [];
        $found = false;
        foreach ($items as &$item) {
            if ($item['product_id'] === $product['id']) {
                $item['qty'] += $qty;
                if ($product['stock_policy'] === 'track_keys' && $item['qty'] > $available) {
                    $item['qty'] = $available;
                }
                $found = true;
                break;
            }
        }
        if (!$found) {
            $items[] = ['product_id' => (int) $product['id'], 'qty' => max(1, $qty)];
        }
        $_SESSION[self::SESSION_KEY] = $items;
        if (Auth::check()) {
            $this->persist();
        }
        Flash::set('Ürün sepete eklendi.', 'success');
    }

    public function update(int $productId, int $qty): void
    {
        $qty = max(0, $qty);
        $items = $_SESSION[self::SESSION_KEY] ?? [];
        foreach ($items as $index => &$item) {
            if ($item['product_id'] === $productId) {
                if ($qty <= 0) {
                    unset($items[$index]);
                } else {
                    $product = DB::query('SELECT stock_policy FROM products WHERE id = :id', ['id' => $productId])->fetch(PDO::FETCH_ASSOC);
                    $available = $this->availableQuantity($productId, $product['stock_policy'] ?? 'track_keys');
                    if ($product['stock_policy'] === 'track_keys' && $qty > $available) {
                        $qty = $available;
                    }
                    $item['qty'] = $qty;
                }
                break;
            }
        }
        $_SESSION[self::SESSION_KEY] = array_values($items);
        if (Auth::check()) {
            $this->persist();
        }
    }

    public function remove(int $productId): void
    {
        $items = $_SESSION[self::SESSION_KEY] ?? [];
        foreach ($items as $index => $item) {
            if ($item['product_id'] === $productId) {
                unset($items[$index]);
            }
        }
        $_SESSION[self::SESSION_KEY] = array_values($items);
        if (Auth::check()) {
            $this->persist();
        }
    }

    public function clear(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
        if (Auth::check()) {
            DB::query('DELETE FROM carts WHERE user_id = :uid', ['uid' => Auth::id()]);
        }
    }

    public function totals(): array
    {
        $items = $this->all();
        $subtotal = array_sum(array_column($items, 'total_price'));
        $currency = $items[0]['currency'] ?? 'TRY';
        return [
            'items' => $items,
            'subtotal' => $subtotal,
            'currency' => $currency,
        ];
    }

    protected function persist(): void
    {
        $userId = Auth::id();
        if (!$userId) {
            return;
        }
        $sessionId = session_id();
        DB::query('DELETE FROM carts WHERE user_id = :uid OR session_id = :sid', ['uid' => $userId, 'sid' => $sessionId]);
        DB::query('INSERT INTO carts (user_id, session_id, created_at, updated_at) VALUES (:uid, :sid, NOW(), NOW())', [
            'uid' => $userId,
            'sid' => $sessionId,
        ]);
        $cartId = (int) DB::pdo()->lastInsertId();
        $items = $_SESSION[self::SESSION_KEY] ?? [];
        $stmt = DB::pdo()->prepare('INSERT INTO cart_items (cart_id, product_id, qty, unit_price, total_price) VALUES (:cart, :pid, :qty, :unit, :total)');
        foreach ($items as $item) {
            $product = DB::query('SELECT price, sale_price FROM products WHERE id = :id', ['id' => $item['product_id']])->fetch(PDO::FETCH_ASSOC);
            $unit = $product && $product['sale_price'] !== null ? (float) $product['sale_price'] : (float) ($product['price'] ?? 0);
            $stmt->execute([
                'cart' => $cartId,
                'pid' => $item['product_id'],
                'qty' => $item['qty'],
                'unit' => $unit,
                'total' => $unit * $item['qty'],
            ]);
        }
    }

    protected function availableQuantity(int $productId, string $policy): int
    {
        if ($policy === 'unlimited') {
            return 9999;
        }
        $product = DB::query('SELECT type FROM products WHERE id = :id', ['id' => $productId])->fetch(PDO::FETCH_ASSOC);
        if (!$product) {
            return 0;
        }
        if ($product['type'] === 'account') {
            $stmt = DB::query('SELECT COUNT(*) FROM product_accounts WHERE product_id = :id AND status = "unused"', ['id' => $productId]);
        } else {
            $stmt = DB::query('SELECT COUNT(*) FROM product_keys WHERE product_id = :id AND status = "unused"', ['id' => $productId]);
        }
        return (int) $stmt->fetchColumn();
    }
}
