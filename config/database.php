<?php
declare(strict_types=1);

$defaults = [
    'driver' => 'mysql',
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'epin_platform',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'options' => [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        \PDO::ATTR_EMULATE_PREPARES => false,
    ],
];

$phpOverride = __DIR__ . '/database.local.php';
if (is_file($phpOverride)) {
    $overrides = include $phpOverride;
    if (!is_array($overrides)) {
        throw new \RuntimeException('config/database.local.php must return an array.');
    }

    return array_replace_recursive($defaults, $overrides);
}

$iniFile = __DIR__ . '/database.ini';
if (is_file($iniFile)) {
    $parsed = parse_ini_file($iniFile, false, INI_SCANNER_RAW);
    if ($parsed === false) {
        throw new \RuntimeException('Unable to parse config/database.ini. Please ensure it uses key=value syntax without quotes.');
    }

    $normalized = [];
    foreach ($parsed as $key => $value) {
        $normalized[strtolower(trim($key))] = is_string($value) ? trim($value) : $value;
    }

    $allowed = array_intersect_key($normalized, array_flip([
        'driver', 'host', 'port', 'database', 'username', 'password', 'charset', 'collation'
    ]));

    if (isset($allowed['port'])) {
        $allowed['port'] = (int) $allowed['port'];
    }

    return array_replace($defaults, $allowed);
}

throw new \RuntimeException('Database configuration missing. Copy config/database.ini.example to config/database.ini or create config/database.local.php returning an array of overrides.');
