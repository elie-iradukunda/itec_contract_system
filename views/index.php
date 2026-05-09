<?php
$basePath = '/itec_contract_system';
$assetVersion = time();
$pageTitle = 'Contract Portal';
$pageHeading = 'Contract Portal';
$pageEyebrow = 'finance execution workspace';
$pageLead = 'Manage drafting, tracked changes, client signing choice, body lock, company execution, and final distribution from one portal.';
$activeNav = 'home';
$headerMeta = 'contract operations';
$pageActions = [
    '<a class="button ghost" href="' . $basePath . '/contracts">View Contracts</a>',
    '<a class="button" href="' . $basePath . '/contracts#new">New Contract</a>'
];

ob_start();
?>
<section class="metric-grid" aria-label="Contract status summary">
    <article class="metric"><span class="metric-code draft">DR</span><div><strong>12</strong><small>Draft contracts</small></div></article>
    <article class="metric"><span class="metric-code client">CL</span><div><strong>5</strong><small>Awaiting client</small></div></article>
    <article class="metric"><span class="metric-code company">CO</span><div><strong>3</strong><small>Company action</small></div></article>
    <article class="metric"><span class="metric-code final">FN</span><div><strong>18</strong><small>Fully signed</small></div></article>
</section>

<section class="dashboard-grid">
    <div class="surface queue-surface">
        <div class="section-head">
            <div>
                <p>Execution queue</p>
                <h2>Contracts needing attention</h2>
            </div>
            <a href="<?= $basePath ?>/contracts">Open list</a>
        </div>
        <div class="responsive-table">
            <table>
                <thead>
                    <tr><th>Contract</th><th>Client</th><th>Status</th><th>Owner</th><th>Next action</th></tr>
                </thead>
                <tbody>
                    <tr><td>Service Agreement #1</td><td>Rwanda Tech Group</td><td><span class="status-pill draft">Draft</span></td><td>Elie</td><td><a href="<?= $basePath ?>/contracts/1/edit">Edit document</a></td></tr>
                    <tr><td>Financing Contract #8</td><td>Umucyo Stores</td><td><span class="status-pill client">Awaiting Client</span></td><td>Finance</td><td>Client signature</td></tr>
                    <tr><td>Lease Addendum #14</td><td>Kivu Logistics</td><td><span class="status-pill company">Company Action</span></td><td>Legal</td><td>Seal and sign</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <aside class="surface workflow-surface">
        <div class="section-head compact">
            <div>
                <p>Workflow</p>
                <h2>Execution path</h2>
            </div>
        </div>
        <ol class="workflow-list">
            <li><strong>Draft</strong><span>Internal review and tracked changes</span></li>
            <li><strong>Client choice</strong><span>Digital sign or hard copy upload</span></li>
            <li><strong>Body lock</strong><span>Document freezes after client signature</span></li>
            <li><strong>Company execution</strong><span>Authorized signature and certified seal</span></li>
            <li><strong>Distribution</strong><span>Final PDF and secure portal link</span></li>
        </ol>
    </aside>
</section>

<section class="surface activity-surface">
    <div class="section-head">
        <div>
            <p>Chain of custody</p>
            <h2>Recent activity</h2>
        </div>
        <a href="<?= $basePath ?>/views/contracts/audit-trail.php">Audit trail</a>
    </div>
    <div class="activity-grid">
        <article><strong>Version saved</strong><span>Contract #1 saved as a new document version.</span></article>
        <article><strong>Tracked changes ready</strong><span>Reviewer panel prepared for accept and reject actions.</span></article>
        <article><strong>Distribution prepared</strong><span>Final PDF and token link UI ready for backend connection.</span></article>
    </div>
</section>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/layouts/app.php';
