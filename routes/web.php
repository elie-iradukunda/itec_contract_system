<?php

// ============================================
// WEB ROUTES 
// ============================================

// Home
$router->get('/', [\Controllers\HomeController::class, 'index']);

// Contract views
$router->get('/contracts', [\Controllers\ContractController::class, 'index']);
$router->get('/contracts/create', [\Controllers\ContractController::class, 'create']);
$router->get('/contracts/show/{id}', [\Controllers\ContractController::class, 'show']);
$router->get('/contracts/edit/{id}', [\Controllers\ContractController::class, 'edit']);
$router->get('/contracts/review/{id}', [\Controllers\ContractController::class, 'review']);
$router->get('/contracts/readonly/{id}', [\Controllers\ContractController::class, 'readonly']);
$router->get('/contracts/versions/{id}', [\Controllers\VersionController::class, 'index']);
$router->get('/contracts/audit/{id}', [\Controllers\AuditController::class, 'reviewerPanel']);
$router->get('/contracts/sign/{id}', [\Controllers\SignatureController::class, 'signPage']);
$router->get('/contracts/sign-company/{id}', [\Controllers\SignatureController::class, 'companySignPage']);
$router->get('/contracts/view/{id}', [\Controllers\ContractController::class, 'viewFinal']);

// Snapshot views
$router->get('/snapshots/{contractId}', function($contractId) {
    $snapshotService = new \Services\OscarSnapshotService();
    $snapshots = $snapshotService->getSnapshots($contractId);
    
    echo "<h1>Snapshots for Contract {$contractId}</h1>";
    echo "<pre>";
    print_r($snapshots);
    echo "</pre>";
});

// Auth views
$router->get('/auth/login', [\Controllers\AuthController::class, 'loginForm']);
$router->get('/auth/register', [\Controllers\AuthController::class, 'registerForm']);

// Portal
$router->get('/client/portal', [\Controllers\ClientController::class, 'portal']);



