(function (window) {
    const pages = window.ContractPages = window.ContractPages || {};

    pages.initSignaturePage = function (context) {
        const form = document.getElementById('signatureCaptureForm');
        if (!form) return;

        const store = context.store;
        const contracts = store.load();
        const contract = store.getByToken(contracts, context.query.get('token')) || store.getById(contracts, context.query.get('id')) || contracts[0];
        const elements = {
            title: document.getElementById('signatureContractTitle'),
            summary: document.getElementById('signatureContractSummary'),
            status: document.getElementById('signatureStatusPill'),
            path: document.getElementById('signaturePathPill'),
            name: document.getElementById('signatureName'),
            text: document.getElementById('signatureText'),
            preview: document.getElementById('signaturePreview'),
            consent: document.getElementById('signatureConsent'),
            message: document.getElementById('signatureResultMessage')
        };

        if (contract) {
            elements.title.textContent = contract.title;
            elements.summary.textContent = contract.body;
            elements.status.textContent = store.statusText(contract.status);
            elements.status.className = 'status-pill ' + store.statusClass(contract.status);
            elements.path.textContent = contract.path;
        }

        elements.text.addEventListener('input', function () {
            elements.preview.textContent = elements.text.value.trim() || 'Typed signature will appear here';
        });

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            if (!contract) {
                elements.message.textContent = 'No contract is connected to this signature request.';
                return;
            }

            if (!elements.name.value.trim() || !elements.text.value.trim() || !elements.consent.checked) {
                elements.message.textContent = 'Enter signer details and confirm consent before applying the signature.';
                return;
            }

            store.update(function (list) {
                const target = store.getById(list, contract.id);
                if (!target) return list;
                target.clientChoice = 'digital';
                target.clientSignatureName = elements.name.value.trim();
                target.clientSignatureText = elements.text.value.trim();
                target.clientSignedAt = new Date().toISOString();
                target.sharedAt = target.sharedAt || new Date().toISOString();
                store.setStatus(target, 'AWAITING_COMPANY');
                store.pushAudit(target, 'Client signed digitally as ' + target.clientSignatureName);
                return list;
            });

            elements.message.textContent = 'Signature applied in frontend demo mode. The contract now waits for company sign and seal.';
            elements.status.textContent = 'Company Action';
            elements.status.className = 'status-pill company';
            elements.preview.textContent = elements.text.value.trim();
        });
    };
})(window);
