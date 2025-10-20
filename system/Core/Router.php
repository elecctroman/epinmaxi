<?php
namespace System\Core;

use Closure;
use System\Helpers\Csrf;

class Router
{
    protected array $routes = [];

    public function add(string $method, string $path, $handler): self
    {
        $method = strtoupper($method);
        $pattern = '#^' . preg_replace('#\{([a-zA-Z0-9_]+)\}#', '(?P<$1>[^/]+)', $path) . '$#';
        $this->routes[$method][] = [
            'pattern' => $pattern,
            'handler' => $handler,
        ];
        return $this;
    }

    public function get(string $path, $handler): self
    {
        return $this->add('GET', $path, $handler);
    }

    public function post(string $path, $handler): self
    {
        return $this->add('POST', $path, $handler);
    }

    public function put(string $path, $handler): self
    {
        return $this->add('PUT', $path, $handler);
    }

    public function delete(string $path, $handler): self
    {
        return $this->add('DELETE', $path, $handler);
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
        $routes = $this->routes[$method] ?? [];

        $requiresCsrf = in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true);

        foreach ($routes as $route) {
            if (preg_match($route['pattern'], rtrim($uri, '/') ?: '/', $matches)) {
                if ($requiresCsrf) {
                    $token = $_POST['_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
                    if (!Csrf::verify($token)) {
                        http_response_code(419);
                        header('Content-Type: application/json');
                        echo json_encode(['message' => 'CSRF verification failed.']);
                        return;
                    }
                }

                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                return $this->invoke($route['handler'], $params);
            }
        }

        http_response_code(404);
        echo '404 Not Found';
    }

    protected function invoke($handler, array $params = []): void
    {
        if (is_callable($handler)) {
            $handler(...array_values($params));
            return;
        }

        if (is_string($handler) && strpos($handler, '@') !== false) {
            [$class, $method] = explode('@', $handler);
            $class = 'System\\Controllers\\' . $class;
            if (!class_exists($class)) {
                throw new \RuntimeException("Controller {$class} not found");
            }
            $controller = new $class();
            if (!method_exists($controller, $method)) {
                throw new \RuntimeException("Action {$method} not found in {$class}");
            }
            $controller->$method(...array_values($params));
            return;
        }

        throw new \InvalidArgumentException('Invalid route handler.');
    }
}
