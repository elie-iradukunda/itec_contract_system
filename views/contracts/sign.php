<?php
$basePath = '/itec_contract_system';
$state = strtoupper($contract['signing_state'] ?? 'AWAITING_CLIENT');
$assetVersion = time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Contract - <?= htmlspecialchars($contract['title'] ?? 'Contract') ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            background: #f0f2f5;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
        .card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .card h1 {
            margin-bottom: 20px;
            font-size: 24px;
            color: #333;
        }
        .card h2 {
            margin-bottom: 15px;
            font-size: 18px;
            color: #555;
        }
        .contract-preview {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            max-height: 400px;
            overflow-y: auto;
            margin-bottom: 20px;
        }
        .contract-preview pre {
            white-space: pre-wrap;
            font-family: inherit;
        }
        .signature-box {
            border-top: 1px solid #ddd;
            padding-top: 20px;
            margin-top: 20px;
        }
        .choice-grid {
            display: flex;
            gap: 20px;
            margin: 20px 0;
        }
        .choice-card {
            flex: 1;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .choice-card:hover {
            border-color: #007bff;
            box-shadow: 0 4px 12px rgba(0,123,255,0.2);
        }
        .choice-card.selected {
            border-color: #007bff;
            background: #e7f1ff;
        }
        button {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 15px;
        }
        button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .hidden {
            display: none;
        }
        .upload-area {
            margin-top: 15px;
            padding: 15px;
            border: 1px dashed #ddd;
            border-radius: 8px;
            text-align: center;
        }
        .success {
            background: #d4edda;
            border: 1px solid #28a745;
            color: #155724;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .error {
            background: #f8d7da;
            border: 1px solid #dc3545;
            color: #721c24;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .signature-pad {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
        }
        canvas {
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: crosshair;
            display: block;
            margin: 0 auto;
        }
        .small {
            padding: 8px 16px;
            font-size: 14px;
        }
        .file-input {
            margin: 15px 0;
        }
        .file-input input {
            display: block;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>Sign Contract</h1>
            <p><strong><?= htmlspecialchars($contract['title'] ?? 'Untitled Contract') ?></strong></p>
            
            <div class="contract-preview">
                <h2>Contract Terms</h2>
                <p><strong>Client:</strong> <?= htmlspecialchars($contract['client_name'] ?? 'N/A') ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($contract['client_email'] ?? 'N/A') ?></p>
                <p><strong>Status:</strong> <?= htmlspecialchars($state) ?></p>
                <hr>
                <pre><?= htmlspecialchars(substr($contract['content'] ?? 'No content available', 0, 2000)) ?></pre>
                <?php if (strlen($contract['content'] ?? '') > 2000): ?>
                    <p><em>Content truncated. Full document will be available for download.</em></p>
                <?php endif; ?>
            </div>

            <div id="signingMethodContainer">
                <h2>Select Signing Method</h2>
                <div class="choice-grid">
                    <div class="choice-card" data-method="digital">
                        <h3>Digital Signature</h3>
                        <p>Sign directly in the portal using your mouse or touch</p>
                    </div>
                    <div class="choice-card" data-method="hardcopy">
                        <h3>Hard Copy Signature</h3>
                        <p>Download PDF, print, sign physically, and upload the scanned copy</p>
                    </div>
                </div>
            </div>

            <div id="digitalSignArea" class="hidden">
                <div class="signature-pad">
                    <h3>Draw Your Signature</h3>
                    <canvas id="signatureCanvas" width="400" height="150"></canvas>
                    <div style="margin-top: 10px; text-align: center;">
                        <button type="button" id="clearSignature" class="small">Clear Signature</button>
                    </div>
                    <p style="font-size: 12px; color: #666; margin-top: 10px;">Use your mouse or touch to draw your signature in the box above.</p>
                </div>
                <button id="submitDigitalSign">Submit Digital Signature</button>
            </div>

            <div id="hardcopyArea" class="hidden">
                <p>To sign using hard copy:</p>
                <ol style="margin: 15px 0 15px 25px;">
                    <li>Click the button below to download the contract as PDF</li>
                    <li>Print the PDF document</li>
                    <li>Sign your name on the designated signature line</li>
                    <li>Scan the signed document</li>
                    <li>Upload the scanned file using the form below</li>
                </ol>
                
                <button id="downloadPdfBtn" style="background: #28a745;">Download Print-Ready PDF</button>
                
                <div class="upload-area">
                    <h3>Upload Signed Copy</h3>
                    <p>After signing the printed document, upload the scanned file here.</p>
                    <div class="file-input">
                        <input type="file" id="signedFile" accept=".pdf,.png,.jpg,.jpeg">
                    </div>
                    <button id="uploadSignedBtn" style="background: #6c757d;">Upload Signed Document</button>
                    <div id="uploadMessage" style="margin-top: 10px;"></div>
                </div>
            </div>

            <div id="messageArea" style="margin-top: 20px;"></div>
        </div>
    </div>

    <script>
        const CONTRACT_ID = <?= json_encode($contract['id']) ?>;
        const TOKEN = <?= json_encode($token ?? '') ?>;
        const BASE_PATH = '<?= $basePath ?>';
        
        let selectedMethod = null;
        let signatureData = null;

        // Signature canvas setup
        const canvas = document.getElementById('signatureCanvas');
        const ctx = canvas.getContext('2d');
        let drawing = false;
        
        if (canvas) {
            ctx.fillStyle = 'white';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.strokeStyle = '#000';
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            
            canvas.addEventListener('mousedown', startDrawing);
            canvas.addEventListener('mousemove', draw);
            canvas.addEventListener('mouseup', stopDrawing);
            canvas.addEventListener('touchstart', startDrawing);
            canvas.addEventListener('touchmove', draw);
            canvas.addEventListener('touchend', stopDrawing);
        }
        
        function startDrawing(e) {
            drawing = true;
            const pos = getPosition(e);
            ctx.beginPath();
            ctx.moveTo(pos.x, pos.y);
            ctx.lineTo(pos.x, pos.y);
            ctx.stroke();
        }
        
        function draw(e) {
            if (!drawing) return;
            e.preventDefault();
            const pos = getPosition(e);
            ctx.lineTo(pos.x, pos.y);
            ctx.stroke();
        }
        
        function stopDrawing() {
            drawing = false;
            ctx.beginPath();
        }
        
        function getPosition(e) {
            const rect = canvas.getBoundingClientRect();
            const scaleX = canvas.width / rect.width;
            const scaleY = canvas.height / rect.height;
            
            let clientX, clientY;
            
            if (e.touches) {
                clientX = e.touches[0].clientX;
                clientY = e.touches[0].clientY;
            } else {
                clientX = e.clientX;
                clientY = e.clientY;
            }
            
            return {
                x: (clientX - rect.left) * scaleX,
                y: (clientY - rect.top) * scaleY
            };
        }
        
        document.getElementById('clearSignature')?.addEventListener('click', function() {
            ctx.fillStyle = 'white';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.fillStyle = '#000';
            signatureData = null;
        });
        
        // Method selection
        document.querySelectorAll('.choice-card').forEach(card => {
            card.addEventListener('click', function() {
                document.querySelectorAll('.choice-card').forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
                selectedMethod = this.dataset.method;
                
                document.getElementById('digitalSignArea').classList.add('hidden');
                document.getElementById('hardcopyArea').classList.add('hidden');
                
                if (selectedMethod === 'digital') {
                    document.getElementById('digitalSignArea').classList.remove('hidden');
                } else if (selectedMethod === 'hardcopy') {
                    document.getElementById('hardcopyArea').classList.remove('hidden');
                }
            });
        });
        
        // Digital signature submission
        document.getElementById('submitDigitalSign')?.addEventListener('click', async function() {
            // Capture signature from canvas
            const imageData = canvas.toDataURL('image/png');
            
            const response = await fetch(`${BASE_PATH}/api/contracts/${CONTRACT_ID}/sign`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    role: 'client',
                    signer_id: '<?= htmlspecialchars($contract['client_email'] ?? 'client@example.com') ?>',
                    signature_image: imageData
                })
            });
            
            const result = await response.json();
            const messageArea = document.getElementById('messageArea');
            
            if (result.success) {
                messageArea.innerHTML = '<div class="success">Contract signed successfully! You will receive the final document via email once the company completes the process.</div>';
                document.getElementById('digitalSignArea').innerHTML = '<div class="success">Thank you for signing. The contract has been submitted.</div>';
            } else {
                messageArea.innerHTML = '<div class="error">Error: ' + result.error + '</div>';
            }
        });
        
        // Download PDF for hard copy
        document.getElementById('downloadPdfBtn')?.addEventListener('click', function() {
            window.open(`${BASE_PATH}/contracts/${CONTRACT_ID}/print-pdf`, '_blank');
        });
        
        // Upload signed copy
        document.getElementById('uploadSignedBtn')?.addEventListener('click', async function() {
            const fileInput = document.getElementById('signedFile');
            const file = fileInput.files[0];
            
            if (!file) {
                alert('Please select a file to upload');
                return;
            }
            
            const formData = new FormData();
            formData.append('signed_copy', file);
            
            const response = await fetch(`${BASE_PATH}/contracts/${CONTRACT_ID}/upload-signed-copy`, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            const messageArea = document.getElementById('messageArea');
            
            if (result.success) {
                messageArea.innerHTML = '<div class="success">Signed document uploaded successfully! The contract will be processed.</div>';
                document.getElementById('hardcopyArea').innerHTML = '<div class="success">Thank you. Your signed document has been received.</div>';
            } else {
                messageArea.innerHTML = '<div class="error">Upload failed: ' + result.error + '</div>';
            }
        });
    </script>
</body>
</html>