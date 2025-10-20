<?php
namespace System\Controllers\Site;

use PDO;
use System\Core\Controller;
use System\Core\DB;
use System\Helpers\Flash;

class VerificationController extends Controller
{
    public function verify(string $token): void
    {
        try {
            $record = DB::query('SELECT ev.id, ev.user_id, u.status FROM email_verifications ev JOIN users u ON u.id = ev.user_id WHERE ev.token = :token LIMIT 1', [
                'token' => $token,
            ])->fetch(PDO::FETCH_ASSOC);
            if (!$record) {
                throw new \RuntimeException('Doğrulama bağlantısı geçersiz veya süresi dolmuş.');
            }
            DB::transaction(function ($pdo) use ($record, $token) {
                $pdo->prepare('DELETE FROM email_verifications WHERE token = :token')->execute(['token' => $token]);
                $pdo->prepare('UPDATE users SET status = "active" WHERE id = :id')->execute(['id' => $record['user_id']]);
            });
            Flash::set('E-posta adresiniz doğrulandı. Giriş yapabilirsiniz.', 'success');
            $this->redirect('/giris');
        } catch (\Throwable $e) {
            Flash::set($e->getMessage(), 'danger');
            $this->redirect('/giris');
        }
    }
}
