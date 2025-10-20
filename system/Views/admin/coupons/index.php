<section class="grid cols-2" style="gap:1.5rem;">
    <article class="card">
        <header class="card-header"><h2>Kupon Oluştur</h2></header>
        <form method="post" action="/admin/kuponlar" class="grid cols-2" style="gap:1rem;">
            <?= csrf_field(); ?>
            <label class="input-field">
                <span>Kod</span>
                <input type="text" name="code" required>
            </label>
            <label class="input-field">
                <span>Tip</span>
                <select name="type">
                    <option value="percent">Yüzde</option>
                    <option value="fixed">Sabit</option>
                </select>
            </label>
            <label class="input-field">
                <span>Tutar</span>
                <input type="number" step="0.01" name="value" required>
            </label>
            <label class="input-field">
                <span>Max Kullanım</span>
                <input type="number" name="max_uses" value="0">
            </label>
            <label class="input-field">
                <span>Min Sepet</span>
                <input type="number" step="0.01" name="min_subtotal" value="0">
            </label>
            <label class="input-field">
                <span>Max İndirim</span>
                <input type="number" step="0.01" name="max_discount" value="0">
            </label>
            <label class="input-field">
                <span>Kullanıcı Başına</span>
                <input type="number" name="per_user_limit" value="0">
            </label>
            <label class="input-field">
                <span>Başlangıç</span>
                <input type="datetime-local" name="starts_at">
            </label>
            <label class="input-field">
                <span>Bitiş</span>
                <input type="datetime-local" name="ends_at">
            </label>
            <label class="input-field">
                <span>Durum</span>
                <select name="status">
                    <option value="active">Aktif</option>
                    <option value="inactive">Pasif</option>
                </select>
            </label>
            <div style="grid-column:1 / span 2;">
                <button class="button-primary" type="submit">Kaydet</button>
            </div>
        </form>
    </article>
    <article class="card">
        <header class="card-header"><h2>Kuponlar</h2></header>
        <table class="table">
            <thead>
                <tr>
                    <th>Kod</th>
                    <th>Tip</th>
                    <th>Tutar</th>
                    <th>Kullanım</th>
                    <th>Durum</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($coupons as $coupon): ?>
                    <tr>
                        <td><?= e($coupon['code']); ?></td>
                        <td><?= e($coupon['type']); ?></td>
                        <td><?= e($coupon['value']); ?></td>
                        <td><?= (int) $coupon['used_count']; ?>/<?= (int) $coupon['max_uses']; ?></td>
                        <td><?= e($coupon['status']); ?></td>
                        <td>
                            <details>
                                <summary>Düzenle</summary>
                                <form method="post" action="/admin/kuponlar/<?= (int) $coupon['id']; ?>/guncelle" class="grid cols-2" style="gap:0.5rem;margin-top:0.5rem;">
                                    <?= csrf_field(); ?>
                                    <input type="number" step="0.01" name="value" value="<?= e($coupon['value']); ?>">
                                    <select name="type">
                                        <option value="percent" <?= $coupon['type'] === 'percent' ? 'selected' : ''; ?>>Yüzde</option>
                                        <option value="fixed" <?= $coupon['type'] === 'fixed' ? 'selected' : ''; ?>>Sabit</option>
                                    </select>
                                    <input type="number" name="max_uses" value="<?= (int) $coupon['max_uses']; ?>">
                                    <input type="number" step="0.01" name="min_subtotal" value="<?= e($coupon['min_subtotal']); ?>">
                                    <input type="number" step="0.01" name="max_discount" value="<?= e($coupon['max_discount']); ?>">
                                    <input type="number" name="per_user_limit" value="<?= (int) $coupon['per_user_limit']; ?>">
                                    <input type="datetime-local" name="starts_at" value="<?= $coupon['starts_at'] ? date('Y-m-d\TH:i', strtotime($coupon['starts_at'])) : ''; ?>">
                                    <input type="datetime-local" name="ends_at" value="<?= $coupon['ends_at'] ? date('Y-m-d\TH:i', strtotime($coupon['ends_at'])) : ''; ?>">
                                    <select name="status">
                                        <option value="active" <?= $coupon['status'] === 'active' ? 'selected' : ''; ?>>Aktif</option>
                                        <option value="inactive" <?= $coupon['status'] === 'inactive' ? 'selected' : ''; ?>>Pasif</option>
                                    </select>
                                    <div style="grid-column:1 / span 2; display:flex; gap:0.5rem;">
                                        <button class="button-secondary" type="submit">Güncelle</button>
                                        <button class="button-link" form="delete-coupon-<?= (int) $coupon['id']; ?>" onclick="return confirm('Kupon silinsin mi?');">Sil</button>
                                    </div>
                                </form>
                                <form id="delete-coupon-<?= (int) $coupon['id']; ?>" method="post" action="/admin/kuponlar/<?= (int) $coupon['id']; ?>/sil" style="display:none;">
                                    <?= csrf_field(); ?>
                                </form>
                            </details>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </article>
</section>
