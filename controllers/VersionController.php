<?php

namespace Controllers;

use Core\Controller;

class VersionController extends Controller
{
    private $versionModel;

    public function __construct($versionModel)
    {
        parent::__construct();
        $this->versionModel = $versionModel;
    }

    public function index($id)
    {
        // Return version history for the editor side panel.
        try {
            $this->json([
                'success' => true,
                'versions' => $this->versionModel->findByContract((int) $id)
            ]);
        } catch (\Throwable $error) {
            $this->json(['success' => false, 'message' => $error->getMessage()], 500);
        }
    }

    public function restore($id, $version)
    {
        // Restore one version and snapshot the restored state.
        try {
            $restored = $this->versionModel->restore((int) $id, (int) $version, $this->currentUserId());
            $this->json([
                'success' => true,
                'message' => 'Version restored successfully',
                'version' => $restored
            ]);
        } catch (\Throwable $error) {
            $this->json(['success' => false, 'message' => $error->getMessage()], 500);
        }
    }

    public function download($id, $version)
    {
        // Download one saved version file.
        $this->versionModel->streamDownload((int) $id, (int) $version);
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
