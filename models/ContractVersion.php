<?php

namespace Models;

use Core\Database;
use PDO;

class ContractVersion
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findByContract($contractId)
    {
        // List saved versions for the editor history panel.
        $stmt = $this->db->prepare("
            SELECT dv.id, dv.contract_id, dv.version_no, dv.saved_by, dv.saved_at, dv.file_path, u.name AS saved_by_name
            FROM doc_versions dv
            LEFT JOIN users u ON u.id = dv.saved_by
            WHERE dv.contract_id = ?
            ORDER BY dv.version_no DESC
        ");
        $stmt->execute([(int) $contractId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($contractId, $savedBy, $sourcePath)
    {
        // Snapshot the current DOCX file into the next version slot.
        if (!file_exists($sourcePath)) {
            throw new \Exception('The current contract file does not exist');
        }

        $versionNo = $this->nextVersionNo($contractId);
        $relativePath = "storage/contracts/" . (int) $contractId . "/v{$versionNo}.docx";
        $targetPath = $this->absolutePath($relativePath);
        $folder = dirname($targetPath);

        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }

        if (!copy($sourcePath, $targetPath)) {
            throw new \Exception('The contract version could not be saved');
        }

        $stmt = $this->db->prepare("
            INSERT INTO doc_versions (contract_id, version_no, saved_by, saved_at, file_path)
            VALUES (?, ?, ?, NOW(), ?)
        ");
        $stmt->execute([(int) $contractId, $versionNo, $savedBy ?: null, $relativePath]);

        return $this->findByVersion($contractId, $versionNo);
    }

    public function findByVersion($contractId, $versionNo)
    {
        // Load one version by its public version number.
        $stmt = $this->db->prepare("
            SELECT id, contract_id, version_no, saved_by, saved_at, file_path
            FROM doc_versions
            WHERE contract_id = ? AND version_no = ?
            LIMIT 1
        ");
        $stmt->execute([(int) $contractId, (int) $versionNo]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function restore($contractId, $versionNo, $savedBy)
    {
        // Copy the chosen version back to the current contract file.
        $version = $this->findByVersion($contractId, $versionNo);
        if (!$version) {
            throw new \Exception('The requested version was not found');
        }

        $sourcePath = $this->absolutePath($version['file_path']);
        if (!file_exists($sourcePath)) {
            throw new \Exception('The requested version file is missing');
        }

        $folder = $this->contractFolder($contractId);
        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }

        $currentPath = $folder . '/contract.docx';
        if (!copy($sourcePath, $currentPath)) {
            throw new \Exception('The contract version could not be restored');
        }

        @unlink($folder . '/contract.html');
        @unlink($folder . '/contract.txt');

        return $this->create($contractId, $savedBy, $currentPath);
    }

    public function streamDownload($contractId, $versionNo)
    {
        // Stream a versioned DOCX to the browser.
        $version = $this->findByVersion($contractId, $versionNo);
        if (!$version) {
            http_response_code(404);
            echo 'Version not found';
            return;
        }

        $path = $this->absolutePath($version['file_path']);
        if (!file_exists($path)) {
            http_response_code(404);
            echo 'Version file not found';
            return;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="contract-' . (int) $contractId . '-v' . (int) $versionNo . '.docx"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
    }

    private function nextVersionNo($contractId)
    {
        // Calculate the next version number for this contract.
        $stmt = $this->db->prepare("SELECT COALESCE(MAX(version_no), 0) + 1 FROM doc_versions WHERE contract_id = ?");
        $stmt->execute([(int) $contractId]);

        return (int) $stmt->fetchColumn();
    }

    private function absolutePath($relativePath)
    {
        // Resolve storage paths from the project root.
        return dirname(__DIR__) . '/' . ltrim(str_replace('\\', '/', $relativePath), '/');
    }

    private function contractFolder($contractId)
    {
        // Build the contract storage folder path.
        return dirname(__DIR__) . '/storage/contracts/' . (int) $contractId;
    }
}
