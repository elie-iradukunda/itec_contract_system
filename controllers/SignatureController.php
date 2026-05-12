<?php

namespace Controllers;

use Core\Controller;
use Models\Contract;
use Services\DocumentGeneratorService;
use Services\OscarSignatureService;
use Services\OscarStateMachineService;

class SignatureController extends Controller
{
    private $signatureService;
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->signatureService = new OscarSignatureService();
        $this->db = \Core\Database::getInstance()->getConnection();
    }

    // POST /api/contracts/{id}/sign
    public function sign($contractId)
{
    // Get role from POST body
    $input = json_decode(file_get_contents('php://input'), true);
    $role = $input['role'] ?? $_POST['role'] ?? null;
    $signerId = $input['signer_id'] ?? $_POST['signer_id'] ?? null;
    
    // Validate role
    if (!$role || !in_array($role, ['client', 'company_rep'])) {
        $this->json(['success' => false, 'error' => 'Invalid or missing role. Must be "client" or "company_rep"'], 400);
        return;
    }
    
    if (!$signerId) {
        $this->json(['success' => false, 'error' => 'Missing signer_id'], 400);
        return;
    }
    
    // Get contract details
    $stmt = $this->db->prepare("SELECT id, file_path, signing_state FROM contracts WHERE id = ?");
    $stmt->execute([$contractId]);
    $contract = $stmt->fetch();
    
    if (!$contract) {
        $this->json(['success' => false, 'error' => 'Contract not found'], 404);
        return;
    }
    
    $filePath = $contract['file_path'];
    if (!file_exists($filePath)) {
        $this->json(['success' => false, 'error' => 'Contract file not found'], 404);
        return;
    }
    
    // Check if contract is in correct state for signing
    $currentState = $contract['signing_state'];
    
    if ($role === 'client' && $currentState !== 'AWAITING_CLIENT') {
        $this->json(['success' => false, 'error' => "Client cannot sign. Current state: {$currentState}. Expected: AWAITING_CLIENT"], 400);
        return;
    }
    
    if ($role === 'company_rep' && $currentState !== 'AWAITING_COMPANY') {
        $this->json(['success' => false, 'error' => "Company cannot sign. Current state: {$currentState}. Expected: AWAITING_COMPANY"], 400);
        return;
    }
    
    // Sign the document (OpenSSL)
    $result = $this->signatureService->signDocument($contractId, $signerId, $role, $filePath);
    
    if (!$result['success']) {
        $this->json(['success' => false, 'error' => $result['error']], 500);
        return;
    }
    
    // Update contract state
    $stateMachine = new OscarStateMachineService($contractId);
    
    if ($role === 'client') {
        $stateMachine->clientSign($signerId, $result['doc_hash']);
        $stateMachine->escalateToCompany('system');
        $newState = 'AWAITING_COMPANY';
        $message = 'Document signed by client successfully';
    } else {
        $stateMachine->companySign($signerId, $result['doc_hash']);
        $newState = 'FULLY_SIGNED';
        $message = 'Document signed by company successfully';
    }
    
    $this->json([
        'success' => true,
        'signature_id' => $result['signature_id'],
        'doc_hash' => $result['doc_hash'],
        'new_state' => $newState,
        'message' => $message
    ]);
}

    // GET /api/contracts/{id}/verify
    public function verify($contractId)
    {
        $result = $this->signatureService->verifyDocument($contractId);
        
        // Get signer chain
        $signerChain = $this->signatureService->getSignerChain($contractId);
        
        $this->json([
            'contract_id' => $contractId,
            'verification' => $result,
            'signer_chain' => $signerChain,
            'total_signatures' => count($signerChain)
        ]);
    }

    // GET /api/contracts/{id}/signatures
    public function getSignatures($contractId)
    {
        $signatures = $this->signatureService->getAllSignatures($contractId);
        
        $this->json([
            'contract_id' => $contractId,
            'signatures' => $signatures,
            'count' => count($signatures)
        ]);
    }

    public function getSignerChain($contractId)
    {
        $this->json([
            'contract_id' => $contractId,
            'signer_chain' => $this->signatureService->getSignerChain($contractId),
        ]);
    }

    public function applySeal($contractId)
    {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $approver = trim((string) ($input['signer_id'] ?? $input['approver_name'] ?? $_POST['signer_id'] ?? $_POST['approver_name'] ?? 'company@itec.com'));
        if ($approver === '') {
            $approver = 'company@itec.com';
        }

        try {
            $stmt = $this->db->prepare("SELECT signing_state FROM contracts WHERE id = ?");
            $stmt->execute([(int) $contractId]);
            $state = strtoupper((string) $stmt->fetchColumn());

            if ($state === 'AWAITING_COMPANY') {
                $contractStmt = $this->db->prepare("SELECT file_path FROM contracts WHERE id = ?");
                $contractStmt->execute([(int) $contractId]);
                $contract = $contractStmt->fetch();
                $filePath = $this->resolvePath($contract['file_path'] ?? '');
                $signature = $this->signatureService->signDocument((int) $contractId, $approver, 'company_rep', $filePath);
                if (empty($signature['success'])) {
                    throw new \Exception($signature['error'] ?? 'Company signature failed');
                }

                $stateResult = (new OscarStateMachineService((int) $contractId))->companySign($approver, $signature['doc_hash']);
                $stateResult['signature_id'] = $signature['signature_id'];
                $stateResult['doc_hash'] = $signature['doc_hash'];
            } elseif ($state !== 'FULLY_SIGNED') {
                $this->json(['success' => false, 'error' => 'Company seal is available only after client signing'], 409);
                return;
            }

            $result = (new \Services\OscarSealService())->applySeal((int) $contractId, $approver);
            if (isset($stateResult)) {
                $result = array_merge($stateResult, $result);
            }

            $this->json($result, !empty($result['success']) ? 200 : 500);
        } catch (\Throwable $error) {
            $this->json(['success' => false, 'error' => $error->getMessage()], 500);
        }
    }

    public function getSealInfo($contractId)
    {
        $stmt = $this->db->prepare("SELECT id, file_path, signing_state FROM contracts WHERE id = ?");
        $stmt->execute([(int) $contractId]);

        $this->json([
            'success' => true,
            'contract' => $stmt->fetch(),
            'seal_path' => 'storage/seals/company_seal.png'
        ]);
    }

    public function showChoice($contractId)
    {
        $this->view('contracts/sign-digitally', ['contract_id' => (int) $contractId, 'title' => 'Signing Choice']);
    }

    public function handleChoice($contractId)
    {
        $choice = $_POST['choice'] ?? $_POST['signing_choice'] ?? 'digital';
        $this->json([
            'success' => true,
            'contract_id' => (int) $contractId,
            'choice' => $choice,
            'message' => $choice === 'hard_copy' ? 'Hard copy path selected' : 'Digital signing selected'
        ]);
    }

    public function signPage($contractId = null)
    {
       

        $contractId = (int) ($contractId ?: 1);
        $stmt = $this->db->prepare("SELECT id, title, client_name, client_email, signing_state FROM contracts WHERE id = ?");
        $stmt->execute([$contractId]);
        $contract = $stmt->fetch();

        if (!$contract) {
            $this->view('errors/404', ['message' => 'Contract not found']);
            return;
        }

        $this->view('contracts/sign-digitally', [
            'contract_id' => $contractId,
            'contract' => $contract,
            'already_signed' => in_array($contract['signing_state'], ['CLIENT_SIGNED', 'AWAITING_COMPANY', 'FULLY_SIGNED'], true),
            'signing_email' => $_SESSION['signing_email'] ?? '',
            'title' => 'Digital Signing'
        ]);
    }

    public function digitalSignPage($contractId = null)
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $contractId = (int) ($contractId ?: 1);
        if (
            !isset($_SESSION['signing_authorized']) ||
            $_SESSION['signing_authorized'] !== true ||
            (int) ($_SESSION['signing_contract_id'] ?? 0) !== $contractId
        ) {
            header('Location: ' . BASE_URL . '/');
            exit;
        }

        $this->signPage($contractId);
    }

    public function previewSignaturePdf($contractId)
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $contractId = (int) $contractId;
        if (
            !isset($_SESSION['signing_authorized']) ||
            $_SESSION['signing_authorized'] !== true ||
            (int) ($_SESSION['signing_contract_id'] ?? 0) !== $contractId
        ) {
            http_response_code(403);
            echo 'This signing session is not authorized. Please use the secure email link.';
            return;
        }

        $contract = (new Contract())->getEditorData($contractId);
        if (!$contract) {
            http_response_code(404);
            echo 'Contract not found';
            return;
        }

        $typedName = trim((string) ($_POST['typed_signature'] ?? ''));
        $signerEmail = trim((string) ($_POST['signer_id'] ?? ''));

        if ($typedName !== '') {
            $contract['client_name'] = $typedName;
        }

        if ($signerEmail !== '') {
            $contract['client_email'] = $signerEmail;
        }

        $tempSignaturePath = $this->savePreviewSignatureImage($_POST['signature_data'] ?? '');
        $signatures = $this->getSignaturesForPreview($contractId);

        if ($tempSignaturePath) {
            $signatures = array_values(array_filter($signatures, function ($signature) {
                return ($signature['signer_role'] ?? '') !== 'client';
            }));
            $signatures[] = [
                'id' => 0,
                'signer_id' => $signerEmail ?: ($contract['client_email'] ?? 'client'),
                'signer_role' => 'client',
                'signature_file_path' => $tempSignaturePath,
                'signed_at' => date('Y-m-d H:i:s'),
            ];
        }

        $pdfPath = null;
        try {
            $pdfPath = (new DocumentGeneratorService())->generateContractPdf($contractId, $contract, $signatures);

            if (!$pdfPath || !is_file($pdfPath)) {
                http_response_code(500);
                echo 'Failed to generate preview PDF';
                return;
            }

            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="contract_' . $contractId . '_review.pdf"');
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($pdfPath));
            readfile($pdfPath);
        } finally {
            if ($pdfPath && is_file($pdfPath)) {
                @unlink($pdfPath);
            }
            if ($tempSignaturePath && is_file($tempSignaturePath)) {
                @unlink($tempSignaturePath);
            }
        }
    }

    public function sealPage()
    {
        $this->view('contracts/readonly', ['contract_id' => 1, 'title' => 'Company Seal']);
    }

    public function verifyPage()
    {
        $this->view('contracts/audit-trail', ['contract_id' => 1, 'title' => 'Verify Signatures']);
    }

    private function resolvePath($path)
    {
        $path = str_replace('\\', '/', (string) $path);
        if ($path === '') {
            return '';
        }

        return preg_match('/^[A-Za-z]:\//', $path) || str_starts_with($path, '/')
            ? $path
            : dirname(__DIR__) . '/' . ltrim($path, '/');
    }

    private function rememberClientSigningDetails($contractId, $typedName, $email)
    {
        $name = trim((string) $typedName);
        if ($name === '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $name = ucwords(str_replace(['.', '_', '-'], ' ', strstr($email, '@', true) ?: $email));
        }

        $stmt = $this->db->prepare("
            UPDATE contracts
            SET client_name = COALESCE(NULLIF(?, ''), client_name),
                client_email = COALESCE(NULLIF(?, ''), client_email),
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$name, $email, (int) $contractId]);
    }

    private function getSignaturesForPreview($contractId)
    {
        $stmt = $this->db->prepare("
            SELECT id, signer_id, signer_role, signature_file_path, signed_at
            FROM doc_signatures
            WHERE contract_id = ?
            ORDER BY signed_at ASC
        ");
        $stmt->execute([(int) $contractId]);

        return $stmt->fetchAll() ?: [];
    }

    private function savePreviewSignatureImage($dataUrl)
    {
        if (!is_string($dataUrl) || $dataUrl === '') {
            return null;
        }

        if (!preg_match('/^data:image\/(jpe?g|png);base64,/i', $dataUrl, $matches)) {
            return null;
        }

        [, $encoded] = explode('base64,', $dataUrl, 2);
        $decoded = base64_decode($encoded, true);
        if ($decoded === false) {
            return null;
        }

        $tempDir = dirname(__DIR__) . '/storage/temp/';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $extension = strtolower($matches[1]) === 'png' ? 'png' : 'jpg';
        $path = $tempDir . 'preview_signature_' . uniqid() . '.' . $extension;

        return file_put_contents($path, $decoded) === false ? null : $path;
    }

    // POST /api/contracts/{id}/submit
   
}
