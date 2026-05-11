<?php

// ============================================
// TASK O1: DIGITAL SIGNATURE API
// ============================================

// Staff/Admin submits contract for signing
$router->post('/api/contracts/{id}/submit', [\Controllers\ContractController::class, 'submitForSigning']);
// Sign a document
$router->post('/api/contracts/{id}/sign', [\Controllers\SignatureController::class, 'sign']);


// Verify document integrity
$router->get('/api/contracts/{id}/verify', [\Controllers\SignatureController::class, 'verify']);

// Get all signatures for a contract
$router->get('/api/contracts/{id}/signatures', [\Controllers\SignatureController::class, 'getSignatures']);

// Get signer chain
$router->get('/api/contracts/{id}/signer-chain', [\Controllers\SignatureController::class, 'getSignerChain']);

// ============================================
// TASK O2: COMPANY SEAL API
// ============================================

// Apply company seal to contract
$router->post('/api/contracts/{id}/seal', [\Controllers\SignatureController::class, 'applySeal']);

// Get seal information
$router->get('/api/contracts/{id}/seal-info', [\Controllers\SignatureController::class, 'getSealInfo']);

// ============================================
// TASK O3: STATE MACHINE API
// ============================================

// Get current state
$router->get('/api/contracts/{id}/state', [\Controllers\ContractController::class, 'getState']);

// Submit for signing (DRAFT -> AWAITING_CLIENT)
$router->post('/api/contracts/{id}/submit', [\Controllers\ContractController::class, 'submitForSigning']);

// Client signs (AWAITING_CLIENT -> CLIENT_SIGNED)
$router->post('/api/contracts/{id}/client-sign', [\Controllers\ContractController::class, 'clientSign']);

// Escalate to company (CLIENT_SIGNED -> AWAITING_COMPANY)
$router->post('/api/contracts/{id}/escalate', [\Controllers\ContractController::class, 'escalateToCompany']);

// Company signs (AWAITING_COMPANY -> FULLY_SIGNED)
$router->post('/api/contracts/{id}/company-sign', [\Controllers\ContractController::class, 'companySign']);

// Transition to specific state
$router->post('/api/contracts/{id}/transition', [\Controllers\ContractController::class, 'transition']);

// ============================================
// TASK O4: SNAPSHOT API
// ============================================

// Get all snapshots for a contract
$router->get('/api/contracts/{id}/snapshots', function($id) {
    $snapshotService = new \Services\OscarSnapshotService();
    $snapshots = $snapshotService->getSnapshots($id);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'contract_id' => $id,
        'snapshots' => $snapshots,
        'count' => count($snapshots)
    ]);
});

// Get snapshot by signature ID
$router->get('/api/snapshots/signature/{signatureId}', function($signatureId) {
    $snapshotService = new \Services\OscarSnapshotService();
    $snapshot = $snapshotService->getSnapshotBySignature($signatureId);
    
    header('Content-Type: application/json');
    if ($snapshot && file_exists($snapshot['snapshot_file_path'])) {
        echo json_encode([
            'success' => true,
            'snapshot_file' => $snapshot['snapshot_file_path']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Snapshot not found'
        ]);
    }
});

// Download snapshot PDF
$router->get('/api/snapshots/download/{signatureId}', function($signatureId) {
    $snapshotService = new \Services\OscarSnapshotService();
    $snapshot = $snapshotService->getSnapshotBySignature($signatureId);
    
    if ($snapshot && file_exists($snapshot['snapshot_file_path'])) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="snapshot_' . $signatureId . '.pdf"');
        readfile($snapshot['snapshot_file_path']);
        exit;
    } else {
        http_response_code(404);
        echo "Snapshot not found";
    }
});

// Create snapshot manually (for testing)
$router->post('/api/contracts/{id}/snapshot', function($id) {
    $input = json_decode(file_get_contents('php://input'), true);
    $signatureId = $input['signature_id'] ?? null;
    $sourceFile = $input['source_file'] ?? null;
    
    if (!$signatureId || !$sourceFile) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing signature_id or source_file']);
        return;
    }
    
    $snapshotService = new \Services\OscarSnapshotService();
    $result = $snapshotService->createSnapshot($id, $signatureId, 'system', $sourceFile);
    
    header('Content-Type: application/json');
    echo json_encode($result);
});

// ============================================
// TASK O5: AUDIT TRAIL API
// ============================================

// Get full audit trail
$router->get('/api/contracts/{id}/audit', [\Controllers\AuditController::class, 'index']);

// Get audit with verification
$router->get('/api/contracts/{id}/audit/verify', function($id) {
    $auditService = new \Services\OscarAuditService();
    $signatureService = new \Services\OscarSignatureService();
    
    $audit = $auditService->getAuditTrail($id);
    $verification = $signatureService->verifyDocument($id);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'contract_id' => $id,
        'verification' => $verification,
        'audit_trail' => $audit,
        'total_events' => count($audit)
    ]);
});

// Get full chain with signatures
$router->get('/api/contracts/{id}/audit/chain', function($id) {
    $auditService = new \Services\OscarAuditService();
    $chain = $auditService->getFullChain($id);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'contract_id' => $id,
        'chain' => $chain,
        'total' => count($chain)
    ]);
});

// Export audit as CSV
$router->get('/api/contracts/{id}/audit/export', function($id) {
    $auditService = new \Services\OscarAuditService();
    $audit = $auditService->getAuditTrail($id);
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="audit_contract_' . $id . '_' . date('Ymd_His') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Contract ID', 'Signer ID', 'Event Type', 'Document Hash', 'IP Address', 'User Agent', 'Timestamp']);
    
    foreach ($audit as $row) {
        fputcsv($output, [
            $row['id'],
            $row['contract_id'],
            $row['signer_id'],
            $row['event_type'],
            $row['doc_hash'],
            $row['ip_address'],
            $row['user_agent'],
            $row['timestamp']
        ]);
    }
    fclose($output);
});

// Get verification report
$router->get('/api/contracts/{id}/verification-report', function($id) {
    $signatureService = new \Services\OscarSignatureService();
    $auditService = new \Services\OscarAuditService();
    
    $verification = $signatureService->verifyDocument($id);
    $audit = $auditService->getAuditTrail($id);
    $chain = $signatureService->getSignerChain($id);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'contract_id' => $id,
        'verification' => $verification,
        'signer_chain' => $chain,
        'audit_summary' => [
            'total_events' => count($audit),
            'first_event' => $audit[0]['timestamp'] ?? null,
            'last_event' => $audit[count($audit)-1]['timestamp'] ?? null
        ]
    ]);
});

// ============================================
// CONTRACT CRUD API
// ============================================

$router->get('/api/contracts', [\Controllers\ContractController::class, 'apiIndex']);
$router->get('/api/contracts/{id}', [\Controllers\ContractController::class, 'apiShow']);
$router->post('/api/contracts', [\Controllers\ContractController::class, 'apiStore']);
$router->put('/api/contracts/{id}', [\Controllers\ContractController::class, 'apiUpdate']);
$router->delete('/api/contracts/{id}', [\Controllers\ContractController::class, 'apiDelete']);

// ============================================
// HARD COPY UPLOAD API
// ============================================

$router->post('/api/contracts/{id}/upload-hard-copy', [\Controllers\UploadController::class, 'uploadHardCopy']);

// ============================================
// DISTRIBUTION API
// ============================================

$router->post('/api/contracts/{id}/distribute', [\Controllers\ContractController::class, 'distribute']);
$router->get('/api/access/{token}', [\Controllers\ContractController::class, 'tokenAccess']);