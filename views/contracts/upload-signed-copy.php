<?php
require_once dirname(__DIR__) . '/components/ui.php';

$contractId = (int) ($contract_id ?? 1);
$title = 'Upload Signed Copy';
$activeNav = 'contracts';
$headerMeta = 'hard copy workflow';
$pageTitle = 'Upload Signed Copy';
$pageHeading = 'Upload Signed Hard Copy';
$pageEyebrow = 'client signing step';
$pageLead = 'After printing and signing the contract physically, upload a clear scan or photo here so the contract can move to company execution.';
$pageActions = [
    '<a class="button ghost" href="' . BASE_URL . '/sign/' . $contractId . '">Back to Signing Options</a>',
    '<a class="button" href="' . BASE_URL . '/contracts/' . $contractId . '/print-pdf" target="_blank" rel="noopener">Download PDF Again</a>',
];

ob_start();
?>
<section class="signing-stage surface">
    <div class="signing-stage-copy">
        <p>step 2 of 3</p>
        <h2>Upload the signed copy</h2>
        <span>Use this page only after you have printed the contract and signed it physically. Once uploaded, the company team will continue the final countersign and seal process.</span>
    </div>
    <div class="signing-stage-state">
        <strong>After upload</strong>
        <span>Company review + seal</span>
    </div>
</section>

<section class="content-split">
    <form class="surface form-surface form-grid" action="<?= BASE_URL ?>/contracts/<?= $contractId ?>/upload-signed-copy" method="post" enctype="multipart/form-data">
        <label class="field-span">
            <span>Signed scan or photo</span>
            <input type="file" name="signed_copy" accept=".pdf,.png,.jpg,.jpeg" required>
        </label>
        <label class="field-span">
            <span>Optional note</span>
            <textarea name="notes" placeholder="Add a note only if something about the scan needs explanation."></textarea>
        </label>
        <div class="field-span form-actions">
            <button class="button" type="submit">Upload Signed Copy</button>
        </div>
    </form>

    <div class="page-stack">
        <div class="surface surface-pad">
            <h2>Before you upload</h2>
            <ul class="check-list">
                <li>The signature is clearly visible.</li>
                <li>All pages are included and readable.</li>
                <li>The scan or photo matches this contract copy.</li>
            </ul>
        </div>
        <div class="surface surface-pad">
            <h2>What happens next</h2>
            <ul class="bullet-list">
                <li>Your uploaded signed copy is attached to the contract record.</li>
                <li>The client signing step is marked complete.</li>
                <li>The company team continues with countersignature and seal.</li>
            </ul>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/app.php';
