<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Yönetim Girişi'); ?></title>
    <link rel="stylesheet" href="<?= asset('css/theme.css'); ?>">
    <style>
        body { display:flex; min-height:100vh; align-items:center; justify-content:center; background:linear-gradient(135deg,var(--color-primary-strong),#0f172a); padding:2rem; }
        .auth-card { background:#fff; border-radius:1.5rem; padding:3rem; width:100%; max-width:460px; box-shadow:0 30px 60px rgba(15,23,42,0.35); }
        .auth-card h1 { margin-bottom:1.5rem; }
        .auth-card form { display:grid; gap:1rem; }
    </style>
</head>
<body>
    <?= $content; ?>
</body>
</html>
