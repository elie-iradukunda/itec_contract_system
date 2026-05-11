<?php

namespace Controllers;

use Core\Controller;
use Core\Database;
use Services\OscarStateMachineService;

class ContractController extends Controller
{
    private $contractService;
    private $auditService;
    private $db;

    public function __construct($contractService = null, $auditService = null)
    {
        parent::__construct();
        $this->db = Database::getInstance()->getConnection();
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
        $contract = $this->contractService->getEditorData((int) $id);
        $this->view('contracts/editor', ['contract' => $contract]);
    }

    public function saveDocument($id)
    {
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
        $contract = $this->contractService->getEditorData((int) $id);

        $this->json([
            'success' => true,
            'signing_state' => $contract['signing_state'] ?? 'DRAFT'
        ]);
    }

    public function downloadDocument($id)
    {
        $this->contractService->downloadEditorFile((int) $id);
    }

    private function currentUserId()
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        return $_SESSION['user_id'] ?? null;
    }

    public function getState($id)
    {
        $stateMachine = new OscarStateMachineService($id);
        $stateInfo = $stateMachine->getCurrentState();
        
        $this->json([
            'contract_id' => $id,
            'current_state' => $stateInfo['state'],
            'allowed_actions' => $stateInfo['allowed_actions'],
            'timeline' => $stateInfo['timeline']
        ]);
    }

    public function submitForSigning($id)
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $signerId = $input['signer_id'] ?? $_POST['signer_id'] ?? 'staff@itec.com';
        
        $stmt = $this->db->prepare("SELECT id, title, signing_state FROM contracts WHERE id = ?");
        $stmt->execute([$id]);
        $contract = $stmt->fetch();
        
        if (!$contract) {
            $this->json(['success' => false, 'error' => 'Contract not found'], 404);
            return;
        }
        
        if ($contract['signing_state'] !== 'DRAFT') {
            $this->json([
                'success' => false, 
                'error' => 'Contract cannot be submitted. Current state: ' . $contract['signing_state']
            ], 400);
            return;
        }
        
        $stateMachine = new OscarStateMachineService($id);
        $result = $stateMachine->submitForSigning($signerId);
        
        $this->json($result);
    }

    public function transition($id)
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $targetState = $input['target_state'] ?? null;
        $signerId = $input['signer_id'] ?? 'system';
        
        if (!$targetState) {
            $this->json(['success' => false, 'error' => 'target_state required'], 400);
            return;
        }
        
        $stateMachine = new OscarStateMachineService($id);
        $result = $stateMachine->transition($targetState, $signerId);
        
        $this->json($result);
    }
    
    public function editor($id)
    {
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
        
        $isLocked = $contract['signing_state'] !== 'DRAFT';
        
        $versionStmt = $db->prepare("
            SELECT version_no, file_path, saved_at 
            FROM doc_versions 
            WHERE contract_id = ? 
            ORDER BY version_no DESC 
            LIMIT 1
        ");
        $versionStmt->execute([$id]);
        $latestVersion = $versionStmt->fetch();
        
        $filePath = $contract['file_path'];
        if ($latestVersion && file_exists($latestVersion['file_path'])) {
            $filePath = $latestVersion['file_path'];
        }
        
        $content = '';
        if ($filePath && file_exists($filePath)) {
            $content = file_get_contents($filePath);
        } else {
            $content = '<p>Start editing your contract here...</p>';
        }
        
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
        $db = \Core\Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("
            SELECT id, author_id, original_text, new_text, status, changed_at 
            FROM doc_tracked_changes 
            WHERE contract_id = ? 
            ORDER BY changed_at DESC
        ");
        $stmt->execute([$id]);
        $changes = $stmt->fetchAll();
        
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
        
        $db->beginTransaction();
        
        try {
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
            
            $updateStmt = $db->prepare("
                UPDATE doc_tracked_changes 
                SET status = 'accepted' 
                WHERE id = ?
            ");
            $updateStmt->execute([$changeId]);
            
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
        
        $db->beginTransaction();
        
        try {
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
            
            $updateStmt = $db->prepare("
                UPDATE doc_tracked_changes 
                SET status = 'rejected' 
                WHERE id = ?
            ");
            $updateStmt->execute([$changeId]);
            
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

public function apiStore()
{
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $clientName = $input['client_name'] ?? $_POST['client_name'] ?? null;
    $clientEmail = $input['client_email'] ?? $_POST['client_email'] ?? null;
    $title = $input['title'] ?? $_POST['title'] ?? null;
    
    if (!$clientName || !$clientEmail || !$title) {
        $this->json([
            'success' => false, 
            'error' => 'Missing required fields: client_name, client_email, title'
        ], 400);
        return;
    }
    
    try {
        // Insert into database first to get contract ID
        $sql = "INSERT INTO contracts (client_name, client_email, title, description, signing_state, created_by) 
                VALUES (:client_name, :client_email, :title, :description, 'DRAFT', :created_by)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'client_name' => $clientName,
            'client_email' => $clientEmail,
            'title' => $title,
            'description' => $input['description'] ?? null,
            'created_by' => $input['created_by'] ?? $_POST['created_by'] ?? 'api_user'
        ]);
        
        $contractId = $this->db->lastInsertId();
        
        // Generate document using DocumentGeneratorService
        $docGenerator = new \Services\DocumentGeneratorService();
        $filePath = $docGenerator->generateContract($contractId, [
            'title' => $title,
            'client_name' => $clientName,
            'client_email' => $clientEmail,
            'content' => $input['content'] ?? null,
            'sections' => $input['sections'] ?? null,
            'services' => $input['services'] ?? null,
            'amount' => $input['amount'] ?? null,
            'payment_terms' => $input['payment_terms'] ?? null,
            'start_date' => $input['start_date'] ?? null,
            'duration' => $input['duration'] ?? null,
            'termination' => $input['termination'] ?? null,
            'governing_law' => $input['governing_law'] ?? 'Rwanda',
            'effective_date' => $input['effective_date'] ?? null
        ]);
        
        // Update contract with file path
        $updateSql = "UPDATE contracts SET file_path = :file_path WHERE id = :id";
        $updateStmt = $this->db->prepare($updateSql);
        $updateStmt->execute([
            'file_path' => $filePath,
            'id' => $contractId
        ]);
        
        $versionSql = "INSERT INTO doc_versions (contract_id, version_no, saved_by, file_path, saved_at) 
                       VALUES (:contract_id, 1, :saved_by, :file_path, NOW())";
        $versionStmt = $this->db->prepare($versionSql);
        $versionStmt->execute([
            'contract_id' => $contractId,
            'saved_by' => $input['created_by'] ?? 'system',
            'file_path' => $filePath
        ]);
        
        $this->json([
            'success' => true,
            'message' => 'Contract created successfully',
            'contract_id' => $contractId,
            'contract' => [
                'id' => $contractId,
                'title' => $title,
                'client_name' => $clientName,
                'client_email' => $clientEmail,
                'signing_state' => 'DRAFT',
                'file_path' => $filePath,
                'created_at' => date('Y-m-d H:i:s')
            ]
        ]);
        
    } catch (PDOException $e) {
        $this->json([
            'success' => false, 
            'error' => 'Database error: ' . $e->getMessage()
        ], 500);
    } catch (Exception $e) {
        $this->json([
            'success' => false, 
            'error' => 'Error: ' . $e->getMessage()
        ], 500);
    }
}

}
