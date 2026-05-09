<?php
$basePath = '/itec_contract_system';
$state = strtoupper($contract['signing_state'] ?? 'DRAFT');
$isReadOnly = $state !== 'DRAFT';
$assetVersion = time();
$editorContent = $contract['content'] ?? '<p></p>';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($contract['title'] ?? 'Contract Editor') ?></title>
    <link rel="stylesheet" href="<?= $basePath ?>/public/assets/css/contract-editor.css?v=<?= $assetVersion ?>">
</head>
<body>
    <!-- Contract system shell -->
    <header class="topbar">
        <div class="brand">
            <span class="brand-logo-frame"><img class="brand-logo" src="<?= $basePath ?>/public/assets/logo.png" alt="System logo"></span>
            <span>contract editor</span>
        </div>
        <div class="profile"><span class="avatar"></span><strong>Staff Portal</strong><small>contract team</small></div>
    </header>

    <!-- Page navigation -->
    <nav class="nav">
        <a href="<?= $basePath ?>/">Home</a>
        <a class="active" href="<?= $basePath ?>/contracts">Contracts</a>
        <a href="<?= $basePath ?>/contracts/<?= (int) $contract['id'] ?>/edit">Editor</a>
        <a href="<?= $basePath ?>/migrate">Migrations</a>
    </nav>

    <!-- Contract editor UI -->
    <main class="page" data-contract-editor data-state="<?= htmlspecialchars($state) ?>">
        <section class="titlebar">
            <div>
                <p>In-browser document workspace</p>
                <h1><?= htmlspecialchars($contract['title'] ?? 'Untitled Contract') ?></h1>
            </div>
            <span id="statusBadge" class="badge <?= strtolower($state) ?>"><?= htmlspecialchars($state) ?></span>
        </section>

        <div id="readOnlyBanner" class="warning <?= $isReadOnly ? '' : 'hidden' ?>">
            Read Only: this contract is no longer in Draft state and cannot be edited.
        </div>

        <div class="workspace">
            <section class="editor-card">
                <div class="toolbar">
                    <div>
                        <strong>Contract #<?= (int) $contract['id'] ?></strong>
                        <small id="saveMessage">Ready to edit</small>
                    </div>
                    <button id="saveButton" type="button" <?= $isReadOnly ? 'disabled' : '' ?>>
                        <span class="spinner hidden"></span><span class="buttonText">Save Contract</span>
                    </button>
                </div>

                <textarea id="documentEditor"
                          class="document-editor"
                          data-placeholder="Start editing contract text..."><?= htmlspecialchars($editorContent, ENT_QUOTES, 'UTF-8') ?></textarea>
            </section>

            <aside class="versions-panel" aria-label="Version history">
                <div class="versions-header">
                    <h2>Versions</h2>
                    <small id="versionCount">0 saved</small>
                </div>
                <div id="versionsList" class="versions-list">
                    <p class="muted">Loading history...</p>
                </div>
            </aside>
        </div>
    </main>

    <script>
        window.contractEditorConfig = {
            saveUrl: "<?= $basePath ?>/contracts/<?= (int) $contract['id'] ?>/save",
            statusUrl: "<?= $basePath ?>/contracts/<?= (int) $contract['id'] ?>/status",
            versionsUrl: "<?= $basePath ?>/contracts/<?= (int) $contract['id'] ?>/versions",
            restoreUrlTemplate: "<?= $basePath ?>/contracts/<?= (int) $contract['id'] ?>/versions/__VERSION__/restore",
            downloadUrlTemplate: "<?= $basePath ?>/contracts/<?= (int) $contract['id'] ?>/versions/__VERSION__/download",
            tinyMceBaseUrl: "<?= $basePath ?>/public/vendor/tinymce",
            signingState: "<?= htmlspecialchars($state) ?>"
        };
    </script>
    <script src="<?= $basePath ?>/public/vendor/tinymce/tinymce.min.js"></script>
    <script src="<?= $basePath ?>/public/assets/js/contract-editor.js?v=<?= $assetVersion ?>"></script>
</body>
</html>
