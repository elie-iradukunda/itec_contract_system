<?php
$basePath = '/itec_contract_system';
$assetVersion = time();
$pageTitle = 'Capture Signature';
$pageHeading = 'Capture Digital Signature';
$pageEyebrow = 'signature workspace';
$pageLead = 'This page isolates the signer action so the contract body stays untouched while the system records the signing event.';
$activeNav = 'contracts';
$headerMeta = 'signature workspace';
$pageActions = [
    '<a class="button ghost" href="' . $basePath . '/views/clients/portal.php">Back to Client Portal</a>'
];
$pageScripts = [
    $basePath . '/public/assets/js/contract-ui-common.js?v=' . $assetVersion,
    $basePath . '/public/assets/js/contract-demo-store.js?v=' . $assetVersion,
    $basePath . '/public/assets/js/signature-page.js?v=' . $assetVersion,
    $basePath . '/public/assets/js/portal.js?v=' . $assetVersion
];

ob_start();
?>
<section class="content-split">
    <div class="surface surface-pad">
        <div class="pill-row">
            <span id="signatureStatusPill" class="status-pill client">Awaiting Client</span>
            <span id="signaturePathPill" class="plain-pill">Digital signing</span>
        </div>
        <h2 id="signatureContractTitle" style="margin-top: 14px;">Capture client signature</h2>
        <p id="signatureContractSummary" class="muted-copy">The selected contract summary appears here so the client signs the expected document.</p>
        <form id="signatureCaptureForm" class="form-grid" style="margin-top: 16px;">
            <label class="field-span">
                <span>Signer full name</span>
                <input id="signatureName" name="signer_name" type="text" required placeholder="Client signer">
            </label>
            <label class="field-span">
                <span>Typed signature</span>
                <input id="signatureText" name="signature_text" type="text" required placeholder="Type the signature exactly as it should appear">
            </label>
            <label class="field-span">
                <span>Signature preview</span>
                <div class="signature-pad" id="signaturePreview">Typed signature will appear here</div>
            </label>
            <label class="field-span consent-row">
                <input id="signatureConsent" type="checkbox">
                <span>I confirm that I am authorized to sign this contract and that the document text has been reviewed.</span>
            </label>
            <div class="field-span form-actions">
                <button id="applyClientSignatureButton" class="button" type="submit">Apply Signature</button>
            </div>
        </form>
        <p id="signatureResultMessage" class="muted-copy">The signature event will update the frontend demo status to company action.</p>
    </div>
    <aside class="surface surface-pad">
        <h2>Recorded with signature</h2>
        <ul class="bullet-list">
            <li>Signer identity</li>
            <li>Signing timestamp</li>
            <li>Current document hash</li>
            <li>IP address and user agent</li>
        </ul>
        <div class="notice-banner" style="margin-top: 18px;">
            <strong>Frontend-only test mode</strong>
            <span>This demo stores the signature state in browser storage so you can test the full client journey without backend changes.</span>
        </div>
    </aside>
</section>
<script>
    window.contractPortalConfig = { basePath: "<?= $basePath ?>" };
</script>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
