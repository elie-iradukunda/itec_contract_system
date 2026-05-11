<?php
$basePath = '/itec_contract_system';
$contractId = $contract['id'] ?? 1;
$assetVersion = time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Contract - Choose Method</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f5f5f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 40px 0;
        }
        .choice-card {
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
            border: 2px solid #dee2e6;
        }
        .choice-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .choice-card.selected {
            border-color: #0d6efd;
            background-color: #f0f8ff;
        }
        .btn-digital {
            background-color: #0d6efd;
            color: white;
        }
        .btn-digital:hover {
            background-color: #0b5ed7;
            color: white;
        }
        .btn-hardcopy {
            background-color: #6c757d;
            color: white;
        }
        .btn-hardcopy:hover {
            background-color: #5c636a;
            color: white;
        }
        .btn-download {
            background-color: #28a745;
            color: white;
        }
        .btn-download:hover {
            background-color: #218838;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-file-signature fa-3x text-primary mb-3"></i>
                            <h2 class="card-title">Sign Contract</h2>
                            <p class="text-muted">Choose how you would like to sign this contract</p>
                        </div>

                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <div class="choice-card card h-100 text-center p-4" data-method="digital">
                                    <div class="card-body">
                                        <i class="fas fa-laptop fa-3x text-primary mb-3"></i>
                                        <h4>Digital Signature</h4>
                                        <p class="text-muted">Sign directly in the portal using your mouse or touch</p>
                                        <div class="mt-3">
                                            <span class="badge bg-primary">Instant</span>
                                            <span class="badge bg-success">Secure</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="choice-card card h-100 text-center p-4" data-method="hardcopy">
                                    <div class="card-body">
                                        <i class="fas fa-print fa-3x text-secondary mb-3"></i>
                                        <h4>Hard Copy Signature</h4>
                                        <p class="text-muted">Download PDF, print, sign physically, and upload the scanned copy</p>
                                        <div class="mt-3">
                                            <span class="badge bg-secondary">Offline</span>
                                            <span class="badge bg-info">Manual</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="digitalSection" class="d-none">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> You will be redirected to the digital signature page.
                            </div>
                            <div class="d-grid">
                                <a href="<?= $basePath ?>/sign-digitally/<?= $contractId ?>" class="btn btn-digital btn-lg py-3">
                                    <i class="fas fa-pen-fancy me-2"></i> Proceed to Digital Signature
                                </a>
                            </div>
                        </div>

                        <div id="hardcopySection" class="d-none">
                            <div class="alert alert-secondary">
                                <i class="fas fa-info-circle"></i> Download the contract, sign it physically, then upload the scanned copy.
                            </div>
                            <div class="d-grid gap-3">
                                <a href="<?= $basePath ?>/contracts/<?= $contractId ?>/print-pdf" class="btn btn-download btn-lg py-3" target="_blank">
                                    <i class="fas fa-download me-2"></i> Download Contract (PDF)
                                </a>
                                <div class="text-center text-muted">
                                    <i class="fas fa-arrow-down"></i> After downloading, sign the document and click below
                                </div>
                                <a href="<?= $basePath ?>/upload-contract/<?= $contractId ?>" class="btn btn-hardcopy btn-lg py-3">
                                    <i class="fas fa-upload me-2"></i> I Have Signed - Upload Document
                                </a>
                            </div>
                        </div>

                        <div id="messageArea" class="mt-4"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const CONTRACT_ID = <?= json_encode($contractId) ?>;
        const BASE_PATH = '<?= $basePath ?>';
        
        let selectedMethod = null;

        // Method selection
        document.querySelectorAll('.choice-card').forEach(card => {
            card.addEventListener('click', function() {
                document.querySelectorAll('.choice-card').forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
                selectedMethod = this.dataset.method;
                
                document.getElementById('digitalSection').classList.add('d-none');
                document.getElementById('hardcopySection').classList.add('d-none');
                
                if (selectedMethod === 'digital') {
                    document.getElementById('digitalSection').classList.remove('d-none');
                } else if (selectedMethod === 'hardcopy') {
                    document.getElementById('hardcopySection').classList.remove('d-none');
                }
            });
        });
    </script>
</body>
</html>