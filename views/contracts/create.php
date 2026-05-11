<?php
require_once dirname(__DIR__) . '/components/ui.php';

$title = 'Create New Contract';
$activeNav = 'contracts';
$headerMeta = 'contract drafting';
$showPageHeader = false;
$pageStyles = [BASE_URL . '/public/assets/css/contract-editor.css'];
$pageScripts = [
    BASE_URL . '/public/vendor/tinymce/tinymce.min.js',
    BASE_URL . '/public/assets/js/contract-editor.js',
];

ob_start();
?>
<section class="create-hero surface">
    <div>
        <p>phase 1 starts here</p>
        <h1>Create a contract draft</h1>
        <span>Enter the client details, prepare the first version, then continue into review before sending it for signing.</span>
    </div>
    <a class="button ghost" href="<?= BASE_URL ?>/contracts">Back to contracts</a>
</section>

<section class="create-guide" aria-label="Create contract guide">
    <article class="surface guide-card">
        <span><?= ui_icon('person-vcard') ?></span>
        <div><strong>Client details</strong><small>Name and email are used for the signing link.</small></div>
    </article>
    <article class="surface guide-card">
        <span><?= ui_icon('file-earmark-text') ?></span>
        <div><strong>Draft body</strong><small>The first save creates version 1.</small></div>
    </article>
    <article class="surface guide-card">
        <span><?= ui_icon('check2-square') ?></span>
        <div><strong>Review next</strong><small>Tracked changes are cleared before signing.</small></div>
    </article>
</section>

<div class="create-contract-page">
<?php

$editor_config = [
    'contract_id' => 0,
    'title' => 'Untitled Draft',
    'content' => '<h2>Contract Overview</h2><p>Describe the agreement, parties, services, payment terms, timeline, and signature obligations.</p><h2>Scope of Work</h2><p>Add the services or obligations covered by this contract.</p><h2>Payment Terms</h2><p>Add payment amount, schedule, and conditions.</p>',
    'signing_state' => 'DRAFT',
    'show_side_panel' => false,
];

require dirname(__DIR__) . '/components/contract-editor.php';
?>
</div>
<?php

$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/app.php';
