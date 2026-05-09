<?php
$basePath = '/itec_contract_system';
$assetVersion = time();
$pageTitle = 'Clients';
$pageHeading = 'Clients';
$pageEyebrow = 'relationship overview';
$pageLead = 'Review client accounts, active contract load, and execution readiness from one list.';
$activeNav = 'clients';
$headerMeta = 'client relationships';
$pageActions = [
    '<a class="button ghost" href="' . $basePath . '/views/clients/portal.php">Client Portal</a>'
];

ob_start();
?>
<section class="surface">
    <div class="section-head compact">
        <div><p>Client directory</p><h2>Registered clients</h2></div>
    </div>
    <div class="responsive-table">
        <table>
            <thead>
                <tr><th>Client</th><th>Active contracts</th><th>Pending action</th><th>Last activity</th><th>Profile</th></tr>
            </thead>
            <tbody>
                <tr><td>Rwanda Tech Group</td><td>4</td><td>Draft review</td><td>Today</td><td><a href="<?= $basePath ?>/views/clients/show.php">Open</a></td></tr>
                <tr><td>Umucyo Stores</td><td>2</td><td>Awaiting client signature</td><td>Yesterday</td><td><a href="<?= $basePath ?>/views/clients/show.php">Open</a></td></tr>
                <tr><td>Kivu Logistics</td><td>1</td><td>Company sign and seal</td><td>May 8, 2026</td><td><a href="<?= $basePath ?>/views/clients/show.php">Open</a></td></tr>
            </tbody>
        </table>
    </div>
</section>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
