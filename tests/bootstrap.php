<?php
use PDO;

require_once __DIR__ . '/../system/Helpers/functions.php';

spl_autoload_register(function ($class) {
    if (str_starts_with($class, 'System\\')) {
        $relative = substr($class, strlen('System\\'));
        $path = __DIR__ . '/../system/' . str_replace('\\', '/', $relative) . '.php';
        if (is_file($path)) {
            require_once $path;
        }
    } elseif (str_starts_with($class, 'Tests\\')) {
        $relative = substr($class, strlen('Tests\\'));
        $path = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';
        if (is_file($path)) {
            require_once $path;
        }
    }
});

\System\Core\DB::init([
    'driver' => 'sqlite',
    'database' => ':memory:',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ],
]);

$pdo = \System\Core\DB::pdo();
$pdo->sqliteCreateFunction('NOW', function () {
    return date('Y-m-d H:i:s');
});

$schema = <<<SQL
CREATE TABLE products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    type TEXT,
    title TEXT,
    slug TEXT,
    sku TEXT,
    category_id INTEGER,
    price REAL,
    sale_price REAL,
    currency TEXT,
    stock_policy TEXT,
    description TEXT,
    status TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE product_keys (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER,
    code TEXT,
    meta_json TEXT,
    status TEXT,
    order_item_id INTEGER,
    used_at TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE product_accounts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER,
    username TEXT,
    password_encrypted TEXT,
    meta_json TEXT,
    status TEXT,
    order_item_id INTEGER,
    used_at TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_no TEXT,
    user_id INTEGER,
    email TEXT,
    phone TEXT,
    subtotal REAL DEFAULT 0,
    discount_total REAL DEFAULT 0,
    tax_total REAL DEFAULT 0,
    grand_total REAL DEFAULT 0,
    currency TEXT,
    payment_method TEXT,
    payment_status TEXT,
    status TEXT,
    ip TEXT,
    user_agent TEXT,
    coupon_code TEXT,
    coupon_discount REAL DEFAULT 0,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    paid_at TEXT
);
CREATE TABLE order_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER,
    product_id INTEGER,
    qty INTEGER,
    unit_price REAL,
    total_price REAL,
    delivery_payload TEXT
);
CREATE TABLE payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER,
    provider TEXT,
    provider_txn_id TEXT,
    amount REAL,
    currency TEXT,
    status TEXT,
    raw_response_json TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE coupons (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT,
    type TEXT,
    value REAL,
    max_uses INTEGER,
    used_count INTEGER,
    min_subtotal REAL,
    max_discount REAL,
    per_user_limit INTEGER,
    starts_at TEXT,
    ends_at TEXT,
    status TEXT
);
CREATE TABLE settings (
    `key` TEXT PRIMARY KEY,
    `value` TEXT
);
SQL;
$pdo->exec($schema);
