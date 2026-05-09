<?php
$activeNav = $activeNav ?? 'home';
?>
<nav class="nav">
    <a class="<?= $activeNav === 'home' ? 'active' : '' ?>" href="<?= $basePath ?>/">Home</a>
    <a class="<?= $activeNav === 'contracts' ? 'active' : '' ?>" href="<?= $basePath ?>/contracts">Contracts</a>
    <a class="<?= $activeNav === 'clients' ? 'active' : '' ?>" href="<?= $basePath ?>/clients/portal">Clients</a>
    <a class="<?= $activeNav === 'editor' ? 'active' : '' ?>" href="<?= $basePath ?>/contracts/1/edit">Editor</a>
    <a class="<?= $activeNav === 'migrations' ? 'active' : '' ?>" href="<?= $basePath ?>/migrate">Migrations</a>
</nav>
