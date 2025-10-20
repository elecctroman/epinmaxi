<section class="card">
    <header class="card-header"><h2>Cüzdanlar</h2></header>
    <table class="table">
        <thead>
            <tr>
                <th>Kullanıcı</th>
                <th>Bakiye</th>
                <th>Oluşturulma</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($wallets as $wallet): ?>
                <tr>
                    <td><?= e($wallet['email']); ?></td>
                    <td><?= format_currency((float) $wallet['balance']); ?></td>
                    <td><?= e(date('d.m.Y H:i', strtotime($wallet['created_at']))); ?></td>
                    <td>
                        <details>
                            <summary>Düzenle</summary>
                            <form method="post" action="/admin/cuzdanlar/<?= (int) $wallet['id']; ?>/islem" class="grid cols-2" style="gap:0.5rem;margin-top:0.5rem;">
                                <?= csrf_field(); ?>
                                <select name="type">
                                    <option value="credit">Yükleme</option>
                                    <option value="debit">Düş</option>
                                </select>
                                <input type="number" step="0.01" name="amount" required>
                                <textarea name="note" rows="2" placeholder="Açıklama"></textarea>
                                <div style="grid-column:1 / span 2;">
                                    <button class="button-secondary" type="submit">Uygula</button>
                                </div>
                            </form>
                        </details>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="card" style="margin-top:2rem;">
    <header class="card-header"><h2>Son İşlemler</h2></header>
    <table class="table">
        <thead>
            <tr>
                <th>Kullanıcı</th>
                <th>Tip</th>
                <th>Tutar</th>
                <th>Not</th>
                <th>Tarih</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions as $txn): ?>
                <tr>
                    <td><?= e($txn['email']); ?></td>
                    <td><?= e($txn['type']); ?></td>
                    <td><?= format_currency((float) $txn['amount']); ?></td>
                    <td><?= e($txn['note']); ?></td>
                    <td><?= e(date('d.m.Y H:i', strtotime($txn['created_at']))); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
