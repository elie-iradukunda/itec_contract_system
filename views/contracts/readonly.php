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
$pageLead = 'The document body is frozen. Only designated execution blocks remain actionable.';
$pageActions = [
    '<a class="button warn" href="' . BASE_URL . '/contracts/sign/' . $contractId . '">Signature Block</a>',
    '<a class="button success" href="' . BASE_URL . '/contracts/' . $contractId . '/editor#signing">Company Seal</a>',
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
                <li>Company representative adds their digital signature when required.</li>
                <li>Certified company seal is applied in the execution block.</li>
                <li>Final distribution starts after FULLY SIGNED.</li>
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
