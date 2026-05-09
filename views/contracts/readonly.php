<?php
$basePath = '/itec_contract_system';
$assetVersion = time();
$pageTitle = 'Read Only Contract';
$pageHeading = 'Read-Only Execution View';
$pageEyebrow = 'body lock enforced';
$pageLead = 'The document body is frozen. Only the designated signature and seal blocks remain actionable for the next company step.';
$activeNav = 'contracts';
$headerMeta = 'execution workspace';
$pageActions = [
    '<a class="button warn" href="' . $basePath . '/views/signatures/sign.php">Open Signature Block</a>',
    '<a class="button success" href="' . $basePath . '/views/signatures/seal.php">Open Seal Block</a>'
];

ob_start();
?>
<section class="notice-banner warn">
    <strong>Body lock active</strong>
    <span>No paragraph, clause, or table cell in the contract body should change after client signing.</span>
</section>

<section class="content-split">
    <div class="surface doc-preview">
        <h3>Protected document body</h3>
        <p>Client obligations are complete. The contract body has transitioned into strict read-only mode while the company finishes its execution tasks.</p>
        <p>Signature and seal blocks remain available in designated areas only.</p>
    </div>

    <div class="page-stack">
        <div class="surface surface-pad">
            <h2>Allowed actions</h2>
            <ul class="check-list">
                <li>Apply the authorized company digital signature.</li>
                <li>Overlay the certified seal onto the final executed PDF.</li>
                <li>Generate the final immutable snapshot.</li>
            </ul>
        </div>
        <div class="surface surface-pad">
            <h2>After execution</h2>
            <ul class="bullet-list">
                <li>Transition contract status to fully signed.</li>
                <li>Produce the definitive flattened PDF.</li>
                <li>Distribute email and secure portal access.</li>
            </ul>
        </div>
    </div>
</section>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
