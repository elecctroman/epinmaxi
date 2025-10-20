# E-PIN Premium Platformu (Önyapı)

Bu proje, profesyonel bir e-PIN / lisans & dijital hesap satış platformu için temel uygulama iskeletini içerir. PHP 8+ ve MySQL 8+ üzerinde çalışmak üzere tasarlanmış olup, premium tema görünümü ve güvenlik odaklı altyapı sağlar.

## Minimum Gereksinimler
- PHP 8.0 veya üzeri (pdo_mysql, mbstring, gd, fileinfo eklentileri etkin)
- MySQL 8+
- Apache/Nginx (Rewrite desteği)
- Composer (geliştirici araçları ve testler için önerilir)

## Kurulum
1. Depoyu sunucunuza aktarın ve `public/` dizinini web kök dizini olarak ayarlayın.
2. `config/database.php` dosyasındaki veritabanı bağlantı bilgilerini kendi ortamınıza göre güncelleyin.
3. Boş bir MySQL veritabanı oluşturun ve `sql/database.sql` dosyasını içe aktarın:
   ```bash
   mysql -u <kullanici> -p <veritabani_adi> < sql/database.sql
   ```
4. `storage/`, `storage/logs/` ve `storage/cache/` dizinlerinin web sunucusu tarafından yazılabilir olduğundan emin olun.
5. Kurulum tamamlandığında yönetici hesabıyla oturum açabilir, admin panelinden site ayarlarını güncelleyebilirsiniz.

## Dosya Yapısı
```
public/          # Front controller ve varlık dosyaları
system/          # Çekirdek sınıflar, controller, view, service
config/database.php
routes.php
app.php
sql/database.sql # Şema + demo veriler tek dosyada
```

## Güvenlik Özellikleri
- PDO + hazırlanmış sorgular, sqlite uyumlu test altyapısı
- Tüm mutasyon isteği türleri (POST/PUT/DELETE) için global CSRF doğrulaması ve `X-CSRF-Token` başlığı desteği
- XSS için `e()` ve `sanitize_html()` yardımcıları, çıkışta HTML kaçışlama
- Brute-force denemelerine ve webhooks/login/ödeme uçlarına IP tabanlı rate limit uygulaması
- Session hijacking riskini azaltmak için `HttpOnly` & `SameSite=Lax` çerez parametreleri, fingerprint kontrolü
- Dosya yüklemeleri için MIME/uzantı doğrulaması, rastgele dosya adı ve 0644 izinleri
- İçerik Güvenlik Politikası (CSP), X-Frame-Options, Referrer-Policy, Permissions-Policy başlıkları
- Tüm PHP hata ve istisnaları `storage/logs/error.log` dosyasına kaydeden merkezi hata günlükleme sistemi
- API ve webhook çağrıları için imza doğrulama örnekleri ve `webhook_logs` denetim kayıtları

## .htaccess
Proje kökünde yer alan `.htaccess` dosyası tüm istekleri otomatik olarak `public/` dizinine yönlendirir ve duyarlı dosyaların doğrudan servis edilmesini engeller. Apache kullanıyorsanız bu dosyanın etkin olduğundan emin olun. `public/.htaccess` dosyası ise pretty URL yönlendirmesi, gzip/deflate ve güvenlik başlıklarını hazırlar.

## Önbellek & Performans
- `system/Core/Cache.php` basit dosya tabanlı cache katmanını sağlar. `cache_remember()` helper'ı ile kategori, ürün ve anasayfa sorguları disk üzerinde TTL'li saklanır.
- Statik varlıklar `asset()` helper'ı sayesinde fingerprint alır (`?v=md5`), böylece dağıtım sonrası cache geçersizliği kolaylaşır.
- Hızlı arama ve API gibi uç noktalar rate limit ile korunur.
- `public/index.php` ve tema dosyaları kritik CSS/JS minimal ve lazy-load stratejilerini uygular.

## Public API
- `Authorization: Bearer <token>` başlığı veya `?token=` parametresi ile `api.token_hash` ayarındaki değer doğrulanır.
- Opsiyonel `X-Signature` başlığı, gövde üzerinden `hash_hmac('sha256', body, token)` ile doğrulanır.
- `/api/products?type=&q=&page=` → filtrelenebilir ürün listesi, sayfalama meta bilgisi ile döner.
- `/api/orders/{order_no}` → `api.token_subject` (e-posta veya kullanıcı ID) kapsamına ait siparişleri döndürür.

## Bildirimler
- `system/Services/NotificationService` e-posta şablonlarını (`system/Views/emails/*`) kullanarak sipariş alındı/ödendi/teslim edildi bildirimlerini gönderir.
- SMTP bilgileri admin panelinden yönetilir, etkin değilse PHP `mail()` fonksiyonuna geri düşer.
- Opsiyonel SMS adaptörü (`System\Services\Sms\SmsProviderInterface`) ile mock log oluşturulur; gerçek sağlayıcı anahtarı tanımlandığında genişletilebilir.

## Test & Kalite
- PHPUnit 10 ile örnek birim testleri (`tests/`): `LicenseKeyService`, `CouponService`, `OrderService` kritik akışları doğrular.
- Testleri çalıştırmak için:
  ```bash
  composer install
  ./vendor/bin/phpunit
  ```
- Kod stili PSR-12 ile uyumludur; `composer dump-autoload` ile sınıf eşlemeleri yenilenebilir.

## Dağıtım Notları
- `public/` dizinini web sunucusu kök dizini olarak ayarlayın (ör. Nginx `root /var/www/project/public;`).
- PHP-FPM ile çalışırken `app.php` oturum seçenekleri güvenli çerezleri otomatik etkinleştirir.
- Cron önerileri: günlük rapor/istatistik temizliği, cache temizleme (`storage/cache`), webhook log arşivleme.
- Yedekleme: `sql/database.sql` + düzenli `mysqldump` ile günlük tam yedek, `storage/uploads` ve `storage/logs` dosya sistemi kopyası.
- Ortam değişkeni kullanılmaz; veritabanı erişim bilgileri `config/database.php` içerisinde saklanır (kurulum sihirbazı tarafından yönetilir).

## Demo İçerik
Kurulum tamamlandığında `sql/database.sql` dosyasındaki örnek kategoriler, ürünler, e-pin anahtarları ve kuponlar veritabanına yüklenir. Bu veriler front-end ve admin panelde gösterim amacıyla kullanılır.

## Geliştirme Notları
- `system/Services/Payments/MockGateway` sınıfı test ödemeleri için sağlanmıştır.
- Tema dosyaları `public/assets/css/theme.css` ve `public/assets/js/app.js` altındadır.
- Yeni controller eklerken `System\Controllers\` ad alanını kullanın ve rotaları `routes.php` dosyasına tanımlayın.

## Özellik Özeti
- Çok adımlı müşteri akışı: katalog filtreleri, ürün detayları, sepete ekleme, kupon doğrulama, ödeme ve sipariş tamamlama.
- Mock, PayTR, Iyzico ve Stripe için genişletilebilir ödeme sürücüleri ve imza doğrulamalı webhook uç noktaları.
- Dijital teslimat motoru E-PIN ve hesap kayıtlarını ödeme başarıyla sonuçlandığında otomatik olarak atar.
- Hesabım panelinde siparişler, teslimatlar, cüzdan bakiyesi ve destek bileti yönetimi sunulur.
- AJAX hızlı arama, canlı mini sepet, tema anahtarı, toast bildirimleri ve erişilebilir premium arayüz bileşenleri.
- Gelişmiş yönetici paneli: ürün/kategori CRUD, E-PIN & hesap havuzu import/export, sipariş & iade yönetimi, kullanıcı & rol denetimi,
  kupon ve cüzdan araçları, destek yönetimi, grafik tabanlı raporlar ve audit log kayıtları.

## SSS
- **Kurulum tekrar nasıl çalıştırılır?** Yeni bir veritabanı oluşturup `sql/database.sql` dosyasını yeniden içe aktarın; gerekirse `config/database.php` dosyasındaki bağlantı bilgilerini güncelleyin.
- **Composer zorunlu mu?** Uygulama composer olmadan çalışır, ancak PHPUnit testleri ve otomatik yüklemeyi güncellemek için `composer install` önerilir.

## Lisans
MIT Lisansı.
