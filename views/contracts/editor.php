<!-- editor  -->
 
<?php
$title = 'Contract Editor - ' . ($contract['title'] ?? 'New Contract');
$activeNav = 'editor';
$headerMeta = 'document editor';
$showPageHeader = false;
$pageStyles = [
    BASE_URL . '/public/assets/css/contract-editor.css',
    'https://cdn.quilljs.com/1.3.6/quill.snow.css',
];
$pageScripts = [
    'https://cdn.quilljs.com/1.3.6/quill.js',
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
    'client_name' => $contract['client_name'] ?? '',
    'client_email' => $contract['client_email'] ?? '',
];

require dirname(__DIR__) . '/components/contract-editor.php';

$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/app.php';
