<?php
if (!defined('BASE_URL')) {
    require_once dirname(__DIR__, 2) . '/config/constants.php';
}

require_once dirname(__DIR__) . '/components/ui.php';

$basePath = BASE_URL;
$assetVersion = $assetVersion ?? time();
$pageTitle = $title ?? $pageTitle ?? 'Contract Management System';
$bodyContent = $content ?? $pageContent ?? '';
$pageStyles = $pageStyles ?? [];
$pageScripts = $pageScripts ?? [];
$showPageHeader = $showPageHeader ?? isset($pageHeading);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= ui_e($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= ui_asset('css/home.css') ?>?v=<?= ui_e($assetVersion) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Rubik:ital,wght@0,300..900;1,300..900&display=swap" rel="stylesheet">
<style>

body {
  font-family: "Rubik", sans-serif;
  font-optical-sizing: auto;
  font-weight: 400;
  font-style: normal;
}

</style>
    <?php foreach ($pageStyles as $style): ?>
        <link rel="stylesheet" href="<?= ui_e($style) ?>?v=<?= ui_e($assetVersion) ?>">
    <?php endforeach; ?>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body>
<?php require __DIR__ . '/header.php'; ?>
<?php require __DIR__ . '/navbar.php'; ?>

<main class="page">
    <?php if ($showPageHeader): ?>
        <!-- Shared page header keeps all contract workspace screens visually consistent. -->
        <section class="page-head">
            <div>
                <?php if (!empty($pageEyebrow)): ?><p><?= ui_e($pageEyebrow) ?></p><?php endif; ?>
                <h1><?= ui_e($pageHeading ?? $pageTitle) ?></h1>
                <?php if (!empty($pageLead)): ?><div class="page-lead"><?= ui_e($pageLead) ?></div><?php endif; ?>
            </div>
            <?php if (!empty($pageActions)): ?>
                <div class="head-actions"><?= implode('', $pageActions) ?></div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?= $bodyContent ?>
</main>

<footer class="site-footer">
    <div>
        <strong>ITEC Contract System</strong>
        <span>Versioned drafting, signing, sealing, and distribution workspace.</span>
    </div>
    <span>&copy; 2026 ITEC LTD. All rights reserved.</span>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= ui_asset('js/contract-ui-common.js') ?>?v=<?= ui_e($assetVersion) ?>"></script>
<?php foreach ($pageScripts as $script): ?>
    <script src="<?= ui_e($script) ?>?v=<?= ui_e($assetVersion) ?>"></script>
<?php endforeach; ?>
</body>
</html>
