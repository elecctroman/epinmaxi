<?php
/** @var \System\Core\Router $router */
$router->get('/', 'Site\\HomeController@index');
$router->get('/admin', 'Admin\\DashboardController@index');
