<?php
$title = $title ?? 'Sepetim';
$items = $cart['items'] ?? [];
$subtotal = $cart['subtotal'] ?? 0;
$coupon = $_SESSION['cart_coupon'] ?? null;
$discount = $coupon['discount'] ?? 0;
$grand = max($subtotal - $discount, 0);
?>
<section class="page-heading">
    <h1>Sepetim</h1>
    <p><?= count($items); ?> ürün bulunuyor.</p>
</section>
<form method="post" action="/sepet/guncelle" class="cart-form">
    <?= csrf_field(); ?>
    <table class="table cart-table">
        <thead>
            <tr>
                <th>Ürün</th>
                <th>Adet</th>
                <th>Birim Fiyat</th>
                <th>Toplam</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <strong><a href="/urun/<?= e($item['slug']); ?>"><?= e($item['title']); ?></a></strong>
                        <p class="meta">Tür: <?= e($item['type']); ?> | Stok: <?= $item['available'] > 9000 ? 'Sınırsız' : $item['available']; ?></p>
                        <input type="hidden" name="items[][product_id]" value="<?= e($item['product_id']); ?>">
                    </td>
                    <td>
                        <input type="number" name="items[][qty]" value="<?= e($item['qty']); ?>" min="1">
                    </td>
                    <td><?= format_currency($item['unit_price'], $item['currency']); ?></td>
                    <td><?= format_currency($item['total_price'], $item['currency']); ?></td>
                    <td>
                        <button type="submit" name="remove" value="<?= e($item['product_id']); ?>" formaction="/sepet/kaldir/<?= e($item['product_id']); ?>" formmethod="post" class="icon-button" aria-label="Kaldır">✖</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="cart-actions">
        <button type="submit" class="button-secondary">Sepeti Güncelle</button>
        <a href="/odeme" class="button-primary">Ödemeye Devam</a>
    </div>
</form>
<section class="cart-summary">
    <div class="card">
        <h2>Özet</h2>
        <dl>
            <div class="summary-line"><dt>Ara toplam</dt><dd><?= format_currency($subtotal, $cart['currency'] ?? 'TRY'); ?></dd></div>
            <div class="summary-line"><dt>İndirim</dt><dd>-<?= format_currency($discount, $cart['currency'] ?? 'TRY'); ?></dd></div>
            <div class="summary-line total"><dt>Genel toplam</dt><dd><?= format_currency($grand, $cart['currency'] ?? 'TRY'); ?></dd></div>
        </dl>
        <form method="post" action="/kupon/uygula" data-coupon-form>
            <?= csrf_field(); ?>
            <label for="coupon">Kupon kodu</label>
            <input type="text" id="coupon" name="code" placeholder="Kupon kodu" value="<?= e($coupon['coupon']['code'] ?? ''); ?>">
            <button type="submit" class="button-secondary">Uygula</button>
        </form>
        <a href="/odeme" class="button-primary" style="width:100%;margin-top:1rem;">Ödemeye geç</a>
    </div>
</section>
