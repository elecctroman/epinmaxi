<?php
namespace System\Controllers\Site;

use PDO;
use System\Core\Auth;
use System\Core\Controller;
use System\Core\DB;
use System\Core\Mailer;
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
            if (!rate_limit('login:' . ($_SERVER['REMOTE_ADDR'] ?? 'cli'), 10, 60)) {
                throw new \RuntimeException('Çok fazla giriş denemesi. Lütfen daha sonra tekrar deneyin.');
            }
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
            if (!rate_limit('register:' . ($_SERVER['REMOTE_ADDR'] ?? 'cli'), 5, 300)) {
                throw new \RuntimeException('Kayıt deneme limiti aşıldı. Lütfen daha sonra tekrar deneyin.');
            }
            $name = trim($_POST['name'] ?? '');
            $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['password_confirmation'] ?? '';
            if (!$email || $name === '') {
                throw new \RuntimeException('Formu eksiksiz doldurun.');
            }
            if ($password !== $confirm) {
                throw new \RuntimeException('Parolalar eşleşmiyor.');
            }
            if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
                throw new \RuntimeException('Parola en az 8 karakter olmalı ve büyük/küçük harf ile rakam içermelidir.');
            }
            $exists = DB::query('SELECT id FROM users WHERE email = :email', ['email' => $email])->fetch(PDO::FETCH_ASSOC);
            if ($exists) {
                throw new \RuntimeException('Bu e-posta zaten kayıtlı.');
            }
            $user = Auth::register($name, $email, $password, $_POST['phone'] ?? null, false);
            $token = bin2hex(random_bytes(32));
            DB::query('DELETE FROM email_verifications WHERE user_id = :id', ['id' => $user['id']]);
            DB::query('INSERT INTO email_verifications (user_id, token, created_at) VALUES (:user_id, :token, NOW())', [
                'user_id' => $user['id'],
                'token' => $token,
            ]);
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $baseUrl = rtrim(setting('app.url', $scheme . '://' . $host), '/');
            $verificationUrl = $baseUrl . '/email-dogrula/' . $token;
            $html = render_email('verify_email', [
                'name' => $name,
                'verificationUrl' => $verificationUrl,
            ]);
            Mailer::send($email, 'Lütfen e-postanızı doğrulayın', $html, [
                'From' => setting('mail.from', 'no-reply@localhost'),
            ]);
            Flash::set('Hesabınız oluşturuldu. Lütfen e-postanızı doğrulayın.', 'success');
            $this->redirect('/giris');
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
