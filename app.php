<?php
use System\Core\Router;
use System\Core\DB;

session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);

session_start();

require_once __DIR__ . '/system/Helpers/functions.php';

spl_autoload_register(function ($class) {
    $prefix = 'System\\';
    if (str_starts_with($class, $prefix)) {
        $relative = substr($class, strlen($prefix));
        $path = __DIR__ . '/system/' . str_replace('\\', '/', $relative) . '.php';
        if (is_file($path)) {
            require_once $path;
        }
        return;
    }
});

$config = require __DIR__ . '/config/database.php';
DB::init($config);

$router = new Router();
require __DIR__ . '/routes.php';

$GLOBALS['router'] = $router;

return $router;
