<?php
require_once __DIR__ . '/ui.php';

$editor_config = array_merge([
    'contract_id' => 0,
    'title' => 'New Contract',
    'content' => '<p>Start drafting the contract here...</p>',
    'signing_state' => 'DRAFT',
    'readonly' => false,
    'show_side_panel' => true,
    'signatures' => [],
], $editor_config ?? []);

$contractId = (int) $editor_config['contract_id'];
$isNew = $contractId === 0;
$state = strtoupper($editor_config['signing_state'] ?? 'DRAFT');
$content = $editor_config['content'] ?? '';
$base = BASE_URL;

$jsConfig = [
    'contractId' => $contractId,
    'isNew' => $isNew,
    'signingState' => $state,
    'tinyMceBaseUrl' => $base . '/public/vendor/tinymce',
    'createUrl' => $base . '/api/contracts',
    'saveUrl' => $isNew ? $base . '/api/contracts' : $base . '/contracts/' . $contractId . '/save',
    'editUrlTemplate' => $base . '/contracts/__ID__/editor',
    'statusUrl' => $isNew ? null : $base . '/contracts/' . $contractId . '/status',
    'versionsUrl' => $isNew ? null : $base . '/contracts/' . $contractId . '/versions',
    'restoreUrlTemplate' => $base . '/contracts/' . $contractId . '/versions/__VERSION__/restore',
    'downloadUrlTemplate' => $base . '/contracts/' . $contractId . '/versions/__VERSION__/download',
    'changesUrl' => $isNew ? null : $base . '/contracts/' . $contractId . '/changes',
    'acceptChangeUrlTemplate' => $base . '/contracts/' . $contractId . '/changes/__CHANGE__/accept',
    'rejectChangeUrlTemplate' => $base . '/contracts/' . $contractId . '/changes/__CHANGE__/reject',
    'signingChoiceUrl' => $isNew ? null : $base . '/contracts/' . $contractId . '/signing-choice',
    'submitUrl' => $isNew ? null : $base . '/api/contracts/' . $contractId . '/submit',
    'printPdfUrl' => $isNew ? null : $base . '/contracts/' . $contractId . '/print-pdf',
    'uploadSignedUrl' => $isNew ? null : $base . '/api/contracts/' . $contractId . '/upload-hard-copy',
    'signUrl' => $isNew ? null : $base . '/contracts/' . $contractId . '/sign-digitally',
    'sealUrl' => $isNew ? null : $base . '/api/contracts/' . $contractId . '/seal',
    'finalPdfUrl' => $isNew ? null : $base . '/contracts/final-pdf/' . $contractId,
    'distributeUrl' => $isNew ? null : $base . '/api/contracts/' . $contractId . '/distribute',
    'accessUrlTemplate' => $base . '/view/__TOKEN__',
];
?>

<script>
    window.contractEditorConfig = <?= json_encode($jsConfig, JSON_UNESCAPED_SLASHES) ?>;
</script>

<section class="titlebar">
    <div>
        <p><?= $isNew ? 'new draft' : 'contract editor' ?></p>
        <h1><?= ui_e($editor_config['title']) ?></h1>
    </div>
    <span id="statusBadge" class="badge <?= ui_status_class($state) ?>"><?= ui_e(ui_status_label($state)) ?></span>
</section>

<div id="readOnlyBanner" class="warning <?= $state === 'DRAFT' ? 'hidden' : '' ?>">
    <?= ui_icon('lock-fill') ?> Body editing is locked because this contract has entered execution.
</div>

<div id="bodyLockBanner" class="lock-banner <?= $state === 'DRAFT' ? 'hidden' : '' ?>">
    <strong>Body lock active</strong>
    <span id="bodyLockMessage">Only signature, seal, and distribution actions remain available.</span>
</div>

<section class="execution-rail" aria-label="Contract execution phases">
    <article class="execution-step" data-execution-state="DRAFT">
        <span><?= ui_icon('file-earmark-text') ?></span>
        <div><strong>Draft</strong><small>Edit, save versions, and review changes.</small></div>
    </article>
    <article class="execution-step" data-execution-state="AWAITING_CLIENT">
        <span><?= ui_icon('person-check') ?></span>
        <div><strong>Client</strong><small>Locked body with digital or hard-copy signing.</small></div>
    </article>
    <article class="execution-step" data-execution-state="AWAITING_COMPANY">
        <span><?= ui_icon('shield-check') ?></span>
        <div><strong>Company</strong><small>Representative signature, seal, and approval stamp.</small></div>
    </article>
    <article class="execution-step" data-execution-state="FULLY_SIGNED">
        <span><?= ui_icon('send-check') ?></span>
        <div><strong>Final</strong><small>Final PDF, secure link, and distribution record.</small></div>
    </article>
</section>

<?php if ($isNew): ?>
    <form id="contractCreateForm" class="surface form-surface form-grid mb-3">
        <!-- Feature E1: metadata is collected beside the embedded editor before creating the first draft. -->
        <label>
            <span>Contract title</span>
            <input id="contractTitle" name="title" type="text" value="New Contract" required>
        </label>
        <label>
            <span>Document type</span>
            <select id="contractType" name="document_type">
                <option>Service Agreement</option>
                <option>Financing Contract</option>
                <option>Legal Agreement</option>
            </select>
        </label>
        <label class="field-span">
            <span>Description</span>
            <textarea id="contractDescription" name="description" rows="2" placeholder="Short internal description"></textarea>
        </label>
    </form>
<?php endif; ?>

<section class="workspace contract-editor-shell">
    <article class="editor-card">
        <div class="toolbar">
            <div>
                <strong><?= ui_icon('file-earmark-text') ?> Document body</strong>
                <small id="saveMessage">Ready to edit</small>
            </div>
            <button type="button" id="saveButton">
                <span class="spinner hidden"></span><span class="buttonText"><?= $isNew ? 'Create Contract' : 'Save Contract' ?></span>
            </button>
        </div>
        <textarea id="documentEditor" class="document-editor" <?= $state !== 'DRAFT' ? 'readonly' : '' ?>><?= ui_e($content) ?></textarea>
        
        <!-- SIGNATURE BLOCK - Display all signatures -->
        <?php if (!empty($editor_config['signatures'])): ?>
            <div class="signature-block" style="margin-top: 30px; border-top: 2px solid #ddd; padding-top: 20px;">
                <h3 style="margin-bottom: 15px; color: #333;">Document Signatures</h3>
                <div style="display: grid; gap: 15px;">
                    <?php foreach ($editor_config['signatures'] as $sig): ?>
                        <div style="background: #f9f9f9; border-left: 4px solid #007bff; padding: 12px; border-radius: 4px;">
                            <div style="display: flex; justify-content: space-between; align-items: start;">
                                <div>
                                    <strong style="display: block; margin-bottom: 4px; text-transform: capitalize;">
                                        <?= ucfirst(str_replace('_', ' ', $sig['signer_role'])) ?>
                                    </strong>
                                    <span style="color: #666; font-size: 0.9rem;">Signed by: <?= htmlspecialchars($sig['signer_id']) ?></span>
                                </div>
                                <span style="color: #999; font-size: 0.85rem; white-space: nowrap;">
                                    <?= date('M d, Y @ H:i', strtotime($sig['signed_at'])) ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="signature-block" style="margin-top: 30px; border-top: 2px solid #ddd; padding-top: 20px;">
                <p style="color: #999; font-style: italic;">No signatures yet. Signatures will appear here after the contract is signed.</p>
            </div>
        <?php endif; ?>
    </article>

    <?php if ($editor_config['show_side_panel']): ?>
        <aside class="review-panel">
            <div class="panel-tabs">
                <button class="panel-tab active" type="button" data-panel-tab="versions">Versions</button>
                <button class="panel-tab" type="button" data-panel-tab="changes">Changes</button>
                <button class="panel-tab" type="button" data-panel-tab="signing">Signing</button>
                <button class="panel-tab" type="button" data-panel-tab="distribution">Distribution</button>
            </div>

            <section class="panel-section active" data-panel-section="versions">
                <div class="panel-header"><h2>Version history</h2><small id="versionCount">0 saved</small></div>
                <div id="versionsList" class="panel-list"><p class="muted">Save the contract to create versions.</p></div>
            </section>

            <section class="panel-section" data-panel-section="changes">
                <button class="collapse-toggle" type="button" data-collapse-target="changesList">
                    <span>Tracked changes</span><small id="changeCount">0 pending</small>
                </button>
                <div id="changesList" class="panel-list compact-list"></div>
            </section>

            <section class="panel-section" data-panel-section="signing">
                <!-- Feature E4: client choice and hard-copy upload use the existing signing endpoints. -->
                <div class="panel-header"><h2>Signing workflow</h2><small id="phaseInstruction">Send the approved draft to the client when review is complete.</small></div>
                <div class="signing-summary">
                    <div class="lock-status">
                        <span id="lockPill" class="lock-pill <?= $state === 'DRAFT' ? 'draft' : 'locked' ?>">Draft editable</span>
                        <small id="lockStatusText">Body editing is open until signing starts.</small>
                    </div>
                    <div class="phase-action-grid">
                        <button id="submitForSigning" class="primary-action" type="button" <?= $isNew ? 'disabled' : '' ?>>Submit for signing</button>
                        <button id="openSigningChoice" class="secondary-action" type="button" <?= $isNew ? 'disabled' : '' ?>>Client path</button>
                    </div>
                    <label class="recipient-field" for="clientEmails">
                        <span>Client recipient emails</span>
                        <textarea id="clientEmails" rows="3" placeholder="client@example.com, second@example.com"><?= ui_e($editor_config['client_email'] ?? '') ?></textarea>
                        <small>Use commas, spaces, or new lines for multiple recipients.</small>
                    </label>
                </div>
                <div class="signature-actions">
                    <h3>Signature and seal</h3>
                    <a id="signatureAction" class="signature-action" href="<?= ui_e($jsConfig['signUrl'] ?? '#') ?>">Open signature block</a>
                    <a id="sealAction" class="signature-action" href="#">Apply company seal</a>
                    <small id="signatureActionHint">These actions become available as the contract moves through signing.</small>
                </div>
                <form id="signedCopyForm" class="upload-box" enctype="multipart/form-data">
                    <label for="signedCopyFile">Returned hard-copy scan</label>
                    <input id="signedCopyFile" name="signed_copy" type="file" accept=".pdf,.png,.jpg,.jpeg">
                    <button type="submit">Upload signed scan</button>
                    <small id="uploadMessage">Attach the scan after the client signs a printed copy.</small>
                </form>
            </section>

            <section class="panel-section" data-panel-section="distribution">
                <!-- Feature E5: distribution unlocks after company execution is complete. -->
                <div class="panel-header"><h2>Final distribution</h2><small>Available after the contract is fully signed.</small></div>
                <form id="distributionForm" class="distribution-box">
                    <label for="recipientEmail">Client email</label>
                    <input id="recipientEmail" name="recipient_email" type="email" placeholder="client@example.com">
                    <button id="distributeButton" type="submit" disabled>Send final contract</button>
                    <small id="distributionStateText">Available after full execution</small>
                    <small id="distributionMessage"></small>
                </form>
                <div id="distributionResult" class="distribution-result hidden">
                    <strong>Secure read-only link</strong>
                    <a id="portalLink" href="#"></a>
                    <small id="expiryLabel">Expires in 30 days</small>
                </div>
                <div class="signature-actions">
                    <a id="finalPdfPreview" class="secondary-action disabled" href="<?= ui_e($jsConfig['finalPdfUrl'] ?? '#') ?>">Preview final PDF</a>
                </div>
            </section>
        </aside>
    <?php endif; ?>
</section>

<div id="signingModal" class="modal-backdrop hidden">
    <section class="modal">
        <header class="modal-header">
            <div><p>client execution</p><h2>Choose signing path</h2></div>
            <button id="closeSigningModal" class="icon-button" type="button" aria-label="Close"><?= ui_icon('x-lg') ?></button>
        </header>
        <div class="choice-grid">
            <article class="choice-card">
                <strong><?= ui_icon('pen') ?> Digital sign</strong>
                <small>Client signs in the portal and the document body locks immediately.</small>
                <button type="button" data-signing-choice="digital">Use digital signing</button>
            </article>
            <article class="choice-card">
                <strong><?= ui_icon('printer') ?> Hard copy</strong>
                <small>Generate a print-ready PDF, then upload the returned signed scan.</small>
                <button type="button" data-signing-choice="hard_copy">Generate hard copy</button>
            </article>
        </div>
        <div class="modal-actions">
            <span id="signingMessage">Choose the path requested by the client.</span>
            <a href="<?= ui_e($jsConfig['printPdfUrl'] ?? '#') ?>" target="_blank" rel="noopener">Print PDF</a>
        </div>
    </section>
</div>
