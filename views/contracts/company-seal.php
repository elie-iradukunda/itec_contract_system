<?php
$title = 'Company Seal - ' . ($contract['title'] ?? 'Contract');
$activeNav = 'contracts';
$headerMeta = 'company seal application';
$showPageHeader = false;

ob_start();

use Core\Database;

$db = Database::getInstance()->getConnection();

$contractId    = $contract['id'] ?? 0;
$contractTitle = $contract['title'] ?? 'Untitled Contract';
$clientName    = $contract['client_name'] ?? 'N/A';
$clientEmail   = $contract['client_email'] ?? 'N/A';
$currentState  = $contract['signing_state'] ?? 'AWAITING_COMPANY';
$isReadOnly    = ($currentState !== 'AWAITING_COMPANY');
$approvalCode  = $contract['approval_code'] ?? strtoupper(bin2hex(random_bytes(4)));

// 1. Get the physical system path
$stmt = $db->prepare("
    SELECT uploaded_file_path 
    FROM doc_signature_audit 
    WHERE contract_id = ? AND event_type = 'hard_copy_uploaded'
    ORDER BY timestamp DESC 
    LIMIT 1
");
$stmt->execute([$contractId]);
$auditRecord = $stmt->fetch();

$contractFile = ($auditRecord && !empty($auditRecord['uploaded_file_path']))
    ? $auditRecord['uploaded_file_path']
    : ($contract['file_path'] ?? '');

// 2. Check file existence
$pdfExists = !empty($contractFile) && file_exists($contractFile);

// 3. Prepare the URL
// We use rawurlencode to handle special characters and ensure the path starts correctly.
$fileName = basename($contractFile);
$pdfUrl = BASE_URL . '/storage/contracts/signed_copies/' . rawurlencode($fileName);
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-file-pdf text-danger me-2"></i>
                        Contract Preview
                    </h5>
                    <?php if ($pdfExists): ?>
                        <a href="<?= $pdfUrl ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-external-link-alt"></i> Open in New Tab
                        </a>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <div id="pdf-viewer" style="height: 75vh; width: 100%;">
                        <?php if ($pdfExists): ?>
                            <iframe src="<?= $pdfUrl ?>#toolbar=0" width="100%" height="100%" style="border: none;"></iframe>
                        <?php endif; ?>
                    </div>
                    <div id="pdf-error" class="alert alert-danger m-3 d-none">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <span id="error-message"></span>
                    </div>
                </div>
                <div class="card-footer bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">
                            <i class="fas fa-info-circle me-1"></i> Review entire contract before sealing
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <h5 class="mb-0 text-primary">
                        <i class="fas fa-stamp me-2"></i>
                        Apply Company Seal
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($isReadOnly): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            This contract is not in a state that allows sealing.
                            <br>Current state: <strong><?= $currentState ?></strong>
                        </div>
                        <a href="<?= BASE_URL ?>/contracts/show/<?= $contractId ?>" class="btn btn-secondary w-100">
                            <i class="fas fa-arrow-left me-2"></i> Back to Contract
                        </a>
                    <?php else: ?>
                        <div class="mb-4">
                            <h6 class="text-uppercase text-muted small fw-bold mb-3">Contract Summary</h6>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Contract ID:</span>
                                <span class="fw-bold">#<?= $contractId ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Client:</span>
                                <span><?= htmlspecialchars($clientName) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Status:</span>
                                <span class="badge bg-warning text-dark"><?= $currentState ?></span>
                            </div>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <label for="approverName" class="form-label">Approver Name</label>
                            <input type="text" class="form-control" id="approverName"
                                   value="<?= htmlspecialchars($_SESSION['user_name'] ?? 'Company Representative') ?>">
                        </div>

                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="confirmReview">
                                <label class="form-check-label small" for="confirmReview">
                                    I confirm that I have reviewed the document and it is ready for execution.
                                </label>
                            </div>
                        </div>

                        <div class="mb-4 p-3 border rounded bg-light" style="border-left: 4px solid #0d6efd !important;">
                            <h6 class="mb-2 small fw-bold text-primary text-uppercase">Digital Stamp Preview</h6>
                            <div class="font-monospace" style="font-size: 0.75rem; line-height: 1.4;">
                                <div class="fw-bold">APPROVED FOR EXECUTION</div>
                                <div>BY: <span id="previewApprover"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Company Representative') ?></span></div>
                                <div>CODE: <span id="previewCode"><?= $approvalCode ?></span></div>
                                <div>DATE: <span id="previewDate"><?= date('Y-m-d H:i:s') ?></span></div>
                            </div>
                        </div>

                        <button type="button" class="btn btn-primary btn-lg w-100 mb-2" id="applySealBtn" disabled>
                            <i class="fas fa-stamp me-2"></i> Finalize & Seal
                        </button>

                        <a href="<?= BASE_URL ?>/contracts/show/<?= $contractId ?>" class="btn btn-link w-100 text-decoration-none text-muted">
                            Cancel
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="module">
    import EmbedPDF from 'https://cdn.jsdelivr.net/npm/@embedpdf/snippet@2/dist/embedpdf.js';
    
    const pdfUrl = '<?= addslashes($pdfUrl) ?>';
    const pdfExists = <?= $pdfExists ? 'true' : 'false' ?>;
    const errorDiv = document.getElementById('pdf-error');
    const errorMessage = document.getElementById('error-message');
    
    if (!pdfExists || !pdfUrl) {
        if (errorDiv) {
            errorDiv.classList.remove('d-none');
            errorMessage.textContent = 'Document file not found on server.';
        }
    } else {
        try {
            // Attempt to initialize the viewer; if it fails, the iframe fallback is already in the HTML.
            EmbedPDF.init({
                type: 'container',
                target: document.getElementById('pdf-viewer'),
                src: pdfUrl,
                theme: { preference: 'system' }
            });
        } catch (error) {
            console.error('EmbedPDF failed, falling back to iframe.');
        }
    }
</script>

<script>
const confirmCheckbox = document.getElementById('confirmReview');
const applySealBtn = document.getElementById('applySealBtn');
const approverInput = document.getElementById('approverName');
const previewApprover = document.getElementById('previewApprover');
const previewDate = document.getElementById('previewDate');

if (confirmCheckbox && applySealBtn) {
    confirmCheckbox.addEventListener('change', function() {
        applySealBtn.disabled = !this.checked;
    });
}

if (approverInput && previewApprover) {
    approverInput.addEventListener('input', function() {
        previewApprover.textContent = this.value;
    });
}

if (previewDate) {
    setInterval(() => {
        previewDate.textContent = new Date().toLocaleString();
    }, 1000);
}

if (applySealBtn) {
    applySealBtn.addEventListener('click', async function() {
        const approverName = approverInput ? approverInput.value.trim() : '';
        if (!approverName) { alert('Please enter approver name'); return; }
        
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Processing...';
        
        try {
            const response = await fetch('<?= BASE_URL ?>/api/contracts/<?= $contractId ?>/seal', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ approver_name: approverName })
            });
            
            const result = await response.json();
            if (result.success) {
                window.location.href = '<?= BASE_URL ?>/contracts/show/<?= $contractId ?>?sealed=1';
            } else {
                alert('Error: ' + (result.error || 'Failed to apply seal'));
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-stamp me-2"></i> Finalize & Seal';
            }
        } catch (error) {
            alert('Connection error: ' + error.message);
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-stamp me-2"></i> Finalize & Seal';
        }
    });
}
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/app.php';
?>