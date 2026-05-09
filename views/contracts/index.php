<?php
$basePath = '/itec_contract_system';
$assetVersion = time();
$pageTitle = 'Contracts';
$pageHeading = 'Contracts';
$pageEyebrow = 'contract lifecycle';
$pageLead = 'Browse, create, filter, and inspect contracts while keeping execution status, ownership, and next actions visible.';
$activeNav = 'contracts';
$headerMeta = 'contract workspace';
$pageActions = [
    '<button id="newContractButton" class="button" type="button">New Contract</button>'
];
$pageScripts = [
    $basePath . '/public/assets/js/contract-ui-common.js?v=' . $assetVersion,
    $basePath . '/public/assets/js/contract-demo-store.js?v=' . $assetVersion,
    $basePath . '/public/assets/js/contracts-page.js?v=' . $assetVersion,
    $basePath . '/public/assets/js/portal.js?v=' . $assetVersion
];

ob_start();
?>
<section class="metric-grid" aria-label="Contract status summary">
    <article class="metric">
        <span class="metric-code draft">DR</span>
        <div><strong id="draftCount">0</strong><small>Draft</small></div>
    </article>
    <article class="metric">
        <span class="metric-code client">CL</span>
        <div><strong id="clientCount">0</strong><small>Awaiting client</small></div>
    </article>
    <article class="metric">
        <span class="metric-code company">CO</span>
        <div><strong id="companyCount">0</strong><small>Company action</small></div>
    </article>
    <article class="metric">
        <span class="metric-code final">FN</span>
        <div><strong id="finalCount">0</strong><small>Fully signed</small></div>
    </article>
</section>

<section class="notice-banner success">
    <strong>Frontend demo flow is active</strong>
    <span>Create a contract here, save the required fields, generate a client portal link, and test the client read and sign journey without touching the backend.</span>
</section>

<section class="workspace-grid">
    <div class="surface contracts-surface">
        <div class="list-toolbar">
            <label class="search-box" for="contractSearch">
                <span>Search</span>
                <input id="contractSearch" type="search" placeholder="Client, title, owner">
            </label>
            <label class="filter-box" for="statusFilter">
                <span>Status</span>
                <select id="statusFilter">
                    <option value="all">All statuses</option>
                    <option value="DRAFT">Draft</option>
                    <option value="AWAITING_CLIENT">Awaiting client</option>
                    <option value="CLIENT_SIGNED">Client signed</option>
                    <option value="AWAITING_COMPANY">Company action</option>
                    <option value="FULLY_SIGNED">Fully signed</option>
                </select>
            </label>
        </div>

        <div class="responsive-table">
            <table>
                <thead>
                    <tr>
                        <th>Contract</th>
                        <th>Client</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Owner</th>
                        <th>Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="contractsTableBody"></tbody>
            </table>
        </div>

        <div id="emptyContracts" class="empty-state hidden">
            <strong>No contracts found</strong>
            <span>Adjust the filters or create a new contract.</span>
        </div>
    </div>

    <aside class="surface detail-panel">
        <div class="section-head compact">
            <div>
                <p>Selected contract</p>
                <h2 id="detailTitle">Choose a contract</h2>
            </div>
        </div>
        <dl class="detail-list">
            <div><dt>Client</dt><dd id="detailClient">-</dd></div>
            <div><dt>Email</dt><dd id="detailEmail">-</dd></div>
            <div><dt>Document type</dt><dd id="detailType">-</dd></div>
            <div><dt>Signing path</dt><dd id="detailPath">-</dd></div>
            <div><dt>Next action</dt><dd id="detailAction">-</dd></div>
        </dl>
        <div class="surface-pad share-panel">
            <strong>Client portal link</strong>
            <a id="detailPortalLink" class="inline-link" href="<?= $basePath ?>/views/clients/portal.php" target="_blank" rel="noopener">Portal link will appear here</a>
            <small id="detailShareText" class="muted-copy">Send the client link after the record is ready.</small>
        </div>
        <div class="timeline">
            <span class="timeline-step active">Draft</span>
            <span class="timeline-step">Client</span>
            <span class="timeline-step">Lock</span>
            <span class="timeline-step">Company</span>
            <span class="timeline-step">Final</span>
        </div>
        <div class="detail-actions">
            <a id="detailEditorLink" class="button" href="<?= $basePath ?>/contracts/1/edit">Open Editor</a>
            <button id="sendToClientButton" class="button success" type="button">Send to Client</button>
            <button id="copyPortalLinkButton" class="button ghost" type="button">Copy Link</button>
            <a id="previewClientPortalLink" class="button ghost" href="<?= $basePath ?>/views/clients/portal.php" target="_blank" rel="noopener">Open Client Portal</a>
            <a id="detailAuditLink" class="button ghost" href="<?= $basePath ?>/views/contracts/audit-trail.php" target="_blank" rel="noopener">Audit Trail</a>
        </div>
        <div class="surface-pad">
            <a id="emailClientLink" class="row-action" href="mailto:">Open Email Draft</a>
            <span id="detailActionMessage" class="muted-copy"></span>
        </div>
    </aside>
</section>

<div id="contractModal" class="modal-backdrop hidden" role="dialog" aria-modal="true" aria-labelledby="contractModalTitle">
    <section class="modal contract-modal">
        <div class="modal-header">
            <div>
                <p>New contract</p>
                <h2 id="contractModalTitle">Create contract record</h2>
            </div>
            <button id="closeContractModal" class="icon-button" type="button" aria-label="Close">x</button>
        </div>

        <form id="contractForm" class="contract-form">
            <label>
                <span>Contract title</span>
                <input name="title" type="text" required placeholder="Service Agreement">
            </label>
            <label>
                <span>Client name</span>
                <input name="client" type="text" required placeholder="Client company">
            </label>
            <label>
                <span>Client email</span>
                <input name="clientEmail" type="email" required placeholder="client@example.com">
            </label>
            <label>
                <span>Document type</span>
                <select name="type">
                    <option>Service Agreement</option>
                    <option>Financing Contract</option>
                    <option>Lease Addendum</option>
                    <option>Legal Agreement</option>
                </select>
            </label>
            <label>
                <span>Signing path</span>
                <select name="path">
                    <option>Digital first</option>
                    <option>Hard copy first</option>
                    <option>Client decides</option>
                </select>
            </label>
            <label>
                <span>Owner</span>
                <input name="owner" type="text" value="Elie">
            </label>
            <label>
                <span>Status</span>
                <select name="status">
                    <option value="DRAFT">Draft</option>
                    <option value="AWAITING_CLIENT">Awaiting client</option>
                    <option value="AWAITING_COMPANY">Company action</option>
                </select>
            </label>
            <label class="field-span">
                <span>Contract body</span>
                <textarea name="body" required placeholder="Write the contract summary, pricing terms, obligations, and signature instructions for the client."></textarea>
            </label>
            <div class="form-actions">
                <button class="button ghost" type="button" id="cancelContractForm">Cancel</button>
                <button class="button" type="submit">Create Contract</button>
            </div>
        </form>
    </section>
</div>

<script>
    window.contractPortalConfig = { basePath: "<?= $basePath ?>" };
</script>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
