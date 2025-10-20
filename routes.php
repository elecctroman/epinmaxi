<?php
/** @var \System\Core\Router $router */
$router->get('/', 'Site\\HomeController@index');
$router->get('/search', 'Site\\HomeController@search');

$router->get('/kategori/{slug}', 'Site\\CatalogController@category');
$router->get('/urun/{slug}', 'Site\\CatalogController@product');

$router->get('/giris', 'Site\\AuthController@showLogin');
$router->post('/giris', 'Site\\AuthController@login');
$router->get('/kayit', 'Site\\AuthController@showRegister');
$router->post('/kayit', 'Site\\AuthController@register');
$router->post('/cikis', 'Site\\AuthController@logout');
$router->get('/iki-adim', 'Site\\AuthController@showTwoFactor');
$router->post('/iki-adim', 'Site\\AuthController@verifyTwoFactor');

$router->get('/sepet', 'Site\\CartController@show');
$router->post('/sepete-ekle', 'Site\\CartController@add');
$router->post('/sepet/guncelle', 'Site\\CartController@update');
$router->post('/sepet/kaldir/{product_id}', 'Site\\CartController@remove');
$router->post('/kupon/uygula', 'Site\\CartController@applyCoupon');

$router->get('/odeme', 'Site\\CheckoutController@index');
$router->post('/odeme', 'Site\\CheckoutController@store');

$router->get('/siparis/{orderNo}', 'Site\\OrderController@show');

$router->get('/hesabim', 'Site\\AccountController@index');
$router->post('/hesabim/profil', 'Site\\AccountController@updateProfile');
$router->post('/hesabim/parola', 'Site\\AccountController@updatePassword');
$router->post('/hesabim/iki-adim', 'Site\\AccountController@toggleTwoFactor');

$router->get('/destek', 'Site\\TicketController@index');
$router->post('/destek', 'Site\\TicketController@store');
$router->get('/destek/{id}', 'Site\\TicketController@show');
$router->post('/destek/{id}', 'Site\\TicketController@reply');

$router->post('/webhook/paytr', 'Site\\WebhookController@paytr');
$router->post('/webhook/iyzico', 'Site\\WebhookController@iyzico');
$router->post('/webhook/stripe', 'Site\\WebhookController@stripe');

$router->get('/admin', 'Admin\\DashboardController@index');
