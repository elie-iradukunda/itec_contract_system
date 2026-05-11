<?php
require_once dirname(__DIR__) . '/components/ui.php';

$contractId = (int) ($contract_id ?? 1);
$title = 'Digital Signing';
$activeNav = 'contracts';
$headerMeta = 'client execution';
$pageTitle = 'Digital Signing';
$pageHeading = 'Digital Signing';
$pageEyebrow = 'client execution path';
$pageLead = 'Choose a signing path for the locked client copy.';
$pageActions = [
    '<a class="button ghost" href="' . BASE_URL . '/clients/portal">Back to Client Portal</a>',
    '<a class="button" href="' . BASE_URL . '/contracts/' . $contractId . '/editor#signing">Open Contract</a>',
];

ob_start();
?>
<section class="signing-stage surface">
    <div class="signing-stage-copy">
        <p>awaiting client</p>
        <h2>Client execution workspace</h2>
        <span>The body is frozen. The client can sign digitally or complete the hard-copy path without changing contract text.</span>
    </div>
    <div class="signing-stage-state">
        <strong>After client signs</strong>
        <span>Company execution</span>
    </div>
</section>

<section class="content-split">
    <div class="surface surface-pad execution-card">
        <div class="section-head compact no-border"><div><p>digital signature</p><h2>Portal signing</h2></div></div>
        <p class="muted-copy">By signing, the client confirms the locked contract body is accepted exactly as presented.</p>
        <form id="digitalSignForm" class="form-grid" data-sign-url="<?= BASE_URL ?>/api/contracts/<?= $contractId ?>/sign">
            <!-- Feature E4: this form records the client signature and keeps the body locked. -->
            <input type="hidden" name="role" value="client">
            <label class="field-span">
                <span>Signer email</span>
                <input type="email" name="signer_id" value="client@itec.local" required>
            </label>
            <label class="field-span">
            <span>Full legal name</span>
                <input type="text" name="typed_signature" placeholder="Full legal name" required>
            </label>
            <div class="signature-pad signature-capture">
                <strong>Draw your signature</strong>
                <span style="display: block; margin-bottom: 10px; font-size: 0.9rem; color: #666;">Use your mouse or touch device to sign below:</span>
                <canvas id="signaturePad" width="500" height="150" style="border: 2px solid #ddd; cursor: crosshair; background: white; display: block; margin: 10px 0; border-radius: 4px;"></canvas>
                <div style="display: flex; gap: 10px; margin-top: 10px;">
                    <button type="button" class="button ghost" id="clearSignature">Clear Signature</button>
                    <span id="signatureStatus" style="color: #999; font-size: 0.9rem;"></span>
                </div>
                <input type="hidden" name="signature_data" id="signatureData" value="">
            </div>
            <div class="field-span form-actions">
                <button class="button" type="submit">Sign Digitally</button>
                <span id="digitalSignMessage" class="muted-copy"></span>
            </div>
        </form>
    </div>

    <aside class="page-stack">
        <div class="surface surface-pad execution-card">
            <div class="section-head compact no-border"><div><p>hard copy</p><h2>Print and upload</h2></div></div>
            <p class="muted-copy">Download the print-ready PDF, sign it physically, then staff can upload the scan.</p>
            <div class="form-actions">
                <a class="button ghost" href="<?= BASE_URL ?>/contracts/<?= $contractId ?>/print-pdf" target="_blank" rel="noopener">Download PDF</a>
                <a class="button" href="<?= BASE_URL ?>/contracts/<?= $contractId ?>/upload-hard-copy">Upload scan</a>
            </div>
        </div>
        <div class="surface surface-pad execution-card">
            <div class="section-head compact no-border"><div><p>after signing</p><h2>Company handoff</h2></div></div>
            <div class="handoff-list">
                <span><?= ui_icon('lock-fill') ?> Body remains frozen</span>
                <span><?= ui_icon('envelope') ?> Company representative receives the next action</span>
                <span><?= ui_icon('fingerprint') ?> Signature hash is stored in audit trail</span>
            </div>
        </div>
    </aside>
</section>

<script>
// Initialize signature pad
const canvas = document.getElementById('signaturePad');
const ctx = canvas.getContext('2d');
const clearBtn = document.getElementById('clearSignature');
const signatureStatus = document.getElementById('signatureStatus');
const signatureDataInput = document.getElementById('signatureData');

let isDrawing = false;
let hasSignature = false;

// Resize canvas to fit container on load
function resizeCanvas() {
    const rect = canvas.getBoundingClientRect();
    canvas.width = rect.width;
    canvas.height = rect.height;
}

resizeCanvas();
window.addEventListener('resize', resizeCanvas);

// Drawing functions
function startDrawing(e) {
    isDrawing = true;
    const rect = canvas.getBoundingClientRect();
    const x = (e.clientX || e.touches?.[0]?.clientX) - rect.left;
    const y = (e.clientY || e.touches?.[0]?.clientY) - rect.top;
    ctx.beginPath();
    ctx.moveTo(x, y);
}

function draw(e) {
    if (!isDrawing) return;
    const rect = canvas.getBoundingClientRect();
    const x = (e.clientX || e.touches?.[0]?.clientX) - rect.left;
    const y = (e.clientY || e.touches?.[0]?.clientY) - rect.top;
    ctx.lineWidth = 2;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.strokeStyle = '#000';
    ctx.lineTo(x, y);
    ctx.stroke();
    hasSignature = true;
    signatureStatus.textContent = 'Signature captured ✓';
}

function stopDrawing() {
    isDrawing = false;
    ctx.closePath();
}

// Mouse events
canvas.addEventListener('mousedown', startDrawing);
canvas.addEventListener('mousemove', draw);
canvas.addEventListener('mouseup', stopDrawing);
canvas.addEventListener('mouseout', stopDrawing);

// Touch events (for mobile/tablet)
canvas.addEventListener('touchstart', (e) => { e.preventDefault(); startDrawing(e); }, false);
canvas.addEventListener('touchmove', (e) => { e.preventDefault(); draw(e); }, false);
canvas.addEventListener('touchend', (e) => { e.preventDefault(); stopDrawing(); }, false);

// Clear button
clearBtn.addEventListener('click', function() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    hasSignature = false;
    signatureStatus.textContent = '';
    signatureDataInput.value = '';
});

// Form submission with signature capture
document.getElementById('digitalSignForm')?.addEventListener('submit', async function (event) {
    event.preventDefault();
    const message = document.getElementById('digitalSignMessage');
    
    // Capture signature as base64
    if (hasSignature) {
        signatureDataInput.value = canvas.toDataURL('image/png');
    }
    
    message.textContent = 'Signing contract...';

    try {
        const response = await fetch(this.dataset.signUrl, {
            method: 'POST',
            body: new FormData(this),
            headers: { Accept: 'application/json' }
        });
        const result = await (window.ContractUi ? ContractUi.responseJson(response) : response.json());
        if (!response.ok || result.success === false) throw new Error(result.message || result.error || 'Signing failed');
        message.textContent = 'Signed successfully. Redirecting to execution view...';
        window.location.href = '<?= BASE_URL ?>/contracts/<?= $contractId ?>/editor#signing';
    } catch (error) {
        message.textContent = error.message || 'Signing failed';
    }
});
</script>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/app.php';
