<section class="card">
    <header class="card-header"><h2>Destek Talepleri</h2></header>
    <table class="table">
        <thead>
            <tr>
                <th>Konu</th>
                <th>Müşteri</th>
                <th>Durum</th>
                <th>Öncelik</th>
                <th>Tarih</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tickets as $ticket): ?>
                <tr>
                    <td><a href="/admin/destek/<?= (int) $ticket['id']; ?>"><?= e($ticket['subject']); ?></a></td>
                    <td><?= e($ticket['email']); ?></td>
                    <td><span class="badge-soft"><?= e($ticket['status']); ?></span></td>
                    <td><?= e($ticket['priority']); ?></td>
                    <td><?= e(date('d.m.Y H:i', strtotime($ticket['created_at']))); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
