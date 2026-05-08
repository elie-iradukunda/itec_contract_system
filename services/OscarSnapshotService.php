<?php

namespace Services;

use Core\Database;

class OscarSnapshotService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function createSnapshot($contractId, $signatureId, $filePath)
    {
        $hash = hash_file('sha256', $filePath);
        $snapshotPath = __DIR__ . "/../storage/snapshots/{$contractId}/{$signatureId}.pdf";
        
        // Create directory if not exists
        if (!is_dir(dirname($snapshotPath))) {
            mkdir(dirname($snapshotPath), 0777, true);
        }
        
        // Copy file as snapshot
        copy($filePath, $snapshotPath);
        
        $sql = "INSERT INTO document_hashes (contract_id, signature_id, file_path, hash_value) 
                VALUES (:contract_id, :signature_id, :file_path, :hash_value)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'contract_id' => $contractId,
            'signature_id' => $signatureId,
            'file_path' => $snapshotPath,
            'hash_value' => $hash
        ]);
    }

    public function getSnapshots($contractId)
    {
        $sql = "SELECT * FROM document_hashes WHERE contract_id = :contract_id ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['contract_id' => $contractId]);
        return $stmt->fetchAll();
    }
}