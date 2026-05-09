<?php

namespace Services;

use Models\Contract;
use Models\ContractVersion;

class ContractService
{
    private $contractModel;
    private $versionModel;

    public function __construct(Contract $contractModel, ContractVersion $versionModel = null)
    {
        $this->contractModel = $contractModel;
        $this->versionModel = $versionModel;
    }

    public function getAllContracts()
    {
        return $this->contractModel->findAll();
    }

    public function getContractById($id)
    {
        return $this->contractModel->find($id);
    }

    public function getEditorData($id)
    {
        print_r($id);
        return null;
        // Load contract data needed by the editor page.
        return $this->contractModel->getEditorData($id);
    }

    public function saveEditorContent($id, $content, $savedBy = null)
    {
        // Save editor content and create a version snapshot.
        $path = $this->contractModel->saveEditorContent($id, $content);

        return [
            'file_path' => $path,
            'version' => $this->versionModel ? $this->versionModel->create($id, $savedBy, $path) : null
        ];
    }

    public function saveEditorFile($id, $file, $savedBy = null)
    {
        // Save an uploaded DOCX file and create a version snapshot.
        $path = $this->contractModel->saveEditorFile($id, $file);

        return [
            'file_path' => $path,
            'version' => $this->versionModel ? $this->versionModel->create($id, $savedBy, $path) : null
        ];
    }

    public function downloadEditorFile($id)
    {
        // Stream the current DOCX contract file.
        $path = $this->contractModel->documentPath($id);
        if (!file_exists($path)) {
            http_response_code(404);
            echo 'Contract file not found';
            return;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="contract-' . (int) $id . '.docx"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
    }

}
