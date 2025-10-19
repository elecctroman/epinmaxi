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
}
