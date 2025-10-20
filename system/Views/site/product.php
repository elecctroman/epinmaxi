<?php
$title = $title ?? $product['title'];
$gallery = $product['gallery'] ? json_decode($product['gallery'], true) : [];
$faq = $product['faq'] ? json_decode($product['faq'], true) : [];
$highlights = $product['highlights'] ? json_decode($product['highlights'], true) : [];
$tags = $product['tags'] ? json_decode($product['tags'], true) : [];
?>
<section class="product-detail">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="/">Anasayfa</a>
        <?php if (!empty($product['category_slug'])): ?>
            <span aria-hidden="true">›</span>
            <a href="/kategori/<?= e($product['category_slug']); ?>"><?= e($product['category_name']); ?></a>
        <?php endif; ?>
        <span aria-hidden="true">›</span>
        <span><?= e($product['title']); ?></span>
    </nav>
    <div class="product-grid">
        <div class="product-gallery">
            <img src="<?= e($product['cover_image']); ?>" alt="<?= e($product['title']); ?>" class="main-image">
            <?php if ($gallery): ?>
                <div class="thumbs">
                    <?php foreach ($gallery as $image): ?>
                        <img src="<?= e($image); ?>" alt="<?= e($product['title']); ?> görsel" loading="lazy">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="product-summary">
            <h1><?= e($product['title']); ?></h1>
            <p class="product-type badge badge-primary"><?= e(strtoupper($product['type'])); ?> • Anında teslim</p>
            <div class="price-line">
                <span class="price"><?= format_currency($product['sale_price'] ?? $product['price'], $product['currency']); ?></span>
                <?php if ($product['sale_price']): ?>
                    <span class="price-old"><?= format_currency($product['price'], $product['currency']); ?></span>
                <?php endif; ?>
            </div>
            <ul class="highlight-list">
                <?php foreach ($highlights as $item): ?>
                    <li><?= e($item); ?></li>
                <?php endforeach; ?>
            </ul>
            <p class="stock">Stok durumu: <?= $available > 9000 ? 'Sınırsız' : $available . ' adet'; ?></p>
            <form method="post" action="/sepete-ekle" class="add-to-cart" data-product-form>
                <?= csrf_field(); ?>
                <input type="hidden" name="product_id" value="<?= e($product['id']); ?>">
                <label for="qty">Adet</label>
                <input type="number" name="qty" id="qty" value="1" min="1">
                <button type="submit" class="button-primary">Sepete ekle</button>
            </form>
            <div class="share">
                <span>Paylaş:</span>
                <a href="https://twitter.com/intent/tweet?url=<?= urlencode('https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/urun/' . $product['slug']); ?>" aria-label="Twitter">🐦</a>
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode('https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/urun/' . $product['slug']); ?>" aria-label="Facebook">📘</a>
            </div>
            <?php if ($tags): ?>
                <div class="product-tags">
                    <?php foreach ($tags as $tag): ?>
                        <a href="/kategori/<?= e($product['category_slug']); ?>?etiket=<?= urlencode($tag); ?>" class="chip">#<?= e($tag); ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="product-tabs" data-tabs>
        <button type="button" data-tab-target="desc" class="active">Açıklama</button>
        <button type="button" data-tab-target="faq">SSS</button>
    </div>
    <div class="tab-content" id="desc" data-tab-panel>
        <article class="card">
            <?= sanitize_html($product['description']); ?>
        </article>
    </div>
    <div class="tab-content" id="faq" data-tab-panel hidden>
        <div class="card">
            <?php if ($faq): ?>
                <dl class="faq-list">
                    <?php foreach ($faq as $entry): ?>
                        <dt><?= e($entry['question']); ?></dt>
                        <dd><?= e($entry['answer']); ?></dd>
                    <?php endforeach; ?>
                </dl>
            <?php else: ?>
                <p>Bu ürün için sık sorulan sorular henüz eklenmedi.</p>
            <?php endif; ?>
        </div>
    </div>
</section>
<section class="related" aria-labelledby="related-products">
    <div class="section-heading">
        <h2 id="related-products">İlgili ürünler</h2>
    </div>
    <div class="grid cols-4">
        <?php foreach ($related as $item): ?>
            <article class="card product">
                <div class="card-body">
                    <h3><a href="/urun/<?= e($item['slug']); ?>"><?= e($item['title']); ?></a></h3>
                    <p><?= format_currency($item['sale_price'] ?? $item['price'], $item['currency']); ?></p>
                    <a href="/urun/<?= e($item['slug']); ?>" class="button-secondary">İncele</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
