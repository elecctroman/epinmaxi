<?php
namespace System\Helpers;

class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_token'])) {
            $_SESSION['_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_token'];
    }

    public static function verify(?string $token): bool
    {
        return hash_equals(self::token(), $token ?? '');
    }
}
