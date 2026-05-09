<?php

namespace Controllers;

use Core\Controller;
use Services\OscarAuditService;
use Services\OscarSignatureService;

class AuditController extends Controller
{
    private $auditService;
    private $signatureService;
    
    public function __construct()
    {
        parent::__construct();
        $this->auditService = new OscarAuditService();
        $this->signatureService = new OscarSignatureService();
    }
    
    public function index($contractId)
    {
        $audit = $this->auditService->getAuditTrail($contractId);
        $this->json([
            'contract_id' => $contractId,
            'audit_trail' => $audit,
            'total_events' => count($audit)
        ]);
    }
    
    public function verify($contractId)
    {
        $verification = $this->signatureService->verifyDocument($contractId);
        $audit = $this->auditService->getAuditTrail($contractId);
        
        $this->json([
            'contract_id' => $contractId,
            'verification' => $verification,
            'audit_trail' => $audit
        ]);
    }
    
    public function reviewerPanel($contractId)
    {
        $audit = $this->auditService->getFullChain($contractId);
        $verification = $this->signatureService->verifyDocument($contractId);
        
        $this->view('audit/reviewer', [
            'contract_id' => $contractId,
            'audit_chain' => $audit,
            'verification' => $verification,
            'title' => 'Reviewer Panel - Audit Trail'
        ]);
    }
}