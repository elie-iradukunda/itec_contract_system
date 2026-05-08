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
        // Prepare editor title, saved text, and document file details.
        $contract = $this->safeFind($id);
        $folder = $this->contractFolder($id);
        $textFile = $folder . '/contract.txt';

        return [
            'id' => (int) $id,
            'title' => $contract['title'] ?? "Contract #{$id}",
            'signing_state' => $contract['signing_state'] ?? 'DRAFT',
            'content' => file_exists($textFile) ? file_get_contents($textFile) : '',
            'file_name' => 'contract.docx',
            'file_exists' => file_exists($folder . '/contract.docx')
        ];
    }

    public function saveEditorContent($id, $content)
    {
        // Save fallback text without corrupting the DOCX file.
        $folder = $this->ensureContractFolder($id);
        file_put_contents($folder . '/contract.txt', $content);

        if (!$this->isValidDocx($folder . '/contract.docx')) {
            copy($this->templatePath(), $folder . '/contract.docx');
        }

        return $folder . '/contract.docx';
    }

    public function saveEditorFile($id, $file)
    {
        // Save an uploaded .docx file from the browser.
        if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'docx') {
            throw new \Exception('Only .docx files can be saved');
        }

        $folder = $this->ensureContractFolder($id);
        if (!move_uploaded_file($file['tmp_name'], $folder . '/contract.docx')) {
            throw new \Exception('The contract file could not be saved');
        }

        return $folder . '/contract.docx';
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

    private function templatePath()
    {
        // Use the blank DOCX template for new editor files.
        return __DIR__ . '/../storage/templates/blank-contract.docx';
    }
}
