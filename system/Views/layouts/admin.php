<?php use System\Core\Gate; use System\Helpers\Flash; ?>
<!DOCTYPE html>
<html lang="tr" data-theme="<?= e(setting('app.theme.mode', 'light')); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Yönetim Paneli'); ?> - <?= e(setting('app.name', 'Mağaza')); ?></title>
    <link rel="stylesheet" href="<?= asset('css/theme.css'); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <style>
        body { display: grid; grid-template-columns: 260px 1fr; min-height: 100vh; background: var(--color-bg-muted); }
        aside { background: var(--color-surface-strong); color: var(--color-text-on-primary); padding: 1.5rem; position: sticky; top: 0; height: 100vh; overflow-y: auto; box-shadow: inset -1px 0 0 rgba(15,23,42,0.1); }
        aside header { display:flex; align-items:center; gap:0.75rem; margin-bottom:2rem; }
        aside nav a { color: inherit; display:flex; align-items:center; gap:0.75rem; padding:0.65rem 0.85rem; border-radius:1rem; text-decoration:none; font-weight:500; }
        aside nav a:hover, aside nav a[aria-current="page"] { background: rgba(255,255,255,0.08); }
        main { padding: 2rem 3rem; background: var(--color-bg); }
        header.admin-top { display:flex; align-items:center; justify-content:space-between; margin-bottom:2rem; }
        header.admin-top .actions { display:flex; gap:1rem; align-items:center; }
        .badge { border-radius:999px; padding:0.25rem 0.75rem; font-size:0.75rem; background:var(--color-primary); color:#fff; }
        .flash { margin-bottom:1.5rem; padding:1rem 1.25rem; border-radius:1rem; }
        .flash-success { background:rgba(16,185,129,0.15); color:#065f46; }
        .flash-danger { background:rgba(239,68,68,0.15); color:#991b1b; }
        .flash-warning { background:rgba(245,158,11,0.15); color:#92400e; }
    </style>
</head>
<body>
<?php $menu = Gate::menu(); $user = $authUser ?? \System\Core\Auth::user(); $flash = Flash::get(); ?>
<aside role="navigation" aria-label="Yönetim menüsü">
    <header>
        <div>
            <strong><?= e(setting('app.name', 'Mağaza')); ?></strong>
            <div style="font-size:0.75rem;opacity:0.75;">Yönetim Paneli</div>
        </div>
    </header>
    <nav>
        <?php if (isset($menu['dashboard'])): ?><a href="/admin" aria-current="<?= ($_SERVER['REQUEST_URI'] ?? '') === '/admin' ? 'page' : 'false'; ?>">📊 Gösterge Paneli</a><?php endif; ?>
        <?php if (isset($menu['products'])): ?><a href="/admin/urunler">🛒 Ürünler</a><?php endif; ?>
        <?php if (isset($menu['categories'])): ?><a href="/admin/kategoriler">🗂️ Kategoriler</a><?php endif; ?>
        <?php if (isset($menu['keys'])): ?><a href="/admin/epin-havuzu">🔑 Anahtar Havuzu</a><?php endif; ?>
        <?php if (isset($menu['accounts'])): ?><a href="/admin/hesap-havuzu">👤 Hesap Havuzu</a><?php endif; ?>
        <?php if (isset($menu['orders'])): ?><a href="/admin/siparisler">📦 Siparişler</a><?php endif; ?>
        <?php if (isset($menu['payments'])): ?><a href="/admin/odemeler">💳 Ödemeler</a><?php endif; ?>
        <?php if (isset($menu['users'])): ?><a href="/admin/kullanicilar">🧑‍🤝‍🧑 Kullanıcılar</a><?php endif; ?>
        <?php if (isset($menu['coupons'])): ?><a href="/admin/kuponlar">🎁 Kuponlar</a><?php endif; ?>
        <?php if (isset($menu['wallets'])): ?><a href="/admin/cuzdanlar">💰 Cüzdanlar</a><?php endif; ?>
        <?php if (isset($menu['support'])): ?><a href="/admin/destek">💬 Destek</a><?php endif; ?>
        <?php if (isset($menu['settings'])): ?><a href="/admin/ayarlar">⚙️ Ayarlar</a><?php endif; ?>
        <?php if (isset($menu['logs'])): ?><a href="/admin/gunlukler">🪵 Günlükler</a><?php endif; ?>
        <?php if (isset($menu['reports'])): ?><a href="/admin/raporlar">📈 Raporlar</a><?php endif; ?>
    </nav>
</aside>
<main role="main">
    <header class="admin-top">
        <div>
            <h1 style="margin:0;font-size:1.75rem;"><?= e($title ?? 'Yönetim Paneli'); ?></h1>
            <p style="margin:0.25rem 0 0;color:var(--color-text-muted);">Kontrol, rapor ve otomatik teslimat süreçlerini yönetin.</p>
        </div>
        <div class="actions">
            <button class="button-secondary" data-toggle="theme">Tema Değiştir</button>
            <div class="badge"><?= e($user['name'] ?? 'Admin'); ?></div>
            <form action="/admin/cikis" method="post" style="margin:0;">
                <?= csrf_field(); ?>
                <button class="button-link" style="color:var(--color-danger);">Çıkış</button>
            </form>
        </div>
    </header>

    <?php if ($flash): ?>
        <div class="flash flash-<?= e($flash['level']); ?>" role="alert"><?= e($flash['message']); ?></div>
    <?php endif; ?>

    <?= $content; ?>
</main>
<script src="<?= asset('js/app.js'); ?>" defer></script>
</body>
</html>
