<?php

namespace Models;

use Core\Database;
use PDO;
use Services\DocumentGeneratorService;

class Contract
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findAll($filters = [])
    {
        $sql = "
            SELECT c.*, COALESCE(cl.company_name, c.client_name) AS company_name,
                   COALESCE(cl.name, c.client_name) AS client_name,
                   COALESCE(cl.email, c.client_email) AS client_email,
                   u.name AS created_by_name
            FROM contracts c
            LEFT JOIN clients cl ON cl.id = c.client_id
            LEFT JOIN users u ON u.id = c.created_by
            WHERE 1 = 1
        ";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND c.signing_state = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (c.title LIKE ? OR c.description LIKE ? OR cl.company_name LIKE ? OR cl.name LIKE ?)";
            $term = '%' . $filters['search'] . '%';
            array_push($params, $term, $term, $term, $term);
        }

        $sql .= " ORDER BY c.updated_at DESC, c.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($id)
    {
        $stmt = $this->db->prepare("
            SELECT c.*, COALESCE(cl.company_name, c.client_name) AS company_name,
                   COALESCE(cl.name, c.client_name) AS client_name,
                   COALESCE(cl.email, c.client_email) AS client_email,
                   u.name AS created_by_name
            FROM contracts c
            LEFT JOIN clients cl ON cl.id = c.client_id
            LEFT JOIN users u ON u.id = c.created_by
            WHERE c.id = ?
        ");
        $stmt->execute([(int) $id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createDraft(array $data)
    {
        $this->ensureDefaultParties();

        $stmt = $this->db->prepare("
            INSERT INTO contracts (client_id, client_name, client_email, title, document_type, description, signing_state, created_by)
            VALUES (?, ?, ?, ?, ?, ?, 'DRAFT', ?)
        ");
        $stmt->execute([
            (int) ($data['client_id'] ?? 1),
            $data['client_name'] ?? $data['company_name'] ?? 'Demo Client',
            $data['client_email'] ?? 'client@itec.local',
            trim($data['title'] ?? '') ?: 'Untitled Contract',
            $data['document_type'] ?? $data['type'] ?? 'Service Agreement',
            $data['description'] ?? null,
            (int) ($data['created_by'] ?? 1),
        ]);

        $id = (int) $this->db->lastInsertId();
        $this->saveEditorContent($id, $data['content'] ?? '<p></p>', false);

        return $this->find($id);
    }

    public function updateContract($id, array $data)
    {
        $stmt = $this->db->prepare("
            UPDATE contracts
            SET title = COALESCE(?, title),
                document_type = COALESCE(?, document_type),
                description = COALESCE(?, description),
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $data['title'] ?? null,
            $data['document_type'] ?? $data['type'] ?? null,
            $data['description'] ?? null,
            (int) $id,
        ]);

        return $this->find($id);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM contracts WHERE id = ?");
        return $stmt->execute([(int) $id]);
    }

    public function getEditorData($id)
    {
        $contract = $this->find($id);
        if (!$contract) {
            return null;
        }

        $folder = $this->contractFolder($id);
        $htmlFile = $folder . '/contract.html';
        $textFile = $folder . '/contract.txt';
        $docxFile = $this->absolutePath($contract['file_path'] ?: $this->relativeStoragePath($folder . '/contract.docx'));

        $contract['content'] = $this->loadEditorHtml($htmlFile, $textFile, $docxFile);
        $contract['file_path'] = $contract['file_path'] ?: $this->relativeStoragePath($docxFile);

        return $contract;
    }

    public function saveEditorContent($id, $content, $trackChange = true)
    {
        $this->ensureBodyEditable($id);

        $folder = $this->ensureContractFolder($id);
        $htmlFile = $folder . '/contract.html';
        $oldHtml = file_exists($htmlFile) ? file_get_contents($htmlFile) : '';
        $html = $this->cleanEditorHtml($content);

        file_put_contents($htmlFile, $html);
        file_put_contents($folder . '/contract.txt', $this->htmlToPlainText($html));

        $contract = $this->find($id);
        if (!$contract) {
            throw new \Exception('Contract not found');
        }

        $path = (new DocumentGeneratorService())->generateContract((int) $id, [
            'title' => $contract['title'] ?? 'Untitled Contract',
            'document_type' => $contract['document_type'] ?? 'Service Agreement',
            'description' => $contract['description'] ?? null,
            'client_name' => $contract['client_name'] ?? null,
            'client_email' => $contract['client_email'] ?? null,
            'content' => $html,
        ]);
        $this->updateContractFilePath($id, $path);

        if ($trackChange && trim($oldHtml) !== trim($html)) {
            $this->recordTrackedChange($id, $oldHtml, $html);
        }

        return $path;
    }

    public function saveEditorFile($id, $file)
    {
        $this->ensureBodyEditable($id);

        if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'docx') {
            throw new \Exception('Only .docx files can be saved');
        }

        $folder = $this->ensureContractFolder($id);
        $path = $folder . '/contract.docx';
        if (!move_uploaded_file($file['tmp_name'], $path)) {
            throw new \Exception('The contract file could not be saved');
        }

        $html = $this->docxToHtml($path);
        file_put_contents($folder . '/contract.html', $html);
        file_put_contents($folder . '/contract.txt', $this->htmlToPlainText($html));
        $this->updateContractFilePath($id, $path);
        $this->recordTrackedChange($id, '', $html);

        return $path;
    }

    public function documentPath($id)
    {
        return $this->contractFolder($id) . '/contract.docx';
    }

    public function absolutePath($path)
    {
        $path = str_replace('\\', '/', (string) $path);
        if ($path === '') {
            return '';
        }

        return preg_match('/^[A-Za-z]:\//', $path) || str_starts_with($path, '/')
            ? $path
            : dirname(__DIR__) . '/' . ltrim($path, '/');
    }

    private function ensureDefaultParties()
    {
        $this->db->exec("
            INSERT INTO users (id, name, email, password, role)
            VALUES (1, 'Demo Staff', 'staff@itec.local', '', 'staff')
            ON DUPLICATE KEY UPDATE name = VALUES(name)
        ");
        $this->db->exec("
            INSERT INTO clients (id, name, email, company_name)
            VALUES (1, 'Demo Client', 'client@itec.local', 'Demo Client Company')
            ON DUPLICATE KEY UPDATE name = VALUES(name), email = VALUES(email)
        ");
    }

    private function recordTrackedChange($id, $oldHtml, $newHtml)
    {
        // Feature E3: every save batch creates a reviewer-visible tracked change row.
        $oldText = $this->htmlToPlainText($oldHtml);
        $newText = $this->htmlToPlainText($newHtml);

        if ($oldText === $newText) {
            return;
        }

        $stmt = $this->db->prepare("
            INSERT INTO doc_tracked_changes (contract_id, doc_id, author_id, original_text, new_text, status, changed_at)
            VALUES (?, ?, ?, ?, ?, 'pending', NOW())
        ");
        $stmt->execute([(int) $id, (int) $id, 1, $oldText, $newText]);
    }

    private function ensureBodyEditable($id)
    {
        // Feature E5: backend body lock mirrors the editor read-only state after signing starts.
        $stmt = $this->db->prepare("SELECT signing_state FROM contracts WHERE id = ?");
        $stmt->execute([(int) $id]);
        $state = strtoupper((string) $stmt->fetchColumn());

        if ($state && $state !== 'DRAFT') {
            throw new \Exception('The contract body is locked after signing starts.');
        }
    }

    private function contractFolder($id)
    {
        return dirname(__DIR__) . '/storage/contracts/' . (int) $id;
    }

    private function ensureContractFolder($id)
    {
        $folder = $this->contractFolder($id);
        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }

        return $folder;
    }

    private function isValidDocx($path)
    {
        return file_exists($path) && file_get_contents($path, false, null, 0, 2) === 'PK';
    }

    private function loadEditorHtml($htmlFile, $textFile, $docxFile)
    {
        if (file_exists($htmlFile)) {
            return file_get_contents($htmlFile);
        }

        if (file_exists($textFile)) {
            return $this->plainTextToHtml(file_get_contents($textFile));
        }

        return $this->docxToHtml($docxFile);
    }

    private function cleanEditorHtml($content)
    {
        $allowed = '<p><br><strong><b><em><i><u><s><span><div><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><a><table><thead><tbody><tfoot><tr><td><th><pre><code><sup><sub><hr><img>';
        $html = strip_tags((string) $content, $allowed);
        $html = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
        $html = preg_replace('/javascript\s*:/i', '', $html);

        return trim($html) !== '' ? trim($html) : '<p></p>';
    }

    private function docxToHtml($path)
    {
        if (!$this->isValidDocx($path)) {
            return '<p></p>';
        }

        $xml = $this->zipEntry($path, 'word/document.xml');
        if ($xml === null) {
            return '<p></p>';
        }

        $xml = preg_replace('/<w:tab\s*\/>/i', "\t", $xml);
        $xml = preg_replace('/<\/w:p>/i', "\n", $xml);
        $text = html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');

        return $this->plainTextToHtml($text);
    }

    private function writeDocx($path, $html)
    {
        $this->writeZip($path, [
            '[Content_Types].xml' => $this->contentTypesXml(),
            '_rels/.rels' => $this->rootRelsXml(),
            'word/document.xml' => $this->documentXml($this->htmlToPlainText($html))
        ]);
    }

    private function documentXml($text)
    {
        $paragraphs = preg_split('/\R/', trim($text));
        if (!$paragraphs || $paragraphs === ['']) {
            $paragraphs = [''];
        }

        $body = '';
        foreach ($paragraphs as $paragraph) {
            $body .= '<w:p><w:r><w:t xml:space="preserve">' . htmlspecialchars($paragraph, ENT_QUOTES | ENT_XML1, 'UTF-8') . '</w:t></w:r></w:p>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            . '<w:body>' . $body
            . '<w:sectPr><w:pgSz w:w="12240" w:h="15840"/><w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440"/></w:sectPr>'
            . '</w:body></w:document>';
    }

    private function htmlToPlainText($html)
    {
        $html = preg_replace('/<\/(td|th)>/i', "\t", (string) $html);
        $html = preg_replace('/<(\/p|br|hr|\/div|\/li|\/h[1-6]|\/blockquote|\/tr)>/i', "\n", $html);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/[ \t]+\n/", "\n", $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text);

        return trim($text);
    }

    private function plainTextToHtml($text)
    {
        $lines = preg_split('/\R{2,}/', trim((string) $text));
        if (!$lines || $lines === ['']) {
            return '<p></p>';
        }

        return implode('', array_map(function ($line) {
            return '<p>' . nl2br(htmlspecialchars(trim($line), ENT_QUOTES, 'UTF-8')) . '</p>';
        }, $lines));
    }

    private function updateContractFilePath($id, $path)
    {
        $stmt = $this->db->prepare("UPDATE contracts SET file_path = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$this->relativeStoragePath($path), (int) $id]);
    }

    private function relativeStoragePath($path)
    {
        return 'storage/contracts/' . basename(dirname($path)) . '/' . basename($path);
    }

    private function contentTypesXml()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            . '</Types>';
    }

    private function rootRelsXml()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            . '</Relationships>';
    }

    private function writeZip($path, array $files)
    {
        $localData = '';
        $centralData = '';
        [$dosTime, $dosDate] = $this->dosTimestamp();

        foreach ($files as $name => $contents) {
            $offset = strlen($localData);
            $size = strlen($contents);
            $crc = hexdec(hash('crc32b', $contents));
            $nameLength = strlen($name);

            $localData .= pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 0, $dosTime, $dosDate, $crc, $size, $size, $nameLength, 0);
            $localData .= $name . $contents;

            $centralData .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, 0, $dosTime, $dosDate, $crc, $size, $size, $nameLength, 0, 0, 0, 0, 0, $offset);
            $centralData .= $name;
        }

        $entryCount = count($files);
        $zip = $localData . $centralData . pack('VvvvvVVv', 0x06054b50, 0, 0, $entryCount, $entryCount, strlen($centralData), strlen($localData), 0);

        if (file_put_contents($path, $zip) === false) {
            throw new \Exception('The DOCX file could not be created');
        }
    }

    private function zipEntry($path, $entryName)
    {
        $data = file_get_contents($path);
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

    private function dosTimestamp()
    {
        $now = getdate();
        $time = ($now['hours'] << 11) | ($now['minutes'] << 5) | (int) ($now['seconds'] / 2);
        $date = (($now['year'] - 1980) << 9) | ($now['mon'] << 5) | $now['mday'];

        return [$time, $date];
    }
}
