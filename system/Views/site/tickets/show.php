<?php
$title = $title ?? 'Destek Talebi';
?>
<section class="page-heading">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="/destek">Destek</a>
        <span aria-hidden="true">›</span>
        <span><?= e($ticket['subject']); ?></span>
    </nav>
    <h1><?= e($ticket['subject']); ?></h1>
    <p>Durum: <span class="badge"><?= e($ticket['status']); ?></span></p>
</section>
<section class="card">
    <h2>Mesajlar</h2>
    <ul class="message-thread">
        <?php foreach ($messages as $message): ?>
            <li>
                <div class="meta">
                    <strong><?= e($message['name'] ?? 'Destek'); ?></strong>
                    <span><?= e(date('d.m.Y H:i', strtotime($message['created_at']))); ?></span>
                </div>
                <p><?= nl2br(e($message['message'])); ?></p>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
<section class="card">
    <h2>Yanıt Gönder</h2>
    <form method="post" action="/destek/<?= e($ticket['id']); ?>">
        <?= csrf_field(); ?>
        <div class="form-group">
            <label for="reply-message">Mesaj</label>
            <textarea id="reply-message" name="message" required></textarea>
        </div>
        <button type="submit" class="button-primary">Gönder</button>
    </form>
</section>
