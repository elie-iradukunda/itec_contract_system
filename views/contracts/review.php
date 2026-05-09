<?php
$basePath = '/itec_contract_system';
$assetVersion = time();
$pageTitle = 'Review Contract';
$pageHeading = 'Review Workspace';
$pageEyebrow = 'legal and finance review';
$pageLead = 'Use this page to prepare tracked changes, confirm signing route readiness, and move the contract toward client execution.';
$activeNav = 'contracts';
$headerMeta = 'review workspace';
$pageActions = [
    '<a class="button ghost" href="' . $basePath . '/contracts/versions">Versions</a>',
    '<a class="button" href="' . $basePath . '/contracts/1/edit#changes">Open Change Panel</a>'
];

ob_start();
?>
<section class="panel-grid">
    <article class="surface info-card"><strong>Pending edits</strong><span>6 tracked changes awaiting reviewer action.</span></article>
    <article class="surface info-card"><strong>Signature path</strong><span>Client decides between digital and hard copy.</span></article>
    <article class="surface info-card"><strong>Review status</strong><span>Internal review still open.</span></article>
</section>

<section class="content-split">
    <div class="page-stack">
        <div class="surface">
            <div class="section-head compact">
                <div><p>Focus areas</p><h2>Reviewer checklist</h2></div>
            </div>
            <div class="surface-pad">
                <ul class="check-list">
                    <li>Confirm commercial clauses match the financing terms.</li>
                    <li>Make sure signature and seal blocks are positioned correctly.</li>
                    <li>Verify hard copy instructions are present for client use.</li>
                    <li>Approve body content before the contract leaves draft.</li>
                </ul>
            </div>
        </div>
        <div class="surface">
            <div class="section-head compact">
                <div><p>Drafting phase</p><h2>Tracked change snapshot</h2></div>
            </div>
            <ul class="audit-list">
                <li><strong>Billing clause</strong><span>Pricing paragraph updated to match current commercial offer.</span></li>
                <li><strong>Signature section</strong><span>Client and company signature areas separated for clear body lock behavior.</span></li>
                <li><strong>Distribution wording</strong><span>Final PDF and secure read-only delivery notes added.</span></li>
            </ul>
        </div>
    </div>

    <aside class="surface surface-pad">
        <h2>Release gate</h2>
        <ul class="bullet-list">
            <li>No unresolved drafting changes remain before submission.</li>
            <li>The client path is understandable without staff intervention.</li>
            <li>Company signatory and seal steps are ready for the locked document.</li>
        </ul>
    </aside>
</section>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
