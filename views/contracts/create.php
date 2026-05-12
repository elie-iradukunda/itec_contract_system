<?php
require_once dirname(__DIR__) . '/components/ui.php';

$title = 'Create New Contract';
$activeNav = 'contracts';
$headerMeta = 'contract drafting';
$pageStyles = [
    BASE_URL . '/public/assets/css/output.css',
    'https://cdn.quilljs.com/1.3.6/quill.snow.css'
];
$pageScripts = ['https://cdn.quilljs.com/1.3.6/quill.js'];
$pageHeading = 'Create Contract Draft';
$pageEyebrow = 'Dynamic Document Builder';
$pageLead = 'Create a clear first draft with contract details, rich paragraphs, lists, acceptance checks, and signing blocks before the document moves into review.';
$pageActions = [
    '<a class="inline-flex min-h-10 items-center gap-2 rounded-md bg-slate-200 px-4 text-sm font-semibold text-slate-800 transition hover:bg-slate-300" href="' . BASE_URL . '/contracts">' . ui_icon('arrow-left') . ' Back to Contracts</a>',
];

ob_start();
?>

<style>
    [x-cloak] { display: none !important; }

    .draft-builder-shell .ql-toolbar.ql-snow {
        border-color: #cbd5e1;
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
        background: #f8fafc;
    }

    .draft-builder-shell .ql-container.ql-snow {
        min-height: 160px;
        border-color: #cbd5e1;
        border-bottom-left-radius: 8px;
        border-bottom-right-radius: 8px;
        background: #fff;
        font-family: inherit;
    }

    .draft-builder-shell .ql-editor {
        min-height: 160px;
        color: #1e293b;
        font-size: 15px;
        line-height: 1.65;
    }

    .draft-builder-shell .ql-editor.ql-blank::before {
        color: #94a3b8;
        font-style: normal;
    }

    .draft-preview-page h2,
    .draft-preview-page h3 {
        margin: 0 0 10px;
        color: #0f2a45;
        font-weight: 800;
    }

    .draft-preview-page h2 { font-size: 20px; }
    .draft-preview-page h3 { font-size: 16px; }

    .draft-preview-page p,
    .draft-preview-page li {
        color: #334155;
        font-size: 13px;
        line-height: 1.65;
    }

    .draft-preview-page ul,
    .draft-preview-page ol {
        margin: 8px 0 14px;
        padding-left: 18px;
    }
</style>

<script>
    window.contractDraftConfig = {
        baseUrl: '<?= BASE_URL ?>',
        storeUrl: '<?= BASE_URL ?>/api/contracts/'
    };
</script>

<div
    x-data="contractDraftBuilder(window.contractDraftConfig)"
    x-init="boot()"
    x-cloak
    class="draft-builder-shell min-h-screen bg-slate-100 px-4 py-6"
>
    <div class="mx-auto grid max-w-7xl grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_390px]">
        <section class="space-y-6">
            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-slate-50 px-5 py-4">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Draft setup</p>
                            <h2 class="mt-1 text-xl font-bold text-slate-900">Start with a reusable contract draft</h2>
                            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                                Build the contract body first. Recipients can be chosen later when the draft is ready to send for review or signing.
                            </p>
                        </div>
                        <div class="grid grid-cols-3 gap-2 text-center text-xs text-slate-500">
                            <div class="rounded-md border border-slate-200 bg-white px-3 py-2">
                                <strong class="block text-lg text-blue-700" x-text="sections.length"></strong>
                                Sections
                            </div>
                            <div class="rounded-md border border-slate-200 bg-white px-3 py-2">
                                <strong class="block text-lg text-teal-700" x-text="paragraphCount()"></strong>
                                Paragraphs
                            </div>
                            <div class="rounded-md border border-slate-200 bg-white px-3 py-2">
                                <strong class="block text-lg text-amber-700" x-text="completionScore() + '%'"></strong>
                                Ready
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 p-5">
                    <label class="grid gap-2">
                        <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Contract title</span>
                        <input
                            x-model.trim="draft.title"
                            type="text"
                            class="min-h-12 rounded-md border-slate-300 px-4 text-base font-semibold text-slate-900 shadow-sm focus:border-blue-600 focus:ring-blue-600"
                            placeholder="Example: Software Development Agreement"
                        >
                    </label>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-white px-5 py-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Created draft parts</p>
                        <h2 class="mt-1 text-xl font-bold text-slate-900">Contract body sections</h2>
                        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                            Edit the current draft sections here. The document builder appears below the created content so the next part is added in the right place.
                        </p>
                    </div>
                </div>

                <div class="space-y-4 p-5">
                    <template x-if="sections.length === 0">
                        <div class="grid min-h-72 place-items-center rounded-lg border border-dashed border-slate-300 bg-slate-50 px-6 text-center">
                            <div class="max-w-lg">
                                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-md bg-white text-blue-700 shadow-sm">
                                    <i class="bi bi-file-earmark-richtext text-xl" aria-hidden="true"></i>
                                </span>
                                <h3 class="mt-4 text-lg font-bold text-slate-900">Your draft body is empty</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-600">
                                    Choose a section type such as rich paragraph, list, text clause, checkbox, date line, or signature block to start shaping the contract.
                                </p>
                                <button
                                    type="button"
                                    @click="addSection('paragraph')"
                                    class="mt-5 inline-flex min-h-10 items-center gap-2 rounded-md bg-blue-700 px-4 text-sm font-bold text-white transition hover:bg-blue-800"
                                >
                                    <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                    Add Rich Paragraph
                                </button>
                            </div>
                        </div>
                    </template>

                    <template x-for="(section, index) in sections" :key="section.id">
                        <article
                            class="rounded-lg border border-slate-200 bg-white shadow-sm transition hover:border-slate-300"
                            :data-section-scroll="section.id"
                        >
                            <header class="flex flex-col gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3 md:flex-row md:items-center md:justify-between">
                                <div class="flex min-w-0 items-center gap-3">
                                    <span
                                        class="flex h-9 w-9 flex-none items-center justify-center rounded-md text-white"
                                        :class="sectionAccent(section.type).bg"
                                    >
                                        <i class="bi" :class="sectionAccent(section.type).icon" aria-hidden="true"></i>
                                    </span>
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500" x-text="'Section ' + (index + 1)"></p>
                                        <h3 class="truncate text-base font-bold text-slate-900" x-text="sectionAccent(section.type).label"></h3>
                                    </div>
                                </div>

                                <div class="flex items-center gap-1">
                                    <button
                                        type="button"
                                        @click="moveSection(index, -1)"
                                        :disabled="index === 0"
                                        class="flex h-9 w-9 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-100 disabled:opacity-40"
                                        title="Move section up"
                                    >
                                        <i class="bi bi-arrow-up" aria-hidden="true"></i>
                                    </button>
                                    <button
                                        type="button"
                                        @click="moveSection(index, 1)"
                                        :disabled="index === sections.length - 1"
                                        class="flex h-9 w-9 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-100 disabled:opacity-40"
                                        title="Move section down"
                                    >
                                        <i class="bi bi-arrow-down" aria-hidden="true"></i>
                                    </button>
                                    <button
                                        type="button"
                                        @click="duplicateSection(section)"
                                        class="flex h-9 w-9 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-100"
                                        title="Duplicate section"
                                    >
                                        <i class="bi bi-copy" aria-hidden="true"></i>
                                    </button>
                                    <button
                                        type="button"
                                        @click="removeSection(section.id)"
                                        class="flex h-9 w-9 items-center justify-center rounded-md border border-rose-200 bg-rose-50 text-rose-700 transition hover:bg-rose-100"
                                        title="Delete section"
                                    >
                                        <i class="bi bi-trash3" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </header>

                            <div class="space-y-4 p-4">
                                <template x-if="section.type === 'heading'">
                                    <div class="grid gap-4">
                                        <label class="grid gap-2">
                                            <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Clause heading</span>
                                            <input
                                                x-model.trim="section.heading"
                                                type="text"
                                                class="rounded-md border-slate-300 px-4 text-lg font-bold text-slate-900 shadow-sm focus:border-blue-600 focus:ring-blue-600"
                                                placeholder="Example: Scope of Services"
                                            >
                                        </label>
                                        <label class="grid gap-2">
                                            <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Optional note</span>
                                            <input
                                                x-model.trim="section.note"
                                                type="text"
                                                class="rounded-md border-slate-300 px-4 shadow-sm focus:border-blue-600 focus:ring-blue-600"
                                                placeholder="Short supporting line for this heading"
                                            >
                                        </label>
                                    </div>
                                </template>

                                <template x-if="section.type === 'paragraph'">
                                    <div class="grid gap-4" x-init="$nextTick(() => mountQuill(section))">
                                        <label class="grid gap-2">
                                            <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Paragraph label</span>
                                            <input
                                                x-model.trim="section.label"
                                                type="text"
                                                class="rounded-md border-slate-300 px-4 shadow-sm focus:border-blue-600 focus:ring-blue-600"
                                                placeholder="Example: 1.1 Background"
                                            >
                                        </label>
                                        <div>
                                            <div :data-quill-id="section.id"></div>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="section.type === 'text'">
                                    <div class="grid gap-4">
                                        <label class="grid gap-2">
                                            <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Text clause title</span>
                                            <input
                                                x-model.trim="section.label"
                                                type="text"
                                                class="rounded-md border-slate-300 px-4 shadow-sm focus:border-blue-600 focus:ring-blue-600"
                                                placeholder="Example: Definitions"
                                            >
                                        </label>
                                        <label class="grid gap-2">
                                            <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Clause text</span>
                                            <textarea
                                                x-model.trim="section.content"
                                                class="min-h-32 rounded-md border-slate-300 px-4 py-3 text-sm leading-6 shadow-sm focus:border-blue-600 focus:ring-blue-600"
                                                placeholder="Write a plain text clause or definition."
                                            ></textarea>
                                        </label>
                                    </div>
                                </template>

                                <template x-if="section.type === 'list'">
                                    <div class="grid gap-4">
                                        <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_190px]">
                                            <label class="grid gap-2">
                                                <span class="text-xs font-bold uppercase tracking-wide text-slate-500">List heading</span>
                                                <input
                                                    x-model.trim="section.label"
                                                    type="text"
                                                    class="rounded-md border-slate-300 px-4 shadow-sm focus:border-blue-600 focus:ring-blue-600"
                                                    placeholder="Example: Deliverables"
                                                >
                                            </label>
                                            <label class="grid gap-2">
                                                <span class="text-xs font-bold uppercase tracking-wide text-slate-500">List type</span>
                                                <select
                                                    x-model="section.listStyle"
                                                    class="rounded-md border-slate-300 bg-white px-3 shadow-sm focus:border-blue-600 focus:ring-blue-600"
                                                >
                                                    <option value="bullet">Bullet list</option>
                                                    <option value="numbered">Numbered list</option>
                                                    <option value="checklist">Checklist</option>
                                                </select>
                                            </label>
                                        </div>

                                        <div class="space-y-2">
                                            <template x-for="(item, itemIndex) in section.items" :key="item.id">
                                                <div class="grid grid-cols-[32px_minmax(0,1fr)_36px] items-center gap-2">
                                                    <div class="flex h-9 items-center justify-center rounded-md bg-slate-100 text-xs font-bold text-slate-600">
                                                        <span x-show="section.listStyle === 'bullet'">&bull;</span>
                                                        <span x-show="section.listStyle === 'numbered'" x-text="itemIndex + 1"></span>
                                                        <input
                                                            x-show="section.listStyle === 'checklist'"
                                                            x-model="item.checked"
                                                            type="checkbox"
                                                            class="h-4 min-h-4 w-4 rounded border-slate-300 text-blue-700 focus:ring-blue-600"
                                                        >
                                                    </div>
                                                    <input
                                                        x-model.trim="item.text"
                                                        type="text"
                                                        class="rounded-md border-slate-300 px-3 shadow-sm focus:border-blue-600 focus:ring-blue-600"
                                                        placeholder="List item"
                                                    >
                                                    <button
                                                        type="button"
                                                        @click="removeListItem(section, item.id)"
                                                        class="flex h-9 w-9 items-center justify-center rounded-md text-rose-700 transition hover:bg-rose-50"
                                                        title="Remove list item"
                                                    >
                                                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                                                    </button>
                                                </div>
                                            </template>
                                        </div>

                                        <button
                                            type="button"
                                            @click="addListItem(section)"
                                            class="inline-flex min-h-9 w-fit items-center gap-2 rounded-md border border-slate-200 bg-white px-3 text-sm font-bold text-blue-700 transition hover:bg-blue-50"
                                        >
                                            <i class="bi bi-plus-circle" aria-hidden="true"></i>
                                            Add List Item
                                        </button>
                                    </div>
                                </template>

                                <template x-if="section.type === 'checkbox'">
                                    <div class="grid gap-4">
                                        <label class="grid gap-2">
                                            <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Acceptance statement</span>
                                            <input
                                                x-model.trim="section.label"
                                                type="text"
                                                class="rounded-md border-slate-300 px-4 shadow-sm focus:border-blue-600 focus:ring-blue-600"
                                                placeholder="Example: The client accepts the terms in this agreement."
                                            >
                                        </label>
                                        <label class="flex items-start gap-3 rounded-md border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700">
                                            <input
                                                x-model="section.checked"
                                                type="checkbox"
                                                class="mt-1 h-4 min-h-4 w-4 rounded border-slate-300 text-blue-700 focus:ring-blue-600"
                                            >
                                            <span x-text="section.label || 'Acceptance checkbox'"></span>
                                        </label>
                                    </div>
                                </template>

                                <template x-if="section.type === 'date'">
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <label class="grid gap-2">
                                            <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Date label</span>
                                            <input
                                                x-model.trim="section.label"
                                                type="text"
                                                class="rounded-md border-slate-300 px-4 shadow-sm focus:border-blue-600 focus:ring-blue-600"
                                                placeholder="Example: Effective date"
                                            >
                                        </label>
                                        <label class="grid gap-2">
                                            <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Date value</span>
                                            <input
                                                x-model="section.value"
                                                type="date"
                                                class="rounded-md border-slate-300 px-4 shadow-sm focus:border-blue-600 focus:ring-blue-600"
                                            >
                                        </label>
                                    </div>
                                </template>

                                <template x-if="section.type === 'signature'">
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <label class="grid gap-2">
                                            <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Left signer</span>
                                            <input
                                                x-model.trim="section.leftSigner"
                                                type="text"
                                                class="rounded-md border-slate-300 px-4 shadow-sm focus:border-blue-600 focus:ring-blue-600"
                                                placeholder="Client representative"
                                            >
                                        </label>
                                        <label class="grid gap-2">
                                            <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Right signer</span>
                                            <input
                                                x-model.trim="section.rightSigner"
                                                type="text"
                                                class="rounded-md border-slate-300 px-4 shadow-sm focus:border-blue-600 focus:ring-blue-600"
                                                placeholder="ITEC representative"
                                            >
                                        </label>
                                        <label class="grid gap-2 md:col-span-2">
                                            <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Signing note</span>
                                            <input
                                                x-model.trim="section.note"
                                                type="text"
                                                class="rounded-md border-slate-300 px-4 shadow-sm focus:border-blue-600 focus:ring-blue-600"
                                                placeholder="Optional note shown above the signature lines"
                                            >
                                        </label>
                                    </div>
                                </template>
                            </div>
                        </article>
                    </template>
                </div>

                <div class="border-t border-slate-200 bg-slate-50 px-5 py-4">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Document builder</p>
                            <h2 class="mt-1 text-xl font-bold text-slate-900">Add the next draft section</h2>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                Select the next block you need, then continue building the draft below the existing content.
                            </p>
                        </div>

                        <div class="grid gap-2 sm:grid-cols-[minmax(230px,1fr)_auto]">
                            <label class="sr-only" for="sectionType">Section type</label>
                            <select
                                id="sectionType"
                                x-model="selectedType"
                                class="rounded-md border-slate-300 bg-white px-3 text-sm font-semibold text-slate-700 shadow-sm focus:border-blue-600 focus:ring-blue-600"
                            >
                                <template x-for="option in sectionTypes" :key="option.type">
                                    <option :value="option.type" x-text="option.label"></option>
                                </template>
                            </select>
                            <button
                                type="button"
                                @click="addSelectedSection()"
                                class="inline-flex min-h-10 items-center justify-center gap-2 rounded-md bg-blue-700 px-4 text-sm font-bold text-white shadow-sm transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                                Add Section
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-2 sm:grid-cols-3 lg:grid-cols-7">
                        <template x-for="option in sectionTypes" :key="option.type">
                            <button
                                type="button"
                                @click="addSection(option.type)"
                                class="inline-flex min-h-10 items-center justify-center gap-2 rounded-md border border-slate-200 bg-white px-3 text-xs font-bold text-slate-700 transition hover:border-blue-300 hover:text-blue-700"
                            >
                                <i class="bi" :class="option.icon" aria-hidden="true"></i>
                                <span x-text="option.shortLabel"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <div class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 md:flex-row md:items-center md:justify-between">
                    <p class="text-sm text-slate-600" x-text="footerHint()"></p>
                    <div class="flex flex-wrap gap-2 md:justify-end">
                        <a
                            href="<?= BASE_URL ?>/contracts"
                            class="inline-flex min-h-10 items-center justify-center gap-2 rounded-md border border-slate-300 bg-white px-4 text-sm font-bold text-slate-700 transition hover:bg-slate-100"
                        >
                            <i class="bi bi-x-lg" aria-hidden="true"></i>
                            Cancel
                        </a>
                        <button
                            type="button"
                            @click="resetDraft()"
                            class="inline-flex min-h-10 items-center justify-center gap-2 rounded-md border border-slate-300 bg-white px-4 text-sm font-bold text-slate-700 transition hover:bg-slate-100"
                        >
                            <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                            Reset
                        </button>
                        <button
                            type="button"
                            @click="submitDraft()"
                            :disabled="isSaving"
                            class="inline-flex min-h-10 items-center justify-center gap-2 rounded-md bg-teal-700 px-5 text-sm font-bold text-white shadow-sm transition hover:bg-teal-800 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <i class="bi" :class="isSaving ? 'bi-hourglass-split' : 'bi-file-earmark-check'" aria-hidden="true"></i>
                            <span x-text="isSaving ? 'Creating Draft...' : 'Generate Draft'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <aside class="space-y-6 xl:sticky xl:top-6 xl:self-start">
            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-slate-50 px-5 py-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Live preview</p>
                    <h2 class="mt-1 text-xl font-bold text-slate-900">Document shape</h2>
                </div>

                <div class="p-5">
                    <div class="draft-preview-page min-h-[520px] rounded-md border border-slate-200 bg-white px-6 py-7 shadow-sm">
                        <div class="border-b border-slate-200 pb-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Draft contract</p>
                            <h2 class="mt-2 break-words" x-text="draft.title || 'Untitled Contract'"></h2>
                        </div>

                        <div class="mt-5 space-y-4" x-html="previewHtml()"></div>

                        <template x-if="sections.length === 0">
                            <p class="mt-5 rounded-md bg-slate-50 p-3 text-sm text-slate-500">
                                Added sections will appear here as the draft grows.
                            </p>
                        </template>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-slate-50 px-5 py-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Draft outline</p>
                    <h2 class="mt-1 text-xl font-bold text-slate-900">Section order</h2>
                </div>
                <div class="max-h-80 overflow-y-auto p-5">
                    <template x-if="sections.length === 0">
                        <p class="text-sm leading-6 text-slate-500">No body sections yet.</p>
                    </template>
                    <div class="space-y-2">
                        <template x-for="(section, index) in sections" :key="section.id">
                            <button
                                type="button"
                                @click="scrollToSection(section.id)"
                                class="grid w-full grid-cols-[28px_minmax(0,1fr)] items-center gap-3 rounded-md border border-slate-200 bg-white px-3 py-2 text-left transition hover:border-blue-300 hover:bg-blue-50"
                            >
                                <span class="flex h-7 w-7 items-center justify-center rounded-md text-xs font-bold text-white" :class="sectionAccent(section.type).bg" x-text="index + 1"></span>
                                <span class="min-w-0">
                                    <strong class="block truncate text-sm text-slate-800" x-text="outlineTitle(section)"></strong>
                                    <span class="block truncate text-xs text-slate-500" x-text="sectionAccent(section.type).label"></span>
                                </span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <div
                x-show="notice.message"
                x-transition
                class="rounded-lg border p-4 shadow-sm"
                :class="notice.type === 'success' ? 'border-teal-200 bg-teal-50 text-teal-900' : 'border-amber-200 bg-amber-50 text-amber-900'"
            >
                <div class="flex gap-3">
                    <i class="bi mt-0.5" :class="notice.type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'" aria-hidden="true"></i>
                    <p class="text-sm font-semibold leading-6" x-text="notice.message"></p>
                </div>
            </div>
        </aside>
    </div>
</div>

<script>
function contractDraftBuilder(config) {
    return {
        draft: {
            title: 'New Contract'
        },
        selectedType: 'paragraph',
        sections: [],
        isSaving: false,
        notice: {
            type: '',
            message: ''
        },
        nextId: 1,
        quillInstances: {},
        sectionTypes: [
            { type: 'heading', label: 'Clause heading', shortLabel: 'Heading', icon: 'bi-type-h2', bg: 'bg-blue-700' },
            { type: 'paragraph', label: 'Rich paragraph', shortLabel: 'Paragraph', icon: 'bi-text-paragraph', bg: 'bg-teal-700' },
            { type: 'text', label: 'Plain text clause', shortLabel: 'Text', icon: 'bi-card-text', bg: 'bg-slate-700' },
            { type: 'list', label: 'List or checklist', shortLabel: 'List', icon: 'bi-list-check', bg: 'bg-amber-600' },
            { type: 'checkbox', label: 'Acceptance checkbox', shortLabel: 'Checkbox', icon: 'bi-check2-square', bg: 'bg-rose-700' },
            { type: 'date', label: 'Date line', shortLabel: 'Date', icon: 'bi-calendar3', bg: 'bg-indigo-700' },
            { type: 'signature', label: 'Signature block', shortLabel: 'Signature', icon: 'bi-pen', bg: 'bg-emerald-700' }
        ],

        boot() {
            this.setNotice('', '');
        },

        sectionAccent(type) {
            return this.sectionTypes.find((item) => item.type === type) || this.sectionTypes[0];
        },

        createId(prefix) {
            const id = prefix + '-' + this.nextId;
            this.nextId += 1;
            return id;
        },

        addSelectedSection() {
            this.addSection(this.selectedType);
        },

        addSection(type) {
            const section = this.makeSection(type);
            this.sections.push(section);
            this.setNotice('', '');

            this.$nextTick(() => {
                if (section.type === 'paragraph') {
                    this.mountQuill(section);
                }
                this.scrollToSection(section.id);
            });
        },

        makeSection(type) {
            const section = {
                id: this.createId('section'),
                type: type,
                label: '',
                content: '',
                note: ''
            };

            if (type === 'heading') {
                section.heading = 'New Clause Heading';
            }

            if (type === 'paragraph') {
                section.label = 'Draft paragraph';
            }

            if (type === 'text') {
                section.label = 'Text clause';
                section.content = '';
            }

            if (type === 'list') {
                section.label = 'Key terms';
                section.listStyle = 'bullet';
                section.items = [
                    { id: this.createId('item'), text: '', checked: false }
                ];
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
        },

        mountQuill(section) {
            if (!section || section.type !== 'paragraph' || this.quillInstances[section.id]) {
                return;
            }

            if (typeof Quill === 'undefined') {
                this.setNotice('warning', 'Quill editor could not load. Check your internet connection and refresh the page.');
                return;
            }

            const container = document.querySelector('[data-quill-id="' + section.id + '"]');
            if (!container) return;

            const quill = new Quill(container, {
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

            if (section.content) {
                quill.clipboard.dangerouslyPasteHTML(section.content);
            }

            quill.on('text-change', () => {
                section.content = quill.root.innerHTML;
            });

            this.quillInstances[section.id] = quill;
        },

        moveSection(index, direction) {
            const targetIndex = index + direction;
            if (targetIndex < 0 || targetIndex >= this.sections.length) return;

            const moved = this.sections.splice(index, 1)[0];
            this.sections.splice(targetIndex, 0, moved);
        },

        duplicateSection(section) {
            const copy = JSON.parse(JSON.stringify(section));
            copy.id = this.createId('section');

            if (Array.isArray(copy.items)) {
                copy.items = copy.items.map((item) => ({
                    id: this.createId('item'),
                    text: item.text,
                    checked: item.checked
                }));
            }

            const index = this.sections.findIndex((item) => item.id === section.id);
            this.sections.splice(index + 1, 0, copy);

            this.$nextTick(() => {
                if (copy.type === 'paragraph') {
                    this.mountQuill(copy);
                }
                this.scrollToSection(copy.id);
            });
        },

        removeSection(sectionId) {
            this.sections = this.sections.filter((section) => section.id !== sectionId);
            delete this.quillInstances[sectionId];
        },

        addListItem(section) {
            if (!Array.isArray(section.items)) {
                section.items = [];
            }

            section.items.push({
                id: this.createId('item'),
                text: '',
                checked: false
            });
        },

        removeListItem(section, itemId) {
            section.items = section.items.filter((item) => item.id !== itemId);

            if (section.items.length === 0) {
                this.addListItem(section);
            }
        },

        paragraphCount() {
            return this.sections.filter((section) => section.type === 'paragraph').length;
        },

        completionScore() {
            const checks = [
                this.draft.title.trim(),
                this.sections.length > 0
            ];

            const complete = checks.filter(Boolean).length;
            return Math.round((complete / checks.length) * 100);
        },

        footerHint() {
            if (this.sections.length === 0) {
                return 'Add at least one body section before generating the draft.';
            }

            return 'The draft will be generated from the title and ordered sections shown in the preview.';
        },

        outlineTitle(section) {
            if (section.type === 'heading') return section.heading || 'Heading';
            if (section.type === 'signature') return 'Signature block';
            if (section.type === 'date') return section.label || 'Date line';
            return section.label || this.sectionAccent(section.type).label;
        },

        scrollToSection(sectionId) {
            const target = document.querySelector('[data-section-scroll="' + sectionId + '"]');
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }

            const quillTarget = document.querySelector('[data-quill-id="' + sectionId + '"]');
            quillTarget?.closest('article')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        },

        resetDraft() {
            this.draft = {
                title: 'New Contract'
            };
            this.sections = [];
            this.quillInstances = {};
            this.setNotice('', '');
        },

        validateDraft() {
            if (!this.draft.title.trim()) {
                return 'Add a contract title before generating the draft.';
            }

            if (this.sections.length === 0) {
                return 'Add at least one body section before generating the draft.';
            }

            return '';
        },

        async submitDraft() {
            const error = this.validateDraft();
            if (error) {
                this.setNotice('warning', error);
                return;
            }

            this.isSaving = true;
            this.setNotice('', '');

            const payload = {
                title: this.draft.title.trim(),
                description: '',
                content: this.compiledContent(),
                created_by: 'staff-portal'
            };

            try {
                const response = await fetch(config.storeUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const result = await this.readJson(response);

                if (!response.ok || result.success === false) {
                    throw new Error(result.error || result.message || 'The draft could not be created.');
                }

                this.setNotice('success', 'Contract draft created. Opening the document editor...');
                const contractId = result.contract_id || result.contract?.id;

                if (contractId) {
                    window.location.href = config.baseUrl + '/contracts/' + encodeURIComponent(contractId) + '/editor#signing';
                }
            } catch (submitError) {
                this.setNotice('warning', submitError.message || 'The draft could not be created.');
            } finally {
                this.isSaving = false;
            }
        },

        async readJson(response) {
            const text = await response.text();
            if (!text) return {};

            try {
                return JSON.parse(text);
            } catch (error) {
                return {
                    success: response.ok,
                    message: text.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim()
                };
            }
        },

        compiledContent() {
            const parts = [];

            this.sections.forEach((section, index) => {
                parts.push(this.sectionContentHtml(section, index));
            });

            return parts.filter(Boolean).join('\n');
        },

        sectionNumber(index, value) {
            const text = String(value || '').trim();
            return (index + 1) + '. ' + (text || 'Contract section');
        },

        richHtmlToParagraphs(value) {
            const template = document.createElement('template');
            template.innerHTML = this.sanitizeRichHtml(value || '');
            const output = [];

            const addParagraph = (html) => {
                const content = String(html || '').trim();
                if (content) output.push('<p>' + content + '</p>');
            };

            const walk = (node, orderedIndex) => {
                if (node.nodeType === Node.TEXT_NODE) {
                    const text = node.textContent.replace(/\s+/g, ' ').trim();
                    if (text) addParagraph(this.escapeHtml(text));
                    return orderedIndex;
                }

                if (node.nodeType !== Node.ELEMENT_NODE) return orderedIndex;

                const tag = node.tagName.toLowerCase();
                if (/^h[1-6]$/.test(tag)) {
                    addParagraph('<strong>' + this.escapeHtml(node.textContent.trim()) + '</strong>');
                    return orderedIndex;
                }
                if (tag === 'p') {
                    addParagraph(node.innerHTML || '<br>');
                    return orderedIndex;
                }
                if (tag === 'li') {
                    const prefix = orderedIndex ? 'No. ' + orderedIndex + ': ' : '- ';
                    addParagraph(this.escapeHtml(prefix + node.textContent.trim()));
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

                addParagraph(this.escapeHtml(node.textContent.trim()));
                return orderedIndex;
            };

            Array.from(template.content.childNodes).forEach((node) => walk(node, 0));
            return output.join('') || '<p><br></p>';
        },

        sectionContentHtml(section, index) {
            if (section.type === 'heading') {
                const heading = this.escapeHtml(this.sectionNumber(index, section.heading || 'Clause Heading'));
                const note = section.note ? '<p data-section-note>' + this.escapeHtml(section.note) + '</p>' : '';
                return '<div data-draft-section="heading"><p><strong>' + heading + '</strong></p>' + note + '</div>';
            }

            if (section.type === 'paragraph') {
                const label = this.escapeHtml(section.label || '');
                return '<div data-draft-section="paragraph" data-label="' + label + '"><p><strong>' + this.escapeHtml(this.sectionNumber(index, section.label || 'Paragraph')) + '</strong></p><div data-section-content>' + this.richHtmlToParagraphs(section.content || '') + '</div></div>';
            }

            if (section.type === 'text') {
                const label = this.escapeHtml(section.label || '');
                const title = '<p><strong>' + this.escapeHtml(this.sectionNumber(index, section.label || 'Text clause')) + '</strong></p>';
                const lines = (section.content || '').split(/\n+/).map((line) => line.trim()).filter(Boolean);
                return '<div data-draft-section="text" data-label="' + label + '">' + title + '<div data-section-content>' + (lines.map((line) => '<p>' + this.escapeHtml(line) + '</p>').join('') || '<p><br></p>') + '</div></div>';
            }

            if (section.type === 'list') {
                const label = this.escapeHtml(section.label || '');
                const title = '<p><strong>' + this.escapeHtml(this.sectionNumber(index, section.label || 'List')) + '</strong></p>';
                const items = (section.items || []).map((item, index) => {
                    const rawText = item.text || 'List item';
                    const text = this.escapeHtml(rawText);
                    if (section.listStyle === 'numbered') return '<p data-list-item data-text="' + text + '" data-checked="' + (item.checked ? '1' : '0') + '">No. ' + (index + 1) + ': ' + text + '</p>';
                    if (section.listStyle === 'checklist') return '<p data-list-item data-text="' + text + '" data-checked="' + (item.checked ? '1' : '0') + '">[' + (item.checked ? 'x' : ' ') + '] ' + text + '</p>';
                    return '<p data-list-item data-text="' + text + '" data-checked="' + (item.checked ? '1' : '0') + '">- ' + text + '</p>';
                }).join('');
                return '<div data-draft-section="list" data-label="' + label + '" data-list-style="' + this.escapeHtml(section.listStyle || 'bullet') + '">' + title + items + '</div>';
            }

            if (section.type === 'checkbox') {
                const label = this.escapeHtml(section.label || 'Acceptance checkbox');
                return '<div data-draft-section="checkbox" data-label="' + label + '" data-checked="' + (section.checked ? '1' : '0') + '"><p><strong>' + this.escapeHtml(this.sectionNumber(index, 'Acceptance')) + '</strong></p><p>[' + (section.checked ? 'x' : ' ') + '] ' + label + '</p></div>';
            }

            if (section.type === 'date') {
                const label = this.escapeHtml(section.label || 'Date');
                return '<div data-draft-section="date" data-label="' + label + '" data-value="' + this.escapeHtml(section.value || '') + '"><p><strong>' + this.escapeHtml(this.sectionNumber(index, section.label || 'Date')) + ':</strong> ' + this.escapeHtml(section.value || '________________') + '</p></div>';
            }

            if (section.type === 'signature') {
                const note = section.note ? '<p data-section-note>' + this.escapeHtml(section.note) + '</p>' : '';
                return '<div data-draft-section="signature" data-left-signer="' + this.escapeHtml(section.leftSigner || '') + '" data-right-signer="' + this.escapeHtml(section.rightSigner || '') + '"><p><strong>' + this.escapeHtml(this.sectionNumber(index, 'Signature block')) + '</strong></p>' + note
                    + '<p>' + this.escapeHtml(section.leftSigner || 'Client Representative') + ': ____________________________</p>'
                    + '<p>' + this.escapeHtml(section.rightSigner || 'ITEC Representative') + ': ____________________________</p></div>';
            }

            return '';
        },

        previewHtml() {
            const html = this.sections.map((section, index) => {
                if (section.type === 'list') {
                    return this.previewListHtml(section);
                }

                if (section.type === 'signature') {
                    return this.previewSignatureHtml(section);
                }

                return this.sectionContentHtml(section, index);
            }).join('');

            return html || '';
        },

        previewListHtml(section) {
            const title = section.label ? '<h3>' + this.escapeHtml(section.label) + '</h3>' : '';
            const tag = section.listStyle === 'numbered' ? 'ol' : 'ul';
            const items = (section.items || []).map((item) => {
                const prefix = section.listStyle === 'checklist' ? '[' + (item.checked ? 'x' : ' ') + '] ' : '';
                return '<li>' + prefix + this.escapeHtml(item.text || 'List item') + '</li>';
            }).join('');

            return title + '<' + tag + '>' + items + '</' + tag + '>';
        },

        previewSignatureHtml(section) {
            const note = section.note ? '<p>' + this.escapeHtml(section.note) + '</p>' : '';
            return note
                + '<div class="mt-4 grid grid-cols-2 gap-4 text-xs text-slate-700">'
                + '<div class="border-t border-slate-400 pt-2">' + this.escapeHtml(section.leftSigner || 'Client Representative') + '</div>'
                + '<div class="border-t border-slate-400 pt-2">' + this.escapeHtml(section.rightSigner || 'ITEC Representative') + '</div>'
                + '</div>';
        },

        sanitizeRichHtml(value) {
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
        },

        escapeHtml(value) {
            return String(value || '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            })[char]);
        },

        validEmail(value) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(value || '').trim());
        },

        setNotice(type, message) {
            this.notice = {
                type: type,
                message: message
            };
        }
    };
}
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/app.php';
?>
