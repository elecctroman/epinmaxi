<?php
require_once __DIR__ . '/../app.php';

$router = app_router();
$router->dispatch();
