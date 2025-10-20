<?php
namespace System\Controllers\Admin;

use System\Core\Cache;
use System\Core\DB;
use System\Helpers\Flash;

class SettingsController extends AdminController
{
    protected array $settings = [
        'app.name', 'app.currency', 'app.timezone', 'app.theme.primary', 'app.theme.secondary', 'app.theme.mode',
        'mail.host', 'mail.port', 'mail.username', 'mail.password', 'mail.encryption', 'mail.from',
        'payment.default', 'payment.mock.api_key', 'payment.iyzico.key', 'payment.paytr.key', 'payment.stripe.secret',
        'api.token_hash', 'api.token_hint', 'api.token_subject',
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
        $skipKeys = ['api.token_hash', 'api.token_hint'];
        foreach ($this->settings as $key) {
            if (in_array($key, $skipKeys, true)) {
                continue;
            }
            $value = $_POST[str_replace('.', '_', $key)] ?? null;
            if ($value !== null) {
                $stmt->execute([
                    'key' => $key,
                    'value' => trim($value),
                ]);
            }
        }
        $newToken = trim($_POST['api_token_new'] ?? '');
        if ($newToken !== '') {
            $stmt->execute(['key' => 'api.token_hash', 'value' => hash('sha256', $newToken)]);
            $stmt->execute(['key' => 'api.token_hint', 'value' => substr($newToken, 0, 4) . str_repeat('*', max(0, strlen($newToken) - 4))]);
        }
        if (!empty($_FILES['logo']['tmp_name'])) {
            $upload = $this->handleFileUpload($_FILES['logo']);
            if ($upload) {
                $stmt->execute(['key' => 'app.logo', 'value' => $upload]);
            }
        }
        Cache::forget('settings_all');
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
        if (!move_uploaded_file($file['tmp_name'], $path)) {
            Flash::set('Dosya yüklenemedi.', 'danger');
            $this->redirect('/admin/ayarlar');
        }
        chmod($path, 0644);
        return '/uploads/' . $name;
    }
}
