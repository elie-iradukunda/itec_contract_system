<?php

namespace Models;

use Core\Database;
use PDO;

class Contract
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findAll()
    {
        $stmt = $this->db->query("SELECT * FROM contracts");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM contracts WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getEditorData($id)
    {
        // Prepare editor content and document file details.
        $contract = $this->safeFind($id);
        $folder = $this->ensureContractFolder($id);
        $htmlFile = $folder . '/contract.html';
        $textFile = $folder . '/contract.txt';
        $docxFile = $folder . '/contract.docx';

        return [
            'id' => (int) $id,
            'title' => $contract['title'] ?? "Contract #{$id}",
            'signing_state' => $contract['signing_state'] ?? 'DRAFT',
            'content' => $this->loadEditorHtml($htmlFile, $textFile, $docxFile),
            'file_name' => 'contract.docx',
            'file_exists' => file_exists($docxFile)
        ];
    }

    public function saveEditorContent($id, $content)
    {
        // Save CKEditor content and rebuild the current DOCX file.
        $folder = $this->ensureContractFolder($id);
        $html = $this->cleanEditorHtml($content);
        file_put_contents($folder . '/contract.html', $html);
        file_put_contents($folder . '/contract.txt', $this->htmlToPlainText($html));

        $path = $folder . '/contract.docx';
        $this->writeDocx($path, $html);
        $this->updateContractFilePath($id, $path);

        return $path;
    }

    public function saveEditorFile($id, $file)
    {
        // Save an uploaded .docx file from the browser.
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

        return $path;
    }

    public function documentPath($id)
    {
        // Return the current DOCX path for downloads and version snapshots.
        return $this->contractFolder($id) . '/contract.docx';
    }

    private function safeFind($id)
    {
        // Avoid editor failure when the database row is not ready yet.
        try {
            return $this->find($id) ?: [];
        } catch (\Throwable $error) {
            return [];
        }
    }

    private function contractFolder($id)
    {
        // Build the contract storage folder path.
        return __DIR__ . '/../storage/contracts/' . (int) $id;
    }

    private function ensureContractFolder($id)
    {
        // Create the contract storage folder when missing.
        $folder = $this->contractFolder($id);

        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }

        return $folder;
    }

    private function isValidDocx($path)
    {
        // Check for the ZIP header used by real DOCX files.
        return file_exists($path) && file_get_contents($path, false, null, 0, 2) === 'PK';
    }

    private function loadEditorHtml($htmlFile, $textFile, $docxFile)
    {
        // Load the best available source for CKEditor.
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
        // Keep only the formatting CKEditor is configured to produce.
        $allowed = '<p><br><strong><b><em><i><u><s><span><div><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><a><table><thead><tbody><tfoot><tr><td><th><pre><code><sup><sub><hr><img>';
        $html = strip_tags((string) $content, $allowed);
        $html = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
        $html = preg_replace('/javascript\s*:/i', '', $html);

        return trim($html) !== '' ? trim($html) : '<p></p>';
    }

    private function docxToHtml($path)
    {
        // Extract readable text from a DOCX file for CKEditor.
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
        // Write a lightweight DOCX package from the editor text.
        $this->writeZip($path, [
            '[Content_Types].xml' => $this->contentTypesXml(),
            '_rels/.rels' => $this->rootRelsXml(),
            'word/document.xml' => $this->documentXml($this->htmlToPlainText($html))
        ]);
    }

    private function documentXml($text)
    {
        // Convert plain text lines into Word paragraphs.
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
        // Flatten editor HTML into text for TXT and DOCX storage.
        $html = preg_replace('/<\/(td|th)>/i', "\t", (string) $html);
        $html = preg_replace('/<(\/p|br|hr|\/div|\/li|\/h[1-6]|\/blockquote|\/tr)>/i', "\n", $html);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/[ \t]+\n/", "\n", $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text);

        return trim($text);
    }

    private function plainTextToHtml($text)
    {
        // Convert stored plain text into editor paragraphs.
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
        // Keep the contract row pointed at the current file when it exists.
        try {
            $stmt = $this->db->prepare("UPDATE contracts SET file_path = ? WHERE id = ?");
            $stmt->execute([$this->relativeStoragePath($path), (int) $id]);
        } catch (\Throwable $error) {
        }
    }

    private function relativeStoragePath($path)
    {
        // Store portable paths in the database.
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
        // Create a minimal ZIP package without requiring PHP ZipArchive.
        $localData = '';
        $centralData = '';
        [$dosTime, $dosDate] = $this->dosTimestamp();

        foreach ($files as $name => $contents) {
            $offset = strlen($localData);
            $nameLength = strlen($name);
            $size = strlen($contents);
            $crc = hexdec(hash('crc32b', $contents));

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
        // Read one file from a ZIP package using the central directory.
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
        // Decode stored or deflated ZIP file contents.
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
        // Convert current time to the ZIP date/time fields.
        $now = getdate();
        $time = ($now['hours'] << 11) | ($now['minutes'] << 5) | (int) ($now['seconds'] / 2);
        $date = (($now['year'] - 1980) << 9) | ($now['mon'] << 5) | $now['mday'];

        return [$time, $date];
    }
}
