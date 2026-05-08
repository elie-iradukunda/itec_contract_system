<?php

namespace Services;

use Core\Database;

class OscarAuditService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function logEvent($contractId, $userId, $action, $eventType, $details = [])
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $sql = "INSERT INTO audit_logs (contract_id, user_id, action, event_type, ip_address, user_agent, details) 
                VALUES (:contract_id, :user_id, :action, :event_type, :ip_address, :user_agent, :details)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'contract_id' => $contractId,
            'user_id' => $userId,
            'action' => $action,
            'event_type' => $eventType,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'details' => json_encode($details)
        ]);
    }

    public function getAuditTrail($contractId)
    {
        $sql = "SELECT * FROM audit_logs WHERE contract_id = :contract_id ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['contract_id' => $contractId]);
        return $stmt->fetchAll();
    }
}