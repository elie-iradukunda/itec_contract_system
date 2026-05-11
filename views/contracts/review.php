<?php
require_once dirname(__DIR__) . '/components/ui.php';

$contractId = (int) ($contract_id ?? ($contract['id'] ?? 1));
$state = strtoupper($contract['signing_state'] ?? 'DRAFT');
$title = 'Review Contract';
$activeNav = 'contracts';
$headerMeta = 'review workspace';
$pageTitle = 'Review Contract';
$pageHeading = $contract['title'] ?? 'Review Workspace';
$pageEyebrow = 'legal and finance review';
$pageLead = 'Clear tracked changes, confirm the contract is ready, and release the approved draft for client signing.';
$pageActions = [
    '<a class="button ghost" href="' . BASE_URL . '/contracts/' . $contractId . '/editor#versions">Versions</a>',
    '<a class="button" href="' . BASE_URL . '/contracts/' . $contractId . '/editor#changes">Open Change Panel</a>',
];

ob_start();
?>
<section class="panel-grid">
    <article class="surface info-card"><strong>Status</strong><span><?= ui_e(ui_status_label($state)) ?></span></article>
    <article class="surface info-card"><strong>Signature path</strong><span>Client decides between digital and hard copy.</span></article>
    <article class="surface info-card"><strong>Body lock</strong><span><?= $state === 'DRAFT' ? 'Editable during review' : 'Locked for execution' ?></span></article>
</section>

<section class="review-gate surface">
    <div class="section-head compact">
        <div><p>phase 1 release gate</p><h2>Draft & internal review</h2></div>
        <span class="status-pill <?= ui_status_class($state) ?>"><?= ui_e(ui_status_label($state)) ?></span>
    </div>
    <div class="gate-grid">
        <article>
            <strong><?= ui_icon('pencil-square') ?> Editable body</strong>
            <span>Originator can keep drafting while the state remains DRAFT.</span>
        </article>
        <article>
            <strong><?= ui_icon('clock-history') ?> Versions</strong>
            <span>Every save creates a doc_versions record for rollback and audit.</span>
        </article>
        <article>
            <strong><?= ui_icon('check2-square') ?> Tracked changes</strong>
            <span>Reviewers clear pending edits before the client receives the frozen document.</span>
        </article>
        <article>
            <strong><?= ui_icon('send') ?> Submit</strong>
            <span>The approved draft moves to client signing and the body becomes locked.</span>
        </article>
    </div>
</section>

<section class="content-split">
    <div class="page-stack">
        <div class="surface">
            <div class="section-head compact"><div><p>focus areas</p><h2>Reviewer checklist</h2></div></div>
            <div class="surface-pad">
                <ul class="check-list">
            <li>Confirm commercial clauses match the agreed terms.</li>
                    <li>Accept or reject all pending tracked changes.</li>
                    <li>Verify client and company signature blocks are present.</li>
                    <li>Submit for signing only after the document is approved.</li>
                </ul>
            </div>
        </div>
        <div class="surface surface-pad">
            <h2>Review actions</h2>
            <div class="form-actions">
                <a class="button ghost" href="<?= BASE_URL ?>/contracts/<?= $contractId ?>/editor#changes">Tracked changes</a>
                <a class="button" href="<?= BASE_URL ?>/contracts/<?= $contractId ?>/editor#signing">Signing controls</a>
            </div>
        </div>
    </div>

    <aside class="surface surface-pad">
        <h2>Release gate</h2>
        <ul class="bullet-list">
            <li>No unresolved drafting changes remain before submission.</li>
            <li>The client path is clear without staff intervention.</li>
            <li>Company signatory and seal steps are ready once the client signs.</li>
        </ul>
    </aside>
</section>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/app.php';
