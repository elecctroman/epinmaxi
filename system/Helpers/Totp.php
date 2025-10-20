<?php
namespace System\Helpers;

class Totp
{
    public static function generateSecret(int $length = 16): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return $secret;
    }

    public static function getCode(string $secret, int $timeSlice = null): string
    {
        $timeSlice = $timeSlice ?? floor(time() / 30);
        $secretKey = self::base32Decode($secret);
        $time = pack('N*', 0) . pack('N*', $timeSlice);
        $hm = hash_hmac('sha1', $time, $secretKey, true);
        $offset = ord(substr($hm, -1)) & 0x0F;
        $hashPart = substr($hm, $offset, 4);
        $value = unpack('N', $hashPart)[1] & 0x7FFFFFFF;
        $modulo = 10 ** 6;
        return str_pad((string) ($value % $modulo), 6, '0', STR_PAD_LEFT);
    }

    public static function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/[^0-9]/', '', $code);
        if (strlen($code) !== 6) {
            return false;
        }
        $current = floor(time() / 30);
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::getCode($secret, $current + $i), $code)) {
                return true;
            }
        }
        return false;
    }

    protected static function base32Decode(string $secret): string
    {
        $secret = strtoupper($secret);
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $flipped = array_flip(str_split($alphabet));
        $paddingCharCount = substr_count($secret, '=');
        $secret = str_replace('=', '', $secret);
        $bits = '';
        foreach (str_split($secret) as $char) {
            if (!isset($flipped[$char])) {
                throw new \InvalidArgumentException('Invalid base32 string');
            }
            $bits .= str_pad(decbin($flipped[$char]), 5, '0', STR_PAD_LEFT);
        }
        $bytes = '';
        for ($i = 0; $i < strlen($bits); $i += 8) {
            $chunk = substr($bits, $i, 8);
            if (strlen($chunk) === 8) {
                $bytes .= chr(bindec($chunk));
            }
        }
        if ($paddingCharCount) {
            $bytes = substr($bytes, 0, strlen($bytes) - $paddingCharCount);
        }
        return $bytes;
    }
}
