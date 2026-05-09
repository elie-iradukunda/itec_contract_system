<?php
$title = 'Contracts Dashboard';
ob_start();
?>

<div x-data="contractsList()" x-init="loadContracts()">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Contracts</h2>
            <p class="text-muted mb-0">Manage and track all contract documents</p>
        </div>
        <a href="<?= BASE_URL ?>/contracts/create" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Create Contract
        </a>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Status Filter</label>
                    <select class="form-select" x-model="filters.status" @change="loadContracts()">
                        <option value="">All Status</option>
                        <option value="DRAFT">Draft</option>
                        <option value="AWAITING_CLIENT">Awaiting Client</option>
                        <option value="CLIENT_SIGNED">Client Signed</option>
                        <option value="AWAITING_COMPANY">Awaiting Company</option>
                        <option value="FULLY_SIGNED">Fully Signed</option>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" x-model="filters.search" @keyup.enter="loadContracts()" placeholder="Search by title or ID...">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-outline-secondary w-100" @click="resetFilters()">
                        <i class="bi bi-arrow-repeat"></i> Reset Filters
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Contracts Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60px">ID</th>
                        <th>Title / Description</th>
                        <th style="width: 140px">Status</th>
                        <th style="width: 120px">Created</th>
                        <th style="width: 120px">Updated</th>
                        <th style="width: 120px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Loading State -->
                    <template x-if="loading">
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status"></div>
                                <p class="mt-2 mb-0 text-muted">Loading contracts...</p>
                            </td>
                        </tr>
                    </template>
                    
                    <!-- Empty State -->
                    <template x-if="!loading && contracts.length === 0">
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="bi bi-folder2-open fs-1 text-muted"></i>
                                <p class="mt-2 mb-0 text-muted">No contracts found</p>
                                <a href="<?= BASE_URL ?>/contracts/create" class="btn btn-sm btn-primary mt-3">
                                    <i class="bi bi-plus-lg"></i> Create your first contract
                                </a>
                            </td>
                        </tr>
                    </template>
                    
                    <!-- Contracts List -->
                    <template x-for="contract in contracts" :key="contract.id">
                        <tr>
                            <td class="font-monospace small" x-text="contract.id"></td>
                            <td>
                                <strong x-text="contract.title"></strong>
                                <div class="small text-muted" x-text="contract.description || 'No description'"></div>
                            </td>
                            <td>
                                <span x-show="contract.signing_state === 'DRAFT'" class="badge bg-secondary px-2 py-1">
                                    <i class="bi bi-pencil-square me-1"></i> Draft
                                </span>
                                <span x-show="contract.signing_state === 'AWAITING_CLIENT'" class="badge bg-info text-dark px-2 py-1">
                                    <i class="bi bi-envelope me-1"></i> Awaiting Client
                                </span>
                                <span x-show="contract.signing_state === 'CLIENT_SIGNED'" class="badge bg-warning text-dark px-2 py-1">
                                    <i class="bi bi-check2-circle me-1"></i> Client Signed
                                </span>
                                <span x-show="contract.signing_state === 'AWAITING_COMPANY'" class="badge bg-primary px-2 py-1">
                                    <i class="bi bi-building me-1"></i> Awaiting Company
                                </span>
                                <span x-show="contract.signing_state === 'FULLY_SIGNED'" class="badge bg-success px-2 py-1">
                                    <i class="bi bi-check2-all me-1"></i> Fully Signed
                                </span>
                            </td>
                            <td class="small text-muted" x-text="formatDate(contract.created_at)"></td>
                            <td class="small text-muted" x-text="formatDate(contract.updated_at)"></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a :href="BASE_URL + '/contracts/' + contract.id + '/edit'" 
                                       x-show="contract.signing_state === 'DRAFT'"
                                       class="btn btn-outline-primary"
                                       title="Edit Contract">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a :href="BASE_URL + '/contracts/' + contract.id + '/sign'" 
                                       x-show="contract.signing_state === 'AWAITING_CLIENT'"
                                       class="btn btn-outline-info"
                                       title="Sign Contract">
                                        <i class="bi bi-pen"></i>
                                    </a>
                                    <a :href="BASE_URL + '/contracts/' + contract.id + '/sign-company'" 
                                       x-show="contract.signing_state === 'AWAITING_COMPANY'"
                                       class="btn btn-outline-primary"
                                       title="Company Sign">
                                        <i class="bi bi-building-check"></i>
                                    </a>
                                    <a :href="BASE_URL + '/contracts/' + contract.id" 
                                       class="btn btn-outline-secondary"
                                       title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a :href="BASE_URL + '/contracts/' + contract.id + '/audit'" 
                                       class="btn btn-outline-secondary"
                                       title="View Audit Trail">
                                        <i class="bi bi-list-check"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Results Count -->
    <div class="mt-3 text-muted small" x-show="!loading && contracts.length > 0">
        Showing <span x-text="contracts.length"></span> contract(s)
    </div>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';

function contractsList() {
    return {
        contracts: [],
        loading: false,
        filters: {
            status: '',
            search: ''
        },
        BASE_URL: BASE_URL,
        
        async loadContracts() {
            this.loading = true;
            try {
                // Build query string
                const params = new URLSearchParams();
                if (this.filters.status) params.append('status', this.filters.status);
                if (this.filters.search) params.append('search', this.filters.search);
                
                let url = BASE_URL + '/api/contracts';
                if (params.toString()) url += '?' + params.toString();
                
                const response = await fetch(url);
                const data = await response.json();
                
                if (data.success) {
                    this.contracts = data.contracts || [];
                } else {
                    this.contracts = [];
                }
            } catch (error) {
                console.error('Error loading contracts:', error);
                // Demo data for UI testing when API isn't ready
                this.contracts = [
                    { id: 1, title: 'Service Agreement - ABC Corp', description: 'Annual IT support services', signing_state: 'DRAFT', created_at: '2026-05-01 10:00:00', updated_at: '2026-05-07 14:30:00' },
                    { id: 2, title: 'Financing Contract - XYZ Ltd', description: 'Equipment lease agreement', signing_state: 'AWAITING_CLIENT', created_at: '2026-05-05 09:15:00', updated_at: '2026-05-06 11:20:00' },
                    { id: 3, title: 'NDA - Strategic Partner', description: 'Confidentiality agreement', signing_state: 'CLIENT_SIGNED', created_at: '2026-04-28 13:00:00', updated_at: '2026-05-02 16:45:00' },
                    { id: 4, title: 'Partnership Agreement', description: 'Joint venture terms', signing_state: 'AWAITING_COMPANY', created_at: '2026-05-03 10:30:00', updated_at: '2026-05-08 09:00:00' },
                    { id: 5, title: 'Master Services Agreement', description: 'Ongoing services', signing_state: 'FULLY_SIGNED', created_at: '2026-04-15 14:00:00', updated_at: '2026-04-20 11:30:00' }
                ];
            } finally {
                this.loading = false;
            }
        },
        
        resetFilters() {
            this.filters.status = '';
            this.filters.search = '';
            this.loadContracts();
        },
        
        formatDate(dateString) {
            if (!dateString) return 'N/A';
            try {
                let date = new Date(dateString);
                return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            } catch {
                return dateString;
            }
        },
        
        getStatusBadge(status) {
            const badges = {
                'DRAFT': 'bg-secondary',
                'AWAITING_CLIENT': 'bg-info text-dark',
                'CLIENT_SIGNED': 'bg-warning text-dark',
                'AWAITING_COMPANY': 'bg-primary',
                'FULLY_SIGNED': 'bg-success'
            };
            return badges[status] || 'bg-secondary';
        }
    }
}
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/app.php';
?>