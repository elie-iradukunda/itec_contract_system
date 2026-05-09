<?php
$basePath = '/itec_contract_system';
$assetVersion = time();
$pageTitle = 'Dashboard';
$pageHeading = 'Operations Dashboard';
$pageEyebrow = 'finance system';
$pageLead = 'A quick operational view of contract drafting, execution, audit readiness, and client distribution workload.';
$activeNav = 'home';
$headerMeta = 'operations dashboard';
$pageActions = [
    '<a class="button ghost" href="' . $basePath . '/contracts">Contracts</a>',
    '<a class="button" href="' . $basePath . '/views/clients/portal.php">Client Portal</a>'
];

ob_start();
?>
<section class="metric-grid">
    <article class="metric"><span class="metric-code draft">DR</span><div><strong>12</strong><small>Drafting</small></div></article>
    <article class="metric"><span class="metric-code client">CL</span><div><strong>5</strong><small>Awaiting client</small></div></article>
    <article class="metric"><span class="metric-code company">CO</span><div><strong>3</strong><small>Company action</small></div></article>
    <article class="metric"><span class="metric-code final">FN</span><div><strong>18</strong><small>Finalized</small></div></article>
</section>

<section class="banner-card surface">
    <h2>Today’s focus</h2>
    <p>Keep drafting work moving, convert client decisions into signatures or uploads, then finish company execution cleanly so final PDFs can be distributed without breaking the custody chain.</p>
</section>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
