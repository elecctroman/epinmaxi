<section class="card">
    <header class="card-header">
        <h2>Raporlar</h2>
    </header>
    <form method="get" class="grid cols-3" style="gap:1rem;margin-bottom:1.5rem;">
        <label class="input-field">
            <span>Başlangıç</span>
            <input type="date" name="from" value="<?= e($from); ?>">
        </label>
        <label class="input-field">
            <span>Bitiş</span>
            <input type="date" name="to" value="<?= e($to); ?>">
        </label>
        <div class="input-field" style="align-self:end;">
            <button class="button-secondary" type="submit">Uygula</button>
        </div>
    </form>
    <section class="grid cols-4" style="gap:1rem;">
        <article class="card"><span class="text-muted">Sipariş</span><strong class="stat-value"><?= (int) ($data['summary']['orders'] ?? 0); ?></strong></article>
        <article class="card"><span class="text-muted">Ciro</span><strong class="stat-value"><?= format_currency((float) ($data['summary']['revenue'] ?? 0)); ?></strong></article>
        <article class="card"><span class="text-muted">İndirim</span><strong class="stat-value"><?= format_currency((float) ($data['summary']['discounts'] ?? 0)); ?></strong></article>
        <article class="card"><span class="text-muted">Vergi</span><strong class="stat-value"><?= format_currency((float) ($data['summary']['taxes'] ?? 0)); ?></strong></article>
    </section>
</section>

<section class="grid cols-2" style="gap:1.5rem;margin-top:2rem;">
    <article class="card">
        <header class="card-header"><h2>En Çok Satanlar</h2></header>
        <table class="table">
            <thead><tr><th>Ürün</th><th>Adet</th><th>Ciro</th></tr></thead>
            <tbody>
                <?php foreach ($data['top_products'] as $row): ?>
                    <tr><td><?= e($row['title']); ?></td><td><?= (int) $row['quantity']; ?></td><td><?= format_currency((float) $row['revenue']); ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </article>
    <article class="card">
        <header class="card-header"><h2>Kupon Performansı</h2></header>
        <table class="table">
            <thead><tr><th>Kod</th><th>Kullanım</th><th>İndirim</th></tr></thead>
            <tbody>
                <?php foreach ($data['coupons'] as $row): ?>
                    <tr><td><?= e($row['coupon_code']); ?></td><td><?= (int) $row['uses']; ?></td><td><?= format_currency((float) $row['discount_total']); ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </article>
</section>
