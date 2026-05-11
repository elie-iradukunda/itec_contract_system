<?php
$basePath = '/itec_contract_system';
$assetVersion = time();
$contractId = 1;
$pageTitle = 'Edit Contract';
$pageHeading = 'Edit Contract Record';
$pageEyebrow = 'metadata and routing';
$pageLead = 'Update contract metadata, ownership, and signing setup before the drafting flow moves into execution.';
$activeNav = 'contracts';
$headerMeta = 'contract workspace';
$pageActions = [
    '<a class="button ghost" href="' . $basePath . '/contracts">Contract List</a>',
    '<a class="button" href="' . $basePath . '/contracts/' . $contractId . '/editor">Open Document Editor</a>'
];

ob_start();
?>
<section class="content-split">
    <form class="surface form-surface form-grid" action="<?= $basePath ?>/contracts/update/<?= $contractId ?>" method="post">
        <label>
            <span>Contract title</span>
            <input type="text" name="title" value="Service Agreement #1">
        </label>
        <label>
            <span>Status</span>
            <select name="status">
                <option selected>Draft</option>
                <option>Awaiting Client</option>
                <option>Client Signed</option>
                <option>Awaiting Company</option>
                <option>Fully Signed</option>
            </select>
        </label>
        <label>
            <span>Internal owner</span>
            <input type="text" name="owner" value="Elie">
        </label>
        <label>
            <span>Signing route</span>
            <select name="signing_route">
                <option selected>Client decides</option>
                <option>Digital first</option>
                <option>Hard copy first</option>
            </select>
        </label>
        <label class="field-span">
            <span>Review notes</span>
            <textarea name="notes">Client requested updated billing clause and a visible signature block on the final page.</textarea>
        </label>
        <div class="field-span form-actions">
            <button class="button ghost" type="button">Save Draft</button>
            <button class="button success" type="button">Submit for Review</button>
        </div>
    </form>

    <div class="page-stack">
        <div class="surface surface-pad">
            <h2>Editing guardrails</h2>
            <ul class="check-list">
                <li>Metadata can be updated while the contract remains in draft.</li>
                <li>Body edits should happen inside the browser editor, not here.</li>
                <li>Once the client signs, the document body must stay frozen.</li>
            </ul>
        </div>
        <div class="surface surface-pad">
            <h2>Connected pages</h2>
            <div class="stack-cards">
                <a class="row-action primary" href="<?= $basePath ?>/contracts/<?= $contractId ?>/editor">Go to editor</a>
                <a class="row-action" href="<?= $basePath ?>/contracts/<?= $contractId ?>/versions">Version history</a>
                <a class="row-action" href="<?= $basePath ?>/contracts/review/<?= $contractId ?>">Review workspace</a>
            </div>
        </div>
    </div>
</section>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
