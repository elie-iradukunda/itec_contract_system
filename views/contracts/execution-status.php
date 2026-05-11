<?php
require_once dirname(__DIR__) . '/components/ui.php';

$contractId = (int) ($contract_id ?? 1);
$base = BASE_URL;
$title = 'Execution Status';
$activeNav = 'contracts';
$headerMeta = 'execution dashboard';
$pageTitle = 'Execution Status';
$pageHeading = 'Execution Status';
$pageEyebrow = 'signing progress';
$pageLead = 'Use this screen to jump into each frontend interface that maps to the Oscar backend flow.';
$pageActions = [
    '<a class="button ghost" href="' . $base . '/contracts/show/' . $contractId . '">Contract Details</a>',
    '<a class="button" href="' . $base . '/contracts/' . $contractId . '/editor#signing">Execution Controls</a>',
];

ob_start();
?>
<section class="flow-board surface">
    <div class="section-head compact">
        <div><p>test map</p><h2>Frontend interfaces by phase</h2></div>
        <span class="flow-note">ready for testing</span>
    </div>
    <div class="phase-grid">
        <article class="phase-card">
            <span class="phase-index">1</span>
            <strong>Draft & Review</strong>
            <small>Editor body, version list, tracked changes, reviewer release gate.</small>
            <div class="phase-actions">
                <a href="<?= $base ?>/contracts/<?= $contractId ?>/editor#versions">Editor</a>
                <a href="<?= $base ?>/contracts/<?= $contractId ?>/review">Review</a>
            </div>
        </article>
        <article class="phase-card">
            <span class="phase-index">2</span>
            <strong>Client Signs</strong>
            <small>Token page, digital signature canvas, print PDF, scan upload.</small>
            <div class="phase-actions">
                <a href="<?= $base ?>/contracts/sign/<?= $contractId ?>">Client page</a>
                <a href="<?= $base ?>/contracts/<?= $contractId ?>/print-pdf">Print PDF</a>
            </div>
        </article>
        <article class="phase-card">
            <span class="phase-index">3</span>
            <strong>Company + Seal</strong>
            <small>Read-only body, company signature action, seal/stamp action.</small>
            <div class="phase-actions">
                <a href="<?= $base ?>/contracts/sign-company/<?= $contractId ?>">Read-only</a>
                <a href="<?= $base ?>/contracts/<?= $contractId ?>/editor#signing">Seal</a>
            </div>
        </article>
        <article class="phase-card">
            <span class="phase-index">4</span>
            <strong>Final Distribution</strong>
            <small>Final PDF preview, secure token link, distribution history.</small>
            <div class="phase-actions">
                <a href="<?= $base ?>/contracts/final-pdf/<?= $contractId ?>">PDF</a>
                <a href="<?= $base ?>/contracts/<?= $contractId ?>/editor#distribution">Distribute</a>
            </div>
        </article>
    </div>
</section>

<section class="panel-grid">
    <article class="surface info-card"><strong>DRAFT</strong><span>Use editor and review UI until the draft is approved.</span></article>
    <article class="surface info-card"><strong>AWAITING_CLIENT</strong><span>Use client signing page or hard-copy upload.</span></article>
    <article class="surface info-card"><strong>AWAITING_COMPANY</strong><span>Use company signature and seal controls.</span></article>
</section>

<section class="content-split">
    <div class="surface surface-pad">
        <h2>Recommended frontend test order</h2>
        <ul class="check-list">
            <li>Create or open a draft and save the document body.</li>
            <li>Accept/reject tracked changes in the review interface.</li>
            <li>Submit for signing from editor execution controls.</li>
            <li>Open the client token page and test digital or hard-copy signing.</li>
            <li>Apply company seal from the editor signing panel.</li>
            <li>Send final contract from the distribution panel.</li>
        </ul>
    </div>
    <aside class="surface surface-pad">
        <h2>Backend alignment</h2>
        <ul class="data-list">
            <li>Submit uses Oscar `/api/contracts/{id}/submit`.</li>
            <li>Signing uses Oscar `/api/contracts/{id}/sign`.</li>
            <li>Hard copy uses Oscar `/api/contracts/{id}/upload-hard-copy`.</li>
            <li>Seal uses Oscar `/api/contracts/{id}/seal`.</li>
            <li>Distribution uses Oscar `/api/contracts/{id}/distribute`.</li>
        </ul>
    </aside>
</section>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/app.php';
