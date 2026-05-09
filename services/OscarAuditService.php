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
    
    public function logEvent($contractId, $signerId, $eventType, $docHash = null)
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $query = "INSERT INTO doc_signature_audit 
                  (contract_id, signer_id, event_type, doc_hash, ip_address, user_agent, timestamp) 
                  VALUES (:contract_id, :signer_id, :event_type, :doc_hash, :ip, :ua, NOW())";
        
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            'contract_id' => $contractId,
            'signer_id' => $signerId,
            'event_type' => $eventType,
            'doc_hash' => $docHash,
            'ip' => $ip,
            'ua' => $userAgent
        ]);
    }
    
    public function getAuditTrail($contractId)
    {
        $query = "SELECT * FROM doc_signature_audit 
                  WHERE contract_id = :contract_id 
                  ORDER BY timestamp ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute(['contract_id' => $contractId]);
        return $stmt->fetchAll();
    }
    
    public function getFullChain($contractId)
    {
        $query = "SELECT a.*, s.doc_hash as signed_hash, s.snapshot_file_path
                  FROM doc_signature_audit a
                  LEFT JOIN doc_signatures s ON a.contract_id = s.contract_id AND a.signer_id = s.signer_id
                  WHERE a.contract_id = :contract_id
                  ORDER BY a.timestamp ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute(['contract_id' => $contractId]);
        return $stmt->fetchAll();
    }
}