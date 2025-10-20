<?php
session_start();

use PDO;
use PDOException;
use finfo;

$lockFile = __DIR__ . '/install.lock';
if (file_exists($lockFile)) {
    exit('Kurulum tamamlanmış görünüyor. install.lock dosyasını kaldırmadan tekrar kurulum yapılamaz.');
}

$step = (int) ($_GET['step'] ?? 1);

function requirement_check(): array
{
    $requirements = [
        'PHP 8.0+' => PHP_VERSION_ID >= 80000,
        'pdo_mysql' => extension_loaded('pdo_mysql'),
        'gd' => extension_loaded('gd'),
        'mbstring' => extension_loaded('mbstring'),
        'fileinfo' => extension_loaded('fileinfo'),
        'public/ yazılabilir' => is_writable(__DIR__ . '/public'),
    ];
    return $requirements;
}

function render_header(string $title): void
{
    echo '<!DOCTYPE html><html lang="tr"><head><meta charset="utf-8"><title>' . htmlspecialchars($title) . '</title>';
    echo '<link rel="stylesheet" href="/assets/css/theme.css"></head><body><main class="container" style="padding:3rem 0;">';
    echo '<h1>' . htmlspecialchars($title) . '</h1>';
}

function render_footer(): void
{
    echo '</main></body></html>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['_token'] ?? '';
    if (!hash_equals($_SESSION['_token'] ?? '', $token)) {
        exit('CSRF doğrulaması başarısız.');
    }
}

$_SESSION['_token'] = $_SESSION['_token'] ?? bin2hex(random_bytes(32));

switch ($step) {
    case 1:
        $reqs = requirement_check();
        render_header('Adım 1 - Sistem Gereksinimleri');
        echo '<ul class="card">';
        $allOk = true;
        foreach ($reqs as $label => $ok) {
            $allOk = $allOk && $ok;
            echo '<li>' . htmlspecialchars($label) . ': ' . ($ok ? '✅' : '❌') . '</li>';
        }
        echo '</ul>';
        if ($allOk) {
            echo '<form method="post" action="?step=2" style="margin-top:2rem;">';
            echo '<input type="hidden" name="_token" value="' . $_SESSION['_token'] . '">';
            echo '<button class="button-primary">Devam Et</button></form>';
        } else {
            echo '<p>Eksik gereksinimler tamamlanmadan kuruluma devam edilemez.</p>';
        }
        render_footer();
        break;

    case 2:
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $db = [
                'driver' => 'mysql',
                'host' => trim($_POST['host'] ?? 'localhost'),
                'database' => trim($_POST['database'] ?? ''),
                'username' => trim($_POST['username'] ?? ''),
                'password' => $_POST['password'] ?? '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'options' => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ],
            ];

            $configString = "<?php\nreturn " . var_export($db, true) . ";\n";
            file_put_contents(__DIR__ . '/config/database.php', $configString);
            $_SESSION['db_configured'] = true;
            header('Location: install.php?step=3');
            exit;
        }
        render_header('Adım 2 - Veritabanı Bilgileri');
        echo '<form method="post" class="card" style="display:grid;gap:1rem;max-width:480px;">';
        echo '<input type="hidden" name="_token" value="' . $_SESSION['_token'] . '">';
        echo '<label>Host<input type="text" name="host" required value="localhost"></label>';
        echo '<label>Veritabanı<input type="text" name="database" required></label>';
        echo '<label>Kullanıcı Adı<input type="text" name="username" required></label>';
        echo '<label>Parola<input type="password" name="password"></label>';
        echo '<button class="button-primary" type="submit">Kaydet & Devam Et</button>';
        echo '</form>';
        render_footer();
        break;

    case 3:
        if (empty($_SESSION['db_configured'])) {
            header('Location: install.php?step=2');
            exit;
        }

        $config = require __DIR__ . '/config/database.php';
        try {
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $config['host'], $config['database'], $config['charset']);
            $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
        } catch (PDOException $e) {
            render_header('Adım 3 - Veritabanı Kurulumu');
            echo '<p>Veritabanı bağlantı hatası: ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<a class="button-primary" href="?step=2">Geri dön</a>';
            render_footer();
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $adminEmail = trim($_POST['email']);
            $adminPass = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $twofa = !empty($_POST['twofa']) ? bin2hex(random_bytes(16)) : null;
            $pdo->exec(file_get_contents(__DIR__ . '/sql/schema.sql'));
            $pdo->exec(file_get_contents(__DIR__ . '/sql/seed.sql'));
            $stmt = $pdo->prepare('INSERT INTO users(role,name,email,phone,password_hash,twofa_secret,permissions_json,status,created_at) VALUES("admin",:name,:email,:phone,:password,:twofa,JSON_ARRAY("*"),"active",NOW())');
            $stmt->execute([
                'name' => 'Kurucu Admin',
                'email' => $adminEmail,
                'phone' => $_POST['phone'] ?? null,
                'password' => $adminPass,
                'twofa' => $twofa,
            ]);
            $settingsStmt = $pdo->prepare('REPLACE INTO settings(`key`,`value`) VALUES (:key,:value)');
            $settingsStmt->execute(['key' => 'app.encryption_key', 'value' => bin2hex(random_bytes(16))]);
            $apiToken = bin2hex(random_bytes(16));
            $settingsStmt->execute(['key' => 'api.token_hash', 'value' => hash('sha256', $apiToken)]);
            $settingsStmt->execute(['key' => 'api.token_hint', 'value' => substr($apiToken, 0, 4) . str_repeat('*', 12)]);
            $_SESSION['api_token_plain'] = $apiToken;
            $_SESSION['admin_created'] = true;
            header('Location: install.php?step=4');
            exit;
        }

        render_header('Adım 3 - Veritabanı Kurulumu');
        echo '<form method="post" class="card" style="display:grid;gap:1rem;max-width:520px;">';
        echo '<input type="hidden" name="_token" value="' . $_SESSION['_token'] . '">';
        echo '<label>Yönetici E-posta<input type="email" name="email" required></label>';
        echo '<label>Telefon<input type="text" name="phone"></label>';
        echo '<label>Parola<input type="password" name="password" required></label>';
        echo '<label><input type="checkbox" name="twofa" value="1"> 2FA aktif et</label>';
        echo '<button class="button-primary" type="submit">Kurulumu Başlat</button>';
        echo '</form>';
        render_footer();
        break;

    case 4:
        if (empty($_SESSION['admin_created'])) {
            header('Location: install.php?step=3');
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $config = require __DIR__ . '/config/database.php';
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $config['host'], $config['database'], $config['charset']);
            $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
            $stmt = $pdo->prepare('REPLACE INTO settings(`key`,`value`) VALUES (:key,:value)');
            $settings = [
                'app.name' => trim($_POST['site_name']),
                'app.currency' => $_POST['currency'],
                'app.timezone' => $_POST['timezone'],
                'app.url' => rtrim((isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'), '/'),
            ];
            foreach ($settings as $key => $value) {
                $stmt->execute(['key' => $key, 'value' => $value]);
            }
            if (!empty($_FILES['logo']['tmp_name'])) {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($_FILES['logo']['tmp_name']);
                if (!in_array($mime, ['image/png', 'image/jpeg', 'image/svg+xml'])) {
                    exit('Logo formatı desteklenmiyor.');
                }
                $extension = $mime === 'image/png' ? 'png' : ($mime === 'image/jpeg' ? 'jpg' : 'svg');
                $filename = 'logo_' . bin2hex(random_bytes(6)) . '.' . $extension;
                $destination = __DIR__ . '/public/uploads/' . $filename;
                if (!move_uploaded_file($_FILES['logo']['tmp_name'], $destination)) {
                    exit('Logo yüklenemedi.');
                }
                chmod($destination, 0644);
                $stmt->execute(['key' => 'app.logo', 'value' => '/uploads/' . $filename]);
            }
            file_put_contents($lockFile, 'installed:' . date('c'));
            $apiTokenPlain = $_SESSION['api_token_plain'] ?? null;
            $_SESSION = [];
            if ($apiTokenPlain) {
                $_SESSION['install_token_notice'] = $apiTokenPlain;
            }
            header('Location: install.php?success=1');
            exit;
        }

        render_header('Adım 4 - Site Ayarları');
        echo '<form method="post" enctype="multipart/form-data" class="card" style="display:grid;gap:1rem;max-width:480px;">';
        echo '<input type="hidden" name="_token" value="' . $_SESSION['_token'] . '">';
        echo '<label>Site Adı<input type="text" name="site_name" required></label>';
        echo '<label>Logo<input type="file" name="logo" accept="image/png,image/jpeg,image/svg+xml"></label>';
        echo '<label>Para Birimi<select name="currency"><option value="TRY">TRY</option><option value="USD">USD</option><option value="EUR">EUR</option></select></label>';
        echo '<label>Zaman Dilimi<input type="text" name="timezone" value="Europe/Istanbul" required></label>';
        echo '<button class="button-primary" type="submit">Kurulumu Tamamla</button>';
        echo '</form>';
        render_footer();
        break;

    default:
        if (!empty($_GET['success'])) {
            render_header('Kurulum Tamamlandı');
            echo '<div class="card" style="max-width:520px;">Kurulum başarıyla tamamlandı. Güvenlik için install.php dosyasını silmeniz önerilir.</div>';
            if (!empty($_SESSION['install_token_notice'])) {
                echo '<p class="card" style="margin-top:1rem;max-width:520px;background:#0f172a;color:#f8fafc;padding:1rem;border-radius:1rem;">API Tokenınız: <strong>' . htmlspecialchars($_SESSION['install_token_notice']) . '</strong><br><small>Bu token yalnızca bir kez gösterilir. Güvenle saklayın.</small></p>';
                unset($_SESSION['install_token_notice']);
            }
            echo '<p><a class="button-primary" href="/">Siteye Git</a> <a class="button-primary" href="/admin" style="margin-left:1rem;">Yönetim Paneli</a></p>';
            render_footer();
        }
        break;
}
