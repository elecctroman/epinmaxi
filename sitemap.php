<?php
require_once __DIR__ . '/app.php';
header('Content-Type: application/xml');
$router = app_router();

$pdo = \System\Core\DB::pdo();
$urls = [
    '/',
    '/destek',
    '/blog',
    '/sepet',
    '/odeme',
];

foreach ($pdo->query('SELECT slug FROM categories ORDER BY sort ASC') as $row) {
    $urls[] = '/kategori/' . $row['slug'];
}
foreach ($pdo->query('SELECT slug FROM products WHERE status = "active"') as $row) {
    $urls[] = '/urun/' . $row['slug'];
}

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
foreach ($urls as $loc) {
    echo "  <url><loc>" . htmlspecialchars((string)($_SERVER['REQUEST_SCHEME'] ?? 'https') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $loc, ENT_XML1) . "</loc></url>\n";
}
echo "</urlset>";
