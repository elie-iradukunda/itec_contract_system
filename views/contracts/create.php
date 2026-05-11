<?php
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

$editor_config = [
    'contract_id' => 0,
    'title' => 'New Contract',
    'content' => '<p>Start drafting your contract here...</p>',
    'signing_state' => 'DRAFT',
    'show_side_panel' => false,
];

require dirname(__DIR__) . '/components/contract-editor.php';

$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/app.php';
