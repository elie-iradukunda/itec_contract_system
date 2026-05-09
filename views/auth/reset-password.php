<?php
$basePath = '/itec_contract_system';
$assetVersion = time();
$pageTitle = 'Reset Password';
$pageHeading = 'Choose a New Password';
$pageEyebrow = 'account recovery';
$pageLead = 'Set a fresh password and return to the protected contract portal.';

ob_start();
?>
<h2>Create a new password</h2>
<p>Use a strong password for contract records, signatures, and distribution access.</p>
<form class="form-grid" action="<?= $basePath ?>/api/auth/reset-password" method="post">
    <label class="field-span">
        <span>New password</span>
        <input type="password" name="password" required>
    </label>
    <label class="field-span">
        <span>Confirm password</span>
        <input type="password" name="password_confirmation" required>
    </label>
    <div class="field-span form-actions">
        <a class="button ghost" href="<?= $basePath ?>/auth/login">Back to login</a>
        <button class="button" type="submit">Update Password</button>
    </div>
</form>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layouts/guest.php';
