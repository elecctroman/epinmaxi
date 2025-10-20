<?php
$title = $title ?? 'Destek Taleplerim';
?>
<section class="page-heading">
    <h1>Destek Taleplerim</h1>
    <p>Yeni bir talep oluşturabilir veya mevcut taleplerinizi görüntüleyebilirsiniz.</p>
</section>
<section class="card">
    <h2>Yeni Talep Oluştur</h2>
    <form method="post" action="/destek">
        <?= csrf_field(); ?>
        <div class="form-group">
            <label for="ticket-subject">Konu</label>
            <input type="text" id="ticket-subject" name="subject" required>
        </div>
        <div class="form-group">
            <label for="ticket-message">Mesaj</label>
            <textarea id="ticket-message" name="message" required></textarea>
        </div>
        <div class="form-group">
            <label for="ticket-priority">Öncelik</label>
            <select id="ticket-priority" name="priority">
                <option value="normal">Normal</option>
                <option value="high">Yüksek</option>
            </select>
        </div>
        <button type="submit" class="button-primary">Talep Gönder</button>
    </form>
</section>
<section class="card">
    <h2>Talep Geçmişi</h2>
    <ul class="ticket-list">
        <?php foreach ($tickets as $ticket): ?>
            <li>
                <div>
                    <a href="/destek/<?= e($ticket['id']); ?>"><?= e($ticket['subject']); ?></a>
                    <p><?= e(date('d.m.Y H:i', strtotime($ticket['created_at']))); ?></p>
                </div>
                <span class="badge"><?= e($ticket['status']); ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
