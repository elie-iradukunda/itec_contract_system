<?php

namespace Controllers;

use Core\Controller;
use Services\OscarSignatureService;

class SignatureController extends Controller
{
    private $signatureService;

    public function __construct()
    {
        parent::__construct();
        $this->signatureService = new OscarSignatureService();
    }

    // POST /api/contracts/{id}/sign
    public function sign($contractId)
    {
        // Get signer info from session or request
        session_start();
        $signerId = $_SESSION['user_email'] ?? $_POST['signer_id'] ?? 'unknown@user.com';
        
        // Get contract file path
        $db = \Core\Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT file_path FROM contracts WHERE id = ?");
        $stmt->execute([$contractId]);
        $contract = $stmt->fetch();
        
        if (!$contract || !file_exists($contract['file_path'])) {
            $this->json(['success' => false, 'error' => 'Contract file not found'], 404);
            return;
        }
        
        $result = $this->signatureService->signDocument($contractId, $signerId, $contract['file_path']);
        
        if ($result['success']) {
            $this->json([
                'success' => true,
                'signature_id' => $result['signature_id'],
                'doc_hash' => $result['doc_hash'],
                'message' => 'Document signed successfully'
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
}