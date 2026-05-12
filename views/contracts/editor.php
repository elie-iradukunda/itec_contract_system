<!-- editor  -->
 
<?php
$title = 'Contract Editor - ' . ($contract['title'] ?? 'New Contract');
$activeNav = 'editor';
$headerMeta = 'document editor';
$showPageHeader = false;
$pageStyles = [BASE_URL . '/public/assets/css/contract-editor.css'];
$pageScripts = [
    BASE_URL . '/public/vendor/tinymce/tinymce.min.js',
    BASE_URL . '/public/assets/js/contract-editor.js',
];

ob_start();

$editor_config = [
    'contract_id' => $contract['id'] ?? 0,
    'title' => $contract['title'] ?? 'New Contract',
    'content' => $contract['content'] ?? '',
    'signing_state' => $contract['signing_state'] ?? 'DRAFT',
    'readonly' => ($contract['signing_state'] ?? 'DRAFT') !== 'DRAFT',
    'show_side_panel' => true,
    'signatures' => $signatures ?? [],
];

require dirname(__DIR__) . '/components/contract-editor.php';

$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/app.php';
