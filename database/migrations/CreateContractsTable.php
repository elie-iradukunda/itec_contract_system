<?php

namespace Services;

use Core\Database;
use Core\Mail;
use Exception;
use PDO;

class OscarStateMachineService
{
    // State constants
    const STATE_DRAFT = 'DRAFT';
    const STATE_AWAITING_CLIENT = 'AWAITING_CLIENT';
    const STATE_CLIENT_SIGNED = 'CLIENT_SIGNED';
    const STATE_AWAITING_COMPANY = 'AWAITING_COMPANY';
    const STATE_FULLY_SIGNED = 'FULLY_SIGNED';
    
    private $db;
    private $mail;
    private $contractId;
    private $currentState;
    
    public function __construct($contractId)
    {
        $this->db = Database::getInstance()->getConnection();
        $this->mail = new Mail();
        $this->contractId = $contractId;
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
        $query = "SELECT c.*, cl.name as client_name, cl.email as client_email 
                  FROM contracts c 
                  LEFT JOIN clients cl ON c.client_id = cl.id 
                  WHERE c.id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$this->contractId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function updateState($newState, $additionalData = [])
    {
        // Begin atomic transaction
        $this->db->beginTransaction();
        
        try {
            // Build update query
            $updateFields = "signing_state = ?, updated_at = NOW()";
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
            
            $params[] = $this->contractId;
            
            $query = "UPDATE contracts SET {$updateFields} WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
            // Insert audit log (within same transaction)
            $auditQuery = "INSERT INTO doc_signature_audit 
                           (contract_id, signer_id, event_type, doc_hash, ip_address, user_agent, timestamp) 
                           VALUES (?, ?, ?, ?, ?, ?, NOW())";
            
            $auditStmt = $this->db->prepare($auditQuery);
            $auditStmt->execute([
                $this->contractId,
                $additionalData['signer_id'] ?? 'system',
                $this->getEventTypeForTransition($newState),
                $additionalData['doc_hash'] ?? null,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
            
            // Commit transaction
            $this->db->commit();
            $this->currentState = $newState;
            
            // Auto-email next party and trigger pipeline (after commit)
            $this->sendEmailOnTransition($newState);
            
            if ($newState === self::STATE_FULLY_SIGNED) {
                $this->triggerStampPipeline();
            }
            
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
    
    private function sendEmailOnTransition($newState)
    {
        $contract = $this->getContractDetails();
        
        if (!$contract) {
            return;
        }
        
        $companyEmail = getenv('SMTP_FROM_EMAIL') ?: 'company@itec.com';
        
        switch ($newState) {
            case self::STATE_AWAITING_CLIENT:
                // Email to client - ready to sign
                $this->mail->send(
                    $contract['client_email'],
                    'Contract Ready for Signing - ' . $contract['title'],
                    "Dear {$contract['client_name']},\n\n"
                    . "Contract '{$contract['title']}' is ready for your signature.\n\n"
                    . "Please click the link to sign: http://{$_SERVER['HTTP_HOST']}/sign/{$this->contractId}\n\n"
                    . "Thank you."
                );
                break;
                
            case self::STATE_CLIENT_SIGNED:
                // Email to company - client has signed
                $this->mail->send(
                    $companyEmail,
                    'Contract Signed by Client - ' . $contract['title'],
                    "Contract '{$contract['title']}' has been signed by the client.\n\n"
                    . "Please review and add company signature: http://{$_SERVER['HTTP_HOST']}/review/{$this->contractId}\n\n"
                    . "Action Required."
                );
                break;
                
            case self::STATE_AWAITING_COMPANY:
                // Email reminder to company
                $this->mail->send(
                    $companyEmail,
                    'Action Required: Company Signature Needed - ' . $contract['title'],
                    "Contract '{$contract['title']}' requires company signature and seal.\n\n"
                    . "Please complete: http://{$_SERVER['HTTP_HOST']}/sign-company/{$this->contractId}"
                );
                break;
                
            case self::STATE_FULLY_SIGNED:
                // Email to client - contract fully executed
                $this->mail->send(
                    $contract['client_email'],
                    'Contract Fully Executed - ' . $contract['title'],
                    "Dear {$contract['client_name']},\n\n"
                    . "Contract '{$contract['title']}' has been fully executed.\n\n"
                    . "You can view the final contract at: http://{$_SERVER['HTTP_HOST']}/view/{$this->contractId}\n\n"
                    . "Thank you for your business."
                );
                break;
        }
    }
    
    private function triggerStampPipeline()
    {
        // Get contract details
        $contract = $this->getContractDetails();
        
        if (!$contract || !file_exists($contract['file_path'])) {
            return;
        }
        
        // Trigger Oscar's seal pipeline (Task O2)
        try {
            $sealService = new OscarSealService();
            
            // Get approver name (who signed last)
            $stmt = $this->db->prepare("SELECT signer_id FROM doc_signature_audit 
                                        WHERE contract_id = ? AND event_type = 'company_signed' 
                                        ORDER BY timestamp DESC LIMIT 1");
            $stmt->execute([$this->contractId]);
            $signer = $stmt->fetch();
            $approverName = $signer['signer_id'] ?? 'Company Representative';
            
            $result = $sealService->applySeal($this->contractId, $approverName);
            
            if ($result['success']) {
                // Update contract with sealed file path
                $updateStmt = $this->db->prepare("UPDATE contracts SET file_path = ? WHERE id = ?");
                $updateStmt->execute([$result['sealed_file'], $this->contractId]);
            }
            
        } catch (Exception $e) {
            error_log("Stamp pipeline failed: " . $e->getMessage());
        }
    }
    
    // Public transition methods
    
    public function submitForSigning($signerId)
    {
        if ($this->currentState !== self::STATE_DRAFT) {
            throw new Exception("Cannot submit contract from state: {$this->currentState}");
        }
        
        $this->updateState(self::STATE_AWAITING_CLIENT, [
            'signer_id' => $signerId
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
        
        return [
            'contract_id' => $this->contractId,
            'state' => $this->currentState,
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
    
    public function transition($targetState, $signerId, $docHash = null)
    {
        if (!$this->canTransitionTo($targetState)) {
            throw new Exception("Cannot transition from {$this->currentState} to {$targetState}");
        }
        
        switch ($targetState) {
            case self::STATE_AWAITING_CLIENT:
                return $this->submitForSigning($signerId);
            case self::STATE_CLIENT_SIGNED:
                return $this->clientSign($signerId, $docHash);
            case self::STATE_AWAITING_COMPANY:
                return $this->escalateToCompany($signerId);
            case self::STATE_FULLY_SIGNED:
                return $this->companySign($signerId, $docHash);
            default:
                throw new Exception("Invalid target state: {$targetState}");
        }
    }
}