<?php
$basePath = $basePath ?? '/itec_contract_system';
$assetVersion = $assetVersion ?? time();
$pageTitle = $pageTitle ?? 'Secure Access';
$pageHeading = $pageHeading ?? $pageTitle;
$pageEyebrow = $pageEyebrow ?? 'account access';
$pageLead = $pageLead ?? '';
$pageContent = $pageContent ?? '';
$pageClass = $pageClass ?? 'guest-page';
$pageScripts = $pageScripts ?? [];
$headerMeta = $headerMeta ?? 'secure access';
$profileLabel = $profileLabel ?? 'Client Access';
$profileSubLabel = $profileSubLabel ?? 'protected workspace';
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

    <main class="page guest-shell <?= htmlspecialchars($pageClass) ?>">
        <section class="auth-layout">
            <div class="auth-copy surface">
                <p class="kicker"><?= htmlspecialchars($pageEyebrow) ?></p>
                <h1><?= htmlspecialchars($pageHeading) ?></h1>
                <?php if ($pageLead !== ''): ?>
                    <div class="page-lead"><?= $pageLead ?></div>
                <?php endif; ?>
                <div class="auth-points">
                    <article><strong>Version control</strong><span>Every save becomes a recoverable document version.</span></article>
                    <article><strong>Execution workflow</strong><span>Client choice, body lock, company seal, final PDF.</span></article>
                    <article><strong>Audit trail</strong><span>Every action is tied to time, signer, and document state.</span></article>
                </div>
            </div>
            <div class="auth-card surface">
                <?= $pageContent ?>
            </div>
        </section>
    </main>

    <?php require __DIR__ . '/footer.php'; ?>
