<?php

namespace Controllers;

use Core\Controller;
use Core\Database;
use Services\OscarStateMachineService;

class UploadController extends Controller
{
    public function uploadPage($contractId)
    {
        $this->uploadHardCopyPage($contractId);
    }

    public function uploadHardCopyPage($contractId)
    {
        $this->view('contracts/upload-signed-copy', ['contract_id' => (int) $contractId, 'title' => 'Upload Signed Copy']);
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

            $folder = dirname(__DIR__) . '/storage/contracts/' . (int) $contractId;
            if (!is_dir($folder)) {
                mkdir($folder, 0777, true);
            }

            $path = $folder . '/signed-copy.' . $extension;
            if (!move_uploaded_file($file['tmp_name'], $path)) {
                throw new \Exception('The signed scan could not be saved.');
            }

            // Feature E4: hard-copy upload fulfils the client obligation and moves to company action.
            $stateMachine = new OscarStateMachineService((int) $contractId);
            $state = $stateMachine->getCurrentState()['state'] ?? 'DRAFT';
            if ($state === 'AWAITING_CLIENT') {
                $stateMachine->clientSign('hard-copy-client', hash_file('sha256', $path));
                $stateMachine = new OscarStateMachineService((int) $contractId);
                $stateMachine->escalateToCompany('system');
            } elseif ($state === 'CLIENT_SIGNED') {
                $stateMachine->escalateToCompany('system');
            } elseif (!in_array($state, ['AWAITING_COMPANY', 'FULLY_SIGNED'], true)) {
                throw new \Exception('Submit the contract for client signing before uploading a hard copy.');
            }

            $this->logUpload((int) $contractId, $path);
            $this->respond(['success' => true, 'message' => 'Signed scan uploaded successfully.', 'file_path' => $path], 200, (int) $contractId);
        } catch (\Throwable $error) {
            $this->respond(['success' => false, 'message' => $error->getMessage()], 500, (int) $contractId);
        }
    }

    private function logUpload($contractId, $path)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO doc_signature_audit (contract_id, signer_id, event_type, doc_hash, ip_address, user_agent, timestamp)
            VALUES (?, ?, 'signature_created', ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $contractId,
            'staff',
            file_exists($path) ? hash_file('sha256', $path) : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }

    private function respond(array $payload, $status = 200, $contractId = 0)
    {
        // Browser forms redirect back to the editor; AJAX/API callers receive JSON.
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        if (stripos($accept, 'application/json') !== false || str_starts_with($_SERVER['REQUEST_URI'] ?? '', BASE_URL . '/api/')) {
            $this->json($payload, $status);
        }

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $isClientSigningSession = isset($_SESSION['signing_authorized'])
            && $_SESSION['signing_authorized'] === true
            && (int) ($_SESSION['signing_contract_id'] ?? 0) === (int) $contractId;

        $target = $isClientSigningSession
            ? BASE_URL . '/sign/' . (int) $contractId . '?signed=hard_copy'
            : ($contractId ? BASE_URL . '/contracts/' . (int) $contractId . '/editor#signing' : BASE_URL . '/contracts');
        header('Location: ' . $target);
        exit;
    }
}
