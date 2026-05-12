<?php
require_once dirname(__DIR__) . '/components/ui.php';

if (!function_exists('readonly_file_url')) {
    function readonly_project_path($path)
    {
        $path = str_replace('\\', '/', (string) $path);
        if ($path === '') {
            return '';
        }

        if (preg_match('/^[A-Za-z]:\//', $path) || str_starts_with($path, '/')) {
            return $path;
        }

        return str_replace('\\', '/', dirname(__DIR__, 2)) . '/' . ltrim($path, '/');
    }

    function readonly_file_url($path)
    {
        $path = readonly_project_path($path);
        if ($path === '') {
            return '';
        }

        $root = str_replace('\\', '/', dirname(__DIR__, 2)) . '/';
        $real = str_replace('\\', '/', realpath($path) ?: $path);
        if (str_starts_with($real, $root)) {
            return BASE_URL . '/' . ltrim(substr($real, strlen($root)), '/');
        }

        return BASE_URL . '/' . ltrim($path, '/');
    }
}

if (!function_exists('readonly_format_date')) {
    function readonly_format_date($value)
    {
        return !empty($value) ? date('M d, Y @ H:i', strtotime($value)) : 'Not recorded';
    }
}

if (!function_exists('readonly_latest_file')) {
    function readonly_latest_file($pattern)
    {
        $files = glob($pattern) ?: [];
        usort($files, function ($a, $b) {
            return filemtime($b) <=> filemtime($a);
        });

        return $files[0] ?? null;
    }
}

$contract = $contract ?? [];
$signatures = $signatures ?? [];
$contractId = (int) ($contract_id ?? ($contract['id'] ?? 1));
$state = strtoupper($contract['signing_state'] ?? 'DRAFT');
$isFinal = $state === 'FULLY_SIGNED';
$isAwaitingCompany = $state === 'AWAITING_COMPANY';
$clientName = $contract['client_name'] ?? $contract['company_name'] ?? 'Client pending';
$clientEmail = $contract['client_email'] ?? $contract['email'] ?? 'Not provided';
$documentType = $contract['document_type'] ?? $contract['type'] ?? 'Contract';
$createdBy = $contract['created_by_name'] ?? $contract['created_by'] ?? 'System';
$createdAt = readonly_format_date($contract['created_at'] ?? null);
$updatedAt = readonly_format_date($contract['updated_at'] ?? null);
$finalPdfUrl = BASE_URL . '/contracts/final-pdf/' . $contractId;
$companySignUrl = BASE_URL . '/contracts/sign-company/' . $contractId;
$distributionUrl = BASE_URL . '/contracts/' . $contractId . '/editor#distribution';
$detailsUrl = BASE_URL . '/contracts/show/' . $contractId;
$auditUrl = BASE_URL . '/contracts/audit-trail/' . $contractId;
$sealedPath = readonly_latest_file(dirname(__DIR__, 2) . '/storage/contracts/' . $contractId . '/sealed_*.pdf');
$sealedUrl = $sealedPath ? readonly_file_url($sealedPath) : '';

$clientSignature = null;
$companySignature = null;
foreach ($signatures as $signature) {
    if (($signature['signer_role'] ?? '') === 'client') {
        $clientSignature = $signature;
    }
    if (($signature['signer_role'] ?? '') === 'company_rep') {
        $companySignature = $signature;
    }
}

$title = 'Final Contract';
$activeNav = 'contracts';
$headerMeta = $isFinal ? 'final execution packet' : 'execution workspace';
$pageTitle = 'Final Contract';
$pageHeading = $contract['title'] ?? 'Read-Only Execution View';
$pageEyebrow = $isFinal ? 'fully signed contract' : 'body lock enforced';
$pageLead = $isFinal
    ? 'Review the completed contract details, signatures, sealed copy, and final distribution actions.'
    : 'Review the locked contract and continue with the next execution action.';

$pageActions = [
    '<a class="button ghost" href="' . $detailsUrl . '">' . ui_icon('file-text') . ' Contract Details</a>',
    '<a class="button ghost" href="' . $finalPdfUrl . '" target="_blank" rel="noopener">' . ui_icon('file-earmark-pdf') . ' Generated PDF</a>',
];
if ($sealedUrl) {
    $pageActions[] = '<a class="button success" href="' . $sealedUrl . '" target="_blank" rel="noopener">' . ui_icon('patch-check') . ' Sealed Copy</a>';
} elseif ($isAwaitingCompany) {
    $pageActions[] = '<a class="button success" href="' . $companySignUrl . '">' . ui_icon('building-check') . ' Company Sign & Seal</a>';
}

ob_start();
?>
<style>
    .final-contract-grid { display: grid; grid-template-columns: minmax(0, 1.35fr) minmax(320px, .75fr); gap: 20px; align-items: start; }
    .final-summary-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; margin-bottom: 20px; }
    .final-summary-card { min-height: 112px; display: grid; align-content: start; gap: 8px; padding: 16px; }
    .final-summary-card span { width: 34px; height: 34px; display: grid; place-items: center; border-radius: 4px; color: #2f70f2; background: #eaf1ff; }
    .final-summary-card strong { color: #183655; font-size: 15px; }
    .final-summary-card small { color: #657086; line-height: 1.45; }
    .contract-detail-list { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
    .contract-detail-list div { min-height: 64px; padding: 12px; border: 1px solid #edf1f6; border-radius: 4px; background: #fbfcfe; }
    .contract-detail-list span { display: block; color: #8a94a6; font-size: 12px; font-weight: 800; letter-spacing: .03em; text-transform: uppercase; }
    .contract-detail-list strong { display: block; margin-top: 5px; color: #183655; line-height: 1.4; overflow-wrap: anywhere; }
    .final-section-head { display: flex; justify-content: space-between; gap: 14px; align-items: start; padding-bottom: 14px; margin-bottom: 16px; border-bottom: 1px solid #edf1f6; }
    .final-section-head h2, .final-section-head h3 { margin: 0; color: #183655; }
    .final-section-head small { color: #657086; }
    .final-document-card { padding: 22px; }
    .readonly-document { color: #20344f; line-height: 1.68; }
    .readonly-document h1, .readonly-document h2, .readonly-document h3 { color: #183655; }
    .readonly-document p { margin: 0 0 14px; }
    .signature-list { display: grid; gap: 12px; }
    .signature-card { display: grid; gap: 10px; padding: 14px; border: 1px solid #edf1f6; border-left: 4px solid #117d71; border-radius: 4px; background: #fff; }
    .signature-card-head { display: flex; justify-content: space-between; gap: 12px; align-items: start; }
    .signature-card strong { color: #183655; }
    .signature-card span, .signature-card small { color: #657086; overflow-wrap: anywhere; }
    .signature-image { max-width: 180px; max-height: 62px; object-fit: contain; padding: 6px; border: 1px solid #edf1f6; border-radius: 4px; background: #fbfcfe; }
    .final-actions { display: grid; gap: 10px; }
    .final-actions .button { width: 100%; }
    .final-state-banner { margin-bottom: 20px; }
    @media (max-width: 1100px) {
        .final-contract-grid, .final-summary-grid { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 760px) {
        .final-contract-grid, .final-summary-grid, .contract-detail-list { grid-template-columns: 1fr; }
        .final-section-head, .signature-card-head { flex-direction: column; }
    }
</style>

<section class="notice-banner <?= $isFinal ? 'success' : 'warn' ?> final-state-banner">
    <strong><?= $isFinal ? 'Final contract ready' : 'Body lock active' ?></strong>
    <span>Current state: <?= ui_e(ui_status_label($state)) ?>. Contract body changes are disabled.</span>
</section>

<section class="final-summary-grid">
    <article class="surface final-summary-card">
        <span><?= ui_icon($isFinal ? 'check-circle' : 'lock-fill') ?></span>
        <strong><?= ui_e(ui_status_label($state)) ?></strong>
        <small><?= $isFinal ? 'Both parties have signed and the contract is ready for final distribution.' : 'The body is locked while execution continues.' ?></small>
    </article>
    <article class="surface final-summary-card">
        <span><?= ui_icon('person-badge') ?></span>
        <strong><?= ui_e($clientName) ?></strong>
        <small><?= ui_e($clientEmail) ?></small>
    </article>
    <article class="surface final-summary-card">
        <span><?= ui_icon('pen') ?></span>
        <strong><?= $clientSignature ? 'Client signed' : 'Client pending' ?></strong>
        <small><?= $clientSignature ? ui_e(readonly_format_date($clientSignature['signed_at'] ?? null)) : 'No client signature recorded yet.' ?></small>
    </article>
    <article class="surface final-summary-card">
        <span><?= ui_icon('patch-check') ?></span>
        <strong><?= $companySignature ? 'Company signed' : 'Company pending' ?></strong>
        <small><?= $sealedUrl ? 'Sealed PDF copy is available.' : ($isAwaitingCompany ? 'Company seal step is ready.' : 'Seal copy not found yet.') ?></small>
    </article>
</section>

<section class="final-contract-grid">
    <div class="page-stack">
        <article class="surface surface-pad">
            <div class="final-section-head">
                <div>
                    <small>contract record</small>
                    <h2>Full Contract Details</h2>
                </div>
                <span class="status-pill <?= ui_status_class($state) ?>"><?= ui_e(ui_status_label($state)) ?></span>
            </div>
            <div class="contract-detail-list">
                <div><span>Contract ID</span><strong>#<?= $contractId ?></strong></div>
                <div><span>Document Type</span><strong><?= ui_e($documentType) ?></strong></div>
                <div><span>Client Name</span><strong><?= ui_e($clientName) ?></strong></div>
                <div><span>Client Email</span><strong><?= ui_e($clientEmail) ?></strong></div>
                <div><span>Created By</span><strong><?= ui_e($createdBy) ?></strong></div>
                <div><span>Created</span><strong><?= ui_e($createdAt) ?></strong></div>
                <div><span>Last Updated</span><strong><?= ui_e($updatedAt) ?></strong></div>
                <div><span>Sealed Copy</span><strong><?= $sealedUrl ? 'Available' : 'Not generated yet' ?></strong></div>
            </div>
        </article>

        <article class="surface final-document-card">
            <div class="final-section-head">
                <div>
                    <small>locked document body</small>
                    <h2><?= ui_e($contract['title'] ?? 'Protected document body') ?></h2>
                </div>
            </div>
            <div class="readonly-document">
                <?= $contract['content'] ?? '<p>Contract content is not available.</p>' ?>
            </div>
        </article>
    </div>

    <aside class="page-stack">
        <article class="surface surface-pad">
            <div class="final-section-head">
                <div>
                    <small>execution evidence</small>
                    <h2>Document Signatures</h2>
                </div>
            </div>
            <?php if (!empty($signatures)): ?>
                <div class="signature-list">
                    <?php foreach ($signatures as $sig): ?>
                        <?php
                            $role = ucfirst(str_replace('_', ' ', $sig['signer_role'] ?? 'Signer'));
                            $visualUrl = '';
                            $visualPath = $sig['signature_file_path'] ?? '';
                            if ($visualPath && preg_match('/\.(png|jpe?g)$/i', $visualPath) && is_file(readonly_project_path($visualPath))) {
                                $visualUrl = readonly_file_url($visualPath);
                            }
                        ?>
                        <div class="signature-card">
                            <div class="signature-card-head">
                                <div>
                                    <strong><?= ui_e($role) ?></strong>
                                    <span>Signed by: <?= ui_e($sig['signer_id'] ?? 'Unknown signer') ?></span>
                                </div>
                                <small><?= ui_e(readonly_format_date($sig['signed_at'] ?? null)) ?></small>
                            </div>
                            <?php if ($visualUrl): ?>
                                <img class="signature-image" src="<?= ui_e($visualUrl) ?>" alt="<?= ui_e($role) ?> signature">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="muted-copy">No signatures have been recorded for this contract yet.</p>
            <?php endif; ?>
        </article>

        <article class="surface surface-pad">
            <div class="final-section-head">
                <div>
                    <small>final actions</small>
                    <h2><?= $isFinal ? 'Final Contract Packet' : 'Next Execution Step' ?></h2>
                </div>
            </div>
            <div class="final-actions">
                <?php if ($sealedUrl): ?>
                    <a class="button success" href="<?= ui_e($sealedUrl) ?>" target="_blank" rel="noopener"><?= ui_icon('patch-check') ?> Open Sealed Copy</a>
                <?php endif; ?>
                <a class="button ghost" href="<?= ui_e($finalPdfUrl) ?>" target="_blank" rel="noopener"><?= ui_icon('file-earmark-pdf') ?> Open Generated PDF</a>
                <?php if ($isAwaitingCompany): ?>
                    <a class="button success" href="<?= ui_e($companySignUrl) ?>"><?= ui_icon('building-check') ?> Company Sign & Seal</a>
                <?php endif; ?>
                <?php if ($isFinal): ?>
                    <a class="button" href="<?= ui_e($distributionUrl) ?>"><?= ui_icon('send-check') ?> Open Distribution</a>
                <?php endif; ?>
                <a class="button ghost" href="<?= ui_e($auditUrl) ?>"><?= ui_icon('clipboard-check') ?> Audit Trail</a>
            </div>
        </article>
    </aside>
</section>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/app.php';
?>
