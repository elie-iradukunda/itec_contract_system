<?php

namespace Services;

use Core\Database;

class OscarSignatureService
{
    private $db;
    private $signaturesDir;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->signaturesDir = __DIR__ . '/../storage/signatures/';
        
        // Create signatures directory if not exists
        if (!is_dir($this->signaturesDir)) {
            mkdir($this->signaturesDir, 0777, true);
        }
    }

    public function generateHash($filePath)
    {
        if (!file_exists($filePath)) {
            return false;
        }
        return hash_file('sha256', $filePath);
    }

    private function saveSignatureFile($signature, $contractId, $signerId)
    {
        $sanitizedSigner = preg_replace('/[^a-zA-Z0-9]/', '_', $signerId);
        $filename = "sig_{$contractId}_{$sanitizedSigner}_" . time() . ".txt";
        $filepath = $this->signaturesDir . $filename;
        
        file_put_contents($filepath, $signature);
        
        return $filepath;
    }

    public function signDocument($contractId, $signerId, $filePath)
    {
        // Check if contract exists
        $stmt = $this->db->prepare("SELECT id, title, file_path FROM contracts WHERE id = ?");
        $stmt->execute([$contractId]);
        $contract = $stmt->fetch();
        
        if (!$contract) {
            return ['success' => false, 'error' => 'Contract not found'];
        }
        
        // Use the contract's file path from database if not provided
        if (empty($filePath)) {
            $filePath = $contract['file_path'];
        }
        
        // Generate document hash
        $documentHash = $this->generateHash($filePath);
        
        if (!$documentHash) {
            return ['success' => false, 'error' => 'Failed to generate document hash. File may not exist.'];
        }
        
        // Create a simple signature (timestamp + signer + hash)
        // This is a simplified signature for testing
        $signatureData = [
            'signer' => $signerId,
            'timestamp' => date('Y-m-d H:i:s'),
            'hash' => $documentHash,
            'contract_id' => $contractId
        ];
        
        $signature = json_encode($signatureData);
        
        // Save signature to file
        $signatureFilePath = $this->saveSignatureFile($signature, $contractId, $signerId);
        
        // Store in database (doc_signatures table)
        $sql = "INSERT INTO doc_signatures (contract_id, signer_id, signature_file_path, public_key, doc_hash, signed_at) 
                VALUES (:contract_id, :signer_id, :signature_file_path, :public_key, :doc_hash, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            'contract_id' => $contractId,
            'signer_id' => $signerId,
            'signature_file_path' => $signatureFilePath,
            'public_key' => 'simplified_signature_method',
            'doc_hash' => $documentHash
        ]);
        
        if ($result) {
            $signatureId = $this->db->lastInsertId();
            return [
                'success' => true, 
                'signature_id' => $signatureId, 
                'signature_file' => $signatureFilePath,
                'doc_hash' => $documentHash
            ];
        }
        
        return ['success' => false, 'error' => 'Failed to save signature to database'];
    }

    public function verifyDocument($contractId)
    {
        // Get the latest signature for this contract
        $sql = "SELECT signature_file_path, doc_hash, signed_at 
                FROM doc_signatures 
                WHERE contract_id = :contract_id 
                ORDER BY signed_at DESC LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['contract_id' => $contractId]);
        $signature = $stmt->fetch();
        
        if (!$signature) {
            return [
                'valid' => false, 
                'warning' => 'No signature found for this contract',
                'tampered' => false
            ];
        }
        
        // Get current contract file path
        $contractStmt = $this->db->prepare("SELECT file_path FROM contracts WHERE id = ?");
        $contractStmt->execute([$contractId]);
        $contract = $contractStmt->fetch();
        
        if (!$contract || !file_exists($contract['file_path'])) {
            return [
                'valid' => false,
                'warning' => 'Contract file not found',
                'tampered' => true
            ];
        }
        
        // Recompute current hash
        $currentHash = $this->generateHash($contract['file_path']);
        
        // Compare hashes
        if ($currentHash !== $signature['doc_hash']) {
            return [
                'valid' => false,
                'warning' => 'DOCUMENT TAMPERED: File has been modified after signing',
                'tampered' => true,
                'original_hash' => $signature['doc_hash'],
                'current_hash' => $currentHash,
                'signed_at' => $signature['signed_at']
            ];
        }
        
        // Check if signature file exists
        if (!file_exists($signature['signature_file_path'])) {
            return [
                'valid' => false,
                'warning' => 'Signature file not found',
                'tampered' => true
            ];
        }
        
        return [
            'valid' => true,
            'warning' => null,
            'tampered' => false,
            'signed_at' => $signature['signed_at'],
            'message' => 'Signature is valid and document has not been tampered'
        ];
    }

    public function getSignerChain($contractId)
    {
        $sql = "SELECT id, signer_id, doc_hash, signed_at 
                FROM doc_signatures 
                WHERE contract_id = :contract_id 
                ORDER BY signed_at ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['contract_id' => $contractId]);
        return $stmt->fetchAll();
    }

    public function getAllSignatures($contractId)
    {
        $sql = "SELECT id, signer_id, signature_file_path, doc_hash, signed_at 
                FROM doc_signatures 
                WHERE contract_id = :contract_id 
                ORDER BY signed_at ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['contract_id' => $contractId]);
        return $stmt->fetchAll();
    }
}