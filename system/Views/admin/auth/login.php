<section class="auth-card">
    <h1>Yönetim Paneli</h1>
    <?php if ($flash = flash()): ?>
        <div class="alert alert-<?= e($flash['level']); ?>"><?= e($flash['message']); ?></div>
    <?php endif; ?>
    <form method="post" action="/admin/giris">
        <?= csrf_field(); ?>
        <label class="input-field">
            <span>E-posta</span>
            <input type="email" name="email" required autofocus>
        </label>
        <label class="input-field">
            <span>Parola</span>
            <input type="password" name="password" required>
        </label>
        <button class="button-primary" type="submit">Giriş Yap</button>
    </form>
</section>
