<?php
require_once dirname(__DIR__) . '/components/ui.php';

$contract = $contract ?? [];
$contractId = (int) ($contract['id'] ?? 1);
$state = strtoupper($contract['signing_state'] ?? 'DRAFT');
$stateOrder = ['DRAFT', 'AWAITING_CLIENT', 'CLIENT_SIGNED', 'AWAITING_COMPANY', 'FULLY_SIGNED'];
$currentIndex = array_search($state, $stateOrder, true);
$currentIndex = $currentIndex === false ? 0 : $currentIndex;

$title = 'Contract Details';
$activeNav = 'contracts';
$headerMeta = 'contract workspace';
$pageTitle = 'Contract Details';
$pageHeading = $contract['title'] ?? 'Contract Details';
$pageEyebrow = 'contract workspace';
$pageLead = 'A guided test workspace for checking drafting, client signing, company execution, and final distribution.';
$pageActions = [
    '<a class="inline-flex items-center gap-2 px-4 py-2 border border-slate-300 rounded-xl text-slate-700 hover:bg-slate-50 transition text-sm font-medium" href="' . BASE_URL . '/contracts">← Back to Contracts</a>',
    '<a class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 rounded-xl text-white text-sm font-medium transition shadow-sm" href="' . BASE_URL . '/contracts/' . $contractId . '/editor">Open Workspace</a>',
];

$phaseMap = [
    'DRAFT' => [
        'label' => 'Draft & Internal Review',
        'summary' => 'Prepare the contract, save versions, and review tracked changes before sending it to the client.',
        'actor' => 'Originator and reviewer',
        'body' => 'Editable',
        'primary' => 'Open Draft Workspace',
        'href' => BASE_URL . '/contracts/' . $contractId . '/editor',
        'checklist' => [
            'Edit the contract body and save the draft.',
            'Confirm a new version is recorded after saving.',
            'Open review and accept or reject a tracked change.',
            'Submit the contract for client signing.',
        ],
    ],
    'AWAITING_CLIENT' => [
        'label' => 'Client Signing',
        'summary' => 'The document is frozen while the client chooses digital signing or hard-copy return.',
        'actor' => 'Client',
        'body' => 'Read only',
        'primary' => 'Open Client Signing',
        'href' => BASE_URL . '/contracts/sign/' . $contractId,
        'checklist' => [
            'Confirm the client cannot edit the document body.',
            'Test the digital signature option.',
            'Test the hard-copy upload option if needed.',
            'Confirm the contract advances after client signing.',
        ],
    ],
    'CLIENT_SIGNED' => [
        'label' => 'Client Signed',
        'summary' => 'Client execution is recorded and the workflow is ready to move to company signing.',
        'actor' => 'System handoff',
        'body' => 'Read only',
        'primary' => 'Check Execution Status',
        'href' => BASE_URL . '/contracts/execution-status/' . $contractId,
        'checklist' => [
            'Confirm the client signature appears in the signing history.',
            'Confirm the company handoff is visible.',
            'Refresh the contract and check the next state.',
            'Open the audit trail and verify the client event.',
        ],
    ],
    'AWAITING_COMPANY' => [
        'label' => 'Company Signature & Seal',
        'summary' => 'The company representative signs the frozen contract and applies the company seal.',
        'actor' => 'Company representative',
        'body' => 'Read only',
        'primary' => 'Open Company Signing',
        'href' => BASE_URL . '/contracts/sign-company/' . $contractId,
        'checklist' => [
            'Confirm the document body remains locked.',
            'Add the company signature.',
            'Confirm the seal or approval stamp is applied.',
            'Confirm the contract advances to fully signed.',
        ],
    ],
    'FULLY_SIGNED' => [
        'label' => 'Finalization & Distribution',
        'summary' => 'The signed contract is final. Test final PDF access, secure link viewing, and distribution records.',
        'actor' => 'No edits allowed',
        'body' => 'Locked',
        'primary' => 'Open Final Contract',
        'href' => BASE_URL . '/contracts/view/' . $contractId,
        'checklist' => [
            'Confirm no editing controls are available.',
            'Open or download the final PDF.',
            'Confirm distribution records are listed.',
            'Open the audit trail and verify the full lifecycle.',
        ],
    ],
];

$phase = $phaseMap[$state] ?? $phaseMap['DRAFT'];
$clientName = $contract['client_name'] ?? $contract['company_name'] ?? 'Client pending';
$clientEmail = $contract['client_email'] ?? $contract['email'] ?? 'Not provided';
$createdAt = !empty($contract['created_at']) ? date('M j, Y', strtotime($contract['created_at'])) : 'Not recorded';

// Helper function to get status badge color based on state
function getStatusBadgeClass($state) {
    return match ($state) {
        'DRAFT' => 'bg-indigo-100 text-indigo-700 border-indigo-200',
        'AWAITING_CLIENT' => 'bg-amber-100 text-amber-700 border-amber-200',
        'CLIENT_SIGNED' => 'bg-blue-100 text-blue-700 border-blue-200',
        'AWAITING_COMPANY' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
        'FULLY_SIGNED' => 'bg-purple-100 text-purple-700 border-purple-200',
        default => 'bg-slate-100 text-slate-700 border-slate-200',
    };
}

ob_start();
?>

<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 py-8 px-4">
    <div class="max-w-7xl mx-auto space-y-8">
        
        <!-- Status Banner -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium border <?= getStatusBadgeClass($state) ?>">
                    <?= ui_e(ui_status_label($state)) ?>
                </span>
                <div>
                    <strong class="text-slate-800"><?= ui_e($phase['label']) ?></strong>
                    <p class="text-sm text-slate-500 mt-0.5"><?= ui_e($phase['summary']) ?></p>
                </div>
            </div>
        </div>

        <!-- Client Info Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex items-center gap-3">
                <i class="bi bi-person-badge text-indigo-500 text-2xl"></i>
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Client</p>
                    <p class="text-slate-800 font-medium"><?= ui_e($clientName) ?></p>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex items-center gap-3">
                <i class="bi bi-envelope text-indigo-500 text-2xl"></i>
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Contact</p>
                    <p class="text-slate-800 font-medium"><?= ui_e($clientEmail) ?></p>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex items-center gap-3">
                <i class="bi bi-calendar-check text-indigo-500 text-2xl"></i>
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Created</p>
                    <p class="text-slate-800 font-medium"><?= ui_e($createdAt) ?></p>
                </div>
            </div>
        </div>

        <!-- Lifecycle Progress Board -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex flex-wrap justify-between items-center">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">lifecycle progress</p>
                    <h2 class="text-xl font-bold text-slate-800">Contract phase journey</h2>
                </div>
                <a href="<?= ui_e($phase['href']) ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 rounded-xl text-white text-sm font-medium transition shadow-sm">
                    <?= ui_e($phase['primary']) ?>
                </a>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <?php foreach ($stateOrder as $index => $phaseState): ?>
                        <?php
                            $isComplete = $index < $currentIndex;
                            $isActive = $index === $currentIndex;
                            $phaseLabel = $phaseMap[$phaseState]['label'];
                            $phaseSummary = $phaseMap[$phaseState]['summary'];
                            $actor = $phaseMap[$phaseState]['actor'];
                            $bodyState = $phaseMap[$phaseState]['body'];
                        ?>
                        <div class="relative rounded-xl border transition-all <?= $isActive ? 'border-indigo-300 bg-indigo-50/30 shadow-md' : ($isComplete ? 'border-emerald-200 bg-emerald-50/20' : 'border-slate-200 bg-white hover:shadow-sm') ?>">
                            <div class="p-4">
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold <?= $isActive ? 'bg-indigo-600 text-white' : ($isComplete ? 'bg-emerald-500 text-white' : 'bg-slate-200 text-slate-500') ?>">
                                        <?= $index + 1 ?>
                                    </span>
                                    <strong class="text-sm text-slate-800"><?= ui_e($phaseLabel) ?></strong>
                                </div>
                                <p class="text-xs text-slate-500 mb-3"><?= ui_e($phaseSummary) ?></p>
                                <div class="flex justify-between items-center text-xs text-slate-400 border-t border-slate-100 pt-2 mt-2">
                                    <span><?= ui_e($actor) ?></span>
                                    <span class="px-2 py-0.5 rounded-full bg-slate-100"><?= ui_e($bodyState) ?></span>
                                </div>
                            </div>
                            <?php if ($isActive): ?>
                                <div class="absolute -top-1 -right-1 w-3 h-3 bg-indigo-500 rounded-full animate-pulse"></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Functional Test & Next Action Split -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left: Checklist -->
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">functional test</p>
                    <h2 class="text-xl font-bold text-slate-800">What to test now</h2>
                </div>
                <div class="p-6 space-y-3">
                    <?php foreach ($phase['checklist'] as $item): ?>
                        <div class="flex items-start gap-3 p-3 rounded-xl bg-slate-50/50 hover:bg-slate-100 transition">
                            <i class="bi bi-check2-circle text-emerald-500 text-lg mt-0.5"></i>
                            <p class="text-sm text-slate-700"><?= ui_e($item) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Right: Next Action Card -->
            <aside class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden sticky top-6 h-fit">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">next action</p>
                    <h2 class="text-xl font-bold text-slate-800"><?= ui_e($phase['label']) ?></h2>
                </div>
                <div class="p-6 space-y-5">
                    <p class="text-sm text-slate-600"><?= ui_e($phase['summary']) ?></p>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between items-center py-2 border-b border-slate-100">
                            <span class="text-sm font-medium text-slate-500">Current actor</span>
                            <span class="text-sm text-slate-800"><?= ui_e($phase['actor']) ?></span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-slate-100">
                            <span class="text-sm font-medium text-slate-500">Document body</span>
                            <span class="text-sm text-slate-800"><?= ui_e($phase['body']) ?></span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-slate-100">
                            <span class="text-sm font-medium text-slate-500">Contract ID</span>
                            <span class="text-sm font-mono text-slate-800">#<?= $contractId ?></span>
                        </div>
                    </div>

                    <div class="flex gap-3 pt-4">
                        <a href="<?= ui_e($phase['href']) ?>" class="flex-1 text-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 rounded-xl text-white text-sm font-medium transition shadow-sm">
                            <?= ui_e($phase['primary']) ?>
                        </a>
                        <a href="<?= BASE_URL ?>/contracts/<?= $contractId ?>/audit" class="px-4 py-2.5 border border-slate-300 rounded-xl text-slate-700 hover:bg-slate-50 transition text-sm font-medium">
                            Open Audit
                        </a>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/app.php';
?>