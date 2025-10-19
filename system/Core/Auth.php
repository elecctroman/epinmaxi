<?php
namespace System\Core;

use DateInterval;
use DateTimeImmutable;
use PDO;
use System\Helpers\Csrf;

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
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['user_role'] = $user['role'];
            unset($_SESSION[$key]);
            return true;
        }

        $_SESSION[$key] = [
            'count' => $attempts + 1,
            'expires' => time() + self::RATE_LIMIT_WINDOW,
        ];

        return false;
    }

    public static function user(): ?array
    {
        if (!empty($_SESSION['user_id'])) {
            try {
                return DB::query('SELECT * FROM users WHERE id = :id', ['id' => $_SESSION['user_id']])->fetch(PDO::FETCH_ASSOC) ?: null;
            } catch (\Throwable $e) {
                return null;
            }
        }
        return null;
    }

    public static function check(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    public static function logout(): void
    {
        session_regenerate_id(true);
        $_SESSION = [];
        session_destroy();
    }

    protected static function ensureSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }
}
