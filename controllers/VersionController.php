<?php

namespace Controllers;

use Core\Controller;
use Core\Database;

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
            $this->assertDraft((int) $id);
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

    public function compare($id, $v1, $v2)
    {
        // Lightweight comparison endpoint for the editor side panel.
        try {
            $first = $this->versionModel->findByVersion((int) $id, (int) $v1);
            $second = $this->versionModel->findByVersion((int) $id, (int) $v2);

            $this->json([
                'success' => (bool) ($first && $second),
                'versions' => ['from' => $first, 'to' => $second],
            ], ($first && $second) ? 200 : 404);
        } catch (\Throwable $error) {
            $this->json(['success' => false, 'message' => $error->getMessage()], 500);
        }
    }

    private function currentUserId()
    {
        // Read the active user id when auth is available.
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        return $_SESSION['user_id'] ?? null;
    }

    private function assertDraft($id)
    {
        // Feature E5: version restore changes body content, so it is draft-only.
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT signing_state FROM contracts WHERE id = ?");
        $stmt->execute([(int) $id]);

        if (strtoupper((string) $stmt->fetchColumn()) !== 'DRAFT') {
            throw new \Exception('Versions cannot be restored after signing starts.');
        }
    }
}
