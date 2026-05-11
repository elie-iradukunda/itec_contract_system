<?php

namespace Services;

use Core\Database;
use Core\Mail;
use Exception;

class OscarStateMachineService
{
    // State constants
    const STATE_DRAFT = 'DRAFT';
    const STATE_AWAITING_CLIENT = 'AWAITING_CLIENT';
    const STATE_CLIENT_SIGNED = 'CLIENT_SIGNED';
    const STATE_AWAITING_COMPANY = 'AWAITING_COMPANY';
    const STATE_FULLY_SIGNED = 'FULLY_SIGNED';
    
    private $contractId;
    private $db;
    private $mail;
    private $currentState;
    
    public function __construct($contractId)
    {
        $this->contractId = $contractId;
        $this->db = Database::getInstance()->getConnection();
        $this->mail = new Mail();
        $this->currentState = $this->loadCurrentState();
    }
    
    private function loadCurrentState()
    {
        $query = "SELECT signing_state FROM contracts WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$this->contractId]);
        $result = $stmt->fetch();
        
        return $result ? $result['signing_state'] : self::STATE_DRAFT;
    }
    
    private function getContractDetails()
    {
        $query = "SELECT * FROM contracts WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$this->contractId]);
        return $stmt->fetch();
    }
    
    private function getClientEmail()
    {
        // Get from doc_signing_order or use default
        $query = "SELECT user_id FROM doc_signing_order WHERE contract_id = ? AND party_role = 'client' LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$this->contractId]);
        $result = $stmt->fetch();
        
        return $result ? $result['user_id'] : 'client@example.com';
    }
    
    private function getLatestDocumentHash()
    {
        $query = "SELECT doc_hash FROM doc_signatures WHERE contract_id = ? ORDER BY signed_at DESC LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$this->contractId]);
        $result = $stmt->fetch();
        
        return $result ? $result['doc_hash'] : null;
    }
    
    private function updateState($newState, $additionalData = [])
{
    $this->db->beginTransaction();
    
    try {
        $updateFields = "signing_state = ?";
        $params = [$newState];
        
        if (isset($additionalData['client_signed_at'])) {
            $updateFields .= ", client_signed_at = ?";
            $params[] = $additionalData['client_signed_at'];
        }
        
        if (isset($additionalData['company_signed_at'])) {
            $updateFields .= ", company_signed_at = ?";
            $params[] = $additionalData['company_signed_at'];
        }
        
        if (isset($additionalData['finalized_at'])) {
            $updateFields .= ", finalized_at = ?";
            $params[] = $additionalData['finalized_at'];
        }
        
        $updateFields .= ", updated_at = NOW()";
        $params[] = $this->contractId;
        
        $query = "UPDATE contracts SET {$updateFields} WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        
        $docHash = $this->getLatestDocumentHash();
        
        if ($docHash === null) {
            $docHash = 'state_' . $newState . '_' . time();
        }
        
        $auditQuery = "INSERT INTO doc_signature_audit 
                       (contract_id, signer_id, event_type, doc_hash, ip_address, user_agent, timestamp) 
                       VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $auditStmt = $this->db->prepare($auditQuery);
        $auditStmt->execute([
            $this->contractId,
            $additionalData['signer_id'] ?? 'system',
            $this->getEventTypeForTransition($newState),
            $docHash,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        
        $this->db->commit();
        $this->currentState = $newState;
        
        $this->sendEmailOnTransition($newState, $additionalData);
        
        return true;
        
    } catch (Exception $e) {
        $this->db->rollBack();
        throw $e;
    }
}
    
    private function getEventTypeForTransition($newState)
    {
        $events = [
            self::STATE_AWAITING_CLIENT => 'contract_submitted',
            self::STATE_CLIENT_SIGNED => 'client_signed',
            self::STATE_AWAITING_COMPANY => 'escalated_to_company',
            self::STATE_FULLY_SIGNED => 'company_signed'
        ];
        
        return $events[$newState] ?? 'state_change';
    }
    
    private function sendEmailOnTransition($newState, $additionalData = [])
{
    $contract = $this->getContractDetails();
    
    if (!$contract) {
        return;
    }
    
    $baseUrl = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/itec_contract_system";
    $companyEmail = getenv('SMTP_FROM_EMAIL') ?: 'company@itec.com';
    $clientEmail = $additionalData['client_email'] ?? $this->getClientEmail();
    
    switch ($newState) {
        case self::STATE_AWAITING_CLIENT:
            // Generate distribution token for client
            $distribution = $this->generateDistributionToken($this->contractId, $clientEmail);
            
            $to = $clientEmail;
            $subject = "Contract Ready for Signing - {$contract['title']}";
            $body = "<html><body>
                <h2>Contract Ready for Signing</h2>
                <p>Dear Client,</p>
                <p>Contract <strong>{$contract['title']}</strong> is ready for your signature.</p>
                
                <p><strong>Option 1 - Digital Sign:</strong><br>
                <a href='{$distribution['sign_url']}'>Click here to sign digitally</a></p>
                
                <p><strong>Option 2 - Hard Copy:</strong><br>
                <a href='{$baseUrl}/contracts/{$this->contractId}/print-pdf'>Download Print-Ready PDF</a><br>
                Print the contract, sign it physically, and upload the scanned copy using the link above.</p>
                
                <p>This signing link expires on: {$distribution['expires_at']}</p>
                
                <p>Thank you,<br>ITEC Team</p>
                </body></html>";
            
            $this->mail->send($to, $subject, $body);
            break;
            
        case self::STATE_CLIENT_SIGNED:
            $to = $companyEmail;
            $subject = "Contract Signed by Client - {$contract['title']}";
            $body = "<html><body>
                <h2>Contract Signed by Client</h2>
                <p>Contract <strong>{$contract['title']}</strong> has been signed by the client.</p>
                <p><a href='{$baseUrl}/contracts/review/{$this->contractId}'>Review and Add Company Signature</a></p>
                </body></html>";
            $this->mail->send($to, $subject, $body);
            break;
            
        case self::STATE_AWAITING_COMPANY:
            $to = $companyEmail;
            $subject = "Company Signature Required - {$contract['title']}";
            $body = "<html><body>
                <h2>Company Signature Required</h2>
                <p>Contract <strong>{$contract['title']}</strong> requires company signature and seal.</p>
                <p><a href='{$baseUrl}/contracts/sign-company/{$this->contractId}'>Sign as Company</a></p>
                </body></html>";
            $this->mail->send($to, $subject, $body);
            break;
            
        case self::STATE_FULLY_SIGNED:
            $to = $clientEmail;
            $subject = "Contract Fully Executed - {$contract['title']}";
            $body = "<html><body>
                <h2>Contract Fully Executed</h2>
                <p>Dear Client,</p>
                <p>Contract <strong>{$contract['title']}</strong> has been fully executed.</p>
                <p><a href='{$baseUrl}/contracts/view/{$this->contractId}'>View Final Contract</a></p>
                <p>The final PDF is attached to this email for your records.</p>
                </body></html>";
            $this->mail->send($to, $subject, $body);
            
            // Trigger stamp pipeline
            $this->triggerStampPipeline($additionalData);
            break;
    }
}


    private function generateDistributionToken($contractId, $clientEmail)
{
    // Generate unique token
    $expiryDays = 30;
    $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiryDays} days"));
    
    // Create token: hash(contract_id + client_email + secret + expiry)
    $secret = getenv('APP_SECRET') ?: 'itec_contract_secret_key';
    $tokenData = $contractId . $clientEmail . $secret . $expiresAt;
    $token = hash('sha256', $tokenData);
    
    // Store in doc_distributions table
    $sql = "INSERT INTO doc_distributions (contract_id, recipient_email, token, expires_at, status, created_at) 
            VALUES (:contract_id, :recipient_email, :token, :expires_at, 'pending', NOW())";
    
    $stmt = $this->db->prepare($sql);
    $stmt->execute([
        'contract_id' => $contractId,
        'recipient_email' => $clientEmail,
        'token' => $token,
        'expires_at' => $expiresAt
    ]);
    
    return [
        'token' => $token,
        'expires_at' => $expiresAt,
        'sign_url' => "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/itec_contract_system/access/{$token}"
    ];
}
    private function triggerStampPipeline($additionalData = [])
    {
        try {
            $sealService = new OscarSealService();
            $approverName = $additionalData['signer_id'] ?? 'Company Representative';
            $result = $sealService->applySeal($this->contractId, $approverName);
            
            if ($result['success']) {
                $updateStmt = $this->db->prepare("UPDATE contracts SET file_path = ? WHERE id = ?");
                $updateStmt->execute([$result['sealed_file'], $this->contractId]);
            }
        } catch (Exception $e) {
            error_log("Stamp pipeline failed: " . $e->getMessage());
        }
    }
    
    // ============================================
    // Main State Transition Methods
    // ============================================
    
   public function submitForSigning($signerId, $clientEmail = null)
{
    if ($this->currentState !== self::STATE_DRAFT) {
        throw new Exception("Cannot submit contract from state: {$this->currentState}");
    }
    
    // Get client email if not provided
    if (!$clientEmail) {
        $clientEmail = $this->getClientEmail();
    }
    
    $this->updateState(self::STATE_AWAITING_CLIENT, [
        'signer_id' => $signerId,
        'client_email' => $clientEmail
    ]);
    
    return [
        'success' => true,
        'old_state' => self::STATE_DRAFT,
        'new_state' => self::STATE_AWAITING_CLIENT,
        'message' => 'Contract submitted for client signing'
    ];
}
    
    public function clientSign($signerId, $docHash = null)
    {
        if ($this->currentState !== self::STATE_AWAITING_CLIENT) {
            throw new Exception("Cannot sign from state: {$this->currentState}");
        }
        
        $this->updateState(self::STATE_CLIENT_SIGNED, [
            'signer_id' => $signerId,
            'client_signed_at' => date('Y-m-d H:i:s'),
            'doc_hash' => $docHash
        ]);
        
        return [
            'success' => true,
            'old_state' => self::STATE_AWAITING_CLIENT,
            'new_state' => self::STATE_CLIENT_SIGNED,
            'message' => 'Client signed successfully'
        ];
    }
    
    public function escalateToCompany($signerId)
    {
        if ($this->currentState !== self::STATE_CLIENT_SIGNED) {
            throw new Exception("Cannot escalate from state: {$this->currentState}");
        }
        
        $this->updateState(self::STATE_AWAITING_COMPANY, [
            'signer_id' => $signerId
        ]);
        
        return [
            'success' => true,
            'old_state' => self::STATE_CLIENT_SIGNED,
            'new_state' => self::STATE_AWAITING_COMPANY,
            'message' => 'Contract escalated for company signature'
        ];
    }
    
    public function companySign($signerId, $docHash = null)
    {
        if ($this->currentState !== self::STATE_AWAITING_COMPANY) {
            throw new Exception("Cannot sign from state: {$this->currentState}");
        }
        
        $this->updateState(self::STATE_FULLY_SIGNED, [
            'signer_id' => $signerId,
            'company_signed_at' => date('Y-m-d H:i:s'),
            'finalized_at' => date('Y-m-d H:i:s'),
            'doc_hash' => $docHash
        ]);
        
        return [
            'success' => true,
            'old_state' => self::STATE_AWAITING_COMPANY,
            'new_state' => self::STATE_FULLY_SIGNED,
            'message' => 'Contract fully executed'
        ];
    }
    
    public function getCurrentState()
    {
        $contract = $this->getContractDetails();
        $latestHash = $this->getLatestDocumentHash();
        
        return [
            'contract_id' => $this->contractId,
            'state' => $this->currentState,
            'document_hash' => $latestHash,
            'allowed_actions' => $this->getAllowedActions(),
            'timeline' => [
                'client_signed_at' => $contract['client_signed_at'] ?? null,
                'company_signed_at' => $contract['company_signed_at'] ?? null,
                'finalized_at' => $contract['finalized_at'] ?? null
            ]
        ];
    }
    
    private function getAllowedActions()
    {
        $actions = [
            self::STATE_DRAFT => ['submit_for_signing'],
            self::STATE_AWAITING_CLIENT => ['client_sign'],
            self::STATE_CLIENT_SIGNED => ['escalate_to_company'],
            self::STATE_AWAITING_COMPANY => ['company_sign'],
            self::STATE_FULLY_SIGNED => []
        ];
        
        return $actions[$this->currentState] ?? [];
    }
    
    public function canTransitionTo($targetState)
    {
        $allowedTransitions = [
            self::STATE_DRAFT => [self::STATE_AWAITING_CLIENT],
            self::STATE_AWAITING_CLIENT => [self::STATE_CLIENT_SIGNED],
            self::STATE_CLIENT_SIGNED => [self::STATE_AWAITING_COMPANY],
            self::STATE_AWAITING_COMPANY => [self::STATE_FULLY_SIGNED],
            self::STATE_FULLY_SIGNED => []
        ];
        
        return in_array($targetState, $allowedTransitions[$this->currentState] ?? []);
    }
}