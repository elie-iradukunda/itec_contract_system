<?php
require_once dirname(__DIR__) . '/components/ui.php';

$contractId = (int) ($contract_id ?? 1);
$contractTitle = $contract['title'] ?? ('Contract #' . $contractId);
$alreadySigned = (bool) ($already_signed ?? false);
$title = 'Digital Signing';
$activeNav = 'contracts';
$headerMeta = 'client execution';
$pageTitle = 'Digital Signing';
$pageHeading = 'Digital Signature';
$pageEyebrow = 'client signing step';
$pageLead = $alreadySigned
    ? 'This contract has already completed the client signing step.'
    : 'Review the final instruction below, confirm your details, draw your signature, and submit the client execution step.';
$pageActions = [
    '<a class="button ghost" href="' . BASE_URL . '/sign/' . $contractId . '">Back to Signing Options</a>',
    '<a class="button" href="' . BASE_URL . '/contracts/' . $contractId . '/print-pdf" target="_blank" rel="noopener">Download PDF</a>',
];

ob_start();
?>
<section class="signing-stage surface">
    <div class="signing-stage-copy">
        <p>step 2 of 3</p>
        <h2><?= ui_e($contractTitle) ?></h2>
        <span>
            <?= $alreadySigned
                ? 'The digital signing step is already complete. The contract is now waiting for company signature and seal.'
                : 'The contract body is locked. Your digital signature confirms that you accept the contract exactly as presented.' ?>
        </span>
    </div>
    <div class="signing-stage-state">
        <strong>After you sign</strong>
        <span>Company countersign + seal</span>
    </div>
</section>

<?php if ($alreadySigned): ?>
    <section class="notice-banner success">
        <strong>Client signing already completed</strong>
        <span>You can close this page. The company team will continue the final execution step.</span>
    </section>
<?php else: ?>
    <section class="content-split">
        <div class="surface surface-pad execution-card">
            <div class="section-head compact no-border">
                <div>
                    <p>digital signature</p>
                    <h2>Complete client signing</h2>
                </div>
            </div>
            <div class="notice-banner" style="margin-bottom: 18px;">
                <strong>What happens on this page</strong>
                <span>Fill your legal name, draw your signature, and submit once. The document body remains locked while the contract moves to the company approval step.</span>
            </div>
            <form id="digitalSignForm" class="form-grid" data-sign-url="<?= BASE_URL ?>/api/contracts/<?= $contractId ?>/sign" data-complete-url="<?= BASE_URL ?>/sign/<?= $contractId ?>?signed=digital">
                <input type="hidden" name="role" value="client">
                <label class="field-span">
                    <span>Signer email</span>
                    <input type="email" name="signer_id" value="<?= ui_e($signing_email ?? '') ?>" placeholder="client@example.com" required>
                </label>
                <label class="field-span">
                    <span>Full legal name</span>
                    <input type="text" name="typed_signature" placeholder="Full legal name" required>
                </label>
                <div class="signature-pad signature-capture field-span" style="min-height: auto; place-items: stretch; text-align: left;">
                    <strong>Draw your signature</strong>
                    <span style="display: block; margin-bottom: 10px; font-size: 0.95rem; color: #666;">Use your mouse, touchpad, or touchscreen to sign below.</span>
                    <canvas id="signaturePad" width="500" height="150" style="border: 2px solid #ddd; cursor: crosshair; background: white; display: block; margin: 10px 0; border-radius: 4px; width: 100%;"></canvas>
                    <div style="display: flex; gap: 10px; margin-top: 10px; align-items: center; flex-wrap: wrap;">
                        <button type="button" class="button ghost" id="clearSignature">Clear Signature</button>
                        <span id="signatureStatus" style="color: #999; font-size: 0.95rem;"></span>
                    </div>
                    <input type="hidden" name="signature_data" id="signatureData" value="">
                </div>
                <div class="field-span form-actions">
                    <button class="button" type="submit">Submit Digital Signature</button>
                    <span id="digitalSignMessage" class="muted-copy"></span>
                </div>
            </form>
        </div>

        <aside class="page-stack">
            <div class="surface surface-pad execution-card">
                <div class="section-head compact no-border">
                    <div>
                        <p>before submit</p>
                        <h2>Checklist</h2>
                    </div>
                </div>
                <ul class="check-list">
                    <li>Your email matches the signing invitation.</li>
                    <li>Your legal name is entered exactly as required.</li>
                    <li>Your signature is clearly drawn before submitting.</li>
                </ul>
            </div>
            <div class="surface surface-pad execution-card">
                <div class="section-head compact no-border">
                    <div>
                        <p>what happens next</p>
                        <h2>After client signing</h2>
                    </div>
                </div>
                <div class="handoff-list">
                    <span><?= ui_icon('check-circle') ?> Client signature is stored in the audit trail</span>
                    <span><?= ui_icon('lock-fill') ?> Contract body remains locked</span>
                    <span><?= ui_icon('building-check') ?> Company proceeds with countersign and seal</span>
                </div>
            </div>
            <div class="surface surface-pad execution-card">
                <div class="section-head compact no-border">
                    <div>
                        <p>need paper instead?</p>
                        <h2>Switch to hard copy</h2>
                    </div>
                </div>
                <p class="muted-copy">If you prefer physical signing, go back and choose the hard-copy path instead.</p>
                <div class="form-actions">
                    <a class="button ghost" href="<?= BASE_URL ?>/sign/<?= $contractId ?>">Back to options</a>
                </div>
            </div>
        </aside>
    </section>

    <script>
    const canvas = document.getElementById('signaturePad');
    const ctx = canvas.getContext('2d');
    const clearBtn = document.getElementById('clearSignature');
    const signatureStatus = document.getElementById('signatureStatus');
    const signatureDataInput = document.getElementById('signatureData');

    let isDrawing = false;
    let hasSignature = false;

    function resizeCanvas() {
        const rect = canvas.getBoundingClientRect();
        const snapshot = canvas.toDataURL();
        canvas.width = rect.width;
        canvas.height = 150;

        if (hasSignature) {
            const image = new Image();
            image.onload = function () {
                ctx.drawImage(image, 0, 0, canvas.width, canvas.height);
            };
            image.src = snapshot;
        }
    }

    resizeCanvas();
    window.addEventListener('resize', resizeCanvas);

    function point(event) {
        const rect = canvas.getBoundingClientRect();
        return {
            x: (event.clientX || event.touches?.[0]?.clientX) - rect.left,
            y: (event.clientY || event.touches?.[0]?.clientY) - rect.top
        };
    }

    function startDrawing(event) {
        isDrawing = true;
        const pos = point(event);
        ctx.beginPath();
        ctx.moveTo(pos.x, pos.y);
    }

    function draw(event) {
        if (!isDrawing) return;
        const pos = point(event);
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.strokeStyle = '#000';
        ctx.lineTo(pos.x, pos.y);
        ctx.stroke();
        hasSignature = true;
        signatureStatus.textContent = 'Signature captured successfully.';
    }

    function stopDrawing() {
        isDrawing = false;
        ctx.closePath();
    }

    canvas.addEventListener('mousedown', startDrawing);
    canvas.addEventListener('mousemove', draw);
    canvas.addEventListener('mouseup', stopDrawing);
    canvas.addEventListener('mouseout', stopDrawing);
    canvas.addEventListener('touchstart', function (event) { event.preventDefault(); startDrawing(event); }, false);
    canvas.addEventListener('touchmove', function (event) { event.preventDefault(); draw(event); }, false);
    canvas.addEventListener('touchend', function (event) { event.preventDefault(); stopDrawing(); }, false);

    clearBtn.addEventListener('click', function () {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        hasSignature = false;
        signatureStatus.textContent = '';
        signatureDataInput.value = '';
    });

    document.getElementById('digitalSignForm')?.addEventListener('submit', async function (event) {
        event.preventDefault();
        const message = document.getElementById('digitalSignMessage');

        if (!hasSignature) {
            message.textContent = 'Please draw your signature before submitting.';
            return;
        }

        signatureDataInput.value = canvas.toDataURL('image/png');
        message.textContent = 'Submitting your digital signature...';

        try {
            const response = await fetch(this.dataset.signUrl, {
                method: 'POST',
                body: new FormData(this),
                headers: { Accept: 'application/json' }
            });
            const result = await (window.ContractUi ? ContractUi.responseJson(response) : response.json());
            if (!response.ok || result.success === false) throw new Error(result.message || result.error || 'Signing failed');
            message.textContent = 'Signature submitted successfully. Redirecting...';
            window.location.href = this.dataset.completeUrl;
        } catch (error) {
            message.textContent = error.message || 'Signing failed';
        }
    });
    </script>
<?php endif; ?>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/app.php';
