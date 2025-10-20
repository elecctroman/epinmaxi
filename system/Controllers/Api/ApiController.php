<?php
namespace System\Controllers\Api;

use System\Core\Controller;
use System\Helpers\Response;

abstract class ApiController extends Controller
{
    protected function authenticate(): string
    {
        $authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = null;
        if (preg_match('/Bearer\s+(.*)$/i', $authorization, $matches)) {
            $token = trim($matches[1]);
        }
        if (!$token && isset($_GET['token'])) {
            $token = trim($_GET['token']);
        }
        if (!$token) {
            $this->unauthorized('API token gerekli.');
        }
        $hash = hash('sha256', $token);
        if (!hash_equals(setting('api.token_hash', ''), $hash)) {
            $this->unauthorized('Geçersiz API token.');
        }
        $signature = $_SERVER['HTTP_X_SIGNATURE'] ?? null;
        if ($signature) {
            $expected = hash_hmac('sha256', file_get_contents('php://input'), $token);
            if (!hash_equals($expected, $signature)) {
                $this->unauthorized('İmza doğrulanamadı.');
            }
        }
        if (!rate_limit('api:' . ($_SERVER['REMOTE_ADDR'] ?? 'cli'), 100, 60)) {
            $this->tooManyRequests();
        }
        return $token;
    }

    protected function unauthorized(string $message): void
    {
        Response::json(['message' => $message], 401);
        exit;
    }

    protected function tooManyRequests(): void
    {
        Response::json(['message' => 'Çok fazla istek.'], 429);
        exit;
    }
}
