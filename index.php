<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__."/config/constants.php";

use Core\Container;
use Core\Router;
use Core\Database;
use Core\Mail;

// Load .env file
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env');
    foreach ($lines as $line) {
        $line = trim($line);
        if (!empty($line) && strpos($line, '=') !== false) {
            putenv($line);
            $_ENV[explode('=', $line)[0]] = explode('=', $line)[1];
        }
    }
}

// Initialize container
$container = new Container();

// Register Database
$container->singleton(Database::class, function() {
    return Database::getInstance();
});

$container->bind(\Models\Contract::class, function() {
    return new \Models\Contract();
});

$container->bind(\Models\ContractVersion::class, function() {
    return new \Models\ContractVersion();
});

$container->bind(\Models\Client::class, function() {
    return new \Models\Client();
});

$container->bind(\Models\AuditLog::class, function() {
    return new \Models\AuditLog();
});

$container->singleton(Mail::class, function($container) {
    return new Mail();
});

// Register Services
$container->bind(\Services\ContractService::class, function($container) {
    return new \Services\ContractService(
        $container->resolve(\Models\Contract::class),
        $container->resolve(\Models\ContractVersion::class)
    );
});

$container->bind(\Services\OscarSignatureService::class, function($container) {
    return new \Services\OscarSignatureService();
});

$container->bind(\Services\OscarSealService::class, function($container) {
    return new \Services\OscarSealService();
});

$container->bind(\Services\OscarSnapshotService::class, function($container) {
    return new \Services\OscarSnapshotService();
});

$container->bind(\Services\OscarAuditService::class, function($container) {
    return new \Services\OscarAuditService();
});

// Register Controllers
$container->bind(\Controllers\HomeController::class, function($container) {
    return new \Controllers\HomeController($container);
});

$container->bind(\Controllers\ContractController::class, function($container) {
    return new \Controllers\ContractController(
        $container->resolve(\Services\ContractService::class),
        $container->resolve(\Services\OscarAuditService::class)
    );
});

$container->bind(\Controllers\SignatureController::class, function($container) {
    return new \Controllers\SignatureController();
});

$container->bind(\Controllers\AuditController::class, function($container) {
    return new \Controllers\AuditController();
});

$container->bind(\Controllers\ClientController::class, function($container) {
    return new \Controllers\ClientController($container->resolve(\Models\Client::class));
});

$container->bind(\Controllers\AuthController::class, function($container) {
    return new \Controllers\AuthController();
});

$container->bind(\Controllers\VersionController::class, function($container) {
    return new \Controllers\VersionController($container->resolve(\Models\ContractVersion::class));
});

$container->bind(\Controllers\UploadController::class, function($container) {
    return new \Controllers\UploadController();
});

$container->bind(\Controllers\MigrationController::class, function($container) {
    return new \Controllers\MigrationController();
});

// Initialize router
$router = new Router($container);

// Remove base path from URI
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = BASE_URL;
if (strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
}
if (empty($requestUri) || $requestUri == '') {
    $requestUri = '/';
}

// ============================================
// INCLUDE ROUTES
// ============================================

require_once __DIR__ . '/routes/web.php';
require_once __DIR__ . '/routes/api.php';

// ============================================
// MIGRATION ROUTES
// ============================================
$router->get('/migrate', [\Controllers\MigrationController::class, 'run']);
$router->get('/migrate/rollback', [\Controllers\MigrationController::class, 'rollback']);
$router->get('/seed', function() {
    require_once __DIR__ . '/seed.php';
    echo "<br><a href='/'>Back to Home</a>";
});

// ============================================
// Dispatch the request
// ============================================
$method = $_SERVER['REQUEST_METHOD'];
$router->dispatch($method, $requestUri);
