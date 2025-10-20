<?php
$title = $title ?? '2FA Doğrulama';
?>
<section class="auth-card card">
    <h1>İki Aşamalı Doğrulama</h1>
    <p>Lütfen doğrulama uygulamanızdaki 6 haneli kodu girin.</p>
    <form method="post" action="/iki-adim">
        <?= csrf_field(); ?>
        <div class="form-group">
            <label for="twofactor-code">Kod</label>
            <input type="text" id="twofactor-code" name="code" pattern="[0-9]{6}" maxlength="6" required>
        </div>
        <button type="submit" class="button-primary" style="width:100%;">Doğrula</button>
    </form>
</section>
