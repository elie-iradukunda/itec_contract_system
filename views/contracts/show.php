<?php
require_once dirname(__DIR__) . '/components/ui.php';

$contract = $contract ?? [];
$contractId = (int) ($contract['id'] ?? 1);
$state = strtoupper($contract['signing_state'] ?? 'DRAFT');
$title = 'Contract Details';
$activeNav = 'contracts';
$headerMeta = 'contract workspace';
$pageTitle = 'Contract Details';
$pageHeading = $contract['title'] ?? 'Contract Details';
$pageEyebrow = 'contract details';
$pageLead = 'Review the current lifecycle state, available interface, and backend action path for this contract.';
$pageActions = [
    '<a class="button ghost" href="' . BASE_URL . '/contracts">Back to List</a>',
    '<a class="button" href="' . BASE_URL . '/contracts/' . $contractId . '/editor">Open Editor</a>',
];

$phaseMap = [
    'DRAFT' => [
        'label' => 'Draft & Internal Review',
        'summary' => 'Originator edits the body, saves versions, and reviewers clear tracked changes.',
        'action' => 'Open editor or review gate',
        'href' => BASE_URL . '/contracts/' . $contractId . '/editor#changes',
    ],
    'AWAITING_CLIENT' => [
        'label' => 'Client Signs',
        'summary' => 'Client chooses digital signature or hard-copy return from a frozen document.',
        'action' => 'Open client signing',
        'href' => BASE_URL . '/contracts/sign/' . $contractId,
    ],
    'CLIENT_SIGNED' => [
        'label' => 'Client Signed',
        'summary' => 'Client signature is recorded. Backend should escalate to company execution.',
        'action' => 'Open execution controls',
        'href' => BASE_URL . '/contracts/' . $contractId . '/editor#signing',
    ],
    'AWAITING_COMPANY' => [
        'label' => 'Company Signs + Seal',
        'summary' => 'Company representative signs, applies seal, and final stamp/snapshot runs.',
        'action' => 'Open company controls',
        'href' => BASE_URL . '/contracts/' . $contractId . '/editor#signing',
    ],
    'FULLY_SIGNED' => [
        'label' => 'Finalization & Distribution',
        'summary' => 'Terminal state. Send final PDF and secure tokenized access link.',
        'action' => 'Open distribution',
        'href' => BASE_URL . '/contracts/' . $contractId . '/editor#distribution',
    ],
];
$phase = $phaseMap[$state] ?? $phaseMap['DRAFT'];

ob_start();
?>
<section class="status-banner">
    <span class="status-pill <?= ui_status_class($state) ?>"><?= ui_e(ui_status_label($state)) ?></span>
    <div>
        <strong><?= ui_e($phase['label']) ?></strong>
        <div class="muted-copy"><?= ui_e($phase['summary']) ?></div>
    </div>
</section>

<section class="panel-grid">
    <article class="surface info-card"><strong>Client</strong><span><?= ui_e($contract['client_name'] ?? $contract['company_name'] ?? 'Client pending') ?></span></article>
    <article class="surface info-card"><strong>Email</strong><span><?= ui_e($contract['client_email'] ?? 'client@itec.local') ?></span></article>
    <article class="surface info-card"><strong>Owner</strong><span><?= ui_e($contract['created_by_name'] ?? $contract['created_by'] ?? 'Staff') ?></span></article>
</section>

<section class="flow-board surface">
    <div class="section-head compact">
        <div><p>phase interfaces</p><h2>Available screens</h2></div>
        <a href="<?= ui_e($phase['href']) ?>"><?= ui_e($phase['action']) ?></a>
    </div>
    <div class="gate-grid">
        <article><strong><?= ui_icon('pencil-square') ?> Draft UI</strong><span>Editor, versions, tracked changes, review gate.</span></article>
        <article><strong><?= ui_icon('person-check') ?> Client UI</strong><span>Token signing page, digital signature, hard-copy upload.</span></article>
        <article><strong><?= ui_icon('shield-check') ?> Company UI</strong><span>Read-only body, company signature, seal action.</span></article>
        <article><strong><?= ui_icon('send-check') ?> Final UI</strong><span>Final PDF preview, token link, distribution record.</span></article>
    </div>
</section>

<section class="content-split">
    <div class="surface">
        <div class="section-head compact">
            <div><p>backend routes</p><h2>Functional test targets</h2></div>
        </div>
        <div class="responsive-table">
            <table>
                <thead><tr><th>Interface</th><th>Backend route</th><th>Status</th></tr></thead>
                <tbody>
                    <tr><td>Submit draft</td><td>/api/contracts/<?= $contractId ?>/submit</td><td>Oscar API</td></tr>
                    <tr><td>Client signature</td><td>/api/contracts/<?= $contractId ?>/sign</td><td>Oscar API</td></tr>
                    <tr><td>Hard copy upload</td><td>/api/contracts/<?= $contractId ?>/upload-hard-copy</td><td>Oscar API</td></tr>
                    <tr><td>Company seal</td><td>/api/contracts/<?= $contractId ?>/seal</td><td>Oscar API</td></tr>
                    <tr><td>Distribution</td><td>/api/contracts/<?= $contractId ?>/distribute</td><td>Oscar API</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <aside class="surface surface-pad">
        <h2>Next test</h2>
        <p class="muted-copy"><?= ui_e($phase['summary']) ?></p>
        <div class="form-actions">
            <a class="button" href="<?= ui_e($phase['href']) ?>"><?= ui_e($phase['action']) ?></a>
            <a class="button ghost" href="<?= BASE_URL ?>/contracts/<?= $contractId ?>/audit">Audit</a>
        </div>
    </aside>
</section>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/app.php';
