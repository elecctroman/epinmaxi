# E-PIN Premium Platformu (Önyapı)

Bu proje, profesyonel bir e-PIN / lisans & dijital hesap satış platformu için temel uygulama iskeletini içerir. PHP 8+ ve MySQL 8+ üzerinde çalışmak üzere tasarlanmış olup, premium tema görünümü ve güvenlik odaklı altyapı sağlar.

## Minimum Gereksinimler
- PHP 8.0 veya üzeri (pdo_mysql, mbstring, gd, fileinfo eklentileri etkin)
- MySQL 8+
- Apache/Nginx (Rewrite desteği)
- composer gerektirmez

## Kurulum
1. Depoyu sunucunuza aktarın ve `public/` dizinini web kök dizini olarak ayarlayın.
2. Tarayıcıdan `install.php` dosyasını açın ve dört adımlı sihirbazı takip edin:
   - Sistem gereksinimlerini doğrulayın.
   - Veritabanı bilgilerinizi girin (bilgiler `config/database.php` dosyasına yazılır).
   - Şemayı yükleyip ilk yönetici hesabını oluşturun (isteğe bağlı 2FA anahtarı).
   - Site ayarlarını girip kurulumu tamamlayın. `install.lock` oluşturulur.
3. Kurulum sonrası güvenlik amacıyla `install.php` dosyasını silin veya taşıyın.

## Dosya Yapısı
```
public/          # Front controller ve varlık dosyaları
system/          # Çekirdek sınıflar, controller, view, service
config/database.php
routes.php
app.php
install.php
sql/schema.sql   # Veritabanı şeması
sql/seed.sql     # Demo veriler
```

## Güvenlik Özellikleri
- PDO + hazırlanmış sorgular
- CSRF token kontrolü (POST)
- XSS için `e()` ve `sanitize_html()` yardımcıları
- Brute-force denemelerine karşı oturum tabanlı rate limit
- Session hijacking riskini azaltmak için `HttpOnly` & `SameSite=Lax` çerez parametreleri
- Dosya yüklemeleri için MIME/uzantı doğrulaması

## .htaccess
`public/.htaccess` dosyası URL yönlendirme, gzip/deflate ve güvenlik başlıklarını hazırlar. Apache kullanıyorsanız etkinleştirin.

## Demo İçerik
Kurulum tamamlandığında `sql/seed.sql` dosyasındaki örnek kategoriler, ürünler, e-pin anahtarları ve kuponlar veritabanına yüklenir. Bu veriler front-end ve admin panelde gösterim amacıyla kullanılır.

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
- **Kurulum tekrar nasıl çalıştırılır?** `install.lock` dosyasını silip tarayıcıdan `install.php`yi açın.
- **Neden composer yok?** Gereksinim gereği yalnızca native PHP bileşenleri kullanılmaktadır.

## Lisans
MIT Lisansı.
