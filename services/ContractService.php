<?php

namespace Services;

use Models\Contract;

class ContractService
{
    private $contractModel;

    public function __construct(Contract $contractModel)
    {
        $this->contractModel = $contractModel;
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
        // Load contract data needed by the editor page.
        return $this->contractModel->getEditorData($id);
    }

    public function saveEditorContent($id, $content)
    {
        // Save fallback editor text to the contract storage folder.
        return $this->contractModel->saveEditorContent($id, $content);
    }

    public function saveEditorFile($id, $file)
    {
        // Save a browser-uploaded .docx file to the contract storage folder.
        return $this->contractModel->saveEditorFile($id, $file);
    }
}
