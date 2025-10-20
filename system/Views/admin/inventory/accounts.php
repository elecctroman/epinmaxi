<section class="card">
    <header class="card-header">
        <div>
            <h2>Dijital Hesap Havuzu</h2>
            <p class="text-muted">Kullanıcı adı ve şifreleri şifreli saklanır, görüntülemek için satıra tıklayın.</p>
        </div>
        <form method="get" action="/admin/hesap-havuzu/disariver">
            <input type="hidden" name="product_id" value="<?= e($filters['product_id']); ?>">
            <input type="hidden" name="status" value="<?= e($filters['status']); ?>">
            <button class="button-secondary" type="submit">CSV Dışa Aktar</button>
        </form>
    </header>
    <form method="get" class="grid cols-3" style="gap:1rem;margin-bottom:1.5rem;">
        <label class="input-field">
            <span>Ürün</span>
            <select name="product_id">
                <option value="">Tümü</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?= (int) $product['id']; ?>" <?= ($filters['product_id'] ?? '') == $product['id'] ? 'selected' : ''; ?>><?= e($product['title']); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="input-field">
            <span>Durum</span>
            <select name="status">
                <option value="">Tümü</option>
                <option value="unused" <?= ($filters['status'] ?? '') === 'unused' ? 'selected' : ''; ?>>Beklemede</option>
                <option value="used" <?= ($filters['status'] ?? '') === 'used' ? 'selected' : ''; ?>>Kullanıldı</option>
                <option value="refunded" <?= ($filters['status'] ?? '') === 'refunded' ? 'selected' : ''; ?>>İade</option>
            </select>
        </label>
        <div class="input-field" style="align-self:end;">
            <button class="button-secondary" type="submit">Filtrele</button>
        </div>
    </form>
    <table class="table" data-account-table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Ürün</th>
                <th>Durum</th>
                <th>Oluşturulma</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($accounts as $account): ?>
                <tr data-account="<?= (int) $account['id']; ?>">
                    <td>#<?= (int) $account['id']; ?></td>
                    <td><?= e($account['title']); ?><br><small><?= e($account['sku']); ?></small></td>
                    <td><span class="badge-soft"><?= e($account['status']); ?></span></td>
                    <td><?= e(date('d.m.Y H:i', strtotime($account['created_at']))); ?></td>
                    <td>
                        <form method="post" action="/admin/hesap-havuzu/<?= (int) $account['id']; ?>/sil">
                            <?= csrf_field(); ?>
                            <button class="button-link" onclick="return confirm('Kaydı silmek istiyor musunuz?');">Sil</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div data-account-preview class="card" style="margin-top:1.5rem;display:none;">
        <h3>Hesap Detayı</h3>
        <pre style="background:var(--color-bg-muted); padding:1rem; border-radius:1rem;"></pre>
    </div>
</section>

<section class="card" style="margin-top:2rem;">
    <header class="card-header">
        <div>
            <h2>Toplu Hesap İçe Aktarma</h2>
            <p class="text-muted">CSV/XLSX formatında <code>username</code>, <code>password</code>, <code>meta</code> sütunları.</p>
        </div>
        <a class="button-link" href="/assets/samples/accounts_import_example.csv" download>Örnek Şablon</a>
    </header>
    <form method="post" enctype="multipart/form-data" action="/admin/hesap-havuzu/icerik" class="grid cols-2" style="gap:1rem;">
        <?= csrf_field(); ?>
        <label class="input-field">
            <span>Ürün</span>
            <select name="product_id" required>
                <option value="">Seçiniz</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?= (int) $product['id']; ?>"><?= e($product['title']); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="input-field">
            <span>Dosya</span>
            <input type="file" name="import_file" accept=".csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
        </label>
        <div style="grid-column:1 / span 2;">
            <button class="button-primary" type="submit">İçe Aktar</button>
        </div>
    </form>
</section>
