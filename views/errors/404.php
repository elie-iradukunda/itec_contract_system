<?php
$basePath = '/itec_contract_system';
$assetVersion = time();
$pageTitle = '404 Not Found';
$pageHeading = 'Page Not Found';
$pageEyebrow = 'missing destination';
$pageLead = 'The link may be outdated, incomplete, or unavailable in the contract workspace.';

ob_start();
?>
<section class="surface error-card">
    <div class="error-code">404 not found</div>
    <p>Try returning to the contract list, dashboard, or client portal to continue from a known page.</p>
    <div class="head-actions" style="justify-content: center;">
        <a class="button ghost" href="<?= $basePath ?>/contracts">Contracts</a>
        <a class="button" href="<?= $basePath ?>/">Dashboard</a>
    </div>
</section>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layouts/guest.php';
