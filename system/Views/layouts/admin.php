<?php
?><!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Yönetim Paneli'); ?></title>
    <link rel="stylesheet" href="<?= asset('css/theme.css'); ?>">
    <style>
        body { display: grid; grid-template-columns: 260px 1fr; min-height: 100vh; }
        aside { background: #111827; color: #f8fafc; padding: 2rem 1.5rem; }
        aside a { color: inherit; display: block; margin-bottom: 1rem; text-decoration: none; }
        main { padding: 2rem; background: var(--color-bg); }
    </style>
</head>
<body>
<aside role="navigation" aria-label="Yönetim menüsü">
    <h2 style="margin-top:0;">Admin</h2>
    <a href="/admin">Gösterge Paneli</a>
    <a href="/admin/urunler">Ürünler</a>
    <a href="/admin/epin-havuzu">E-PIN Havuzu</a>
    <a href="/admin/hesap-havuzu">Hesap Havuzu</a>
    <a href="/admin/siparisler">Siparişler</a>
    <a href="/admin/kuponlar">Kuponlar</a>
    <a href="/admin/ayarlar">Ayarlar</a>
</aside>
<main role="main">
    <?= $content; ?>
</main>
</body>
</html>
