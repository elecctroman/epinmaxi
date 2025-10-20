<section class="card">
    <header class="card-header">
        <div>
            <h2>Siparişler</h2>
            <p class="text-muted">Dijital teslimatlar, ödemeler ve iadeleri takip edin.</p>
        </div>
    </header>
    <form method="get" class="grid cols-4" style="gap:1rem;margin-bottom:1.5rem;">
        <label class="input-field">
            <span>Arama</span>
            <input type="search" name="q" value="<?= e($_GET['q'] ?? ''); ?>" placeholder="Sipariş no veya e-posta">
        </label>
        <label class="input-field">
            <span>Sipariş Durumu</span>
            <select name="status">
                <option value="">Tümü</option>
                <?php foreach (['new','processing','completed','cancelled'] as $status): ?>
                    <option value="<?= $status; ?>" <?= ($_GET['status'] ?? '') === $status ? 'selected' : ''; ?>><?= ucfirst($status); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="input-field">
            <span>Ödeme Durumu</span>
            <select name="payment_status">
                <option value="">Tümü</option>
                <?php foreach (['pending','paid','failed','refunded'] as $status): ?>
                    <option value="<?= $status; ?>" <?= ($_GET['payment_status'] ?? '') === $status ? 'selected' : ''; ?>><?= ucfirst($status); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <div class="input-field" style="align-self:end;">
            <button class="button-secondary" type="submit">Filtrele</button>
        </div>
    </form>
    <table class="table">
        <thead>
            <tr>
                <th>Sipariş</th>
                <th>Müşteri</th>
                <th>Tutar</th>
                <th>Sipariş Durumu</th>
                <th>Ödeme</th>
                <th>Tarih</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td><a href="/admin/siparisler/<?= (int) $order['id']; ?>"><?= e($order['order_no']); ?></a></td>
                    <td><?= e($order['email']); ?></td>
                    <td><?= format_currency((float) $order['grand_total'], $order['currency']); ?></td>
                    <td><span class="badge-soft"><?= e($order['status']); ?></span></td>
                    <td><span class="badge-soft"><?= e($order['payment_status']); ?></span></td>
                    <td><?= e(date('d.m.Y H:i', strtotime($order['created_at']))); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
