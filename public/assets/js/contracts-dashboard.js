(function (window, document) {
    const config = window.contractDashboardConfig || {};
    const ui = window.ContractUi || {};
    const escapeHtml = ui.escapeHtml || ((value) => String(value));
    const formatDate = ui.formatDate || ((value) => value || '');
    const responseJson = ui.responseJson || ((response) => response.json());
    const stateOrder = ['DRAFT', 'AWAITING_CLIENT', 'CLIENT_SIGNED', 'AWAITING_COMPANY', 'FULLY_SIGNED'];
    const state = { contracts: [], selectedId: null, loading: false };

    const nodes = {
        table: document.getElementById('contractsTableBody'),
        search: document.getElementById('contractSearch'),
        status: document.getElementById('statusFilter'),
        empty: document.getElementById('emptyContracts'),
        counts: {
            DRAFT: document.getElementById('draftCount'),
            AWAITING_CLIENT: document.getElementById('clientCount'),
            AWAITING_COMPANY: document.getElementById('companyCount'),
            FULLY_SIGNED: document.getElementById('finalCount')
        },
        detail: {
            title: document.getElementById('detailTitle'),
            client: document.getElementById('detailClient'),
            type: document.getElementById('detailType'),
            path: document.getElementById('detailPath'),
            action: document.getElementById('detailAction'),
            phaseTitle: document.getElementById('detailPhaseTitle'),
            phaseCopy: document.getElementById('detailPhaseCopy'),
            editor: document.getElementById('detailEditorLink'),
            audit: document.getElementById('detailAuditLink'),
            view: document.getElementById('detailViewLink')
        }
    };

    if (!nodes.table) return;

    function normalize(contract) {
        const status = (contract.signing_state || contract.status || 'DRAFT').toUpperCase();
        return {
            id: contract.id,
            title: contract.title || 'Untitled Contract',
            client: contract.client_name || contract.company_name || contract.client || 'Client pending',
            type: contract.document_type || contract.type || 'Service Agreement',
            owner: contract.owner_name || contract.created_by_name || contract.owner || 'Elie',
            updated: contract.updated_at || contract.created_at || '',
            status: status,
            path: contract.file_path ? 'Document generated' : 'No document generated',
            description: contract.description || ''
        };
    }

    function statusText(status) {
        return {
            DRAFT: 'Draft',
            AWAITING_CLIENT: 'Awaiting Client',
            CLIENT_SIGNED: 'Client Signed',
            AWAITING_COMPANY: 'Company Action',
            FULLY_SIGNED: 'Fully Signed'
        }[status] || 'Draft';
    }

    function statusClass(status) {
        return {
            DRAFT: 'draft',
            AWAITING_CLIENT: 'client',
            CLIENT_SIGNED: 'client',
            AWAITING_COMPANY: 'company',
            FULLY_SIGNED: 'final'
        }[status] || 'draft';
    }

    function nextAction(contract) {
        return {
            DRAFT: 'Review the draft and send it to the client',
            AWAITING_CLIENT: 'Client chooses digital or hard copy',
            CLIENT_SIGNED: 'Move into company execution',
            AWAITING_COMPANY: 'Company representative signs and seals',
            FULLY_SIGNED: 'Send final PDF and secure 30-day link'
        }[contract.status] || 'Draft and review';
    }

    function phaseCopy(status) {
        return {
            DRAFT: 'Originator and reviewers can still change the body. Each save creates a version and review record.',
            AWAITING_CLIENT: 'The body is frozen. Client signs digitally in the portal or returns a scanned hard copy.',
            CLIENT_SIGNED: 'Client signature is recorded. The contract is ready for company execution.',
            AWAITING_COMPANY: 'Only company signature, seal, stamp, and snapshot actions should run now.',
            FULLY_SIGNED: 'The contract is terminal. Distribution creates the client access token and audit record.'
        }[status] || 'Select a contract to inspect the lifecycle state.';
    }

    function stateRank(status) {
        return stateOrder.indexOf(status);
    }

    function filteredContracts() {
        const search = (nodes.search?.value || '').trim().toLowerCase();
        const status = nodes.status?.value || '';

        return state.contracts.filter((contract) => {
            const text = [contract.title, contract.client, contract.type, contract.owner].join(' ').toLowerCase();
            return (!status || contract.status === status) && (!search || text.includes(search));
        });
    }

    function renderCounts() {
        const counts = state.contracts.reduce((carry, contract) => {
            const key = contract.status === 'CLIENT_SIGNED' ? 'AWAITING_COMPANY' : contract.status;
            carry[key] = (carry[key] || 0) + 1;
            return carry;
        }, {});

        stateOrder.forEach((status) => {
            if (nodes.counts[status]) nodes.counts[status].textContent = counts[status] || 0;
        });
    }

    function route(path) {
        return (config.baseUrl || '') + path;
    }

    function renderTable() {
        const rows = filteredContracts();
        nodes.empty?.classList.toggle('hidden', rows.length > 0 || state.loading);

        if (state.loading) {
            nodes.table.innerHTML = '<tr><td colspan="7">Loading contracts...</td></tr>';
            return;
        }

        nodes.table.innerHTML = rows.map((contract) => [
            '<tr class="' + (String(contract.id) === String(state.selectedId) ? 'is-active' : '') + '" data-contract-id="' + escapeHtml(contract.id) + '">',
            '<td><strong>' + escapeHtml(contract.title) + '</strong><small>' + escapeHtml(contract.description || contract.path) + '</small></td>',
            '<td>' + escapeHtml(contract.client) + '</td>',
            '<td>' + escapeHtml(contract.type) + '</td>',
            '<td><span class="status-pill ' + statusClass(contract.status) + '">' + statusText(contract.status) + '</span></td>',
            '<td>' + escapeHtml(contract.owner) + '</td>',
            '<td>' + escapeHtml(formatDate(contract.updated)) + '</td>',
            '<td><div class="table-actions">',
            '<a class="row-action" href="' + route('/contracts/show/' + encodeURIComponent(contract.id)) + '">View</a>',
            '<a class="row-action primary" href="' + route('/contracts/' + encodeURIComponent(contract.id) + '/editor#' + actionHash(contract.status)) + '">' + actionLabel(contract.status) + '</a>',
            '</div></td>',
            '</tr>'
        ].join('')).join('');
    }

    function renderDetail() {
        const selected = state.contracts.find((contract) => String(contract.id) === String(state.selectedId)) || state.contracts[0];
        if (!selected) return;

        state.selectedId = selected.id;
        nodes.detail.title.textContent = selected.title;
        nodes.detail.client.textContent = selected.client;
        nodes.detail.type.textContent = selected.type;
        nodes.detail.path.textContent = selected.path;
        nodes.detail.action.textContent = nextAction(selected);
        if (nodes.detail.phaseTitle) nodes.detail.phaseTitle.textContent = statusText(selected.status);
        if (nodes.detail.phaseCopy) nodes.detail.phaseCopy.textContent = phaseCopy(selected.status);
        nodes.detail.editor.href = route('/contracts/' + encodeURIComponent(selected.id) + '/editor#' + actionHash(selected.status));
        nodes.detail.audit.href = route('/contracts/audit-trail/' + encodeURIComponent(selected.id));
        nodes.detail.view.href = route('/contracts/show/' + encodeURIComponent(selected.id));

        document.querySelectorAll('.timeline-step').forEach((step) => {
            step.classList.toggle('active', stateOrder.indexOf(step.dataset.state) <= stateRank(selected.status));
        });
        document.querySelectorAll('[data-phase-state]').forEach((phase) => {
            const phaseState = phase.dataset.phaseState;
            const normalizedSelected = selected.status === 'CLIENT_SIGNED' ? 'AWAITING_COMPANY' : selected.status;
            phase.classList.toggle('active', phaseState === normalizedSelected || phaseState === selected.status);
            phase.classList.toggle('complete', stateOrder.indexOf(phaseState) < stateRank(selected.status));
        });
    }

    function actionHash(status) {
        return {
            DRAFT: 'changes',
            AWAITING_CLIENT: 'signing',
            CLIENT_SIGNED: 'signing',
            AWAITING_COMPANY: 'signing',
            FULLY_SIGNED: 'distribution'
        }[status] || 'versions';
    }

    function actionLabel(status) {
        return status === 'FULLY_SIGNED' ? 'Distribute' : status === 'DRAFT' ? 'Review' : 'Execute';
    }

    function render() {
        renderCounts();
        renderTable();
        renderDetail();
    }

    async function loadContracts() {
        state.loading = true;
        renderTable();

        try {
            const response = await fetch(config.apiUrl, { headers: { Accept: 'application/json' } });
            const result = await responseJson(response);
            const list = Array.isArray(result) ? result : (result.contracts || result.data || []);
            state.contracts = list.map(normalize);
            state.selectedId = state.contracts[0]?.id || null;
        } catch (error) {
            state.contracts = [];
            nodes.table.innerHTML = '<tr><td colspan="7">Contracts could not be loaded. Check Apache and MySQL, then refresh.</td></tr>';
        } finally {
            state.loading = false;
            render();
        }
    }

    nodes.search?.addEventListener('input', renderTable);
    nodes.status?.addEventListener('change', renderTable);
    nodes.table.addEventListener('click', (event) => {
        const row = event.target.closest('[data-contract-id]');
        if (!row) return;
        state.selectedId = row.dataset.contractId;
        render();
    });

    loadContracts();
})(window, document);
