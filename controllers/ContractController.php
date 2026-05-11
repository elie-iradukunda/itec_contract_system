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

    public function dashboard()
    {
        $this->index();
    }
    
    public function create()
    {
        $this->view('contracts/create', [
            'title' => 'Create New Contract'
        ]);
    }

    public function show($id)
    {
        $contract = $this->contractService->getContractById((int) $id);
        if (!$contract) {
            $this->view('errors/404', ['message' => 'Contract not found']);
            return;
        }

        $this->view('contracts/show', ['contract' => $contract, 'title' => $contract['title']]);
    }

    public function edit($id)
    {
        $this->editor($id);
    }

    public function store()
    {
        $this->apiStore();
    }

    public function update($id)
    {
        $this->contractService->updateContract((int) $id, $_POST ?: $this->requestData());
        header('Location: ' . BASE_URL . '/contracts/show/' . (int) $id);
        exit;
    }

    public function review($id)
    {
        $contract = $this->contractService->getContractById((int) $id);
        $this->view('contracts/review', ['contract_id' => (int) $id, 'contract' => $contract, 'title' => 'Review Contract']);
    }

    public function readonly($id)
    {
        $contract = $this->contractService->getEditorData((int) $id);
        $this->view('contracts/readonly', ['contract_id' => (int) $id, 'contract' => $contract, 'title' => 'Read Only Contract']);
    }

    public function viewFinal($id)
    {
        $this->readonly($id);
    }

    public function executionStatus($id)
    {
        $this->view('contracts/execution-status', ['contract_id' => (int) $id, 'title' => 'Execution Status']);
    }

    public function finalPDF($id)
    {
        $this->streamContractPdf((int) $id, false);
    }

    public function auditTrailView($id)
    {
        $this->view('contracts/audit-trail', ['contract_id' => (int) $id, 'title' => 'Audit Trail']);
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

    public function getDocumentContent($id)
    {
        $contract = $this->contractService->getEditorData((int) $id);
        $this->json(['success' => (bool) $contract, 'content' => $contract['content'] ?? ''], $contract ? 200 : 404);
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
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $signerId = $input['signer_id'] ?? $_POST['signer_id'] ?? 'staff@itec.com';
        $clientEmail = $input['client_email'] ?? $_POST['client_email'] ?? null;
        
        $stmt = $this->db->prepare("SELECT id, title, signing_state, client_email FROM contracts WHERE id = ?");
        $stmt->execute([$id]);
        $contract = $stmt->fetch();
        
        if (!$contract) {
            $this->json(['success' => false, 'error' => 'Contract not found'], 404);
            return;
        }

        if (!$clientEmail) {
            $clientEmail = $contract['client_email'] ?? null;
        }
        
        if ($contract['signing_state'] !== 'DRAFT') {
            $this->json([
                'success' => false, 
                'error' => 'Contract cannot be submitted. Current state: ' . $contract['signing_state']
            ], 400);
            return;
        }
        
        $stateMachine = new OscarStateMachineService($id);
        $result = $stateMachine->submitForSigning($signerId, $clientEmail);
        
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
        
        try {
            $stateMachine = new OscarStateMachineService((int) $id);
            $result = $this->runTargetTransition($stateMachine, $targetState, $signerId);
            $this->json($result);
        } catch (\Throwable $error) {
            $this->json(['success' => false, 'error' => $error->getMessage()], 400);
        }
    }

    public function clientSign($id)
    {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $signerId = $input['signer_id'] ?? $_POST['signer_id'] ?? 'client@itec.com';

        try {
            $stateMachine = new OscarStateMachineService((int) $id);
            $result = $stateMachine->clientSign($signerId, $input['doc_hash'] ?? null);

            $stateMachine = new OscarStateMachineService((int) $id);
            $stateMachine->escalateToCompany('system');
            $result['new_state'] = 'AWAITING_COMPANY';
            $result['message'] = 'Client signed successfully and contract escalated for company signature';

            $this->json($result);
        } catch (\Throwable $error) {
            $this->json(['success' => false, 'error' => $error->getMessage()], 400);
        }
    }

    public function escalateToCompany($id)
    {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $signerId = $input['signer_id'] ?? $_POST['signer_id'] ?? 'system';

        try {
            $this->json((new OscarStateMachineService((int) $id))->escalateToCompany($signerId));
        } catch (\Throwable $error) {
            $this->json(['success' => false, 'error' => $error->getMessage()], 400);
        }
    }

    public function companySign($id)
    {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $signerId = $input['signer_id'] ?? $_POST['signer_id'] ?? 'company@itec.com';

        try {
            $result = (new OscarStateMachineService((int) $id))->companySign($signerId, $input['doc_hash'] ?? null);
            $this->json($result);
        } catch (\Throwable $error) {
            $this->json(['success' => false, 'error' => $error->getMessage()], 400);
        }
    }
    
    public function editor($id)
    {
        $contract = $this->contractService->getEditorData((int) $id);

        if (!$contract) {
            $this->view('errors/404', [
                'title' => 'Contract Not Found',
                'message' => 'Contract with ID ' . $id . ' does not exist'
            ]);
            return;
        }

        $this->view('contracts/editor', [
            'contract_id' => (int) $id,
            'contract' => $contract,
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

    public function changesPanel($id)
    {
        $this->getChanges($id);
    }

    public function generatePrintPDF($id)
    {
        $this->streamContractPdf((int) $id, true);
    }

    public function apiIndex()
    {
        $contracts = $this->contractService->getAllContracts([
            'status' => $_GET['status'] ?? null,
            'search' => $_GET['search'] ?? null,
        ]);

        $this->json(['success' => true, 'contracts' => $contracts]);
    }

    public function apiShow($id)
    {
        $contract = $this->contractService->getContractById((int) $id);
        $this->json(['success' => (bool) $contract, 'contract' => $contract], $contract ? 200 : 404);
    }

    public function apiUpdate($id)
    {
        try {
            $contract = $this->contractService->updateContract((int) $id, $this->requestData());
            $this->json(['success' => true, 'contract' => $contract]);
        } catch (\Throwable $error) {
            $this->json(['success' => false, 'error' => $error->getMessage()], 500);
        }
    }

    public function apiDelete($id)
    {
        $this->json(['success' => $this->contractService->deleteContract((int) $id)]);
    }

    public function distribute($id)
    {
        $contract = $this->contractService->getContractById((int) $id);
        if (!$contract) {
            $this->json(['success' => false, 'error' => 'Contract not found'], 404);
            return;
        }

        if (strtoupper($contract['signing_state'] ?? '') !== 'FULLY_SIGNED') {
            $this->json(['success' => false, 'error' => 'Distribution is available only after FULLY_SIGNED'], 409);
            return;
        }

        $input = $this->requestData();
        $email = $input['recipient_email'] ?? $contract['client_email'] ?? 'client@itec.local';
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
        $token = hash('sha256', $id . $email . (getenv('APP_SECRET') ?: 'dev-secret') . $expiresAt);

        $stmt = $this->db->prepare("
            INSERT INTO doc_distributions (contract_id, recipient_email, token, expires_at)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([(int) $id, $email, $token, $expiresAt]);

        $this->json([
            'success' => true,
            'token' => $token,
            'portal_url' => BASE_URL . '/access/' . $token,
            'expires_at' => $expiresAt
        ]);
    }

    public function tokenAccess($token)
    {
        $stmt = $this->db->prepare("SELECT * FROM doc_distributions WHERE token = ? LIMIT 1");
        $stmt->execute([$token]);
        $distribution = $stmt->fetch();

        if (!$distribution) {
            $this->view('errors/404', ['message' => 'This secure link is invalid or expired.']);
            return;
        }

        if (strtotime($distribution['expires_at']) < time()) {
            $this->view('errors/419', ['message' => 'This signing link has expired.']);
            return;
        }

        $contract = $this->contractService->getEditorData((int) $distribution['contract_id']);
        if (!$contract) {
            $this->view('errors/404', ['message' => 'Contract not found for this secure link.']);
            return;
        }

        $this->db->prepare("UPDATE doc_distributions SET opened_at = COALESCE(opened_at, NOW()), status = 'delivered' WHERE id = ?")->execute([$distribution['id']]);

        $this->view('contracts/sign', [
            'contract' => $contract,
            'token' => $token,
            'title' => 'Sign Contract',
        ]);
    }

    public function getDistributions($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM doc_distributions WHERE contract_id = ? ORDER BY created_at DESC");
        $stmt->execute([(int) $id]);
        $this->json(['success' => true, 'distributions' => $stmt->fetchAll()]);
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
        $clientId = $this->ensureClientForContract($clientName, $clientEmail);
        $createdBy = $this->resolveCreatedBy($input['created_by'] ?? $_POST['created_by'] ?? null);

        // Insert into database first to get contract ID.
        $sql = "INSERT INTO contracts (client_id, client_name, client_email, title, document_type, description, signing_state, created_by) 
                VALUES (:client_id, :client_name, :client_email, :title, :document_type, :description, 'DRAFT', :created_by)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'client_id' => $clientId,
            'client_name' => $clientName,
            'client_email' => $clientEmail,
            'title' => $title,
            'document_type' => $input['document_type'] ?? $input['type'] ?? 'Service Agreement',
            'description' => $input['description'] ?? null,
            'created_by' => $createdBy
        ]);
        
        $contractId = $this->db->lastInsertId();
        
        $initialContent = $input['content'] ?? '<p></p>';
        $saveResult = $this->contractService->saveEditorContent($contractId, $initialContent, $createdBy);
        $filePath = $saveResult['file_path'];
        
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
        
    } catch (\PDOException $e) {
        $this->json([
            'success' => false, 
            'error' => 'Database error: ' . $e->getMessage()
        ], 500);
    } catch (\Throwable $e) {
        $this->json([
            'success' => false, 
            'error' => 'Error: ' . $e->getMessage()
        ], 500);
    }
}

    private function ensureClientForContract($clientName, $clientEmail)
    {
        $stmt = $this->db->prepare("SELECT id FROM clients WHERE email = ? LIMIT 1");
        $stmt->execute([$clientEmail]);
        $existing = $stmt->fetchColumn();
        if ($existing) {
            return (int) $existing;
        }

        $stmt = $this->db->prepare("
            INSERT INTO clients (name, email, company_name, status)
            VALUES (?, ?, ?, 'active')
        ");
        $stmt->execute([$clientName, $clientEmail, $clientName]);

        return (int) $this->db->lastInsertId();
    }

    private function resolveCreatedBy($createdBy)
    {
        $id = (int) $createdBy;
        if ($id > 0) {
            return $id;
        }

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $sessionId = (int) ($_SESSION['user_id'] ?? 0);
        if ($sessionId > 0) {
            return $sessionId;
        }

        $existing = (int) $this->db->query("SELECT id FROM users ORDER BY id ASC LIMIT 1")->fetchColumn();
        if ($existing > 0) {
            return $existing;
        }

        $stmt = $this->db->prepare("
            INSERT INTO users (name, email, password, role)
            VALUES ('Demo Staff', 'staff@itec.local', '', 'staff')
        ");
        $stmt->execute();

        return (int) $this->db->lastInsertId();
    }

    private function requestData()
    {
        $raw = file_get_contents('php://input');
        $json = json_decode($raw ?: '', true);

        return is_array($json) ? $json : $_POST;
    }

    private function streamContractPdf($id, $printReady)
    {
        $contract = $this->contractService->getEditorData($id);
        if (!$contract) {
            http_response_code(404);
            echo 'Contract not found';
            return;
        }

        $pdf = new \TCPDF();
        $pdf->SetCreator('ITEC Contract System');
        $pdf->SetTitle($contract['title']);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 11);
        $pdf->writeHTML('<h1>' . htmlspecialchars($contract['title']) . '</h1>' . ($contract['content'] ?? ''), true, false, true, false, '');

        if ($printReady) {
            $pdf->Ln(12);
            $pdf->Cell(0, 8, 'Client Signature: ____________________________    Date: _______________', 0, 1);
        }

        $pdf->Output('contract-' . $id . '.pdf', 'I');
    }

    private function runTargetTransition(OscarStateMachineService $stateMachine, $targetState, $signerId)
    {
        $map = [
            OscarStateMachineService::STATE_AWAITING_CLIENT => 'submitForSigning',
            OscarStateMachineService::STATE_CLIENT_SIGNED => 'clientSign',
            OscarStateMachineService::STATE_AWAITING_COMPANY => 'escalateToCompany',
            OscarStateMachineService::STATE_FULLY_SIGNED => 'companySign',
        ];

        if (!isset($map[$targetState])) {
            throw new \Exception('Unsupported target_state: ' . $targetState);
        }

        return $stateMachine->{$map[$targetState]}($signerId);
    }

}
