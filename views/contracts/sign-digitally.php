<?php
require_once dirname(__DIR__) . '/components/ui.php';

$contractId = (int) ($contract_id ?? 1);
$title = 'Digital Signing';
$activeNav = 'contracts';
$headerMeta = 'client execution';
$pageTitle = 'Digital Signing';
$pageHeading = 'Digital Signing';
$pageEyebrow = 'client execution path';
$pageLead = 'Choose digital signing or download a hard copy PDF for physical execution.';
$pageActions = [
    '<a class="button ghost" href="' . BASE_URL . '/clients/portal">Back to Client Portal</a>',
    '<a class="button" href="' . BASE_URL . '/contracts/' . $contractId . '/editor#signing">Open Contract</a>',
];

ob_start();
?>
<section class="signing-stage surface">
    <div class="signing-stage-copy">
        <p>awaiting client</p>
        <h2>Client execution workspace</h2>
        <span>The body is frozen. The client can sign digitally or complete the hard-copy path without changing contract text.</span>
    </div>
    <div class="signing-stage-state">
        <strong>Next backend state</strong>
        <span>AWAITING_COMPANY</span>
    </div>
</section>

<section class="content-split">
    <div class="surface surface-pad execution-card">
        <div class="section-head compact no-border"><div><p>digital signature</p><h2>Portal signing</h2></div></div>
        <p class="muted-copy">By signing, the client confirms the contract body is accepted exactly as presented.</p>
        <form id="digitalSignForm" class="form-grid" data-sign-url="<?= BASE_URL ?>/api/contracts/<?= $contractId ?>/sign">
            <!-- Feature E4: this form calls the existing digital signature endpoint and lets the backend lock the body. -->
            <input type="hidden" name="role" value="client">
            <label class="field-span">
                <span>Signer email</span>
                <input type="email" name="signer_id" value="client@itec.local" required>
            </label>
            <label class="field-span">
                <span>Typed signature</span>
                <input type="text" name="typed_signature" placeholder="Full legal name" required>
            </label>
            <div class="signature-pad signature-capture">
                <strong>Signature capture</strong>
                <span>Typed name is stored with the digital signing action.</span>
            </div>
            <div class="field-span form-actions">
                <button class="button" type="submit">Sign Digitally</button>
                <span id="digitalSignMessage" class="muted-copy"></span>
            </div>
        </form>
    </div>

    <aside class="page-stack">
        <div class="surface surface-pad execution-card">
            <div class="section-head compact no-border"><div><p>hard copy</p><h2>Print and upload</h2></div></div>
            <p class="muted-copy">Download the print-ready PDF, sign it physically, then staff can upload the scan.</p>
            <div class="form-actions">
                <a class="button ghost" href="<?= BASE_URL ?>/contracts/<?= $contractId ?>/print-pdf" target="_blank" rel="noopener">Download PDF</a>
                <a class="button" href="<?= BASE_URL ?>/contracts/<?= $contractId ?>/upload-hard-copy">Upload scan</a>
            </div>
        </div>
        <div class="surface surface-pad execution-card">
            <div class="section-head compact no-border"><div><p>after signing</p><h2>Company handoff</h2></div></div>
            <div class="handoff-list">
                <span><?= ui_icon('lock-fill') ?> Body remains frozen</span>
                <span><?= ui_icon('envelope') ?> Company rep receives the next action</span>
                <span><?= ui_icon('fingerprint') ?> Signature hash is stored in audit trail</span>
            </div>
        </div>
    </aside>
</section>

<script>
document.getElementById('digitalSignForm')?.addEventListener('submit', async function (event) {
    event.preventDefault();
    const message = document.getElementById('digitalSignMessage');
    message.textContent = 'Signing contract...';

    try {
        const response = await fetch(this.dataset.signUrl, {
            method: 'POST',
            body: new FormData(this),
            headers: { Accept: 'application/json' }
        });
        const result = await (window.ContractUi ? ContractUi.responseJson(response) : response.json());
        if (!response.ok || result.success === false) throw new Error(result.message || result.error || 'Signing failed');
        message.textContent = 'Signed successfully. Redirecting to execution view...';
        window.location.href = '<?= BASE_URL ?>/contracts/<?= $contractId ?>/editor#signing';
    } catch (error) {
        message.textContent = error.message || 'Signing failed';
    }
});
</script>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/app.php';
