<?php
$basePath = $basePath ?? '/itec_contract_system';
$assetVersion = $assetVersion ?? time();
$pageTitle = $pageTitle ?? 'Contract Portal';
$pageHeading = $pageHeading ?? $pageTitle;
$pageEyebrow = $pageEyebrow ?? 'contract workspace';
$pageLead = $pageLead ?? '';
$pageContent = $pageContent ?? '';
$pageActions = $pageActions ?? [];
$pageClass = $pageClass ?? '';
$pageScripts = $pageScripts ?? [];
$activeNav = $activeNav ?? 'home';
$headerMeta = $headerMeta ?? 'contract operations';
$profileLabel = $profileLabel ?? 'Staff Portal';
$profileSubLabel = $profileSubLabel ?? 'contract team';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= $basePath ?>/public/assets/css/home.css?v=<?= $assetVersion ?>">
</head>
<body>
    <?php require __DIR__ . '/header.php'; ?>
    <?php require __DIR__ . '/navbar.php'; ?>

    <main class="page <?= htmlspecialchars($pageClass) ?>">
        <section class="page-head">
            <div>
                <p><?= htmlspecialchars($pageEyebrow) ?></p>
                <h1><?= htmlspecialchars($pageHeading) ?></h1>
                <?php if ($pageLead !== ''): ?>
                    <div class="page-lead"><?= $pageLead ?></div>
                <?php endif; ?>
            </div>
            <?php if (!empty($pageActions)): ?>
                <div class="head-actions">
                    <?php foreach ($pageActions as $action): ?>
                        <?= $action ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <?= $pageContent ?>
    </main>

    <?php require __DIR__ . '/footer.php'; ?>
