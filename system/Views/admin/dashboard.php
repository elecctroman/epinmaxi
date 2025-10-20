<?php $title = 'Gösterge Paneli'; ?>
<section class="grid cols-3" style="gap:1.5rem;">
    <article class="card">
        <span class="text-muted">Toplam Sipariş</span>
        <strong class="stat-value"><?= (int) ($summary['orders'] ?? 0); ?></strong>
    </article>
    <article class="card">
        <span class="text-muted">Brüt Ciro</span>
        <strong class="stat-value"><?= format_currency((float) ($summary['revenue'] ?? 0)); ?></strong>
    </article>
    <article class="card">
        <span class="text-muted">Bekleyen Destek</span>
        <strong class="stat-value"><?= (int) $pendingTickets; ?></strong>
    </article>
</section>

<section class="grid cols-2" style="gap:1.5rem;margin-top:2rem;">
    <article class="card" style="min-height:320px;">
        <header class="card-header">
            <h2>Satış Grafiği</h2>
            <span class="text-muted">Son 14 Gün</span>
        </header>
        <canvas data-chart-line='<?= json_encode($chartData ?? []); ?>' height="240"></canvas>
    </article>
    <article class="card">
        <header class="card-header">
            <h2>Stok Uyarıları</h2>
        </header>
        <ul class="list -divided">
            <?php if ($stockAlerts): foreach ($stockAlerts as $alert): ?>
                <li>
                    <strong><?= e($alert['title']); ?></strong>
                    <small><?= e(strtoupper($alert['type'])); ?> · <?= (int) $alert['remaining']; ?> adet</small>
                </li>
            <?php endforeach; else: ?>
                <li>Tüm stoklar güvenli seviyede.</li>
            <?php endif; ?>
        </ul>
    </article>
</section>

<section class="card" style="margin-top:2rem;">
    <header class="card-header">
        <h2>Son Siparişler</h2>
    </header>
    <table class="table">
        <thead>
            <tr>
                <th>No</th>
                <th>Müşteri</th>
                <th>Tutar</th>
                <th>Durum</th>
                <th>Tarih</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recentOrders as $recent): ?>
                <tr>
                    <td><a href="/admin/siparisler/<?= (int) $recent['id']; ?>"><?= e($recent['order_no']); ?></a></td>
                    <td><?= e($recent['email']); ?></td>
                    <td><?= format_currency((float) $recent['grand_total'], $recent['currency'] ?? 'TRY'); ?></td>
                    <td><span class="badge-soft"><?= e($recent['status']); ?></span></td>
                    <td><?= e(date('d.m.Y H:i', strtotime($recent['created_at']))); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
