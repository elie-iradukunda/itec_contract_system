<?php
require_once dirname(__DIR__) . '/components/ui.php';

$contract = $contract ?? [];
$contractId = (int) ($contract['id'] ?? 1);
$state = strtoupper($contract['signing_state'] ?? 'DRAFT');
$stateOrder = ['DRAFT', 'AWAITING_CLIENT', 'CLIENT_SIGNED', 'AWAITING_COMPANY', 'FULLY_SIGNED'];
$currentIndex = array_search($state, $stateOrder, true);
$currentIndex = $currentIndex === false ? 0 : $currentIndex;

$title = 'Contract Details';
$activeNav = 'contracts';
$headerMeta = 'contract workspace';
$pageTitle = 'Contract Details';
$pageHeading = $contract['title'] ?? 'Contract Details';
$pageEyebrow = 'contract workspace';
$pageLead = 'A guided test workspace for checking drafting, client signing, company execution, and final distribution.';
$pageActions = [
    '<a class="button ghost" href="' . BASE_URL . '/contracts">Back to Contracts</a>',
    '<a class="button" href="' . BASE_URL . '/contracts/' . $contractId . '/editor">Open Workspace</a>',
];

$phaseMap = [
    'DRAFT' => [
        'label' => 'Draft & Internal Review',
        'summary' => 'Prepare the contract, save versions, and review tracked changes before sending it to the client.',
        'actor' => 'Originator and reviewer',
        'body' => 'Editable',
        'primary' => 'Open Draft Workspace',
        'href' => BASE_URL . '/contracts/' . $contractId . '/editor',
        'checklist' => [
            'Edit the contract body and save the draft.',
            'Confirm a new version is recorded after saving.',
            'Open review and accept or reject a tracked change.',
            'Submit the contract for client signing.',
        ],
    ],
    'AWAITING_CLIENT' => [
        'label' => 'Client Signing',
        'summary' => 'The document is frozen while the client chooses digital signing or hard-copy return.',
        'actor' => 'Client',
        'body' => 'Read only',
        'primary' => 'Open Client Signing',
        'href' => BASE_URL . '/contracts/sign/' . $contractId,
        'checklist' => [
            'Confirm the client cannot edit the document body.',
            'Test the digital signature option.',
            'Test the hard-copy upload option if needed.',
            'Confirm the contract advances after client signing.',
        ],
    ],
    'CLIENT_SIGNED' => [
        'label' => 'Client Signed',
        'summary' => 'Client execution is recorded and the workflow is ready to move to company signing.',
        'actor' => 'System handoff',
        'body' => 'Read only',
        'primary' => 'Check Execution Status',
        'href' => BASE_URL . '/contracts/execution-status/' . $contractId,
        'checklist' => [
            'Confirm the client signature appears in the signing history.',
            'Confirm the company handoff is visible.',
            'Refresh the contract and check the next state.',
            'Open the audit trail and verify the client event.',
        ],
    ],
    'AWAITING_COMPANY' => [
        'label' => 'Company Signature & Seal',
        'summary' => 'The company representative signs the frozen contract and applies the company seal.',
        'actor' => 'Company representative',
        'body' => 'Read only',
        'primary' => 'Open Company Signing',
        'href' => BASE_URL . '/contracts/sign-company/' . $contractId,
        'checklist' => [
            'Confirm the document body remains locked.',
            'Add the company signature.',
            'Confirm the seal or approval stamp is applied.',
            'Confirm the contract advances to fully signed.',
        ],
    ],
    'FULLY_SIGNED' => [
        'label' => 'Finalization & Distribution',
        'summary' => 'The signed contract is final. Test final PDF access, secure link viewing, and distribution records.',
        'actor' => 'No edits allowed',
        'body' => 'Locked',
        'primary' => 'Open Final Contract',
        'href' => BASE_URL . '/contracts/view/' . $contractId,
        'checklist' => [
            'Confirm no editing controls are available.',
            'Open or download the final PDF.',
            'Confirm distribution records are listed.',
            'Open the audit trail and verify the full lifecycle.',
        ],
    ],
];

$phase = $phaseMap[$state] ?? $phaseMap['DRAFT'];
$clientName = $contract['client_name'] ?? $contract['company_name'] ?? 'Client pending';
$clientEmail = $contract['client_email'] ?? $contract['email'] ?? 'Not provided';
$createdAt = !empty($contract['created_at']) ? date('M j, Y', strtotime($contract['created_at'])) : 'Not recorded';

ob_start();
?>
<section class="status-banner contract-focus-banner">
    <span class="status-pill <?= ui_status_class($state) ?>"><?= ui_e(ui_status_label($state)) ?></span>
    <div>
        <strong><?= ui_e($phase['label']) ?></strong>
        <div class="muted-copy"><?= ui_e($phase['summary']) ?></div>
    </div>
</section>

<section class="panel-grid">
    <article class="surface info-card">
        <strong><?= ui_icon('person-badge') ?> Client</strong>
        <span><?= ui_e($clientName) ?></span>
    </article>
    <article class="surface info-card">
        <strong><?= ui_icon('envelope') ?> Contact</strong>
        <span><?= ui_e($clientEmail) ?></span>
    </article>
    <article class="surface info-card">
        <strong><?= ui_icon('calendar-check') ?> Created</strong>
        <span><?= ui_e($createdAt) ?></span>
    </article>
</section>

<section class="flow-board surface">
    <div class="section-head compact">
        <div>
            <p>lifecycle progress</p>
            <h2>Contract phase journey</h2>
        </div>
        <a href="<?= ui_e($phase['href']) ?>"><?= ui_e($phase['primary']) ?></a>
    </div>
    <div class="phase-grid">
        <?php foreach ($stateOrder as $index => $phaseState): ?>
            <?php
                $phaseClass = $index < $currentIndex ? 'complete' : ($index === $currentIndex ? 'active' : '');
                $phaseLabel = $phaseMap[$phaseState]['label'];
            ?>
            <article class="phase-card <?= ui_e($phaseClass) ?>">
                <span class="phase-index"><?= $index + 1 ?></span>
                <strong><?= ui_e($phaseLabel) ?></strong>
                <small><?= ui_e($phaseMap[$phaseState]['summary']) ?></small>
                <div class="phase-actions">
                    <span><?= ui_e($phaseMap[$phaseState]['actor']) ?></span>
                    <span><?= ui_e($phaseMap[$phaseState]['body']) ?></span>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="content-split">
    <div class="surface surface-pad">
        <div class="section-head compact">
            <div>
                <p>functional test</p>
                <h2>What to test now</h2>
            </div>
        </div>
        <div class="test-checklist">
            <?php foreach ($phase['checklist'] as $item): ?>
                <div class="test-check">
                    <span><?= ui_icon('check2-circle') ?></span>
                    <p><?= ui_e($item) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <aside class="surface surface-pad">
        <div class="section-head compact">
            <div>
                <p>next action</p>
                <h2><?= ui_e($phase['label']) ?></h2>
            </div>
        </div>
        <p class="muted-copy"><?= ui_e($phase['summary']) ?></p>
        <div class="detail-stack">
            <div><strong>Current actor</strong><span><?= ui_e($phase['actor']) ?></span></div>
            <div><strong>Document body</strong><span><?= ui_e($phase['body']) ?></span></div>
            <div><strong>Contract ID</strong><span>#<?= $contractId ?></span></div>
        </div>
        <div class="form-actions">
            <a class="button" href="<?= ui_e($phase['href']) ?>"><?= ui_e($phase['primary']) ?></a>
            <a class="button ghost" href="<?= BASE_URL ?>/contracts/<?= $contractId ?>/audit">Open Audit</a>
        </div>
    </aside>
</section>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/app.php';
