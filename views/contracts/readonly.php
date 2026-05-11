<?php
require_once dirname(__DIR__) . '/components/ui.php';

$contractId = (int) ($contract_id ?? ($contract['id'] ?? 1));
$state = strtoupper($contract['signing_state'] ?? 'DRAFT');
$title = 'Read Only Contract';
$activeNav = 'contracts';
$headerMeta = 'execution workspace';
$pageTitle = 'Read Only Contract';
$pageHeading = $contract['title'] ?? 'Read-Only Execution View';
$pageEyebrow = 'body lock enforced';
$pageLead = 'Review the locked document and complete company execution, sealing, or final distribution.';
$pageActions = [
    '<a class="button ghost" href="' . BASE_URL . '/contracts/show/' . $contractId . '">Contract Details</a>',
    '<a class="button success" href="' . BASE_URL . '/contracts/' . $contractId . '/editor#signing">Company Actions</a>',
];

ob_start();
?>
<section class="notice-banner warn">
    <strong>Body lock active</strong>
    <span>Current state: <?= ui_e(ui_status_label($state)) ?>. Contract body changes are disabled.</span>
</section>

<section class="content-split">
    <div class="surface doc-preview">
        <h3><?= ui_e($contract['title'] ?? 'Protected document body') ?></h3>
        <div class="readonly-document">
            <?= $contract['content'] ?? '<p>Contract content is not available.</p>' ?>
        </div>
    </div>

    <div class="page-stack">
        <!-- SIGNATURE BLOCK - PROMINENT DISPLAY -->
        <div class="surface surface-pad" style="background: #f5f5f5; border: 2px solid #007bff;">
            <h2 style="margin-bottom: 15px; color: #0056b3;">Document Signatures</h2>
            <?php if (!empty($signatures)): ?>
                <div style="display: grid; gap: 12px;">
                    <?php foreach ($signatures as $sig): ?>
                        <div style="background: white; border-left: 4px solid #28a745; padding: 12px; border-radius: 4px;">
                            <div style="display: flex; justify-content: space-between; align-items: start;">
                                <div>
                                    <strong style="display: block; margin-bottom: 4px; text-transform: capitalize; color: #0056b3;">
                                        <?= ucfirst(str_replace('_', ' ', $sig['signer_role'])) ?>
                                    </strong>
                                    <span style="color: #666; font-size: 0.9rem;">Signed by: <?= htmlspecialchars($sig['signer_id']) ?></span>
                                </div>
                                <span style="color: #666; font-size: 0.85rem; white-space: nowrap;">
                                    ✓ <?= date('M d, Y @ H:i', strtotime($sig['signed_at'])) ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="padding: 15px; background: #fff3cd; border-radius: 4px; color: #856404;">
                    <p style="margin: 0; font-style: italic;">⚠️ No signatures yet. Signatures will appear here after parties sign the contract.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- COMPANY SIGNATURE ACTION -->
        <div class="surface surface-pad" style="border: 2px solid #28a745; background: #f0f8f0;">
            <h3 style="color: #28a745; margin-bottom: 12px;">✎ Company Representative Sign</h3>
            <p style="color: #555; margin-bottom: 15px;">Sign digitally to apply your signature and company seal:</p>
            <form id="companySignForm" method="POST" action="<?= BASE_URL ?>/api/contracts/<?= $contractId ?>/sign" style="display: grid; gap: 12px;">
                <input type="hidden" name="role" value="company_rep">
                
                <label style="display: grid; gap: 5px;">
                    <span style="font-weight: bold; color: #333;">Your email:</span>
                    <input type="email" name="signer_id" value="admin@itec.com" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;" required>
                </label>
                
                <label style="display: grid; gap: 5px;">
                    <span style="font-weight: bold; color: #333;">Your full name:</span>
                    <input type="text" name="typed_signature" placeholder="Full legal name" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;" required>
                </label>
                
                <!-- SIGNATURE CANVAS -->
                <div style="display: grid; gap: 8px;">
                    <span style="font-weight: bold; color: #333;">Draw your signature:</span>
                    <canvas id="companySignaturePad" width="500" height="120" style="border: 2px solid #28a745; cursor: crosshair; background: white; display: block; border-radius: 4px;"></canvas>
                    <div style="display: flex; gap: 10px;">
                        <button type="button" id="clearCompanySignature" style="padding: 8px 12px; background: #f0f0f0; border: 1px solid #ddd; border-radius: 4px; cursor: pointer;">Clear Signature</button>
                        <span id="companySignatureStatus" style="color: #999; font-size: 0.9rem;"></span>
                    </div>
                    <input type="hidden" name="signature_data" id="companySignatureData" value="">
                </div>
                
                <button type="submit" style="padding: 12px; background: #28a745; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">
                    Sign & Apply Seal
                </button>
                <span id="companySignMessage" style="color: #666; font-size: 0.9rem;"></span>
            </form>
        </div>

        <div class="surface surface-pad">
            <h2>Allowed actions</h2>
            <ul class="check-list">
                <li>Company representative signs when the contract is awaiting company action.</li>
                <li>Company seal and approval stamp are applied to the execution copy.</li>
                <li>Final distribution starts only after the contract is fully signed.</li>
            </ul>
        </div>
        <div class="surface surface-pad">
            <h2>Execution links</h2>
            <div class="form-actions">
                <a class="button ghost" href="<?= BASE_URL ?>/contracts/final-pdf/<?= $contractId ?>">Preview PDF</a>
                <a class="button" href="<?= BASE_URL ?>/contracts/<?= $contractId ?>/editor#distribution">Distribution</a>
            </div>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/app.php';
?>

<script>
// Company signature pad setup
const companyCanvas = document.getElementById('companySignaturePad');
const companyCtx = companyCanvas.getContext('2d');
const companyClearBtn = document.getElementById('clearCompanySignature');
const companyStatus = document.getElementById('companySignatureStatus');
const companySignatureData = document.getElementById('companySignatureData');

let companyIsDrawing = false;
let companyHasSignature = false;

// Resize canvas
function resizeCompanyCanvas() {
    const rect = companyCanvas.getBoundingClientRect();
    companyCanvas.width = rect.width;
    companyCanvas.height = rect.height;
}

resizeCompanyCanvas();
window.addEventListener('resize', resizeCompanyCanvas);

// Drawing functions
function startCompanyDrawing(e) {
    companyIsDrawing = true;
    const rect = companyCanvas.getBoundingClientRect();
    const x = (e.clientX || e.touches?.[0]?.clientX) - rect.left;
    const y = (e.clientY || e.touches?.[0]?.clientY) - rect.top;
    companyCtx.beginPath();
    companyCtx.moveTo(x, y);
}

function drawCompany(e) {
    if (!companyIsDrawing) return;
    const rect = companyCanvas.getBoundingClientRect();
    const x = (e.clientX || e.touches?.[0]?.clientX) - rect.left;
    const y = (e.clientY || e.touches?.[0]?.clientY) - rect.top;
    companyCtx.lineWidth = 2;
    companyCtx.lineCap = 'round';
    companyCtx.lineJoin = 'round';
    companyCtx.strokeStyle = '#000';
    companyCtx.lineTo(x, y);
    companyCtx.stroke();
    companyHasSignature = true;
    companyStatus.textContent = '✓ Signature captured';
}

function stopCompanyDrawing() {
    companyIsDrawing = false;
    companyCtx.closePath();
}

// Mouse events
companyCanvas.addEventListener('mousedown', startCompanyDrawing);
companyCanvas.addEventListener('mousemove', drawCompany);
companyCanvas.addEventListener('mouseup', stopCompanyDrawing);
companyCanvas.addEventListener('mouseout', stopCompanyDrawing);

// Touch events
companyCanvas.addEventListener('touchstart', (e) => { e.preventDefault(); startCompanyDrawing(e); }, false);
companyCanvas.addEventListener('touchmove', (e) => { e.preventDefault(); drawCompany(e); }, false);
companyCanvas.addEventListener('touchend', (e) => { e.preventDefault(); stopCompanyDrawing(); }, false);

// Clear button
companyClearBtn.addEventListener('click', function() {
    companyCtx.clearRect(0, 0, companyCanvas.width, companyCanvas.height);
    companyHasSignature = false;
    companyStatus.textContent = '';
    companySignatureData.value = '';
});

// Form submission
document.getElementById('companySignForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const msg = document.getElementById('companySignMessage');
    
    // Capture signature as base64
    if (companyHasSignature) {
        companySignatureData.value = companyCanvas.toDataURL('image/png');
    }
    
    msg.textContent = 'Signing and applying seal...';
    
    try {
        const response = await fetch(this.action, {
            method: 'POST',
            body: new FormData(this),
            headers: { Accept: 'application/json' }
        });
        const result = await response.json();
        
        if (!response.ok || !result.success) {
            throw new Error(result.error || 'Signing failed');
        }
        
        msg.textContent = '✓ Signed successfully! Refreshing page...';
        setTimeout(() => location.reload(), 1500);
    } catch(err) {
        msg.textContent = '✗ ' + (err.message || 'Error signing');
    }
});
</script>