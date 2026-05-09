<?php
$title = 'Contract Editor - ' . ($contract['title'] ?? 'New Contract');
ob_start();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <?php 
            $editor_config = [
                'contract_id' => $contract['id'],
                'content' => $contract['content'] ?? '',
                'is_locked' => $contract['signing_state'] !== 'DRAFT',
                'readonly' => false,
                'height' => '600px',
                'show_version_history' => true,
                'show_tracked_changes' => true
            ];
            include __DIR__ . '/components/contract-editor.php'; 
            ?>
        </div>
    </div>
    
    <div class="row mt-3">
        <div class="col-12 text-end">
            <button class="btn btn-success btn-lg" 
                    onclick="document.querySelector('[x-data]').__x.$data.submitForSigning?.()"
                    x-show="!<?= $contract['signing_state'] !== 'DRAFT' ?>">
                <i class="bi bi-send-check"></i> Submit for Signing
            </button>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/app.php';
?>