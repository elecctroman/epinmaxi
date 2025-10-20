<?php
require_once __DIR__ . '/../app.php';

$headers = [
    'X-Frame-Options' => 'SAMEORIGIN',
    'X-Content-Type-Options' => 'nosniff',
    'Referrer-Policy' => 'strict-origin-when-cross-origin',
    'Permissions-Policy' => 'interest-cohort=()'
];
foreach ($headers as $key => $value) {
    header($key . ': ' . $value);
}
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; connect-src 'self'; frame-ancestors 'self';");

$router = app_router();
$router->dispatch();
