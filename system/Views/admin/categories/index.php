<section class="grid cols-2" style="gap:1.5rem;">
    <article class="card">
        <header class="card-header"><h2>Kategori Ekle</h2></header>
        <form method="post" action="/admin/kategoriler" class="grid cols-2" style="gap:1rem;">
            <?= csrf_field(); ?>
            <label class="input-field">
                <span>Ad</span>
                <input type="text" name="name" required>
            </label>
            <label class="input-field">
                <span>Slug</span>
                <input type="text" name="slug" required>
            </label>
            <label class="input-field">
                <span>Üst Kategori</span>
                <select name="parent_id">
                    <option value="">Yok</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id']; ?>"><?= e($category['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="input-field">
                <span>Sıra</span>
                <input type="number" name="sort" value="0">
            </label>
            <div style="grid-column:1 / span 2;">
                <button class="button-primary" type="submit">Kaydet</button>
            </div>
        </form>
    </article>
    <article class="card">
        <header class="card-header"><h2>Mevcut Kategoriler</h2></header>
        <table class="table">
            <thead>
                <tr>
                    <th>Ad</th>
                    <th>Slug</th>
                    <th>Üst</th>
                    <th>Sıra</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td><?= e($category['name']); ?></td>
                        <td><?= e($category['slug']); ?></td>
                        <td><?= $category['parent_id'] ? e(array_values(array_filter($categories, fn($c) => $c['id'] == $category['parent_id']))[0]['name'] ?? '-') : '-'; ?></td>
                        <td><?= (int) $category['sort']; ?></td>
                        <td>
                            <details>
                                <summary>Düzenle</summary>
                                <form method="post" action="/admin/kategoriler/<?= (int) $category['id']; ?>/guncelle" class="grid cols-2" style="gap:0.5rem;margin-top:0.5rem;">
                                    <?= csrf_field(); ?>
                                    <input type="text" name="name" value="<?= e($category['name']); ?>" required>
                                    <input type="text" name="slug" value="<?= e($category['slug']); ?>" required>
                                    <select name="parent_id">
                                        <option value="">Yok</option>
                                        <?php foreach ($categories as $parent): if ($parent['id'] == $category['id']) continue; ?>
                                            <option value="<?= (int) $parent['id']; ?>" <?= $category['parent_id'] == $parent['id'] ? 'selected' : ''; ?>><?= e($parent['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="number" name="sort" value="<?= (int) $category['sort']; ?>">
                                    <div style="grid-column:1 / span 2; display:flex; gap:0.5rem;">
                                        <button class="button-secondary" type="submit">Güncelle</button>
                                        <button class="button-link" form="delete-cat-<?= (int) $category['id']; ?>" onclick="return confirm('Kategoriyi silmek istiyor musunuz?');">Sil</button>
                                    </div>
                                </form>
                            </details>
                            <form id="delete-cat-<?= (int) $category['id']; ?>" method="post" action="/admin/kategoriler/<?= (int) $category['id']; ?>/sil" style="display:none;">
                                <?= csrf_field(); ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </article>
</section>
