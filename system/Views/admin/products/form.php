<section class="card">
    <header class="card-header">
        <h2><?= $product ? 'Ürün Düzenle' : 'Yeni Ürün'; ?></h2>
    </header>
    <form method="post" enctype="multipart/form-data" action="<?= $product ? '/admin/urunler/' . (int) $product['id'] . '/guncelle' : '/admin/urunler'; ?>" class="grid cols-2" style="gap:1.5rem;">
        <?= csrf_field(); ?>
        <div class="input-field">
            <span>Başlık</span>
            <input type="text" name="title" value="<?= e($product['title'] ?? ''); ?>" required>
        </div>
        <div class="input-field">
            <span>SKU</span>
            <input type="text" name="sku" value="<?= e($product['sku'] ?? ''); ?>" required>
        </div>
        <div class="input-field">
            <span>Slug</span>
            <input type="text" name="slug" value="<?= e($product['slug'] ?? ''); ?>" required>
        </div>
        <div class="input-field">
            <span>Tür</span>
            <select name="type">
                <option value="epin" <?= ($product['type'] ?? '') === 'epin' ? 'selected' : ''; ?>>E-PIN</option>
                <option value="license" <?= ($product['type'] ?? '') === 'license' ? 'selected' : ''; ?>>Lisans</option>
                <option value="account" <?= ($product['type'] ?? '') === 'account' ? 'selected' : ''; ?>>Hesap</option>
            </select>
        </div>
        <div class="input-field">
            <span>Kategori</span>
            <select name="category_id">
                <option value="">Seçiniz</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int) $category['id']; ?>" <?= ($product['category_id'] ?? '') == $category['id'] ? 'selected' : ''; ?>><?= e($category['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="input-field">
            <span>Durum</span>
            <select name="status">
                <option value="draft" <?= ($product['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>Taslak</option>
                <option value="active" <?= ($product['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Aktif</option>
                <option value="inactive" <?= ($product['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Pasif</option>
            </select>
        </div>
        <div class="input-field">
            <span>Stok Politikası</span>
            <select name="stock_policy">
                <option value="track_keys" <?= ($product['stock_policy'] ?? '') === 'track_keys' ? 'selected' : ''; ?>>Anahtar takibi</option>
                <option value="unlimited" <?= ($product['stock_policy'] ?? '') === 'unlimited' ? 'selected' : ''; ?>>Sınırsız</option>
            </select>
        </div>
        <div class="input-field">
            <span>Fiyat</span>
            <input type="number" step="0.01" name="price" value="<?= e($product['price'] ?? '0'); ?>" required>
        </div>
        <div class="input-field">
            <span>İndirimli Fiyat</span>
            <input type="number" step="0.01" name="sale_price" value="<?= e($product['sale_price'] ?? ''); ?>">
        </div>
        <div class="input-field">
            <span>Para Birimi</span>
            <select name="currency">
                <?php foreach (['TRY','USD','EUR'] as $currency): ?>
                    <option value="<?= $currency; ?>" <?= ($product['currency'] ?? 'TRY') === $currency ? 'selected' : ''; ?>><?= $currency; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="input-field" style="grid-column:1 / span 2;">
            <span>Açıklama</span>
            <textarea name="description" rows="4"><?= e($product['description'] ?? ''); ?></textarea>
        </div>
        <div class="input-field">
            <span>Etiketler (virgül)</span>
            <textarea name="tags" rows="3"><?= isset($product['tags']) ? implode(', ', json_decode($product['tags'], true) ?? []) : ''; ?></textarea>
        </div>
        <div class="input-field">
            <span>Öne Çıkanlar (satır satır)</span>
            <textarea name="highlights" rows="3"><?= isset($product['highlights']) ? implode("\n", json_decode($product['highlights'], true) ?? []) : ''; ?></textarea>
        </div>
        <div class="input-field" style="grid-column:1 / span 2;">
            <span>SSS (Soru|Cevap biçiminde her satır)</span>
            <textarea name="faq" rows="4"><?= isset($product['faq']) ? implode("\n", array_map(fn($item) => ($item['question'] ?? '') . '|' . ($item['answer'] ?? ''), json_decode($product['faq'], true) ?? [])) : ''; ?></textarea>
        </div>
        <div class="input-field">
            <span>Kapak Görseli</span>
            <input type="file" name="cover_image" accept="image/png,image/jpeg,image/webp">
            <?php if (!empty($product['cover_image'])): ?><small>Mevcut: <?= e($product['cover_image']); ?></small><?php endif; ?>
        </div>
        <div class="input-field">
            <span>Galeri</span>
            <input type="file" name="gallery[]" accept="image/png,image/jpeg,image/webp" multiple>
        </div>
        <div style="grid-column:1 / span 2; display:flex; gap:1rem;">
            <button class="button-primary" type="submit">Kaydet</button>
            <a class="button-secondary" href="/admin/urunler">Vazgeç</a>
        </div>
    </form>
</section>
