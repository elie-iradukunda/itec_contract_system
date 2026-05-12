(function () {
    const config = window.contractEditorConfig || {};
    const editorElement = document.getElementById('documentEditor');
    const saveButton = document.getElementById('saveButton');
    const message = document.getElementById('saveMessage');
    const badge = document.getElementById('statusBadge');
    const banner = document.getElementById('readOnlyBanner');
    const versionsList = document.getElementById('versionsList');
    const versionPreview = document.getElementById('versionPreview');
    const versionCount = document.getElementById('versionCount');
    const panelTabs = document.querySelectorAll('[data-panel-tab]');
    const panelSections = document.querySelectorAll('[data-panel-section]');
    const changesList = document.getElementById('changesList');
    const changeCount = document.getElementById('changeCount');
    const signingModal = document.getElementById('signingModal');
    const openSigningChoice = document.getElementById('openSigningChoice');
    const submitForSigning = document.getElementById('submitForSigning');
    const companySigningAction = document.getElementById('companySigningAction');
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
    const clientEmails = document.getElementById('clientEmails');
    const sendClientPanel = document.querySelector('[data-send-client-panel]');
    const sendClientStatus = document.getElementById('sendClientStatus');
    const sendClientTitle = document.getElementById('sendClientTitle');
    const sendClientSubtitle = document.getElementById('sendClientSubtitle');
    const sendClientActions = document.getElementById('sendClientActions');
    const sendAwaitingActions = document.getElementById('sendAwaitingActions');
    const sendClientFootnote = document.getElementById('sendClientFootnote');
    const recipientPreview = document.getElementById('recipientPreview');
    const recipientValidation = document.getElementById('recipientValidation');
    const sendResult = document.getElementById('sendResult');
    const submitSigningSpinner = document.getElementById('submitSigningSpinner');
    const submitSigningText = document.getElementById('submitSigningText');
    const draftBuilderRoot = document.getElementById('draftBuilderRoot');
    let draftBodyEditor = null;
    let currentState = config.signingState || 'DRAFT';
    let isSaving = false;
    let isSubmittingForSigning = false;
    let submittedRecipients = [];
    let selectedVersionNo = null;

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
        draftBodyEditor?.setReadOnly(readOnly);
        if (editorElement) editorElement.readOnly = readOnly;
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
        signedCopyForm?.classList.toggle('hidden', currentState !== 'AWAITING_CLIENT');

        if (previousState !== currentState && previousState) {
            setMessage(isDraft ? 'Contract returned to draft editing' : 'Signing state changed. Body editing is locked.', isDraft ? 'success' : 'error');
        }
    }

    function getEditorData() {
        return draftBodyEditor ? draftBodyEditor.getContent() : editorElement.value.trim();
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

    function versionPreviewUrl(versionNo) {
        return config.versionPreviewUrlTemplate
            ? config.versionPreviewUrlTemplate.replace('__VERSION__', encodeURIComponent(versionNo))
            : '';
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

    function isValidEmail(value) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(value || '').trim());
    }

    function parseRecipientInput(value) {
        const rawItems = String(value || '')
            .split(/[\s,;]+/)
            .map((item) => item.trim())
            .filter(Boolean);
        const seen = new Set();
        const valid = [];
        const invalid = [];

        rawItems.forEach((item) => {
            const key = item.toLowerCase();
            if (seen.has(key)) return;
            seen.add(key);

            if (isValidEmail(item)) {
                valid.push(item);
            } else {
                invalid.push(item);
            }
        });

        return { valid, invalid, total: rawItems.length };
    }

    function recipientCountText(count) {
        if (!count) return 'No recipients added';
        return count + ' recipient' + (count === 1 ? '' : 's') + ' ready';
    }

    function renderRecipientPreview(parsed) {
        if (!recipientPreview) return;
        const recipients = parsed || parseRecipientInput(clientEmails?.value || '');
        const chips = []
            .concat(recipients.valid.map((email) => (
                '<span class="recipient-chip"><i class="bi bi-check2-circle" aria-hidden="true"></i>' + escapeHtml(email) + '</span>'
            )))
            .concat(recipients.invalid.map((email) => (
                '<span class="recipient-chip invalid"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i>' + escapeHtml(email) + '</span>'
            )));

        recipientPreview.classList.toggle('empty', chips.length === 0);
        recipientPreview.innerHTML = chips.length ? chips.join('') : 'Add the client email to prepare the invitation.';
    }

    function setSubmitSigningLoading(loading, label) {
        isSubmittingForSigning = Boolean(loading);
        submitSigningSpinner?.classList.toggle('hidden', !loading);
        if (submitSigningText) submitSigningText.textContent = loading ? (label || 'Sending email...') : 'Save draft and email client';
        renderSendClientState();
    }

    function showSendResult(type, title, detail) {
        if (!sendResult) return;
        sendResult.classList.remove('hidden', 'error');
        if (type === 'error') sendResult.classList.add('error');
        sendResult.innerHTML = '<strong>' + escapeHtml(title) + '</strong>' + (detail ? '<span>' + escapeHtml(detail) + '</span>' : '');
    }

    function clearSendResult() {
        sendResult?.classList.add('hidden');
        sendResult?.classList.remove('error');
    }

    function renderSendClientState() {
        if (!sendClientPanel) return;

        const parsed = parseRecipientInput(clientEmails?.value || '');
        const isDraft = isDraftState(currentState);
        const isAwaiting = currentState === 'AWAITING_CLIENT';
        const hasValidRecipients = parsed.valid.length > 0;
        const hasInvalidRecipients = parsed.invalid.length > 0;
        const canSubmit = isDraft && hasValidRecipients && !hasInvalidRecipients && !isSubmittingForSigning && !config.isNew;

        renderRecipientPreview(parsed);

        sendClientPanel.classList.toggle('is-awaiting', isAwaiting);
        sendClientPanel.classList.toggle('is-complete', !isDraft && !isAwaiting);

        if (sendClientStatus) {
            const label = isDraft ? (hasValidRecipients && !hasInvalidRecipients ? 'Ready' : 'Needs email') : isAwaiting ? 'Sent' : 'Locked';
            const statusClass = isAwaiting ? ' awaiting' : !isDraft ? ' locked' : '';
            sendClientStatus.textContent = label;
            sendClientStatus.className = 'send-status-pill' + statusClass;
        }

        if (sendClientTitle) {
            sendClientTitle.textContent = isAwaiting ? 'Invitation sent' : isDraft ? 'Client invitation' : 'Client step locked';
        }

        if (sendClientSubtitle) {
            sendClientSubtitle.textContent = isAwaiting
                ? 'The contract is waiting for the client signature.'
                : isDraft
                    ? 'Email the secure signing link to the client.'
                    : 'The contract has moved past client invitation.';
        }

        if (recipientValidation) {
            if (hasInvalidRecipients) {
                recipientValidation.textContent = 'Fix invalid email: ' + parsed.invalid.join(', ');
                recipientValidation.className = 'send-validation error';
            } else if (hasValidRecipients) {
                recipientValidation.textContent = recipientCountText(parsed.valid.length);
                recipientValidation.className = 'send-validation success';
            } else {
                recipientValidation.textContent = 'At least one valid client email is required.';
                recipientValidation.className = 'send-validation';
            }
        }

        if (clientEmails) {
            clientEmails.readOnly = !isDraft;
            clientEmails.setAttribute('aria-invalid', hasInvalidRecipients ? 'true' : 'false');
        }

        if (submitForSigning) {
            submitForSigning.disabled = !canSubmit;
            submitForSigning.classList.toggle('hidden', !isDraft);
        }

        sendClientActions?.classList.toggle('hidden', !isDraft);
        sendAwaitingActions?.classList.toggle('hidden', !isAwaiting);

        if (sendClientFootnote) {
            sendClientFootnote.textContent = config.isNew
                ? 'Save the draft before sending it to the client.'
                : 'Latest edits are saved before the secure signing link is emailed.';
        }

        if (isAwaiting && sendResult && sendResult.classList.contains('hidden')) {
            const count = submittedRecipients.length || parsed.valid.length;
            showSendResult('success', 'Client invitation active', count ? 'Waiting on ' + count + ' recipient' + (count === 1 ? '.' : 's.') : 'Waiting on the client signature.');
        }
    }

    function createDraftBodyEditor(textarea, root) {
        const sectionTypes = [
            { type: 'heading', label: 'Clause heading', shortLabel: 'Heading', icon: 'bi-type-h2', color: 'blue' },
            { type: 'paragraph', label: 'Rich paragraph', shortLabel: 'Paragraph', icon: 'bi-text-paragraph', color: 'teal' },
            { type: 'text', label: 'Plain text clause', shortLabel: 'Text', icon: 'bi-card-text', color: 'slate' },
            { type: 'list', label: 'List or checklist', shortLabel: 'List', icon: 'bi-list-check', color: 'amber' },
            { type: 'checkbox', label: 'Acceptance checkbox', shortLabel: 'Checkbox', icon: 'bi-check2-square', color: 'rose' },
            { type: 'date', label: 'Date line', shortLabel: 'Date', icon: 'bi-calendar3', color: 'indigo' },
            { type: 'signature', label: 'Signature block', shortLabel: 'Signature', icon: 'bi-pen', color: 'emerald' }
        ];
        let sections = parseInitialContent(textarea.value);
        let selectedType = 'paragraph';
        let nextId = sections.length + 1;
        let readOnly = false;
        const quills = new Map();

        function id(prefix) {
            const value = prefix + '-' + nextId;
            nextId += 1;
            return value;
        }

        function typeMeta(type) {
            return sectionTypes.find((item) => item.type === type) || sectionTypes[1];
        }

        function parseInitialContent(html) {
            const cleaned = String(html || '').trim();
            if (!cleaned || cleaned === '<p></p>' || cleaned === '<p><br></p>') return [];

            const template = document.createElement('template');
            template.innerHTML = cleaned;
            const savedSections = Array.from(template.content.querySelectorAll('[data-draft-section]'));
            if (!savedSections.length) {
                return sectionsFromGeneratedDocument(cleaned);
            }

            const parsedSections = savedSections.map((node, index) => {
                const type = node.getAttribute('data-draft-section') || 'paragraph';
                const base = {
                    id: 'section-' + index,
                    type,
                    label: node.getAttribute('data-label') || '',
                    content: '',
                    note: ''
                };

                if (type === 'heading') {
                    base.heading = cleanStoredHeading(node.querySelector('h1,h2,h3,p strong')?.textContent?.trim() || 'Clause heading');
                    base.note = node.querySelector('[data-section-note]')?.textContent?.trim() || '';
                } else if (type === 'paragraph') {
                    const contentNode = node.querySelector('[data-section-content]');
                    base.content = contentNode ? contentNode.innerHTML : node.innerHTML;
                } else if (type === 'text') {
                    base.content = node.querySelector('[data-section-content]')?.textContent?.trim() || '';
                } else if (type === 'list') {
                    base.listStyle = node.getAttribute('data-list-style') || 'bullet';
                    base.items = Array.from(node.querySelectorAll('[data-list-item]')).map((item, itemIndex) => ({
                        id: 'item-' + index + '-' + itemIndex,
                        text: item.getAttribute('data-text') || cleanListText(item.textContent),
                        checked: item.getAttribute('data-checked') === '1'
                    }));
                    if (!base.items.length) base.items = [{ id: 'item-' + index + '-0', text: '', checked: false }];
                } else if (type === 'checkbox') {
                    base.checked = node.getAttribute('data-checked') === '1';
                    base.label = base.label || node.textContent.trim() || 'Acceptance checkbox';
                } else if (type === 'date') {
                    base.value = node.getAttribute('data-value') || '';
                    base.label = base.label || 'Effective date';
                } else if (type === 'signature') {
                    base.leftSigner = node.getAttribute('data-left-signer') || 'Client Representative';
                    base.rightSigner = node.getAttribute('data-right-signer') || 'ITEC Representative';
                    base.note = node.querySelector('[data-section-note]')?.textContent?.trim() || '';
                }

                return base;
            });

            const onlySection = parsedSections[0] || null;
            if (
                parsedSections.length === 1
                && onlySection.type === 'paragraph'
                && /agreement details|contract ref|client signature/i.test((onlySection.label || '') + ' ' + (onlySection.content || ''))
            ) {
                return sectionsFromGeneratedDocument(onlySection.content);
            }

            return parsedSections;
        }

        function sectionsFromGeneratedDocument(html) {
            const lines = extractAgreementLines(html);
            if (!lines.length) {
                return [{
                    id: 'section-0',
                    type: 'paragraph',
                    label: 'Imported document body',
                    content: paragraphsFromLines(htmlToLines(html)),
                    note: ''
                }];
            }

            return groupLinesIntoSections(lines);
        }

        function extractAgreementLines(html) {
            const lines = htmlToLines(html);
            const lower = lines.map((line) => line.toLowerCase());
            const agreementIndexes = [];
            lower.forEach((line, index) => {
                if (line === 'agreement details') agreementIndexes.push(index);
            });

            let bodyLines = agreementIndexes.length ? lines.slice(agreementIndexes[agreementIndexes.length - 1] + 1) : lines;
            const signatureIndex = bodyLines.findIndex((line) => {
                const normalized = line.toLowerCase();
                return normalized === 'signatures'
                    || normalized.startsWith('client signature')
                    || normalized.startsWith('itec solutions')
                    || normalized.startsWith('authorized signatory');
            });

            if (signatureIndex >= 0) bodyLines = bodyLines.slice(0, signatureIndex);

            return bodyLines
                .map((line) => line.trim())
                .filter(Boolean)
                .filter((line) => line.toLowerCase() !== 'imported document body');
        }

        function htmlToLines(html) {
            const template = document.createElement('template');
            template.innerHTML = html || '';
            const blocks = Array.from(template.content.querySelectorAll('h1,h2,h3,h4,h5,h6,p,li,td,th,div'));
            const source = blocks.length ? blocks : [template.content];

            return source
                .map((node) => (node.textContent || '').replace(/\s+/g, ' ').trim())
                .filter(Boolean);
        }

        function groupLinesIntoSections(lines) {
            const sectionsFromLines = [];
            let label = '';
            let content = [];

            function flush() {
                if (!label && !content.length) return;
                sectionsFromLines.push({
                    id: 'section-' + sectionsFromLines.length,
                    type: 'paragraph',
                    label: label || 'Contract details',
                    content: paragraphsFromLines(content),
                    note: ''
                });
                label = '';
                content = [];
            }

            lines.forEach((line, index) => {
                const next = lines[index + 1] || '';
                if (isLikelySectionTitle(line, next)) {
                    flush();
                    label = cleanStoredHeading(line);
                    return;
                }
                content.push(line);
            });

            flush();
            return sectionsFromLines.length ? sectionsFromLines : [{
                id: 'section-0',
                type: 'paragraph',
                label: 'Contract details',
                content: paragraphsFromLines(lines),
                note: ''
            }];
        }

        function isLikelySectionTitle(line, next) {
            if (/^\d+(\.\d+)*\.?\s+\S/.test(line)) return true;
            return line.length <= 70 && next.length > 70 && !/[.!?;:]$/.test(line);
        }

        function paragraphsFromLines(lines) {
            return (lines || [])
                .map((line) => String(line || '').trim())
                .filter(Boolean)
                .map((line) => '<p>' + escapeHtml(line) + '</p>')
                .join('');
        }

        function cleanStoredHeading(value) {
            return String(value || '').replace(/^\d+(\.\d+)*\.?\s+/, '').trim();
        }

        function cleanListText(value) {
            return String(value || '').replace(/^(No\.\s*\d+:|\d+\.|[-*]|\[[x ]\])\s*/i, '').trim();
        }

        function makeSection(type) {
            const section = { id: id('section'), type, label: '', content: '', note: '' };
            if (type === 'heading') section.heading = 'New Clause Heading';
            if (type === 'paragraph') section.label = 'Draft paragraph';
            if (type === 'text') section.label = 'Text clause';
            if (type === 'list') {
                section.label = 'Key terms';
                section.listStyle = 'bullet';
                section.items = [{ id: id('item'), text: '', checked: false }];
            }
            if (type === 'checkbox') {
                section.label = 'The parties accept this clause.';
                section.checked = false;
            }
            if (type === 'date') {
                section.label = 'Effective date';
                section.value = '';
            }
            if (type === 'signature') {
                section.leftSigner = 'Client Representative';
                section.rightSigner = 'ITEC Representative';
                section.note = 'Signed by the authorised representatives of both parties.';
            }
            return section;
        }

        function sectionById(sectionId) {
            return sections.find((section) => section.id === sectionId);
        }

        function itemById(section, itemId) {
            return (section.items || []).find((item) => item.id === itemId);
        }

        function collectQuillContent() {
            quills.forEach((quill, sectionId) => {
                const section = sectionById(sectionId);
                if (section) section.content = quill.root.innerHTML;
            });
        }

        function sectionTitle(section) {
            if (section.type === 'heading') return section.heading || 'Clause heading';
            if (section.type === 'signature') return 'Signature block';
            if (section.type === 'date') return section.label || 'Date line';
            return section.label || typeMeta(section.type).label;
        }

        function renderSection(section, index) {
            const meta = typeMeta(section.type);
            const disabled = readOnly ? ' disabled' : '';
            return [
                '<article class="draft-section-card" data-section-scroll="' + escapeHtml(section.id) + '">',
                '<header class="draft-section-head">',
                '<div class="draft-section-title">',
                '<span class="draft-section-icon ' + escapeHtml(meta.color) + '"><i class="bi ' + escapeHtml(meta.icon) + '"></i></span>',
                '<div><small>Section ' + (index + 1) + '</small><strong>' + escapeHtml(meta.label) + '</strong></div>',
                '</div>',
                '<div class="draft-section-actions">',
                '<button type="button" title="Move up" data-section-action="up" data-section-id="' + escapeHtml(section.id) + '"' + disabled + '><i class="bi bi-arrow-up"></i></button>',
                '<button type="button" title="Move down" data-section-action="down" data-section-id="' + escapeHtml(section.id) + '"' + disabled + '><i class="bi bi-arrow-down"></i></button>',
                '<button type="button" title="Duplicate" data-section-action="duplicate" data-section-id="' + escapeHtml(section.id) + '"' + disabled + '><i class="bi bi-copy"></i></button>',
                '<button type="button" title="Delete" data-section-action="delete" data-section-id="' + escapeHtml(section.id) + '"' + disabled + '><i class="bi bi-trash3"></i></button>',
                '</div>',
                '</header>',
                '<div class="draft-section-body">',
                renderSectionFields(section, disabled),
                '</div>',
                '</article>'
            ].join('');
        }

        function renderField(label, input) {
            return '<label class="draft-field"><span>' + escapeHtml(label) + '</span>' + input + '</label>';
        }

        function fieldAttr(section, field) {
            return ' data-section-id="' + escapeHtml(section.id) + '" data-section-field="' + escapeHtml(field) + '"';
        }

        function renderSectionFields(section, disabled) {
            if (section.type === 'heading') {
                return [
                    renderField('Clause heading', '<input type="text" value="' + escapeHtml(section.heading || '') + '"' + fieldAttr(section, 'heading') + disabled + '>'),
                    renderField('Optional note', '<input type="text" value="' + escapeHtml(section.note || '') + '"' + fieldAttr(section, 'note') + disabled + '>')
                ].join('');
            }

            if (section.type === 'paragraph') {
                const editor = window.Quill
                    ? '<div class="quill-section" data-quill-id="' + escapeHtml(section.id) + '"></div>'
                    : '<textarea rows="8"' + fieldAttr(section, 'content') + disabled + '>' + escapeHtml(section.content || '') + '</textarea>';
                return [
                    renderField('Paragraph label', '<input type="text" value="' + escapeHtml(section.label || '') + '"' + fieldAttr(section, 'label') + disabled + '>'),
                    editor
                ].join('');
            }

            if (section.type === 'text') {
                return [
                    renderField('Text clause title', '<input type="text" value="' + escapeHtml(section.label || '') + '"' + fieldAttr(section, 'label') + disabled + '>'),
                    renderField('Clause text', '<textarea rows="6"' + fieldAttr(section, 'content') + disabled + '>' + escapeHtml(section.content || '') + '</textarea>')
                ].join('');
            }

            if (section.type === 'list') {
                const options = [
                    ['bullet', 'Bullet list'],
                    ['numbered', 'Numbered list'],
                    ['checklist', 'Checklist']
                ].map(([value, label]) => '<option value="' + value + '"' + (section.listStyle === value ? ' selected' : '') + '>' + label + '</option>').join('');
                const items = (section.items || []).map((item, itemIndex) => {
                    const marker = section.listStyle === 'numbered' ? String(itemIndex + 1) : section.listStyle === 'checklist'
                        ? '<input type="checkbox" data-list-checked="1" data-section-id="' + escapeHtml(section.id) + '" data-item-id="' + escapeHtml(item.id) + '"' + (item.checked ? ' checked' : '') + disabled + '>'
                        : '&bull;';
                    return [
                        '<div class="draft-list-row">',
                        '<span>' + marker + '</span>',
                        '<input type="text" value="' + escapeHtml(item.text || '') + '" data-list-field="text" data-section-id="' + escapeHtml(section.id) + '" data-item-id="' + escapeHtml(item.id) + '"' + disabled + '>',
                        '<button type="button" title="Remove item" data-list-action="remove" data-section-id="' + escapeHtml(section.id) + '" data-item-id="' + escapeHtml(item.id) + '"' + disabled + '><i class="bi bi-x-lg"></i></button>',
                        '</div>'
                    ].join('');
                }).join('');
                return [
                    '<div class="draft-two-col">',
                    renderField('List heading', '<input type="text" value="' + escapeHtml(section.label || '') + '"' + fieldAttr(section, 'label') + disabled + '>'),
                    renderField('List type', '<select' + fieldAttr(section, 'listStyle') + disabled + '>' + options + '</select>'),
                    '</div>',
                    '<div class="draft-list-items">' + items + '</div>',
                    '<button type="button" class="draft-add-item" data-list-action="add" data-section-id="' + escapeHtml(section.id) + '"' + disabled + '><i class="bi bi-plus-circle"></i> Add List Item</button>'
                ].join('');
            }

            if (section.type === 'checkbox') {
                return [
                    renderField('Acceptance statement', '<input type="text" value="' + escapeHtml(section.label || '') + '"' + fieldAttr(section, 'label') + disabled + '>'),
                    '<label class="draft-check-row"><input type="checkbox"' + fieldAttr(section, 'checked') + (section.checked ? ' checked' : '') + disabled + '><span>' + escapeHtml(section.label || 'Acceptance checkbox') + '</span></label>'
                ].join('');
            }

            if (section.type === 'date') {
                return [
                    '<div class="draft-two-col">',
                    renderField('Date label', '<input type="text" value="' + escapeHtml(section.label || '') + '"' + fieldAttr(section, 'label') + disabled + '>'),
                    renderField('Date value', '<input type="date" value="' + escapeHtml(section.value || '') + '"' + fieldAttr(section, 'value') + disabled + '>'),
                    '</div>'
                ].join('');
            }

            if (section.type === 'signature') {
                return [
                    '<div class="draft-two-col">',
                    renderField('Left signer', '<input type="text" value="' + escapeHtml(section.leftSigner || '') + '"' + fieldAttr(section, 'leftSigner') + disabled + '>'),
                    renderField('Right signer', '<input type="text" value="' + escapeHtml(section.rightSigner || '') + '"' + fieldAttr(section, 'rightSigner') + disabled + '>'),
                    '</div>',
                    renderField('Signing note', '<input type="text" value="' + escapeHtml(section.note || '') + '"' + fieldAttr(section, 'note') + disabled + '>')
                ].join('');
            }

            return '';
        }

        function render() {
            collectQuillContent();
            quills.clear();
            const disabled = readOnly ? ' disabled' : '';
            const options = sectionTypes.map((option) => '<option value="' + option.type + '"' + (selectedType === option.type ? ' selected' : '') + '>' + escapeHtml(option.label) + '</option>').join('');
            const quickButtons = sectionTypes.map((option) => [
                '<button type="button" data-quick-add="' + option.type + '"' + disabled + '>',
                '<i class="bi ' + escapeHtml(option.icon) + '"></i><span>' + escapeHtml(option.shortLabel) + '</span>',
                '</button>'
            ].join('')).join('');

            root.classList.toggle('is-readonly', readOnly);
            root.innerHTML = [
                '<div class="draft-created-panel">',
                '<div class="draft-created-head">',
                '<p>Created draft parts</p>',
                '<h2>Contract body sections</h2>',
                '<span>Edit the contract body here. The add controls stay below the created content so each new part is placed after the current draft.</span>',
                '</div>',
                '<div class="draft-sections">',
                sections.length ? sections.map(renderSection).join('') : emptyState(disabled),
                '</div>',
                '<div class="draft-add-panel">',
                '<div><p>Document builder</p><h2>Add the next draft section</h2><span>Choose the next block needed for this contract draft.</span></div>',
                '<div class="draft-add-controls">',
                '<select data-section-type-select="1"' + disabled + '>' + options + '</select>',
                '<button type="button" data-add-selected="1"' + disabled + '><i class="bi bi-plus-lg"></i> Add Section</button>',
                '</div>',
                '<div class="draft-quick-grid">' + quickButtons + '</div>',
                '</div>',
                '</div>'
            ].join('');

            mountQuills();
            sync();
        }

        function emptyState(disabled) {
            return [
                '<div class="draft-empty-state">',
                '<span><i class="bi bi-file-earmark-richtext"></i></span>',
                '<strong>Your draft body is empty</strong>',
                '<p>Add a rich paragraph, clause heading, list, checkbox, date line, or signature block to start shaping the contract.</p>',
                '<button type="button" data-quick-add="paragraph"' + disabled + '><i class="bi bi-pencil-square"></i> Add Rich Paragraph</button>',
                '</div>'
            ].join('');
        }

        function mountQuills() {
            if (!window.Quill) return;
            sections.forEach((section) => {
                if (section.type !== 'paragraph') return;
                const node = root.querySelector('[data-quill-id="' + section.id + '"]');
                if (!node) return;
                const quill = new window.Quill(node, {
                    theme: 'snow',
                    placeholder: 'Write the contract paragraph here...',
                    modules: {
                        toolbar: [
                            [{ header: [false, 2, 3] }],
                            ['bold', 'italic', 'underline'],
                            ['link'],
                            [{ list: 'ordered' }, { list: 'bullet' }],
                            ['clean']
                        ]
                    }
                });
                quill.clipboard.dangerouslyPasteHTML(section.content || '<p><br></p>');
                quill.enable(!readOnly);
                quill.on('text-change', () => {
                    section.content = quill.root.innerHTML;
                    sync();
                });
                quills.set(section.id, quill);
            });
        }

        function addSection(type) {
            const section = makeSection(type || selectedType);
            sections.push(section);
            render();
            setTimeout(() => root.querySelector('[data-section-scroll="' + section.id + '"]')?.scrollIntoView({ behavior: 'smooth', block: 'center' }), 50);
        }

        function sync() {
            textarea.value = compile();
        }

        function compile() {
            collectQuillContent();
            return sections.map((section, index) => sectionHtml(section, index)).filter(Boolean).join('\n');
        }

        function cleanRichHtml(value) {
            const template = document.createElement('template');
            template.innerHTML = value || '';
            template.content.querySelectorAll('script, style, iframe, object, embed').forEach((node) => node.remove());
            template.content.querySelectorAll('*').forEach((node) => {
                Array.from(node.attributes).forEach((attribute) => {
                    const name = attribute.name.toLowerCase();
                    const val = attribute.value.trim().toLowerCase();
                    if (name.startsWith('on') || (name === 'href' && val.startsWith('javascript:'))) {
                        node.removeAttribute(attribute.name);
                    }
                });
            });
            return template.innerHTML;
        }

        function richHtmlToParagraphs(value) {
            const template = document.createElement('template');
            template.innerHTML = cleanRichHtml(value || '');
            const output = [];

            function addParagraph(html) {
                const content = String(html || '').trim();
                if (content) output.push('<p>' + content + '</p>');
            }

            function walk(node, orderedIndex) {
                if (node.nodeType === Node.TEXT_NODE) {
                    const text = node.textContent.replace(/\s+/g, ' ').trim();
                    if (text) addParagraph(escapeHtml(text));
                    return orderedIndex;
                }

                if (node.nodeType !== Node.ELEMENT_NODE) return orderedIndex;

                const tag = node.tagName.toLowerCase();
                if (/^h[1-6]$/.test(tag)) {
                    addParagraph('<strong>' + escapeHtml(node.textContent.trim()) + '</strong>');
                    return orderedIndex;
                }
                if (tag === 'p') {
                    addParagraph(node.innerHTML || '<br>');
                    return orderedIndex;
                }
                if (tag === 'li') {
                    const prefix = orderedIndex ? 'No. ' + orderedIndex + ': ' : '- ';
                    addParagraph(escapeHtml(prefix + node.textContent.trim()));
                    return orderedIndex ? orderedIndex + 1 : orderedIndex;
                }
                if (tag === 'ol') {
                    Array.from(node.children).forEach((child, childIndex) => walk(child, childIndex + 1));
                    return orderedIndex;
                }
                if (tag === 'ul') {
                    Array.from(node.children).forEach((child) => walk(child, 0));
                    return orderedIndex;
                }
                if (node.children.length) {
                    Array.from(node.childNodes).forEach((child) => walk(child, orderedIndex));
                    return orderedIndex;
                }

                addParagraph(escapeHtml(node.textContent.trim()));
                return orderedIndex;
            }

            Array.from(template.content.childNodes).forEach((node) => walk(node, 0));
            return output.join('') || '<p><br></p>';
        }

        function sectionNumber(index, value) {
            const text = String(value || '').trim();
            return (index + 1) + '. ' + (text || 'Contract section');
        }

        function sectionHtml(section, index) {
            const label = escapeHtml(section.label || '');
            if (section.type === 'heading') {
                return '<div data-draft-section="heading"><p><strong>' + escapeHtml(sectionNumber(index, section.heading || 'Clause Heading')) + '</strong></p>' + (section.note ? '<p data-section-note>' + escapeHtml(section.note) + '</p>' : '') + '</div>';
            }
            if (section.type === 'paragraph') {
                return '<div data-draft-section="paragraph" data-label="' + label + '"><p><strong>' + escapeHtml(sectionNumber(index, section.label || 'Paragraph')) + '</strong></p><div data-section-content>' + richHtmlToParagraphs(section.content || '') + '</div></div>';
            }
            if (section.type === 'text') {
                const lines = String(section.content || '').split(/\n+/).map((line) => line.trim()).filter(Boolean).map((line) => '<p>' + escapeHtml(line) + '</p>').join('');
                return '<div data-draft-section="text" data-label="' + label + '"><p><strong>' + escapeHtml(sectionNumber(index, section.label || 'Text clause')) + '</strong></p><div data-section-content>' + (lines || '<p><br></p>') + '</div></div>';
            }
            if (section.type === 'list') {
                const style = section.listStyle || 'bullet';
                const items = (section.items || []).map((item, itemIndex) => {
                    const text = item.text || 'List item';
                    const prefix = style === 'numbered' ? 'No. ' + (itemIndex + 1) + ': ' : style === 'checklist' ? '[' + (item.checked ? 'x' : ' ') + '] ' : '- ';
                    return '<p data-list-item data-text="' + escapeHtml(text) + '" data-checked="' + (item.checked ? '1' : '0') + '">' + escapeHtml(prefix + text) + '</p>';
                }).join('');
                return '<div data-draft-section="list" data-label="' + label + '" data-list-style="' + escapeHtml(style) + '"><p><strong>' + escapeHtml(sectionNumber(index, section.label || 'List')) + '</strong></p>' + items + '</div>';
            }
            if (section.type === 'checkbox') {
                return '<div data-draft-section="checkbox" data-label="' + label + '" data-checked="' + (section.checked ? '1' : '0') + '"><p><strong>' + escapeHtml(sectionNumber(index, 'Acceptance')) + '</strong></p><p>[' + (section.checked ? 'x' : ' ') + '] ' + escapeHtml(section.label || 'Acceptance checkbox') + '</p></div>';
            }
            if (section.type === 'date') {
                return '<div data-draft-section="date" data-label="' + label + '" data-value="' + escapeHtml(section.value || '') + '"><p><strong>' + escapeHtml(sectionNumber(index, section.label || 'Date')) + ':</strong> ' + escapeHtml(section.value || '________________') + '</p></div>';
            }
            if (section.type === 'signature') {
                return '<div data-draft-section="signature" data-left-signer="' + escapeHtml(section.leftSigner || '') + '" data-right-signer="' + escapeHtml(section.rightSigner || '') + '"><p><strong>' + escapeHtml(sectionNumber(index, 'Signature block')) + '</strong></p>' + (section.note ? '<p data-section-note>' + escapeHtml(section.note) + '</p>' : '') + '<p>' + escapeHtml(section.leftSigner || 'Client Representative') + ': ____________________________</p><p>' + escapeHtml(section.rightSigner || 'ITEC Representative') + ': ____________________________</p></div>';
            }
            return '';
        }

        root.addEventListener('click', (event) => {
            if (readOnly) return;
            const quick = event.target.closest('[data-quick-add]');
            if (quick) return addSection(quick.dataset.quickAdd);
            if (event.target.closest('[data-add-selected]')) return addSection(selectedType);

            const action = event.target.closest('[data-section-action]');
            if (action) {
                collectQuillContent();
                const index = sections.findIndex((section) => section.id === action.dataset.sectionId);
                if (index < 0) return;
                if (action.dataset.sectionAction === 'up' && index > 0) {
                    sections.splice(index - 1, 0, sections.splice(index, 1)[0]);
                } else if (action.dataset.sectionAction === 'down' && index < sections.length - 1) {
                    sections.splice(index + 1, 0, sections.splice(index, 1)[0]);
                } else if (action.dataset.sectionAction === 'duplicate') {
                    const copy = JSON.parse(JSON.stringify(sections[index]));
                    copy.id = id('section');
                    if (Array.isArray(copy.items)) copy.items = copy.items.map((item) => ({ ...item, id: id('item') }));
                    sections.splice(index + 1, 0, copy);
                } else if (action.dataset.sectionAction === 'delete') {
                    sections.splice(index, 1);
                }
                render();
            }

            const listAction = event.target.closest('[data-list-action]');
            if (listAction) {
                const section = sectionById(listAction.dataset.sectionId);
                if (!section) return;
                if (listAction.dataset.listAction === 'add') {
                    section.items.push({ id: id('item'), text: '', checked: false });
                } else {
                    section.items = section.items.filter((item) => item.id !== listAction.dataset.itemId);
                    if (!section.items.length) section.items.push({ id: id('item'), text: '', checked: false });
                }
                render();
            }
        });

        root.addEventListener('input', (event) => {
            if (readOnly) return;
            const field = event.target.closest('[data-section-field]');
            if (field) {
                const section = sectionById(field.dataset.sectionId);
                if (section) {
                    section[field.dataset.sectionField] = field.type === 'checkbox' ? field.checked : field.value;
                    sync();
                }
            }

            const listField = event.target.closest('[data-list-field]');
            if (listField) {
                const section = sectionById(listField.dataset.sectionId);
                const item = section ? itemById(section, listField.dataset.itemId) : null;
                if (item) {
                    item.text = listField.value;
                    sync();
                }
            }
        });

        root.addEventListener('change', (event) => {
            if (event.target.matches('[data-section-type-select]')) {
                selectedType = event.target.value;
                return;
            }
            if (readOnly) return;
            const field = event.target.closest('[data-section-field]');
            if (field) {
                const section = sectionById(field.dataset.sectionId);
                if (section) {
                    section[field.dataset.sectionField] = field.type === 'checkbox' ? field.checked : field.value;
                    if (field.dataset.sectionField === 'listStyle') render();
                    sync();
                }
            }
            const checked = event.target.closest('[data-list-checked]');
            if (checked) {
                const section = sectionById(checked.dataset.sectionId);
                const item = section ? itemById(section, checked.dataset.itemId) : null;
                if (item) {
                    item.checked = checked.checked;
                    sync();
                }
            }
        });

        render();

        return {
            getContent: compile,
            setReadOnly(value) {
                readOnly = Boolean(value);
                quills.forEach((quill) => quill.enable(!readOnly));
                render();
            }
        };
    }

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
            if (versionPreview) {
                versionPreview.innerHTML = '<p class="muted">Save the contract to create a generated document preview.</p>';
            }
            return;
        }

        if (!selectedVersionNo) selectedVersionNo = items[0].version_no;

        versionsList.innerHTML = items.map((item) => {
            const author = item.saved_by_name || (item.saved_by ? 'User #' + item.saved_by : 'Unknown user');
            const downloadUrl = versionUrl(config.downloadUrlTemplate, item.version_no);
            const selected = String(item.version_no) === String(selectedVersionNo) ? ' selected' : '';

            return [
                '<article class="version-item' + selected + '" data-version-card="' + escapeHtml(item.version_no) + '">',
                '<div class="version-card-head">',
                '<span class="version-badge">V' + escapeHtml(item.version_no) + '</span>',
                '<div><strong>Version ' + escapeHtml(item.version_no) + '</strong>',
                '<small>' + escapeHtml(formatSavedAt(item.saved_at)) + ' by ' + escapeHtml(author) + '</small></div>',
                '</div>',
                '<div class="version-actions">',
                '<button class="preview-version" type="button" data-preview-version="' + escapeHtml(item.version_no) + '">Preview</button>',
                '<button type="button" data-restore-version="' + escapeHtml(item.version_no) + '">Restore</button>',
                '<a href="' + escapeHtml(downloadUrl) + '">DOCX</a>',
                '</div>',
                '</article>'
            ].join('');
        }).join('');

        renderRestoreLocks();
        loadVersionPreview(selectedVersionNo);
    }

    function renderVersionPreviewLoading(versionNo) {
        if (!versionPreview) return;
        versionPreview.innerHTML = [
            '<div class="version-preview-loading">',
            '<span class="spinner"></span>',
            '<strong>Loading version ' + escapeHtml(versionNo) + ' preview...</strong>',
            '</div>'
        ].join('');
    }

    function renderVersionPreviewError(messageText) {
        if (!versionPreview) return;
        versionPreview.innerHTML = [
            '<div class="version-preview-error">',
            '<strong>Preview unavailable</strong>',
            '<span>' + escapeHtml(messageText || 'The saved DOCX could not be previewed.') + '</span>',
            '</div>'
        ].join('');
    }

    function renderVersionPreview(documentData, versionData) {
        if (!versionPreview) return;
        const doc = documentData || {};
        const version = versionData || {};
        const body = Array.isArray(doc.body) ? doc.body : [];
        const bodyHtml = body.map((block) => {
            const text = escapeHtml(block.text || '');
            if ((block.type || '') === 'heading') return '<h4>' + text + '</h4>';
            if ((block.type || '') === 'list') return '<p class="version-list-line">' + text + '</p>';
            if ((block.type || '') === 'muted') return '<p class="version-muted-line">' + text + '</p>';
            return '<p>' + text + '</p>';
        }).join('');

        versionPreview.innerHTML = [
            '<article class="generated-version-page" aria-label="Read-only generated contract preview">',
            '<header class="generated-version-header">',
            doc.logo_url ? '<img src="' + escapeHtml(doc.logo_url) + '" alt="ITEC logo">' : '<strong>' + escapeHtml(doc.company_name || 'ITEC Solutions') + '</strong>',
            '<span>' + escapeHtml(doc.tagline || 'BE SMART, CHOOSE SMART') + '</span>',
            '</header>',
            '<div class="generated-version-rule"></div>',
            '<h3>' + escapeHtml(doc.title || 'Contract') + '</h3>',
            '<dl class="generated-version-meta">',
            '<div><dt>Contract Ref</dt><dd>' + escapeHtml(doc.contract_ref || '') + '</dd></div>',
            '<div><dt>Client</dt><dd>' + escapeHtml(doc.client_name || '') + '</dd></div>',
            '<div><dt>Date</dt><dd>' + escapeHtml(doc.document_date || '') + '</dd></div>',
            '<div><dt>Email</dt><dd>' + escapeHtml(doc.client_email || '') + '</dd></div>',
            '</dl>',
            '<section class="generated-version-body">',
            '<h4>Agreement Details</h4>',
            bodyHtml,
            '</section>',
            '<section class="generated-version-signatures">',
            '<h4>Signatures</h4>',
            '<div class="signature-lines">',
            '<div><span></span><strong>Client Signature</strong><small>' + escapeHtml(doc.signature?.client_name || doc.client_name || 'Client Representative') + '</small><small>Date: _______________</small></div>',
            '<div><span></span><strong>' + escapeHtml(doc.signature?.company_name || 'ITEC Solutions') + '</strong><small>' + escapeHtml(doc.signature?.company_signer || 'Authorized Signatory') + '</small><small>Date: _______________</small></div>',
            '</div>',
            '</section>',
            '<footer class="generated-version-footer">',
            '<strong>Read-only generated preview</strong>',
            '<span>Version ' + escapeHtml(version.version_no || selectedVersionNo || '') + ' saved ' + escapeHtml(formatSavedAt(version.saved_at)) + '</span>',
            '</footer>',
            '</article>'
        ].join('');
    }

    async function loadVersionPreview(versionNo) {
        if (!versionNo || !versionPreview || !versionPreviewUrl(versionNo)) return;
        selectedVersionNo = versionNo;
        versionsList?.querySelectorAll('[data-version-card]').forEach((card) => {
            card.classList.toggle('selected', String(card.dataset.versionCard) === String(versionNo));
        });
        renderVersionPreviewLoading(versionNo);

        try {
            const response = await fetch(versionPreviewUrl(versionNo), { headers: { Accept: 'application/json' } });
            const result = await responseJson(response);
            if (!response.ok || result.success === false) throw new Error(result.message || 'Could not load version preview');
            renderVersionPreview(result.document, result.version);
        } catch (error) {
            renderVersionPreviewError(error.message || 'Could not load version preview.');
        }
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

        renderSendClientState();
        if (openSigningChoice) openSigningChoice.disabled = !canSign;
        if (companySigningAction) {
            companySigningAction.classList.toggle('disabled', !canSeal);
            companySigningAction.setAttribute('aria-disabled', canSeal ? 'false' : 'true');
        }

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
                ? 'Signature and seal actions activate after signing begins.'
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
                        ? 'Client signature was recorded. Company signing is ready.'
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
            distributionMessage.textContent = 'Distribution unlocks after the company completes signing.';
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
                window.location.href = config.editUrlTemplate.replace('__ID__', encodeURIComponent(result.contract_id)) + '#signing';
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

    async function saveDraftBeforeSigning() {
        if (!isDraftState(currentState) || config.isNew || !config.saveUrl) return;

        setSubmitSigningLoading(true, 'Saving latest draft...');

        const body = new FormData();
        body.append('content', getEditorData());

        const response = await fetch(config.saveUrl, {
            method: 'POST',
            body,
            headers: { Accept: 'application/json' }
        });
        const result = await responseJson(response);

        if (!response.ok || result.success === false) {
            throw new Error(result.message || result.error || 'Could not save the latest draft before sending.');
        }

        await loadVersions();
    }

    async function submitDraftForSigning() {
        if (!config.submitUrl || !isDraftState(currentState)) return;
        const parsed = parseRecipientInput(clientEmails?.value || '');
        const emails = parsed.valid.join(', ');

        if (!parsed.valid.length) {
            setMessage('Add at least one client recipient email before sending.', 'error');
            renderSendClientState();
            clientEmails?.focus();
            activatePanel('signing');
            return;
        }

        if (parsed.invalid.length) {
            const detail = 'Fix invalid email: ' + parsed.invalid.join(', ');
            setMessage(detail, 'error');
            showSendResult('error', 'Recipient email needs attention', detail);
            renderSendClientState();
            clientEmails?.focus();
            activatePanel('signing');
            return;
        }

        clearSendResult();
        setSubmitSigningLoading(true, 'Preparing email...');
        setMessage('Saving latest draft before emailing the client...', '');

        try {
            await saveDraftBeforeSigning();
            setSubmitSigningLoading(true, 'Sending email...');
            setMessage('Emailing the secure signing link to the client...', '');

            const response = await fetch(config.submitUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify({ client_emails: emails })
            });
            const result = await responseJson(response);
            if (!response.ok || result.success === false) throw new Error(result.message || result.error || 'Could not submit contract');
            submittedRecipients = Array.isArray(result.recipients) && result.recipients.length ? result.recipients : parsed.valid;
            applySigningState(result.new_state || 'AWAITING_CLIENT');
            const count = submittedRecipients.length || 1;
            showSendResult('success', 'Email sent', 'The secure signing link was emailed to ' + count + ' recipient' + (count === 1 ? '.' : 's.') + ' The contract is now locked for signing.');
            setMessage((result.message || 'Contract submitted for client signing.') + ' Email sent to ' + count + ' recipient' + (count === 1 ? '.' : 's.'), 'success');
            activatePanel('signing');
        } catch (error) {
            const detail = error.message || 'Could not submit contract for signing.';
            showSendResult('error', 'Could not send email', detail);
            setMessage(detail, 'error');
        } finally {
            setSubmitSigningLoading(false);
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
        if (!draftBuilderRoot) {
            setMessage('Draft builder could not start; basic editing is active.', 'error');
            applySigningState(currentState);
            return;
        }

        editorElement.classList.add('hidden');
        draftBodyEditor = createDraftBodyEditor(editorElement, draftBuilderRoot);
        applySigningState(currentState);
        setMessage('Draft builder editor ready', 'success');
    }

    function resizeEditor() {
        return;
    }

    if (clientEmails && !clientEmails.value.trim() && config.clientEmail) {
        clientEmails.value = config.clientEmail;
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
    clientEmails?.addEventListener('input', () => {
        clearSendResult();
        renderSendClientState();
    });
    closeSigningModal?.addEventListener('click', () => setModalOpen(false));
    [signatureAction, sealAction, companySigningAction, finalPdfPreview].forEach((link) => {
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
        const previewButton = event.target.closest('[data-preview-version]');
        if (previewButton) {
            loadVersionPreview(previewButton.dataset.previewVersion);
            return;
        }

        const button = event.target.closest('[data-restore-version]');
        if (button) restoreVersion(button.dataset.restoreVersion);
    });
    setInterval(pollStatus, 10000);
})();
