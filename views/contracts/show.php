<?php
$basePath = '/itec_contract_system';
$assetVersion = time();
$contractId = 1;
$pageTitle = 'Contract Details';
$pageHeading = 'Service Agreement #1';
$pageEyebrow = 'contract details';
$pageLead = 'See the execution path, current obligations, distribution state, and linked pages around the working contract record.';
$activeNav = 'contracts';
$headerMeta = 'contract workspace';
$pageActions = [
    '<a class="button ghost" href="' . $basePath . '/contracts">Back to List</a>',
    '<a class="button" href="' . $basePath . '/contracts/' . $contractId . '/edit">Open Editor</a>'
];

ob_start();
?>
<section class="status-banner">
    <span class="status-pill draft">Draft</span>
    <div>
        <strong>Internal drafting is still open.</strong>
        <div class="muted-copy">Client choice, body lock, company signature, and distribution steps will appear here as the backend state advances.</div>
    </div>
</section>

<section class="panel-grid">
    <article class="surface info-card"><strong>Client</strong><span>Rwanda Tech Group</span></article>
    <article class="surface info-card"><strong>Signing path</strong><span>Client decides at submission</span></article>
    <article class="surface info-card"><strong>Owner</strong><span>Elie</span></article>
</section>

<section class="content-split">
    <div class="page-stack">
        <div class="surface">
            <div class="section-head compact">
                <div><p>Obligations</p><h2>Execution requirements</h2></div>
            </div>
            <div class="surface-pad">
                <ul class="check-list">
                    <li>Client signs digitally or returns a signed hard copy.</li>
                    <li>Company signatory adds the authorized digital signature.</li>
                    <li>The company seal is applied to the final flattened PDF.</li>
                    <li>The audit trail records signer, time, IP, and hash chain.</li>
                </ul>
            </div>
        </div>
        <div class="surface">
            <div class="section-head compact">
                <div><p>Linked records</p><h2>Working assets</h2></div>
            </div>
            <div class="responsive-table">
                <table>
                    <thead>
                        <tr><th>Asset</th><th>Status</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>Document body</td><td>Draft</td><td><a href="<?= $basePath ?>/contracts/<?= $contractId ?>/edit">Open editor</a></td></tr>
                        <tr><td>Version history</td><td>Available</td><td><a href="<?= $basePath ?>/contracts/versions">View versions</a></td></tr>
                        <tr><td>Audit trail</td><td>Prepared</td><td><a href="<?= $basePath ?>/contracts/audit-trail">View timeline</a></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <aside class="surface surface-pad">
        <h2>Current routing</h2>
        <ul class="data-list">
            <li>Drafting and tracked changes stay on the internal team.</li>
            <li>Client execution begins once the contract is submitted.</li>
            <li>Distribution starts only after both company obligations are complete.</li>
        </ul>
    </aside>
</section>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
