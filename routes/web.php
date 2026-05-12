<?php

// ============================================
// WEB ROUTES (Elie - Frontend)
// Authentication handled by parent Finance System
// ============================================

// Home
$router->get('/', [\Controllers\HomeController::class, 'index']);

// Dashboard
$router->get('/dashboard', [\Controllers\ContractController::class, 'dashboard']);

// ============================================
// CONTRACT MANAGEMENT
// ============================================
$router->get('/contracts', [\Controllers\ContractController::class, 'index']);
$router->get('/contracts/create', [\Controllers\ContractController::class, 'create']);
$router->post('/contracts/store', [\Controllers\ContractController::class, 'store']);
$router->get('/contracts/show/{id}', [\Controllers\ContractController::class, 'show']);
$router->get('/contracts/edit/{id}', [\Controllers\ContractController::class, 'edit']);
$router->get('/contracts/{id}/edit', [\Controllers\ContractController::class, 'edit']);
$router->post('/contracts/update/{id}', [\Controllers\ContractController::class, 'update']);
$router->get('/contracts/review/{id}', [\Controllers\ContractController::class, 'review']);
$router->get('/contracts/{id}/review', [\Controllers\ContractController::class, 'review']);
$router->get('/contracts/readonly/{id}', [\Controllers\ContractController::class, 'readonly']);
$router->get('/contracts/{id}/readonly', [\Controllers\ContractController::class, 'readonly']);
$router->get('/contracts/view/{id}', [\Controllers\ContractController::class, 'viewFinal']);
$router->get('/contracts/execution-status/{id}', [\Controllers\ContractController::class, 'executionStatus']);
$router->get('/contracts/final-pdf/{id}', [\Controllers\ContractController::class, 'finalPDF']);
$router->get('/contracts/audit-trail/{id}', [\Controllers\ContractController::class, 'auditTrailView']);

// Tokenized access (no login required)
$router->get('/access/{token}', [\Controllers\ContractController::class, 'tokenAccess']);
// Choice page (after token validation)
$router->get('/sign/{id}', [\Controllers\ContractController::class, 'signPage']);

// Digital signature page
$router->get('/sign-digitally/{id}', [\Controllers\SignatureController::class, 'digitalSignPage']);

// Upload hard copy page
$router->get('/upload-contract/{id}', [\Controllers\UploadController::class, 'uploadPage']);
// ============================================
// TASK E1: IN-BROWSER EDITOR
// ============================================
$router->get('/contracts/{id}/editor', [\Controllers\ContractController::class, 'editor']);

$router->post('/contracts/{id}/save', [\Controllers\ContractController::class, 'saveDocument']);
$router->get('/contracts/{id}/content', [\Controllers\ContractController::class, 'getDocumentContent']);
$router->get('/sign/success/{id}', [\Controllers\ContractController::class, 'signSuccessPage']);
// ============================================
// TASK E2: VERSION CONTROL
// ============================================
$router->get('/contracts/{id}/versions', [\Controllers\VersionController::class, 'index']);
$router->post('/contracts/{id}/versions/{version}/restore', [\Controllers\VersionController::class, 'restore']);
$router->get('/contracts/{id}/versions/{version}/download', [\Controllers\VersionController::class, 'download']);
$router->get('/contracts/{id}/versions/compare/{v1}/{v2}', [\Controllers\VersionController::class, 'compare']);

// ============================================
// TASK E3: TRACKED CHANGES
// ============================================
$router->get('/contracts/{id}/changes', [\Controllers\ContractController::class, 'getChanges']);
$router->post('/contracts/{id}/changes/{change}/accept', [\Controllers\ContractController::class, 'acceptChange']);
$router->post('/contracts/{id}/changes/{change}/reject', [\Controllers\ContractController::class, 'rejectChange']);
$router->get('/contracts/{id}/changes-panel', [\Controllers\ContractController::class, 'changesPanel']);

// ============================================
// TASK E4: SIGNING CHOICE + HARD COPY
// ============================================
$router->get('/contracts/{id}/signing-choice', [\Controllers\SignatureController::class, 'showChoice']);
$router->post('/contracts/{id}/signing-choice', [\Controllers\SignatureController::class, 'handleChoice']);
$router->get('/contracts/{id}/sign-digitally', [\Controllers\SignatureController::class, 'signPage']);
$router->get('/contracts/sign/{id}', [\Controllers\SignatureController::class, 'signPage']);
$router->get('/contracts/sign-company/{id}', [\Controllers\ContractController::class, 'readonly']);
$router->get('/contracts/{id}/print-pdf', [\Controllers\ContractController::class, 'generatePrintPDF']);
$router->post('/contracts/{id}/upload-signed-copy', [\Controllers\UploadController::class, 'uploadSignedCopy']);
$router->get('/contracts/{id}/upload-hard-copy', [\Controllers\UploadController::class, 'uploadHardCopyPage']);

// ============================================
// TASK E5: BODY LOCK + DISTRIBUTION
// ============================================
$router->get('/contracts/{id}/status', [\Controllers\ContractController::class, 'getStatus']);
$router->post('/contracts/{id}/distribute', [\Controllers\ContractController::class, 'distribute']);
$router->get('/access/{token}', [\Controllers\ContractController::class, 'tokenAccess']);
$router->get('/view/{token}', [\Controllers\ContractController::class, 'tokenAccess']);
$router->get('/contracts/{id}/distributions', [\Controllers\ContractController::class, 'getDistributions']);

// ============================================
// CLIENT PORTAL
// ============================================
$router->get('/clients/portal', [\Controllers\ClientController::class, 'portal']);
$router->get('/clients/{id}/show', [\Controllers\ClientController::class, 'show']);
$router->get('/clients/contracts/{id}', [\Controllers\ClientController::class, 'clientContracts']);

// ============================================
// SIGNATURE PAGES
// ============================================
$router->get('/signatures/sign', [\Controllers\SignatureController::class, 'signPage']);
$router->get('/signatures/seal', [\Controllers\SignatureController::class, 'sealPage']);
$router->get('/signatures/verify', [\Controllers\SignatureController::class, 'verifyPage']);

// ============================================
// SNAPSHOT VIEW (Debug)
// ============================================
$router->get('/snapshots/{contractId}', function($contractId) {
    $snapshotService = new \Services\OscarSnapshotService();
    $snapshots = $snapshotService->getSnapshots($contractId);
    
    echo "<h1>Snapshots for Contract {$contractId}</h1>";
    echo "<pre>";
    print_r($snapshots);
    echo "</pre>";
});
