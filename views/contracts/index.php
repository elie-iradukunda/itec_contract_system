<?php
require_once dirname(__DIR__) . '/components/ui.php';

$title = 'Contracts';
$activeNav = 'contracts';
$headerMeta = 'contract workspace';
$pageTitle = 'Contracts';
$pageHeading = 'Contracts';
$pageEyebrow = 'contract lifecycle';
$pageLead = 'Manage every contract from first draft through client signing, company execution, sealing, and final distribution.';
$pageActions = [
    '<a class="button" href="' . BASE_URL . '/contracts/create">' . ui_icon('plus-lg') . ' New Contract</a>',
];
$pageScripts = [BASE_URL . '/public/assets/js/contracts-dashboard.js'];

ob_start();
?>
<script>
    window.contractDashboardConfig = {
        baseUrl: '<?= BASE_URL ?>',
        apiUrl: '<?= BASE_URL ?>/api/contracts'
    };
</script>

<section class="metric-grid" aria-label="Contract status summary">
    <article class="metric"><span class="metric-code draft">DR</span><div><strong id="draftCount">0</strong><small>Draft</small></div></article>
    <article class="metric"><span class="metric-code client">CL</span><div><strong id="clientCount">0</strong><small>Awaiting client</small></div></article>
    <article class="metric"><span class="metric-code company">CO</span><div><strong id="companyCount">0</strong><small>Company action</small></div></article>
    <article class="metric"><span class="metric-code final">FN</span><div><strong id="finalCount">0</strong><small>Fully signed</small></div></article>
</section>

<section class="flow-board surface" aria-label="Contract system flow">
    <div class="section-head compact">
        <div><p>execution map</p><h2>Draft to final distribution</h2></div>
        <span class="flow-note">ready for live testing</span>
    </div>
    <div class="phase-grid">
        <article class="phase-card" data-phase-state="DRAFT">
            <span class="phase-index">1</span>
            <strong>Draft & Review</strong>
            <small>Draft the body, capture versions, and clear internal review before signing starts.</small>
            <div class="phase-actions">
                <span>Create</span><span>Save version</span><span>Submit</span>
            </div>
        </article>
        <article class="phase-card" data-phase-state="AWAITING_CLIENT">
            <span class="phase-index">2</span>
            <strong>Client Signs</strong>
            <small>Send a locked document to the client for digital signing or hard-copy return.</small>
            <div class="phase-actions">
                <span>Portal sign</span><span>Print PDF</span><span>Upload scan</span>
            </div>
        </article>
        <article class="phase-card" data-phase-state="AWAITING_COMPANY">
            <span class="phase-index">3</span>
            <strong>Company + Seal</strong>
            <small>Complete company execution with signature, seal, approval stamp, and snapshot.</small>
            <div class="phase-actions">
                <span>Company sign</span><span>Seal</span><span>Snapshot</span>
            </div>
        </article>
        <article class="phase-card" data-phase-state="FULLY_SIGNED">
            <span class="phase-index">4</span>
            <strong>Final Distribution</strong>
            <small>Share the completed contract with a secure link and permanent audit trail.</small>
            <div class="phase-actions">
                <span>Final PDF</span><span>Token link</span><span>Email</span>
            </div>
        </article>
    </div>
</section>

<section class="workspace-grid">
    <div class="surface">
        <div class="list-toolbar">
            <label class="search-box">
                <span>Search</span>
                <input id="contractSearch" type="search" placeholder="Client, title, owner">
            </label>
            <label class="filter-box">
                <span>Status</span>
                <select id="statusFilter">
                    <option value="">All statuses</option>
                    <option value="DRAFT">Draft</option>
                    <option value="AWAITING_CLIENT">Awaiting client</option>
                    <option value="CLIENT_SIGNED">Client signed</option>
                    <option value="AWAITING_COMPANY">Company action</option>
                    <option value="FULLY_SIGNED">Fully signed</option>
                </select>
            </label>
        </div>

        <div class="responsive-table">
            <table class="contracts-table">
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
            <span>Create a draft or adjust the filters.</span>
        </div>
    </div>

    <aside class="surface detail-panel">
        <div class="section-head compact">
            <div><p>selected contract</p><h2 id="detailTitle">Select a contract</h2></div>
        </div>
        <dl class="detail-list">
            <div><dt>Client</dt><dd id="detailClient">-</dd></div>
            <div><dt>Document type</dt><dd id="detailType">-</dd></div>
            <div><dt>Document</dt><dd id="detailPath">-</dd></div>
            <div><dt>Next action</dt><dd id="detailAction">-</dd></div>
        </dl>
        <div class="next-action-panel">
            <strong id="detailPhaseTitle">Lifecycle phase</strong>
            <span id="detailPhaseCopy">Select a contract to see the next step.</span>
        </div>
        <div class="timeline">
            <span class="timeline-step" data-state="DRAFT">Draft</span>
            <span class="timeline-step" data-state="AWAITING_CLIENT">Client</span>
            <span class="timeline-step" data-state="AWAITING_COMPANY">Company</span>
            <span class="timeline-step" data-state="FULLY_SIGNED">Final</span>
        </div>
        <div class="detail-actions">
            <a id="detailViewLink" class="row-action" href="#">View</a>
            <a id="detailAuditLink" class="row-action" href="#">Audit</a>
            <a id="detailEditorLink" class="row-action primary" href="#">Open editor</a>
        </div>
    </aside>
</section>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/app.php';
