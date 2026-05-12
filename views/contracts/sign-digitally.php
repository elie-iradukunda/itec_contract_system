<?php
require_once dirname(__DIR__) . '/components/ui.php';

$contractId = (int) ($contract_id ?? 1);
$contractTitle = $contract['title'] ?? ('Contract #' . $contractId);
$signingEmail = $signing_email ?? ($contract['client_email'] ?? '');
$clientName = $contract['client_name'] ?? 'Client';
$pdfUrl = BASE_URL . '/contracts/' . $contractId . '/print-pdf';
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
    '<a class="button ghost" href="' . BASE_URL . '/sign/' . $contractId . '">' . ui_icon('arrow-left') . ' Back to Signing Options</a>',
    '<a class="button" href="' . $pdfUrl . '" target="_blank" rel="noopener" data-review-pdf>' . ui_icon('file-earmark-pdf') . ' Review Full PDF</a>',
];

ob_start();
?>
<style>
    .digital-sign-page { display: grid; gap: 20px; }
    .execution-map { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0; overflow: hidden; }
    .execution-map article { min-height: 116px; display: grid; align-content: start; gap: 8px; padding: 18px; border-right: 1px solid #edf1f6; background: #fff; }
    .execution-map article:last-child { border-right: 0; }
    .execution-map .active { background: #f4f9ff; box-shadow: inset 0 3px 0 #2f70f2; }
    .execution-map .future { background: #fbfcfe; }
    .execution-map span { width: 34px; height: 34px; display: grid; place-items: center; border-radius: 50%; color: #2f70f2; background: #eaf1ff; }
    .execution-map strong { color: #183655; font-size: 16px; }
    .execution-map small { color: #657086; line-height: 1.45; }
    .digital-sign-shell { display: grid; grid-template-columns: minmax(360px, .88fr) minmax(0, 1.12fr); gap: 20px; align-items: start; }
    .sign-panel { display: grid; gap: 18px; }
    .execution-form { display: grid; gap: 16px; }
    .review-gate-panel { display: grid; gap: 14px; padding: 16px; border: 1px solid #d7e6ff; border-radius: 6px; background: #f8fbff; }
    .review-gate-head { display: grid; grid-template-columns: 38px 1fr; gap: 12px; align-items: start; }
    .review-gate-head i { width: 38px; height: 38px; display: grid; place-items: center; border-radius: 50%; color: #2f70f2; background: #eaf1ff; font-size: 18px; }
    .review-gate-head strong { display: block; color: #183655; margin-bottom: 4px; }
    .review-gate-head span { color: #657086; line-height: 1.5; }
    .review-gate-status { min-height: 28px; display: inline-flex; align-items: center; color: #8a94a6; font-size: 13px; font-weight: 800; }
    .review-gate-status.ready { color: #117d71; }
    .review-confirm { display: flex; gap: 10px; align-items: start; color: #42546a; line-height: 1.5; font-weight: 700; }
    .review-confirm input { width: 18px; min-height: 18px; margin-top: 2px; padding: 0; flex: 0 0 auto; }
    .review-confirm input:disabled + span { color: #8a94a6; }
    .digital-sign-page .button:disabled { opacity: .55; cursor: not-allowed; transform: none; box-shadow: none; }
    .field-group { display: grid; gap: 6px; }
    .field-group span, .upload-inline span { color: #657086; font-size: 12px; font-weight: 800; letter-spacing: .03em; text-transform: uppercase; }
    .signature-canvas-wrap { display: grid; gap: 12px; padding: 16px; border: 1px solid #d7e6ff; border-radius: 6px; background: #f8fbff; }
    .signature-canvas-head { display: flex; justify-content: space-between; gap: 14px; align-items: start; }
    .signature-canvas-head strong { color: #183655; }
    .signature-canvas-head span { color: #657086; font-size: 13px; line-height: 1.45; }
    #signaturePad { width: 100%; height: 168px; display: block; border: 2px solid #b8c9e3; border-radius: 6px; background: #fff; cursor: crosshair; touch-action: none; }
    .signature-tools { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
    .signature-status { color: #657086; font-size: 0.95rem; }
    .signature-status.ready { color: #117d71; font-weight: 800; }
    .sign-message { min-height: 24px; display: inline-flex; align-items: center; }
    .sign-message.is-error { color: #a0002b; font-weight: 800; }
    .sign-message.is-success { color: #117d71; font-weight: 800; }
    .contract-execution-preview { padding: 22px; }
    .preview-paper { min-height: 520px; display: grid; align-content: space-between; gap: 26px; padding: 28px; border: 1px solid #d8dee8; border-radius: 6px; background: #fff; box-shadow: 0 16px 36px rgba(25, 38, 60, .08); }
    .preview-paper-header { display: grid; gap: 8px; padding-bottom: 18px; border-bottom: 2px solid #111827; }
    .preview-paper-header span { color: #8a94a6; font-size: 12px; font-weight: 900; letter-spacing: .04em; text-transform: uppercase; }
    .preview-paper-header strong { color: #183655; font-size: 22px; }
    .preview-body-lines { display: grid; gap: 10px; }
    .preview-body-lines i { display: block; height: 11px; border-radius: 999px; background: #edf1f6; }
    .preview-body-lines i:nth-child(1) { width: 92%; }
    .preview-body-lines i:nth-child(2) { width: 78%; }
    .preview-body-lines i:nth-child(3) { width: 86%; }
    .preview-signature-title { color: #80181a; font-size: 15px; font-weight: 900; text-transform: uppercase; }
    .signature-reserve-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 24px; align-items: end; }
    .signature-reserve { min-height: 190px; display: grid; align-content: end; gap: 8px; position: relative; }
    .signature-reserve-box { height: 74px; display: grid; place-items: center; border-bottom: 2px solid #111827; background: linear-gradient(180deg, #fff, #fbfcfe); overflow: hidden; }
    .signature-reserve-box img { max-width: 92%; max-height: 62px; object-fit: contain; }
    .signature-reserve-box span { color: #8a94a6; font-size: 13px; font-weight: 700; text-align: center; }
    .signature-reserve strong { color: #183655; font-size: 14px; }
    .signature-reserve small { color: #657086; line-height: 1.45; }
    .signature-reserve.client { background: transparent; }
    .signature-reserve.client .signature-reserve-box { border-color: #2f70f2; background: #f8fbff; }
    .company-seal-preview { width: 74px; height: 74px; display: grid; place-items: center; justify-self: end; margin-bottom: -10px; border: 2px solid rgba(160, 0, 43, .56); border-radius: 50%; color: #a0002b; font-size: 11px; font-weight: 900; text-align: center; text-transform: uppercase; transform: rotate(-9deg); }
    .upload-inline { display: grid; gap: 12px; }
    .upload-inline-actions { display: flex; gap: 10px; flex-wrap: wrap; }
    @media (max-width: 1100px) {
        .digital-sign-shell { grid-template-columns: 1fr; }
        .contract-execution-preview { order: -1; }
    }
    @media (max-width: 760px) {
        .execution-map, .signature-reserve-grid { grid-template-columns: 1fr; }
        .execution-map article { border-right: 0; border-bottom: 1px solid #edf1f6; }
        .execution-map article:last-child { border-bottom: 0; }
        .preview-paper { min-height: auto; padding: 18px; }
        .signature-canvas-head, .upload-inline-actions { align-items: stretch; flex-direction: column; }
        .review-gate-head { grid-template-columns: 1fr; }
    }
</style>

<div class="digital-sign-page">
    <section class="signing-stage surface">
        <div class="signing-stage-copy">
            <p>step 2 of 3</p>
            <h2><?= ui_e($contractTitle) ?></h2>
            <span>
                <?= $alreadySigned
                    ? 'The digital signing step is already complete. The contract is now waiting for company signature and seal.'
                    : 'The contract body is locked. Your signature is captured into the client space reserved by the generated contract.' ?>
            </span>
        </div>
        <div class="signing-stage-state">
            <strong>After you sign</strong>
            <span>Company countersign + seal</span>
        </div>
    </section>

    <section class="execution-map surface">
        <article class="active">
            <span><?= ui_icon('pen') ?></span>
            <strong>Client signature</strong>
            <small>Captured here and submitted through the existing digital signing endpoint.</small>
        </article>
        <article class="future">
            <span><?= ui_icon('building-check') ?></span>
            <strong>ITEC signature</strong>
            <small>Reserved for the company representative after the client step completes.</small>
        </article>
        <article class="future">
            <span><?= ui_icon('patch-check') ?></span>
            <strong>Company seal</strong>
            <small>Applied by the existing company seal workflow on the execution copy.</small>
        </article>
    </section>

<?php if ($alreadySigned): ?>
    <section class="notice-banner success">
        <strong>Client signing already completed</strong>
        <span>You can close this page. The company team will continue the final execution step.</span>
    </section>
<?php else: ?>
    <section class="digital-sign-shell">
        <div class="sign-panel">
            <div class="surface surface-pad execution-card">
                <div class="section-head compact no-border">
                    <div>
                        <p>digital signature</p>
                        <h2>Complete client signing</h2>
                    </div>
                </div>
                <form id="digitalSignForm" class="execution-form" data-sign-url="<?= BASE_URL ?>/api/contracts/<?= $contractId ?>/sign" data-preview-url="<?= BASE_URL ?>/contracts/<?= $contractId ?>/preview-signature-pdf" data-complete-url="<?= BASE_URL ?>/sign/<?= $contractId ?>?signed=digital">
                    <input type="hidden" name="role" value="client">
                    <div class="review-gate-panel">
                        <div class="review-gate-head">
                            <?= ui_icon('file-earmark-text') ?>
                            <div>
                                <strong>Review the full generated contract first</strong>
                                <span>Open the PDF generated from this contract, check the full document, then confirm the client signature space before submitting.</span>
                            </div>
                        </div>
                        <div class="form-actions">
                            <a id="reviewPdfButton" class="button ghost" href="<?= $pdfUrl ?>" target="_blank" rel="noopener" data-review-pdf><?= ui_icon('box-arrow-up-right') ?> Open Full Contract PDF</a>
                            <span id="reviewGateStatus" class="review-gate-status">PDF review required before signing</span>
                        </div>
                        <label class="review-confirm">
                            <input id="reviewConfirmed" type="checkbox" disabled>
                            <span>I have reviewed the full contract PDF and confirm this is the correct client signature space.</span>
                        </label>
                    </div>
                    <label class="field-group">
                        <span>Signer email</span>
                        <input id="signerEmail" type="email" name="signer_id" value="<?= ui_e($signingEmail) ?>" placeholder="client@example.com" required>
                    </label>
                    <label class="field-group">
                        <span>Full legal name</span>
                        <input id="typedSignature" type="text" name="typed_signature" value="<?= ui_e($clientName !== 'Client' ? $clientName : '') ?>" placeholder="Full legal name" required>
                    </label>
                    <div class="signature-canvas-wrap">
                        <div class="signature-canvas-head">
                            <div>
                                <strong>Client signature box</strong>
                                <span>Draw inside the box. The preview shows the reserved client signature position in the generated contract.</span>
                            </div>
                            <span id="signatureStatus" class="signature-status">Waiting for signature</span>
                        </div>
                        <canvas id="signaturePad" width="700" height="168" aria-label="Draw client signature"></canvas>
                        <div class="signature-tools">
                            <button type="button" class="button ghost" id="clearSignature"><?= ui_icon('eraser') ?> Clear</button>
                            <a class="button ghost" href="<?= $pdfUrl ?>" target="_blank" rel="noopener" data-review-pdf><?= ui_icon('file-earmark-pdf') ?> Review Full PDF</a>
                        </div>
                        <input type="hidden" name="signature_data" id="signatureData" value="">
                    </div>
                    <div class="form-actions">
                        <button id="submitSignatureButton" class="button" type="submit" disabled><?= ui_icon('check-circle') ?> Submit Digital Signature</button>
                        <span id="digitalSignMessage" class="muted-copy sign-message"></span>
                    </div>
                </form>
            </div>

            <div class="surface surface-pad execution-card">
                <div class="section-head compact no-border">
                    <div>
                        <p>signed PDF upload</p>
                        <h2>Hard-copy alternative</h2>
                    </div>
                </div>
                <form class="upload-inline" action="<?= BASE_URL ?>/contracts/<?= $contractId ?>/upload-signed-copy" method="post" enctype="multipart/form-data">
                    <label class="field-group">
                        <span>Upload signed PDF or scan</span>
                        <input type="file" name="signed_copy" accept=".pdf,.png,.jpg,.jpeg" required>
                    </label>
                    <div class="upload-inline-actions">
                        <a class="button ghost" href="<?= $pdfUrl ?>" target="_blank" rel="noopener" data-review-pdf><?= ui_icon('download') ?> Download PDF</a>
                        <button class="button success" type="submit"><?= ui_icon('cloud-upload') ?> Upload Signed Copy</button>
                    </div>
                </form>
            </div>
        </div>

        <aside class="page-stack">
            <div class="surface surface-pad execution-card">
                <div class="section-head compact no-border">
                    <div>
                        <p>placement preview</p>
                        <h2>Contract execution block</h2>
                    </div>
                </div>
                <div class="contract-execution-preview">
                    <div class="preview-paper">
                        <div class="preview-paper-header">
                            <span>Generated contract</span>
                            <strong><?= ui_e($contractTitle) ?></strong>
                        </div>
                        <div class="preview-body-lines" aria-hidden="true">
                            <i></i><i></i><i></i>
                        </div>
                        <div>
                            <div class="preview-signature-title">Signatures</div>
                            <div class="signature-reserve-grid">
                                <div class="signature-reserve client">
                                    <div class="signature-reserve-box">
                                        <img id="signaturePreviewImage" alt="Client signature preview" hidden>
                                        <span id="signaturePreviewHint">Client signature appears here</span>
                                    </div>
                                    <strong>Client Signature</strong>
                                    <small id="previewClientName"><?= ui_e($clientName) ?></small>
                                    <small id="previewClientEmail"><?= ui_e($signingEmail ?: 'client@example.com') ?></small>
                                    <small>Date: <?= ui_e(date('M d, Y')) ?></small>
                                </div>
                                <div class="signature-reserve">
                                    <div class="company-seal-preview">Company<br>Seal</div>
                                    <div class="signature-reserve-box">
                                        <span>Reserved for ITEC signatory</span>
                                    </div>
                                    <strong>ITEC Solutions</strong>
                                    <small>Authorized Signatory</small>
                                    <small>Date: _______________</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
                    <li>The full generated PDF was opened and reviewed.</li>
                    <li>Your drawn signature is visible in the client signature box.</li>
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
        </aside>
    </section>

    <script>
    (function () {
        const form = document.getElementById('digitalSignForm');
        const canvas = document.getElementById('signaturePad');
        if (!form || !canvas) return;

        const ctx = canvas.getContext('2d');
        const clearBtn = document.getElementById('clearSignature');
        const submitButton = document.getElementById('submitSignatureButton');
        const reviewConfirmed = document.getElementById('reviewConfirmed');
        const reviewGateStatus = document.getElementById('reviewGateStatus');
        const signatureStatus = document.getElementById('signatureStatus');
        const signatureDataInput = document.getElementById('signatureData');
        const signaturePreviewImage = document.getElementById('signaturePreviewImage');
        const signaturePreviewHint = document.getElementById('signaturePreviewHint');
        const typedSignature = document.getElementById('typedSignature');
        const signerEmail = document.getElementById('signerEmail');
        const previewClientName = document.getElementById('previewClientName');
        const previewClientEmail = document.getElementById('previewClientEmail');

        let isDrawing = false;
        let hasSignature = false;
        let hasReviewedPdf = false;
        let lastPoint = null;

        function setMessage(text, state) {
            const message = document.getElementById('digitalSignMessage');
            message.textContent = text || '';
            message.className = 'muted-copy sign-message' + (state ? ' is-' + state : '');
        }

        function prepareCanvas() {
            const rect = canvas.getBoundingClientRect();
            const ratio = window.devicePixelRatio || 1;
            const snapshot = hasSignature ? canvas.toDataURL('image/png') : null;
            canvas.width = Math.max(1, Math.round(rect.width * ratio));
            canvas.height = Math.max(1, Math.round(rect.height * ratio));
            ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
            ctx.lineWidth = 2.4;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.strokeStyle = '#111827';

            if (snapshot) {
                const image = new Image();
                image.onload = function () {
                    ctx.drawImage(image, 0, 0, rect.width, rect.height);
                    refreshPreview();
                };
                image.src = snapshot;
            }
        }

        function updateSubmitState() {
            submitButton.disabled = !(hasSignature && hasReviewedPdf && reviewConfirmed.checked);
        }

        function markPdfReviewed() {
            hasReviewedPdf = true;
            reviewConfirmed.disabled = false;
            reviewGateStatus.textContent = 'PDF opened. Confirm the review checkbox to submit.';
            reviewGateStatus.classList.add('ready');
            updateSubmitState();
        }

        function appendPreviewField(previewForm, name, value) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value || '';
            previewForm.appendChild(input);
        }

        function openPreviewPdf(event) {
            event.preventDefault();
            markPdfReviewed();

            const previewForm = document.createElement('form');
            previewForm.method = 'POST';
            previewForm.action = form.dataset.previewUrl;
            previewForm.target = '_blank';
            previewForm.style.display = 'none';

            appendPreviewField(previewForm, 'role', 'client');
            appendPreviewField(previewForm, 'signer_id', signerEmail.value.trim());
            appendPreviewField(previewForm, 'typed_signature', typedSignature.value.trim());
            appendPreviewField(previewForm, 'signature_data', hasSignature ? signatureDataUrl() : '');

            document.body.appendChild(previewForm);
            previewForm.submit();
            window.setTimeout(function () {
                previewForm.remove();
            }, 1000);
        }

        function point(event) {
            const rect = canvas.getBoundingClientRect();
            return {
                x: event.clientX - rect.left,
                y: event.clientY - rect.top
            };
        }

        function refreshPreview() {
            previewClientName.textContent = typedSignature.value.trim() || 'Client';
            previewClientEmail.textContent = signerEmail.value.trim() || 'client@example.com';

            if (!hasSignature) {
                signaturePreviewImage.hidden = true;
                signaturePreviewHint.hidden = false;
                return;
            }

            signaturePreviewImage.src = canvas.toDataURL('image/png');
            signaturePreviewImage.hidden = false;
            signaturePreviewHint.hidden = true;
            updateSubmitState();
        }

        function signatureDataUrl() {
            const output = document.createElement('canvas');
            output.width = canvas.width;
            output.height = canvas.height;
            const outputCtx = output.getContext('2d');
            outputCtx.fillStyle = '#fff';
            outputCtx.fillRect(0, 0, output.width, output.height);
            outputCtx.drawImage(canvas, 0, 0);
            return output.toDataURL('image/jpeg', 0.92);
        }

        function beginSignature(event) {
            event.preventDefault();
            isDrawing = true;
            hasSignature = true;
            lastPoint = point(event);
            ctx.beginPath();
            ctx.fillStyle = '#111827';
            ctx.arc(lastPoint.x, lastPoint.y, 0.9, 0, Math.PI * 2);
            ctx.fill();
            ctx.beginPath();
            ctx.moveTo(lastPoint.x, lastPoint.y);
            canvas.setPointerCapture?.(event.pointerId);
            signatureStatus.textContent = 'Signature captured';
            signatureStatus.classList.add('ready');
            refreshPreview();
        }

        function drawSignature(event) {
            if (!isDrawing) return;
            event.preventDefault();
            const nextPoint = point(event);
            ctx.lineTo(nextPoint.x, nextPoint.y);
            ctx.stroke();
            lastPoint = nextPoint;
            refreshPreview();
        }

        function endSignature(event) {
            if (!isDrawing) return;
            event.preventDefault();
            isDrawing = false;
            ctx.closePath();
            canvas.releasePointerCapture?.(event.pointerId);
            refreshPreview();
        }

        prepareCanvas();
        refreshPreview();
        window.addEventListener('resize', prepareCanvas);
        typedSignature.addEventListener('input', refreshPreview);
        signerEmail.addEventListener('input', refreshPreview);
        reviewConfirmed.addEventListener('change', updateSubmitState);
        document.querySelectorAll('[data-review-pdf]').forEach(function (link) {
            link.addEventListener('click', openPreviewPdf);
        });
        canvas.addEventListener('pointerdown', beginSignature);
        canvas.addEventListener('pointermove', drawSignature);
        canvas.addEventListener('pointerup', endSignature);
        canvas.addEventListener('pointerleave', endSignature);
        canvas.addEventListener('pointercancel', endSignature);

        clearBtn.addEventListener('click', function () {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            hasSignature = false;
            lastPoint = null;
            signatureStatus.textContent = 'Waiting for signature';
            signatureStatus.classList.remove('ready');
            signatureDataInput.value = '';
            refreshPreview();
            updateSubmitState();
            setMessage('', '');
        });

        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            if (!hasReviewedPdf || !reviewConfirmed.checked) {
                setMessage('Please review the full contract PDF and confirm the review before submitting.', 'error');
                updateSubmitState();
                return;
            }

            if (!hasSignature) {
                setMessage('Please draw your signature before submitting.', 'error');
                updateSubmitState();
                return;
            }

            signatureDataInput.value = signatureDataUrl();
            setMessage('Submitting your digital signature...', '');
            submitButton.disabled = true;

            try {
                const response = await fetch(form.dataset.signUrl, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { Accept: 'application/json' }
                });
                const result = await (window.ContractUi ? ContractUi.responseJson(response) : response.json());
                if (!response.ok || result.success === false) {
                    throw new Error(result.message || result.error || 'Signing failed');
                }
                setMessage('Signature submitted successfully. Redirecting...', 'success');
                window.location.href = form.dataset.completeUrl;
            } catch (error) {
                updateSubmitState();
                setMessage(error.message || 'Signing failed', 'error');
            }
        });
    })();
    </script>
<?php endif; ?>
</div>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/app.php';
