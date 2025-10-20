<section class="card">
    <header class="card-header">
        <div>
            <h2><?= e($ticket['subject']); ?></h2>
            <p class="text-muted"><?= e($ticket['email']); ?> · <?= e($ticket['status']); ?> · Öncelik: <?= e($ticket['priority']); ?></p>
        </div>
        <form method="post" action="/admin/destek/<?= (int) $ticket['id']; ?>/kapat">
            <?= csrf_field(); ?>
            <button class="button-secondary">Talebi Kapat</button>
        </form>
    </header>
    <div class="messages" style="display:grid;gap:1rem;">
        <?php foreach ($messages as $message): ?>
            <article class="card" style="background:var(--color-bg-muted);">
                <header style="display:flex;justify-content:space-between;align-items:center;">
                    <strong><?= e($message['email'] ?? 'Sistem'); ?></strong>
                    <small><?= e(date('d.m.Y H:i', strtotime($message['created_at']))); ?></small>
                </header>
                <div><?= sanitize_html($message['message']); ?></div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="card" style="margin-top:2rem;">
    <header class="card-header"><h2>Yanıtla</h2></header>
    <form method="post" action="/admin/destek/<?= (int) $ticket['id']; ?>/yanit" class="grid" style="gap:1rem;">
        <?= csrf_field(); ?>
        <textarea name="message" rows="4" required></textarea>
        <button class="button-primary" type="submit">Yanıt Gönder</button>
    </form>
</section>
