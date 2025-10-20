<?php
namespace System\Core;

class Controller
{
    protected function view(string $view, array $data = [], string $layout = 'main'): void
    {
        extract($data);
        $viewFile = __DIR__ . '/../Views/' . $view . '.php';
        $layoutFile = __DIR__ . '/../Views/layouts/' . $layout . '.php';

        if (!is_file($viewFile)) {
            throw new \RuntimeException("View {$view} not found");
        }

        ob_start();
        include $viewFile;
        $content = ob_get_clean();

        include $layoutFile;
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . $path);
        exit;
    }

    protected function wantsJson(): bool
    {
        $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        if (strtolower($requestedWith) === 'xmlhttprequest') {
            return true;
        }

        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return stripos($accept, 'application/json') !== false;
    }

    protected function authorize(string $ability): void
    {
        if (!\System\Core\Gate::allows($ability)) {
            http_response_code(403);
            echo 'Bu işlem için yetkiniz bulunmuyor.';
            exit;
        }
    }
}
