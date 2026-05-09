<?php
$basePath = '/itec_contract_system';
$assetVersion = time();
$pageTitle = '419 Session Expired';
$pageHeading = 'Session Expired';
$pageEyebrow = 'refresh required';
$pageLead = 'Your session is no longer active, so the protected contract action cannot continue safely.';

ob_start();
?>
<section class="surface error-card">
    <div class="error-code">419 session expired</div>
    <p>Refresh the page and sign in again before retrying the save, signature, or upload action.</p>
    <div class="head-actions" style="justify-content: center;">
        <a class="button ghost" href="<?= $basePath ?>/">Home</a>
        <a class="button" href="<?= $basePath ?>/auth/login">Sign In Again</a>
    </div>
</section>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layouts/guest.php';
