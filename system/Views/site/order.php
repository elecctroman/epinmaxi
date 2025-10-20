<?php
$title = $title ?? 'Sipariş';
$payloads = [];
foreach ($items as &$item) {
    $item['delivery'] = $item['delivery_payload'] ? json_decode($item['delivery_payload'], true) : [];
}
?>
<section class="page-heading">
    <h1>Sipariş #<?= e($order['order_no']); ?></h1>
    <p>Durum: <span class="badge"><?= e($order['status']); ?></span> • Ödeme: <?= e($order['payment_status']); ?></p>
</section>
<section class="card">
    <h2>Özet</h2>
    <dl class="order-summary">
        <div><dt>Tarih</dt><dd><?= e(date('d.m.Y H:i', strtotime($order['created_at']))); ?></dd></div>
        <div><dt>Tutar</dt><dd><?= format_currency($order['grand_total'], $order['currency']); ?></dd></div>
        <div><dt>Kupon</dt><dd><?= e($order['coupon_code'] ?? '-'); ?></dd></div>
    </dl>
</section>
<section class="card">
    <h2>Ürünler</h2>
    <ul class="order-items">
        <?php foreach ($items as $item): ?>
            <li>
                <div>
                    <strong><?= e($item['title']); ?></strong>
                    <p><?= e($item['qty']); ?> adet • <?= format_currency($item['unit_price'], $order['currency']); ?></p>
                </div>
                <span><?= format_currency($item['total_price'], $order['currency']); ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
<section class="card">
    <h2>Dijital Teslimatlar</h2>
    <?php foreach ($items as $item): ?>
        <article class="delivery">
            <h3><?= e($item['title']); ?></h3>
            <?php if ($order['payment_status'] !== 'paid'): ?>
                <p>Ödeme onayı bekleniyor. Teslimat hazır olduğunda burada görüntülenecek.</p>
            <?php else: ?>
                <?php if ($item['type'] === 'account'): ?>
                    <ul>
                        <?php foreach ($item['delivery'] as $account): ?>
                            <li>
                                <span>Kullanıcı adı: <?= e($account['username']); ?></span>
                                <span>Parola: <button class="button-secondary" data-copy="<?= e($account['password']); ?>">Kopyala</button></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <ul>
                        <?php foreach ($item['delivery'] as $code): ?>
                            <li>
                                <span>Kod:</span>
                                <strong><button class="button-secondary" data-copy="<?= e($code); ?>"><?= e(mask_secret($code, 4)); ?></button></strong>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
</section>
<section class="card">
    <h2>İşlem Kaydı</h2>
    <p>İlgili e-posta gönderildi ve audit loglarına işlendi.</p>
</section>
