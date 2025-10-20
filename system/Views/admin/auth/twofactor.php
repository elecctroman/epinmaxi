<section class="auth-card">
    <h1>2 Adımlı Doğrulama</h1>
    <p class="text-muted">Lütfen doğrulama uygulamanızdaki 6 haneli kodu girin.</p>
    <?php if ($flash = flash()): ?>
        <div class="alert alert-<?= e($flash['level']); ?>"><?= e($flash['message']); ?></div>
    <?php endif; ?>
    <form method="post" action="/admin/iki-adim">
        <?= csrf_field(); ?>
        <label class="input-field">
            <span>Doğrulama Kodu</span>
            <input type="text" name="code" inputmode="numeric" maxlength="6" required>
        </label>
        <button class="button-primary" type="submit">Doğrula</button>
    </form>
</section>
