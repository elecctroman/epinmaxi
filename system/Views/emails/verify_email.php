<table style="width:100%;max-width:600px;margin:0 auto;font-family:'Segoe UI',sans-serif;background:#0f172a;color:#f8fafc;padding:32px;border-radius:24px;">
    <tr>
        <td style="text-align:center;">
            <h1 style="margin-bottom:0;color:#38bdf8;"><?= e(setting('app.name', 'E-PIN Premium')); ?></h1>
            <p style="margin-top:8px;color:#cbd5f5;">Merhaba <?= e($name); ?>,</p>
        </td>
    </tr>
    <tr>
        <td style="padding:24px;background:#111c44;border-radius:16px;">
            <p style="color:#e2e8f0;">Hesabınızı etkinleştirmek için aşağıdaki butona tıklayın. Bu bağlantı 24 saat boyunca geçerlidir.</p>
            <p style="text-align:center;margin:32px 0;">
                <a href="<?= e($verificationUrl); ?>" style="display:inline-block;padding:14px 28px;background:#38bdf8;color:#0f172a;font-weight:600;border-radius:999px;text-decoration:none;">E-postamı Doğrula</a>
            </p>
            <p style="font-size:12px;color:#94a3b8;">Eğer buton çalışmazsa şu adresi tarayıcınıza yapıştırın:<br><?= e($verificationUrl); ?></p>
        </td>
    </tr>
    <tr>
        <td style="padding-top:24px;text-align:center;color:#64748b;font-size:12px;">
            Bu mesaj size <?= e(setting('app.name', 'E-PIN Premium')); ?> tarafından gönderilmiştir.
        </td>
    </tr>
</table>
