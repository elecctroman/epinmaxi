<?php
namespace System\Controllers\Admin;

use System\Core\DB;
use System\Helpers\Flash;

class SettingsController extends AdminController
{
    protected array $settings = [
        'app.name', 'app.currency', 'app.timezone', 'app.theme.primary', 'app.theme.secondary', 'app.theme.mode',
        'mail.host', 'mail.port', 'mail.username', 'mail.password', 'mail.encryption',
        'payment.default', 'payment.mock.api_key', 'payment.iyzico.key', 'payment.paytr.key', 'payment.stripe.secret',
        'recaptcha.site', 'recaptcha.secret'
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function index(): void
    {
        $this->enforce('manage-settings');
        $values = [];
        foreach ($this->settings as $key) {
            $values[$key] = setting($key);
        }
        $this->render('settings/index', [
            'title' => 'Genel Ayarlar',
            'values' => $values,
        ]);
    }

    public function update(): void
    {
        $this->enforce('manage-settings');
        $stmt = DB::pdo()->prepare('REPLACE INTO settings (`key`,`value`) VALUES (:key,:value)');
        foreach ($this->settings as $key) {
            $value = $_POST[str_replace('.', '_', $key)] ?? null;
            if ($value !== null) {
                $stmt->execute([
                    'key' => $key,
                    'value' => trim($value),
                ]);
            }
        }
        if (!empty($_FILES['logo']['tmp_name'])) {
            $upload = $this->handleFileUpload($_FILES['logo']);
            if ($upload) {
                $stmt->execute(['key' => 'app.logo', 'value' => $upload]);
            }
        }
        $this->audit('update', 'settings', null);
        Flash::set('Ayarlar güncellendi.', 'success');
        $this->redirect('/admin/ayarlar');
    }

    protected function handleFileUpload(array $file): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!in_array($mime, ['image/png', 'image/jpeg', 'image/svg+xml'])) {
            Flash::set('Logo formatı desteklenmiyor.', 'danger');
            $this->redirect('/admin/ayarlar');
        }
        if ($mime !== 'image/svg+xml' && ($file['size'] ?? 0) > 2 * 1024 * 1024) {
            Flash::set('Logo boyutu 2MB sınırını aşamaz.', 'danger');
            $this->redirect('/admin/ayarlar');
        }
        $extension = $mime === 'image/svg+xml' ? 'svg' : pathinfo($file['name'], PATHINFO_EXTENSION);
        $name = 'logo_' . bin2hex(random_bytes(6)) . '.' . $extension;
        $path = __DIR__ . '/../../../public/uploads/' . $name;
        move_uploaded_file($file['tmp_name'], $path);
        return '/uploads/' . $name;
    }
}
