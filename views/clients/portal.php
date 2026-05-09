<?php
$basePath = '/itec_contract_system';
$assetVersion = time();
$pageTitle = 'Client Portal';
$pageHeading = 'Client Portal';
$pageEyebrow = 'client access';
$pageLead = 'Show the client only what they need: pending signatures, delivered contracts, and secure read-only access to executed files.';
$activeNav = 'clients';
$headerMeta = 'client portal';
$pageActions = [
    '<a class="button ghost" href="' . $basePath . '/views/clients/show.php">Client Profile</a>',
    '<a id="clientHeaderSignLink" class="button" href="' . $basePath . '/views/signatures/sign.php">Digital Signing View</a>'
];
$pageScripts = [
    $basePath . '/public/assets/js/contract-ui-common.js?v=' . $assetVersion,
    $basePath . '/public/assets/js/contract-demo-store.js?v=' . $assetVersion,
    $basePath . '/public/assets/js/client-portal-page.js?v=' . $assetVersion,
    $basePath . '/public/assets/js/portal.js?v=' . $assetVersion
];

ob_start();
?>
<section class="panel-grid">
    <article class="surface info-card"><strong id="clientPendingCount">0</strong><span>Pending signatures</span></article>
    <article class="surface info-card"><strong id="clientFinalCount">0</strong><span>Final downloads</span></article>
    <article class="surface info-card"><strong>Secure links</strong><span>Read-only token access expires after 30 days.</span></article>
</section>

<section class="content-split">
    <div class="surface">
        <div class="section-head compact">
            <div><p>Client tasks</p><h2>Contracts in portal</h2></div>
        </div>
        <div class="responsive-table">
            <table>
                <thead>
                    <tr><th>Contract</th><th>Status</th><th>Available action</th><th>Open</th></tr>
                </thead>
                <tbody id="clientContractsBody"></tbody>
            </table>
        </div>
    </div>
    <div class="page-stack">
        <div class="surface surface-pad">
            <div class="pill-row">
                <span id="clientContractStatus" class="status-pill client">Awaiting Client</span>
                <span id="clientContractPath" class="plain-pill">Client decides</span>
            </div>
            <h2 id="clientContractTitle" style="margin-top: 14px;">Select a contract</h2>
            <p id="clientContractMeta" class="muted-copy">Open a shared contract to read the latest draft and continue the client action.</p>
            <div class="doc-preview client-reader">
                <h3>Contract body</h3>
                <p id="clientContractBody">The selected contract text will appear here for the client to review before signing.</p>
            </div>
        </div>
        <div class="surface surface-pad">
            <h2>Save notes</h2>
            <textarea id="clientContractNotes" placeholder="Add client-side notes or reminders before signing."></textarea>
            <div class="form-actions" style="margin-top: 12px;">
                <button id="saveClientNotesButton" class="button ghost" type="button">Save Notes</button>
            </div>
            <p id="clientPortalMessage" class="muted-copy">Notes are saved in the browser for frontend demo testing.</p>
        </div>
        <div class="surface surface-pad">
            <h2>Client action</h2>
            <div class="detail-actions">
                <a id="clientSignLink" class="button" href="<?= $basePath ?>/views/signatures/sign.php">Sign Digitally</a>
                <button id="clientHardCopyButton" class="button ghost" type="button">Choose Hard Copy</button>
                <a id="clientDownloadFinal" class="button ghost hidden" href="<?= $basePath ?>/views/contracts/final-pdf.php">Open Final PDF</a>
            </div>
        </div>
    </div>
</section>
<script>
    window.contractPortalConfig = { basePath: "<?= $basePath ?>" };
</script>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
