<?php
$basePath = '/itec_contract_system';
$assetVersion = time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="<?= $basePath ?>/public/assets/css/home.css?v=<?= $assetVersion ?>">
</head>
<body>
    <!-- Contract system header -->
    <header class="topbar">
        <div class="brand">
            <span class="brand-logo-frame"><img class="brand-logo" src="<?= $basePath ?>/public/assets/logo.png" alt="System logo"></span>
            <span>portal</span>
        </div>
        <div class="profile"><span class="avatar"></span><strong>Staff Portal</strong><small>contract team</small></div>
    </header>

    <!-- Main navigation -->
    <nav class="nav">
        <a class="active" href="<?= $basePath ?>/">Home</a>
        <a href="<?= $basePath ?>/contracts">Contracts</a>
        <a href="<?= $basePath ?>/contracts/1/edit">Editor</a>
        <a href="<?= $basePath ?>/migrate">Migrations</a>
    </nav>

    <!-- Dashboard welcome area -->
    <main class="page">
        <h1><?= htmlspecialchars($title) ?></h1>

        <section class="cards">
            <a class="card blue" href="<?= $basePath ?>/contracts">
                <span>1</span>
                <div><strong>Contracts</strong><small>View contract list</small></div>
            </a>

            <a class="card green" href="<?= $basePath ?>/contracts/1/edit">
                <span>2</span>
                <div><strong>Contract Editor</strong><small>Open contract #1</small></div>
            </a>

            <a class="card red" href="<?= $basePath ?>/migrate">
                <span>3</span>
                <div><strong>Migrations</strong><small>Update database tables</small></div>
            </a>
        </section>

        <section class="panel">
            <h2>Today: In-Browser Contract Editor</h2>
            <p>Open a contract, edit its content in the browser, then save it to the server storage folder.</p>
            <a class="button" href="<?= $basePath ?>/contracts/1/edit">Open Editor</a>
        </section>
    </main>
</body>
</html>
