(function (window) {
    const pages = window.ContractPages = window.ContractPages || {};

    pages.initClientPortal = function (context) {
        const tableBody = document.getElementById('clientContractsBody');
        if (!tableBody) return;

        const store = context.store;
        const selectedToken = context.query.get('token');
        const elements = {
            title: document.getElementById('clientContractTitle'),
            status: document.getElementById('clientContractStatus'),
            path: document.getElementById('clientContractPath'),
            meta: document.getElementById('clientContractMeta'),
            body: document.getElementById('clientContractBody'),
            notes: document.getElementById('clientContractNotes'),
            saveNotesButton: document.getElementById('saveClientNotesButton'),
            message: document.getElementById('clientPortalMessage'),
            signLink: document.getElementById('clientSignLink'),
            headerSignLink: document.getElementById('clientHeaderSignLink'),
            hardCopyButton: document.getElementById('clientHardCopyButton'),
            downloadFinal: document.getElementById('clientDownloadFinal'),
            pendingCount: document.getElementById('clientPendingCount'),
            finalCount: document.getElementById('clientFinalCount')
        };
        let contracts = store.load();
        let visible = store.getVisibleToClient(contracts);
        let selected = store.getByToken(visible, selectedToken) || visible[0] || null;

        function renderCounts() {
            const pending = visible.filter(function (contract) { return contract.status === 'AWAITING_CLIENT'; }).length;
            const finalCount = visible.filter(function (contract) { return contract.status === 'FULLY_SIGNED'; }).length;
            elements.pendingCount.textContent = pending;
            elements.finalCount.textContent = finalCount;
        }

        function renderTable() {
            if (!visible.length) {
                tableBody.innerHTML = '<tr><td colspan="4">No client-access contracts available yet.</td></tr>';
                return;
            }

            tableBody.innerHTML = visible.map(function (contract) {
                return [
                    '<tr>',
                    '<td><strong>' + window.ContractUi.escapeHtml(contract.title) + '</strong></td>',
                    '<td><span class="status-pill ' + store.statusClass(contract.status) + '">' + store.statusText(contract.status) + '</span></td>',
                    '<td>' + window.ContractUi.escapeHtml(store.nextAction(contract)) + '</td>',
                    '<td><button class="row-action" type="button" data-client-contract="' + contract.id + '">Open</button></td>',
                    '</tr>'
                ].join('');
            }).join('');
        }

        function renderSelected() {
            if (!selected) return;

            elements.title.textContent = selected.title;
            elements.status.textContent = store.statusText(selected.status);
            elements.status.className = 'status-pill ' + store.statusClass(selected.status);
            elements.path.textContent = selected.path;
            elements.meta.textContent = selected.client + ' - ' + selected.clientEmail;
            elements.body.textContent = selected.body;
            elements.notes.value = selected.clientNotes || '';
            elements.signLink.href = store.signatureLink(selected);
            elements.headerSignLink.href = store.signatureLink(selected);
            elements.downloadFinal.classList.toggle('hidden', selected.status !== 'FULLY_SIGNED');

            if (selected.status === 'FULLY_SIGNED') {
                elements.downloadFinal.href = context.basePath + '/views/contracts/final-pdf.php?token=' + encodeURIComponent(selected.token);
                elements.message.textContent = 'This contract is fully executed. The client can open the final PDF.';
            } else if (selected.status === 'AWAITING_COMPANY') {
                elements.message.textContent = 'Client signature is complete. The company will finish execution next.';
            } else if (selected.clientChoice === 'hard_copy') {
                elements.message.textContent = 'Hard copy selected. Staff should receive, scan, and attach the signed document.';
            } else {
                elements.message.textContent = 'Review the contract, save notes if needed, then proceed to digital signing or choose hard copy.';
            }
        }

        function refresh() {
            contracts = store.load();
            visible = store.getVisibleToClient(contracts);
            selected = store.getByToken(visible, selectedToken) || store.getById(visible, selected && selected.id) || visible[0] || null;
            renderCounts();
            renderTable();
            renderSelected();
        }

        function saveNotes() {
            if (!selected) return;

            store.update(function (list) {
                const contract = store.getById(list, selected.id);
                if (!contract) return list;
                contract.clientNotes = elements.notes.value.trim();
                contract.updatedAt = new Date().toISOString();
                contract.updated = store.updateLabel(contract.updatedAt);
                store.pushAudit(contract, 'Client saved portal notes');
                return list;
            });

            refresh();
            elements.message.textContent = 'Client notes saved in the browser for demo testing.';
        }

        function chooseHardCopy() {
            if (!selected) return;

            store.update(function (list) {
                const contract = store.getById(list, selected.id);
                if (!contract) return list;
                contract.clientChoice = 'hard_copy';
                contract.sharedAt = contract.sharedAt || new Date().toISOString();
                store.setStatus(contract, 'AWAITING_CLIENT');
                store.pushAudit(contract, 'Client selected hard copy signing');
                return list;
            });

            refresh();
            elements.message.textContent = 'Hard copy path selected. Staff can now collect and upload the signed scan.';
        }

        refresh();

        tableBody.addEventListener('click', function (event) {
            const button = event.target.closest('[data-client-contract]');
            if (!button) return;
            selected = store.getById(visible, button.dataset.clientContract);
            renderSelected();
        });
        elements.saveNotesButton.addEventListener('click', saveNotes);
        elements.hardCopyButton.addEventListener('click', chooseHardCopy);
    };
})(window);
