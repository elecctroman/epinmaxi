<?php
namespace System\Helpers;

class Flash
{
    public static function set(string $message, string $level = 'info'): void
    {
        $_SESSION['flash'] = [
            'message' => $message,
            'level' => $level,
            'time' => time(),
        ];
    }

    public static function get(): ?array
    {
        if (empty($_SESSION['flash'])) {
            return null;
        }
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
}
