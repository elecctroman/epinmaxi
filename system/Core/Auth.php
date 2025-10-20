<?php
namespace System\Core;

use PDO;
use System\Helpers\Flash;
use System\Helpers\Totp;

class Auth
{
    protected const RATE_LIMIT_MAX = 5;
    protected const RATE_LIMIT_WINDOW = 300; // seconds

    public static function attempt(string $email, string $password): bool
    {
        self::ensureSession();
        $key = 'auth_attempts_' . sha1($email . ($_SERVER['REMOTE_ADDR'] ?? 'cli'));
        $attempts = $_SESSION[$key]['count'] ?? 0;
        $expires = $_SESSION[$key]['expires'] ?? 0;

        if ($attempts >= self::RATE_LIMIT_MAX && $expires > time()) {
            throw new \RuntimeException('Too many login attempts. Please try again later.');
        }

        $user = DB::query('SELECT * FROM users WHERE email = :email LIMIT 1', ['email' => $email])->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password_hash'])) {
            if (!empty($user['twofa_secret'])) {
                $_SESSION['twofa_pending'] = (int) $user['id'];
                unset($_SESSION['user_id'], $_SESSION['user_role']);
            } else {
                self::completeLogin($user);
            }
            unset($_SESSION[$key]);
            return true;
        }

        $_SESSION[$key] = [
            'count' => $attempts + 1,
            'expires' => time() + self::RATE_LIMIT_WINDOW,
        ];

        return false;
    }

    public static function register(string $name, string $email, string $password, ?string $phone = null): int
    {
        self::ensureSession();
        $hash = password_hash($password, PASSWORD_DEFAULT);
        DB::query('INSERT INTO users (role, name, email, phone, password_hash, status, created_at) VALUES ("customer", :name, :email, :phone, :hash, "active", NOW())', [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'hash' => $hash,
        ]);
        $userId = (int) DB::pdo()->lastInsertId();
        self::completeLogin([
            'id' => $userId,
            'role' => 'customer',
        ]);
        return $userId;
    }

    public static function completeLogin(array $user): void
    {
        self::ensureSession();
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_role'] = $user['role'] ?? 'customer';
        $_SESSION['session_fingerprint'] = hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . ($_SERVER['REMOTE_ADDR'] ?? 'cli'));
        session_regenerate_id(true);
    }

    public static function verifyTwoFactor(string $code): bool
    {
        self::ensureSession();
        if (empty($_SESSION['twofa_pending'])) {
            return false;
        }
        $user = DB::query('SELECT id, role, twofa_secret FROM users WHERE id = :id', ['id' => $_SESSION['twofa_pending']])->fetch(PDO::FETCH_ASSOC);
        if (!$user || empty($user['twofa_secret'])) {
            unset($_SESSION['twofa_pending']);
            return false;
        }
        if (!Totp::verify($user['twofa_secret'], $code)) {
            return false;
        }
        unset($_SESSION['twofa_pending']);
        self::completeLogin($user);
        return true;
    }

    public static function needsTwoFactor(): bool
    {
        self::ensureSession();
        return !empty($_SESSION['twofa_pending']);
    }

    public static function user(): ?array
    {
        self::ensureSession();
        if (!empty($_SESSION['user_id'])) {
            try {
                return DB::query('SELECT * FROM users WHERE id = :id', ['id' => $_SESSION['user_id']])->fetch(PDO::FETCH_ASSOC) ?: null;
            } catch (\Throwable $e) {
                return null;
            }
        }
        return null;
    }

    public static function id(): ?int
    {
        return self::check() ? (int) $_SESSION['user_id'] : null;
    }

    public static function check(): bool
    {
        self::ensureSession();
        if (empty($_SESSION['user_id']) || empty($_SESSION['session_fingerprint'])) {
            return false;
        }
        $fingerprint = hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . ($_SERVER['REMOTE_ADDR'] ?? 'cli'));
        if (!hash_equals($_SESSION['session_fingerprint'], $fingerprint)) {
            self::logout();
            return false;
        }
        return true;
    }

    public static function logout(): void
    {
        session_regenerate_id(true);
        $_SESSION = [];
        session_destroy();
    }

    public static function enableTwoFactor(int $userId): string
    {
        $secret = Totp::generateSecret();
        DB::query('UPDATE users SET twofa_secret = :secret WHERE id = :id', [
            'secret' => $secret,
            'id' => $userId,
        ]);
        return $secret;
    }

    public static function disableTwoFactor(int $userId): void
    {
        DB::query('UPDATE users SET twofa_secret = NULL WHERE id = :id', ['id' => $userId]);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            Flash::set('Devam etmek için lütfen giriş yapın.', 'warning');
            header('Location: /giris');
            exit;
        }
    }

    protected static function ensureSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }
}
