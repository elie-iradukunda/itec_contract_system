<?php
require_once dirname(__DIR__) . '/components/ui.php';

$title = 'Create New Contract';
$activeNav = 'contracts';
$headerMeta = 'contract drafting';
$pageStyles = [BASE_URL . '/public/assets/css/contract-editor.css'];
$pageScripts = [BASE_URL . '/public/assets/js/contract-create.js'];
$pageHeading = 'Create Contract Draft';
$pageEyebrow = 'document generator';
$pageLead = 'Capture only the contract-specific inputs. The generator adds the logo, company identity block, contract reference, and signature structure automatically.';
$pageActions = [
    '<a class="button ghost" href="' . BASE_URL . '/contracts">Back to contracts</a>',
];

ob_start();
?>
<script>
    window.contractCreateConfig = <?= json_encode([
        'createUrl' => BASE_URL . '/api/contracts',
        'editUrlTemplate' => BASE_URL . '/contracts/__ID__/editor',
    ], JSON_UNESCAPED_SLASHES) ?>;
</script>

<section class="surface generator-stage">
    <div class="generator-stage-copy">
        <p>generator-first drafting</p>
        <h2>Start from the contract data, not the final layout</h2>
        <span>The document generator already owns the official format. Each field below shows where its content appears in the generated contract so the first draft is easy to fill correctly.</span>
    </div>
    <div class="generator-pills">
        <span><?= ui_icon('image') ?> Company logo</span>
        <span><?= ui_icon('building') ?> Company details</span>
        <span><?= ui_icon('123') ?> Contract reference</span>
        <span><?= ui_icon('pen') ?> Signature block</span>
    </div>
</section>

<div class="content-split create-builder-layout">
    <form id="contractGeneratorForm" class="surface form-surface generator-form">
        <section class="generator-section">
            <div class="section-head compact">
                <div>
                    <p>core details</p>
                    <h2>Agreement setup</h2>
                </div>
            </div>
            <div class="generator-grid">
                <label>
                    <span>Contract title</span>
                    <input id="contractTitle" name="title" type="text" value="New Contract" required>
                </label>
                <label>
                    <span>Document type</span>
                    <select id="contractType" name="document_type">
                        <option value="Service Agreement">Service Agreement</option>
                        <option value="Financing Contract">Financing Contract</option>
                        <option value="Legal Agreement">Legal Agreement</option>
                    </select>
                </label>
                <label class="field-span">
                    <span>Agreement details</span>
                    <small class="generator-field-note">Appears at the top of the generated <strong>Agreement Details</strong> section.</small>
                    <textarea id="contractDescription" name="description" rows="3" placeholder="Describe the agreement, parties, services, payment terms, timeline, and signature obligations."></textarea>
                </label>
                <label>
                    <span>Effective date</span>
                    <small class="generator-field-note">Added inside the generated <strong>Timeline</strong> section.</small>
                    <input id="effectiveDate" name="effective_date" type="date">
                </label>
                <label>
                    <span>Start date</span>
                    <small class="generator-field-note">Added inside the generated <strong>Timeline</strong> section.</small>
                    <input id="startDate" name="start_date" type="date">
                </label>
                <label>
                    <span>Duration</span>
                    <small class="generator-field-note">Added inside the generated <strong>Timeline</strong> section.</small>
                    <input id="duration" name="duration" type="text" placeholder="12 months">
                </label>
                <label>
                    <span>Governing law</span>
                    <small class="generator-field-note">Added in metadata and repeated under <strong>Additional Clauses</strong>.</small>
                    <input id="governingLaw" name="governing_law" type="text" value="Rwanda">
                </label>
            </div>
        </section>

        <section class="generator-section">
            <div class="section-head compact">
                <div>
                    <p>body inputs</p>
                    <h2>Terms to generate</h2>
                </div>
            </div>
            <div class="generator-grid">
                <label class="field-span">
                    <span>Scope of work</span>
                    <small class="generator-field-note">Fills the generated <strong>Scope of Work</strong> section.</small>
                    <textarea id="services" name="services" rows="5" placeholder="Add the services or obligations covered by this contract."></textarea>
                </label>
                <label>
                    <span>Contract amount</span>
                    <small class="generator-field-note">Inserted into the generated <strong>Payment Terms</strong> section.</small>
                    <input id="amount" name="amount" type="text" placeholder="USD 2,000">
                </label>
                <label class="field-span">
                    <span>Payment terms</span>
                    <small class="generator-field-note">Fills the generated <strong>Payment Terms</strong> section.</small>
                    <textarea id="paymentTerms" name="payment_terms" rows="4" placeholder="Add payment amount, schedule, and conditions."></textarea>
                </label>
                <label class="field-span">
                    <span>Termination terms</span>
                    <small class="generator-field-note">Fills the generated <strong>Termination</strong> section.</small>
                    <textarea id="terminationTerms" name="termination" rows="4" placeholder="Explain how this contract may be terminated or renewed."></textarea>
                </label>
                <label class="field-span">
                    <span>Additional clauses</span>
                    <small class="generator-field-note">Fills the generated <strong>Additional Clauses</strong> section.</small>
                    <textarea id="additionalClauses" name="additional_clauses" rows="5" placeholder="Add other clauses covered by the generator such as confidentiality, liability, support, acceptance, or other obligations."></textarea>
                </label>
            </div>
        </section>

        <div class="generator-submit-bar">
            <div class="generator-status">
                <strong>Next step</strong>
                <span>The first draft is generated as a formatted contract document, then opened in the draft workspace for review and sending.</span>
            </div>
            <button id="createDraftButton" class="button" type="submit">Generate Contract Draft</button>
        </div>
        <p id="generatorMessage" class="generator-message">Ready to generate the first draft.</p>
    </form>

    <aside class="page-stack">
        <div class="surface surface-pad generator-summary">
            <div class="section-head compact no-border">
                <div>
                    <p>automatic output</p>
                    <h2>Added by generator</h2>
                </div>
            </div>
            <ul class="generator-list">
                <li>Company logo and branded header</li>
                <li>ITEC company address, TIN, phone, and footer</li>
                <li>Contract reference number and document type block</li>
                <li>Client placeholder until recipients are chosen later</li>
                <li>Signature lines for client and company approval</li>
            </ul>
        </div>

        <div class="surface surface-pad generator-summary">
            <div class="section-head compact no-border">
                <div>
                    <p>not collected here</p>
                    <h2>Handled later</h2>
                </div>
            </div>
            <ul class="generator-list">
                <li>Client recipient emails</li>
                <li>Digital or hard-copy signing choice</li>
                <li>Company signature and seal</li>
                <li>Final distribution link</li>
            </ul>
        </div>

        <div class="surface surface-pad generator-summary">
            <div class="section-head compact no-border">
                <div>
                    <p>generated sections</p>
                    <h2>Draft structure</h2>
                </div>
            </div>
            <div class="generator-preview-list">
                <span>Agreement Details</span>
                <span>Scope of Work</span>
                <span>Payment Terms</span>
                <span>Timeline</span>
                <span>Termination</span>
                <span>Additional Clauses</span>
            </div>
        </div>
    </aside>
</div>
<?php

$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/app.php';
