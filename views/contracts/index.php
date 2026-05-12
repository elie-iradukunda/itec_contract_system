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
    '<a class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 rounded-xl text-white text-sm font-medium transition shadow-sm" href="' . BASE_URL . '/contracts/create">' . ui_icon('plus-lg') . ' New Contract</a>',
];
$pageScripts = [BASE_URL . '/public/assets/js/contracts-dashboard.js']; // preserves original JS

ob_start();
?>

<script>
    window.contractDashboardConfig = {
        baseUrl: '<?= BASE_URL ?>',
        apiUrl: '<?= BASE_URL ?>/api/contracts'
    };
</script>

<div x-data="contractsIndex()" class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 py-8 px-4">
    <div class="max-w-7xl mx-auto space-y-8">
        
        <!-- Metric Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex items-center justify-between">
                <div>
                    <span class="text-3xl font-bold text-slate-800" id="draftCount">0</span>
                    <p class="text-sm text-slate-500 mt-1">Draft</p>
                </div>
                <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center">
                    <i class="bi bi-file-earmark-text text-indigo-600 text-xl"></i>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex items-center justify-between">
                <div>
                    <span class="text-3xl font-bold text-slate-800" id="clientCount">0</span>
                    <p class="text-sm text-slate-500 mt-1">Awaiting client</p>
                </div>
                <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center">
                    <i class="bi bi-person-raised-hand text-amber-600 text-xl"></i>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex items-center justify-between">
                <div>
                    <span class="text-3xl font-bold text-slate-800" id="companyCount">0</span>
                    <p class="text-sm text-slate-500 mt-1">Company action</p>
                </div>
                <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center">
                    <i class="bi bi-building text-emerald-600 text-xl"></i>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex items-center justify-between">
                <div>
                    <span class="text-3xl font-bold text-slate-800" id="finalCount">0</span>
                    <p class="text-sm text-slate-500 mt-1">Fully signed</p>
                </div>
                <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center">
                    <i class="bi bi-check2-all text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Flow Board (Execution Map) -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex flex-wrap justify-between items-center">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">execution map</p>
                    <h2 class="text-xl font-bold text-slate-800">Draft to final distribution</h2>
                </div>
                <span class="text-xs bg-indigo-50 text-indigo-600 px-3 py-1 rounded-full">ready for live testing</span>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                    <!-- Phase 1 -->
                    <div class="rounded-xl border border-slate-200 p-4 hover:shadow-md transition">
                        <div class="flex items-center gap-3 mb-3">
                            <span class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-sm">1</span>
                            <strong class="text-slate-800">Draft & Review</strong>
                        </div>
                        <p class="text-xs text-slate-500 mb-3">Draft the body, capture versions, and clear internal review before signing starts.</p>
                        <div class="flex flex-wrap gap-2 text-xs text-slate-400">
                            <span class="px-2 py-1 bg-slate-100 rounded">Create</span>
                            <span class="px-2 py-1 bg-slate-100 rounded">Save version</span>
                            <span class="px-2 py-1 bg-slate-100 rounded">Submit</span>
                        </div>
                    </div>
                    <!-- Phase 2 -->
                    <div class="rounded-xl border border-slate-200 p-4 hover:shadow-md transition">
                        <div class="flex items-center gap-3 mb-3">
                            <span class="w-8 h-8 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-sm">2</span>
                            <strong class="text-slate-800">Client Signs</strong>
                        </div>
                        <p class="text-xs text-slate-500 mb-3">Send a locked document to the client for digital signing or hard-copy return.</p>
                        <div class="flex flex-wrap gap-2 text-xs text-slate-400">
                            <span class="px-2 py-1 bg-slate-100 rounded">Portal sign</span>
                            <span class="px-2 py-1 bg-slate-100 rounded">Print PDF</span>
                            <span class="px-2 py-1 bg-slate-100 rounded">Upload scan</span>
                        </div>
                    </div>
                    <!-- Phase 3 -->
                    <div class="rounded-xl border border-slate-200 p-4 hover:shadow-md transition">
                        <div class="flex items-center gap-3 mb-3">
                            <span class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-sm">3</span>
                            <strong class="text-slate-800">Company + Seal</strong>
                        </div>
                        <p class="text-xs text-slate-500 mb-3">Complete company execution with signature, seal, approval stamp, and snapshot.</p>
                        <div class="flex flex-wrap gap-2 text-xs text-slate-400">
                            <span class="px-2 py-1 bg-slate-100 rounded">Company sign</span>
                            <span class="px-2 py-1 bg-slate-100 rounded">Seal</span>
                            <span class="px-2 py-1 bg-slate-100 rounded">Snapshot</span>
                        </div>
                    </div>
                    <!-- Phase 4 -->
                    <div class="rounded-xl border border-slate-200 p-4 hover:shadow-md transition">
                        <div class="flex items-center gap-3 mb-3">
                            <span class="w-8 h-8 rounded-full bg-purple-100 text-purple-700 flex items-center justify-center font-bold text-sm">4</span>
                            <strong class="text-slate-800">Final Distribution</strong>
                        </div>
                        <p class="text-xs text-slate-500 mb-3">Share the completed contract with a secure link and permanent audit trail.</p>
                        <div class="flex flex-wrap gap-2 text-xs text-slate-400">
                            <span class="px-2 py-1 bg-slate-100 rounded">Final PDF</span>
                            <span class="px-2 py-1 bg-slate-100 rounded">Token link</span>
                            <span class="px-2 py-1 bg-slate-100 rounded">Email</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contracts Table + Detail Panel -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Contracts Table Panel -->
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-5 border-b border-slate-100 bg-slate-50/50">
                    <div class="flex flex-col sm:flex-row gap-3 justify-between">
                        <label class="relative flex-1">
                            <i class="bi bi-search absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400"></i>
                            <input type="search" id="contractSearch" placeholder="Search by client, title, owner" 
                                   class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </label>
                        <select id="statusFilter" class="px-4 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 bg-white">
                            <option value="">All statuses</option>
                            <option value="DRAFT">Draft</option>
                            <option value="AWAITING_CLIENT">Awaiting client</option>
                            <option value="CLIENT_SIGNED">Client signed</option>
                            <option value="AWAITING_COMPANY">Company action</option>
                            <option value="FULLY_SIGNED">Fully signed</option>
                        </select>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Contract</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Client</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Owner</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Updated</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="contractsTableBody" class="divide-y divide-slate-100 bg-white"></tbody>
                    </table>
                </div>
                <div id="emptyContracts" class="hidden text-center py-12">
                    <i class="bi bi-inbox text-4xl text-slate-300 mb-3 block"></i>
                    <strong class="text-slate-600">No contracts found</strong>
                    <p class="text-sm text-slate-400 mt-1">Create a draft or adjust the filters.</p>
                </div>
            </div>

            <!-- Detail Panel -->
            <aside class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden sticky top-6 h-fit">
                <div class="p-5 border-b border-slate-100 bg-slate-50/50">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">selected contract</p>
                    <h2 id="detailTitle" class="text-xl font-bold text-slate-800 mt-1">Select a contract</h2>
                </div>
                <div class="p-5 space-y-5">
                    <dl class="space-y-3">
                        <div class="flex justify-between items-center">
                            <dt class="text-sm font-medium text-slate-500">Client</dt>
                            <dd id="detailClient" class="text-sm text-slate-800">-</dd>
                        </div>
                        <div class="flex justify-between items-center">
                            <dt class="text-sm font-medium text-slate-500">Document type</dt>
                            <dd id="detailType" class="text-sm text-slate-800">-</dd>
                        </div>
                        <div class="flex justify-between items-center">
                            <dt class="text-sm font-medium text-slate-500">Document</dt>
                            <dd id="detailPath" class="text-sm text-slate-800">-</dd>
                        </div>
                        <div class="flex justify-between items-center">
                            <dt class="text-sm font-medium text-slate-500">Next action</dt>
                            <dd id="detailAction" class="text-sm text-slate-800">-</dd>
                        </div>
                    </dl>

                    <div class="bg-indigo-50 rounded-xl p-4">
                        <strong id="detailPhaseTitle" class="text-sm font-semibold text-indigo-800">Lifecycle phase</strong>
                        <p id="detailPhaseCopy" class="text-xs text-indigo-600 mt-1">Select a contract to see the next step.</p>
                    </div>

                    <!-- Timeline steps -->
                    <div class="flex justify-between items-center">
                        <span class="timeline-step text-xs px-2 py-1 rounded-full bg-slate-100 text-slate-600" data-state="DRAFT">Draft</span>
                        <span class="timeline-step text-xs px-2 py-1 rounded-full bg-slate-100 text-slate-600" data-state="AWAITING_CLIENT">Client</span>
                        <span class="timeline-step text-xs px-2 py-1 rounded-full bg-slate-100 text-slate-600" data-state="AWAITING_COMPANY">Company</span>
                        <span class="timeline-step text-xs px-2 py-1 rounded-full bg-slate-100 text-slate-600" data-state="FULLY_SIGNED">Final</span>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <a id="detailViewLink" class="flex-1 text-center px-4 py-2 border border-slate-200 rounded-xl text-sm text-slate-700 hover:bg-slate-50 transition">View</a>
                        <a id="detailAuditLink" class="flex-1 text-center px-4 py-2 border border-slate-200 rounded-xl text-sm text-slate-700 hover:bg-slate-50 transition">Audit</a>
                        <a id="detailEditorLink" class="flex-1 text-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 rounded-xl text-sm text-white font-medium transition">Open editor</a>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>

<!-- Ensure the original contracts-dashboard.js still works (it will attach to the same DOM elements) -->
<script>
    // Small Alpine wrapper for future enhancements (optional), but we keep existing JS
    function contractsIndex() {
        return {
            // Placeholder for any Alpine reactive data (if needed later)
        }
    }
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/app.php';
?>