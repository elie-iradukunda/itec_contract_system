<?php
$basePath = '/itec_contract_system';
$assetVersion = time();
$pageTitle = 'Final Executed PDF';
$pageHeading = 'Final Executed PDF';
$pageEyebrow = 'distribution package';
$pageLead = 'Review the final flattened contract, confirm signature and seal placement, and prepare the client delivery package.';
$activeNav = 'contracts';
$headerMeta = 'distribution package';
$pageActions = [
    '<a class="button ghost" href="' . $basePath . '/contracts/execution-status">Execution Status</a>',
    '<a class="button success" href="' . $basePath . '/contracts/1/edit#distribution">Open Distribution Panel</a>'
];

ob_start();
?>
<section class="content-split">
    <div class="surface doc-preview">
        <h3>Executed document preview</h3>
        <p>This area represents the final flattened PDF with client signature, authorized company signature, and the certified company seal applied.</p>
        <p>The body is immutable and ready for distribution once execution is complete.</p>
    </div>

    <div class="page-stack">
        <div class="surface surface-pad">
            <h2>Final package</h2>
            <ul class="check-list">
                <li>Final PDF is attached to the contract record.</li>
                <li>Secure token access is generated for client viewing.</li>
                <li>Email delivery can include the final executed document.</li>
            </ul>
        </div>
        <div class="surface surface-pad">
            <h2>Seal and signature state</h2>
            <div class="stamp-preview">Certified seal and signer blocks</div>
        </div>
    </div>
</section>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
