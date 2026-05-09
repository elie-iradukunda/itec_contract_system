<?php
$basePath = '/itec_contract_system';
$assetVersion = time();
$pageTitle = '500 Server Error';
$pageHeading = 'Something Went Wrong';
$pageEyebrow = 'internal error';
$pageLead = 'The portal could not finish the requested operation. Contract data should be reviewed before retrying.';

ob_start();
?>
<section class="surface error-card">
    <div class="error-code">500 server error</div>
    <p>Return to the previous page or reopen the contract from the list after the issue is resolved.</p>
    <div class="head-actions" style="justify-content: center;">
        <a class="button ghost" href="<?= $basePath ?>/contracts">Contracts</a>
        <a class="button" href="<?= $basePath ?>/">Dashboard</a>
    </div>
</section>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layouts/guest.php';
