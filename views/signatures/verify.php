<?php
$basePath = '/itec_contract_system';
$assetVersion = time();
$pageTitle = 'Verify Signatures';
$pageHeading = 'Verification Report';
$pageEyebrow = 'signature verification';
$pageLead = 'Check that the recorded signer, document hash, and execution timestamp all match the expected chain of custody.';
$activeNav = 'contracts';
$headerMeta = 'verification report';
$pageActions = [
    '<a class="button ghost" href="' . $basePath . '/contracts/audit-trail">Audit Trail</a>'
];

ob_start();
?>
<section class="page-stack">
    <div class="surface surface-pad">
        <span class="verification-badge">verified chain</span>
        <h2 style="margin-top: 12px;">Signature integrity status</h2>
        <p class="muted-copy">The document hash, signer record, and event timestamp align with the currently expected executed state for this contract.</p>
    </div>
    <div class="surface">
        <div class="section-head compact">
            <div><p>Verification details</p><h2>Recorded values</h2></div>
        </div>
        <div class="responsive-table">
            <table>
                <thead>
                    <tr><th>Field</th><th>Value</th></tr>
                </thead>
                <tbody>
                    <tr><td>Signer</td><td>Authorized Company Representative</td></tr>
                    <tr><td>Hash</td><td>`d0c-7f2-exec-1a9`</td></tr>
                    <tr><td>Signed at</td><td>May 9, 2026 10:42 AM</td></tr>
                    <tr><td>Result</td><td>Signature record consistent</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
