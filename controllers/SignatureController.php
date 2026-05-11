<?php

namespace Controllers;

use Core\Controller;
use Core\Response;
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
        
        if (!$file_path || !file_exists($file_path)) {
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
        $result = $this->signatureService->signDocument($contractId, $signerId, $role, $file_path);
        
        if ($result['success']) {
            // Update contract state after successful signature
            $stateMachine = new OscarStateMachineService($contractId);
            
            if ($role === 'client') {
                $stateResult = $stateMachine->clientSign($signerId, $result['doc_hash']);
            } else {
                $stateResult = $stateMachine->companySign($signerId, $result['doc_hash']);
            }
            
            $this->json([
                'success' => true,
                'signature_id' => $result['signature_id'],
                'doc_hash' => $result['doc_hash'],
                'new_state' => $stateResult['new_state'] ?? null,
                'message' => $role === 'client' ? 'Document signed by client successfully' : 'Document signed by company successfully'
            ]);
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

    // POST /api/contracts/{id}/submit
   
}