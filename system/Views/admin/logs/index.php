<section class="card">
    <header class="card-header">
        <h2>Denetim Günlükleri</h2>
        <a class="button-secondary" href="/admin/gunlukler/indir">CSV İndir</a>
    </header>
    <table class="table">
        <thead>
            <tr>
                <th>Tarih</th>
                <th>Kullanıcı</th>
                <th>Eylem</th>
                <th>Nesne</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= e(date('d.m.Y H:i', strtotime($log['created_at']))); ?></td>
                    <td><?= e($log['email'] ?? '-'); ?></td>
                    <td><?= e($log['action']); ?></td>
                    <td><?= e($log['entity']); ?> #<?= e($log['entity_id']); ?></td>
                    <td><?= e($log['ip']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
