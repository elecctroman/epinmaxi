<?php
use System\Core\Router;
use System\Helpers\Csrf;
use System\Helpers\Sanitizer;
use System\Helpers\Response;
use System\Helpers\Flash;
use System\Core\DB;
use PDO;
use Throwable;

if (!function_exists('app_router')) {
    function app_router(): Router
    {
        if (!isset($GLOBALS['router']) || !$GLOBALS['router'] instanceof Router) {
            throw new \RuntimeException('Router instance not available.');
        }
        return $GLOBALS['router'];
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Csrf::token();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('e')) {
    function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('sanitize_html')) {
    function sanitize_html(string $html): string
    {
        return Sanitizer::stripDangerousTags($html);
    }
}

if (!function_exists('json_response')) {
    function json_response(array $payload, int $status = 200): void
    {
        Response::json($payload, $status);
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return '/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('flash')) {
    function flash(?string $message = null, string $level = 'info'): ?array
    {
        if ($message !== null) {
            Flash::set($message, $level);
            return null;
        }
        return Flash::get();
    }
}

if (!function_exists('setting')) {
    function setting(string $key, $default = null)
    {
        static $settings;
        if ($settings === null) {
            try {
                $stmt = DB::query('SELECT `key`, `value` FROM settings');
                $settings = [];
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $settings[$row['key']] = $row['value'];
                }
            } catch (Throwable $e) {
                $settings = [];
            }
        }
        return $settings[$key] ?? $default;
    }
}

if (!function_exists('format_currency')) {
    function format_currency(float $amount, string $currency = 'TRY'): string
    {
        return number_format($amount, 2, ',', '.') . ' ' . strtoupper($currency);
    }
}

if (!function_exists('mask_secret')) {
    function mask_secret(string $value, int $visible = 4): string
    {
        $len = mb_strlen($value);
        if ($len <= $visible) {
            return str_repeat('*', $len);
        }
        return str_repeat('*', $len - $visible) . mb_substr($value, -$visible);
    }
}
