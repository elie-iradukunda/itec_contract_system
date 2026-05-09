(function () {
    const config = window.contractEditorConfig || {};
    const editorElement = document.getElementById('documentEditor');
    const saveButton = document.getElementById('saveButton');
    const message = document.getElementById('saveMessage');
    const badge = document.getElementById('statusBadge');
    const banner = document.getElementById('readOnlyBanner');
    const versionsList = document.getElementById('versionsList');
    const versionCount = document.getElementById('versionCount');
    let editorInstance = null;
    let currentState = config.signingState || 'DRAFT';
    let isSaving = false;

    if (!editorElement || !saveButton) return;

    function setMessage(text, type) {
        message.textContent = text;
        message.className = type || '';
    }

    function setLoading(loading) {
        isSaving = loading;
        saveButton.disabled = loading || currentState !== 'DRAFT';
        saveButton.querySelector('.spinner').classList.toggle('hidden', !loading);
        saveButton.querySelector('.buttonText').textContent = loading ? 'Saving...' : 'Save Contract';
    }

    function setEditorReadOnly(readOnly) {
        if (editorInstance && editorInstance.mode) {
            editorInstance.mode.set(readOnly ? 'readonly' : 'design');
        } else if (editorElement) {
            editorElement.readOnly = readOnly;
        }
    }

    function applySigningState(state) {
        currentState = (state || 'DRAFT').toUpperCase();
        const isDraft = currentState === 'DRAFT';
        badge.textContent = currentState;
        badge.className = 'badge ' + currentState.toLowerCase();
        banner.classList.toggle('hidden', isDraft);
        saveButton.disabled = !isDraft || isSaving;
        setEditorReadOnly(!isDraft);
        renderRestoreLocks();
    }

    function getEditorData() {
        return editorInstance ? editorInstance.getContent() : editorElement.value.trim();
    }

    function formatSavedAt(value) {
        if (!value) return 'Saved just now';
        const normalized = value.replace(' ', 'T');
        const date = new Date(normalized);

        return Number.isNaN(date.getTime()) ? value : date.toLocaleString();
    }

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, (char) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char]));
    }

    function versionUrl(template, versionNo) {
        return template.replace('__VERSION__', encodeURIComponent(versionNo));
    }

    function renderVersions(versions) {
        const items = Array.isArray(versions) ? versions : [];
        versionCount.textContent = items.length + ' saved';

        if (!items.length) {
            versionsList.innerHTML = '<p class="muted">No saves yet.</p>';
            return;
        }

        versionsList.innerHTML = items.map((item) => {
            const author = item.saved_by_name || (item.saved_by ? 'User #' + item.saved_by : 'Unknown user');
            const downloadUrl = versionUrl(config.downloadUrlTemplate, item.version_no);

            return [
                '<article class="version-item">',
                '<strong>Version ' + escapeHtml(item.version_no) + '</strong>',
                '<small>' + escapeHtml(formatSavedAt(item.saved_at)) + '<br>' + escapeHtml(author) + '</small>',
                '<div class="version-actions">',
                '<button type="button" data-restore-version="' + escapeHtml(item.version_no) + '">Restore</button>',
                '<a href="' + escapeHtml(downloadUrl) + '">DOCX</a>',
                '</div>',
                '</article>'
            ].join('');
        }).join('');

        renderRestoreLocks();
    }

    function renderRestoreLocks() {
        if (!versionsList) return;
        const locked = currentState !== 'DRAFT';
        versionsList.querySelectorAll('[data-restore-version]').forEach((button) => {
            button.disabled = locked;
        });
    }

    async function loadVersions() {
        if (!config.versionsUrl || !versionsList) return;

        try {
            const response = await fetch(config.versionsUrl, { headers: { Accept: 'application/json' } });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || 'Could not load versions');
            renderVersions(result.versions);
        } catch (error) {
            versionsList.innerHTML = '<p class="muted">History unavailable.</p>';
        }
    }

    async function saveContract() {
        setLoading(true);
        setMessage('Saving contract...', '');

        try {
            const body = new FormData();
            body.append('content', getEditorData());

            const response = await fetch(config.saveUrl, { method: 'POST', body });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || 'Save failed');

            const versionNo = result.version && result.version.version_no ? ' Version ' + result.version.version_no + ' created.' : '';
            setMessage((result.message || 'Contract saved successfully') + versionNo, 'success');
            await loadVersions();
        } catch (error) {
            setMessage(error.message || 'Unable to save contract', 'error');
        } finally {
            setLoading(false);
        }
    }

    async function restoreVersion(versionNo) {
        if (!versionNo || currentState !== 'DRAFT') return;
        setMessage('Restoring version...', '');

        try {
            const response = await fetch(versionUrl(config.restoreUrlTemplate, versionNo), { method: 'POST' });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || 'Restore failed');

            window.location.reload();
        } catch (error) {
            setMessage(error.message || 'Unable to restore version', 'error');
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

    function startEditor() {
        if (!window.tinymce) {
            setMessage('TinyMCE could not load; basic editing is active.', 'error');
            applySigningState(currentState);
            return;
        }

        window.tinymce.init({
            selector: '#documentEditor',
            base_url: config.tinyMceBaseUrl,
            suffix: '.min',
            license_key: 'gpl',
            height: Math.max(760, window.innerHeight - 265),
            min_height: 620,
            menubar: 'file edit view insert format tools table help',
            menu: {
                file: { title: 'File', items: 'restoredraft | save preview | export print' },
                edit: { title: 'Edit', items: 'undo redo | cut copy paste pastetext | selectall | searchreplace' },
                view: { title: 'View', items: 'visualaid visualchars visualblocks | preview fullscreen | code wordcount' },
                insert: { title: 'Insert', items: 'image media link anchor codesample inserttable | charmap emoticons insertdatetime pagebreak nonbreaking hr' },
                format: { title: 'Format', items: 'bold italic underline strikethrough superscript subscript | blocks fontfamily fontsize align lineheight | forecolor backcolor | removeformat' },
                tools: { title: 'Tools', items: 'spellchecker spellcheckerlanguage | code wordcount' },
                table: { title: 'Table', items: 'inserttable | cell row column | advtablesort | tableprops deletetable' },
                help: { title: 'Help', items: 'help' }
            },
            plugins: 'accordion advlist anchor autolink autoresize autosave charmap code codesample directionality emoticons fullscreen help image importcss insertdatetime link lists media nonbreaking pagebreak preview quickbars save searchreplace table visualblocks visualchars wordcount',
            toolbar: [
                'save restoredraft | undo redo | blocks fontfamily fontsize lineheight | bold italic underline strikethrough subscript superscript | forecolor backcolor',
                'alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | table link anchor image media | pagebreak hr nonbreaking charmap emoticons insertdatetime',
                'ltr rtl | visualblocks visualchars searchreplace | removeformat fullscreen preview code wordcount'
            ].join(' '),
            toolbar_mode: 'wrap',
            toolbar_sticky: true,
            contextmenu: 'link image table spellchecker',
            quickbars_insert_toolbar: 'quickimage quicktable media codesample',
            quickbars_selection_toolbar: 'bold italic underline | forecolor backcolor | quicklink h2 h3 blockquote',
            block_formats: 'Normal=p; No Spacing=div; Title=h1; Subtitle=h2; Heading 1=h1; Heading 2=h2; Heading 3=h3; Quote=blockquote; Code=pre',
            font_family_formats: 'Calibri=calibri,arial,sans-serif; Arial=arial,helvetica,sans-serif; Aptos=aptos,calibri,arial,sans-serif; Times New Roman=times new roman,times,serif; Georgia=georgia,serif; Courier New=courier new,courier,monospace; Verdana=verdana,geneva,sans-serif',
            font_size_formats: '8pt 9pt 10pt 11pt 12pt 14pt 16pt 18pt 20pt 24pt 28pt 36pt 48pt',
            line_height_formats: '1 1.15 1.5 2 2.5 3',
            style_formats: [
                { title: 'Title', block: 'h1' },
                { title: 'Subtitle', block: 'h2' },
                { title: 'Heading 1', block: 'h1' },
                { title: 'Heading 2', block: 'h2' },
                { title: 'Heading 3', block: 'h3' },
                { title: 'Normal', block: 'p' },
                { title: 'No Spacing', block: 'p', styles: { margin: '0' } },
                { title: 'Quote', block: 'blockquote' },
                { title: 'Contract Clause', block: 'p', classes: 'contract-clause' },
                { title: 'Signature Block', block: 'div', classes: 'signature-block' }
            ],
            table_toolbar: 'tableprops tabledelete | tableinsertrowbefore tableinsertrowafter tabledeleterow | tableinsertcolbefore tableinsertcolafter tabledeletecol | tablecellprops tablemergecells tablesplitcells',
            table_advtab: true,
            table_cell_advtab: true,
            table_row_advtab: true,
            link_default_target: '_blank',
            image_advtab: true,
            image_title: true,
            automatic_uploads: false,
            autosave_ask_before_unload: true,
            autosave_interval: '20s',
            autosave_prefix: 'contract-' + (config.saveUrl || '').replace(/\W+/g, '-') + '-',
            autosave_restore_when_empty: false,
            autosave_retention: '30m',
            save_onsavecallback: saveContract,
            paste_data_images: true,
            browser_spellcheck: true,
            promotion: false,
            branding: false,
            statusbar: true,
            resize: true,
            content_style: [
                'html { background: #eef2f7; }',
                'body { max-width: 794px; min-height: 1123px; margin: 34px auto; padding: 72px; box-sizing: border-box; background: #fff; color: #111827; box-shadow: 0 2px 12px rgba(17,24,39,.18); font-family: Calibri, Arial, sans-serif; font-size: 12pt; line-height: 1.45; }',
                'p { margin: 0 0 10pt; }',
                '.contract-clause { margin: 0 0 10pt; padding-left: 18pt; text-indent: -18pt; }',
                '.signature-block { min-height: 90px; margin-top: 24pt; padding-top: 12pt; border-top: 1px solid #111827; }',
                'h1, h2, h3 { margin: 0 0 12pt; line-height: 1.2; }',
                'table { width: 100%; border-collapse: collapse; }',
                'td, th { border: 1px solid #9ca3af; padding: 6px 8px; }',
                'blockquote { margin: 12pt 0; padding-left: 16px; border-left: 3px solid #9ca3af; color: #4b5563; }'
            ].join(' '),
            setup: function (editor) {
                editor.on('init', function () {
                    editorInstance = editor;
                    applySigningState(currentState);
                    setMessage('Word-style editor ready', 'success');
                });
            }
        }).catch(() => {
            setMessage('TinyMCE could not start; basic editing is active.', 'error');
            applySigningState(currentState);
        });
    }

    function resizeEditor() {
        if (editorInstance && editorInstance.theme) {
            editorInstance.theme.resizeTo(null, Math.max(760, window.innerHeight - 265));
        }
    }

    applySigningState(currentState);
    startEditor();
    loadVersions();
    saveButton.addEventListener('click', saveContract);
    window.addEventListener('resize', resizeEditor);
    versionsList?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-restore-version]');
        if (button) restoreVersion(button.dataset.restoreVersion);
    });
    setInterval(pollStatus, 10000);
})();
