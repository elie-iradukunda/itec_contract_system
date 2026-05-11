<?php
$basePath = defined('BASE_URL') ? BASE_URL : '/itec_contract_system';
$state = strtoupper($contract['signing_state'] ?? 'AWAITING_CLIENT');
$title = $contract['title'] ?? 'Contract';
$clientName = $contract['client_name'] ?? $contract['company_name'] ?? 'Client';
$clientEmail = $contract['client_email'] ?? 'client@example.com';
$content = $contract['content'] ?? 'No contract content is available for preview.';
$assetVersion = time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Contract - <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/public/assets/css/home.css?v=<?= htmlspecialchars((string) $assetVersion, ENT_QUOTES, 'UTF-8') ?>">
    <style>
        body { background: #f3f6fb; }
        .client-sign-shell { max-width: 1180px; margin: 0 auto; padding: 28px 20px 44px; }
        .client-sign-head { display: flex; justify-content: space-between; gap: 20px; align-items: center; margin-bottom: 20px; padding: 22px 24px; border: 1px solid #d8dee8; border-radius: 6px; background: #fff; }
        .client-sign-head p { margin: 0 0 6px; color: #8a94a6; font-size: 13px; font-weight: 900; letter-spacing: .04em; text-transform: uppercase; }
        .client-sign-head h1 { margin: 0; color: #183655; font-size: 28px; }
        .client-sign-head span { color: #657086; line-height: 1.5; }
        .client-status { min-width: 210px; display: grid; gap: 6px; padding: 14px 16px; border-radius: 6px; background: #f5f9ff; border: 1px solid #d7e6ff; }
        .client-status strong { color: #657086; font-size: 12px; text-transform: uppercase; }
        .client-status b { color: #d99a00; }
        .client-sign-grid { display: grid; grid-template-columns: minmax(0, 1.2fr) minmax(320px, .8fr); gap: 20px; align-items: start; }
        .client-doc { max-height: 620px; overflow: auto; padding: 24px; }
        .client-doc-meta { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; margin-bottom: 18px; }
        .client-doc-meta div { padding: 12px; border-radius: 4px; background: #f8fafc; border: 1px solid #edf1f6; }
        .client-doc-meta strong { display: block; margin-bottom: 4px; color: #657086; font-size: 12px; text-transform: uppercase; }
        .client-doc-meta span { color: #183655; font-weight: 800; word-break: break-word; }
        .client-doc-body { padding: 22px; border: 1px solid #d8dee8; border-radius: 4px; background: #fff; color: #26384d; line-height: 1.65; }
        .client-doc-body pre { white-space: pre-wrap; font: inherit; margin: 0; }
        .sign-choice-stack { display: grid; gap: 14px; }
        .sign-card { display: grid; gap: 12px; padding: 18px; border: 1px solid #d8dee8; border-radius: 6px; background: #fff; }
        .sign-card.is-selected { border-color: #2f70f2; box-shadow: inset 0 3px 0 #2f70f2; }
        .sign-card h2 { display: flex; gap: 10px; align-items: center; margin: 0; font-size: 19px; }
        .sign-card p { margin: 0; color: #657086; line-height: 1.55; }
        .sign-card button { width: fit-content; min-height: 40px; padding: 0 14px; border: 0; border-radius: 4px; color: #fff; background: #2f70f2; font-weight: 800; cursor: pointer; }
        .sign-card button.secondary { color: #2f70f2; background: #eaf1ff; }
        .action-panel { display: none; padding: 18px; border: 1px solid #d8dee8; border-radius: 6px; background: #fff; }
        .action-panel.active { display: block; }
        .signature-pad-box { display: grid; gap: 10px; margin: 12px 0; }
        canvas { width: 100%; height: 160px; border: 1px dashed #9fc2f4; border-radius: 4px; background: #fff; cursor: crosshair; }
        .file-input input { width: 100%; min-height: 42px; padding: 8px; border: 1px dashed #cbd5e1; border-radius: 4px; }
        .message-area { margin-top: 14px; }
        .message-area div { padding: 13px 14px; border-radius: 4px; line-height: 1.45; }
        .message-area .success { color: #146b2a; background: #effaf1; border: 1px solid #cdeed2; }
        .message-area .error { color: #9f1d1d; background: #fff1f1; border: 1px solid #ffd1d1; }
        @media (max-width: 900px) {
            .client-sign-head, .client-sign-grid { grid-template-columns: 1fr; }
            .client-sign-head { align-items: stretch; flex-direction: column; }
            .client-doc-meta { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <main class="client-sign-shell">
        <section class="client-sign-head">
            <div>
                <p>secure client signing</p>
                <h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
                <span>Choose digital signing or complete the hard-copy path. The contract body is locked for signing.</span>
            </div>
            <div class="client-status">
                <strong>Current state</strong>
                <b><?= htmlspecialchars($state, ENT_QUOTES, 'UTF-8') ?></b>
            </div>
        </section>

        <section class="client-sign-grid">
            <article class="surface client-doc">
                <div class="section-head compact">
                    <div><p>contract preview</p><h2>Read-only document</h2></div>
                </div>
                <div class="client-doc-meta">
                    <div><strong>Client</strong><span><?= htmlspecialchars($clientName, ENT_QUOTES, 'UTF-8') ?></span></div>
                    <div><strong>Email</strong><span><?= htmlspecialchars($clientEmail, ENT_QUOTES, 'UTF-8') ?></span></div>
                    <div><strong>Access</strong><span>30-day token</span></div>
                </div>
                <div class="client-doc-body">
                    <pre><?= htmlspecialchars(substr(strip_tags($content), 0, 5000), ENT_QUOTES, 'UTF-8') ?></pre>
                </div>
            </article>

            <aside class="sign-choice-stack">
                <article class="sign-card" data-method-card="digital">
                    <h2><i class="bi bi-pen" aria-hidden="true"></i> Digital signature</h2>
                    <p>Sign directly in the portal. Your signature is recorded and the contract moves to company execution.</p>
                    <button type="button" data-method="digital">Sign digitally</button>
                </article>

                <article class="sign-card" data-method-card="hardcopy">
                    <h2><i class="bi bi-printer" aria-hidden="true"></i> Hard copy</h2>
                    <p>Download the PDF, print and sign it, then upload the scanned copy for processing.</p>
                    <button class="secondary" type="button" data-method="hardcopy">Use hard copy</button>
                </article>

                <section id="digitalPanel" class="action-panel">
                    <h2>Draw signature</h2>
                    <p class="muted-copy">Use mouse or touch, then submit your digital signature.</p>
                    <div class="signature-pad-box">
                        <canvas id="signatureCanvas" width="720" height="220"></canvas>
                        <button class="button ghost" type="button" id="clearSignature">Clear</button>
                    </div>
                    <button class="button" type="button" id="submitDigitalSign">Submit Digital Signature</button>
                </section>

                <section id="hardcopyPanel" class="action-panel">
                    <h2>Upload signed scan</h2>
                    <ol class="bullet-list">
                        <li>Download the print-ready PDF.</li>
                        <li>Print, sign, and scan the document.</li>
                        <li>Upload the signed PDF, PNG, JPG, or JPEG.</li>
                    </ol>
                    <div class="form-actions" style="margin: 14px 0;">
                        <button class="button success" id="downloadPdfBtn" type="button">Download PDF</button>
                    </div>
                    <div class="file-input">
                        <input type="file" id="signedFile" accept=".pdf,.png,.jpg,.jpeg">
                    </div>
                    <button class="button" id="uploadSignedBtn" type="button">Upload Signed Document</button>
                </section>

                <div id="messageArea" class="message-area"></div>
            </aside>
        </section>
    </main>

    <script>
        const CONTRACT_ID = <?= json_encode((int) ($contract['id'] ?? 0)) ?>;
        const BASE_PATH = <?= json_encode($basePath) ?>;
        const CLIENT_EMAIL = <?= json_encode($clientEmail) ?>;

        const canvas = document.getElementById('signatureCanvas');
        const ctx = canvas.getContext('2d');
        const messageArea = document.getElementById('messageArea');
        let drawing = false;

        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.strokeStyle = '#111827';
        ctx.lineWidth = 3;
        ctx.lineCap = 'round';

        function showMessage(type, text) {
            messageArea.innerHTML = '<div class="' + type + '">' + text + '</div>';
        }

        function selectMethod(method) {
            document.querySelectorAll('[data-method-card]').forEach((card) => {
                card.classList.toggle('is-selected', card.dataset.methodCard === method);
            });
            document.getElementById('digitalPanel').classList.toggle('active', method === 'digital');
            document.getElementById('hardcopyPanel').classList.toggle('active', method === 'hardcopy');
            showMessage('success', method === 'digital' ? 'Digital signing selected.' : 'Hard-copy path selected.');
        }

        function pointerPosition(event) {
            const rect = canvas.getBoundingClientRect();
            const point = event.touches ? event.touches[0] : event;
            return {
                x: (point.clientX - rect.left) * (canvas.width / rect.width),
                y: (point.clientY - rect.top) * (canvas.height / rect.height)
            };
        }

        function startDrawing(event) {
            drawing = true;
            const pos = pointerPosition(event);
            ctx.beginPath();
            ctx.moveTo(pos.x, pos.y);
            event.preventDefault();
        }

        function draw(event) {
            if (!drawing) return;
            const pos = pointerPosition(event);
            ctx.lineTo(pos.x, pos.y);
            ctx.stroke();
            event.preventDefault();
        }

        function stopDrawing() {
            drawing = false;
            ctx.beginPath();
        }

        canvas.addEventListener('mousedown', startDrawing);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', stopDrawing);
        canvas.addEventListener('mouseleave', stopDrawing);
        canvas.addEventListener('touchstart', startDrawing, { passive: false });
        canvas.addEventListener('touchmove', draw, { passive: false });
        canvas.addEventListener('touchend', stopDrawing);

        document.querySelectorAll('[data-method]').forEach((button) => {
            button.addEventListener('click', () => selectMethod(button.dataset.method));
        });

        document.getElementById('clearSignature').addEventListener('click', () => {
            ctx.fillStyle = '#fff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.fillStyle = '#111827';
        });

        document.getElementById('submitDigitalSign').addEventListener('click', async () => {
            showMessage('success', 'Submitting signature...');
            try {
                const response = await fetch(`${BASE_PATH}/api/contracts/${CONTRACT_ID}/sign`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({
                        role: 'client',
                        signer_id: CLIENT_EMAIL,
                        signature_image: canvas.toDataURL('image/png')
                    })
                });
                const result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.error || result.message || 'Signing failed');
                showMessage('success', 'Contract signed successfully. Company execution is now active.');
                document.getElementById('digitalPanel').innerHTML = '<div class="message-area"><div class="success">Thank you. Your digital signature has been recorded.</div></div>';
            } catch (error) {
                showMessage('error', error.message || 'Signing failed.');
            }
        });

        document.getElementById('downloadPdfBtn').addEventListener('click', () => {
            window.open(`${BASE_PATH}/contracts/${CONTRACT_ID}/print-pdf`, '_blank', 'noopener');
        });

        document.getElementById('uploadSignedBtn').addEventListener('click', async () => {
            const file = document.getElementById('signedFile').files[0];
            if (!file) {
                showMessage('error', 'Choose a signed scan before uploading.');
                return;
            }

            const formData = new FormData();
            formData.append('signed_copy', file);
            showMessage('success', 'Uploading signed scan...');

            try {
                const response = await fetch(`${BASE_PATH}/api/contracts/${CONTRACT_ID}/upload-hard-copy`, {
                    method: 'POST',
                    body: formData,
                    headers: { 'Accept': 'application/json' }
                });
                const result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.message || result.error || 'Upload failed');
                showMessage('success', 'Signed scan uploaded successfully. The company execution step is now active.');
                document.getElementById('hardcopyPanel').innerHTML = '<div class="message-area"><div class="success">Thank you. Your signed document has been received.</div></div>';
            } catch (error) {
                showMessage('error', error.message || 'Upload failed.');
            }
        });
    </script>
</body>
</html>
