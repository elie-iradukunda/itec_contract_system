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
     * Save a visual signature (base64 PNG data URL) to storage.
     * If $signatureId is provided the file will include it for easier lookup.
     * Returns the relative file path on success.
     */
    public function saveVisualSignature($contractId, $signerId, $dataUrl, $signatureId = null)
    {
        // Expect data URL like 'data:image/png;base64,....' or 'data:image/jpeg;base64,....'
        if (!preg_match('/^data:image\/(png|jpe?g);base64,/i', $dataUrl, $matches)) {
            throw new \Exception('Invalid signature data format');
        }

        list($meta, $b64) = explode('base64,', $dataUrl, 2);
        $decoded = base64_decode($b64);
        if ($decoded === false) {
            throw new \Exception('Failed to decode signature image');
        }

        $sanitizedSigner = preg_replace('/[^a-zA-Z0-9]/', '_', $signerId);
        $suffix = $signatureId ? "_{$signatureId}" : '_' . time();
        $extension = strtolower($matches[1]) === 'png' ? 'png' : 'jpg';
        $filename = "sig_{$contractId}_{$sanitizedSigner}{$suffix}.{$extension}";
        $filepath = $this->signaturesDir . $filename;

        $written = file_put_contents($filepath, $decoded);
        if ($written === false) {
            throw new \Exception('Failed to write visual signature file');
        }

        if ($signatureId) {
            $stmt = $this->db->prepare("
                UPDATE doc_signatures
                SET signature_file_path = ?
                WHERE id = ? AND contract_id = ?
            ");
            $stmt->execute([$filepath, (int) $signatureId, (int) $contractId]);
        }

        return $filepath;
    }

    /**
     * Signs a document hash using the system's private key.
     */
   public function signDocument($contractId, $signerId, $roleOrFilePath = null, $filePath = null)
{
    $stmt = $this->db->prepare("SELECT id, file_path FROM contracts WHERE id = ?");
    $stmt->execute([$contractId]);
    $contract = $stmt->fetch();
    
    if (!$contract) {
        return ['success' => false, 'error' => 'Contract not found in database.'];
    }
    
    $role = in_array($roleOrFilePath, ['client', 'company_rep'], true) ? $roleOrFilePath : $this->signerRole($signerId);
    $targetPath = $this->resolvePath($filePath ?? ($roleOrFilePath && $roleOrFilePath !== $role ? $roleOrFilePath : $contract['file_path']));

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
    
    // Convert signature to base64 for blob storage
    $signatureBlob = base64_encode($signature);
    
    $sql = "INSERT INTO doc_signatures (contract_id, signer_id, signer_role, signature_blob, public_key, doc_hash, signature_algorithm, signed_at) 
            VALUES (:contract_id, :signer_id, :signer_role, :signature_blob, :public_key, :doc_hash, 'SHA256', NOW())";
    
    $stmt = $this->db->prepare($sql);
    
    $result = $stmt->execute([
        'contract_id' => $contractId,
        'signer_id' => $signerId,
        'signer_role' => $role,
        'signature_blob' => $signatureBlob,
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
    $sql = "SELECT signature_blob, public_key, doc_hash, signed_at 
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
    $file_path = $this->resolvePath($contract['file_path'] ?? '');
     
    if (!$contract || !file_exists($file_path)) {
        return [
            'valid' => false, 
            'warning' => 'Original contract file is missing.', 
            'tampered' => true,
            'path' => $file_path
        ];
    }
    
    $currentHash = $this->generateHash($file_path);
    
    // 1. Check if the file content hash matches
    if ($currentHash !== $signature['doc_hash']) {
        return [
            'valid' => false,
            'warning' => 'DOCUMENT TAMPERED: Content does not match signed version.',
            'tampered' => true,
            'original_hash' => $signature['doc_hash'],
            'current_hash' => $currentHash
        ];
    }
    
    // 2. Cryptographically verify the signature using signature_blob from database
    $signatureBlob = base64_decode($signature['signature_blob']);
    $publicKey = openssl_pkey_get_public($signature['public_key']);
    
    if ($publicKey === false) {
        return ['valid' => false, 'warning' => 'Invalid public key.', 'tampered' => true];
    }
    
    $result = openssl_verify($signature['doc_hash'], $signatureBlob, $publicKey, OPENSSL_ALGO_SHA256);
    
    if ($result === 1) {
        return [
            'valid' => true,
            'signed_at' => $signature['signed_at'],
            'message' => 'Document and signature are authentic.'
        ];
    } elseif ($result === 0) {
        return [
            'valid' => false,
            'warning' => 'Cryptographic verification failed: Signature does not match.',
            'tampered' => true
        ];
    } else {
        return [
            'valid' => false,
            'warning' => 'Cryptographic verification error: ' . openssl_error_string(),
            'tampered' => true
        ];
    }
}

  public function getSignerChain($contractId)
    {
        $sql = "SELECT id, signer_id, signer_role, doc_hash, signed_at 
                FROM doc_signatures 
                WHERE contract_id = ? 
                ORDER BY signed_at ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$contractId]);
        $signatures = $stmt->fetchAll();
        
        $sequence = 1;
        foreach ($signatures as &$signature) {
            $signature['sequence'] = $sequence++;
        }
        
        return $signatures;
    }

    public function getAllSignatures($contractId)
    {
        $sql = "SELECT id, signer_id, signer_role, doc_hash, signed_at
                FROM doc_signatures
                WHERE contract_id = ?
                ORDER BY signed_at ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$contractId]);

        return $stmt->fetchAll();
    }

    private function resolvePath($path)
    {
        $path = str_replace('\\', '/', (string) $path);
        if ($path === '') {
            return '';
        }

        return preg_match('/^[A-Za-z]:\//', $path) || str_starts_with($path, '/')
            ? $path
            : dirname(__DIR__) . '/' . ltrim($path, '/');
    }

    private function signerRole($signerId)
    {
        return stripos((string) $signerId, 'client') !== false ? 'client' : 'company_rep';
    }
}
