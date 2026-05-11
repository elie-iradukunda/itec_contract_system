<?php
require_once dirname(__DIR__) . '/components/ui.php';

$contractId = (int) ($contract_id ?? 1);
$title = 'Contract Versions';
$activeNav = 'contracts';
$headerMeta = 'version control';
$pageTitle = 'Contract Versions';
$pageHeading = 'Version History';
$pageEyebrow = 'document history';
$pageLead = 'Every save creates a recoverable file record during the drafting phase.';
$pageActions = [
    '<a class="button ghost" href="' . BASE_URL . '/contracts/' . $contractId . '/editor#versions">Back to Editor</a>',
];

ob_start();
?>
<section class="notice-banner">
    <strong>Version browser</strong>
    <span>The live version panel is inside the editor side panel so restore controls can respect body lock state.</span>
</section>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/app.php';
