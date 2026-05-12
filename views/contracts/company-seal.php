<?php
$title = 'Company Seal - ' . ($contract['title'] ?? 'Contract');
$activeNav = 'contracts';
$headerMeta = 'company seal application';
$showPageHeader = false;
$pageStyles = [
    BASE_URL . '/public/assets/css/contract-editor.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'
];
$pageScripts = [
    'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js',
    BASE_URL . '/public/assets/js/company-seal.js',
];

ob_start();

$contractId = $contract['id'] ?? 0;
$contractTitle = $contract['title'] ?? 'Untitled Contract';
$clientName = $contract['client_name'] ?? 'N/A';
$clientEmail = $contract['client_email'] ?? 'N/A';
$currentState = $contract['signing_state'] ?? 'AWAITING_COMPANY';
$isReadOnly = ($currentState !== 'AWAITING_COMPANY');

// Get contract file path for PDF preview
$contractFile = $contract['file_path'] ?? '';
$pdfUrl = !empty($contractFile) ? BASE_URL . '/' . $contractFile : '';
$pdfExists = !empty($contractFile) && file_exists(__DIR__ . '/../' . $contractFile);

// Get ULID approval code from session or generate new
$approvalCode = $_SESSION['approval_code'] ?? 'ULID_' . strtoupper(uniqid());
$_SESSION['approval_code'] = $approvalCode;
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
                    <div id="pdf-viewer" class="bg-light" style="height: 70vh; overflow: auto; padding: 20px;">
                        <div id="pdf-loading" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2 text-muted">Loading contract document...</p>
                        </div>
                        <canvas id="pdf-canvas" style="display: none; margin: 0 auto; box-shadow: 0 2px 8px rgba(0,0,0,0.1);"></canvas>
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
                        <div>
                            <span class="text-muted small">
                                <i class="fas fa-info-circle me-1"></i> Review entire contract before sealing
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Seal Panel -->
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-stamp me-2 text-primary"></i>
                        Apply Company Seal
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($isReadOnly): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            This contract is not in a state that allows sealing.
                            Current state: <strong><?= $currentState ?></strong>
                        </div>
                        <a href="<?= BASE_URL ?>/contracts/show/<?= $contractId ?>" class="btn btn-secondary w-100">
                            <i class="fas fa-arrow-left me-2"></i> Back to Contract
                        </a>
                    <?php else: ?>
                        <!-- Contract Info -->
                        <div class="mb-4">
                            <h6>Contract Information</h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td class="text-muted">Contract ID:</td>
                                    <td class="fw-bold">#<?= $contractId ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Title:</td>
                                    <td><?= htmlspecialchars($contractTitle) ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Client:</td>
                                    <td><?= htmlspecialchars($clientName) ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Email:</td>
                                    <td><?= htmlspecialchars($clientEmail) ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Status:</td>
                                    <td>
                                        <span class="badge bg-warning text-dark"><?= $currentState ?></span>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <hr>

                        <!-- Confirmation -->
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="confirmReview" required>
                                <label class="form-check-label" for="confirmReview">
                                    I have reviewed the entire contract and confirm it is correct.
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="approverName" class="form-label">Approver Name</label>
                            <input type="text" class="form-control" id="approverName" 
                                   value="<?= htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['user_email'] ?? 'Company Representative') ?>">
                        </div>

                        <!-- Stamp Preview -->
                        <div class="mt-4 p-3 bg-light rounded">
                            <h6 class="mb-2">Stamp Preview</h6>
                            <div class="small">
                                <div><strong>APPROVED FOR EXECUTION</strong></div>
                                <div>By: <span id="previewApprover"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Company Representative') ?></span></div>
                                <div>Code: <span id="previewCode"><?= $approvalCode ?></span></div>
                                <div>Date: <span id="previewDate"><?= date('Y-m-d H:i:s') ?></span></div>
                            </div>
                        </div>

                        <hr>

                        <button type="button" class="btn btn-primary btn-lg w-100" id="applySealBtn" disabled>
                            <i class="fas fa-stamp me-2"></i> Apply Company Seal & Finalize
                        </button>

                        <a href="<?= BASE_URL ?>/contracts/show/<?= $contractId ?>" class="btn btn-outline-secondary btn-lg w-100 mt-2">
                            <i class="fas fa-times me-2"></i> Cancel
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';

let pdfDoc = null;
let currentPage = 1;
let scale = 1.5;
let pdfUrl = '<?= $pdfUrl ?>';
let pdfExists = <?= $pdfExists ? 'true' : 'false' ?>;

const canvas = document.getElementById('pdf-canvas');
const ctx = canvas.getContext('2d');
const loadingDiv = document.getElementById('pdf-loading');
const errorDiv = document.getElementById('pdf-error');
const errorMessage = document.getElementById('error-message');
const prevBtn = document.getElementById('prevPageBtn');
const nextBtn = document.getElementById('nextPageBtn');
const pageNumSpan = document.getElementById('pageNum');
const pageCountSpan = document.getElementById('pageCount');
const zoomInBtn = document.getElementById('zoomInBtn');
const zoomOutBtn = document.getElementById('zoomOutBtn');
const resetZoomBtn = document.getElementById('resetZoomBtn');
const confirmCheckbox = document.getElementById('confirmReview');
const applySealBtn = document.getElementById('applySealBtn');
const approverInput = document.getElementById('approverName');
const previewApprover = document.getElementById('previewApprover');
const previewDate = document.getElementById('previewDate');
const previewCode = document.getElementById('previewCode');

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
    if (!pdfExists) {
        loadingDiv.classList.add('d-none');
        errorDiv.classList.remove('d-none');
        errorMessage.textContent = 'Contract document not found. Please ensure the contract file exists.';
        canvas.style.display = 'none';
        return;
    }
    
    pdfjsLib.getDocument(pdfUrl).promise.then(doc => {
        pdfDoc = doc;
        pageCountSpan.textContent = pdfDoc.numPages;
        canvas.style.display = 'block';
        loadingDiv.classList.add('d-none');
        renderPage();
    }).catch(error => {
        loadingDiv.classList.add('d-none');
        errorDiv.classList.remove('d-none');
        errorMessage.textContent = 'Failed to load PDF: ' + error.message;
        canvas.style.display = 'none';
    });
}

function nextPage() {
    if (pdfDoc && currentPage < pdfDoc.numPages) {
        currentPage++;
        renderPage();
    }
}

function prevPage() {
    if (pdfDoc && currentPage > 1) {
        currentPage--;
        renderPage();
    }
}

function zoomIn() {
    scale += 0.25;
    renderPage();
}

function zoomOut() {
    if (scale > 0.5) {
        scale -= 0.25;
        renderPage();
    }
}

function resetZoom() {
    scale = 1.5;
    renderPage();
}

// Update preview when approver name changes
approverInput.addEventListener('input', function() {
    previewApprover.textContent = this.value;
});

// Enable seal button when checkbox is checked
confirmCheckbox.addEventListener('change', function() {
    applySealBtn.disabled = !this.checked;
});

// Update preview date every second
setInterval(() => {
    previewDate.textContent = new Date().toLocaleString();
}, 1000);

// Apply Seal
applySealBtn.addEventListener('click', async function() {
    const approverName = approverInput.value;
    
    if (!approverName.trim()) {
        alert('Please enter approver name');
        return;
    }
    
    applySealBtn.disabled = true;
    applySealBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Applying Seal...';
    
    try {
        const response = await fetch('<?= BASE_URL ?>/api/contracts/<?= $contractId ?>/seal', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                approver_name: approverName
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.location.href = '<?= BASE_URL ?>/contracts/show/<?= $contractId ?>?sealed=1';
        } else {
            alert('Error: ' + (result.error || 'Failed to apply seal'));
            applySealBtn.disabled = false;
            applySealBtn.innerHTML = '<i class="fas fa-stamp me-2"></i> Apply Company Seal & Finalize';
        }
    } catch (error) {
        alert('Network error: ' + error.message);
        applySealBtn.disabled = false;
        applySealBtn.innerHTML = '<i class="fas fa-stamp me-2"></i> Apply Company Seal & Finalize';
    }
});

// Event Listeners
prevBtn.addEventListener('click', prevPage);
nextBtn.addEventListener('click', nextPage);
zoomInBtn.addEventListener('click', zoomIn);
zoomOutBtn.addEventListener('click', zoomOut);
resetZoomBtn.addEventListener('click', resetZoom);

// Load PDF on page load
loadPDF();
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/app.php';