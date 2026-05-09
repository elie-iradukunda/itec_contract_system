<?php

namespace Services;

use Core\Database;

class OscarSignatureService
{
    private $db;
    private $privateKeyPath;
    private $publicKeyPath;
    private $signaturesDir;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        
        // Define paths relative to the service location.
        $this->privateKeyPath = __DIR__ . '/../storage/keys/private.key';
        $this->publicKeyPath = __DIR__ . '/../storage/keys/public.key';
        $this->signaturesDir = __DIR__ . '/../storage/signatures/';
        
        // Initialize environment.
        $this->ensureDirectoriesExist();
        $this->ensureKeysExist();
    }

    /**
     * Ensures required storage directories exist with correct permissions.
     */
    private function ensureDirectoriesExist()
    {
        $dirs = [
            dirname($this->privateKeyPath),
            $this->signaturesDir
        ];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
        }
    }

    /**
     * Checks for existing keys or triggers generation.
     */
    private function ensureKeysExist()
    {
        if (!file_exists($this->privateKeyPath) || !file_exists($this->publicKeyPath)) {
            $this->generateKeyPair();
        }
    }

    /**
     * Generates a new RSA key pair.
     * Automatically detects environment to locate openssl.cnf.
     */
    private function generateKeyPair()
    {
        $config = [
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ];

        // Environment Detection: Fix for 'No such process' error.
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $winConfig = 'C:/xampp/php/extras/ssl/openssl.cnf';
            if (file_exists($winConfig)) {
                $config["config"] = $winConfig;
            }
        } else {
            // Standard Linux production path.
            if (file_exists('/etc/ssl/openssl.cnf')) {
                $config["config"] = '/etc/ssl/openssl.cnf';
            }
        }

        $privateKey = openssl_pkey_new($config);
        
        if ($privateKey === false) {
            throw new \Exception('Failed to generate private key: ' . openssl_error_string());
        }
        
        // Export private key to file.
        $exportParams = isset($config["config"]) ? ["config" => $config["config"]] : [];
        $exportResult = openssl_pkey_export_to_file($privateKey, $this->privateKeyPath, null, $exportParams);
        
        if ($exportResult === false) {
            throw new \Exception('Failed to export private key: ' . openssl_error_string());
        }
        
        // Extract public key.
        $keyDetails = openssl_pkey_get_details($privateKey);
        if ($keyDetails === false) {
            throw new \Exception('Failed to get public key details: ' . openssl_error_string());
        }
        
        file_put_contents($this->publicKeyPath, $keyDetails['key']);
        
        // Set restrictive permissions for security.
        chmod($this->privateKeyPath, 0600);
        chmod($this->publicKeyPath, 0644);
    }

    private function getPrivateKey()
    {
        if (!file_exists($this->privateKeyPath)) {
            throw new \Exception('Private key file missing.');
        }
        return file_get_contents($this->privateKeyPath);
    }

    private function getPublicKey()
    {
        if (!file_exists($this->publicKeyPath)) {
            throw new \Exception('Public key file missing.');
        }
        return file_get_contents($this->publicKeyPath);
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
        $filename = "sig_{$contractId}_{$sanitizedSigner}_" . time() . ".sig";
        $filepath = $this->signaturesDir . $filename;
        
        file_put_contents($filepath, $signature);
        
        return $filepath;
    }

    /**
     * Signs a document hash using the system's private key.
     */
    public function signDocument($contractId, $signerId, $filePath = null)
    {
        $stmt = $this->db->prepare("SELECT id, file_path FROM contracts WHERE id = ?");
        $stmt->execute([$contractId]);
        $contract = $stmt->fetch();
        
        if (!$contract) {
            return ['success' => false, 'error' => 'Contract not found in database.'];
        }
        
        $targetPath = $filePath ?? $contract['file_path'];
        $documentHash = $this->generateHash($targetPath);
        
        if (!$documentHash) {
            return ['success' => false, 'error' => 'Document file not found at: ' . $targetPath];
        }
        
        $privateKey = openssl_pkey_get_private($this->getPrivateKey());
        if ($privateKey === false) {
            return ['success' => false, 'error' => 'Invalid private key format.'];
        }
        
        $signature = '';
        if (!openssl_sign($documentHash, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            return ['success' => false, 'error' => 'Signing failed: ' . openssl_error_string()];
        }
        
        $signatureFilePath = $this->saveSignatureFile($signature, $contractId, $signerId);
        
        $sql = "INSERT INTO doc_signatures (contract_id, signer_id, signature_file_path, public_key, doc_hash, signed_at) 
                VALUES (:contract_id, :signer_id, :signature_file_path, :public_key, :doc_hash, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            'contract_id' => $contractId,
            'signer_id' => $signerId,
            'signature_file_path' => $signatureFilePath,
            'public_key' => $this->getPublicKey(),
            'doc_hash' => $documentHash
        ]);
        
        if ($result) {
            return [
                'success' => true, 
                'signature_id' => $this->db->lastInsertId(), 
                'signature_file' => $signatureFilePath,
                'doc_hash' => $documentHash
            ];
        }
        
        return ['success' => false, 'error' => 'Database insertion failed.'];
    }

    /**
     * Verifies the document integrity against the stored signature.
     */
    public function verifyDocument($contractId)
    {
        $sql = "SELECT signature_file_path, public_key, doc_hash, signed_at 
                FROM doc_signatures 
                WHERE contract_id = :contract_id 
                ORDER BY signed_at DESC LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['contract_id' => $contractId]);
        $signature = $stmt->fetch();
        
        if (!$signature) {
            return ['valid' => false, 'warning' => 'No signature record exists.', 'tampered' => false];
        }
        
        $contractStmt = $this->db->prepare("SELECT file_path FROM contracts WHERE id = ?");
        $contractStmt->execute([$contractId]);
        $contract = $contractStmt->fetch();
        
        if (!$contract || !file_exists($contract['file_path'])) {
            return ['valid' => false, 'warning' => 'Original contract file is missing.', 'tampered' => true];
        }
        
        $currentHash = $this->generateHash($contract['file_path']);
        
        // 1. Check if the file content hash matches.
        if ($currentHash !== $signature['doc_hash']) {
            return [
                'valid' => false,
                'warning' => 'DOCUMENT TAMPERED: Content does not match signed version.',
                'tampered' => true,
                'original_hash' => $signature['doc_hash'],
                'current_hash' => $currentHash
            ];
        }
        
        // 2. Cryptographically verify the signature file.
        if (!file_exists($signature['signature_file_path'])) {
            return ['valid' => false, 'warning' => 'Signature file missing from storage.', 'tampered' => true];
        }
        
        $signatureBlob = file_get_contents($signature['signature_file_path']);
        $publicKey = openssl_pkey_get_public($signature['public_key']);
        
        $result = openssl_verify($signature['doc_hash'], $signatureBlob, $publicKey, OPENSSL_ALGO_SHA256);
        
        if ($result === 1) {
            return [
                'valid' => true,
                'signed_at' => $signature['signed_at'],
                'message' => 'Document and signature are authentic.'
            ];
        }
        
        return [
            'valid' => false,
            'warning' => 'Cryptographic verification failed.',
            'tampered' => true
        ];
    }

    public function getSignerChain($contractId)
    {
        $sql = "SELECT signer_id, doc_hash, signed_at FROM doc_signatures 
                WHERE contract_id = ? ORDER BY signed_at ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$contractId]);
        return $stmt->fetchAll();
    }
}