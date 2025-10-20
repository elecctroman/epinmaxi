<section class="card">
    <header class="card-header">
        <h2><?= e($payment['provider']); ?> İşlemi</h2>
    </header>
    <dl class="meta-grid">
        <div><dt>Sipariş</dt><dd><?= e($payment['order_no']); ?></dd></div>
        <div><dt>Müşteri</dt><dd><?= e($payment['email']); ?></dd></div>
        <div><dt>Tutar</dt><dd><?= format_currency((float) $payment['amount'], $payment['currency']); ?></dd></div>
        <div><dt>Durum</dt><dd><?= e($payment['status']); ?></dd></div>
        <div><dt>Sağlayıcı İşlem ID</dt><dd><?= e($payment['provider_txn_id'] ?? '-'); ?></dd></div>
        <div><dt>Tarih</dt><dd><?= e(date('d.m.Y H:i', strtotime($payment['created_at']))); ?></dd></div>
    </dl>
</section>

<section class="card" style="margin-top:2rem;">
    <header class="card-header"><h2>Webhook Günlükleri</h2></header>
    <table class="table">
        <thead>
            <tr>
                <th>Tarih</th>
                <th>İmza</th>
                <th>Payload</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($webhooks as $log): ?>
                <tr>
                    <td><?= e(date('d.m.Y H:i', strtotime($log['created_at']))); ?></td>
                    <td><?= $log['signature_valid'] ? 'Geçerli' : 'Geçersiz'; ?></td>
                    <td><pre class="code-block"><?= e(json_encode(json_decode($log['payload'], true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$webhooks): ?>
                <tr><td colspan="3">Webhook kaydı bulunamadı.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</section>
