<?php
namespace System\Controllers\Site;

use PDO;
use System\Core\Auth;
use System\Core\Controller;
use System\Core\DB;
use System\Helpers\Flash;

class AccountController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();
        $user = Auth::user();
        $orders = DB::query('SELECT order_no, grand_total, currency, status, created_at FROM orders WHERE user_id = :uid ORDER BY created_at DESC LIMIT 10', ['uid' => $user['id']])->fetchAll(PDO::FETCH_ASSOC);
        $items = DB::query('SELECT oi.delivery_payload, p.title, p.type, o.order_no FROM order_items oi JOIN orders o ON o.id = oi.order_id LEFT JOIN products p ON p.id = oi.product_id WHERE o.user_id = :uid AND o.payment_status = "paid" ORDER BY o.created_at DESC LIMIT 20', ['uid' => $user['id']])->fetchAll(PDO::FETCH_ASSOC);
        $wallet = DB::query('SELECT balance FROM wallets WHERE user_id = :uid', ['uid' => $user['id']])->fetchColumn() ?: 0;
        $tickets = DB::query('SELECT id, subject, status, created_at FROM tickets WHERE user_id = :uid ORDER BY created_at DESC LIMIT 5', ['uid' => $user['id']])->fetchAll(PDO::FETCH_ASSOC);
        $this->view('site/account/dashboard', [
            'user' => $user,
            'orders' => $orders,
            'items' => $items,
            'wallet' => $wallet,
            'tickets' => $tickets,
            'title' => 'Hesabım',
        ]);
    }

    public function updateProfile(): void
    {
        Auth::requireLogin();
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        if ($name === '') {
            Flash::set('İsim boş olamaz.', 'danger');
            $this->redirect('/hesabim');
            return;
        }
        DB::query('UPDATE users SET name = :name, phone = :phone WHERE id = :id', [
            'name' => $name,
            'phone' => $phone ?: null,
            'id' => Auth::id(),
        ]);
        Flash::set('Profil güncellendi.', 'success');
        $this->redirect('/hesabim');
    }

    public function updatePassword(): void
    {
        Auth::requireLogin();
        $current = $_POST['current_password'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['password_confirmation'] ?? '';
        $user = DB::query('SELECT password_hash FROM users WHERE id = :id', ['id' => Auth::id()])->fetch(PDO::FETCH_ASSOC);
        if (!$user || !password_verify($current, $user['password_hash'])) {
            Flash::set('Mevcut parola hatalı.', 'danger');
            $this->redirect('/hesabim');
            return;
        }
        if ($password === '' || $password !== $confirm) {
            Flash::set('Yeni parola doğrulanamadı.', 'danger');
            $this->redirect('/hesabim');
            return;
        }
        DB::query('UPDATE users SET password_hash = :hash WHERE id = :id', [
            'hash' => password_hash($password, PASSWORD_DEFAULT),
            'id' => Auth::id(),
        ]);
        Flash::set('Parolanız güncellendi.', 'success');
        $this->redirect('/hesabim');
    }

    public function toggleTwoFactor(): void
    {
        Auth::requireLogin();
        $user = Auth::user();
        if (!empty($user['twofa_secret'])) {
            Auth::disableTwoFactor((int) $user['id']);
            Flash::set('2FA devre dışı bırakıldı.', 'info');
        } else {
            $secret = Auth::enableTwoFactor((int) $user['id']);
            $_SESSION['twofa_secret'] = $secret;
            Flash::set('2FA etkinleştirildi. Lütfen uygulamanıza ekleyin: ' . $secret, 'success');
        }
        $this->redirect('/hesabim');
    }
}
