<?php
require_once dirname(__DIR__) . '/components/ui.php';

$contractId = (int) ($contract_id ?? 1);
$title = 'Audit Trail';
$activeNav = 'contracts';
$headerMeta = 'audit and verification';
$pageTitle = 'Audit Trail';
$pageHeading = 'Contract Audit Trail';
$pageEyebrow = 'chain of custody';
$pageLead = 'Track who changed, reviewed, signed, sealed, and distributed the contract.';
$pageActions = [
    '<a class="button ghost" href="' . BASE_URL . '/contracts/show/' . $contractId . '">Contract Details</a>',
    '<a class="button" href="' . BASE_URL . '/api/contracts/' . $contractId . '/audit/export">Download Audit CSV</a>',
];

ob_start();
?>
<section class="panel-grid">
    <article class="surface info-card"><strong>Contract</strong><span>#<?= $contractId ?></span></article>
    <article class="surface info-card"><strong>Latest event</strong><span id="auditLatest">Loading...</span></article>
    <article class="surface info-card"><strong>Total events</strong><span id="auditTotal">0</span></article>
</section>

<section class="surface">
    <div class="section-head compact"><div><p>event log</p><h2>Full activity timeline</h2></div></div>
    <ul id="auditList" class="audit-list">
        <li><strong>Loading</strong><span>Reading the contract activity trail.</span></li>
    </ul>
</section>

<script>
(async function () {
    const list = document.getElementById('auditList');
    const latest = document.getElementById('auditLatest');
    const total = document.getElementById('auditTotal');
    const escapeHtml = function (value) {
        return String(value).replace(/[&<>"']/g, function (char) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[char];
        });
    };

    try {
        const response = await fetch('<?= BASE_URL ?>/api/contracts/<?= $contractId ?>/audit', { headers: { Accept: 'application/json' } });
        const result = await response.json();
        const events = result.audit_trail || result.events || [];
        total.textContent = events.length;
        latest.textContent = events[events.length - 1]?.event_type || 'No events yet';
        list.innerHTML = events.length ? events.map(function (event) {
            const time = event.timestamp || event.created_at || '';
            const actor = event.signer_id || 'system';
            const hash = event.doc_hash ? ' Hash: ' + String(event.doc_hash).slice(0, 16) + '...' : '';
            return '<li><strong>' + escapeHtml(time) + '</strong><span>' + escapeHtml(event.event_type || 'event') + ' by ' + escapeHtml(actor) + '.' + escapeHtml(hash) + '</span></li>';
        }).join('') : '<li><strong>Empty</strong><span>No audit events have been written for this contract yet.</span></li>';
    } catch (error) {
        latest.textContent = 'Unavailable';
        list.innerHTML = '<li><strong>Error</strong><span>Audit trail could not be loaded.</span></li>';
    }
})();
</script>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/app.php';
