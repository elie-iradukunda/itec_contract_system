<?php
$basePath = '/itec_contract_system';
$assetVersion = time();
$pageTitle = 'Create Contract';
$pageHeading = 'Create Contract';
$pageEyebrow = 'contract intake';
$pageLead = 'Start a new service agreement, financing contract, or legal document with the right signing path and ownership from day one.';
$activeNav = 'contracts';
$headerMeta = 'contract workspace';
$pageActions = [
    '<a class="button ghost" href="' . $basePath . '/contracts">Back to Contracts</a>',
    '<a class="button" href="' . $basePath . '/contracts/1/edit">Open Editor</a>'
];

ob_start();
?>
<section class="content-split">
    <form class="surface form-surface form-grid" action="<?= $basePath ?>/contracts/store" method="post">
        <label>
            <span>Contract title</span>
            <input name="title" type="text" placeholder="Service Agreement" required>
        </label>
        <label>
            <span>Client</span>
            <input name="client_name" type="text" placeholder="Client company">
        </label>
        <label>
            <span>Document type</span>
            <select name="document_type">
                <option>Service Agreement</option>
                <option>Financing Contract</option>
                <option>Lease Addendum</option>
                <option>General Legal Contract</option>
            </select>
        </label>
        <label>
            <span>Signing route</span>
            <select name="signing_route">
                <option>Client decides</option>
                <option>Digital first</option>
                <option>Hard copy first</option>
            </select>
        </label>
        <label>
            <span>Internal owner</span>
            <input name="owner" type="text" value="Elie">
        </label>
        <label>
            <span>Target execution date</span>
            <input name="execution_date" type="date">
        </label>
        <label class="field-span">
            <span>Internal summary</span>
            <textarea name="summary" placeholder="Describe scope, pricing, obligations, and special clauses."></textarea>
        </label>
        <div class="field-span form-actions">
            <button class="button ghost" type="reset">Clear</button>
            <button class="button" type="submit">Create Record</button>
        </div>
    </form>

    <div class="page-stack">
        <div class="surface surface-pad">
            <h2>What happens next</h2>
            <ul class="check-list">
                <li>The document is drafted and reviewed internally.</li>
                <li>The client chooses digital signing or hard copy return.</li>
                <li>The body locks after client signing.</li>
                <li>The company adds an authorized signature and the certified seal.</li>
                <li>The final PDF is distributed with a secure read-only link.</li>
            </ul>
        </div>
        <div class="surface surface-pad">
            <h2>Recommended setup</h2>
            <ul class="bullet-list">
                <li>Keep titles specific so version history stays easy to scan.</li>
                <li>Choose the expected signing route early to reduce reviewer confusion.</li>
                <li>Assign a single owner before the drafting phase begins.</li>
            </ul>
        </div>
    </div>
</section>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
