<?php
use System\Core\Router;
use System\Helpers\Csrf;
use System\Helpers\Sanitizer;
use System\Helpers\Response;

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
