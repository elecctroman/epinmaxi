<section class="grid cols-2" style="gap:1.5rem;">
    <article class="card">
        <header class="card-header"><h2>Yeni Kullanıcı</h2></header>
        <form method="post" action="/admin/kullanicilar" class="grid cols-2" style="gap:1rem;">
            <?= csrf_field(); ?>
            <label class="input-field">
                <span>Ad Soyad</span>
                <input type="text" name="name" required>
            </label>
            <label class="input-field">
                <span>E-posta</span>
                <input type="email" name="email" required>
            </label>
            <label class="input-field">
                <span>Rol</span>
                <select name="role">
                    <option value="customer">Müşteri</option>
                    <option value="staff">Yetkili</option>
                    <option value="admin">Yönetici</option>
                </select>
            </label>
            <label class="input-field">
                <span>Parola</span>
                <input type="password" name="password" required>
            </label>
            <div style="grid-column:1 / span 2;">
                <button class="button-primary" type="submit">Oluştur</button>
            </div>
        </form>
    </article>
    <article class="card">
        <header class="card-header"><h2>IP Engellemeleri</h2></header>
        <ul class="list -divided">
            <?php foreach ($blockedIps as $blocked): ?>
                <li>
                    <strong><?= e($blocked['ip']); ?></strong>
                    <div><?= e($blocked['reason']); ?></div>
                    <small><?= e(date('d.m.Y H:i', strtotime($blocked['created_at']))); ?></small>
                    <form method="post" action="/admin/kullanicilar/ip-engel/<?= (int) $blocked['id']; ?>/kaldir">
                        <?= csrf_field(); ?>
                        <button class="button-link">Engeli Kaldır</button>
                    </form>
                </li>
            <?php endforeach; ?>
            <?php if (!$blockedIps): ?>
                <li>Aktif engel yok.</li>
            <?php endif; ?>
        </ul>
    </article>
</section>

<section class="card" style="margin-top:2rem;">
    <header class="card-header"><h2>Kullanıcı Listesi</h2></header>
    <table class="table">
        <thead>
            <tr>
                <th>Ad</th>
                <th>E-posta</th>
                <th>Rol</th>
                <th>Durum</th>
                <th>Son Giriş</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= e($user['name']); ?></td>
                    <td><?= e($user['email']); ?></td>
                    <td><?= e($user['role']); ?></td>
                    <td><?= e($user['status']); ?></td>
                    <td><?= $user['last_login_at'] ? e(date('d.m.Y H:i', strtotime($user['last_login_at']))) : '-'; ?></td>
                    <td>
                        <details>
                            <summary>Düzenle</summary>
                            <form method="post" action="/admin/kullanicilar/<?= (int) $user['id']; ?>/guncelle" style="display:grid;gap:0.75rem;margin-top:0.75rem;">
                                <?= csrf_field(); ?>
                                <label>Rol
                                    <select name="role">
                                        <option value="customer" <?= $user['role'] === 'customer' ? 'selected' : ''; ?>>Müşteri</option>
                                        <option value="staff" <?= $user['role'] === 'staff' ? 'selected' : ''; ?>>Yetkili</option>
                                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : ''; ?>>Yönetici</option>
                                    </select>
                                </label>
                                <label>Durum
                                    <select name="status">
                                        <option value="active" <?= $user['status'] === 'active' ? 'selected' : ''; ?>>Aktif</option>
                                        <option value="banned" <?= $user['status'] === 'banned' ? 'selected' : ''; ?>>Yasaklı</option>
                                    </select>
                                </label>
                                <label>İzinler (her satır bir yetki)
                                    <textarea name="permissions" rows="3"><?= e(implode("\n", json_decode($user['permissions_json'] ?? '[]', true) ?? [])); ?></textarea>
                                </label>
                                <div style="display:flex; gap:0.5rem;">
                                    <button class="button-secondary" type="submit">Kaydet</button>
                                    <button class="button-link" form="twofa-reset-<?= (int) $user['id']; ?>">2FA Sıfırla</button>
                                </div>
                            </form>
                            <form id="twofa-reset-<?= (int) $user['id']; ?>" method="post" action="/admin/kullanicilar/<?= (int) $user['id']; ?>/iki-adim-sifirla" style="display:none;">
                                <?= csrf_field(); ?>
                            </form>
                        </details>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="card" style="margin-top:2rem;">
    <header class="card-header"><h2>Giriş Günlükleri</h2></header>
    <table class="table">
        <thead>
            <tr>
                <th>E-posta</th>
                <th>Durum</th>
                <th>IP</th>
                <th>Tarih</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($loginLogs as $log): ?>
                <tr>
                    <td><?= e($log['email'] ?? $log['user_email']); ?></td>
                    <td><?= e($log['status']); ?></td>
                    <td><?= e($log['ip']); ?></td>
                    <td><?= e(date('d.m.Y H:i', strtotime($log['created_at']))); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
