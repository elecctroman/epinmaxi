<?php
namespace System\Core;

class Cache
{
    protected static function path(string $key): string
    {
        $directory = __DIR__ . '/../../storage/cache/data';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
        return $directory . '/' . sha1($key) . '.cache';
    }

    public static function remember(string $key, int $ttl, callable $callback)
    {
        $file = self::path($key);
        $now = time();
        if (is_file($file)) {
            $payload = json_decode((string) file_get_contents($file), true);
            if (is_array($payload) && ($payload['expires_at'] ?? 0) > $now) {
                return $payload['value'];
            }
        }
        $value = $callback();
        $payload = [
            'expires_at' => $now + $ttl,
            'value' => $value,
        ];
        file_put_contents($file, json_encode($payload));
        return $value;
    }

    public static function forget(string $key): void
    {
        $file = self::path($key);
        if (is_file($file)) {
            @unlink($file);
        }
    }
}
