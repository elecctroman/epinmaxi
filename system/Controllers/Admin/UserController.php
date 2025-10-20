<?php
namespace System\Controllers\Admin;

use System\Core\DB;
use System\Core\Validator;
use System\Helpers\Flash;
use System\Core\Gate;

class UserController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(): void
    {
        $this->enforce('manage-users');
        $users = DB::query('SELECT id, name, email, role, status, created_at, last_login_at FROM users ORDER BY created_at DESC LIMIT 100')->fetchAll();
        $loginLogs = DB::query('SELECT ll.*, u.email AS user_email FROM login_logs ll LEFT JOIN users u ON u.id = ll.user_id ORDER BY ll.created_at DESC LIMIT 50')->fetchAll();
        $blockedIps = DB::query('SELECT * FROM blocked_ips ORDER BY created_at DESC')->fetchAll();
        $this->render('users/index', [
            'title' => 'Kullanıcılar & Roller',
            'users' => $users,
            'loginLogs' => $loginLogs,
            'abilities' => Gate::abilities(),
            'blockedIps' => $blockedIps,
        ]);
    }

    public function store(): void
    {
        $this->enforce('manage-users');
        $errors = Validator::required($_POST, [
            'name' => 'İsim',
            'email' => 'E-posta',
            'role' => 'Rol',
            'password' => 'Parola',
        ]);
        if ($errors) {
            Flash::set(reset($errors), 'danger');
            $this->redirect('/admin/kullanicilar');
        }
        DB::query('INSERT INTO users (name,email,role,password_hash,status,created_at) VALUES (:name,:email,:role,:password,"active",NOW())', [
            'name' => trim($_POST['name']),
            'email' => trim($_POST['email']),
            'role' => $_POST['role'],
            'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
        ]);
        $this->audit('create', 'user', (int) DB::pdo()->lastInsertId());
        Flash::set('Kullanıcı oluşturuldu.', 'success');
        $this->redirect('/admin/kullanicilar');
    }

    public function update(int $id): void
    {
        $this->enforce('manage-users');
        $permissions = array_filter(array_map('trim', preg_split('/\r?\n/', $_POST['permissions'] ?? '')));
        DB::query('UPDATE users SET role = :role, status = :status, permissions_json = :permissions WHERE id = :id', [
            'role' => $_POST['role'] ?? 'customer',
            'status' => $_POST['status'] ?? 'active',
            'permissions' => $permissions ? json_encode(array_values($permissions), JSON_UNESCAPED_UNICODE) : null,
            'id' => $id,
        ]);
        $this->audit('update', 'user', $id, ['role' => $_POST['role'] ?? 'customer']);
        Flash::set('Kullanıcı güncellendi.', 'success');
        $this->redirect('/admin/kullanicilar');
    }

    public function resetTwoFactor(int $id): void
    {
        $this->enforce('manage-users');
        DB::query('UPDATE users SET twofa_secret = NULL WHERE id = :id', ['id' => $id]);
        $this->audit('security-reset', 'user', $id);
        Flash::set('2FA sıfırlandı.', 'success');
        $this->redirect('/admin/kullanicilar');
    }

    public function unblockIp(int $id): void
    {
        $this->enforce('manage-ip-blocks');
        DB::query('DELETE FROM blocked_ips WHERE id = :id', ['id' => $id]);
        $this->audit('ip-unblock', 'security', $id);
        Flash::set('IP engeli kaldırıldı.', 'success');
        $this->redirect('/admin/kullanicilar');
    }
}
