<?php
namespace System\Controllers\Site;

use PDO;
use System\Core\Auth;
use System\Core\Controller;
use System\Core\DB;
use System\Helpers\Flash;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        $this->view('site/auth/login', ['title' => 'Giriş Yap']);
    }

    public function login(): void
    {
        try {
            $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
            $password = $_POST['password'] ?? '';
            if (!$email || !$password) {
                throw new \RuntimeException('E-posta ve parola zorunludur.');
            }
            if (!Auth::attempt($email, $password)) {
                throw new \RuntimeException('Geçersiz giriş bilgileri.');
            }
            if (Auth::needsTwoFactor()) {
                Flash::set('Lütfen 2FA kodunuzu girin.', 'info');
                $this->redirect('/iki-adim');
                return;
            }
            Flash::set('Hoş geldiniz!', 'success');
            $this->redirect('/hesabim');
        } catch (\Throwable $e) {
            Flash::set($e->getMessage(), 'danger');
            $this->redirect('/giris');
        }
    }

    public function showRegister(): void
    {
        $this->view('site/auth/register', ['title' => 'Kayıt Ol']);
    }

    public function register(): void
    {
        try {
            $name = trim($_POST['name'] ?? '');
            $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['password_confirmation'] ?? '';
            if (!$email || $name === '' || strlen($password) < 8) {
                throw new \RuntimeException('Formu eksiksiz doldurun.');
            }
            if ($password !== $confirm) {
                throw new \RuntimeException('Parolalar eşleşmiyor.');
            }
            $exists = DB::query('SELECT id FROM users WHERE email = :email', ['email' => $email])->fetch(PDO::FETCH_ASSOC);
            if ($exists) {
                throw new \RuntimeException('Bu e-posta zaten kayıtlı.');
            }
            Auth::register($name, $email, $password, $_POST['phone'] ?? null);
            Flash::set('Hesabınız oluşturuldu.', 'success');
            $this->redirect('/hesabim');
        } catch (\Throwable $e) {
            Flash::set($e->getMessage(), 'danger');
            $this->redirect('/kayit');
        }
    }

    public function logout(): void
    {
        Auth::logout();
        Flash::set('Çıkış yapıldı.', 'info');
        $this->redirect('/');
    }

    public function showTwoFactor(): void
    {
        if (!Auth::needsTwoFactor()) {
            $this->redirect('/hesabim');
            return;
        }
        $this->view('site/auth/twofactor', ['title' => '2FA Doğrulama']);
    }

    public function verifyTwoFactor(): void
    {
        $code = $_POST['code'] ?? '';
        if (Auth::verifyTwoFactor($code)) {
            Flash::set('Giriş tamamlandı.', 'success');
            $this->redirect('/hesabim');
            return;
        }
        Flash::set('Kod doğrulanamadı.', 'danger');
        $this->redirect('/iki-adim');
    }
}
