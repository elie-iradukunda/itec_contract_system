(function (window) {
    const pages = window.ContractPages = window.ContractPages || {};

    pages.initContractsPage = function (context) {
        const tableBody = document.getElementById('contractsTableBody');
        if (!tableBody) return;

        const store = context.store;
        const ui = context.ui;
        const modal = document.getElementById('contractModal');
        const form = document.getElementById('contractForm');
        const searchInput = document.getElementById('contractSearch');
        const statusFilter = document.getElementById('statusFilter');
        const detail = {
            title: document.getElementById('detailTitle'),
            client: document.getElementById('detailClient'),
            email: document.getElementById('detailEmail'),
            type: document.getElementById('detailType'),
            path: document.getElementById('detailPath'),
            action: document.getElementById('detailAction'),
            editor: document.getElementById('detailEditorLink'),
            audit: document.getElementById('detailAuditLink'),
            portal: document.getElementById('detailPortalLink'),
            share: document.getElementById('detailShareText'),
            message: document.getElementById('detailActionMessage')
        };
        const counts = {
            DRAFT: document.getElementById('draftCount'),
            AWAITING_CLIENT: document.getElementById('clientCount'),
            AWAITING_COMPANY: document.getElementById('companyCount'),
            FULLY_SIGNED: document.getElementById('finalCount')
        };
        const controls = {
            emptyState: document.getElementById('emptyContracts'),
            newButton: document.getElementById('newContractButton'),
            closeButton: document.getElementById('closeContractModal'),
            cancelButton: document.getElementById('cancelContractForm'),
            sendButton: document.getElementById('sendToClientButton'),
            copyButton: document.getElementById('copyPortalLinkButton'),
            previewLink: document.getElementById('previewClientPortalLink'),
            emailLink: document.getElementById('emailClientLink')
        };
        let contracts = store.load();
        let selectedId = contracts[0] ? contracts[0].id : null;

        function filteredContracts() {
            const search = (searchInput && searchInput.value || '').trim().toLowerCase();
            const status = statusFilter && statusFilter.value || 'all';

            return contracts.filter(function (contract) {
                const haystack = [contract.title, contract.client, contract.clientEmail, contract.type, contract.owner].join(' ').toLowerCase();
                return (status === 'all' || contract.status === status) && (!search || haystack.includes(search));
            });
        }

        function renderCounts() {
            const summary = contracts.reduce(function (carry, contract) {
                carry[contract.status] = (carry[contract.status] || 0) + 1;
                return carry;
            }, {});

            Object.keys(counts).forEach(function (status) {
                if (counts[status]) counts[status].textContent = summary[status] || 0;
            });
        }

        function renderTable() {
            const rows = filteredContracts();
            if (controls.emptyState) controls.emptyState.classList.toggle('hidden', rows.length > 0);

            tableBody.innerHTML = rows.map(function (contract) {
                return [
                    '<tr>',
                    '<td><strong>' + ui.escapeHtml(contract.title) + '</strong></td>',
                    '<td>' + ui.escapeHtml(contract.client) + '</td>',
                    '<td>' + ui.escapeHtml(contract.type) + '</td>',
                    '<td><span class="status-pill ' + store.statusClass(contract.status) + '">' + store.statusText(contract.status) + '</span></td>',
                    '<td>' + ui.escapeHtml(contract.owner) + '</td>',
                    '<td>' + ui.escapeHtml(contract.updated) + '</td>',
                    '<td><div class="table-actions">',
                    '<button class="row-action" type="button" data-select-contract="' + contract.id + '">View</button>',
                    '<a class="row-action primary" href="' + context.basePath + '/contracts/' + contract.id + '/editor">Editor</a>',
                    '</div></td>',
                    '</tr>'
                ].join('');
            }).join('');
        }

        function renderTimeline(status) {
            const activeIndex = { DRAFT: 0, AWAITING_CLIENT: 1, CLIENT_SIGNED: 2, AWAITING_COMPANY: 3, FULLY_SIGNED: 4 }[status] || 0;
            document.querySelectorAll('.timeline-step').forEach(function (step, index) {
                step.classList.toggle('active', index <= activeIndex);
            });
        }

        function renderDetail() {
            const contract = store.getById(contracts, selectedId) || contracts[0];
            if (!contract) return;

            selectedId = contract.id;
            detail.title.textContent = contract.title;
            detail.client.textContent = contract.client;
            detail.email.textContent = contract.clientEmail;
            detail.type.textContent = contract.type;
            detail.path.textContent = contract.path;
            detail.action.textContent = store.nextAction(contract);
            detail.editor.href = context.basePath + '/contracts/' + contract.id + '/edit';
            detail.audit.href = context.basePath + '/views/contracts/audit-trail.php';
            detail.portal.href = store.contractLink(contract);
            detail.portal.textContent = store.contractLink(contract);
            detail.share.textContent = contract.sharedAt
                ? 'Shared on ' + ui.formatDate(contract.sharedAt) + '. The client can open this link in the portal.'
                : 'This contract has not been sent yet. Use Send to Client to move it into client access.';
            detail.message.textContent = contract.clientSignedAt
                ? 'Client signed on ' + ui.formatDate(contract.clientSignedAt) + '. Next step: company sign and seal.'
                : '';
            controls.previewLink.href = store.contractLink(contract);
            controls.emailLink.href = store.emailLink(contract);
            renderTimeline(contract.status);
        }

        function refresh() {
            contracts = store.load();
            renderCounts();
            renderTable();
            renderDetail();
        }

        function setModalOpen(open) {
            if (modal) modal.classList.toggle('hidden', !open);
            if (open && form) form.querySelector('input[name="title"]').focus();
        }

        function createContract(event) {
            event.preventDefault();
            const data = new FormData(form);
            const nextId = Math.max(1, ...contracts.map(function (contract) { return Number(contract.id) || 1; })) + 1;
            const contract = store.normalize({
                id: nextId,
                title: data.get('title'),
                client: data.get('client'),
                clientEmail: data.get('clientEmail'),
                type: data.get('type'),
                status: data.get('status'),
                owner: data.get('owner'),
                path: data.get('path'),
                body: data.get('body')
            });

            store.pushAudit(contract, 'Contract record created in frontend demo mode');
            contracts.unshift(contract);
            store.save(contracts);
            selectedId = contract.id;
            refresh();
            form.reset();
            form.querySelector('[name="owner"]').value = 'Elie';
            setModalOpen(false);
        }

        function sendSelectedContract() {
            store.update(function (list) {
                const contract = store.getById(list, selectedId);
                if (!contract) return list;
                contract.sharedAt = new Date().toISOString();
                contract.clientChoice = contract.clientChoice || 'digital';
                store.setStatus(contract, 'AWAITING_CLIENT');
                store.pushAudit(contract, 'Client portal link prepared and sent');
                return list;
            });

            refresh();
            const sent = store.getById(contracts, selectedId);
            if (sent) {
                window.open(store.emailLink(sent), '_self');
                detail.message.textContent = 'Email draft opened and portal link is ready to share.';
            }
        }

        function copySelectedLink() {
            const contract = store.getById(contracts, selectedId);
            if (!contract) return;

            const link = store.contractLink(contract);
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(link).then(function () {
                    detail.message.textContent = 'Portal link copied to clipboard.';
                }).catch(function () {
                    detail.message.textContent = link;
                });
            } else {
                detail.message.textContent = link;
            }
        }

        refresh();

        controls.newButton && controls.newButton.addEventListener('click', function () { setModalOpen(true); });
        controls.closeButton && controls.closeButton.addEventListener('click', function () { setModalOpen(false); });
        controls.cancelButton && controls.cancelButton.addEventListener('click', function () { setModalOpen(false); });
        modal && modal.addEventListener('click', function (event) {
            if (event.target === modal) setModalOpen(false);
        });
        form && form.addEventListener('submit', createContract);
        searchInput && searchInput.addEventListener('input', renderTable);
        statusFilter && statusFilter.addEventListener('change', renderTable);
        controls.sendButton && controls.sendButton.addEventListener('click', sendSelectedContract);
        controls.copyButton && controls.copyButton.addEventListener('click', copySelectedLink);
        tableBody.addEventListener('click', function (event) {
            const button = event.target.closest('[data-select-contract]');
            if (!button) return;
            selectedId = button.dataset.selectContract;
            renderDetail();
        });

        if (window.location.hash === '#new') setModalOpen(true);
    };
})(window);
