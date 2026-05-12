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

        $this->snapshotEditorSidecars($contractId, $targetPath);

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
            SELECT dv.id, dv.contract_id, dv.version_no, dv.saved_by, dv.saved_at, dv.file_path, u.name AS saved_by_name
            FROM doc_versions dv
            LEFT JOIN users u ON u.id = dv.saved_by
            WHERE dv.contract_id = ? AND dv.version_no = ?
            LIMIT 1
        ");
        $stmt->execute([(int) $contractId, (int) $versionNo]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function preview($contractId, $versionNo)
    {
        $version = $this->findByVersion($contractId, $versionNo);
        if (!$version) {
            throw new \Exception('The requested version was not found');
        }

        $path = $this->absolutePath($version['file_path']);
        if (!is_file($path)) {
            throw new \Exception('The requested version file is missing');
        }

        $contract = $this->contractSummary($contractId);
        if (!$contract) {
            throw new \Exception('Contract not found');
        }

        $lines = $this->docxTextLines($path);
        $bodyLines = $this->agreementBodyLines($lines);

        return [
            'version' => [
                'version_no' => (int) $version['version_no'],
                'saved_at' => $version['saved_at'],
                'saved_by_name' => $version['saved_by_name'] ?: ($version['saved_by'] ? 'User #' . $version['saved_by'] : 'Unknown user'),
            ],
            'document' => [
                'title' => $contract['title'] ?? ('Contract #' . (int) $contractId),
                'contract_ref' => '#' . (int) $contractId,
                'client_name' => $contract['client_name'] ?: '',
                'client_email' => $contract['client_email'] ?: '',
                'document_date' => $version['saved_at'] ? date('F d, Y', strtotime($version['saved_at'])) : date('F d, Y'),
                'body' => $this->bodyPreviewBlocks($bodyLines),
                'signature' => [
                    'client_name' => $contract['client_name'] ?: 'Client Representative',
                    'company_name' => 'ITEC Solutions',
                    'company_signer' => 'Authorized Signatory',
                ],
            ],
        ];
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

        $this->restoreEditorSidecars($sourcePath, $folder);

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
        $path = str_replace('\\', '/', (string) $relativePath);
        if ($path === '') {
            return '';
        }

        return preg_match('/^[A-Za-z]:\//', $path) || str_starts_with($path, '/')
            ? $path
            : dirname(__DIR__) . '/' . ltrim($path, '/');
    }

    private function contractFolder($contractId)
    {
        // Build the contract storage folder path.
        return dirname(__DIR__) . '/storage/contracts/' . (int) $contractId;
    }

    private function snapshotEditorSidecars($contractId, $targetDocxPath)
    {
        $folder = $this->contractFolder($contractId);
        $targetBase = preg_replace('/\.docx$/i', '', $targetDocxPath);

        foreach (['html', 'txt'] as $extension) {
            $source = $folder . '/contract.' . $extension;
            if (is_file($source)) {
                copy($source, $targetBase . '.' . $extension);
            }
        }
    }

    private function restoreEditorSidecars($sourceDocxPath, $contractFolder)
    {
        $sourceBase = preg_replace('/\.docx$/i', '', $sourceDocxPath);

        foreach (['html', 'txt'] as $extension) {
            $source = $sourceBase . '.' . $extension;
            $target = $contractFolder . '/contract.' . $extension;

            if (is_file($source)) {
                copy($source, $target);
            } else {
                @unlink($target);
            }
        }
    }

    private function contractSummary($contractId)
    {
        $stmt = $this->db->prepare("
            SELECT c.id, c.title,
                   COALESCE(NULLIF(c.client_name, ''), cl.company_name, cl.name, '') AS client_name,
                   COALESCE(NULLIF(c.client_email, ''), cl.email, '') AS client_email
            FROM contracts c
            LEFT JOIN clients cl ON cl.id = c.client_id
            WHERE c.id = ?
            LIMIT 1
        ");
        $stmt->execute([(int) $contractId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function docxTextLines($path)
    {
        $xml = $this->docxEntry($path, 'word/document.xml');
        if ($xml === null) {
            return [];
        }

        $xml = preg_replace('/<w:tab\s*\/>/i', "\t", $xml);
        $xml = preg_replace('/<w:br\s*\/>/i', "\n", $xml);
        $xml = preg_replace('/<\/w:tc>/i', "\t", $xml);
        $xml = preg_replace('/<\/w:tr>/i', "\n", $xml);
        $xml = preg_replace('/<\/w:p>/i', "\n", $xml);

        $text = html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');
        $text = preg_replace('/[ \x{00A0}]+/u', ' ', $text);
        $text = preg_replace("/\t+/", "\t", $text);
        $text = preg_replace("/\n{2,}/", "\n", $text);

        return array_values(array_filter(array_map(function ($line) {
            return trim(preg_replace('/\s+/', ' ', str_replace("\t", ' ', (string) $line)));
        }, preg_split('/\R/', $text)), function ($line) {
            return $line !== '';
        }));
    }

    private function agreementBodyLines(array $lines)
    {
        if (!$lines) {
            return [];
        }

        $start = -1;
        $end = count($lines);

        foreach ($lines as $index => $line) {
            if (strcasecmp($line, 'Agreement Details') === 0) {
                $start = $index + 1;
                continue;
            }

            if ($start >= 0 && preg_match('/^Signatures$/i', $line)) {
                $end = $index;
                break;
            }
        }

        if ($start < 0) {
            return $this->stripGeneratedChrome($lines);
        }

        return array_values(array_filter(array_slice($lines, $start, max(0, $end - $start))));
    }

    private function stripGeneratedChrome(array $lines)
    {
        return array_values(array_filter($lines, function ($line) {
            return !preg_match('/^(Contract Ref|Client|Date|Email|Signatures|Client Signature|ITEC Solutions|Authorized Signatory|Page \d+)/i', $line);
        }));
    }

    private function bodyPreviewBlocks(array $lines)
    {
        if (!$lines) {
            return [[
                'type' => 'muted',
                'text' => 'No agreement body was found in this saved DOCX version.',
            ]];
        }

        return array_map(function ($line) {
            $text = trim((string) $line);
            $type = 'paragraph';

            if (preg_match('/^\d+(\.\d+)*\.?\s+\S/', $text)) {
                $type = 'heading';
            } elseif (preg_match('/^(-|No\.\s*\d+:|\[[x ]\])\s+/i', $text)) {
                $type = 'list';
            }

            return [
                'type' => $type,
                'text' => $text,
            ];
        }, $lines);
    }

    private function docxEntry($path, $entryName)
    {
        if (class_exists('\ZipArchive')) {
            $zip = new \ZipArchive();
            if ($zip->open($path) === true) {
                $content = $zip->getFromName($entryName);
                $zip->close();

                return $content === false ? null : $content;
            }
        }

        return $this->zipEntry($path, $entryName);
    }

    private function zipEntry($path, $entryName)
    {
        $data = @file_get_contents($path);
        if ($data === false) {
            return null;
        }

        $eocdOffset = strrpos($data, "PK\x05\x06");
        if ($eocdOffset === false) {
            return null;
        }

        $eocd = unpack('vdisk/vdiskStart/ventriesDisk/ventries/Vsize/Voffset/vcomment', substr($data, $eocdOffset + 4, 18));
        $offset = $eocd['offset'];
        $end = $offset + $eocd['size'];

        while ($offset < $end && substr($data, $offset, 4) === "PK\x01\x02") {
            $header = unpack('vverMade/vverNeed/vflag/vmethod/vtime/vdate/Vcrc/Vcompressed/Vuncompressed/vnameLen/vextraLen/vcommentLen/vdisk/vinternal/Vexternal/VlocalOffset', substr($data, $offset + 4, 42));
            $name = substr($data, $offset + 46, $header['nameLen']);

            if ($name === $entryName) {
                return $this->zipLocalEntry($data, $header);
            }

            $offset += 46 + $header['nameLen'] + $header['extraLen'] + $header['commentLen'];
        }

        return null;
    }

    private function zipLocalEntry($data, array $header)
    {
        $offset = $header['localOffset'];
        if (substr($data, $offset, 4) !== "PK\x03\x04") {
            return null;
        }

        $local = unpack('vverNeed/vflag/vmethod/vtime/vdate/Vcrc/Vcompressed/Vuncompressed/vnameLen/vextraLen', substr($data, $offset + 4, 26));
        $start = $offset + 30 + $local['nameLen'] + $local['extraLen'];
        $raw = substr($data, $start, $header['compressed']);

        if ($header['method'] === 0) {
            return $raw;
        }

        if ($header['method'] === 8 && function_exists('gzinflate')) {
            $inflated = @gzinflate($raw);
            return $inflated === false ? null : $inflated;
        }

        return null;
    }
}
