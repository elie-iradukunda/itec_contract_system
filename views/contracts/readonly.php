<?php
require_once dirname(__DIR__) . '/components/ui.php';

$contractId = (int) ($contract_id ?? ($contract['id'] ?? 1));
$state = strtoupper($contract['signing_state'] ?? 'DRAFT');
$title = 'Read Only Contract';
$activeNav = 'contracts';
$headerMeta = 'execution workspace';
$pageTitle = 'Read Only Contract';
$pageHeading = $contract['title'] ?? 'Read-Only Execution View';
$pageEyebrow = 'body lock enforced';
$pageLead = 'Review the locked document and complete company execution, sealing, or final distribution.';
$pageActions = [
    '<a class="button ghost" href="' . BASE_URL . '/contracts/show/' . $contractId . '">Contract Details</a>',
    '<a class="button success" href="' . BASE_URL . '/contracts/' . $contractId . '/editor#signing">Company Actions</a>',
];

ob_start();
?>
<section class="notice-banner warn">
    <strong>Body lock active</strong>
    <span>Current state: <?= ui_e(ui_status_label($state)) ?>. Contract body changes are disabled.</span>
</section>

<section class="content-split">
    <div class="surface doc-preview">
        <h3><?= ui_e($contract['title'] ?? 'Protected document body') ?></h3>
        <div class="readonly-document">
            <?= $contract['content'] ?? '<p>Contract content is not available.</p>' ?>
        </div>
    </div>

    <div class="page-stack">
        <div class="surface surface-pad">
            <h2>Allowed actions</h2>
            <ul class="check-list">
                <li>Company representative signs when the contract is awaiting company action.</li>
                <li>Company seal and approval stamp are applied to the execution copy.</li>
                <li>Final distribution starts only after the contract is fully signed.</li>
            </ul>
        </div>
        <div class="surface surface-pad">
            <h2>Execution links</h2>
            <div class="form-actions">
                <a class="button ghost" href="<?= BASE_URL ?>/contracts/final-pdf/<?= $contractId ?>">Preview PDF</a>
                <a class="button" href="<?= BASE_URL ?>/contracts/<?= $contractId ?>/editor#distribution">Distribution</a>
            </div>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/app.php';
