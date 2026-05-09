(function (window) {
    const storageKey = 'itec_contract_portal_contracts';

    function create(basePath) {
        function updateLabel(value) {
            const now = value ? new Date(value) : new Date();
            const today = new Date();
            return now.toDateString() === today.toDateString() ? 'Today' : now.toLocaleDateString();
        }

        function generateToken(id) {
            return 'demo-' + id + '-' + Math.random().toString(36).slice(2, 10);
        }

        function contractBodyFallback(title) {
            return [
                title + ' governs the commercial relationship between the company and the client.',
                'The client may sign digitally through the portal or request a hard copy workflow.',
                'After the client signs, the document body becomes read-only and the company completes execution with its authorized signature and seal.'
            ].join(' ');
        }

        function normalize(contract) {
            const normalized = Object.assign({}, contract);
            normalized.id = Number(normalized.id) || Date.now();
            normalized.title = normalized.title || 'New Contract';
            normalized.client = normalized.client || 'Client';
            normalized.clientEmail = normalized.clientEmail || (normalized.client ? normalized.client.toLowerCase().replace(/\s+/g, '.') + '@example.com' : 'client@example.com');
            normalized.type = normalized.type || 'Service Agreement';
            normalized.status = normalized.status || 'DRAFT';
            normalized.owner = normalized.owner || 'Elie';
            normalized.path = normalized.path || 'Client decides';
            normalized.updatedAt = normalized.updatedAt || new Date().toISOString();
            normalized.updated = normalized.updated || updateLabel(normalized.updatedAt);
            normalized.body = normalized.body || contractBodyFallback(normalized.title);
            normalized.token = normalized.token || generateToken(normalized.id);
            normalized.sharedAt = normalized.sharedAt || '';
            normalized.clientNotes = normalized.clientNotes || '';
            normalized.clientChoice = normalized.clientChoice || '';
            normalized.clientSignedAt = normalized.clientSignedAt || '';
            normalized.clientSignatureName = normalized.clientSignatureName || '';
            normalized.clientSignatureText = normalized.clientSignatureText || '';
            normalized.audit = Array.isArray(normalized.audit) ? normalized.audit : [];
            return normalized;
        }

        function seed() {
            return [
                normalize({
                    id: 1,
                    title: 'Service Agreement #1',
                    client: 'Rwanda Tech Group',
                    clientEmail: 'contracts@rwandatech.example',
                    type: 'Service Agreement',
                    status: 'DRAFT',
                    owner: 'Elie',
                    path: 'Client decides',
                    body: 'This service agreement covers support scope, billing terms, obligations, and the execution sequence for both parties.'
                }),
                normalize({
                    id: 8,
                    title: 'Financing Contract #8',
                    client: 'Umucyo Stores',
                    clientEmail: 'finance@umucyo.example',
                    type: 'Financing Contract',
                    status: 'AWAITING_CLIENT',
                    owner: 'Finance',
                    path: 'Digital first',
                    sharedAt: new Date().toISOString(),
                    body: 'This financing contract defines repayment schedule, collateral obligations, and default handling for the client.'
                }),
                normalize({
                    id: 14,
                    title: 'Lease Addendum #14',
                    client: 'Kivu Logistics',
                    clientEmail: 'legal@kivu.example',
                    type: 'Lease Addendum',
                    status: 'FULLY_SIGNED',
                    owner: 'Legal',
                    path: 'Hard copy first',
                    sharedAt: new Date().toISOString(),
                    clientChoice: 'hard_copy',
                    clientSignedAt: new Date().toISOString(),
                    clientSignatureName: 'Kivu Logistics',
                    clientSignatureText: 'Kivu Logistics'
                })
            ];
        }

        function load() {
            try {
                const raw = JSON.parse(localStorage.getItem(storageKey));
                const list = Array.isArray(raw) && raw.length ? raw : seed();
                return list.map(normalize);
            } catch (error) {
                return seed();
            }
        }

        function save(contracts) {
            localStorage.setItem(storageKey, JSON.stringify(contracts.map(normalize)));
        }

        function update(mutator) {
            const contracts = load();
            const result = mutator(contracts) || contracts;
            save(result);
            return result.map(normalize);
        }

        function getById(contracts, id) {
            return contracts.find(function (contract) {
                return String(contract.id) === String(id);
            }) || null;
        }

        function getByToken(contracts, token) {
            return contracts.find(function (contract) {
                return contract.token === token;
            }) || null;
        }

        function getVisibleToClient(contracts) {
            return contracts.filter(function (contract) {
                return contract.sharedAt || contract.status === 'AWAITING_CLIENT' || contract.status === 'AWAITING_COMPANY' || contract.status === 'FULLY_SIGNED';
            });
        }

        function statusText(status) {
            return {
                DRAFT: 'Draft',
                AWAITING_CLIENT: 'Awaiting Client',
                CLIENT_SIGNED: 'Client Signed',
                AWAITING_COMPANY: 'Company Action',
                FULLY_SIGNED: 'Fully Signed'
            }[status] || status;
        }

        function statusClass(status) {
            if (status === 'FULLY_SIGNED') return 'final';
            if (status === 'AWAITING_COMPANY' || status === 'CLIENT_SIGNED') return 'company';
            if (status === 'AWAITING_CLIENT') return 'client';
            return 'draft';
        }

        function nextAction(contract) {
            return {
                DRAFT: 'Draft and review',
                AWAITING_CLIENT: 'Send and collect client signature',
                CLIENT_SIGNED: 'Lock body',
                AWAITING_COMPANY: 'Company sign and seal',
                FULLY_SIGNED: 'Distribute final PDF'
            }[contract.status] || 'Review';
        }

        function contractLink(contract) {
            return window.location.origin + basePath + '/views/clients/portal.php?token=' + encodeURIComponent(contract.token);
        }

        function signatureLink(contract) {
            return basePath + '/views/signatures/sign.php?token=' + encodeURIComponent(contract.token);
        }

        function emailLink(contract) {
            const subject = encodeURIComponent('Contract for signature: ' + contract.title);
            const body = encodeURIComponent([
                'Hello ' + contract.client + ',',
                '',
                'Please review your contract in the portal using the link below:',
                contractLink(contract),
                '',
                'When ready, you can read, save your notes, and sign digitally through the portal.',
                '',
                'Regards,',
                contract.owner
            ].join('\n'));

            return 'mailto:' + contract.clientEmail + '?subject=' + subject + '&body=' + body;
        }

        function pushAudit(contract, message) {
            const event = window.ContractUi.formatDate(new Date().toISOString()) + ' - ' + message;
            contract.audit = Array.isArray(contract.audit) ? contract.audit : [];
            contract.audit.unshift(event);
        }

        function setStatus(contract, status) {
            contract.status = status;
            contract.updatedAt = new Date().toISOString();
            contract.updated = updateLabel(contract.updatedAt);
        }

        return {
            load: load,
            save: save,
            update: update,
            normalize: normalize,
            getById: getById,
            getByToken: getByToken,
            getVisibleToClient: getVisibleToClient,
            statusText: statusText,
            statusClass: statusClass,
            nextAction: nextAction,
            contractLink: contractLink,
            signatureLink: signatureLink,
            emailLink: emailLink,
            pushAudit: pushAudit,
            setStatus: setStatus,
            updateLabel: updateLabel
        };
    }

    window.ContractDemoStore = {
        create: create
    };
})(window);
