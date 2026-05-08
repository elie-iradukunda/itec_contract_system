<?php

namespace Services;

use Core\Database;

class OscarSignatureService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function generateHash($filePath)
    {
        return hash_file('sha256', $filePath);
    }

    public function signDocument($contractId, $userId, $role, $filePath)
    {
        $hash = $this->generateHash($filePath);
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $sql = "INSERT INTO contract_signatures (contract_id, signer_id, signer_role, signature_blob, document_hash, ip_address, user_agent) 
                VALUES (:contract_id, :signer_id, :signer_role, :signature_blob, :document_hash, :ip_address, :user_agent)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'contract_id' => $contractId,
            'signer_id' => $userId,
            'signer_role' => $role,
            'signature_blob' => 'signed_' . time(),
            'document_hash' => $hash,
            'ip_address' => $ip,
            'user_agent' => $userAgent
        ]);
    }

    public function verifySignature($contractId)
    {
        $sql = "SELECT document_hash, file_path FROM contract_signatures cs 
                JOIN contracts c ON cs.contract_id = c.id 
                WHERE c.id = :contract_id ORDER BY cs.signed_at DESC LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['contract_id' => $contractId]);
        $signature = $stmt->fetch();
        
        if (!$signature) {
            return ['valid' => false, 'warning' => 'No signature found'];
        }
        
        $currentHash = $this->generateHash($signature['file_path']);
        
        if ($currentHash !== $signature['document_hash']) {
            return ['valid' => false, 'warning' => 'Document modified after signing'];
        }
        
        return ['valid' => true, 'warning' => null];
    }
}