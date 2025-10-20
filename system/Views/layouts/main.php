<?php
use System\Core\Auth;

$flash = flash();
$user = Auth::user();
?><!DOCTYPE html>
<html lang="tr" data-theme="<?= e($_SESSION['theme'] ?? 'light'); ?>">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()); ?>">
    <title><?= e($title ?? setting('app.name', 'E-PIN Premium')); ?></title>
    <?php if (!empty($meta_description)): ?>
        <meta name="description" content="<?= e($meta_description); ?>">
    <?php endif; ?>
    <meta property="og:title" content="<?= e($title ?? setting('app.name', 'E-PIN Premium')); ?>">
    <meta property="og:description" content="<?= e($meta_description ?? setting('seo.meta_description', '')); ?>">
    <meta property="og:type" content="website">
    <link rel="stylesheet" href="<?= asset('css/theme.css'); ?>">
</head>
<body>
<header class="premium-header" role="banner">
    <div class="container header-inner">
        <a href="/" class="brand" aria-label="<?= e(setting('app.name', 'E-PIN Premium')); ?> anasayfa"><?= e(setting('app.name', 'E-PIN Premium')); ?></a>
        <div class="mega-search" role="search">
            <input type="search" name="q" placeholder="Ürün ara" aria-label="Ürün ara" data-quick-search>
            <div class="search-results" data-search-results></div>
        </div>
        <nav aria-label="Ana menü">
            <a href="/kategori/oyun-kodlari">Kategoriler</a>
            <a href="/destek">Destek</a>
            <a href="/blog">Blog</a>
        </nav>
        <div class="header-actions">
            <button type="button" data-toggle="theme" aria-label="Tema değiştir" class="icon-button">🌗</button>
            <a href="/sepet" class="icon-button" aria-label="Sepet">
                🛒 <span data-mini-cart-count><?= count($_SESSION['cart_items'] ?? []); ?></span>
            </a>
            <?php if ($user): ?>
                <form method="post" action="/cikis" style="display:inline;">
                    <?= csrf_field(); ?>
                    <button type="submit" class="button-secondary">Çıkış</button>
                </form>
                <a href="/hesabim" class="button-primary">Hesabım</a>
            <?php else: ?>
                <a href="/giris" class="button-secondary">Giriş</a>
                <a href="/kayit" class="button-primary">Kayıt</a>
            <?php endif; ?>
        </div>
    </div>
</header>
<main class="container" role="main">
    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['level']); ?>" role="status" data-toast>
            <?= e($flash['message']); ?>
        </div>
    <?php endif; ?>
    <?= $content; ?>
</main>
<footer role="contentinfo" class="footer">
    <div class="container footer-grid">
        <div>
            <h3><?= e(setting('app.name', 'E-PIN Premium')); ?></h3>
            <p>Oyun kodları, lisanslar ve dijital hesaplarda anında teslimat.</p>
            <p><small>© <?= date('Y'); ?> Tüm hakları saklıdır.</small></p>
        </div>
        <div>
            <h4>Bilgi</h4>
            <ul>
                <li><a href="/kvkk">KVKK</a></li>
                <li><a href="/iade-politikasi">İade Politikası</a></li>
                <li><a href="/sartlar">Kullanım Şartları</a></li>
            </ul>
        </div>
        <div>
            <h4>Bülten</h4>
            <form method="post" action="/bulten" class="newsletter">
                <?= csrf_field(); ?>
                <input type="email" name="email" placeholder="E-posta" required>
                <button type="submit" class="button-primary">Abone Ol</button>
            </form>
        </div>
        <div>
            <h4>Bizi takip edin</h4>
            <div class="social">
                <a href="#" aria-label="Twitter">🐦</a>
                <a href="#" aria-label="Instagram">📸</a>
                <a href="#" aria-label="YouTube">▶️</a>
            </div>
        </div>
    </div>
</footer>
<script src="<?= asset('js/app.js'); ?>" defer></script>
<script>
    window.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
</script>
</body>
</html>
