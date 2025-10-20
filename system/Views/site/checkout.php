<?php
$title = $title ?? 'Ödeme';
$user = \System\Core\Auth::user();
$tax = $tax ?? 0;
?>
<section class="page-heading">
    <h1>Ödeme</h1>
    <p>Güvenli ödeme ve anında teslimat.</p>
</section>
<form method="post" action="/odeme" class="checkout-form">
    <?= csrf_field(); ?>
    <div class="checkout-grid">
        <section class="card">
            <h2>Müşteri Bilgileri</h2>
            <div class="form-group">
                <label for="name">Ad Soyad</label>
                <input type="text" id="name" name="name" value="<?= e($_POST['name'] ?? ($user['name'] ?? '')); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">E-posta</label>
                <input type="email" id="email" name="email" value="<?= e($_POST['email'] ?? ($user['email'] ?? '')); ?>" required>
            </div>
            <div class="form-group">
                <label for="phone">Telefon</label>
                <input type="tel" id="phone" name="phone" value="<?= e($_POST['phone'] ?? ($user['phone'] ?? '')); ?>">
            </div>
        </section>
        <section class="card">
            <h2>Ödeme Yöntemi</h2>
            <div class="form-group">
                <label>
                    <input type="radio" name="gateway" value="mock" <?= empty($_POST['gateway']) || $_POST['gateway'] === 'mock' ? 'checked' : ''; ?>>
                    Mock Gateway (Test)
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="radio" name="gateway" value="paytr" <?= ($_POST['gateway'] ?? '') === 'paytr' ? 'checked' : ''; ?>>
                    PayTR (Hazır)
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="radio" name="gateway" value="iyzico" <?= ($_POST['gateway'] ?? '') === 'iyzico' ? 'checked' : ''; ?>>
                    Iyzico
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="radio" name="gateway" value="stripe" <?= ($_POST['gateway'] ?? '') === 'stripe' ? 'checked' : ''; ?>>
                    Stripe
                </label>
            </div>
        </section>
        <section class="card checkout-summary">
            <h2>Sipariş Özeti</h2>
            <dl>
                <div class="summary-line"><dt>Ara toplam</dt><dd><?= format_currency($cart['subtotal'], $cart['currency']); ?></dd></div>
                <div class="summary-line"><dt>İndirim</dt><dd>-<?= format_currency($discount, $cart['currency']); ?></dd></div>
                <div class="summary-line"><dt>Vergi</dt><dd><?= format_currency($tax ?? 0, $cart['currency']); ?></dd></div>
                <div class="summary-line total"><dt>Genel toplam</dt><dd><?= format_currency($grand, $cart['currency']); ?></dd></div>
            </dl>
            <input type="hidden" name="coupon_code" value="<?= e($coupon['coupon']['code'] ?? ''); ?>">
            <button type="submit" class="button-primary" style="width:100%;">Siparişi Tamamla</button>
        </section>
    </div>
</form>
<section class="card">
    <h2>Sepet İçeriği</h2>
    <ul class="cart-mini">
        <?php foreach ($cart['items'] as $item): ?>
            <li>
                <div>
                    <strong><?= e($item['title']); ?></strong>
                    <p><?= e($item['qty']); ?> adet • <?= format_currency($item['unit_price'], $item['currency']); ?></p>
                </div>
                <span><?= format_currency($item['total_price'], $item['currency']); ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
<section class="card">
    <h2>Ödeme Güvenliği</h2>
    <p>3D Secure, 2FA ve sahtecilik önleme kontrolleri aktif. Ödeme ağ geçitlerinden gelen bildirimler imza doğrulaması ve idempotent kayıtlarla işlenir.</p>
</section>
