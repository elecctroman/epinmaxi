<table style="width:100%;max-width:640px;margin:0 auto;font-family:'Segoe UI',sans-serif;background:#0f172a;color:#f8fafc;padding:32px;border-radius:24px;">
    <tr>
        <td>
            <h2 style="color:#38bdf8;">Sipariş alındı</h2>
            <p>Merhaba <?= e($customer['name'] ?? 'Müşterimiz'); ?>,</p>
            <p><strong><?= e($order_no); ?></strong> numaralı siparişiniz başarıyla oluşturuldu. Ödeme doğrulaması sonrası dijital teslimatlarınız hesabınıza tanımlanacaktır.</p>
            <p>Tutar: <strong><?= format_currency((float) ($total ?? 0), $currency ?? 'TRY'); ?></strong></p>
            <p style="margin-top:24px;font-size:12px;color:#94a3b8;">Bu mesaj otomatik olarak gönderilmiştir.</p>
        </td>
    </tr>
</table>
