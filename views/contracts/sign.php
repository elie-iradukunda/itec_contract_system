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
    <style>
        body {
            background: linear-gradient(180deg, #f4f8fb, #ffffff);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 36px 0;
            color: #183655;
        }
        .sign-shell {
            border: 0;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 24px 70px rgba(24, 54, 85, 0.12);
        }
        .kicker {
            margin: 0 0 8px;
            color: #7b8797;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }
        .headline-note {
            color: #5f6d80;
            font-size: 1.05rem;
            line-height: 1.6;
        }
        .step-strip {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin: 26px 0 6px;
        }
        .step-card {
            min-height: 92px;
            padding: 16px;
            border: 1px solid #dce5ee;
            border-radius: 18px;
            background: #fbfdff;
        }
        .step-card.active {
            border-color: #2f70f2;
            background: #f2f7ff;
        }
        .step-card.complete {
            border-color: #bde5cf;
            background: #f3fbf6;
        }
        .step-card strong {
            display: block;
            margin-bottom: 6px;
            font-size: 1rem;
        }
        .step-card span {
            color: #607086;
            font-size: 0.94rem;
            line-height: 1.45;
        }
        .choice-card {
            height: 100%;
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
            cursor: pointer;
            border: 2px solid #d9e3ee;
            border-radius: 20px;
        }
        .choice-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 40px rgba(24, 54, 85, 0.08);
        }
        .choice-card.selected {
            border-color: #2f70f2;
            background: #f2f7ff;
        }
        .choice-card .card-body {
            padding: 30px 24px;
        }
        .choice-card i {
            font-size: 3rem;
            margin-bottom: 18px;
        }
        .choice-card p {
            color: #5f6d80;
            line-height: 1.55;
        }
        .meta-pill {
            display: inline-flex;
            align-items: center;
            min-height: 28px;
            padding: 0 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
        }
        .next-box {
            border-radius: 18px;
            border: 1px solid #d7ecfb;
            background: #eef9ff;
        }
        .next-box strong {
            display: block;
            margin-bottom: 8px;
            font-size: 1rem;
        }
        .next-box ul {
            margin: 0;
            padding-left: 18px;
            color: #436176;
            line-height: 1.6;
        }
        .primary-cta, .secondary-cta {
            width: 100%;
            min-height: 72px;
            border-radius: 16px;
            font-size: 1.15rem;
            font-weight: 700;
        }
        .success-shell {
            text-align: center;
            padding: 12px 0;
        }
        .success-shell i {
            font-size: 3.6rem;
            color: #1e8f56;
            margin-bottom: 18px;
        }
        .success-shell p {
            color: #5f6d80;
            line-height: 1.6;
        }
        @media (max-width: 768px) {
            .step-strip {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-8">
                <div class="card sign-shell">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <p class="kicker">Secure signing link</p>
                            <i class="fas fa-file-signature fa-3x text-primary mb-3"></i>
                            <h1 class="mb-2"><?= htmlspecialchars($contractTitle) ?></h1>
                            <p class="headline-note mb-0">
                                <?= $alreadySigned
                                    ? 'The client signing step is already complete. The document is now waiting for company action.'
                                    : 'Choose how you would like to sign this contract. After you choose, the next screen will guide you through the exact signing step.' ?>
                            </p>
                        </div>

                        <?php if ($alreadySigned): ?>
                            <div class="success-shell">
                                <i class="fas fa-circle-check"></i>
                                <h2 class="mb-3">
                                    <?= $signedMode === 'hard_copy' ? 'Signed Copy Received' : 'Digital Signature Submitted' ?>
                                </h2>
                                <p class="mb-4">
                                    <?= $signedMode === 'hard_copy'
                                        ? 'Your uploaded signed copy has been received. The company team will now review it, countersign, and apply the official seal.'
                                        : 'Your signature has been captured successfully. The company team will now countersign and apply the official seal.' ?>
                                </p>
                                <div class="step-strip text-start">
                                    <div class="step-card complete">
                                        <strong>1. Client link opened</strong>
                                        <span>You accessed the contract through the secure email link.</span>
                                    </div>
                                    <div class="step-card complete">
                                        <strong>2. Client signing finished</strong>
                                        <span>Your part of the signing workflow is complete.</span>
                                    </div>
                                    <div class="step-card active">
                                        <strong>3. Company countersigns</strong>
                                        <span>ITEC will complete the final company signature and seal.</span>
                                    </div>
                                </div>
                                <div class="alert alert-success mt-4 mb-0">
                                    <i class="fas fa-lock me-2"></i>
                                    You can close this page. The contract body remains locked and unchanged.
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="step-strip">
                                <div class="step-card active">
                                    <strong>1. Choose a signing method</strong>
                                    <span>Select digital signing or hard copy signing.</span>
                                </div>
                                <div class="step-card">
                                    <strong>2. Complete your signature</strong>
                                    <span>Type your details and sign, or upload the signed scan.</span>
                                </div>
                                <div class="step-card">
                                    <strong>3. Company completes execution</strong>
                                    <span>The company adds its signature and official seal.</span>
                                </div>
                            </div>

                            <div class="row g-4 my-2">
                                <div class="col-md-6">
                                    <div class="choice-card card text-center" data-method="digital">
                                        <div class="card-body">
                                            <i class="fas fa-laptop text-primary"></i>
                                            <h3 class="mb-3">Digital Signature</h3>
                                            <p>Sign directly on the next page using your mouse or touch screen.</p>
                                            <div class="d-flex justify-content-center gap-2 mt-3">
                                                <span class="meta-pill text-bg-primary">Instant</span>
                                                <span class="meta-pill text-bg-success">Secure</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="choice-card card text-center" data-method="hardcopy">
                                        <div class="card-body">
                                            <i class="fas fa-print text-secondary"></i>
                                            <h3 class="mb-3">Hard Copy Signature</h3>
                                            <p>Download the PDF, sign it physically, then upload the scanned signed copy.</p>
                                            <div class="d-flex justify-content-center gap-2 mt-3">
                                                <span class="meta-pill text-bg-secondary">Offline</span>
                                                <span class="meta-pill text-bg-info">Manual</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="digitalSection" class="next-box p-4 d-none mt-4">
                                <strong>Next after choosing digital signature</strong>
                                <ul>
                                    <li>You will open the digital signing form.</li>
                                    <li>You will confirm your email and full legal name.</li>
                                    <li>You will draw your signature and submit it.</li>
                                </ul>
                                <div class="d-grid mt-4">
                                    <a href="<?= $basePath ?>/sign-digitally/<?= $contractId ?>" class="btn btn-primary primary-cta">
                                        <i class="fas fa-pen-fancy me-2"></i> Proceed to Digital Signature
                                    </a>
                                </div>
                            </div>

                            <div id="hardcopySection" class="next-box p-4 d-none mt-4">
                                <strong>Next after choosing hard copy</strong>
                                <ul>
                                    <li>Download the contract PDF.</li>
                                    <li>Print it and sign it physically.</li>
                                    <li>Return here and upload the scanned signed copy.</li>
                                </ul>
                                <div class="d-grid gap-3 mt-4">
                                    <a href="<?= $basePath ?>/contracts/<?= $contractId ?>/print-pdf" class="btn btn-success secondary-cta" target="_blank" rel="noopener">
                                        <i class="fas fa-download me-2"></i> Download Contract PDF
                                    </a>
                                    <a href="<?= $basePath ?>/contracts/<?= $contractId ?>/upload-hard-copy" class="btn btn-secondary secondary-cta">
                                        <i class="fas fa-upload me-2"></i> Upload Signed Copy
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!$alreadySigned): ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.choice-card').forEach(function (card) {
            card.addEventListener('click', function () {
                document.querySelectorAll('.choice-card').forEach(function (item) {
                    item.classList.remove('selected');
                });

                this.classList.add('selected');
                const method = this.dataset.method;

                document.getElementById('digitalSection').classList.toggle('d-none', method !== 'digital');
                document.getElementById('hardcopySection').classList.toggle('d-none', method !== 'hardcopy');
            });
        });
    </script>
    <?php endif; ?>
</body>
</html>
