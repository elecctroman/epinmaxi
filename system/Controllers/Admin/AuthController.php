<?php
namespace System\Controllers\Admin;

use System\Core\Auth;
use System\Core\Controller;
use System\Core\Gate;
use System\Helpers\Csrf;
use System\Helpers\Flash;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Gate::allows('access-admin')) {
            $this->redirect('/admin');
        }
        $this->view('admin/auth/login', [
            'title' => 'Yönetici Girişi',
        ], 'blank');
    }

    public function login(): void
    {
        if (!Csrf::verify($_POST['_token'] ?? '')) {
            Flash::set('Oturum açma isteği doğrulanamadı.', 'danger');
            $this->redirect('/admin/giris');
        }
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        try {
            if (Auth::attempt($email, $password)) {
                if (Auth::needsTwoFactor()) {
                    $this->redirect('/admin/iki-adim');
                }
                if (!Gate::allows('access-admin')) {
                    Auth::logout();
                    Flash::set('Bu hesap için yönetim paneli yetkisi tanımlı değil.', 'danger');
                    $this->redirect('/admin/giris');
                }
                Flash::set('Hoş geldiniz.', 'success');
                $this->redirect('/admin');
            }
        } catch (\RuntimeException $e) {
            Flash::set($e->getMessage(), 'danger');
            $this->redirect('/admin/giris');
        }
        Flash::set('E-posta veya parola hatalı.', 'danger');
        $this->redirect('/admin/giris');
    }

    public function logout(): void
    {
        Auth::logout();
        Flash::set('Güvenli çıkış yapıldı.', 'success');
        $this->redirect('/admin/giris');
    }

    public function showTwoFactor(): void
    {
        if (!Auth::needsTwoFactor()) {
            $this->redirect('/admin');
        }
        $this->view('admin/auth/twofactor', [
            'title' => '2 Adımlı Doğrulama',
        ], 'blank');
    }

    public function verifyTwoFactor(): void
    {
        if (!Csrf::verify($_POST['_token'] ?? '')) {
            Flash::set('Doğrulama isteği reddedildi.', 'danger');
            $this->redirect('/admin/iki-adim');
        }
        if (Auth::verifyTwoFactor(trim($_POST['code'] ?? ''))) {
            if (!Gate::allows('access-admin')) {
                Auth::logout();
                Flash::set('Hesabınız için yönetici yetkisi bulunmuyor.', 'danger');
                $this->redirect('/admin/giris');
            }
            Flash::set('Güvenlik doğrulaması başarılı.', 'success');
            $this->redirect('/admin');
        }
        Flash::set('Kod doğrulanamadı.', 'danger');
        $this->redirect('/admin/iki-adim');
    }
}
