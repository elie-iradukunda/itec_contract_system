<?php
$basePath = '/itec_contract_system';
$assetVersion = time();
$pageTitle = 'Upload Signed Copy';
$pageHeading = 'Upload Returned Hard Copy';
$pageEyebrow = 'staff hard copy workflow';
$pageLead = 'Use this staff page when the client signed outside the portal and the scanned copy needs to be attached back into the contract record.';
$activeNav = 'contracts';
$headerMeta = 'hard copy workflow';
$pageActions = [
    '<a class="button ghost" href="' . $basePath . '/contracts">Back to Contracts</a>'
];

ob_start();
?>
<section class="content-split">
    <form class="surface form-surface form-grid" action="<?= $basePath ?>/contracts/1/upload-signed" method="post" enctype="multipart/form-data">
        <label class="field-span">
            <span>Signed scan</span>
            <div class="upload-dropzone">Drop PDF, PNG, or JPG here</div>
        </label>
        <label>
            <span>Received date</span>
            <input type="date" name="received_at">
        </label>
        <label>
            <span>Checked by</span>
            <input type="text" name="checked_by" value="Elie">
        </label>
        <label class="field-span">
            <span>Verification notes</span>
            <textarea name="notes" placeholder="Confirm signature clarity, page count, and attachment quality."></textarea>
        </label>
        <div class="field-span form-actions">
            <button class="button ghost" type="button">Validate Scan</button>
            <button class="button" type="submit">Attach Signed Copy</button>
        </div>
    </form>

    <div class="page-stack">
        <div class="surface surface-pad">
            <h2>Attachment checklist</h2>
            <ul class="check-list">
                <li>Every page is legible and in the correct order.</li>
                <li>The signed page matches the final reviewed version.</li>
                <li>The file will be linked to the right contract record.</li>
            </ul>
        </div>
        <div class="surface surface-pad">
            <h2>After upload</h2>
            <ul class="bullet-list">
                <li>Client obligation is marked fulfilled.</li>
                <li>The body remains frozen.</li>
                <li>The company proceeds with internal signature and seal.</li>
            </ul>
        </div>
    </div>
</section>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
