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

        <div id="bodyLockBanner" class="lock-banner <?= $isReadOnly ? '' : 'hidden' ?>">
            <strong>Body lock active</strong>
            <span id="bodyLockMessage">The document body is read-only. Signature and seal actions remain available for the next party.</span>
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

            <aside class="review-panel" aria-label="Contract review tools">
                <div class="panel-tabs" role="tablist" aria-label="Review tools">
                    <button class="panel-tab active" type="button" data-panel-tab="versions">Versions</button>
                    <button class="panel-tab" type="button" data-panel-tab="changes">Changes</button>
                    <button class="panel-tab" type="button" data-panel-tab="signing">Signing</button>
                    <button class="panel-tab" type="button" data-panel-tab="distribution">Distribute</button>
                </div>

                <section class="panel-section active" data-panel-section="versions">
                    <div class="panel-header">
                        <h2>Versions</h2>
                        <small id="versionCount">0 saved</small>
                    </div>
                    <div id="versionsList" class="panel-list">
                        <p class="muted">Loading history...</p>
                    </div>
                </section>

                <section class="panel-section" data-panel-section="changes">
                    <button class="collapse-toggle" type="button" data-collapse-target="trackedChangesBody" aria-expanded="true">
                        <span>Tracked Changes</span><small id="changeCount">0 pending</small>
                    </button>
                    <div id="trackedChangesBody" class="collapsible-body">
                        <div id="changesList" class="panel-list compact-list">
                            <p class="muted">Loading tracked changes...</p>
                        </div>
                    </div>
                </section>

                <section class="panel-section" data-panel-section="signing">
                    <div class="panel-header">
                        <h2>Signing</h2>
                        <small>Client flow</small>
                    </div>
                    <div class="signing-summary">
                        <div class="lock-status">
                            <span id="lockPill" class="lock-pill <?= $isReadOnly ? 'locked' : 'draft' ?>"><?= $isReadOnly ? 'Body locked' : 'Draft editable' ?></span>
                            <small id="lockStatusText"><?= $isReadOnly ? 'Only signing and sealing actions should continue.' : 'Body editing is open until signing starts.' ?></small>
                        </div>
                        <button id="openSigningChoice" class="primary-action" type="button">Choose Signing Method</button>
                        <a class="secondary-action" href="<?= $basePath ?>/contracts/<?= (int) $contract['id'] ?>/print-pdf" target="_blank" rel="noopener">Preview Print PDF</a>
                    </div>
                    <div class="signature-actions">
                        <h3>Next party actions</h3>
                        <a id="signatureAction" class="signature-action" href="<?= $basePath ?>/views/signatures/sign.php?id=<?= (int) $contract['id'] ?>">Open Signature Block</a>
                        <a id="sealAction" class="signature-action" href="<?= $basePath ?>/views/signatures/seal.php?id=<?= (int) $contract['id'] ?>">Open Seal Block</a>
                        <small id="signatureActionHint">These become the primary actions after the backend records the first signature.</small>
                    </div>
                    <form id="signedCopyForm" class="upload-box" enctype="multipart/form-data">
                        <label for="signedCopyFile">Returned signed scan</label>
                        <input id="signedCopyFile" name="signed_copy" type="file" accept=".pdf,.png,.jpg,.jpeg">
                        <button type="submit">Upload Scan</button>
                        <small id="uploadMessage">PDF, PNG, or JPG accepted.</small>
                    </form>
                </section>

                <section class="panel-section" data-panel-section="distribution">
                    <div class="panel-header">
                        <h2>Distribution</h2>
                        <small id="distributionStateText">Available after full execution</small>
                    </div>
                    <form id="distributionForm" class="distribution-box">
                        <label for="recipientEmail">Client email</label>
                        <input id="recipientEmail" name="recipient_email" type="email" placeholder="client@example.com">
                        <button id="distributeButton" type="submit">Send Final PDF + Portal Link</button>
                        <a id="finalPdfPreview" class="secondary-action disabled" href="<?= $basePath ?>/contracts/<?= (int) $contract['id'] ?>/final-pdf" target="_blank" rel="noopener" aria-disabled="true">Preview Final PDF</a>
                        <small id="distributionMessage">The backend will email the final PDF and generate a 30-day read-only link.</small>
                    </form>
                    <div id="distributionResult" class="distribution-result hidden">
                        <strong>Read-only portal link</strong>
                        <a id="portalLink" href="#" target="_blank" rel="noopener"></a>
                        <small id="expiryLabel">Expires in 30 days</small>
                    </div>
                </section>
            </aside>
        </div>
    </main>

    <div id="signingModal" class="modal-backdrop hidden" role="dialog" aria-modal="true" aria-labelledby="signingModalTitle">
        <section class="modal">
            <div class="modal-header">
                <div>
                    <p>Client signing choice</p>
                    <h2 id="signingModalTitle">Select how this contract will be signed</h2>
                </div>
                <button id="closeSigningModal" class="icon-button" type="button" aria-label="Close signing choice">x</button>
            </div>

            <div class="choice-grid">
                <article class="choice-card">
                    <strong>Digital Sign</strong>
                    <small>Capture the client signature directly in the portal and keep the document workflow online.</small>
                    <button type="button" data-signing-choice="digital">Use Digital Sign</button>
                </article>

                <article class="choice-card">
                    <strong>Hard Copy</strong>
                    <small>Generate a print-ready PDF, then upload the returned signed scan when staff receive it.</small>
                    <button type="button" data-signing-choice="hard_copy">Use Hard Copy</button>
                </article>
            </div>

            <div class="modal-actions">
                <a id="printPdfLink" href="<?= $basePath ?>/contracts/<?= (int) $contract['id'] ?>/print-pdf" target="_blank" rel="noopener">Open print-ready PDF</a>
                <span id="signingMessage">Choose an option to continue.</span>
            </div>
        </section>
    </div>

    <script>
        window.contractEditorConfig = {
            saveUrl: "<?= $basePath ?>/contracts/<?= (int) $contract['id'] ?>/save",
            statusUrl: "<?= $basePath ?>/contracts/<?= (int) $contract['id'] ?>/status",
            versionsUrl: "<?= $basePath ?>/contracts/<?= (int) $contract['id'] ?>/versions",
            restoreUrlTemplate: "<?= $basePath ?>/contracts/<?= (int) $contract['id'] ?>/versions/__VERSION__/restore",
            downloadUrlTemplate: "<?= $basePath ?>/contracts/<?= (int) $contract['id'] ?>/versions/__VERSION__/download",
            changesUrl: "<?= $basePath ?>/contracts/<?= (int) $contract['id'] ?>/changes",
            acceptChangeUrlTemplate: "<?= $basePath ?>/contracts/<?= (int) $contract['id'] ?>/changes/__CHANGE__/accept",
            rejectChangeUrlTemplate: "<?= $basePath ?>/contracts/<?= (int) $contract['id'] ?>/changes/__CHANGE__/reject",
            signingChoiceUrl: "<?= $basePath ?>/contracts/<?= (int) $contract['id'] ?>/signing-choice",
            printPdfUrl: "<?= $basePath ?>/contracts/<?= (int) $contract['id'] ?>/print-pdf",
            uploadSignedUrl: "<?= $basePath ?>/contracts/<?= (int) $contract['id'] ?>/upload-signed",
            distributeUrl: "<?= $basePath ?>/contracts/<?= (int) $contract['id'] ?>/distribute",
            finalPdfUrl: "<?= $basePath ?>/contracts/<?= (int) $contract['id'] ?>/final-pdf",
            accessUrlTemplate: "<?= $basePath ?>/access/__TOKEN__",
            tinyMceBaseUrl: "<?= $basePath ?>/public/vendor/tinymce",
            signingState: "<?= htmlspecialchars($state) ?>"
        };
    </script>
    <script src="<?= $basePath ?>/public/vendor/tinymce/tinymce.min.js"></script>
    <script src="<?= $basePath ?>/public/assets/js/contract-ui-common.js?v=<?= $assetVersion ?>"></script>
    <script src="<?= $basePath ?>/public/assets/js/contract-editor.js?v=<?= $assetVersion ?>"></script>
</body>
</html>
