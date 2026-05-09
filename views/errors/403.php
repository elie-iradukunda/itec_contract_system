<?php
$basePath = '/itec_contract_system';
$assetVersion = time();
$pageTitle = '403 Forbidden';
$pageHeading = 'Access Restricted';
$pageEyebrow = 'permission required';
$pageLead = 'The current account does not have permission to open this workspace or contract action.';

ob_start();
?>
<section class="surface error-card">
    <div class="error-code">403 forbidden</div>
    <p>Request access from the contract administrator or return to a page you are allowed to use.</p>
    <div class="head-actions" style="justify-content: center;">
        <a class="button ghost" href="<?= $basePath ?>/">Home</a>
        <a class="button" href="<?= $basePath ?>/auth/login">Sign In</a>
    </div>
</section>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layouts/guest.php';
