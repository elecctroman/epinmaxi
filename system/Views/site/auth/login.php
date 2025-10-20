<?php
$title = $title ?? 'Giriş Yap';
?>
<section class="auth-card card">
    <h1>Hesabınıza giriş yapın</h1>
    <form method="post" action="/giris">
        <?= csrf_field(); ?>
        <div class="form-group">
            <label for="login-email">E-posta</label>
            <input type="email" id="login-email" name="email" required>
        </div>
        <div class="form-group">
            <label for="login-password">Parola</label>
            <input type="password" id="login-password" name="password" required>
        </div>
        <button type="submit" class="button-primary" style="width:100%;">Giriş Yap</button>
    </form>
    <p class="auth-footer">Hesabınız yok mu? <a href="/kayit">Kayıt olun</a></p>
</section>
