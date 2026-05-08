<?php

namespace Controllers;

use Core\Controller;

class ContractController extends Controller
{
    private $contractService;
    private $auditService;
    private $onlyOfficeService;

    public function __construct($contractService = null, $auditService = null, $onlyOfficeService = null)
    {
        parent::__construct();
        $this->contractService = $contractService;
        $this->auditService = $auditService;
        $this->onlyOfficeService = $onlyOfficeService;
    }

    public function index()
    {
        $this->view('contracts/index', [
            'title' => 'Contracts'
        ]);
    }

    public function show($id)
    {
        $this->json([
            'success' => true,
            'contract_id' => $id,
            'message' => 'Contract found'
        ]);
    }

    public function edit($id)
    {
        // Open the in-browser editor page.
        $contract = $this->contractService->getEditorData((int) $id);
        $contract['onlyoffice'] = $this->onlyOfficeService->config((int) $id, $contract, $this->baseUrl());
        $this->view('contracts/editor', ['contract' => $contract]);
    }

    public function saveDocument($id)
    {
        // Save uploaded .docx file or fallback editor text.
        try {
            $file = $_FILES['contract_file'] ?? null;

            if ($file && $file['error'] === UPLOAD_ERR_OK) {
                $this->contractService->saveEditorFile((int) $id, $file);
            } else {
                $content = $_POST['content'] ?? '';
                $this->contractService->saveEditorContent((int) $id, $content);
            }

            $this->json(['success' => true, 'message' => 'Contract saved successfully']);
        } catch (\Throwable $error) {
            $this->json(['success' => false, 'message' => $error->getMessage()], 500);
        }
    }

    public function getStatus($id)
    {
        // Return the contract state used by the editor polling UI.
        $contract = $this->contractService->getEditorData((int) $id);

        $this->json([
            'success' => true,
            'signing_state' => $contract['signing_state'] ?? 'DRAFT'
        ]);
    }

    public function downloadDocument($id)
    {
        // Stream the DOCX file to ONLYOFFICE Document Server.
        $this->onlyOfficeService->download((int) $id);
    }

    public function onlyOfficeCallback($id)
    {
        // Accept ONLYOFFICE save callbacks and return its expected JSON.
        $payload = json_decode(file_get_contents('php://input'), true) ?: [];
        $this->json($this->onlyOfficeService->callback((int) $id, $payload));
    }

    public function forceOnlyOfficeSave($id)
    {
        // Ask ONLYOFFICE to force-save the current editor session.
        $key = $_POST['key'] ?? '';
        $result = $this->onlyOfficeService->forceSave((int) $id, $key);
        $this->json($result, $result['success'] ? 200 : 500);
    }

    private function baseUrl()
    {
        // Build the public base URL used by ONLYOFFICE callbacks.
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return getenv('APP_URL') ?: $scheme . '://' . $_SERVER['HTTP_HOST'] . '/itec_contract_system';
    }
}
