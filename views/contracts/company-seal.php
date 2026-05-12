<?php
$title = 'Company Seal - ' . ($contract['title'] ?? 'Contract');
$activeNav = 'contracts';
$headerMeta = 'company seal application';
$showPageHeader = false;

ob_start();

use Core\Database;

$db = Database::getInstance()->getConnection();

$contractId = $contract['id'] ?? 0;
$contractTitle = $contract['title'] ?? 'Untitled Contract';
$clientName = $contract['client_name'] ?? 'N/A';
$clientEmail = $contract['client_email'] ?? 'N/A';
$currentState = $contract['signing_state'] ?? 'AWAITING_COMPANY';
$isReadOnly = ($currentState !== 'AWAITING_COMPANY');
$approvalCode = $contract['approval_code'] ?? strtoupper(bin2hex(random_bytes(4)));

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

// 2. Check existence using system path
$pdfExists = !empty($contractFile) && file_exists($contractFile);

/**
 * FIX: Convert Absolute Path to Web URL
 * We strip the local disk path and replace it with the web base URL.
 */
$rootPath = "C:/xampp/htdocs/itec_contract_system/"; 
$cleanPath = str_replace('\\', '/', $contractFile);
$relativeUrl = str_replace($rootPath, '', $cleanPath);
$pdfUrl = BASE_URL . '/' . ltrim($relativeUrl, '/');
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Left Column: PDF Preview -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-file-pdf text-danger me-2"></i>
                        Contract Preview
                    </h5>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary" id="zoomOutBtn" title="Zoom Out">
                            <i class="fas fa-search-minus"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="zoomInBtn" title="Zoom In">
                            <i class="fas fa-search-plus"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="resetZoomBtn" title="Reset Zoom">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div id="pdf-viewer" class="bg-light" style="height: 75vh; overflow: auto; padding: 20px;">
                        <div id="pdf-loading" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2 text-muted">Loading contract document...</p>
                        </div>
                        <canvas id="pdf-canvas" style="display: none; margin: 0 auto; box-shadow: 0 4px 12px rgba(0,0,0,0.15);"></canvas>
                        <div id="pdf-error" class="alert alert-danger m-3 d-none">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <span id="error-message"></span>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="prevPageBtn" disabled>
                                <i class="fas fa-chevron-left me-1"></i> Previous
                            </button>
                            <span class="mx-2">
                                Page <span id="pageNum">1</span> of <span id="pageCount">0</span>
                            </span>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="nextPageBtn" disabled>
                                Next <i class="fas fa-chevron-right ms-1"></i>
                            </button>
                        </div>
                        <span class="text-muted small">
                            <i class="fas fa-info-circle me-1"></i> Review entire contract before sealing
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Seal Panel -->
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

                        <!-- Stamp Preview -->
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

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>

<script>
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';

let pdfDoc = null,
    currentPage = 1,
    scale = 1.5,
    pdfUrl = '<?= $pdfUrl ?>',
    pdfExists = <?= $pdfExists ? 'true' : 'false' ?>;

const canvas = document.getElementById('pdf-canvas'),
      ctx = canvas.getContext('2d'),
      loadingDiv = document.getElementById('pdf-loading'),
      errorDiv = document.getElementById('pdf-error'),
      errorMessage = document.getElementById('error-message'),
      prevBtn = document.getElementById('prevPageBtn'),
      nextBtn = document.getElementById('nextPageBtn'),
      pageNumSpan = document.getElementById('pageNum'),
      pageCountSpan = document.getElementById('pageCount'),
      applySealBtn = document.getElementById('applySealBtn'),
      confirmCheckbox = document.getElementById('confirmReview'),
      approverInput = document.getElementById('approverName'),
      previewApprover = document.getElementById('previewApprover'),
      previewDate = document.getElementById('previewDate');

function renderPage() {
    if (!pdfDoc) return;
    
    pdfDoc.getPage(currentPage).then(page => {
        const viewport = page.getViewport({ scale: scale });
        canvas.height = viewport.height;
        canvas.width = viewport.width;
        
        const renderContext = {
            canvasContext: ctx,
            viewport: viewport
        };
        
        page.render(renderContext);
        
        pageNumSpan.textContent = currentPage;
        prevBtn.disabled = currentPage === 1;
        nextBtn.disabled = currentPage === pdfDoc.numPages;
    });
}

function loadPDF() {
    if (!pdfExists || !pdfUrl) {
        showError('Document file not found on server.');
        return;
    }
    
    pdfjsLib.getDocument(pdfUrl).promise.then(doc => {
        pdfDoc = doc;
        pageCountSpan.textContent = pdfDoc.numPages;
        canvas.style.display = 'block';
        loadingDiv.classList.add('d-none');
        renderPage();
    }).catch(error => {
        showError('Error loading PDF: ' + error.message);
    });
}

function showError(msg) {
    loadingDiv.classList.add('d-none');
    errorDiv.classList.remove('d-none');
    errorMessage.textContent = msg;
    canvas.style.display = 'none';
}

// Controls
document.getElementById('zoomInBtn').addEventListener('click', () => { scale += 0.25; renderPage(); });
document.getElementById('zoomOutBtn').addEventListener('click', () => { if(scale > 0.5) { scale -= 0.25; renderPage(); } });
document.getElementById('resetZoomBtn').addEventListener('click', () => { scale = 1.5; renderPage(); });
prevBtn.addEventListener('click', () => { if(currentPage > 1) { currentPage--; renderPage(); } });
nextBtn.addEventListener('click', () => { if(currentPage < pdfDoc.numPages) { currentPage++; renderPage(); } });

approverInput.addEventListener('input', (e) => previewApprover.textContent = e.target.value);
confirmCheckbox.addEventListener('change', (e) => applySealBtn.disabled = !e.target.checked);

setInterval(() => {
    previewDate.textContent = new Date().toLocaleString();
}, 1000);

applySealBtn.addEventListener('click', async function() {
    const approverName = approverInput.value.trim();
    if (!approverName) return alert('Approver name is required.');

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
            alert(result.error || 'Failed to apply seal');
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-stamp me-2"></i> Finalize & Seal';
        }
    } catch (e) {
        alert('Connection error. Please try again.');
        this.disabled = false;
    }
});

loadPDF();
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/app.php';