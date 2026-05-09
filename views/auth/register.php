<?php
$basePath = '/itec_contract_system';
$assetVersion = time();
$pageTitle = 'Register';
$pageHeading = 'Create Account';
$pageEyebrow = 'new portal access';
$pageLead = 'Create a staff or client account that can take part in the contract lifecycle and secure delivery flow.';

ob_start();
?>
<h2>Set up access</h2>
<p>Use this form for new users who need to draft, review, sign, or view final contracts.</p>
<form class="form-grid" action="<?= $basePath ?>/api/auth/register" method="post">
    <label>
        <span>Full name</span>
        <input type="text" name="name" required>
    </label>
    <label>
        <span>Email</span>
        <input type="email" name="email" required>
    </label>
    <label>
        <span>Role</span>
        <select name="role">
            <option>staff</option>
            <option>client</option>
            <option>admin</option>
        </select>
    </label>
    <label>
        <span>Password</span>
        <input type="password" name="password" required>
    </label>
    <div class="field-span form-actions">
        <a class="button ghost" href="<?= $basePath ?>/auth/login">Back to login</a>
        <button class="button" type="submit">Create Account</button>
    </div>
</form>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layouts/guest.php';
