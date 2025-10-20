<?php
namespace System\Core;

class Crypto
{
    protected const CIPHER = 'aes-256-gcm';

    public static function encrypt(string $plaintext, string $key): string
    {
        $iv = random_bytes(openssl_cipher_iv_length(self::CIPHER));
        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($ciphertext === false) {
            throw new \RuntimeException('Şifreleme başarısız oldu.');
        }
        return base64_encode($iv . $tag . $ciphertext);
    }

    public static function decrypt(string $payload, string $key): string
    {
        $raw = base64_decode($payload, true);
        if ($raw === false) {
            return '';
        }
        $ivLength = openssl_cipher_iv_length(self::CIPHER);
        $iv = substr($raw, 0, $ivLength);
        $tag = substr($raw, $ivLength, 16);
        $ciphertext = substr($raw, $ivLength + 16);
        $plain = openssl_decrypt($ciphertext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag);
        return $plain === false ? '' : $plain;
    }
}
