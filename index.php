<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/vendor/autoload.php';

use Core\Container;
use Core\Router;
use Core\Database;

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

// Register Models
$container->bind(\Models\User::class, function($container) {
    return new \Models\User();
});
$container->bind(\Models\Contract::class, function($container) {
    return new \Models\Contract();
});
$container->bind(\Models\Client::class, function($container) {
    return new \Models\Client();
});
$container->bind(\Models\ContractVersion::class, function($container) {
    return new \Models\ContractVersion();
});
$container->bind(\Models\ContractSignature::class, function($container) {
    return new \Models\ContractSignature();
});
$container->bind(\Models\AuditLog::class, function($container) {
    return new \Models\AuditLog();
});

// Register Services
$container->bind(\Services\ContractService::class, function($container) {
    return new \Services\ContractService(
        $container->resolve(\Models\Contract::class)
    );
});
$container->bind(\Services\SignatureService::class, function($container) {
    return new \Services\SignatureService(
        $container->resolve(\Models\ContractSignature::class)
    );
});
$container->bind(\Services\AuditService::class, function($container) {
    return new \Services\AuditService(
        $container->resolve(\Models\AuditLog::class)
    );
});

// Register Controllers - Elie (Front-end)
$container->bind(\Controllers\HomeController::class, function($container) {
    return new \Controllers\HomeController($container);
});
$container->bind(\Controllers\ContractController::class, function($container) {
    return new \Controllers\ContractController(
        $container->resolve(\Services\ContractService::class),
        $container->resolve(\Services\AuditService::class)
    );
});
$container->bind(\Controllers\VersionController::class, function($container) {
    return new \Controllers\VersionController(
        $container->resolve(\Models\ContractVersion::class)
    );
});
$container->bind(\Controllers\UploadController::class, function($container) {
    return new \Controllers\UploadController();
});

// Register Controllers - Oscar (Back-end)
$container->bind(\Controllers\SignatureController::class, function($container) {
    return new \Controllers\SignatureController(
        $container->resolve(\Services\SignatureService::class),
        $container->resolve(\Services\AuditService::class)
    );
});
$container->bind(\Controllers\AuditController::class, function($container) {
    return new \Controllers\AuditController(
        $container->resolve(\Services\AuditService::class)
    );
});
$container->bind(\Controllers\MigrationController::class, function($container) {
    return new \Controllers\MigrationController();
});

// Initialize router
$router = new Router($container);

// Remove base path from URI
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = '/itec_contract_system';
if (strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
}
if (empty($requestUri) || $requestUri == '') {
    $requestUri = '/';
}

// ============================================
// ELIE ROUTES (Front-end - Views & Pages)
// ============================================

// Home & Dashboard
$router->get('/', [\Controllers\HomeController::class, 'index']);
$router->get('/dashboard', [\Controllers\ContractController::class, 'dashboard']);

// Contract Management
$router->get('/contracts', [\Controllers\ContractController::class, 'index']);
$router->get('/contracts/create', [\Controllers\ContractController::class, 'create']);
$router->post('/contracts/store', [\Controllers\ContractController::class, 'store']);
$router->get('/contracts/show/{id}', [\Controllers\ContractController::class, 'show']);
$router->get('/contracts/edit/{id}', [\Controllers\ContractController::class, 'edit']);
$router->post('/contracts/update/{id}', [\Controllers\ContractController::class, 'update']);
$router->get('/contracts/readonly/{id}', [\Controllers\ContractController::class, 'readonly']);
$router->get('/contracts/review/{id}', [\Controllers\ContractController::class, 'review']);

// Document Editor (Elie)
$router->get('/contracts/{id}/edit', [\Controllers\ContractController::class, 'editor']);
$router->post('/contracts/{id}/save', [\Controllers\ContractController::class, 'saveDocument']);
$router->get('/contracts/{id}/status', [\Controllers\ContractController::class, 'getStatus']);

// Version Control (Elie)
$router->get('/contracts/{id}/versions', [\Controllers\VersionController::class, 'index']);
$router->post('/contracts/{id}/versions/{version}/restore', [\Controllers\VersionController::class, 'restore']);
$router->get('/contracts/{id}/versions/{version}/download', [\Controllers\VersionController::class, 'download']);

// Tracked Changes (Elie)
$router->get('/contracts/{id}/changes', [\Controllers\ContractController::class, 'getChanges']);
$router->post('/contracts/{id}/changes/{change}/accept', [\Controllers\ContractController::class, 'acceptChange']);
$router->post('/contracts/{id}/changes/{change}/reject', [\Controllers\ContractController::class, 'rejectChange']);

// Signing Choice UI (Elie)
$router->get('/contracts/{id}/signing-choice', [\Controllers\SignatureController::class, 'showChoice']);
$router->post('/contracts/{id}/signing-choice', [\Controllers\SignatureController::class, 'handleChoice']);

// Hard Copy Flow (Elie)
$router->get('/contracts/{id}/print-pdf', [\Controllers\ContractController::class, 'generatePrintPDF']);
$router->post('/contracts/{id}/upload-signed', [\Controllers\UploadController::class, 'uploadSignedCopy']);

// Distribution (Elie)
$router->post('/contracts/{id}/distribute', [\Controllers\ContractController::class, 'distribute']);
$router->get('/access/{token}', [\Controllers\ContractController::class, 'tokenAccess']);

// Client Portal (Elie)
$router->get('/clients/portal', [\Controllers\ClientController::class, 'portal']);
$router->get('/clients/{id}/show', [\Controllers\ClientController::class, 'show']);

// Authentication Views (Elie)
$router->get('/auth/login', [\Controllers\AuthController::class, 'loginForm']);
$router->get('/auth/register', [\Controllers\AuthController::class, 'registerForm']);
$router->get('/auth/forgot-password', [\Controllers\AuthController::class, 'forgotPasswordForm']);
$router->get('/auth/reset-password', [\Controllers\AuthController::class, 'resetPasswordForm']);

// Signature Pages (Elie)
$router->get('/signatures/sign', [\Controllers\SignatureController::class, 'signPage']);
$router->get('/signatures/seal', [\Controllers\SignatureController::class, 'sealPage']);
$router->get('/signatures/verify', [\Controllers\SignatureController::class, 'verifyPage']);

// Execution Status (Elie)
$router->get('/contracts/{id}/execution-status', [\Controllers\ContractController::class, 'executionStatus']);
$router->get('/contracts/{id}/final-pdf', [\Controllers\ContractController::class, 'finalPDF']);
$router->get('/contracts/{id}/audit-trail', [\Controllers\ContractController::class, 'auditTrailView']);

// ============================================
// OSCAR ROUTES (Back-end - API Endpoints)
// ============================================

// Authentication API (Oscar)
$router->post('/api/auth/login', [\Controllers\AuthController::class, 'login']);
$router->post('/api/auth/register', [\Controllers\AuthController::class, 'register']);
$router->post('/api/auth/logout', [\Controllers\AuthController::class, 'logout']);
$router->post('/api/auth/forgot-password', [\Controllers\AuthController::class, 'forgotPassword']);
$router->post('/api/auth/reset-password', [\Controllers\AuthController::class, 'resetPassword']);
$router->post('/api/auth/resend-verification', [\Controllers\AuthController::class, 'resendVerification']);

// State Machine (Oscar)
$router->post('/api/contracts/{id}/submit', [\Controllers\ContractController::class, 'submitForSigning']);
$router->post('/api/contracts/{id}/transition', [\Controllers\ContractController::class, 'transition']);
$router->get('/api/contracts/{id}/state', [\Controllers\ContractController::class, 'getState']);

// Digital Signatures (Oscar)
$router->post('/api/contracts/{id}/sign', [\Controllers\SignatureController::class, 'sign']);
$router->get('/api/contracts/{id}/verify', [\Controllers\SignatureController::class, 'verify']);
$router->get('/api/contracts/{id}/signatures', [\Controllers\SignatureController::class, 'getSignatures']);

// Company Seal (Oscar)
$router->post('/api/contracts/{id}/seal', [\Controllers\SignatureController::class, 'applySeal']);

// Snapshots (Oscar)
$router->get('/api/contracts/{id}/snapshots', [\Controllers\ContractController::class, 'getSnapshots']);
$router->post('/api/contracts/{id}/snapshot', [\Controllers\ContractController::class, 'createSnapshot']);
$router->get('/api/contracts/{id}/snapshots/{signatureId}/download', [\Controllers\ContractController::class, 'downloadSnapshot']);

// Audit Trail (Oscar)
$router->get('/api/contracts/{id}/audit', [\Controllers\AuditController::class, 'index']);
$router->get('/api/contracts/{id}/audit/export', [\Controllers\AuditController::class, 'export']);
$router->get('/api/contracts/{id}/verification-report', [\Controllers\AuditController::class, 'verificationReport']);
$router->post('/api/audit/log', [\Controllers\AuditController::class, 'log']);

// Hard Copy Upload API (Oscar)
$router->post('/api/contracts/{id}/upload-hard-copy', [\Controllers\UploadController::class, 'uploadHardCopy']);

// Distribution API (Oscar)
$router->post('/api/contracts/{id}/distribute', [\Controllers\ContractController::class, 'distributeAPI']);

// ============================================
// MIGRATION ROUTES
// ============================================
$router->get('/migrate', [\Controllers\MigrationController::class, 'run']);
$router->get('/migrate/rollback', [\Controllers\MigrationController::class, 'rollback']);

// ============================================
// Dispatch the request
// ============================================
$method = $_SERVER['REQUEST_METHOD'];
$router->dispatch($method, $requestUri);