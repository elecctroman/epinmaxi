<?php
namespace System\Core;

use PDO;
use System\Helpers\Flash;
use System\Helpers\Totp;

class Auth
{
    protected const RATE_LIMIT_MAX = 5;
    protected const RATE_LIMIT_WINDOW = 300; // seconds
    protected const BLOCK_THRESHOLD = 15;

    public static function attempt(string $email, string $password): bool
    {
        self::ensureSession();
        $ip = self::clientIp();

        $blocked = DB::query('SELECT id, expires_at FROM blocked_ips WHERE ip = :ip', ['ip' => $ip])->fetch(PDO::FETCH_ASSOC);
        if ($blocked && (empty($blocked['expires_at']) || strtotime($blocked['expires_at']) > time())) {
            throw new \RuntimeException('IP adresiniz güvenlik nedeniyle geçici olarak engellendi.');
        }

        $failed = DB::query('SELECT id, attempts, locked_until FROM failed_logins WHERE email = :email', ['email' => $email])->fetch(PDO::FETCH_ASSOC);
        if ($failed && !empty($failed['locked_until']) && strtotime($failed['locked_until']) > time()) {
            throw new \RuntimeException('Hesabınız çok sayıda başarısız deneme nedeniyle geçici olarak kilitlendi.');
        }

        $key = 'auth_attempts_' . sha1($email . ($ip));
        $attempts = $_SESSION[$key]['count'] ?? 0;
        $expires = $_SESSION[$key]['expires'] ?? 0;

        if ($attempts >= self::RATE_LIMIT_MAX && $expires > time()) {
            throw new \RuntimeException('Too many login attempts. Please try again later.');
        }

        $user = DB::query('SELECT * FROM users WHERE email = :email LIMIT 1', ['email' => $email])->fetch(PDO::FETCH_ASSOC);
        if ($user && ($user['status'] ?? 'active') !== 'active') {
            throw new \RuntimeException('Hesabınız askıya alınmış durumda.');
        }

        if ($user && password_verify($password, $user['password_hash'])) {
            self::clearFailedAttempts($email);
            unset($_SESSION[$key]);
            if (!empty($user['twofa_secret'])) {
                $_SESSION['twofa_pending'] = (int) $user['id'];
                $_SESSION['twofa_email'] = $email;
                unset($_SESSION['user_id'], $_SESSION['user_role']);
            } else {
                self::completeLogin($user);
                self::recordLogin((int) $user['id'], $email, $ip, 'success');
            }
            return true;
        }

        $_SESSION[$key] = [
            'count' => $attempts + 1,
            'expires' => time() + self::RATE_LIMIT_WINDOW,
        ];

        self::incrementFailedAttempts($email, $ip);
        self::recordLogin(null, $email, $ip, 'failed');

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
        $_SESSION['session_fingerprint'] = hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . self::clientIp());
        session_regenerate_id(true);

        DB::query('UPDATE users SET last_login_at = NOW(), last_login_ip = :ip WHERE id = :id', [
            'ip' => self::clientIp(),
            'id' => (int) $user['id'],
        ]);
    }

    public static function verifyTwoFactor(string $code): bool
    {
        self::ensureSession();
        if (empty($_SESSION['twofa_pending'])) {
            return false;
        }
        $user = DB::query('SELECT id, role, twofa_secret, email FROM users WHERE id = :id', ['id' => $_SESSION['twofa_pending']])->fetch(PDO::FETCH_ASSOC);
        if (!$user || empty($user['twofa_secret'])) {
            unset($_SESSION['twofa_pending'], $_SESSION['twofa_email']);
            return false;
        }
        if (!Totp::verify($user['twofa_secret'], $code)) {
            return false;
        }
        unset($_SESSION['twofa_pending']);
        self::completeLogin($user);
        $email = $_SESSION['twofa_email'] ?? ($user['email'] ?? '');
        unset($_SESSION['twofa_email']);
        self::recordLogin((int) $user['id'], $email, self::clientIp(), 'success');
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
        $fingerprint = hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . self::clientIp());
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

    protected static function incrementFailedAttempts(string $email, string $ip): void
    {
        $failed = DB::query('SELECT id, attempts FROM failed_logins WHERE email = :email', ['email' => $email])->fetch(PDO::FETCH_ASSOC);
        if ($failed) {
            $attempts = (int) $failed['attempts'] + 1;
            $lockedUntil = $attempts >= self::RATE_LIMIT_MAX ? date('Y-m-d H:i:s', time() + self::RATE_LIMIT_WINDOW) : null;
            DB::query('UPDATE failed_logins SET attempts = :attempts, locked_until = :locked WHERE id = :id', [
                'attempts' => $attempts,
                'locked' => $lockedUntil,
                'id' => $failed['id'],
            ]);
            if ($attempts >= self::BLOCK_THRESHOLD) {
                DB::query('INSERT INTO blocked_ips (ip, reason, expires_at) VALUES (:ip, :reason, :expires) ON DUPLICATE KEY UPDATE reason = VALUES(reason), expires_at = VALUES(expires_at)', [
                    'ip' => $ip,
                    'reason' => 'Çoklu başarısız giriş denemesi',
                    'expires' => date('Y-m-d H:i:s', time() + 3600),
                ]);
            }
        } else {
            DB::query('INSERT INTO failed_logins (email, ip, attempts) VALUES (:email, :ip, 1)', [
                'email' => $email,
                'ip' => $ip,
            ]);
        }
    }

    protected static function clearFailedAttempts(string $email): void
    {
        DB::query('DELETE FROM failed_logins WHERE email = :email', ['email' => $email]);
    }

    protected static function recordLogin(?int $userId, string $email, string $ip, string $status): void
    {
        DB::query('INSERT INTO login_logs (user_id, email, ip, user_agent, status) VALUES (:user_id,:email,:ip,:ua,:status)', [
            'user_id' => $userId,
            'email' => $email,
            'ip' => $ip,
            'ua' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'cli', 0, 250),
            'status' => $status,
        ]);
    }

    protected static function clientIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? 'cli';
    }
}
