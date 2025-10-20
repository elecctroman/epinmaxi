<table style="width:100%;max-width:640px;margin:0 auto;font-family:'Segoe UI',sans-serif;background:#0f172a;color:#f8fafc;padding:32px;border-radius:24px;">
    <tr><td>
        <h2 style="color:#facc15;">Dijital teslimat hazır</h2>
        <p>Merhaba <?= e($customer['name'] ?? 'Müşterimiz'); ?>,</p>
        <p><strong><?= e($order_no); ?></strong> numaralı siparişiniz başarıyla teslim edildi. Anahtar veya hesap bilgilerinize <a href="<?= e($order_url ?? '#'); ?>" style="color:#38bdf8;">panelinizden</a> erişebilirsiniz.</p>
        <p>İyi oyunlar!</p>
    </td></tr>
</table>
