<?php
$title = $title ?? ($category['name'] ?? 'Kategori');
?>
<section class="page-heading">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="/">Anasayfa</a>
        <span aria-hidden="true">›</span>
        <span><?= e($category['name']); ?></span>
    </nav>
    <h1><?= e($category['name']); ?></h1>
    <p><?= count($products); ?> sonuç bulundu.</p>
</section>
<section class="catalog">
    <aside class="catalog-filters" aria-label="Filtreler">
        <form method="get" class="filter-form">
            <div class="form-group">
                <label for="filter-type">Ürün tipi</label>
                <select id="filter-type" name="tur">
                    <option value="">Tümü</option>
                    <option value="epin" <?= ($filters['type'] ?? '') === 'epin' ? 'selected' : ''; ?>>E-PIN</option>
                    <option value="license" <?= ($filters['type'] ?? '') === 'license' ? 'selected' : ''; ?>>Lisans</option>
                    <option value="account" <?= ($filters['type'] ?? '') === 'account' ? 'selected' : ''; ?>>Hesap</option>
                </select>
            </div>
            <div class="form-group inline">
                <label for="min">Fiyat (min)</label>
                <input type="number" name="min" id="min" value="<?= e($filters['min'] ?? ''); ?>" step="0.01">
            </div>
            <div class="form-group inline">
                <label for="max">Fiyat (maks)</label>
                <input type="number" name="max" id="max" value="<?= e($filters['max'] ?? ''); ?>" step="0.01">
            </div>
            <div class="form-group">
                <label for="tag">Etiket</label>
                <input type="text" name="etiket" id="tag" value="<?= e($filters['tag'] ?? ''); ?>" placeholder="örn. popüler">
            </div>
            <div class="form-group">
                <label for="sort">Sırala</label>
                <select id="sort" name="sirala">
                    <option value="populer">Popüler</option>
                    <option value="yeni" <?= ($_GET['sirala'] ?? '') === 'yeni' ? 'selected' : ''; ?>>Yeni</option>
                    <option value="fiyat-artan" <?= ($_GET['sirala'] ?? '') === 'fiyat-artan' ? 'selected' : ''; ?>>Fiyat Artan</option>
                    <option value="fiyat-azalan" <?= ($_GET['sirala'] ?? '') === 'fiyat-azalan' ? 'selected' : ''; ?>>Fiyat Azalan</option>
                </select>
            </div>
            <button type="submit" class="button-primary" style="width:100%;">Filtrele</button>
        </form>
    </aside>
    <div class="catalog-results">
        <?php if (empty($products)): ?>
            <div class="card">
                <p>Bu kriterlerde ürün bulunamadı.</p>
            </div>
        <?php else: ?>
            <div class="grid cols-3">
                <?php foreach ($products as $product): ?>
                    <article class="card product">
                        <div class="card-media">
                            <img src="<?= e($product['cover_image']); ?>" alt="<?= e($product['title']); ?>" loading="lazy">
                            <span class="badge"><?= e(strtoupper($product['type'])); ?></span>
                        </div>
                        <div class="card-body">
                            <h3><a href="/urun/<?= e($product['slug']); ?>"><?= e($product['title']); ?></a></h3>
                            <p><?= e(mb_strimwidth($product['description'] ?? '', 0, 100, '…')); ?></p>
                            <div class="price-line">
                                <span class="price"><?= format_currency($product['sale_price'] ?? $product['price'], $product['currency']); ?></span>
                                <?php if ($product['sale_price']): ?>
                                    <span class="price-old"><?= format_currency($product['price'], $product['currency']); ?></span>
                                <?php endif; ?>
                            </div>
                            <p class="stock">Stok: <?= $product['available'] > 9000 ? 'Sınırsız' : $product['available']; ?></p>
                            <div class="card-actions">
                                <a href="/urun/<?= e($product['slug']); ?>" class="button-secondary">İncele</a>
                                <button class="button-primary" data-add-to-cart data-product="<?= e($product['id']); ?>">Sepete ekle</button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
