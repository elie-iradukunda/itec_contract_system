<?php

namespace Models;

use Core\Database;
use PDO;

class AuditLog
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create($data)
    {
        $sql = "INSERT INTO audit_logs (contract_id, user_id, action, event_type, ip_address, user_agent, details) 
                VALUES (:contract_id, :user_id, :action, :event_type, :ip_address, :user_agent, :details)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'contract_id' => $data['contract_id'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'action' => $data['action'] ?? null,
            'event_type' => $data['event_type'] ?? null,
            'ip_address' => $data['ip_address'] ?? $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $data['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? null,
            'details' => isset($data['details']) ? json_encode($data['details']) : null
        ]);
    }

    public function findByContract($contractId)
    {
        $sql = "SELECT * FROM audit_logs WHERE contract_id = :contract_id ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['contract_id' => $contractId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByUser($userId)
    {
        $sql = "SELECT * FROM audit_logs WHERE user_id = :user_id ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findAll($limit = 100, $offset = 0)
    {
        $sql = "SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}