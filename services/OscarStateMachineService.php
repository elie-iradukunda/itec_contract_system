<?php

namespace Services;

use Core\Database;

class OscarStateMachineService
{
    const STATE_DRAFT = 'DRAFT';
    const STATE_AWAITING_CLIENT = 'AWAITING_CLIENT';
    const STATE_CLIENT_SIGNED = 'CLIENT_SIGNED';
    const STATE_AWAITING_COMPANY = 'AWAITING_COMPANY';
    const STATE_FULLY_SIGNED = 'FULLY_SIGNED';

    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function transition($contractId, $currentState)
    {
        $nextState = $this->getNextState($currentState);
        
        if (!$nextState) {
            return false;
        }
        
        // Start transaction
        $this->db->beginTransaction();
        
        try {
            // Update contract state
            $sql = "UPDATE contracts SET signing_state = :state WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['state' => $nextState, 'id' => $contractId]);
            
            // Log to audit
            $auditSql = "INSERT INTO audit_logs (contract_id, user_id, action, event_type, details) 
                         VALUES (:contract_id, :user_id, :action, :event_type, :details)";
            $auditStmt = $this->db->prepare($auditSql);
            $auditStmt->execute([
                'contract_id' => $contractId,
                'user_id' => $_SESSION['user_id'] ?? 0,
                'action' => 'State transition from ' . $currentState . ' to ' . $nextState,
                'event_type' => 'state_change',
                'details' => json_encode(['from' => $currentState, 'to' => $nextState])
            ]);
            
            $this->db->commit();
            
            // Auto-email next party
            $this->sendEmailOnTransition($contractId, $nextState);
            
            return $nextState;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function getNextState($currentState)
    {
        $transitions = [
            self::STATE_DRAFT => self::STATE_AWAITING_CLIENT,
            self::STATE_AWAITING_CLIENT => self::STATE_CLIENT_SIGNED,
            self::STATE_CLIENT_SIGNED => self::STATE_AWAITING_COMPANY,
            self::STATE_AWAITING_COMPANY => self::STATE_FULLY_SIGNED,
        ];
        
        return $transitions[$currentState] ?? null;
    }

    private function sendEmailOnTransition($contractId, $newState)
    {
        // TODO: Implement email sending
        // Get contract details and send to appropriate party
    }
}