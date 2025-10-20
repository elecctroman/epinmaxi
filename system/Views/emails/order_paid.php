<table style="width:100%;max-width:640px;margin:0 auto;font-family:'Segoe UI',sans-serif;background:#0f172a;color:#f8fafc;padding:32px;border-radius:24px;">
    <tr><td>
        <h2 style="color:#34d399;">Ödemeniz onaylandı</h2>
        <p>Merhaba <?= e($customer['name'] ?? 'Müşterimiz'); ?>,</p>
        <p><strong><?= e($order_no); ?></strong> numaralı siparişiniz için ödemeniz alındı. Dijital ürünleriniz hazırlanıyor.</p>
        <p>Ödeme Yöntemi: <?= e($payment_method ?? ''); ?> · Tutar: <?= format_currency((float) ($total ?? 0), $currency ?? 'TRY'); ?></p>
    </td></tr>
</table>
