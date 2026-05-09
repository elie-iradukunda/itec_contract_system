<?php
$basePath = '/itec_contract_system';
$assetVersion = time();
$pageTitle = 'Contract Versions';
$pageHeading = 'Version History';
$pageEyebrow = 'document history';
$pageLead = 'Every save creates a recoverable file record so the drafting phase keeps a complete chain from first draft to final execution.';
$activeNav = 'contracts';
$headerMeta = 'version control';
$pageActions = [
    '<a class="button ghost" href="' . $basePath . '/contracts/1/edit">Back to Editor</a>'
];

ob_start();
?>
<section class="panel-grid">
    <article class="surface info-card"><strong>Current version</strong><span>Version 26 is the active working copy.</span></article>
    <article class="surface info-card"><strong>Stored format</strong><span>Each version preserves a separate `.docx` file path.</span></article>
    <article class="surface info-card"><strong>Restore policy</strong><span>Only draft contracts should restore prior versions.</span></article>
</section>

<section class="surface">
    <div class="section-head compact">
        <div><p>Saved files</p><h2>Document versions</h2></div>
    </div>
    <div class="responsive-table">
        <table>
            <thead>
                <tr><th>Version</th><th>Saved by</th><th>Saved at</th><th>File path</th><th>Action</th></tr>
            </thead>
            <tbody>
                <tr><td>v26</td><td>Elie</td><td>May 9, 2026 10:42</td><td>storage/contracts/1/v26.docx</td><td><a href="<?= $basePath ?>/contracts/1/versions/26/download">Download</a></td></tr>
                <tr><td>v25</td><td>Legal</td><td>May 9, 2026 10:12</td><td>storage/contracts/1/v25.docx</td><td><a href="<?= $basePath ?>/contracts/1/versions/25/download">Download</a></td></tr>
                <tr><td>v24</td><td>Finance</td><td>May 9, 2026 09:58</td><td>storage/contracts/1/v24.docx</td><td><a href="<?= $basePath ?>/contracts/1/versions/24/download">Download</a></td></tr>
            </tbody>
        </table>
    </div>
</section>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
