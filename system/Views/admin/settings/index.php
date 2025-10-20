<section class="card">
    <header class="card-header"><h2>Mağaza Ayarları</h2></header>
    <form method="post" enctype="multipart/form-data" action="/admin/ayarlar" class="grid cols-2" style="gap:1rem;">
        <?= csrf_field(); ?>
        <label class="input-field">
            <span>Site Adı</span>
            <input type="text" name="app_name" value="<?= e($values['app.name'] ?? ''); ?>" required>
        </label>
        <label class="input-field">
            <span>Varsayılan Para Birimi</span>
            <select name="app_currency">
                <?php foreach (['TRY','USD','EUR'] as $currency): ?>
                    <option value="<?= $currency; ?>" <?= ($values['app.currency'] ?? 'TRY') === $currency ? 'selected' : ''; ?>><?= $currency; ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="input-field">
            <span>Zaman Dilimi</span>
            <input type="text" name="app_timezone" value="<?= e($values['app.timezone'] ?? 'Europe/Istanbul'); ?>">
        </label>
        <label class="input-field">
            <span>Logo</span>
            <input type="file" name="logo" accept="image/png,image/jpeg,image/svg+xml">
        </label>
        <label class="input-field">
            <span>Birincil Renk</span>
            <input type="text" name="app_theme_primary" value="<?= e($values['app.theme.primary'] ?? '#6366f1'); ?>">
        </label>
        <label class="input-field">
            <span>İkincil Renk</span>
            <input type="text" name="app_theme_secondary" value="<?= e($values['app.theme.secondary'] ?? '#10b981'); ?>">
        </label>
        <label class="input-field">
            <span>Tema Modu</span>
            <select name="app_theme_mode">
                <option value="light" <?= ($values['app.theme.mode'] ?? 'light') === 'light' ? 'selected' : ''; ?>>Açık</option>
                <option value="dark" <?= ($values['app.theme.mode'] ?? 'light') === 'dark' ? 'selected' : ''; ?>>Koyu</option>
            </select>
        </label>
        <label class="input-field">
            <span>SMTP Host</span>
            <input type="text" name="mail_host" value="<?= e($values['mail.host'] ?? ''); ?>">
        </label>
        <label class="input-field">
            <span>SMTP Port</span>
            <input type="number" name="mail_port" value="<?= e($values['mail.port'] ?? ''); ?>">
        </label>
        <label class="input-field">
            <span>SMTP Kullanıcı</span>
            <input type="text" name="mail_username" value="<?= e($values['mail.username'] ?? ''); ?>">
        </label>
        <label class="input-field">
            <span>SMTP Parola</span>
            <input type="password" name="mail_password" value="<?= e($values['mail.password'] ?? ''); ?>">
        </label>
        <label class="input-field">
            <span>E-posta Gönderen</span>
            <input type="email" name="mail_from" value="<?= e($values['mail.from'] ?? 'no-reply@localhost'); ?>">
        </label>
        <label class="input-field">
            <span>Ödeme Sağlayıcısı</span>
            <select name="payment_default">
                <?php foreach (['mock','iyzico','paytr','stripe'] as $provider): ?>
                    <option value="<?= $provider; ?>" <?= ($values['payment.default'] ?? 'mock') === $provider ? 'selected' : ''; ?>><?= strtoupper($provider); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="input-field">
            <span>Mock API Anahtarı</span>
            <input type="text" name="payment_mock_api_key" value="<?= e($values['payment.mock.api_key'] ?? ''); ?>">
        </label>
        <label class="input-field">
            <span>iyzico Anahtarı</span>
            <input type="text" name="payment_iyzico_key" value="<?= mask_secret($values['payment.iyzico.key'] ?? '', 4); ?>" placeholder="********">
        </label>
        <label class="input-field">
            <span>PayTR Anahtarı</span>
            <input type="text" name="payment_paytr_key" value="<?= mask_secret($values['payment.paytr.key'] ?? '', 4); ?>" placeholder="********">
        </label>
        <label class="input-field">
            <span>Stripe Secret</span>
            <input type="text" name="payment_stripe_secret" value="<?= mask_secret($values['payment.stripe.secret'] ?? '', 4); ?>" placeholder="********">
        </label>
        <label class="input-field">
            <span>reCAPTCHA Site Anahtarı</span>
            <input type="text" name="recaptcha_site" value="<?= e($values['recaptcha.site'] ?? ''); ?>">
        </label>
        <label class="input-field">
            <span>reCAPTCHA Secret</span>
            <input type="text" name="recaptcha_secret" value="<?= mask_secret($values['recaptcha.secret'] ?? '', 4); ?>">
        </label>
        <div class="input-field" style="grid-column:1/span 2;">
            <span>API Erişim Tokenı</span>
            <div class="grid cols-2" style="gap:0.75rem;">
                <input type="text" name="api_token_new" placeholder="Yeni token üretin veya yapıştırın">
                <input type="text" value="Aktif token ipucu: <?= e($values['api.token_hint'] ?? 'tanımsız'); ?>" readonly>
            </div>
            <small class="text-muted">Kaydettiğiniz token SHA-256 ile hashlenir ve bir daha görüntülenmez. API isteklerinde Bearer header ile kullanın.</small>
        </div>
        <label class="input-field" style="grid-column:1/span 2;">
            <span>API Token Yetkilendirme Kapsamı</span>
            <input type="text" name="api_token_subject" value="<?= e($values['api.token_subject'] ?? ''); ?>" placeholder="ör. customer@example.com veya kullanıcı ID">
            <small class="text-muted">Bu değer /api/orders uç noktasında sonuçları sınırlar.</small>
        </label>
        <div style="grid-column:1 / span 2;">
            <button class="button-primary" type="submit">Ayarları Kaydet</button>
        </div>
    </form>
</section>
