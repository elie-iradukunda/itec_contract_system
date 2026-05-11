(function () {
    const config = window.contractEditorConfig || {};
    const editorElement = document.getElementById('documentEditor');
    const saveButton = document.getElementById('saveButton');
    const message = document.getElementById('saveMessage');
    const badge = document.getElementById('statusBadge');
    const banner = document.getElementById('readOnlyBanner');
    const versionsList = document.getElementById('versionsList');
    const versionCount = document.getElementById('versionCount');
    const panelTabs = document.querySelectorAll('[data-panel-tab]');
    const panelSections = document.querySelectorAll('[data-panel-section]');
    const changesList = document.getElementById('changesList');
    const changeCount = document.getElementById('changeCount');
    const signingModal = document.getElementById('signingModal');
    const openSigningChoice = document.getElementById('openSigningChoice');
    const submitForSigning = document.getElementById('submitForSigning');
    const closeSigningModal = document.getElementById('closeSigningModal');
    const phaseInstruction = document.getElementById('phaseInstruction');
    const signingMessage = document.getElementById('signingMessage');
    const signedCopyForm = document.getElementById('signedCopyForm');
    const uploadMessage = document.getElementById('uploadMessage');
    const bodyLockBanner = document.getElementById('bodyLockBanner');
    const bodyLockMessage = document.getElementById('bodyLockMessage');
    const lockPill = document.getElementById('lockPill');
    const lockStatusText = document.getElementById('lockStatusText');
    const signatureAction = document.getElementById('signatureAction');
    const sealAction = document.getElementById('sealAction');
    const signatureActionHint = document.getElementById('signatureActionHint');
    const distributionForm = document.getElementById('distributionForm');
    const recipientEmail = document.getElementById('recipientEmail');
    const distributeButton = document.getElementById('distributeButton');
    const distributionMessage = document.getElementById('distributionMessage');
    const distributionStateText = document.getElementById('distributionStateText');
    const distributionResult = document.getElementById('distributionResult');
    const portalLink = document.getElementById('portalLink');
    const expiryLabel = document.getElementById('expiryLabel');
    const finalPdfPreview = document.getElementById('finalPdfPreview');
    const createForm = document.getElementById('contractCreateForm');
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
        saveButton.disabled = loading || !isDraftState(currentState);
        saveButton.querySelector('.spinner')?.classList.toggle('hidden', !loading);
        const buttonText = saveButton.querySelector('.buttonText');
        if (buttonText) buttonText.textContent = loading ? 'Saving...' : (config.isNew ? 'Create Contract' : 'Save Contract');
    }

    function setEditorReadOnly(readOnly) {
        if (editorInstance && editorInstance.mode) {
            editorInstance.mode.set(readOnly ? 'readonly' : 'design');
        } else if (editorElement) {
            editorElement.readOnly = readOnly;
        }
    }

    function applySigningState(state) {
        const previousState = currentState;
        currentState = (state || 'DRAFT').toUpperCase();
        const isDraft = isDraftState(currentState);
        if (badge) {
            badge.textContent = currentState;
            badge.className = 'badge ' + currentState.toLowerCase();
        }
        banner?.classList.toggle('hidden', isDraft);
        bodyLockBanner?.classList.toggle('hidden', isDraft);
        saveButton.disabled = !isDraft || isSaving;
        setEditorReadOnly(!isDraft);
        renderBodyLockState();
        renderExecutionRail();
        renderRestoreLocks();
        renderChangeLocks();
        renderDistributionState();

        if (previousState !== currentState && previousState) {
            setMessage(isDraft ? 'Contract returned to draft editing' : 'Signing state changed. Body editing is locked.', isDraft ? 'success' : 'error');
        }
    }

    function getEditorData() {
        return editorInstance ? editorInstance.getContent() : editorElement.value.trim();
    }

    const sharedUi = window.ContractUi || {};
    const formatSavedAt = function (value) {
        if (!value) return 'Saved just now';
        return typeof sharedUi.formatDate === 'function' ? sharedUi.formatDate(value) : value;
    };
    const escapeHtml = typeof sharedUi.escapeHtml === 'function'
        ? sharedUi.escapeHtml
        : function (value) {
            return String(value).replace(/[&<>"']/g, (char) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[char]));
        };

    function versionUrl(template, versionNo) {
        return template.replace('__VERSION__', encodeURIComponent(versionNo));
    }

    function changeUrl(template, changeId) {
        return template.replace('__CHANGE__', encodeURIComponent(changeId));
    }

    const responseJson = typeof sharedUi.responseJson === 'function'
        ? sharedUi.responseJson
        : async function (response) {
            const text = await response.text();
            try {
                return text ? JSON.parse(text) : {};
            } catch (error) {
                return { success: response.ok, message: text.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim() };
            }
        };

    // Poll-driven lock and distribution state keeps the frontend aligned with signing progress.
    function isDraftState(state) {
        return (state || '').toUpperCase() === 'DRAFT';
    }

    function isFinalSigningState(state) {
        return ['FULLY_SIGNED', 'FULLY_EXECUTED', 'EXECUTED', 'COMPLETED'].includes((state || '').toUpperCase());
    }

    function stateRank(state) {
        return ['DRAFT', 'AWAITING_CLIENT', 'CLIENT_SIGNED', 'AWAITING_COMPANY', 'FULLY_SIGNED'].indexOf((state || '').toUpperCase());
    }

    function renderExecutionRail() {
        const normalized = currentState === 'CLIENT_SIGNED' ? 'AWAITING_COMPANY' : currentState;
        document.querySelectorAll('[data-execution-state]').forEach((step) => {
            const stepState = step.dataset.executionState;
            step.classList.toggle('active', stepState === normalized || stepState === currentState);
            step.classList.toggle('complete', stateRank(stepState) < stateRank(currentState));
        });
    }

    function portalLinkFromResult(result) {
        const source = result.data || result.distribution || result;
        const directUrl = source.portal_url || source.read_only_url || source.access_url || source.url || source.link;
        const token = source.token || source.access_token || source.distribution_token;

        if (directUrl) return directUrl;
        if (token && config.accessUrlTemplate) {
            return config.accessUrlTemplate.replace('__TOKEN__', encodeURIComponent(token));
        }

        return '';
    }

    function activatePanel(name) {
        panelTabs.forEach((tab) => tab.classList.toggle('active', tab.dataset.panelTab === name));
        panelSections.forEach((section) => section.classList.toggle('active', section.dataset.panelSection === name));
        if (['versions', 'changes', 'signing', 'distribution'].includes(name)) {
            history.replaceState(null, '', '#' + name);
        }
        if (name === 'changes') loadChanges();
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
        const locked = !isDraftState(currentState);
        versionsList.querySelectorAll('[data-restore-version]').forEach((button) => {
            button.disabled = locked;
        });
    }

    function renderChangeLocks() {
        if (!changesList) return;
        const locked = !isDraftState(currentState);
        changesList.querySelectorAll('[data-change-action]').forEach((button) => {
            button.disabled = locked || button.dataset.changePending !== '1';
        });
    }

    function renderBodyLockState() {
        const isDraft = isDraftState(currentState);
        const isFinal = isFinalSigningState(currentState);
        const text = isDraft
            ? 'Body editing is open until signing starts.'
            : isFinal
                ? 'This contract is fully executed. Body content is locked and ready for distribution.'
                : 'Body content is locked. Only the next signature or seal action should continue.';

        if (bodyLockMessage) bodyLockMessage.textContent = text;
        if (lockStatusText) lockStatusText.textContent = text;

        if (lockPill) {
            lockPill.textContent = isDraft ? 'Draft editable' : isFinal ? 'Fully executed' : 'Body locked';
            lockPill.className = 'lock-pill ' + (isDraft ? 'draft' : isFinal ? 'final' : 'locked');
        }

        const canSign = currentState === 'AWAITING_CLIENT';
        const canSeal = currentState === 'AWAITING_COMPANY';
        const canSubmit = currentState === 'DRAFT';

        if (submitForSigning) submitForSigning.disabled = !canSubmit;
        if (openSigningChoice) openSigningChoice.disabled = !(canSubmit || canSign);

        if (signatureAction) {
            signatureAction.classList.toggle('disabled', !canSign);
            signatureAction.setAttribute('aria-disabled', canSign ? 'false' : 'true');
        }

        if (sealAction) {
            sealAction.classList.toggle('disabled', !canSeal);
            sealAction.setAttribute('aria-disabled', canSeal ? 'false' : 'true');
        }

        if (signatureActionHint) {
            signatureActionHint.textContent = isDraft
                ? 'Signature and seal blocks activate after the backend records the first signing state change.'
                : isFinal
                    ? 'Execution is complete. Use Distribution to send the final PDF and read-only portal link.'
                    : canSign
                        ? 'Client digital signature is ready. Body content remains locked.'
                        : canSeal
                        ? 'Company signature and seal are ready. Body content remains locked.'
                        : 'Body editing is locked. Continue with the next required signature.';
        }

        if (phaseInstruction) {
            phaseInstruction.textContent = isDraft
                ? 'Finish internal review, then submit the draft to lock the body and invite the client.'
                : currentState === 'AWAITING_CLIENT'
                    ? 'Client execution is open. Choose digital signing or generate the hard-copy packet.'
                    : currentState === 'CLIENT_SIGNED'
                        ? 'Client signature was recorded. The backend is ready to escalate to company signing.'
                        : canSeal
                            ? 'Company representative can sign and apply the seal. Final PDF generation follows.'
                            : isFinal
                                ? 'Execution is complete. Send the final PDF and secure tokenized link.'
                                : 'Continue with the next available execution action.';
        }
    }

    function renderDistributionState() {
        const isFinal = isFinalSigningState(currentState);
        if (distributionStateText) distributionStateText.textContent = isFinal ? 'Ready to send' : 'Available after full execution';
        if (distributeButton) distributeButton.disabled = !isFinal;
        if (recipientEmail) recipientEmail.disabled = !isFinal;
        if (finalPdfPreview) {
            finalPdfPreview.classList.toggle('disabled', !isFinal);
            finalPdfPreview.setAttribute('aria-disabled', isFinal ? 'false' : 'true');
        }
        if (distributionMessage && !isFinal) {
            distributionMessage.textContent = 'Distribution unlocks when the backend reports FULLY_SIGNED.';
            distributionMessage.className = '';
        }
        if (!isFinal) distributionResult?.classList.add('hidden');
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

    function renderChanges(changes) {
        const items = Array.isArray(changes) ? changes : [];
        const pending = items.filter((item) => (item.status || 'pending').toLowerCase() === 'pending');
        changeCount.textContent = pending.length + (pending.length === 1 ? ' pending' : ' pending');

        if (!items.length) {
            changesList.innerHTML = '<p class="muted">No tracked edits yet.</p>';
            return;
        }

        changesList.innerHTML = items.map((item) => {
            const id = item.id || item.change_id;
            const author = item.author || item.author_name || item.user_name || item.saved_by_name || 'Unknown user';
            const time = item.timestamp || item.created_at || item.saved_at || item.updated_at;
            const oldText = item.old_text || item.original_text || item.before_text || '';
            const newText = item.new_text || item.updated_text || item.after_text || '';
            const status = (item.status || 'pending').toLowerCase();
            const isPending = status === 'pending' && Boolean(id);
            const disabled = !isPending || !isDraftState(currentState) ? ' disabled' : '';
            const pendingValue = isPending ? '1' : '0';

            return [
                '<article class="change-item">',
                '<div class="change-meta"><strong>' + escapeHtml(author) + '</strong><span>' + escapeHtml(formatSavedAt(time)) + '</span></div>',
                '<div class="change-diff">',
                '<div class="diff-box diff-old"><del>' + escapeHtml(oldText || 'Original text unavailable') + '</del></div>',
                '<div class="diff-box diff-new"><ins>' + escapeHtml(newText || 'Updated text unavailable') + '</ins></div>',
                '</div>',
                '<div class="change-actions">',
                '<button class="accept-change" type="button" data-change-action="accept" data-change-pending="' + pendingValue + '" data-change-id="' + escapeHtml(id || '') + '"' + disabled + '>Accept</button>',
                '<button class="reject-change" type="button" data-change-action="reject" data-change-pending="' + pendingValue + '" data-change-id="' + escapeHtml(id || '') + '"' + disabled + '>Reject</button>',
                '</div>',
                '</article>'
            ].join('');
        }).join('');

        renderChangeLocks();
    }

    async function loadChanges() {
        if (!config.changesUrl || !changesList) return;

        try {
            const response = await fetch(config.changesUrl, { headers: { Accept: 'application/json' } });
            const result = await responseJson(response);
            if (!response.ok || result.success === false) throw new Error(result.message || 'Tracked changes are not available yet');
            renderChanges(result.changes || result.tracked_changes || result.data || []);
        } catch (error) {
            changeCount.textContent = '0 pending';
            changesList.innerHTML = '<p class="muted">No tracked edits to review yet.</p>';
        }
    }

    async function reviewChange(changeId, action) {
        const template = action === 'accept' ? config.acceptChangeUrlTemplate : config.rejectChangeUrlTemplate;
        if (!template || !changeId) return;
        if (!isDraftState(currentState)) {
            setMessage('Body content is locked. Tracked changes can no longer be accepted or rejected.', 'error');
            return;
        }

        try {
            const response = await fetch(changeUrl(template, changeId), { method: 'POST', headers: { Accept: 'application/json' } });
            const result = await responseJson(response);
            if (!response.ok || result.success === false) throw new Error(result.message || 'Review service is not connected yet');
            setMessage('Tracked change ' + action + 'ed successfully', 'success');
            loadChanges();
        } catch (error) {
            setMessage(error.message || 'Review service is not connected yet', 'error');
        }
    }

    async function saveContract() {
        setLoading(true);
        setMessage(config.isNew ? 'Creating contract...' : 'Saving contract...', '');

        try {
            // Feature E1/E2: new contracts use JSON creation; existing contracts use FormData saves for versioning.
            let response;
            if (config.isNew) {
                const body = {
                    title: document.getElementById('contractTitle')?.value || 'New Contract',
                    client_name: document.getElementById('clientName')?.value || 'Demo Client',
                    client_email: document.getElementById('clientEmail')?.value || 'client@itec.local',
                    document_type: document.getElementById('contractType')?.value || '',
                    description: document.getElementById('contractDescription')?.value || '',
                    content: getEditorData()
                };

                response = await fetch(config.createUrl || config.saveUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                    body: JSON.stringify(body)
                });
            } else {
                const body = new FormData();
                body.append('content', getEditorData());
                response = await fetch(config.saveUrl, { method: 'POST', body });
            }

            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || 'Save failed');

            if (config.isNew && result.contract_id && config.editUrlTemplate) {
                window.location.href = config.editUrlTemplate.replace('__ID__', encodeURIComponent(result.contract_id));
                return;
            }

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
        if (!versionNo || !isDraftState(currentState)) return;
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

    function setModalOpen(open) {
        if (!signingModal) return;
        signingModal.classList.toggle('hidden', !open);
    }

    async function submitSigningChoice(choice) {
        if (!config.signingChoiceUrl) return;
        signingMessage.textContent = 'Submitting signing choice...';

        try {
            if (isDraftState(currentState) && config.submitUrl) {
                const submitResponse = await fetch(config.submitUrl, { method: 'POST', headers: { Accept: 'application/json' } });
                const submitResult = await responseJson(submitResponse);
                if (!submitResponse.ok || submitResult.success === false) throw new Error(submitResult.message || 'Could not submit contract for signing');
                applySigningState('AWAITING_CLIENT');
            }

            const body = new FormData();
            body.append('choice', choice);
            body.append('signing_choice', choice);

            const response = await fetch(config.signingChoiceUrl, { method: 'POST', body, headers: { Accept: 'application/json' } });
            const result = await responseJson(response);
            if (!response.ok || result.success === false) throw new Error(result.message || 'Signing service is not connected yet');

            signingMessage.textContent = choice === 'digital' ? 'Digital signing selected.' : 'Hard copy selected.';
            if (choice === 'digital' && config.signUrl) {
                window.location.href = config.signUrl;
                return;
            }
        } catch (error) {
            signingMessage.textContent = error.message || 'Signing service is not connected yet.';
        }

        if (choice === 'hard_copy' && config.printPdfUrl) {
            window.open(config.printPdfUrl, '_blank', 'noopener');
        }
    }

    async function uploadSignedCopy(event) {
        event.preventDefault();
        if (!config.uploadSignedUrl) return;

        const fileInput = document.getElementById('signedCopyFile');
        if (!fileInput || !fileInput.files.length) {
            uploadMessage.textContent = 'Choose the returned signed scan first.';
            uploadMessage.className = 'error';
            return;
        }

        uploadMessage.textContent = 'Uploading signed scan...';
        uploadMessage.className = '';

        try {
            const body = new FormData(signedCopyForm);
            const response = await fetch(config.uploadSignedUrl, { method: 'POST', body, headers: { Accept: 'application/json' } });
            const result = await responseJson(response);
            if (!response.ok || result.success === false) throw new Error(result.message || 'Upload service is not connected yet');
            uploadMessage.textContent = result.message || 'Signed scan uploaded successfully.';
            uploadMessage.className = 'success';
            pollStatus();
        } catch (error) {
            uploadMessage.textContent = error.message || 'Upload service is not connected yet.';
            uploadMessage.className = 'error';
        }
    }

    async function distributeContract(event) {
        event.preventDefault();

        if (!isFinalSigningState(currentState)) {
            distributionMessage.textContent = 'Distribution unlocks after the contract is fully signed.';
            distributionMessage.className = 'error';
            return;
        }

        if (!config.distributeUrl || !distributionForm) {
            distributionMessage.textContent = 'Distribution service is not connected yet.';
            distributionMessage.className = 'error';
            return;
        }

        if (!recipientEmail.value.trim()) {
            distributionMessage.textContent = 'Enter the client email before sending.';
            distributionMessage.className = 'error';
            recipientEmail.focus();
            return;
        }

        distributeButton.disabled = true;
        distributionMessage.textContent = 'Sending final PDF and creating the read-only portal link...';
        distributionMessage.className = '';

        try {
            const body = new FormData(distributionForm);
            const response = await fetch(config.distributeUrl, { method: 'POST', body, headers: { Accept: 'application/json' } });
            const result = await responseJson(response);
            if (!response.ok || result.success === false) throw new Error(result.message || 'Distribution service is not connected yet');

            const link = portalLinkFromResult(result);
            distributionMessage.textContent = result.message || 'Final PDF sent and secure portal link created.';
            distributionMessage.className = 'success';

            if (link && portalLink) {
                portalLink.href = link;
                portalLink.textContent = link;
                distributionResult?.classList.remove('hidden');
            }

            if (expiryLabel) {
                const source = result.data || result.distribution || result;
                expiryLabel.textContent = source.expires_at ? 'Expires ' + formatSavedAt(source.expires_at) : 'Expires in 30 days';
            }
        } catch (error) {
            distributionMessage.textContent = error.message || 'Distribution service is not connected yet.';
            distributionMessage.className = 'error';
        } finally {
            renderDistributionState();
        }
    }

    async function submitDraftForSigning() {
        if (!config.submitUrl || !isDraftState(currentState)) return;
        setMessage('Submitting draft for client signing...', '');

        try {
            const response = await fetch(config.submitUrl, { method: 'POST', headers: { Accept: 'application/json' } });
            const result = await responseJson(response);
            if (!response.ok || result.success === false) throw new Error(result.message || result.error || 'Could not submit contract');
            applySigningState(result.new_state || 'AWAITING_CLIENT');
            setMessage(result.message || 'Contract submitted for client signing.', 'success');
            activatePanel('signing');
        } catch (error) {
            setMessage(error.message || 'Could not submit contract for signing.', 'error');
        }
    }

    async function applyCompanySeal(event) {
        event.preventDefault();
        if (!config.sealUrl || sealAction?.getAttribute('aria-disabled') === 'true') return;

        setMessage('Applying company seal...', '');

        try {
            const response = await fetch(config.sealUrl, { method: 'POST', headers: { Accept: 'application/json' } });
            const result = await responseJson(response);
            if (!response.ok || result.success === false) throw new Error(result.message || result.error || 'Seal service is not connected yet');
            setMessage(result.message || 'Company seal applied successfully.', 'success');
            pollStatus();
        } catch (error) {
            setMessage(error.message || 'Unable to apply company seal.', 'error');
        }
    }

    async function pollStatus() {
        if (!config.statusUrl) return;
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
                file: { title: 'File', items: 'restoredraft | save preview fullscreen' },
                edit: { title: 'Edit', items: 'undo redo | cut copy paste pastetext | selectall | searchreplace' },
                view: { title: 'View', items: 'visualaid visualchars visualblocks | preview fullscreen | code wordcount' },
                insert: { title: 'Insert', items: 'image media link anchor codesample inserttable | charmap emoticons insertdatetime pagebreak nonbreaking hr' },
                format: { title: 'Format', items: 'bold italic underline strikethrough superscript subscript | blocks fontfamily fontsize align lineheight | forecolor backcolor | removeformat' },
                tools: { title: 'Tools', items: 'code wordcount' },
                table: { title: 'Table', items: 'inserttable | cell row column | tableprops deletetable' },
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
            contextmenu: 'link image table',
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
        if (!editorInstance) return;

        const height = Math.max(760, window.innerHeight - 265);

        if (editorInstance.theme && typeof editorInstance.theme.resizeTo === 'function') {
            editorInstance.theme.resizeTo(null, height);
            return;
        }

        const container = editorInstance.getContainer && editorInstance.getContainer();
        if (container) {
            container.style.height = height + 'px';
        }

        const iframe = editorInstance.iframeElement;
        if (iframe) {
            iframe.style.height = Math.max(520, height - 120) + 'px';
        }
    }

    applySigningState(currentState);
    startEditor();
    loadVersions();
    loadChanges();
    saveButton.addEventListener('click', saveContract);
    createForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        saveContract();
    });
    window.addEventListener('resize', resizeEditor);
    panelTabs.forEach((tab) => tab.addEventListener('click', () => activatePanel(tab.dataset.panelTab)));
    const initialHash = window.location.hash.replace('#', '');
    if (['changes', 'signing', 'distribution'].includes(initialHash)) {
        activatePanel(initialHash);
    } else if (initialHash === 'signing-choice') {
        activatePanel('signing');
        setModalOpen(true);
    }
    document.querySelectorAll('[data-collapse-target]').forEach((button) => {
        button.addEventListener('click', () => {
            const target = document.getElementById(button.dataset.collapseTarget);
            const collapsed = target.classList.toggle('collapsed');
            button.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        });
    });
    changesList?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-change-action]');
        if (button) {
            if (!isDraftState(currentState)) {
                setMessage('Body content is locked. Reviewer changes are frozen during signing.', 'error');
                return;
            }
            reviewChange(button.dataset.changeId, button.dataset.changeAction);
        }
    });
    openSigningChoice?.addEventListener('click', () => setModalOpen(true));
    submitForSigning?.addEventListener('click', submitDraftForSigning);
    closeSigningModal?.addEventListener('click', () => setModalOpen(false));
    [signatureAction, sealAction, finalPdfPreview].forEach((link) => {
        link?.addEventListener('click', (event) => {
            if (link.getAttribute('aria-disabled') === 'true') {
                event.preventDefault();
                setMessage('That action is locked until the contract reaches the correct signing state.', 'error');
            } else if (link === sealAction) {
                applyCompanySeal(event);
            }
        });
    });
    signingModal?.addEventListener('click', (event) => {
        if (event.target === signingModal) setModalOpen(false);
        const choiceButton = event.target.closest('[data-signing-choice]');
        if (choiceButton) submitSigningChoice(choiceButton.dataset.signingChoice);
    });
    signedCopyForm?.addEventListener('submit', uploadSignedCopy);
    distributionForm?.addEventListener('submit', distributeContract);
    versionsList?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-restore-version]');
        if (button) restoreVersion(button.dataset.restoreVersion);
    });
    setInterval(pollStatus, 10000);
})();
