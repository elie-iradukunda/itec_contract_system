<?php
$basePath = '/itec_contract_system';
$assetVersion = time();
$pageTitle = 'Apply Company Seal';
$pageHeading = 'Apply Company Seal';
$pageEyebrow = 'seal workspace';
$pageLead = 'Preview the certified seal image and apply it only after the company signature is ready on the locked contract.';
$activeNav = 'contracts';
$headerMeta = 'seal workspace';
$pageActions = [
    '<a class="button ghost" href="' . $basePath . '/contracts/final-pdf">Final PDF</a>'
];

ob_start();
?>
<section class="content-split">
    <div class="surface surface-pad">
        <h2>Seal placement</h2>
        <div class="stamp-preview">Certified company seal overlay</div>
    </div>
    <aside class="surface surface-pad">
        <h2>Approval rules</h2>
        <ul class="check-list">
            <li>The body must already be locked.</li>
            <li>The company signatory step should be complete.</li>
            <li>The final PDF should flatten the seal into the executed document.</li>
        </ul>
    </aside>
</section>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
