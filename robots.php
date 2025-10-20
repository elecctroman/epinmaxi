<?php
header('Content-Type: text/plain');
echo "User-agent: *\n";
echo "Allow: /\n";
echo "Sitemap: " . rtrim((string)($_SERVER['REQUEST_SCHEME'] ?? 'https') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'), '/') . "/sitemap.xml\n";
