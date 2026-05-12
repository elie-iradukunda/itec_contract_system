<?php
$basePath = '/itec_contract_system';
$assetVersion = time();
$pageTitle = 'Contract Portal';
$pageHeading = 'Contract Portal';
$pageEyebrow = 'finance execution workspace';
$pageLead = 'Manage drafting, tracked changes, client signing choice, body lock, company execution, and final distribution from one portal.';
$activeNav = 'home';
$headerMeta = 'contract operations';
$pageActions = [
    '<a class="inline-flex items-center gap-2 px-4 py-2 border border-slate-300 rounded-xl text-slate-700 hover:bg-slate-50 transition text-sm font-medium" href="' . $basePath . '/contracts">View Contracts</a>',
    '<a class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 rounded-xl text-white text-sm font-medium transition shadow-sm" href="' . $basePath . '/contracts/create">New Contract</a>'
];

ob_start();
?>

<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 py-8 px-4">
    <div class="max-w-7xl mx-auto space-y-8">
        
        <!-- Metric Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex items-center justify-between">
                <div>
                    <span class="text-3xl font-bold text-slate-800">12</span>
                    <p class="text-sm text-slate-500 mt-1">Draft contracts</p>
                </div>
                <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center">
                    <i class="bi bi-file-earmark-text text-indigo-600 text-xl"></i>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex items-center justify-between">
                <div>
                    <span class="text-3xl font-bold text-slate-800">5</span>
                    <p class="text-sm text-slate-500 mt-1">Awaiting client</p>
                </div>
                <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center">
                    <i class="bi bi-person-raised-hand text-amber-600 text-xl"></i>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex items-center justify-between">
                <div>
                    <span class="text-3xl font-bold text-slate-800">3</span>
                    <p class="text-sm text-slate-500 mt-1">Company action</p>
                </div>
                <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center">
                    <i class="bi bi-building text-emerald-600 text-xl"></i>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex items-center justify-between">
                <div>
                    <span class="text-3xl font-bold text-slate-800">18</span>
                    <p class="text-sm text-slate-500 mt-1">Fully signed</p>
                </div>
                <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center">
                    <i class="bi bi-check2-all text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Dashboard Grid: Execution Queue + Workflow -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Execution Queue Table -->
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex flex-wrap justify-between items-center">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Execution queue</p>
                        <h2 class="text-xl font-bold text-slate-800">Contracts needing attention</h2>
                    </div>
                    <a href="<?= $basePath ?>/contracts" class="text-indigo-600 hover:text-indigo-700 text-sm font-medium">Open list →</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Contract</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Client</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Owner</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Next action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4 text-sm font-medium text-slate-800">Service Agreement</td>
                                <td class="px-6 py-4 text-sm text-slate-600">Rwanda Tech Group</td>
                                <td class="px-6 py-4"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">Draft</span></td>
                                <td class="px-6 py-4 text-sm text-slate-600">Elie</td>
                                <td class="px-6 py-4"><a href="<?= $basePath ?>/contracts/create" class="text-indigo-600 hover:text-indigo-800 text-sm">Start draft</a></td>
                            </tr>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4 text-sm font-medium text-slate-800">Financing Contract #8</td>
                                <td class="px-6 py-4 text-sm text-slate-600">Umucyo Stores</td>
                                <td class="px-6 py-4"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Awaiting Client</span></td>
                                <td class="px-6 py-4 text-sm text-slate-600">Finance</td>
                                <td class="px-6 py-4 text-sm text-slate-500">Client signature</td>
                            </tr>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4 text-sm font-medium text-slate-800">Lease Addendum #14</td>
                                <td class="px-6 py-4 text-sm text-slate-600">Kivu Logistics</td>
                                <td class="px-6 py-4"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">Company Action</span></td>
                                <td class="px-6 py-4 text-sm text-slate-600">Legal</td>
                                <td class="px-6 py-4 text-sm text-slate-500">Seal and sign</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Workflow Sidebar -->
            <aside class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden sticky top-6 h-fit">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Workflow</p>
                    <h2 class="text-xl font-bold text-slate-800">Execution path</h2>
                </div>
                <div class="p-6">
                    <ol class="space-y-4">
                        <li class="flex gap-3">
                            <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold">1</span>
                            <div><strong class="text-slate-800 block">Draft</strong><span class="text-xs text-slate-500">Internal review and tracked changes</span></div>
                        </li>
                        <li class="flex gap-3">
                            <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold">2</span>
                            <div><strong class="text-slate-800 block">Client choice</strong><span class="text-xs text-slate-500">Digital sign or hard copy upload</span></div>
                        </li>
                        <li class="flex gap-3">
                            <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold">3</span>
                            <div><strong class="text-slate-800 block">Body lock</strong><span class="text-xs text-slate-500">Document freezes after client signature</span></div>
                        </li>
                        <li class="flex gap-3">
                            <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold">4</span>
                            <div><strong class="text-slate-800 block">Company execution</strong><span class="text-xs text-slate-500">Authorized signature and certified seal</span></div>
                        </li>
                        <li class="flex gap-3">
                            <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold">5</span>
                            <div><strong class="text-slate-800 block">Distribution</strong><span class="text-xs text-slate-500">Final PDF and secure portal link</span></div>
                        </li>
                    </ol>
                </div>
            </aside>
        </div>

        <!-- Recent Activity Section -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex flex-wrap justify-between items-center">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Chain of custody</p>
                    <h2 class="text-xl font-bold text-slate-800">Recent activity</h2>
                </div>
                <a href="<?= $basePath ?>/contracts" class="text-indigo-600 hover:text-indigo-700 text-sm font-medium">Open contracts →</a>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div class="flex items-start gap-3 p-4 rounded-xl bg-slate-50 hover:bg-slate-100 transition">
                        <i class="bi bi-file-earmark-check text-indigo-500 text-xl"></i>
                        <div>
                            <strong class="text-slate-800 block text-sm">Version saved</strong>
                            <span class="text-xs text-slate-500">Contract #1 saved as a new document version.</span>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-4 rounded-xl bg-slate-50 hover:bg-slate-100 transition">
                        <i class="bi bi-pencil-square text-indigo-500 text-xl"></i>
                        <div>
                            <strong class="text-slate-800 block text-sm">Tracked changes ready</strong>
                            <span class="text-xs text-slate-500">Reviewer panel prepared for accept and reject actions.</span>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-4 rounded-xl bg-slate-50 hover:bg-slate-100 transition">
                        <i class="bi bi-share text-indigo-500 text-xl"></i>
                        <div>
                            <strong class="text-slate-800 block text-sm">Distribution prepared</strong>
                            <span class="text-xs text-slate-500">Final PDF and secure token link are ready for completed contracts.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php
$pageContent = ob_get_clean();
require __DIR__ . '/layouts/app.php';
?>