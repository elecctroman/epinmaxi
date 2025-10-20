<section class="grid cols-2" style="gap:1.5rem;">
    <article class="card">
        <header class="card-header"><h2>Sipariş Bilgileri</h2></header>
        <dl class="meta-grid">
            <div><dt>Sipariş No</dt><dd><?= e($order['order_no']); ?></dd></div>
            <div><dt>Müşteri</dt><dd><?= e($order['email']); ?></dd></div>
            <div><dt>Tutar</dt><dd><?= format_currency((float) $order['grand_total'], $order['currency']); ?></dd></div>
            <div><dt>Durum</dt><dd><?= e($order['status']); ?></dd></div>
            <div><dt>Ödeme</dt><dd><?= e($order['payment_status']); ?></dd></div>
            <div><dt>Oluşturulma</dt><dd><?= e(date('d.m.Y H:i', strtotime($order['created_at']))); ?></dd></div>
        </dl>
        <form method="post" action="/admin/siparisler/<?= (int) $order['id']; ?>/durum" class="grid cols-2" style="gap:1rem;margin-top:1.5rem;">
            <?= csrf_field(); ?>
            <label class="input-field">
                <span>Sipariş Durumu</span>
                <select name="status">
                    <?php foreach (['new','processing','completed','cancelled'] as $status): ?>
                        <option value="<?= $status; ?>" <?= $order['status'] === $status ? 'selected' : ''; ?>><?= ucfirst($status); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="input-field">
                <span>Ödeme Durumu</span>
                <select name="payment_status">
                    <?php foreach (['pending','paid','failed','refunded'] as $status): ?>
                        <option value="<?= $status; ?>" <?= $order['payment_status'] === $status ? 'selected' : ''; ?>><?= ucfirst($status); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div style="grid-column:1 / span 2; display:flex; gap:0.5rem;">
                <button class="button-secondary" type="submit">Durum Güncelle</button>
                <button class="button-primary" form="deliver-form" onclick="return confirm('Dijital teslimat yeniden gerçekleştirilsin mi?');">Teslimatı Yenile</button>
                <button class="button-link" form="refund-form" onclick="return confirm('Sipariş iade edilsin mi?');">İade Et</button>
            </div>
        </form>
        <form id="deliver-form" method="post" action="/admin/siparisler/<?= (int) $order['id']; ?>/teslim" style="display:none;">
            <?= csrf_field(); ?>
        </form>
        <form id="refund-form" method="post" action="/admin/siparisler/<?= (int) $order['id']; ?>/iade" style="display:none;">
            <?= csrf_field(); ?>
        </form>
    </article>
    <article class="card">
        <header class="card-header"><h2>Ödeme Kayıtları</h2></header>
        <ul class="list -divided">
            <?php foreach ($payments as $payment): ?>
                <li>
                    <strong><?= e($payment['provider']); ?></strong>
                    <div><?= e($payment['status']); ?> · <?= format_currency((float) $payment['amount'], $payment['currency']); ?></div>
                    <small><?= e(date('d.m.Y H:i', strtotime($payment['created_at']))); ?></small>
                </li>
            <?php endforeach; ?>
            <?php if (!$payments): ?>
                <li>Kayıt bulunamadı.</li>
            <?php endif; ?>
        </ul>
    </article>
</section>

<section class="card" style="margin-top:2rem;">
    <header class="card-header"><h2>Sipariş Kalemleri</h2></header>
    <table class="table">
        <thead>
            <tr>
                <th>Ürün</th>
                <th>Adet</th>
                <th>Tutar</th>
                <th>Teslimat</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= e($item['title']); ?></td>
                    <td><?= (int) $item['qty']; ?></td>
                    <td><?= format_currency((float) $item['total_price'], $order['currency']); ?></td>
                    <td>
                        <?php if ($item['delivery_payload']): ?>
                            <pre class="code-block"><?= e(json_encode(json_decode($item['delivery_payload'], true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                        <?php else: ?>
                            Bekliyor
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
