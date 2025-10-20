<?php
$title = $title ?? 'Hesabım';
?>
<section class="page-heading">
    <h1>Merhaba, <?= e($user['name']); ?></h1>
    <p>E-posta: <?= e($user['email']); ?> • 2FA: <?= $user['twofa_secret'] ? 'Aktif' : 'Kapalı'; ?></p>
</section>
<div class="account-grid">
    <section class="card">
        <h2>Profil Bilgileri</h2>
        <form method="post" action="/hesabim/profil">
            <?= csrf_field(); ?>
            <div class="form-group">
                <label for="profile-name">Ad Soyad</label>
                <input type="text" id="profile-name" name="name" value="<?= e($user['name']); ?>">
            </div>
            <div class="form-group">
                <label for="profile-phone">Telefon</label>
                <input type="tel" id="profile-phone" name="phone" value="<?= e($user['phone'] ?? ''); ?>">
            </div>
            <button type="submit" class="button-primary">Güncelle</button>
        </form>
        <form method="post" action="/hesabim/parola" class="password-form">
            <?= csrf_field(); ?>
            <div class="form-group">
                <label for="current_password">Mevcut Parola</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>
            <div class="form-group">
                <label for="password">Yeni Parola</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="password_confirmation">Parola Tekrar</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required>
            </div>
            <button type="submit" class="button-secondary">Parolayı Değiştir</button>
        </form>
        <form method="post" action="/hesabim/iki-adim" class="twofa-form">
            <?= csrf_field(); ?>
            <button type="submit" class="button-secondary">2FA <?= $user['twofa_secret'] ? 'Kapat' : 'Aç'; ?></button>
            <?php if (!empty($_SESSION['twofa_secret'])): ?>
                <p class="hint">Yeni gizli anahtar: <?= e($_SESSION['twofa_secret']); unset($_SESSION['twofa_secret']); ?></p>
            <?php endif; ?>
        </form>
    </section>
    <section class="card">
        <h2>Siparişlerim</h2>
        <ul class="order-mini">
            <?php foreach ($orders as $order): ?>
                <li>
                    <div>
                        <a href="/siparis/<?= e($order['order_no']); ?>"><?= e($order['order_no']); ?></a>
                        <p><?= e(date('d.m.Y', strtotime($order['created_at']))); ?></p>
                    </div>
                    <span><?= format_currency($order['grand_total'], $order['currency']); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <section class="card">
        <h2>Dijital Teslimatlar</h2>
        <ul class="delivery-mini">
            <?php foreach ($items as $item): ?>
                <li>
                    <strong><?= e($item['title']); ?></strong>
                    <p>Sipariş #: <a href="/siparis/<?= e($item['order_no']); ?>"><?= e($item['order_no']); ?></a></p>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <section class="card">
        <h2>Cüzdan</h2>
        <p>Bakiye: <strong><?= format_currency($wallet, 'TRY'); ?></strong></p>
        <p>İade ve cüzdan işlemleri ödeme sonrası otomatik olarak güncellenir.</p>
    </section>
    <section class="card">
        <h2>Destek Taleplerim</h2>
        <ul class="ticket-mini">
            <?php foreach ($tickets as $ticket): ?>
                <li>
                    <a href="/destek/<?= e($ticket['id']); ?>"><?= e($ticket['subject']); ?></a>
                    <span class="badge"><?= e($ticket['status']); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
        <form method="post" action="/destek" class="support-form">
            <?= csrf_field(); ?>
            <input type="text" name="subject" placeholder="Yeni talep konusu" required>
            <textarea name="message" placeholder="Mesajınız" required></textarea>
            <select name="priority">
                <option value="normal">Normal</option>
                <option value="high">Yüksek</option>
            </select>
            <button type="submit" class="button-primary">Talep Oluştur</button>
        </form>
    </section>
</div>
