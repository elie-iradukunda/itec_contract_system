<?php

namespace Controllers;

use Core\Controller;
use Core\Database;
use Core\Mail;
use Services\DocumentGeneratorService;
use Services\OscarStateMachineService;
use ZipArchive;
use Exception;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\PhpWord;
use setasign\Fpdi\Tcpdf\Fpdi;


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
        $signatures = $this->getSignaturesForContract((int) $id);
        $this->view('contracts/readonly', ['contract_id' => (int) $id, 'contract' => $contract, 'signatures' => $signatures, 'title' => 'Read Only Contract']);
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

// Token validation endpoint (one-time use)
public function tokenAccess($token)
{
    // Validate token
    $stmt = $this->db->prepare("
        SELECT contract_id, recipient_email, expires_at, status 
        FROM doc_distributions 
        WHERE token = ?
    ");
    $stmt->execute([$token]);
    $distribution = $stmt->fetch();
    
    if (!$distribution) {
        $this->view('errors/404', ['message' => 'Invalid signing link']);
        return;
    }
    
    if (strtotime($distribution['expires_at']) < time()) {
        $this->view('errors/419', ['message' => 'This signing link has expired (30 days limit)']);
        return;
    }
    
    session_start();
    $_SESSION['signing_contract_id'] = $distribution['contract_id'];
    $_SESSION['signing_authorized'] = true;
    $_SESSION['signing_email'] = $distribution['recipient_email'];
    
    // Update distribution as opened
    if ($distribution['status'] === 'pending') {
        $updateStmt = $this->db->prepare("
            UPDATE doc_distributions SET opened_at = NOW(), status = 'delivered' WHERE token = ?
        ");
        $updateStmt->execute([$token]);
    }
    
    // Redirect to clean signing page (no token in URL)
    header('Location: /itec_contract_system/sign/' . $distribution['contract_id']);
    exit;
}

public function signPage($contractId)
{
    session_start();
    
    // Check if user is authorized to sign this contract
    if (!isset($_SESSION['signing_authorized']) || $_SESSION['signing_authorized'] !== true) {
        header('Location: /itec_contract_system/');
        exit;
    }
    
    if (!isset($_SESSION['signing_contract_id']) || $_SESSION['signing_contract_id'] != $contractId) {
        header('Location: /itec_contract_system/');
        exit;
    }
    
    // Get contract details
    $stmt = $this->db->prepare("SELECT * FROM contracts WHERE id = ?");
    $stmt->execute([$contractId]);
    $contract = $stmt->fetch();
    
    if (!$contract) {
        $this->view('errors/404', ['message' => 'Contract not found']);
        return;
    }
    
    // Check if already signed
    $alreadySigned = in_array($contract['signing_state'], ['CLIENT_SIGNED', 'AWAITING_COMPANY', 'FULLY_SIGNED']);
    
    $this->view('contracts/sign', [
        'contract' => $contract,
        'signing_email' => $_SESSION['signing_email'] ?? $contract['client_email'] ?? '',
        'signed_mode' => $_GET['signed'] ?? null,
        'title' => 'Sign Contract',
        'already_signed' => $alreadySigned
    ]);
}

// After signing, clear session
public function completeSigning($contractId)
{
    // After successful signature, clear session
    session_start();
    $_SESSION['signing_authorized'] = false;
    $_SESSION['signing_contract_id'] = null;
    
    $this->json(['success' => true, 'message' => 'Contract signed successfully']);
}

    public function submitForSigning($id)
    {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $signerId = $input['signer_id'] ?? $_POST['signer_id'] ?? 'staff@itec.com';
        $recipientEmails = $this->parseRecipientEmails($input['client_emails'] ?? $input['client_email'] ?? $_POST['client_emails'] ?? $_POST['client_email'] ?? null);
        
        $stmt = $this->db->prepare("SELECT id, title, signing_state, client_email, client_name FROM contracts WHERE id = ?");
        $stmt->execute([$id]);
        $contract = $stmt->fetch();
        
        if (!$contract) {
            $this->json(['success' => false, 'error' => 'Contract not found'], 404);
            return;
        }

        if (!$recipientEmails && !empty($contract['client_email'])) {
            $recipientEmails = $this->parseRecipientEmails($contract['client_email']);
        }
        
        if ($contract['signing_state'] !== 'DRAFT') {
            $this->json([
                'success' => false, 
                'error' => 'Contract cannot be submitted. Current state: ' . $contract['signing_state']
            ], 400);
            return;
        }

        if (!$recipientEmails) {
            $this->json([
                'success' => false,
                'error' => 'Add at least one client email before sending the contract.'
            ], 400);
            return;
        }

        $primaryEmail = $recipientEmails[0];
        $this->rememberPrimaryRecipient((int) $id, $primaryEmail);
        
        $stateMachine = new OscarStateMachineService($id);
        $result = $stateMachine->submitForSigning($signerId, $primaryEmail);
        $result['recipients'] = [$primaryEmail];
        $result['links'] = [];

        foreach (array_slice($recipientEmails, 1) as $email) {
            $distribution = $this->createSigningDistribution((int) $id, $email);
            $this->sendSigningInvitation($contract, $email, $distribution['sign_url']);
            $result['recipients'][] = $email;
            $result['links'][] = $distribution;
        }
        
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

        // Fetch signatures for this contract
        $signatures = $this->getSignaturesForContract((int) $id);

        $this->view('contracts/editor', [
            'contract_id' => (int) $id,
            'contract' => $contract,
            'signatures' => $signatures,
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

    public function getDistributions($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM doc_distributions WHERE contract_id = ? ORDER BY created_at DESC");
        $stmt->execute([(int) $id]);
        $this->json(['success' => true, 'distributions' => $stmt->fetchAll()]);
    }

public function apiStore()
{
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    
    // Validate required fields
    $title = $input['title'] ?? $_POST['title'] ?? null;
    
    if (!$title) {
        $this->json([
            'success' => false, 
            'error' => 'Missing required field: title'
        ], 400);
        return;
    }
    
    try {
        $createdBy = $this->resolveCreatedBy($input['created_by'] ?? $_POST['created_by'] ?? null);
        $documentType = $input['document_type'] ?? $input['type'] ?? 'Service Agreement';
        $clientName = trim($input['client_name'] ?? $_POST['client_name'] ?? '') ?: null;
        $clientEmail = trim($input['client_email'] ?? $_POST['client_email'] ?? '') ?: null;

        // Insert into database first to get contract ID.
        $sql = "INSERT INTO contracts (client_id, client_name, client_email, title, document_type, description, signing_state, created_by) 
                VALUES (:client_id, :client_name, :client_email, :title, :document_type, :description, 'DRAFT', :created_by)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'client_id' => 1,
            'client_name' => $clientName,
            'client_email' => $clientEmail,
            'title' => $title,
            'document_type' => $documentType,
            'description' => $input['description'] ?? null,
            'created_by' => $createdBy
        ]);
        
        $contractId = $this->db->lastInsertId();
        $saveResult = $this->contractService->saveEditorContent((int) $contractId, $input['content'] ?? '<p></p>', $createdBy);
        $filePath = $saveResult['file_path'] ?? null;
        
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
                'file_path' => $this->relativeProjectPath($filePath),
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

    private function parseRecipientEmails($value)
    {
        if (is_array($value)) {
            $items = $value;
        } else {
            $items = preg_split('/[\s,;]+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY);
        }

        $emails = [];
        foreach ($items as $item) {
            $email = trim((string) $item);
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $emails[strtolower($email)] = $email;
            }
        }

        return array_values($emails);
    }

    private function rememberPrimaryRecipient($contractId, $email)
    {
        $stmt = $this->db->prepare("
            UPDATE contracts
            SET client_email = COALESCE(NULLIF(client_email, ''), ?),
                client_name = COALESCE(NULLIF(client_name, ''), ?),
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$email, $this->displayNameFromEmail($email), $contractId]);
    }

    private function createSigningDistribution($contractId, $email)
    {
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
        $secret = getenv('APP_SECRET') ?: 'itec_contract_secret_key';
        $token = hash('sha256', $contractId . $email . $secret . $expiresAt . bin2hex(random_bytes(12)));

        $stmt = $this->db->prepare("
            INSERT INTO doc_distributions (contract_id, recipient_email, token, expires_at, status, created_at)
            VALUES (?, ?, ?, ?, 'pending', NOW())
        ");
        $stmt->execute([$contractId, $email, $token, $expiresAt]);

        return [
            'email' => $email,
            'token' => $token,
            'expires_at' => $expiresAt,
            'sign_url' => $this->absoluteBaseUrl() . '/access/' . $token,
        ];
    }

    private function sendSigningInvitation(array $contract, $email, $signUrl)
    {
        $title = htmlspecialchars($contract['title'] ?? 'Contract', ENT_QUOTES, 'UTF-8');
        $safeUrl = htmlspecialchars($signUrl, ENT_QUOTES, 'UTF-8');
        $body = "<html><body>
            <h2>Contract Ready for Signature</h2>
            <p>Contract <strong>{$title}</strong> is ready for your review and signature.</p>
            <p><a href='{$safeUrl}'>Open and sign contract</a></p>
            <p>You can choose digital signing or hard-copy signing on the secure page.</p>
            </body></html>";

        (new Mail())->send($email, "Verify and Sign Contract - {$contract['title']}", $body);
    }

    private function displayNameFromEmail($email)
    {
        $local = strstr($email, '@', true) ?: $email;
        return ucwords(str_replace(['.', '_', '-'], ' ', $local));
    }

    private function absoluteBaseUrl()
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host . BASE_URL;
    }

    private function relativeProjectPath($path)
    {
        $root = str_replace('\\', '/', dirname(__DIR__)) . '/';
        $normalized = str_replace('\\', '/', realpath((string) $path) ?: (string) $path);
        return str_starts_with($normalized, $root) ? substr($normalized, strlen($root)) : $normalized;
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
        $this->outputGeneratedContractPdf((int) $id, 'inline', 'contract-' . (int) $id . '.pdf');
    }

    private function getSignaturesForContract($contractId)
    {
        $stmt = $this->db->prepare("
            SELECT id, signer_id, signer_role, signature_file_path, signed_at 
            FROM doc_signatures 
            WHERE contract_id = ? 
            ORDER BY signed_at ASC
        ");
        $stmt->execute([$contractId]);
        return $stmt->fetchAll() ?: [];
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

private function convertToPdf($inputPath)
{
    try {
        $extension = strtolower(pathinfo($inputPath, PATHINFO_EXTENSION));

        if ($extension === 'pdf') {
            return $inputPath;
        }

        if (!class_exists('\ZipArchive')) {
            throw new Exception('Zip extension is not enabled.');
        }

        if (!file_exists($inputPath)) {
            throw new Exception('File not found.');
        }

        if (filesize($inputPath) <= 0) {
            throw new Exception('File is empty.');
        }

        // Validate DOCX structure
        $zip = new ZipArchive();
        $openResult = $zip->open($inputPath);
        if ($openResult !== true) {
            throw new Exception('Invalid DOCX. Zip open failed with code: ' . $openResult);
        }
        if ($zip->locateName('_rels/.rels') === false) {
            $zip->close();
            throw new Exception('Invalid DOCX structure.');
        }
        $zip->close();

        // Configure PDF Settings
        \PhpOffice\PhpWord\Settings::setPdfRendererName(\PhpOffice\PhpWord\Settings::PDF_RENDERER_DOMPDF);
        \PhpOffice\PhpWord\Settings::setPdfRendererPath(realpath(__DIR__ . '/../vendor/dompdf/dompdf'));

        // Load the Word Document
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($inputPath);

        $tempPdfPath = dirname($inputPath) . 
                       DIRECTORY_SEPARATOR . 
                       'tmp_' . 
                       uniqid() . 
                       '.pdf';

        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'PDF');

        /** 
         * Logic Fix: Access the underlying Dompdf instance to enable 
         * image loading and remote assets which are often required for logos.
         */
        if (method_exists($writer, 'getDompdf')) {
            $dompdf = $writer->getDompdf();
            $options = $dompdf->getOptions();
            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);
            $dompdf->setOptions($options);
        }

        $writer->save($tempPdfPath);

        if (!file_exists($tempPdfPath)) {
            throw new Exception('PDF conversion failed.');
        }

        return $tempPdfPath;

    } catch (\Throwable $e) {
        // Log error instead of just echoing in a private method context
        error_log('Conversion Error: ' . $e->getMessage());
        return false;
    }
}
    

    public function generatePrintPDF($id)
{
    $this->outputGeneratedContractPdf((int) $id, 'inline', 'contract_' . (int) $id . '.pdf');
}

private function outputGeneratedContractPdf($id, $disposition = 'inline', $filename = null)
{
    $contract = $this->contractService->getEditorData((int) $id);
    if (!$contract) {
        http_response_code(404);
        echo "Contract not found";
        return;
    }

    $signatures = $this->getSignaturesForContract((int) $id);
    $result = (new DocumentGeneratorService())->generateContractPdf((int) $id, $contract, $signatures);

    if (!$result || !is_file($result)) {
        http_response_code(500);
        echo "Failed to generate PDF";
        return;
    }

    $filename = $filename ?: 'contract_' . (int) $id . '.pdf';

    header('Content-Type: application/pdf');
    header('Content-Disposition: ' . $disposition . '; filename="' . basename($filename) . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($result));

    readfile($result);
    @unlink($result);
}

private function resolveContractPath($path)
{
    $path = str_replace('\\', '/', (string) $path);
    if ($path === '') {
        return '';
    }

    return preg_match('/^[A-Za-z]:\//', $path) || str_starts_with($path, '/')
        ? $path
        : dirname(__DIR__) . '/' . ltrim($path, '/');
}

}
