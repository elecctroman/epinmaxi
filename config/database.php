<?php

$defaults = [
    'driver' => 'mysql',
    'host' => 'localhost',
    'database' => 'epin_platform',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
];

// Prefer a dedicated local override so administrators never have to edit this file directly.
$localPhp = __DIR__ . '/database.local.php';
if (is_file($localPhp)) {
    $overrides = include $localPhp;
    if (!is_array($overrides)) {
        throw new RuntimeException('config/database.local.php must return an array.');
    }
    return array_replace_recursive($defaults, $overrides);
}

// Support a key=value config to simplify deployments on shared hosting.
$iniFile = __DIR__ . '/database.ini';
if (is_file($iniFile)) {
    $parsed = parse_ini_file($iniFile, false, INI_SCANNER_TYPED);
    if ($parsed === false) {
        throw new RuntimeException('Unable to parse config/database.ini. Please check the syntax.');
    }

    $mapped = array_intersect_key($parsed, $defaults);
    return array_replace($defaults, $mapped);
}

return $defaults;
