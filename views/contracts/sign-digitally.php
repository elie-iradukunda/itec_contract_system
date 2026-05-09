<?php
$basePath = '/itec_contract_system';
$assetVersion = time();
$pageTitle = 'Digital Signing';
$pageHeading = 'Digital Signing Flow';
$pageEyebrow = 'client execution path';
$pageLead = 'Guide the client through a clear digital signature step, then preserve the contract body before the company finishes execution.';
$activeNav = 'contracts';
$headerMeta = 'signing workspace';
$pageActions = [
    '<a class="button ghost" href="' . $basePath . '/views/clients/portal.php">Back to Client Portal</a>',
    '<a id="digitalFlowSignLink" class="button" href="' . $basePath . '/views/signatures/sign.php">Open Signature Capture</a>'
];
$pageScripts = [];

ob_start();
?>
<section class="content-split">
    <div class="surface surface-pad">
        <h2>Client signing steps</h2>
        <ul class="check-list">
            <li>The client reviews the final draft in the portal.</li>
            <li>The client signs inside the designated signature block.</li>
            <li>The backend records signer identity, timestamp, IP, and document hash.</li>
            <li>The contract body becomes permanently read-only.</li>
        </ul>
        <div class="signature-pad" id="digitalFlowPreview">Client signature capture area</div>
    </div>
    <aside class="page-stack">
        <div class="surface surface-pad">
            <h2>Consent text</h2>
            <p class="muted-copy">By continuing, the signer agrees that the digital signature should be attached to the contract exactly as presented in the locked document state.</p>
        </div>
        <div class="surface surface-pad">
            <h2>Next company step</h2>
            <p class="muted-copy">After client signing, the company representative adds the authorized signature and the certified seal only.</p>
        </div>
    </aside>
</section>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
