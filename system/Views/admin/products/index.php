<section class="card">
    <header class="card-header">
        <div>
            <h2>Ürünler</h2>
            <p class="text-muted">Premium temalı vitrinde listelenen dijital ürünleri yönetin.</p>
        </div>
        <a class="button-primary" href="/admin/urunler/olustur">Yeni Ürün</a>
    </header>
    <form method="get" class="grid cols-4" style="gap:1rem;margin-bottom:1.5rem;">
        <label class="input-field">
            <span>Arama</span>
            <input type="search" name="q" value="<?= e($_GET['q'] ?? ''); ?>" placeholder="Başlık veya SKU">
        </label>
        <label class="input-field">
            <span>Tür</span>
            <select name="type">
                <option value="">Tümü</option>
                <option value="epin" <?= ($_GET['type'] ?? '') === 'epin' ? 'selected' : ''; ?>>E-PIN</option>
                <option value="license" <?= ($_GET['type'] ?? '') === 'license' ? 'selected' : ''; ?>>Lisans</option>
                <option value="account" <?= ($_GET['type'] ?? '') === 'account' ? 'selected' : ''; ?>>Hesap</option>
            </select>
        </label>
        <label class="input-field">
            <span>Durum</span>
            <select name="status">
                <option value="">Tümü</option>
                <option value="active" <?= ($_GET['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Aktif</option>
                <option value="draft" <?= ($_GET['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>Taslak</option>
                <option value="inactive" <?= ($_GET['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Pasif</option>
            </select>
        </label>
        <div class="input-field" style="align-self:end;">
            <button class="button-secondary" type="submit">Filtrele</button>
        </div>
    </form>
    <table class="table">
        <thead>
            <tr>
                <th>Ürün</th>
                <th>Kategori</th>
                <th>SKU</th>
                <th>Fiyat</th>
                <th>Durum</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td>
                        <div><strong><?= e($product['title']); ?></strong></div>
                        <small><?= strtoupper($product['type']); ?></small>
                    </td>
                    <td><?= e($product['category_name'] ?? '-'); ?></td>
                    <td><?= e($product['sku']); ?></td>
                    <td><?= format_currency((float) $product['price'], $product['currency']); ?></td>
                    <td><span class="badge-soft"><?= e($product['status']); ?></span></td>
                    <td style="text-align:right;">
                        <a class="button-link" href="/admin/urunler/<?= (int) $product['id']; ?>/duzenle">Düzenle</a>
                        <form method="post" action="/admin/urunler/<?= (int) $product['id']; ?>/sil" style="display:inline;">
                            <?= csrf_field(); ?>
                            <button class="button-link" onclick="return confirm('Ürünü silmek istediğinize emin misiniz?');">Sil</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
