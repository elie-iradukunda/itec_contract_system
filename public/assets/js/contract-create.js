(function () {
    const config = window.contractCreateConfig || {};
    const form = document.getElementById('contractGeneratorForm');
    const button = document.getElementById('createDraftButton');
    const message = document.getElementById('generatorMessage');
    const ui = window.ContractUi || {};

    if (!form || !button) return;

    const responseJson = typeof ui.responseJson === 'function'
        ? ui.responseJson
        : async function (response) {
            const text = await response.text();
            try {
                return text ? JSON.parse(text) : {};
            } catch (error) {
                return { success: response.ok, message: text };
            }
        };

    const escapeHtml = typeof ui.escapeHtml === 'function'
        ? ui.escapeHtml
        : function (value) {
            return String(value).replace(/[&<>"']/g, function (char) {
                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                }[char];
            });
        };

    function setMessage(text, type) {
        message.textContent = text;
        message.className = 'generator-message' + (type ? ' ' + type : '');
    }

    function setLoading(loading) {
        button.disabled = loading;
        button.textContent = loading ? 'Generating Draft...' : 'Generate Contract Draft';
    }

    function paragraphize(text) {
        const blocks = String(text || '')
            .split(/\n{2,}/)
            .map(function (block) { return block.trim(); })
            .filter(Boolean);

        return blocks.map(function (block) {
            return '<p>' + escapeHtml(block).replace(/\n/g, '<br>') + '</p>';
        }).join('');
    }

    function section(title, body) {
        const html = paragraphize(body);
        return html ? '<h2>' + escapeHtml(title) + '</h2>' + html : '';
    }

    function buildContractBody(data) {
        const timelineLines = [];
        if (data.effective_date) timelineLines.push('Effective Date: ' + data.effective_date);
        if (data.start_date) timelineLines.push('Start Date: ' + data.start_date);
        if (data.duration) timelineLines.push('Duration: ' + data.duration);

        const paymentLines = [];
        if (data.amount) paymentLines.push('Contract Amount: ' + data.amount);
        if (data.payment_terms) paymentLines.push(data.payment_terms);

        const generalLines = [];
        if (data.governing_law) generalLines.push('Governing Law: ' + data.governing_law);
        if (data.additional_clauses) generalLines.push(data.additional_clauses);

        let html = '';
        html += paragraphize(data.description);
        html += section('Scope of Work', data.services);
        html += section('Payment Terms', paymentLines.join('\n\n'));
        html += section('Timeline', timelineLines.join('\n'));
        html += section('Termination', data.termination);
        html += section('Additional Clauses', generalLines.join('\n\n'));

        if (!html) {
            html = '<h2>Agreement Details</h2><p>Contract details will be completed during draft review.</p>';
        }

        return html;
    }

    async function submit(event) {
        event.preventDefault();

        const formData = new FormData(form);
        const payload = {
            title: String(formData.get('title') || '').trim(),
            document_type: String(formData.get('document_type') || '').trim(),
            description: String(formData.get('description') || '').trim(),
            effective_date: String(formData.get('effective_date') || '').trim(),
            start_date: String(formData.get('start_date') || '').trim(),
            duration: String(formData.get('duration') || '').trim(),
            governing_law: String(formData.get('governing_law') || '').trim(),
            services: String(formData.get('services') || '').trim(),
            amount: String(formData.get('amount') || '').trim(),
            payment_terms: String(formData.get('payment_terms') || '').trim(),
            termination: String(formData.get('termination') || '').trim(),
            additional_clauses: String(formData.get('additional_clauses') || '').trim()
        };

        if (!payload.title) {
            setMessage('Contract title is required.', 'error');
            form.querySelector('[name="title"]')?.focus();
            return;
        }

        payload.content = buildContractBody(payload);

        setLoading(true);
        setMessage('Generating the contract draft from the document generator...', '');

        try {
            const response = await fetch(config.createUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const result = await responseJson(response);
            if (!response.ok || result.success === false) {
                throw new Error(result.error || result.message || 'Could not generate the contract draft.');
            }

            setMessage('Draft generated. Opening the review workspace...', 'success');
            if (result.contract_id && config.editUrlTemplate) {
                window.location.href = config.editUrlTemplate.replace('__ID__', encodeURIComponent(result.contract_id));
            }
        } catch (error) {
            setMessage(error.message || 'Could not generate the contract draft.', 'error');
            setLoading(false);
        }
    }

    form.addEventListener('submit', submit);
})();
