<?php
$title = $title ?? 'Premium Dijital Kod Mağazası';
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
?>
<section class="hero">
    <div class="hero-copy">
        <h1>Dijital dünyaya anında erişim.</h1>
        <p>Oyun kodları, lisanslar ve abonelikleri premium tema ile keşfedin. Güvenli ödeme, anında teslimat.</p>
        <div class="hero-actions">
            <a href="/kategori/oyun-kodlari" class="button-primary">Katalogu keşfet</a>
            <a href="/odeme" class="button-secondary">Sepete Git</a>
        </div>
        <ul class="hero-stats" role="list">
            <li><strong>⚡ Anında teslim</strong> <span>Ödeme sonrası otomatik anahtar dağıtımı</span></li>
            <li><strong>🛡️ Güvenli</strong> <span>2FA, SSL ve şifreli veri saklama</span></li>
            <li><strong>🌍 Çoklu dil</strong> <span>TRY, USD ve EUR desteği</span></li>
        </ul>
    </div>
    <div class="hero-card" aria-hidden="true">
        <div class="card badge">
            <span>Yeni kampanya</span>
            <strong>%15 hoş geldin indirimi</strong>
            <p>HOSGELDIN kuponuyla hemen deneyin.</p>
        </div>
    </div>
</section>
<section class="category-carousel" aria-labelledby="categories-title">
    <div class="section-heading">
        <h2 id="categories-title">Kategoriler</h2>
        <a href="/kategori/oyun-kodlari" class="link">Tümünü Gör</a>
    </div>
    <div class="carousel">
        <?php foreach ($categories as $cat): ?>
            <a href="/kategori/<?= e($cat['slug']); ?>" class="chip">#<?= e($cat['name']); ?></a>
        <?php endforeach; ?>
    </div>
</section>
<section class="featured" aria-labelledby="featured-title">
    <div class="section-heading">
        <h2 id="featured-title">Öne çıkan ürünler</h2>
        <p>Popüler oyun kodları ve abonelikler.</p>
    </div>
    <div class="grid cols-3">
        <?php foreach ($featured as $product): ?>
            <article class="card product" data-product-id="<?= e($product['id']); ?>">
                <div class="card-media">
                    <img src="<?= e($product['cover_image']); ?>" alt="<?= e($product['title']); ?>" loading="lazy">
                    <span class="badge badge-primary">Anında teslim</span>
                </div>
                <div class="card-body">
                    <h3><?= e($product['title']); ?></h3>
                    <p><?= e(mb_strimwidth($product['description'] ?? '', 0, 120, '…')); ?></p>
                    <div class="price-line">
                        <span class="price"><?= format_currency($product['sale_price'] ?? $product['price'], $product['currency']); ?></span>
                        <?php if ($product['sale_price']): ?>
                            <span class="price-old"><?= format_currency($product['price'], $product['currency']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="card-actions">
                        <a href="/urun/<?= e($product['slug']); ?>" class="button-secondary">İncele</a>
                        <button class="button-primary" data-add-to-cart data-product="<?= e($product['id']); ?>">Sepete ekle</button>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php if (!empty($latestOrders)): ?>
<section class="orders" aria-labelledby="recent-orders">
    <div class="section-heading">
        <h2 id="recent-orders">Son siparişlerim</h2>
    </div>
    <div class="card">
        <table class="table">
            <thead>
                <tr>
                    <th>Sipariş</th>
                    <th>Tarih</th>
                    <th>Tutar</th>
                    <th>Durum</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($latestOrders as $order): ?>
                    <tr>
                        <td><a href="/siparis/<?= e($order['order_no']); ?>"><?= e($order['order_no']); ?></a></td>
                        <td><?= e(date('d.m.Y H:i', strtotime($order['created_at']))); ?></td>
                        <td><?= format_currency($order['grand_total'], $order['currency']); ?></td>
                        <td><span class="badge"><?= e($order['status']); ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php endif; ?>
<section class="seo-text">
    <div class="card">
        <h2>Schema.org Ürün İşaretleme</h2>
        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "Store",
            "name": "<?= e(setting('app.name', 'E-PIN Premium')); ?>",
            "url": "<?= e($scheme); ?>://<?= e($_SERVER['HTTP_HOST'] ?? 'localhost'); ?>",
            "servesCuisine": "Digital",
            "makesOffer": {
                "@type": "AggregateOffer",
                "lowPrice": "<?= !empty($featured) ? min(array_column($featured, 'price')) : 0; ?>",
                "priceCurrency": "TRY"
            }
        }
        </script>
        <p>E-PIN Premium, dijital anahtar ve lisans satışında güvenilirliğin adresidir. İyileştirilmiş SEO, açık API'ler, ödeme ağ geçidi entegrasyonları ve otomatik teslimat sistemiyle güçlü bir temel sunar.</p>
    </div>
</section>
