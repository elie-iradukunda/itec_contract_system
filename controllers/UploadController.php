<?php

namespace Controllers;

use Core\Controller;
use Core\Database;
use Services\OscarStateMachineService;

class UploadController extends Controller
{
    public function uploadHardCopyPage($contractId)
    {
        // Get contract details to display on upload page
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id, title, client_name FROM contracts WHERE id = ?");
        $stmt->execute([$contractId]);
        $contract = $stmt->fetch();

        if (!$contract) {
            $this->view('errors/404', ['message' => 'Contract not found']);
            return;
        }

        $this->view('contracts/upload-signed-copy', [
            'contract_id' => (int) $contractId, 
            'contract' => $contract,
            'title' => 'Upload Signed Copy'
        ]);
    }

    public function uploadSignedCopy($contractId)
    {
        $this->handleUpload($contractId);
    }

    public function uploadHardCopy($contractId)
    {
        $this->handleUpload($contractId);
    }

   private function handleUpload($contractId)
{
    try {
        $file = $_FILES['signed_copy'] ?? $_FILES['hard_copy'] ?? $_FILES['file'] ?? null;
        
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception('Choose a signed scan to upload.');
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ['pdf', 'png', 'jpg', 'jpeg'], true)) {
            throw new \Exception('Only PDF, PNG, JPG, or JPEG files are allowed.');
        }

        if ($file['size'] > 10 * 1024 * 1024) {
            throw new \Exception('File too large. Maximum size is 10MB.');
        }

        $folder = dirname(__DIR__) . '/storage/contracts/signed_copies/';
        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }

        $timestamp = time();
        $path = $folder . '/signed-copy_' . $timestamp . '.' . $extension;
        
        if (!move_uploaded_file($file['tmp_name'], $path)) {
            throw new \Exception('The signed scan could not be saved.');
        }

        $stateMachine = new OscarStateMachineService((int) $contractId);
        $stateInfo = $stateMachine->getCurrentState();
        $currentState = $stateInfo['state'] ?? 'DRAFT';

        if ($currentState === 'AWAITING_CLIENT') {
            // Hard copy signature - client obligation fulfilled
            // Signer ID indicates this is a hard copy signature
            $signerId = 'hard-copy-client-' . $timestamp;
            
            // Update state to CLIENT_SIGNED (no cryptographic signature)
            $signResult = $stateMachine->clientSign($signerId, hash_file('sha256', $path));
            
            if ($signResult['success']) {
                // Automatically escalate to company
                $stateMachine->escalateToCompany('system');
            }
        } else {
            throw new \Exception('Contract is not in a state that accepts hard copy signing. Current state: ' . $currentState);
        }

        $this->logHardCopyUpload($contractId, $path);

        $responseData = [
            'success' => true, 
            'message' => 'Signed scan uploaded successfully. The contract has been signed.', 
            'file_path' => $path
        ];

        $this->respond($responseData, 200, $contractId);
        
    } catch (\Throwable $error) {
        $this->respond(['success' => false, 'message' => $error->getMessage()], 500, $contractId);
    }
}

   private function logHardCopyUpload($contractId, $path)
{
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        INSERT INTO doc_signature_audit (contract_id, signer_id, event_type, doc_hash, uploaded_file_path, ip_address, user_agent, timestamp)
        VALUES (?, 'hard-copy-client', 'hard_copy_uploaded', ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $contractId,
        hash_file('sha256', $path),
        $path,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null,
    ]);
}

   

    private function respond(array $payload, $status = 200, $contractId = 0)
    {
        // Check if request expects JSON
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        if (stripos($accept, 'application/json') !== false || $isAjax) {
            $this->json($payload, $status);
            return;
        }

        // Browser form redirect
        $target = $contractId ? '/itec_contract_system/contracts/' . (int) $contractId . '/editor#signing' : '/itec_contract_system/contracts';
        
        if ($payload['success']) {
            $_SESSION['upload_success'] = $payload['message'];
        } else {
            $_SESSION['upload_error'] = $payload['message'];
        }
        
        header('Location: ' . $target);
        exit;
    }
}