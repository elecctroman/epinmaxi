<section class="card">
    <header class="card-header">
        <h2>Ödeme Kayıtları</h2>
    </header>
    <table class="table">
        <thead>
            <tr>
                <th>Sağlayıcı</th>
                <th>Sipariş</th>
                <th>Tutar</th>
                <th>Durum</th>
                <th>Tarih</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($payments as $payment): ?>
                <tr>
                    <td><a href="/admin/odemeler/<?= (int) $payment['id']; ?>"><?= e($payment['provider']); ?></a></td>
                    <td><?= e($payment['order_no']); ?></td>
                    <td><?= format_currency((float) $payment['amount'], $payment['currency']); ?></td>
                    <td><span class="badge-soft"><?= e($payment['status']); ?></span></td>
                    <td><?= e(date('d.m.Y H:i', strtotime($payment['created_at']))); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
