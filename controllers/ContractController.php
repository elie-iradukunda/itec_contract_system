<?php

namespace Controllers;

use Core\Controller;

class ContractController extends Controller
{
    private $contractService;
    private $auditService;

    public function __construct($contractService = null, $auditService = null)
    {
        parent::__construct();
        $this->contractService = $contractService;
        $this->auditService = $auditService;
    }

    public function index()
    {
        $this->view('contracts/index', [
            'title' => 'Contracts'
        ]);
    }
        public function create()
        {
            $this->view('contracts/create', [
                'title' => 'Create New Contract'
            ]);
        }

    public function show($id)
    {
        $this->json([
            'success' => true,
            'contract_id' => $id,
            'message' => 'Contract found'
        ]);
    }

    public function edit($id)
    {
        // Open the in-browser editor page.
        $contract = $this->contractService->getEditorData((int) $id);
        $this->view('contracts/editor', ['contract' => $contract]);
    }

    public function saveDocument($id)
    {
        // Save CKEditor content or an uploaded DOCX and create a version.
        try {
            $file = $_FILES['contract_file'] ?? null;
            $savedBy = $this->currentUserId();

            if ($file && $file['error'] === UPLOAD_ERR_OK) {
                $result = $this->contractService->saveEditorFile((int) $id, $file, $savedBy);
            } else {
                $content = $_POST['content'] ?? '';
                $result = $this->contractService->saveEditorContent((int) $id, $content, $savedBy);
            }

            $this->json([
                'success' => true,
                'message' => 'Contract saved successfully',
                'version' => $result['version'] ?? null
            ]);
        } catch (\Throwable $error) {
            $this->json(['success' => false, 'message' => $error->getMessage()], 500);
        }
    }

    public function getStatus($id)
    {
        // Return the contract state used by the editor polling UI.
        $contract = $this->contractService->getEditorData((int) $id);

        $this->json([
            'success' => true,
            'signing_state' => $contract['signing_state'] ?? 'DRAFT'
        ]);
    }

    public function downloadDocument($id)
    {
        // Stream the current browser-edited DOCX file.
        $this->contractService->downloadEditorFile((int) $id);
    }

    private function currentUserId()
    {
        // Read the active user id when auth is available.
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        return $_SESSION['user_id'] ?? null;
    }



public function getState($id)
{
    $stateMachine = new \Services\OscarStateMachineService();
    $state = $stateMachine->getCurrentState($id);
    $nextState = $stateMachine->getNextState($state);
    
    $this->json([
        'contract_id' => $id,
        'current_state' => $state,
        'next_state' => $nextState,
        'can_transition' => ($nextState !== null)
    ]);
}

public function submitForSigning($id)
{
    session_start();
    $userId = $_SESSION['user_id'] ?? null;
    
    $stateMachine = new \Services\OscarStateMachineService();
    $result = $stateMachine->submitForSigning($id, $userId);
    
    $this->json($result);
}

public function transition($id)
{
    session_start();
    $userId = $_SESSION['user_id'] ?? null;
    
    $stateMachine = new \Services\OscarStateMachineService();
    $currentState = $stateMachine->getCurrentState($id);
    
    if (!$currentState) {
        $this->json(['success' => false, 'error' => 'Contract not found'], 404);
        return;
    }
    
    $result = $stateMachine->transition($id, $currentState, $userId);
    $this->json($result);
}
public function editor($id)
{
    // Get contract data directly from database
    $db = \Core\Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("
        SELECT id, title, description, file_path, signing_state, created_by, created_at, updated_at
        FROM contracts 
        WHERE id = ?
    ");
    $stmt->execute([$id]);
    $contract = $stmt->fetch();
    
    if (!$contract) {
        $this->view('errors/404', [
            'title' => 'Contract Not Found',
            'message' => 'Contract with ID ' . $id . ' does not exist'
        ]);
        return;
    }
    
    // Determine if body is frozen (locked)
    $isLocked = $contract['signing_state'] !== 'DRAFT';
    
    // Get the latest version from doc_versions
    $versionStmt = $db->prepare("
        SELECT version_no, file_path, saved_at 
        FROM doc_versions 
        WHERE contract_id = ? 
        ORDER BY version_no DESC 
        LIMIT 1
    ");
    $versionStmt->execute([$id]);
    $latestVersion = $versionStmt->fetch();
    
    // Get file path
    $filePath = $contract['file_path'];
    if ($latestVersion && file_exists($latestVersion['file_path'])) {
        $filePath = $latestVersion['file_path'];
    }
    
    // Load document content
    $content = '';
    if ($filePath && file_exists($filePath)) {
        $content = file_get_contents($filePath);
    } else {
        $content = '<p>Start editing your contract here...</p>';
    }
    
    // Build the contract array expected by the view
    $contractData = [
        'id' => $id,
        'title' => $contract['title'],
        'description' => $contract['description'],
        'signing_state' => $contract['signing_state'],
        'file_path' => $filePath,
        'content' => $content,
        'created_by' => $contract['created_by'],
        'created_at' => $contract['created_at'],
        'updated_at' => $contract['updated_at'],
        'latest_version' => $latestVersion['version_no'] ?? 0,
        'last_saved_at' => $latestVersion['saved_at'] ?? $contract['updated_at']
    ];
    
    $this->view('contracts/editor', [
        'contract' => $contractData,
        'title' => 'Contract Editor'
    ]);
}
public function getChanges($id)
{
    // Get tracked changes for this contract
    $db = \Core\Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("
        SELECT id, author_id, original_text, new_text, status, changed_at 
        FROM doc_tracked_changes 
        WHERE contract_id = ? 
        ORDER BY changed_at DESC
    ");
    $stmt->execute([$id]);
    $changes = $stmt->fetchAll();
    
    // Get current signing state to check if body is locked
    $stateStmt = $db->prepare("SELECT signing_state FROM contracts WHERE id = ?");
    $stateStmt->execute([$id]);
    $contract = $stateStmt->fetch();
    
    $isLocked = $contract['signing_state'] !== 'DRAFT';
    
    $this->json([
        'success' => true,
        'contract_id' => $id,
        'is_locked' => $isLocked,
        'changes' => $changes,
        'total' => count($changes),
        'pending_count' => $this->countPendingChanges($db, $id)
    ]);
}

public function acceptChange($id, $changeId)
{
    $db = \Core\Database::getInstance()->getConnection();
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        // Get the change details
        $stmt = $db->prepare("
            SELECT contract_id, original_text, new_text, status 
            FROM doc_tracked_changes 
            WHERE id = ? AND contract_id = ?
        ");
        $stmt->execute([$changeId, $id]);
        $change = $stmt->fetch();
        
        if (!$change) {
            $this->json(['success' => false, 'error' => 'Change not found'], 404);
            return;
        }
        
        if ($change['status'] !== 'pending') {
            $this->json(['success' => false, 'error' => 'Change already processed'], 400);
            return;
        }
        
        // Update the change status
        $updateStmt = $db->prepare("
            UPDATE doc_tracked_changes 
            SET status = 'accepted' 
            WHERE id = ?
        ");
        $updateStmt->execute([$changeId]);
        
        // Write to audit log
        $auditStmt = $db->prepare("
            INSERT INTO doc_signature_audit 
            (contract_id, signer_id, event_type, doc_hash, ip_address, user_agent, timestamp) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $auditStmt->execute([
            $id,
            $_SESSION['user_id'] ?? 'system',
            'change_accepted',
            null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        
        $db->commit();
        
        $this->json([
            'success' => true,
            'message' => 'Change accepted successfully'
        ]);
        
    } catch (\Exception $e) {
        $db->rollBack();
        $this->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

public function rejectChange($id, $changeId)
{
    $db = \Core\Database::getInstance()->getConnection();
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        // Get the change details
        $stmt = $db->prepare("
            SELECT contract_id, status 
            FROM doc_tracked_changes 
            WHERE id = ? AND contract_id = ?
        ");
        $stmt->execute([$changeId, $id]);
        $change = $stmt->fetch();
        
        if (!$change) {
            $this->json(['success' => false, 'error' => 'Change not found'], 404);
            return;
        }
        
        if ($change['status'] !== 'pending') {
            $this->json(['success' => false, 'error' => 'Change already processed'], 400);
            return;
        }
        
        // Update the change status
        $updateStmt = $db->prepare("
            UPDATE doc_tracked_changes 
            SET status = 'rejected' 
            WHERE id = ?
        ");
        $updateStmt->execute([$changeId]);
        
        // Write to audit log
        $auditStmt = $db->prepare("
            INSERT INTO doc_signature_audit 
            (contract_id, signer_id, event_type, doc_hash, ip_address, user_agent, timestamp) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $auditStmt->execute([
            $id,
            $_SESSION['user_id'] ?? 'system',
            'change_rejected',
            null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        
        $db->commit();
        
        $this->json([
            'success' => true,
            'message' => 'Change rejected successfully'
        ]);
        
    } catch (\Exception $e) {
        $db->rollBack();
        $this->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

private function countPendingChanges($db, $contractId)
{
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM doc_tracked_changes 
        WHERE contract_id = ? AND status = 'pending'
    ");
    $stmt->execute([$contractId]);
    $result = $stmt->fetch();
    return $result['count'] ?? 0;
}
}
