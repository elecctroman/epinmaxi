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

$router->get('/admin/giris', 'Admin\\AuthController@showLogin');
$router->post('/admin/giris', 'Admin\\AuthController@login');
$router->post('/admin/cikis', 'Admin\\AuthController@logout');
$router->get('/admin/iki-adim', 'Admin\\AuthController@showTwoFactor');
$router->post('/admin/iki-adim', 'Admin\\AuthController@verifyTwoFactor');

$router->get('/admin', 'Admin\\DashboardController@index');
$router->get('/admin/urunler', 'Admin\\ProductController@index');
$router->get('/admin/urunler/olustur', 'Admin\\ProductController@create');
$router->post('/admin/urunler', 'Admin\\ProductController@store');
$router->get('/admin/urunler/{id}/duzenle', 'Admin\\ProductController@edit');
$router->post('/admin/urunler/{id}/guncelle', 'Admin\\ProductController@update');
$router->post('/admin/urunler/{id}/sil', 'Admin\\ProductController@destroy');

$router->get('/admin/kategoriler', 'Admin\\CategoryController@index');
$router->post('/admin/kategoriler', 'Admin\\CategoryController@store');
$router->post('/admin/kategoriler/{id}/guncelle', 'Admin\\CategoryController@update');
$router->post('/admin/kategoriler/{id}/sil', 'Admin\\CategoryController@destroy');

$router->get('/admin/epin-havuzu', 'Admin\\KeyPoolController@index');
$router->post('/admin/epin-havuzu/icerik', 'Admin\\KeyPoolController@import');
$router->get('/admin/epin-havuzu/disariver', 'Admin\\KeyPoolController@export');
$router->post('/admin/epin-havuzu/{id}/sil', 'Admin\\KeyPoolController@destroy');

$router->get('/admin/hesap-havuzu', 'Admin\\AccountPoolController@index');
$router->get('/admin/hesap-havuzu/{id}', 'Admin\\AccountPoolController@show');
$router->post('/admin/hesap-havuzu/icerik', 'Admin\\AccountPoolController@import');
$router->get('/admin/hesap-havuzu/disariver', 'Admin\\AccountPoolController@export');
$router->post('/admin/hesap-havuzu/{id}/sil', 'Admin\\AccountPoolController@destroy');

$router->get('/admin/siparisler', 'Admin\\OrderController@index');
$router->get('/admin/siparisler/{id}', 'Admin\\OrderController@show');
$router->post('/admin/siparisler/{id}/durum', 'Admin\\OrderController@updateStatus');
$router->post('/admin/siparisler/{id}/teslim', 'Admin\\OrderController@deliver');
$router->post('/admin/siparisler/{id}/iade', 'Admin\\OrderController@refund');

$router->get('/admin/odemeler', 'Admin\\PaymentController@index');
$router->get('/admin/odemeler/{id}', 'Admin\\PaymentController@show');

$router->get('/admin/kullanicilar', 'Admin\\UserController@index');
$router->post('/admin/kullanicilar', 'Admin\\UserController@store');
$router->post('/admin/kullanicilar/{id}/guncelle', 'Admin\\UserController@update');
$router->post('/admin/kullanicilar/{id}/iki-adim-sifirla', 'Admin\\UserController@resetTwoFactor');
$router->post('/admin/kullanicilar/ip-engel/{id}/kaldir', 'Admin\\UserController@unblockIp');

$router->get('/admin/kuponlar', 'Admin\\CouponController@index');
$router->post('/admin/kuponlar', 'Admin\\CouponController@store');
$router->post('/admin/kuponlar/{id}/guncelle', 'Admin\\CouponController@update');
$router->post('/admin/kuponlar/{id}/sil', 'Admin\\CouponController@destroy');

$router->get('/admin/cuzdanlar', 'Admin\\WalletController@index');
$router->post('/admin/cuzdanlar/{id}/islem', 'Admin\\WalletController@adjust');

$router->get('/admin/destek', 'Admin\\TicketController@index');
$router->get('/admin/destek/{id}', 'Admin\\TicketController@show');
$router->post('/admin/destek/{id}/yanit', 'Admin\\TicketController@reply');
$router->post('/admin/destek/{id}/kapat', 'Admin\\TicketController@close');

$router->get('/admin/ayarlar', 'Admin\\SettingsController@index');
$router->post('/admin/ayarlar', 'Admin\\SettingsController@update');

$router->get('/admin/gunlukler', 'Admin\\LogController@index');
$router->get('/admin/gunlukler/indir', 'Admin\\LogController@download');

$router->get('/admin/raporlar', 'Admin\\ReportController@index');
