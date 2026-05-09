<?php
$basePath = '/itec_contract_system';
$assetVersion = time();
$pageTitle = 'Client Details';
$pageHeading = 'Rwanda Tech Group';
$pageEyebrow = 'client profile';
$pageLead = 'Review active agreements, execution posture, and contact details for this client account.';
$activeNav = 'clients';
$headerMeta = 'client relationships';
$pageActions = [
    '<a class="button ghost" href="' . $basePath . '/views/clients/portal.php">Back to Portal</a>',
    '<a class="button" href="' . $basePath . '/views/clients/portal.php">Portal View</a>'
];

ob_start();
?>
<section class="content-split">
    <div class="page-stack">
        <div class="surface surface-pad">
            <h2>Client summary</h2>
            <ul class="data-list">
                <li>Primary contact: Finance Manager</li>
                <li>Email channel: contracts@rwandatech.example</li>
                <li>Preferred signing path: digital when possible</li>
            </ul>
        </div>
        <div class="surface">
            <div class="section-head compact">
                <div><p>Active work</p><h2>Contracts linked to client</h2></div>
            </div>
            <div class="responsive-table">
                <table>
                    <thead>
                        <tr><th>Contract</th><th>Status</th><th>Next action</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>Service Agreement #1</td><td>Draft</td><td>Internal review</td></tr>
                        <tr><td>Support Annex #4</td><td>Fully Signed</td><td>Delivered</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <aside class="surface surface-pad">
        <h2>Execution notes</h2>
        <p class="muted-copy">This client regularly completes digital signatures quickly, but still requests a visible hard copy fallback in the final workflow instructions.</p>
    </aside>
</section>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
