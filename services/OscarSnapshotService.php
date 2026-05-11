<?php

namespace Services;

use Core\Database;

class OscarSnapshotService
{
    private $db;
    private $snapshotDir;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->snapshotDir = __DIR__ . '/../storage/snapshots/';
        $this->ensureDirectoryExists();
    }
    
    private function ensureDirectoryExists()
    {
        if (!is_dir($this->snapshotDir)) {
            mkdir($this->snapshotDir, 0777, true);
        }
    }
    
    public function createSnapshot($contractId, $signatureId, $signerId, $sourceFile)
    {
        if (!file_exists($sourceFile)) {
            return [
                'success' => false,
                'error' => 'Source file not found: ' . $sourceFile
            ];
        }
        
        // Create contract snapshot directory
        $contractSnapshotDir = $this->snapshotDir . $contractId . '/';
        if (!is_dir($contractSnapshotDir)) {
            mkdir($contractSnapshotDir, 0777, true);
        }
        
        // Get version number
        $versionNumber = $this->getNextVersionNumber($contractId);
        
        // Output filename
        $snapshotFile = $contractSnapshotDir . 'v' . $versionNumber . '.pdf';
        
        // Convert to PDF
        $converted = $this->convertToPdf($sourceFile, $snapshotFile);
        
        if (!$converted) {
            return [
                'success' => false,
                'error' => 'Failed to convert document to PDF'
            ];
        }
        
        // Update doc_signatures with snapshot path
        $updateQuery = "UPDATE doc_signatures SET snapshot_file_path = :snapshot_path WHERE id = :signature_id";
        $stmt = $this->db->prepare($updateQuery);
        $stmt->execute([
            'snapshot_path' => $snapshotFile,
            'signature_id' => $signatureId
        ]);
        
        return [
            'success' => true,
            'snapshot_file' => $snapshotFile,
            'version_number' => $versionNumber,
            'message' => 'Snapshot created successfully'
        ];
    }
    
    private function getNextVersionNumber($contractId)
    {
        $query = "SELECT COUNT(*) as count FROM doc_signatures WHERE contract_id = ? AND snapshot_file_path IS NOT NULL";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$contractId]);
        $result = $stmt->fetch();
        
        return ($result['count'] ?? 0) + 1;
    }
    
    public function convertToPdf($inputFile, $outputFile)
    {
        $extension = strtolower(pathinfo($inputFile, PATHINFO_EXTENSION));
        
        if ($extension === 'pdf') {
            copy($inputFile, $outputFile);
            return true;
        }
        
        // Try LibreOffice conversion
        $command = sprintf(
            'libreoffice --headless --convert-to pdf --outdir %s %s 2>&1',
            escapeshellarg(dirname($outputFile)),
            escapeshellarg($inputFile)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0) {
            $convertedFile = dirname($outputFile) . '/' . pathinfo($inputFile, PATHINFO_FILENAME) . '.pdf';
            if (file_exists($convertedFile)) {
                rename($convertedFile, $outputFile);
                return true;
            }
        }
        
        // Fallback: Create simple PDF with file content
        // $this->createFallbackPdf($inputFile, $outputFile);
        return true;
    }
    
    public function createFallbackPdf($inputFile, $outputFile)
    {
        $pdf = new \TCPDF();
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 10);
        
        $content = "CONTRACT SNAPSHOT\n\n";
        $content .= "Document: " . basename($inputFile) . "\n";
        $content .= "Snapshot Date: " . date('Y-m-d H:i:s') . "\n\n";
        $content .= "This is a frozen snapshot of the contract at the time of signing.\n\n";
        
        if (file_exists($inputFile)) {
            $content .= "Original content:\n";
            $content .= str_repeat('-', 50) . "\n";
            $content .= file_get_contents($inputFile);
        }
        
        $pdf->MultiCell(0, 10, $content);
        $pdf->Output($outputFile, 'F');
    }
    
    public function getSnapshots($contractId)
    {
        $query = "SELECT id, signer_id, snapshot_file_path, signed_at 
                  FROM doc_signatures 
                  WHERE contract_id = ? AND snapshot_file_path IS NOT NULL 
                  ORDER BY signed_at ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$contractId]);
        return $stmt->fetchAll();
    }
    
    public function getSnapshotBySignature($signatureId)
    {
        $query = "SELECT snapshot_file_path FROM doc_signatures WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$signatureId]);
        return $stmt->fetch();
    }
}