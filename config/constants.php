<?php

$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if (getenv($key) === false) {
            putenv($key . '=' . $value);
        }
        $_ENV[$key] = $_ENV[$key] ?? $value;
    }
}

$appUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/itec_contract_system', '/');
$basePath = parse_url($appUrl, PHP_URL_PATH) ?: '/itec_contract_system';
$basePath = '/' . trim($basePath, '/');



if (!defined('APP_URL')) {
    define('APP_URL', $appUrl);
}

if (!defined('BASE_URL')) {
    define('BASE_URL', $basePath);
}

if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', dirname(__DIR__));
}

if (!defined('STORAGE_PATH')) {
    define('STORAGE_PATH', PROJECT_ROOT . '/storage');
}
