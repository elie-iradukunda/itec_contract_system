<?php
$basePath = '/itec_contract_system';
$assetVersion = time();
$pageTitle = 'Execution Status';
$pageHeading = 'Execution Status';
$pageEyebrow = 'signing progress';
$pageLead = 'Monitor each contract obligation from draft through client execution, body lock, company signing, and final distribution.';
$activeNav = 'contracts';
$headerMeta = 'execution dashboard';
$pageActions = [
    '<a class="button ghost" href="' . $basePath . '/views/contracts/show.php">Contract Details</a>',
    '<a class="button" href="' . $basePath . '/views/contracts/final-pdf.php">Final PDF</a>'
];

ob_start();
?>
<section class="mini-metrics">
    <article class="surface mini-metric"><strong>Draft</strong><span>Approved internally</span></article>
    <article class="surface mini-metric"><strong>Client</strong><span>Awaiting signature choice</span></article>
    <article class="surface mini-metric"><strong>Company</strong><span>Pending after body lock</span></article>
</section>

<section class="content-split">
    <div class="surface surface-pad">
        <h2>Current stage map</h2>
        <div class="timeline">
            <span class="timeline-step active">Draft and review</span>
            <span class="timeline-step active">Client signing choice</span>
            <span class="timeline-step">Body lock</span>
            <span class="timeline-step">Company sign and seal</span>
            <span class="timeline-step">Final distribution</span>
        </div>
    </div>
    <div class="page-stack">
        <div class="surface surface-pad">
            <h2>Next action</h2>
            <p class="muted-copy">The client must choose digital execution or hard copy return before the backend can lock the body and hand control to the company signatory.</p>
        </div>
        <div class="surface surface-pad">
            <h2>Final transition</h2>
            <p class="muted-copy">Once the signature and seal are complete, the system moves to `FULLY_SIGNED`, creates a cryptographic snapshot, and prepares distribution.</p>
        </div>
    </div>
</section>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
