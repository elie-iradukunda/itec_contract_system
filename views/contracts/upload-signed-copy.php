<?php
require_once dirname(__DIR__) . '/components/ui.php';

$contractId = (int) ($contract_id ?? 1);
$title = 'Upload Signed Copy';
$activeNav = 'contracts';
$headerMeta = 'hard copy workflow';
$pageTitle = 'Upload Signed Copy';
$pageHeading = 'Upload Returned Hard Copy';
$pageEyebrow = 'staff hard copy workflow';
$pageLead = 'Attach the signed scan after the client signs a physical copy.';
$pageActions = [
    '<a class="button ghost" href="' . BASE_URL . '/contracts/' . $contractId . '/editor#signing">Back to Contract</a>',
];

ob_start();
?>
<section class="content-split">
    <form class="surface form-surface form-grid" action="<?= BASE_URL ?>/contracts/<?= $contractId ?>/upload-signed-copy" method="post" enctype="multipart/form-data">
        <!-- Feature E4: upload attaches the returned scan and advances the contract to company action. -->
        <label class="field-span">
            <span>Signed scan</span>
            <input type="file" name="signed_copy" accept=".pdf,.png,.jpg,.jpeg" required>
        </label>
        <label>
            <span>Received date</span>
            <input type="date" name="received_at" value="<?= date('Y-m-d') ?>">
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
            <a class="button ghost" href="<?= BASE_URL ?>/contracts/<?= $contractId ?>/print-pdf" target="_blank" rel="noopener">Open print PDF</a>
            <button class="button" type="submit">Attach Signed Copy</button>
        </div>
    </form>

    <div class="page-stack">
        <div class="surface surface-pad">
            <h2>Attachment checklist</h2>
            <ul class="check-list">
                <li>Every page is legible and in the correct order.</li>
                <li>The signed page matches the final reviewed version.</li>
                <li>The file belongs to contract #<?= $contractId ?>.</li>
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
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/app.php';
