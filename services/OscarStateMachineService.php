<?php

namespace Services;

use Core\Database;
use Core\Mail;

class OscarStateMachineService
{
    private $db;
    private $mail;
    
    // State constants as class properties
    private $STATE_DRAFT = 'DRAFT';
    private $STATE_AWAITING_CLIENT = 'AWAITING_CLIENT';
    private $STATE_CLIENT_SIGNED = 'CLIENT_SIGNED';
    private $STATE_AWAITING_COMPANY = 'AWAITING_COMPANY';
    private $STATE_FULLY_SIGNED = 'FULLY_SIGNED';

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->mail = new Mail();
    }

    public function transition($contractId, $currentState, $userId = null)
    {
        $nextState = $this->getNextState($currentState);
        
        if (!$nextState) {
            return ['success' => false, 'error' => 'Invalid state transition'];
        }
        
        // Start transaction
        $this->db->beginTransaction();
        
        try {
            // Update contract state
            $sql = "UPDATE contracts SET signing_state = :state WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['state' => $nextState, 'id' => $contractId]);
            
            // Update timestamps based on state
            if ($nextState === $this->STATE_CLIENT_SIGNED) {
                $this->db->exec("UPDATE contracts SET client_signed_at = NOW() WHERE id = {$contractId}");
            } elseif ($nextState === $this->STATE_FULLY_SIGNED) {
                $this->db->exec("UPDATE contracts SET finalized_at = NOW() WHERE id = {$contractId}");
            }
            
            // Log to audit
            $auditSql = "INSERT INTO audit_logs (contract_id, user_id, action, event_type, details, ip_address, user_agent) 
                         VALUES (:contract_id, :user_id, :action, :event_type, :details, :ip, :ua)";
            $auditStmt = $this->db->prepare($auditSql);
            $auditStmt->execute([
                'contract_id' => $contractId,
                'user_id' => $userId ?? $_SESSION['user_id'] ?? 0,
                'action' => 'State transition from ' . $currentState . ' to ' . $nextState,
                'event_type' => 'state_change',
                'details' => json_encode(['from' => $currentState, 'to' => $nextState]),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'ua' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
            
            $this->db->commit();
            
            // Auto-email next party (after commit)
            $this->sendEmailOnTransition($contractId, $nextState);
            
            return ['success' => true, 'new_state' => $nextState];
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getNextState($currentState)
    {
        $transitions = [
            $this->STATE_DRAFT => $this->STATE_AWAITING_CLIENT,
            $this->STATE_AWAITING_CLIENT => $this->STATE_CLIENT_SIGNED,
            $this->STATE_CLIENT_SIGNED => $this->STATE_AWAITING_COMPANY,
            $this->STATE_AWAITING_COMPANY => $this->STATE_FULLY_SIGNED,
        ];
        
        return isset($transitions[$currentState]) ? $transitions[$currentState] : null;
    }

    public function getCurrentState($contractId)
    {
        $stmt = $this->db->prepare("SELECT signing_state FROM contracts WHERE id = :id");
        $stmt->execute(['id' => $contractId]);
        $result = $stmt->fetch();
        return $result ? $result['signing_state'] : null;
    }

    public function canTransition($contractId, $expectedState)
    {
        $currentState = $this->getCurrentState($contractId);
        return $currentState === $expectedState;
    }

    public function getStates()
    {
        return [
            'draft' => $this->STATE_DRAFT,
            'awaiting_client' => $this->STATE_AWAITING_CLIENT,
            'client_signed' => $this->STATE_CLIENT_SIGNED,
            'awaiting_company' => $this->STATE_AWAITING_COMPANY,
            'fully_signed' => $this->STATE_FULLY_SIGNED
        ];
    }

    private function sendEmailOnTransition($contractId, $newState)
    {
        // Get contract details with client info
        $stmt = $this->db->prepare("
            SELECT c.*, cl.name as client_name, cl.email as client_email 
            FROM contracts c 
            JOIN clients cl ON c.client_id = cl.id 
            WHERE c.id = :id
        ");
        $stmt->execute(['id' => $contractId]);
        $contract = $stmt->fetch();
        
        if (!$contract) {
            error_log("Contract not found for email: {$contractId}");
            return false;
        }
        
        $companyEmail = getenv('SMTP_FROM_EMAIL') ?: 'company@itec.com';
        
        if ($newState === $this->STATE_AWAITING_CLIENT) {
            // Send email to client
            return $this->mail->sendContractReadyForSigning(
                $contract['client_email'],
                $contract['title'],
                $contractId,
                $contract['client_name']
            );
        } elseif ($newState === $this->STATE_CLIENT_SIGNED) {
            // Send email to company representative
            return $this->mail->sendContractSignedByClient(
                $companyEmail,
                $contract['title'],
                $contractId
            );
        } elseif ($newState === $this->STATE_FULLY_SIGNED) {
            // Send final contract to client
            return $this->mail->sendContractFullyExecuted(
                $contract['client_email'],
                $contract['title'],
                $contractId,
                $contract['client_name']
            );
        }
        
        return false;
    }

    public function submitForSigning($contractId, $userId = null)
    {
        $currentState = $this->getCurrentState($contractId);
        
        if ($currentState !== $this->STATE_DRAFT) {
            return ['success' => false, 'error' => 'Contract must be in DRAFT state to submit'];
        }
        
        return $this->transition($contractId, $currentState, $userId);
    }

    public function markClientSigned($contractId, $userId = null)
    {
        $currentState = $this->getCurrentState($contractId);
        
        if ($currentState !== $this->STATE_AWAITING_CLIENT) {
            return ['success' => false, 'error' => 'Contract must be AWAITING_CLIENT to mark client signed'];
        }
        
        return $this->transition($contractId, $currentState, $userId);
    }

    public function markCompanySigned($contractId, $userId = null)
    {
        $currentState = $this->getCurrentState($contractId);
        
        if ($currentState !== $this->STATE_CLIENT_SIGNED && $currentState !== $this->STATE_AWAITING_COMPANY) {
            return ['success' => false, 'error' => 'Contract must be CLIENT_SIGNED or AWAITING_COMPANY for company signing'];
        }
        
        return $this->transition($contractId, $currentState, $userId);
    }
}