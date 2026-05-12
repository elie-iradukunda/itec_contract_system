<?php
$basePath = BASE_URL ?? '/itec_contract_system';
$contractId = (int) ($contract['id'] ?? 1);
$contractTitle = $contract['title'] ?? 'Contract';
$signedMode = $signed_mode ?? null;
$alreadySigned = (bool) ($already_signed ?? false);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Contract</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-8">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-file-signature fa-3x text-primary mb-3"></i>
                            <h2><?= htmlspecialchars($contractTitle) ?></h2>
                            <p class="text-muted" id="statusMessage">
                                <?= $alreadySigned
                                    ? 'The client signing step is already complete.'
                                    : 'Choose how you would like to sign this contract.' ?>
                            </p>
                        </div>

                        <?php if ($alreadySigned): ?>
                            <div class="alert alert-success text-center">
                                <i class="fas fa-check-circle me-2"></i>
                                You have already signed this contract.
                            </div>
                            <div class="text-center">
                                <a href="<?= $basePath ?>/contracts/show/<?= $contractId ?>" class="btn btn-outline-primary">
                                    View Contract
                                </a>
                            </div>
                        <?php else: ?>
                            <div id="choiceContainer">
                                <div class="row g-4 mb-4">
                                    <div class="col-md-6">
                                        <div class="card text-center h-100 cursor-pointer" data-method="digital" style="cursor: pointer;">
                                            <div class="card-body">
                                                <i class="fas fa-laptop fa-2x text-primary mb-3"></i>
                                                <h5>Digital Signature</h5>
                                                <p class="small text-muted">Sign directly in the portal</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card text-center h-100 cursor-pointer" data-method="hardcopy" style="cursor: pointer;">
                                            <div class="card-body">
                                                <i class="fas fa-print fa-2x text-secondary mb-3"></i>
                                                <h5>Hard Copy Signature</h5>
                                                <p class="small text-muted">Download, print, sign, upload</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Digital Section - shows Sign Now button -->
                            <div id="digitalSection" class="d-none">
                                <button id="signNowBtn" class="btn btn-primary btn-lg w-100">
                                    <i class="fas fa-pen-fancy me-2"></i> Sign Now
                                </button>
                                <div class="text-center mt-3">
                                    <a href="<?= $basePath ?>/contracts/show/<?= $contractId ?>" class="btn btn-link">View Contract</a>
                                </div>
                            </div>

                            <!-- Success Message Section -->
                            <div id="successSection" class="d-none text-center">
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                                    <h4>Contract Signed Successfully!</h4>
                                    <p>Thank you for signing the contract. The company representative will now review and complete the process.</p>
                                    <hr>
                                    <a href="<?= $basePath ?>/contracts/show/<?= $contractId ?>" class="btn btn-outline-primary">View Contract</a>
                                    <a href="<?= $basePath ?>/" class="btn btn-link">Return to Home</a>
                                </div>
                            </div>

                            <!-- Hard Copy Section -->
                            <div id="hardcopySection" class="d-none">
                                <div class="d-grid gap-3">
                                    <a href="<?= $basePath ?>/contracts/<?= $contractId ?>/print-pdf" class="btn btn-success" target="_blank">
                                        <i class="fas fa-download me-2"></i> Download Contract PDF
                                    </a>
                                    <a href="<?= $basePath ?>/contracts/<?= $contractId ?>/upload-hard-copy" class="btn btn-secondary">
                                        <i class="fas fa-upload me-2"></i> Upload Signed Copy
                                    </a>
                                    <div class="text-center mt-2">
                                        <a href="<?= $basePath ?>/contracts/show/<?= $contractId ?>" class="btn btn-link">View Contract</a>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!$alreadySigned): ?>
    <script>
        const digitalSection = document.getElementById('digitalSection');
        const hardcopySection = document.getElementById('hardcopySection');
        const choiceContainer = document.getElementById('choiceContainer');
        const successSection = document.getElementById('successSection');
        const choiceCards = document.querySelectorAll('[data-method]');
        const signNowBtn = document.getElementById('signNowBtn');
        const statusMessage = document.getElementById('statusMessage');
        const CONTRACT_ID = <?= json_encode($contractId) ?>;
        const BASE_PATH = '<?= $basePath ?>';
        const SIGNER_ID = '<?= htmlspecialchars($contract['client_email'] ?? 'client@example.com') ?>';

        // Method selection
        choiceCards.forEach(card => {
            card.addEventListener('click', function() {
                const method = this.dataset.method;
                
                choiceCards.forEach(c => c.classList.remove('border-primary', 'bg-light'));
                this.classList.add('border-primary', 'bg-light');
                
                if (method === 'digital') {
                    digitalSection.classList.remove('d-none');
                    hardcopySection.classList.add('d-none');
                    statusMessage.textContent = 'You have chosen digital signature. Click Sign Now to proceed.';
                } else if (method === 'hardcopy') {
                    digitalSection.classList.add('d-none');
                    hardcopySection.classList.remove('d-none');
                    statusMessage.textContent = 'You have chosen hard copy signature. Download, sign, and upload.';
                }
            });
        });

        // Sign Now button - fetch API
        if (signNowBtn) {
            signNowBtn.addEventListener('click', async function() {
                signNowBtn.disabled = true;
                signNowBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Processing...';
                
                try {
                    const response = await fetch(`${BASE_PATH}/api/contracts/${CONTRACT_ID}/sign`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            role: 'client',
                            signer_id: SIGNER_ID
                        })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        // Hide sections and show success message
                        choiceContainer.classList.add('d-none');
                        digitalSection.classList.add('d-none');
                        hardcopySection.classList.add('d-none');
                        successSection.classList.remove('d-none');
                        statusMessage.textContent = 'Contract signed successfully!';
                    } else {
                        alert('Error: ' + result.error);
                        signNowBtn.disabled = false;
                        signNowBtn.innerHTML = '<i class="fas fa-pen-fancy me-2"></i> Sign Now';
                    }
                } catch (error) {
                    alert('Network error: ' + error.message);
                    signNowBtn.disabled = false;
                    signNowBtn.innerHTML = '<i class="fas fa-pen-fancy me-2"></i> Sign Now';
                }
            });
        }
    </script>
    <?php endif; ?>
</body>
</html>