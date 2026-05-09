<?php
$basePath = '/itec_contract_system';
$assetVersion = time();
$pageTitle = 'Forgot Password';
$pageHeading = 'Reset Access';
$pageEyebrow = 'password recovery';
$pageLead = 'Request a reset link for your secure portal account so you can return to the contract workflow quickly.';

ob_start();
?>
<h2>Reset your password</h2>
<p>Enter the account email and the system can prepare password reset instructions.</p>
<form class="form-grid" action="<?= $basePath ?>/api/auth/forgot-password" method="post">
    <label class="field-span">
        <span>Email address</span>
        <input type="email" name="email" required>
    </label>
    <div class="field-span form-actions">
        <a class="button ghost" href="<?= $basePath ?>/auth/login">Back to login</a>
        <button class="button" type="submit">Send Reset Link</button>
    </div>
</form>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layouts/guest.php';
