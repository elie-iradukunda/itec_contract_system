<?php

namespace Controllers;

use Core\Controller;

class ContractController extends Controller
{
    private $contractService;
    private $auditService;

    public function __construct($contractService = null, $auditService = null)
    {
        parent::__construct();
        $this->contractService = $contractService;
        $this->auditService = $auditService;
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
        $this->view('contracts/editor', ['contract' => $contract]);
    }

    public function saveDocument($id)
    {
        // Save CKEditor content or an uploaded DOCX and create a version.
        try {
            $file = $_FILES['contract_file'] ?? null;
            $savedBy = $this->currentUserId();

            if ($file && $file['error'] === UPLOAD_ERR_OK) {
                $result = $this->contractService->saveEditorFile((int) $id, $file, $savedBy);
            } else {
                $content = $_POST['content'] ?? '';
                $result = $this->contractService->saveEditorContent((int) $id, $content, $savedBy);
            }

            $this->json([
                'success' => true,
                'message' => 'Contract saved successfully',
                'version' => $result['version'] ?? null
            ]);
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
        // Stream the current browser-edited DOCX file.
        $this->contractService->downloadEditorFile((int) $id);
    }

    private function currentUserId()
    {
        // Read the active user id when auth is available.
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        return $_SESSION['user_id'] ?? null;
    }
}
