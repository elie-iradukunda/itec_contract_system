<?php
$basePath = '/itec_contract_system';
$assetVersion = time();
$pageTitle = 'Audit Trail';
$pageHeading = 'Contract Audit Trail';
$pageEyebrow = 'chain of custody';
$pageLead = 'Track who changed, reviewed, signed, sealed, and distributed the contract with a clear event-by-event timeline.';
$activeNav = 'contracts';
$headerMeta = 'audit and verification';
$pageActions = [
    '<a class="button ghost" href="' . $basePath . '/views/contracts/show.php">Contract Details</a>',
    '<a class="button" href="' . $basePath . '/views/signatures/verify.php">Verify Signatures</a>'
];

ob_start();
?>
<section class="panel-grid">
    <article class="surface info-card"><strong>Current hash</strong><span>`d0c-7f2-exec-1a9`</span></article>
    <article class="surface info-card"><strong>Latest event</strong><span>Version saved during drafting.</span></article>
    <article class="surface info-card"><strong>Distribution state</strong><span>Waiting for final execution.</span></article>
</section>

<section class="surface">
    <div class="section-head compact">
        <div><p>Event log</p><h2>Full activity timeline</h2></div>
    </div>
    <ul class="audit-list">
        <li><strong>10:42 AM</strong><span>Elie saved version 26 from the in-browser editor.</span></li>
        <li><strong>10:12 AM</strong><span>Reviewer accepted tracked changes for billing language.</span></li>
        <li><strong>09:58 AM</strong><span>Contract uploaded into storage and versioned as v24.</span></li>
        <li><strong>09:10 AM</strong><span>Draft created and assigned to finance for internal review.</span></li>
    </ul>
</section>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
