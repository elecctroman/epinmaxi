<?php
$title = 'Premium Dijital Kod Mağazası';
?>
<section class="hero" style="display:grid;gap:2rem;align-items:center;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));">
    <div>
        <h1 style="font-size:clamp(2.5rem,4vw,3.25rem);margin-bottom:1rem;">Dijital dünyaya anında erişim.</h1>
        <p style="color:var(--color-text-muted);font-size:1.1rem;max-width:42ch;">E-PIN, oyun kodu ve aboneliklerinizi güvenle satın alın. Anında teslimat, 7/24 destek.</p>
        <a href="/kategori/dijital-kodlar" class="button-primary" style="margin-top:1.5rem;">Ürünleri keşfet</a>
    </div>
    <div class="card" style="background:linear-gradient(135deg,rgba(91,33,182,0.12),rgba(249,115,22,0.08));">
        <ul style="list-style:none;padding:0;margin:0;display:grid;gap:1rem;">
            <li>
                <strong>⚡ Anında Teslimat</strong>
                <p style="margin:0;color:var(--color-text-muted);">Satın alır almaz hesabınızda.</p>
            </li>
            <li>
                <strong>🛡️ Güvenli Alışveriş</strong>
                <p style="margin:0;color:var(--color-text-muted);">3D Secure, 2FA ve güçlü şifreleme.</p>
            </li>
            <li>
                <strong>🌍 Çoklu Dil & Para Birimi</strong>
                <p style="margin:0;color:var(--color-text-muted);">TRY, USD, EUR desteklenir.</p>
            </li>
        </ul>
    </div>
</section>
<section style="margin-top:3rem;">
    <h2>Öne Çıkan Ürünler</h2>
    <div class="grid cols-3">
        <?php foreach (($featured ?? []) as $product): ?>
            <article class="card" aria-label="<?= e($product['title']); ?>">
                <h3><?= e($product['title']); ?></h3>
                <p style="color:var(--color-text-muted);"><?= e($product['description']); ?></p>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-top:1rem;">
                    <strong><?= number_format($product['price'], 2); ?> <?= e($product['currency']); ?></strong>
                    <a href="/urun/<?= e($product['slug']); ?>" class="button-primary" style="padding:0.5rem 1.25rem;">İncele</a>
                </div>
            </article>
        <?php endforeach; ?>
        <?php if (empty($featured)): ?>
            <p>Demo ürünleri kurulum sonrası burada görüntülenir.</p>
        <?php endif; ?>
    </div>
</section>
