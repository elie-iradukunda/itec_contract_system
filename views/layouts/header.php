<?php
$headerMeta = $headerMeta ?? 'contract operations';
$profileLabel = $profileLabel ?? 'Staff Portal';
$profileSubLabel = $profileSubLabel ?? 'contract team';
?>
<header class="topbar">
    <div class="brand">
        <span class="brand-logo-frame"><img class="brand-logo" src="<?= $basePath ?>/public/assets/logo.png" alt="System logo"></span>
        <span><?= htmlspecialchars($headerMeta) ?></span>
    </div>
    <div class="profile"><span class="avatar"></span><strong><?= htmlspecialchars($profileLabel) ?></strong><small><?= htmlspecialchars($profileSubLabel) ?></small></div>
</header>
