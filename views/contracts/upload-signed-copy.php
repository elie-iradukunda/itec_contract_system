<?php
$basePath = '/itec_contract_system';
$assetVersion = time();
$contractId = $contract['id'];
$uploadSuccess = $_SESSION['upload_success'] ?? null;
$uploadError = $_SESSION['upload_error'] ?? null;

// Clear session messages
unset($_SESSION['upload_success']);
unset($_SESSION['upload_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Signed Contract</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f5f5f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 40px 0;
        }
        .upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: #fafafa;
        }
        .upload-area:hover {
            border-color: #0d6efd;
            background: #f0f8ff;
        }
        .upload-area.dragover {
            border-color: #0d6efd;
            background: #e7f1ff;
        }
        #fileInfo {
            margin-top: 15px;
        }
        .preview-area {
            margin-top: 20px;
            text-align: center;
        }
        .preview-image {
            max-width: 200px;
            max-height: 200px;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 5px;
        }
        .file-name {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .success-message {
            background: #d4edda;
            border: 1px solid #28a745;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .error-message {
            background: #f8d7da;
            border: 1px solid #dc3545;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-upload fa-3x text-primary mb-3"></i>
                            <h2 class="card-title">Upload Signed Contract</h2>
                            <p class="text-muted">Please upload the scanned copy of your signed contract</p>
                        </div>

                        <?php if ($uploadSuccess): ?>
                        <div class="success-message">
                            <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($uploadSuccess) ?>
                            <hr>
                            <p class="mb-0">The contract has been signed. You will receive the final document via email once the company completes the process.</p>
                            <a href="<?= $basePath ?>/" class="btn btn-primary mt-3">Go to Home</a>
                        </div>
                        <?php endif; ?>

                        <?php if ($uploadError): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($uploadError) ?>
                        </div>
                        <?php endif; ?>

                        <?php if (!$uploadSuccess): ?>
                        <div class="alert alert-info">
                            <strong>Contract:</strong> <?= htmlspecialchars($contract['title']) ?><br>
                            <strong>Client:</strong> <?= htmlspecialchars($contract['client_name']) ?>
                        </div>

                        <form id="uploadForm" method="POST" action="<?= $basePath ?>/contracts/<?= $contractId ?>/upload-contract" enctype="multipart/form-data">
                            <div class="upload-area" id="uploadArea">
                                <i class="fas fa-cloud-upload-alt fa-3x text-secondary mb-3"></i>
                                <p class="mb-2">Drag & drop your file here or click to browse</p>
                                <small class="text-muted">Supports PDF, PNG, JPG (Max 10MB)</small>
                                <input type="file" id="fileInput" name="signed_copy" accept=".pdf,.png,.jpg,.jpeg" style="display: none;">
                            </div>
                            
                            <div id="fileInfo" class="text-center d-none">
                                <div class="preview-area" id="previewArea"></div>
                                <p class="file-name" id="fileName"></p>
                                <button type="button" class="btn btn-sm btn-outline-danger" id="removeFileBtn">Remove</button>
                            </div>

                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" class="btn btn-primary btn-lg py-3" id="submitBtn" disabled>
                                    <i class="fas fa-check-circle me-2"></i> Submit Signed Document
                                </button>
                                <a href="<?= $basePath ?>/sign/<?= $contractId ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i> Back to Signing Options
                                </a>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const CONTRACT_ID = <?= json_encode($contractId) ?>;
        
        let selectedFile = null;

        // Upload area click and drag events
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('fileInput');
        const fileInfo = document.getElementById('fileInfo');
        const fileNameSpan = document.getElementById('fileName');
        const previewArea = document.getElementById('previewArea');
        const submitBtn = document.getElementById('submitBtn');
        const removeFileBtn = document.getElementById('removeFileBtn');

        uploadArea.addEventListener('click', () => fileInput.click());
        
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length) {
                handleFile(e.target.files[0]);
            }
        });

        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            if (e.dataTransfer.files.length) {
                handleFile(e.dataTransfer.files[0]);
            }
        });

        function handleFile(file) {
            // Validate file type
            const validTypes = ['application/pdf', 'image/png', 'image/jpeg', 'image/jpg'];
            if (!validTypes.includes(file.type)) {
                alert('Invalid file type. Please upload PDF, PNG, or JPG.');
                return;
            }

            // Validate file size (10MB)
            if (file.size > 10 * 1024 * 1024) {
                alert('File too large. Maximum size is 10MB.');
                return;
            }

            selectedFile = file;
            fileNameSpan.textContent = file.name;
            
            // Show preview for images
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    previewArea.innerHTML = `<img src="${e.target.result}" class="preview-image" alt="Preview">`;
                };
                reader.readAsDataURL(file);
            } else if (file.type === 'application/pdf') {
                previewArea.innerHTML = '<i class="fas fa-file-pdf fa-4x text-danger"></i><p class="mt-2">PDF Document</p>';
            }

            fileInfo.classList.remove('d-none');
            uploadArea.style.display = 'none';
            submitBtn.disabled = false;
        }

        removeFileBtn.addEventListener('click', () => {
            selectedFile = null;
            fileInput.value = '';
            fileInfo.classList.add('d-none');
            previewArea.innerHTML = '';
            fileNameSpan.textContent = '';
            uploadArea.style.display = 'block';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>