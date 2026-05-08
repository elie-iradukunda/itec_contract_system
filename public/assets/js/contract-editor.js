(function () {
    const config = window.contractEditorConfig || {};
    const editor = document.getElementById('documentEditor');
    const mount = document.getElementById('onlyOfficeMount');
    const saveButton = document.getElementById('saveButton');
    const message = document.getElementById('saveMessage');
    const badge = document.getElementById('statusBadge');
    const banner = document.getElementById('readOnlyBanner');
    let onlyOfficeActive = false;
    let currentState = config.signingState || 'DRAFT';

    if (!editor || !mount || !saveButton) return;

    function setMessage(text, type) {
        message.textContent = text;
        message.className = type || '';
    }

    function editorHeight() {
        return Math.max(900, window.innerHeight - 245) + 'px';
    }

    function sizeOnlyOfficeFrame() {
        mount.style.height = editorHeight();
        const frame = mount.querySelector('iframe');
        if (!frame) return;
        frame.style.width = '100%';
        frame.style.height = '100%';
        frame.style.minHeight = editorHeight();
    }

    function setLoading(isLoading) {
        saveButton.disabled = isLoading || currentState !== 'DRAFT';
        saveButton.querySelector('.spinner').classList.toggle('hidden', !isLoading);
        saveButton.querySelector('.buttonText').textContent = isLoading ? 'Saving...' : 'Save Contract';
    }

    function applySigningState(state) {
        currentState = (state || 'DRAFT').toUpperCase();
        const isDraft = currentState === 'DRAFT';
        badge.textContent = currentState;
        badge.className = 'badge ' + currentState.toLowerCase();
        editor.setAttribute('contenteditable', isDraft ? 'true' : 'false');
        banner.classList.toggle('hidden', isDraft);
        saveButton.disabled = !isDraft;
    }

    function showFallback(reason) {
        onlyOfficeActive = false;
        mount.hidden = true;
        editor.hidden = false;
        setMessage(reason || 'Browser editor active until ONLYOFFICE is connected', '');
    }

    function loadScript(url) {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = url;
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    async function startOnlyOffice() {
        const oo = config.onlyOffice || {};
        if (!oo.enabled) return showFallback('ONLYOFFICE Document Server is not configured');
        if (!oo.available) return showFallback(oo.setupHint || 'ONLYOFFICE Document Server is not running');
        if (!oo.apiUrl || !oo.config) return showFallback('ONLYOFFICE configuration is incomplete');
        try {
            await loadScript(oo.apiUrl);
            if (!window.DocsAPI) throw new Error('ONLYOFFICE API did not load');
            editor.hidden = true;
            mount.hidden = false;
            mount.textContent = '';
            oo.config.width = '100%';
            oo.config.height = editorHeight();
            new DocsAPI.DocEditor('onlyOfficeMount', oo.config);
            onlyOfficeActive = true;
            setTimeout(sizeOnlyOfficeFrame, 500);
            setTimeout(sizeOnlyOfficeFrame, 1500);
            setMessage('ONLYOFFICE editor loaded', 'success');
        } catch (error) {
            showFallback('ONLYOFFICE could not load. Browser editor is active.');
        }
    }

    async function saveContract() {
        setLoading(true);
        setMessage('Saving contract...', '');
        try {
            const body = new FormData();
            const url = onlyOfficeActive ? config.onlyOffice.forceSaveUrl : config.saveUrl;
            body.append('key', config.onlyOffice?.config?.document?.key || '');
            body.append('content', editor.innerText.trim());
            const response = await fetch(url, { method: 'POST', body });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || 'Save failed');
            setMessage(result.message || 'Contract saved successfully', 'success');
        } catch (error) {
            setMessage(error.message || 'Unable to save contract', 'error');
        } finally {
            setLoading(false);
        }
    }

    async function pollStatus() {
        try {
            const response = await fetch(config.statusUrl, { headers: { Accept: 'application/json' } });
            if (!response.ok) return;
            const result = await response.json();
            if (result.signing_state) applySigningState(result.signing_state);
        } catch (error) {}
    }

    applySigningState(currentState);
    startOnlyOffice();
    window.addEventListener('resize', sizeOnlyOfficeFrame);
    saveButton.addEventListener('click', saveContract);
    setInterval(pollStatus, 10000);
})();
