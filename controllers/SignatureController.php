<?php

namespace Controllers;

use Core\Controller;
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
        
        // Get contract file path
        $stmt = $this->db->prepare("SELECT id, file_path, signing_state FROM contracts WHERE id = ?");
        $stmt->execute([$contractId]);
        $contract = $stmt->fetch();
        $file_path = $contract['file_path'] ?? null;
        
        if (!$contract) {
            $this->json(['success' => false, 'error' => 'Contract not found'], 404);
            return;
        }
        
        $filePath = $this->resolvePath($file_path);
        if (!$filePath || !file_exists($filePath)) {
            $this->json(['success' => false, 'error' => 'Contract file not found', 'path' => $file_path], 404);
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
        
        // Sign the document
        $result = $this->signatureService->signDocument($contractId, $signerId, $role, $filePath);
        
        if ($result['success']) {
            // If a visual signature (base64 image) was sent, save it linked to the signature record
            $visualPath = null;
            $signatureData = $_POST['signature_data'] ?? null;
            if ($signatureData) {
                try {
                    $visualPath = $this->signatureService->saveVisualSignature((int)$contractId, $signerId, $signatureData, $result['signature_id']);
                } catch (\Throwable $e) {
                    // Non-fatal: log and continue (signature still valid cryptographically)
                    error_log('Failed to save visual signature: ' . $e->getMessage());
                }
            }

            // Update contract state after successful signature
            $stateMachine = new OscarStateMachineService($contractId);
            
            if ($role === 'client') {
                $stateResult = $stateMachine->clientSign($signerId, $result['doc_hash']);
                $stateMachine = new OscarStateMachineService($contractId);
                $stateMachine->escalateToCompany('system');
                $stateResult['new_state'] = 'AWAITING_COMPANY';
            } else {
                $stateResult = $stateMachine->companySign($signerId, $result['doc_hash']);
            }
            
            $response = [
                'success' => true,
                'signature_id' => $result['signature_id'],
                'doc_hash' => $result['doc_hash'],
                'new_state' => $stateResult['new_state'] ?? null,
                'message' => $role === 'client' ? 'Document signed by client successfully' : 'Document signed by company successfully'
            ];
            if (!empty($visualPath)) {
                $response['visual_signature'] = $visualPath;
            }

            $this->json($response);
        } else {
            $this->json(['success' => false, 'error' => $result['error']], 500);
        }
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
        $approver = $input['signer_id'] ?? $_POST['signer_id'] ?? $_POST['approver_name'] ?? 'company@itec.com';

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
        $this->view('contracts/sign-digitally', ['contract_id' => (int) ($contractId ?: 1), 'title' => 'Digital Signing']);
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

    // POST /api/contracts/{id}/submit
   
}
