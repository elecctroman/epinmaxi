<?php
$user = \System\Core\Auth::user();
?><!DOCTYPE html>
<html lang="tr" data-theme="<?= e($_SESSION['theme'] ?? 'light'); ?>">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'E-PIN Platformu'); ?></title>
    <link rel="stylesheet" href="<?= asset('css/theme.css'); ?>">
</head>
<body>
<header class="premium-header" role="banner">
    <div class="container" style="display:flex;align-items:center;justify-content:space-between;gap:1.5rem;">
        <a href="/" class="brand" style="font-weight:700;font-size:1.25rem;">E-PIN Premium</a>
        <nav aria-label="Ana menü">
            <a href="/">Anasayfa</a>
            <a href="/kategori/dijital-kodlar">Kategoriler</a>
            <a href="/destek">Destek</a>
            <a href="/blog">Blog</a>
        </nav>
        <div style="display:flex;align-items:center;gap:1rem;">
            <button type="button" data-toggle="theme" aria-label="Tema değiştir" style="border:none;background:transparent;cursor:pointer;font-size:1rem;">🌗</button>
            <?php if ($user): ?>
                <a href="/hesabim" class="button-primary" style="padding:0.5rem 1.25rem;">Hesabım</a>
            <?php else: ?>
                <a href="/giris" class="button-primary" style="padding:0.5rem 1.25rem;">Giriş</a>
            <?php endif; ?>
        </div>
    </div>
</header>
<main class="container" role="main" style="padding:2.5rem 0;">
    <?= $content; ?>
</main>
<footer role="contentinfo">
    <div class="container" style="display:grid;gap:1.5rem;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));">
        <div>
            <h3>E-PIN Premium</h3>
            <p>Güvenilir dijital kod ve lisans satış platformu.</p>
        </div>
        <div>
            <h4>Hızlı Bağlantılar</h4>
            <ul style="list-style:none;padding:0;margin:0;display:grid;gap:0.5rem;">
                <li><a href="/kvkk">KVKK</a></li>
                <li><a href="/iade-politikasi">İade Politikası</a></li>
                <li><a href="/sartlar">Kullanım Şartları</a></li>
            </ul>
        </div>
        <div>
            <h4>Bülten</h4>
            <form method="post" action="/bulten" style="display:flex;gap:0.5rem;">
                <?= csrf_field(); ?>
                <input type="email" name="email" aria-label="E-posta" placeholder="E-posta adresiniz" required style="flex:1;padding:0.75rem;border-radius:1rem;border:1px solid rgba(148,163,184,0.4);">
                <button type="submit" class="button-primary" style="padding:0.75rem 1.25rem;">Gönder</button>
            </form>
        </div>
    </div>
</footer>
<div id="toast" data-toast class="toast" role="status" aria-live="polite" style="<?= empty($_SESSION['flash']) ? 'display:none;' : ''; ?>">
    <?= e($_SESSION['flash'] ?? ''); unset($_SESSION['flash']); ?>
</div>
<script src="<?= asset('js/app.js'); ?>" defer></script>
</body>
</html>
