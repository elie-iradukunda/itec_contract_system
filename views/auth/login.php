<?php
$basePath = '/itec_contract_system';
$assetVersion = time();
$pageTitle = 'Login';
$pageHeading = 'Sign In';
$pageEyebrow = 'staff and client access';
$pageLead = 'Access contract drafting, signing, audit, and distribution tools through the secure portal.';

ob_start();
?>
<h2>Welcome back</h2>
<p>Use your email and password to continue into the contract workspace.</p>
<form class="form-grid" action="<?= $basePath ?>/api/auth/login" method="post">
    <label class="field-span">
        <span>Email address</span>
        <input type="email" name="email" placeholder="you@example.com" required>
    </label>
    <label class="field-span">
        <span>Password</span>
        <input type="password" name="password" placeholder="Enter password" required>
    </label>
    <div class="field-span form-actions">
        <a class="button ghost" href="<?= $basePath ?>/auth/forgot-password">Forgot password</a>
        <button class="button" type="submit">Sign In</button>
    </div>
</form>
<div class="pill-row" style="margin-top: 16px;">
    <span class="plain-pill">Digital signing</span>
    <span class="plain-pill">Version control</span>
    <span class="plain-pill">Audit trail</span>
</div>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layouts/guest.php';
