<?php
$basePath = BASE_URL ?? '/itec_contract_system';
$contract = $contract ?? [];
$signatures = $signatures ?? [];
$contractId = (int) ($contract_id ?? ($contract['id'] ?? 1));
$contractTitle = $contract['title'] ?? 'Contract';
$contractContent = $contract['content'] ?? '<p>Contract content is not available.</p>';
$signingEmail = $signing_email ?? ($contract['client_email'] ?? '');
$clientName = $contract['client_name'] ?? 'Client';
$state = strtoupper($contract['signing_state'] ?? 'AWAITING_CLIENT');
$alreadySigned = (bool) ($already_signed ?? false);
$pdfUrl = $basePath . '/contracts/' . $contractId . '/print-pdf';
$previewPdfUrl = $basePath . '/contracts/' . $contractId . '/preview-signature-pdf';
$apiSignUrl = $basePath . '/api/contracts/' . $contractId . '/sign';
$hardcopyUploadUrl = $basePath . '/contracts/' . $contractId . '/upload-contract';
$successUrl = $basePath . '/sign/success/' . $contractId;

$clientSignature = null;
foreach ($signatures as $signature) {
    if (($signature['signer_role'] ?? '') === 'client') {
        $clientSignature = $signature;
    }
}

$e = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$statusLabel = [
    'DRAFT' => 'Draft',
    'AWAITING_CLIENT' => 'Awaiting Client Signature',
    'CLIENT_SIGNED' => 'Client Signed',
    'AWAITING_COMPANY' => 'Awaiting Company Signature',
    'FULLY_SIGNED' => 'Fully Signed',
][$state] ?? $state;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Contract</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; color: #26384d; background: #f3f6fb; font-family: "Segoe UI", Arial, sans-serif; }
        a { color: inherit; }
        .sign-shell { max-width: 1520px; margin: 0 auto; padding: 22px 24px 36px; }
        .top-strip { min-height: 74px; display: flex; justify-content: space-between; gap: 18px; align-items: center; margin-bottom: 18px; padding: 16px 18px; border: 1px solid #d8dee8; border-radius: 6px; background: #fff; box-shadow: 0 1px 2px rgba(25, 38, 60, .05); }
        .brand-lockup { display: flex; gap: 13px; align-items: center; min-width: 0; }
        .brand-mark { width: 42px; height: 42px; display: grid; place-items: center; flex: 0 0 auto; border-radius: 6px; color: #fff; background: #80181a; font-size: 22px; }
        .brand-lockup p { margin: 0 0 4px; color: #8a94a6; font-size: 12px; font-weight: 900; letter-spacing: .05em; text-transform: uppercase; }
        .brand-lockup h1 { margin: 0; color: #183655; font-size: 24px; line-height: 1.2; overflow-wrap: anywhere; }
        .state-pill { min-height: 32px; display: inline-flex; align-items: center; gap: 8px; padding: 0 12px; border-radius: 999px; color: #946200; background: #fff4db; font-size: 12px; font-weight: 900; text-transform: uppercase; white-space: nowrap; }
        .state-pill.done { color: #117d71; background: #e9f8ef; }
        .sign-layout { display: grid; grid-template-columns: minmax(0, 1fr) 410px; gap: 18px; align-items: start; }
        .surface { border: 1px solid #d8dee8; border-radius: 6px; background: #fff; box-shadow: 0 1px 2px rgba(25, 38, 60, .05); }
        .document-frame { min-width: 0; overflow: hidden; }
        .document-toolbar { display: flex; justify-content: space-between; gap: 14px; align-items: center; min-height: 62px; padding: 14px 18px; border-bottom: 1px solid #edf1f6; background: #fbfcfe; }
        .document-toolbar strong { display: block; color: #183655; font-size: 17px; }
        .document-toolbar span { color: #657086; font-size: 13px; }
        .toolbar-actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .button { min-height: 40px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 0 14px; border: 0; border-radius: 4px; color: #fff; background: #2f70f2; font: 800 13px "Segoe UI", Arial, sans-serif; text-decoration: none; cursor: pointer; }
        .button.secondary { color: #2f70f2; background: #eaf1ff; }
        .button.success { background: #117d71; }
        .button.warn { background: #c47a00; }
        .button:disabled { opacity: .55; cursor: not-allowed; }
        .contract-paper { max-width: 920px; min-height: 860px; margin: 20px auto 28px; padding: 38px 44px; border: 1px solid #d8dee8; background: #fff; box-shadow: 0 14px 38px rgba(25, 38, 60, .08); }
        .paper-head { margin-bottom: 24px; padding-bottom: 16px; border-bottom: 3px solid #111827; text-align: center; }
        .paper-head p { margin: 0 0 8px; color: #80181a; font-size: 13px; font-weight: 900; letter-spacing: .06em; text-transform: uppercase; }
        .paper-head h2 { margin: 0; color: #183655; font-size: 27px; overflow-wrap: anywhere; }
        .meta-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; margin-bottom: 22px; }
        .meta-grid div { min-height: 56px; padding: 10px 12px; border: 1px solid #edf1f6; background: #fbfcfe; }
        .meta-grid span { display: block; color: #8a94a6; font-size: 11px; font-weight: 900; letter-spacing: .04em; text-transform: uppercase; }
        .meta-grid strong { display: block; margin-top: 4px; color: #183655; overflow-wrap: anywhere; }
        .readonly-contract { color: #1f3147; line-height: 1.68; }
        .readonly-contract h1, .readonly-contract h2, .readonly-contract h3 { color: #183655; }
        .readonly-contract p { margin: 0 0 13px; }
        .readonly-contract [data-draft-section] { margin-bottom: 18px; }
        .signature-reserve { margin-top: 32px; page-break-inside: avoid; }
        .signature-reserve h3 { margin: 0 0 14px; color: #80181a; font-size: 15px; font-weight: 900; text-transform: uppercase; }
        .signature-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 26px; }
        .signature-line { display: grid; gap: 7px; min-height: 132px; align-content: end; }
        .signature-slot { height: 76px; display: grid; place-items: center; border-bottom: 2px solid #111827; background: #fbfcfe; color: #8a94a6; font-size: 13px; font-weight: 700; text-align: center; }
        .signature-slot img { max-width: 88%; max-height: 62px; object-fit: contain; }
        .signature-line strong { color: #183655; }
        .signature-line small { color: #657086; overflow-wrap: anywhere; }
        .sign-panel { position: sticky; top: 18px; display: grid; gap: 14px; }
        .panel-section { padding: 16px; }
        .panel-head { display: grid; gap: 5px; margin-bottom: 14px; }
        .panel-head p { margin: 0; color: #8a94a6; font-size: 12px; font-weight: 900; letter-spacing: .05em; text-transform: uppercase; }
        .panel-head h2 { margin: 0; color: #183655; font-size: 19px; }
        .panel-head span { color: #657086; line-height: 1.45; }
        .method-tabs { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 14px; }
        .method-tab { min-height: 58px; display: grid; align-content: center; justify-items: start; gap: 3px; padding: 10px; border: 1px solid #d8dee8; border-radius: 5px; color: #42546a; background: #fff; cursor: pointer; text-align: left; }
        .method-tab strong { display: flex; gap: 7px; align-items: center; color: #183655; font-size: 14px; }
        .method-tab small { color: #657086; line-height: 1.25; }
        .method-tab.active { border-color: #9fc2f4; background: #f4f9ff; box-shadow: inset 0 3px 0 #2f70f2; }
        .signer-details { display: grid; gap: 12px; margin-bottom: 14px; }
        .method-body { display: none; }
        .method-body.active { display: grid; gap: 13px; }
        .field { display: grid; gap: 6px; }
        .field span, .consent span { color: #657086; font-size: 12px; font-weight: 900; letter-spacing: .04em; text-transform: uppercase; }
        .field input { width: 100%; min-height: 40px; padding: 0 10px; border: 1px solid #cbd5e1; border-radius: 4px; color: #26384d; background: #fff; font: 14px "Segoe UI", Arial, sans-serif; }
        .field input:focus { outline: 2px solid #bcd5ff; border-color: #2f70f2; }
        .consent { display: flex; gap: 9px; align-items: start; padding: 11px; border: 1px solid #edf1f6; border-radius: 5px; background: #fbfcfe; }
        .consent input { width: 18px; height: 18px; margin-top: 2px; flex: 0 0 auto; }
        .consent span { color: #42546a; line-height: 1.45; text-transform: none; letter-spacing: 0; font-weight: 700; }
        .canvas-shell { display: grid; gap: 9px; padding: 12px; border: 1px solid #d7e6ff; border-radius: 5px; background: #f8fbff; }
        .canvas-head { display: flex; justify-content: space-between; gap: 10px; align-items: center; }
        .canvas-head strong { color: #183655; }
        .canvas-head small { color: #657086; font-weight: 800; }
        .canvas-head small.ready { color: #117d71; }
        #signaturePad { width: 100%; height: 156px; display: block; border: 2px solid #b8c9e3; border-radius: 4px; background: #fff; cursor: crosshair; touch-action: none; }
        .canvas-tools { display: flex; justify-content: space-between; gap: 8px; align-items: center; }
        .small-link { color: #2f70f2; font-size: 13px; font-weight: 800; text-decoration: none; }
        .message { min-height: 22px; color: #657086; font-size: 13px; line-height: 1.45; }
        .message.error { color: #9f1d1d; font-weight: 800; }
        .message.success { color: #117d71; font-weight: 800; }
        .upload-drop { display: grid; gap: 9px; padding: 16px; border: 1px dashed #cbd5e1; border-radius: 5px; background: #fbfcfe; text-align: center; }
        .upload-drop i { color: #c47a00; font-size: 28px; }
        .upload-drop input { width: 100%; }
        .review-row { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .preview-upload { color: #2f70f2; background: #eaf1ff; }
        .preview-upload.disabled { opacity: .48; pointer-events: none; }
        .done-panel { border-color: #bde5cf; background: #effbf4; }
        .done-panel strong { color: #117d71; }
        .next-list { margin: 0; padding: 0; list-style: none; display: grid; gap: 9px; }
        .next-list li { display: flex; gap: 9px; align-items: start; color: #42546a; line-height: 1.45; }
        .next-list i { color: #117d71; margin-top: 2px; }
        @media (max-width: 1080px) {
            .sign-layout { grid-template-columns: 1fr; }
            .sign-panel { position: static; grid-row: 1; }
        }
        @media (max-width: 720px) {
            .sign-shell { padding: 14px; }
            .top-strip, .document-toolbar, .canvas-head, .canvas-tools { align-items: stretch; flex-direction: column; }
            .brand-lockup h1 { font-size: 20px; }
            .contract-paper { min-height: auto; margin: 0; padding: 24px 18px; border-left: 0; border-right: 0; box-shadow: none; }
            .meta-grid, .signature-grid, .method-tabs, .review-row { grid-template-columns: 1fr; }
            .toolbar-actions .button { width: 100%; }
        }
    </style>
</head>
<body>
    <main class="sign-shell">
        <header class="top-strip">
            <div class="brand-lockup">
                <span class="brand-mark"><i class="bi bi-file-earmark-text"></i></span>
                <div>
                    <p>secure client signing</p>
                    <h1><?= $e($contractTitle) ?></h1>
                </div>
            </div>
            <span class="state-pill <?= $alreadySigned ? 'done' : '' ?>">
                <i class="bi <?= $alreadySigned ? 'bi-check-circle' : 'bi-clock-history' ?>"></i>
                <?= $e($statusLabel) ?>
            </span>
        </header>

        <div class="sign-layout">
            <section class="document-frame surface">
                <div class="document-toolbar">
                    <div>
                        <strong>Read-only contract</strong>
                        <span>Review the full contract before choosing a signing method.</span>
                    </div>
                    <div class="toolbar-actions">
                        <a class="button secondary" href="<?= $e($previewPdfUrl) ?>" target="_blank" rel="noopener" data-review-pdf><i class="bi bi-file-earmark-pdf"></i> View PDF</a>
                    </div>
                </div>

                <article class="contract-paper">
                    <div class="paper-head">
                        <p>generated contract</p>
                        <h2><?= $e($contractTitle) ?></h2>
                    </div>
                    <div class="meta-grid">
                        <div><span>Contract ref</span><strong>#<?= $contractId ?></strong></div>
                        <div><span>Status</span><strong><?= $e($statusLabel) ?></strong></div>
                        <div><span>Client</span><strong id="paperMetaClientName"><?= $e($alreadySigned ? ($clientName ?: 'Client') : 'Enter full legal name') ?></strong></div>
                        <div><span>Email</span><strong id="paperMetaClientEmail"><?= $e($signingEmail ?: 'Enter email address') ?></strong></div>
                    </div>
                    <div class="readonly-contract">
                        <?= $contractContent ?>
                    </div>
                    <section class="signature-reserve" aria-label="Signature section preview">
                        <h3>Signatures</h3>
                        <div class="signature-grid">
                            <div class="signature-line">
                                <div class="signature-slot" id="paperClientSignatureSlot">
                                    <span id="paperSignatureHint"><?= $clientSignature ? 'Client signature recorded' : 'Client signature appears here' ?></span>
                                    <img id="paperSignaturePreview" alt="Client signature preview" hidden>
                                </div>
                                <strong>Client Signature</strong>
                                <small id="paperClientName"><?= $e($alreadySigned ? ($clientName ?: 'Client') : 'Enter full legal name') ?></small>
                                <small id="paperClientEmail"><?= $e($signingEmail ?: 'client@example.com') ?></small>
                            </div>
                            <div class="signature-line">
                                <div class="signature-slot">Reserved for ITEC signatory</div>
                                <strong>ITEC Solutions</strong>
                                <small>Authorized Signatory</small>
                                <small>Date: _______________</small>
                            </div>
                        </div>
                    </section>
                </article>
            </section>

            <aside class="sign-panel">
                <?php if ($alreadySigned): ?>
                    <section class="surface panel-section done-panel">
                        <div class="panel-head">
                            <p>client task complete</p>
                            <h2>Thank you, this step is finished</h2>
                            <span>The contract is now with the company team for countersignature and seal.</span>
                        </div>
                        <a class="button success" href="<?= $e($pdfUrl) ?>" target="_blank" rel="noopener"><i class="bi bi-file-earmark-pdf"></i> View Signed PDF</a>
                    </section>
                <?php else: ?>
                    <section class="surface panel-section">
                        <div class="panel-head">
                            <p>signing options</p>
                            <h2>Choose how to sign</h2>
                            <span>Your signature is stored in the reserved client signature space in the generated document.</span>
                        </div>

                        <div class="method-tabs" role="tablist" aria-label="Signing methods">
                            <button class="method-tab active" type="button" data-method-tab="digital">
                                <strong><i class="bi bi-pen"></i> Digital</strong>
                                <small>Draw and submit here</small>
                            </button>
                            <button class="method-tab" type="button" data-method-tab="hardcopy">
                                <strong><i class="bi bi-printer"></i> Hard copy</strong>
                                <small>Download and upload scan</small>
                            </button>
                        </div>

                        <div class="signer-details" aria-label="Client signing details">
                            <label class="field">
                                <span>Signer email</span>
                                <input id="signerEmail" type="email" value="<?= $e($signingEmail) ?>" placeholder="client@example.com" required>
                            </label>

                            <label class="field">
                                <span>Full legal name</span>
                                <input id="typedSignature" type="text" value="" placeholder="Write your full legal name" required>
                            </label>
                        </div>

                        <form id="digitalSignForm" class="method-body active" data-success-url="<?= $e($successUrl) ?>" action="<?= $e($apiSignUrl) ?>" method="post">
                            <input type="hidden" name="role" value="client">
                            <input type="hidden" name="signature_data" id="signatureData" value="">

                            <div class="canvas-shell">
                                <div class="canvas-head">
                                    <strong>Draw client signature</strong>
                                    <small id="signatureStatus">Waiting for signature</small>
                                </div>
                                <canvas id="signaturePad" width="720" height="156" aria-label="Draw signature"></canvas>
                                <div class="canvas-tools">
                                    <button class="button secondary" type="button" id="clearSignature"><i class="bi bi-eraser"></i> Clear</button>
                                    <a class="small-link" href="<?= $e($previewPdfUrl) ?>" target="_blank" rel="noopener" data-review-pdf data-requires-signature="1"><i class="bi bi-box-arrow-up-right"></i> Review Signed PDF</a>
                                </div>
                            </div>

                            <label class="consent">
                                <input id="reviewConfirmed" type="checkbox">
                                <span>I have reviewed the read-only contract and confirm I am signing this agreement.</span>
                            </label>

                            <button id="submitSignature" class="button success" type="submit" disabled><i class="bi bi-check-circle"></i> Submit Signature</button>
                            <div id="digitalMessage" class="message" role="status"></div>
                        </form>

                        <form id="hardcopyForm" class="method-body" data-success-url="<?= $e($successUrl) ?>" action="<?= $e($hardcopyUploadUrl) ?>" method="post" enctype="multipart/form-data">
                            <div class="upload-drop">
                                <i class="bi bi-cloud-upload"></i>
                                <strong>Upload signed copy</strong>
                                <span>Download the PDF, print and sign it, then upload the scan or signed PDF.</span>
                                <input id="hardcopyFile" type="file" name="signed_copy" accept=".pdf,.png,.jpg,.jpeg" required>
                            </div>
                            <div class="review-row">
                                <a class="button warn" href="<?= $e($previewPdfUrl) ?>" target="_blank" rel="noopener" data-review-pdf data-review-mode="hardcopy"><i class="bi bi-download"></i> View PDF To Print</a>
                                <a id="hardcopyFilePreview" class="button preview-upload disabled" href="#" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right"></i> Preview Upload</a>
                            </div>
                            <button id="submitHardcopy" class="button success" type="submit"><i class="bi bi-cloud-upload"></i> Upload Signed Copy</button>
                            <div id="hardcopyMessage" class="message" role="status"></div>
                        </form>
                    </section>
                <?php endif; ?>

                <section class="surface panel-section">
                    <div class="panel-head">
                        <p>after signing</p>
                        <h2>What happens next</h2>
                    </div>
                    <ul class="next-list">
                        <li><i class="bi bi-check-circle"></i><span>Your client signing task is marked complete.</span></li>
                        <li><i class="bi bi-lock"></i><span>The contract body stays read-only while execution continues.</span></li>
                        <li><i class="bi bi-building-check"></i><span>ITEC countersigns and applies the company seal.</span></li>
                    </ul>
                </section>
            </aside>
        </div>
    </main>

    <?php if (!$alreadySigned): ?>
    <script>
    (function () {
        const tabs = document.querySelectorAll('[data-method-tab]');
        const digitalForm = document.getElementById('digitalSignForm');
        const hardcopyForm = document.getElementById('hardcopyForm');
        const canvas = document.getElementById('signaturePad');
        const ctx = canvas.getContext('2d');
        const typedSignature = document.getElementById('typedSignature');
        const signerEmail = document.getElementById('signerEmail');
        const reviewConfirmed = document.getElementById('reviewConfirmed');
        const submitSignature = document.getElementById('submitSignature');
        const signatureStatus = document.getElementById('signatureStatus');
        const signatureData = document.getElementById('signatureData');
        const paperSignaturePreview = document.getElementById('paperSignaturePreview');
        const paperSignatureHint = document.getElementById('paperSignatureHint');
        const paperMetaClientName = document.getElementById('paperMetaClientName');
        const paperMetaClientEmail = document.getElementById('paperMetaClientEmail');
        const paperClientName = document.getElementById('paperClientName');
        const paperClientEmail = document.getElementById('paperClientEmail');
        const hardcopyFile = document.getElementById('hardcopyFile');
        const hardcopyFilePreview = document.getElementById('hardcopyFilePreview');
        let hardcopyPreviewUrl = '';
        let isDrawing = false;
        let hasSignature = false;

        function setMessage(id, text, type) {
            const element = document.getElementById(id);
            element.textContent = text || '';
            element.className = 'message' + (type ? ' ' + type : '');
        }

        async function responseJson(response) {
            const text = await response.text();
            try {
                return text ? JSON.parse(text) : {};
            } catch (error) {
                return { success: response.ok, message: text.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim() };
            }
        }

        function setActiveMethod(method) {
            tabs.forEach(function (tab) {
                tab.classList.toggle('active', tab.dataset.methodTab === method);
            });
            digitalForm.classList.toggle('active', method === 'digital');
            hardcopyForm.classList.toggle('active', method === 'hardcopy');
        }

        function signerNameValue() {
            return typedSignature.value.trim();
        }

        function signerEmailValue() {
            return signerEmail.value.trim();
        }

        function validateSignerDetails(messageId) {
            if (!signerEmailValue()) {
                setMessage(messageId, 'Please enter the signer email before continuing.', 'error');
                signerEmail.focus();
                return false;
            }

            if (!signerNameValue()) {
                setMessage(messageId, 'Please write the client full legal name before continuing.', 'error');
                typedSignature.focus();
                return false;
            }

            return true;
        }

        function appendSignerDetails(body) {
            body.set('signer_id', signerEmailValue());
            body.set('typed_signature', signerNameValue());
        }

        function appendPreviewField(previewForm, name, value) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value || '';
            previewForm.appendChild(input);
        }

        function openReviewPdf(event) {
            event.preventDefault();
            const activeMethod = hardcopyForm.classList.contains('active') ? 'hardcopy' : 'digital';
            const mode = event.currentTarget.dataset.reviewMode || activeMethod;
            const messageId = mode === 'hardcopy' ? 'hardcopyMessage' : 'digitalMessage';

            if (!validateSignerDetails(messageId)) {
                return;
            }

            if (event.currentTarget.dataset.requiresSignature === '1' && !hasSignature) {
                setMessage(messageId, 'Please draw your signature first so the PDF can show how it will appear.', 'error');
                return;
            }

            const previewForm = document.createElement('form');
            previewForm.method = 'POST';
            previewForm.action = event.currentTarget.href;
            previewForm.target = '_blank';
            previewForm.style.display = 'none';

            appendPreviewField(previewForm, 'role', 'client');
            appendPreviewField(previewForm, 'signer_id', signerEmailValue());
            appendPreviewField(previewForm, 'typed_signature', signerNameValue());
            appendPreviewField(previewForm, 'signature_data', mode === 'hardcopy' ? '' : (hasSignature ? signatureDataUrl() : ''));

            document.body.appendChild(previewForm);
            previewForm.submit();
            window.setTimeout(function () {
                previewForm.remove();
            }, 1000);
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
                const img = new Image();
                img.onload = function () {
                    ctx.drawImage(img, 0, 0, rect.width, rect.height);
                    refreshPreview();
                };
                img.src = snapshot;
            }
        }

        function point(event) {
            const rect = canvas.getBoundingClientRect();
            return { x: event.clientX - rect.left, y: event.clientY - rect.top };
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

        function refreshPreview() {
            const name = signerNameValue() || 'Enter full legal name';
            const email = signerEmailValue() || 'Enter email address';
            paperMetaClientName.textContent = name;
            paperMetaClientEmail.textContent = email;
            paperClientName.textContent = name;
            paperClientEmail.textContent = email;
            submitSignature.disabled = !(hasSignature && reviewConfirmed.checked && signerNameValue() && signerEmailValue());
            if (!hasSignature) {
                paperSignaturePreview.hidden = true;
                paperSignatureHint.hidden = false;
                return;
            }
            paperSignaturePreview.src = signatureDataUrl();
            paperSignaturePreview.hidden = false;
            paperSignatureHint.hidden = true;
        }

        function begin(event) {
            event.preventDefault();
            isDrawing = true;
            hasSignature = true;
            const current = point(event);
            ctx.beginPath();
            ctx.moveTo(current.x, current.y);
            ctx.arc(current.x, current.y, 0.9, 0, Math.PI * 2);
            ctx.fill();
            ctx.beginPath();
            ctx.moveTo(current.x, current.y);
            canvas.setPointerCapture?.(event.pointerId);
            signatureStatus.textContent = 'Signature captured';
            signatureStatus.classList.add('ready');
            refreshPreview();
        }

        function draw(event) {
            if (!isDrawing) return;
            event.preventDefault();
            const current = point(event);
            ctx.lineTo(current.x, current.y);
            ctx.stroke();
            refreshPreview();
        }

        function end(event) {
            if (!isDrawing) return;
            event.preventDefault();
            isDrawing = false;
            ctx.closePath();
            canvas.releasePointerCapture?.(event.pointerId);
            refreshPreview();
        }

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                setActiveMethod(tab.dataset.methodTab);
            });
        });

        prepareCanvas();
        refreshPreview();
        window.addEventListener('resize', prepareCanvas);
        typedSignature.addEventListener('input', refreshPreview);
        signerEmail.addEventListener('input', refreshPreview);
        reviewConfirmed.addEventListener('change', refreshPreview);
        document.querySelectorAll('[data-review-pdf]').forEach(function (link) {
            link.addEventListener('click', openReviewPdf);
        });
        canvas.addEventListener('pointerdown', begin);
        canvas.addEventListener('pointermove', draw);
        canvas.addEventListener('pointerup', end);
        canvas.addEventListener('pointerleave', end);
        canvas.addEventListener('pointercancel', end);

        document.getElementById('clearSignature').addEventListener('click', function () {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            hasSignature = false;
            signatureData.value = '';
            signatureStatus.textContent = 'Waiting for signature';
            signatureStatus.classList.remove('ready');
            setMessage('digitalMessage', '', '');
            refreshPreview();
        });

        digitalForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            if (!validateSignerDetails('digitalMessage')) {
                refreshPreview();
                return;
            }
            if (!hasSignature) {
                setMessage('digitalMessage', 'Please draw your signature before submitting.', 'error');
                return;
            }
            signatureData.value = signatureDataUrl();
            submitSignature.disabled = true;
            setMessage('digitalMessage', 'Submitting your signature...', '');

            try {
                const body = new FormData(digitalForm);
                appendSignerDetails(body);
                const response = await fetch(digitalForm.action, {
                    method: 'POST',
                    body: body,
                    headers: { Accept: 'application/json' }
                });
                const result = await responseJson(response);
                if (!response.ok || result.success === false) {
                    throw new Error(result.error || result.message || 'Signing failed');
                }
                setMessage('digitalMessage', 'Signature submitted. Redirecting...', 'success');
                window.location.href = digitalForm.dataset.successUrl;
            } catch (error) {
                refreshPreview();
                setMessage('digitalMessage', error.message || 'Signing failed', 'error');
            }
        });

        hardcopyFile.addEventListener('change', function () {
            if (hardcopyPreviewUrl) {
                URL.revokeObjectURL(hardcopyPreviewUrl);
                hardcopyPreviewUrl = '';
            }

            const file = hardcopyFile.files && hardcopyFile.files[0];
            if (!file) {
                hardcopyFilePreview.href = '#';
                hardcopyFilePreview.classList.add('disabled');
                return;
            }

            hardcopyPreviewUrl = URL.createObjectURL(file);
            hardcopyFilePreview.href = hardcopyPreviewUrl;
            hardcopyFilePreview.classList.remove('disabled');
        });

        hardcopyForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            if (!validateSignerDetails('hardcopyMessage')) {
                return;
            }
            const submitHardcopy = document.getElementById('submitHardcopy');
            submitHardcopy.disabled = true;
            setMessage('hardcopyMessage', 'Uploading signed copy...', '');

            try {
                const body = new FormData(hardcopyForm);
                appendSignerDetails(body);
                const response = await fetch(hardcopyForm.action, {
                    method: 'POST',
                    body: body,
                    headers: { Accept: 'application/json' }
                });
                const result = await responseJson(response);
                if (!response.ok || result.success === false) {
                    throw new Error(result.error || result.message || 'Upload failed');
                }
                setMessage('hardcopyMessage', 'Signed copy uploaded. Redirecting...', 'success');
                window.location.href = hardcopyForm.dataset.successUrl;
            } catch (error) {
                submitHardcopy.disabled = false;
                setMessage('hardcopyMessage', error.message || 'Upload failed', 'error');
            }
        });
    })();
    </script>
    <?php endif; ?>
</body>
</html>
