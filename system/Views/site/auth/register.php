<?php
$title = $title ?? 'Kayıt Ol';
?>
<section class="auth-card card">
    <h1>Yeni hesap oluşturun</h1>
    <form method="post" action="/kayit">
        <?= csrf_field(); ?>
        <div class="form-group">
            <label for="register-name">Ad Soyad</label>
            <input type="text" id="register-name" name="name" required>
        </div>
        <div class="form-group">
            <label for="register-email">E-posta</label>
            <input type="email" id="register-email" name="email" required>
        </div>
        <div class="form-group">
            <label for="register-phone">Telefon</label>
            <input type="tel" id="register-phone" name="phone">
        </div>
        <div class="form-group">
            <label for="register-password">Parola</label>
            <input type="password" id="register-password" name="password" required>
        </div>
        <div class="form-group">
            <label for="register-password-confirm">Parola Tekrar</label>
            <input type="password" id="register-password-confirm" name="password_confirmation" required>
        </div>
        <button type="submit" class="button-primary" style="width:100%;">Kayıt Ol</button>
    </form>
    <p class="auth-footer">Zaten üye misiniz? <a href="/giris">Giriş yapın</a></p>
</section>
