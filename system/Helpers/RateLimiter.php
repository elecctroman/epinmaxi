<?php
namespace System\Helpers;

class RateLimiter
{
    protected static function storagePath(): string
    {
        $path = __DIR__ . '/../../storage/cache/ratelimiter';
        if (!is_dir($path)) {
            mkdir($path, 0775, true);
        }
        return $path;
    }

    public static function hit(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        $storage = self::storagePath() . '/' . sha1($key) . '.json';
        $now = time();
        $payload = [
            'count' => 0,
            'expires_at' => $now + $decaySeconds,
        ];

        if (is_file($storage)) {
            $contents = json_decode((string) file_get_contents($storage), true);
            if (is_array($contents) && ($contents['expires_at'] ?? 0) > $now) {
                $payload = $contents;
            }
        }

        if ($payload['expires_at'] <= $now) {
            $payload = [
                'count' => 0,
                'expires_at' => $now + $decaySeconds,
            ];
        }

        if ($payload['count'] >= $maxAttempts) {
            return false;
        }

        $payload['count']++;
        $payload['expires_at'] = max($payload['expires_at'], $now + $decaySeconds);
        file_put_contents($storage, json_encode($payload));
        return true;
    }

    public static function remaining(string $key, int $maxAttempts, int $decaySeconds): int
    {
        $storage = self::storagePath() . '/' . sha1($key) . '.json';
        $now = time();
        if (!is_file($storage)) {
            return $maxAttempts;
        }
        $contents = json_decode((string) file_get_contents($storage), true);
        if (!is_array($contents) || ($contents['expires_at'] ?? 0) <= $now) {
            return $maxAttempts;
        }
        return max(0, $maxAttempts - (int) ($contents['count'] ?? 0));
    }
}
