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
$pageHeading = 'Create Contract';
$pageEyebrow = 'Dynamic Document Builder';
$pageLead = 'Build your contract document with dynamic sections: titles, rich‑text paragraphs, checkboxes and editable lists.';
$pageActions = [
    '<a class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded text-gray-800 text-sm font-medium transition" href="' . BASE_URL . '/contracts">← Back to Contracts</a>',
];

ob_start();
?>

<style>
    .section-card {
        transition: all 0.2s ease-in-out;
    }
    .section-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 20px -12px rgba(0, 0, 0, 0.15);
    }
    .preview-sticky {
        position: sticky;
        top: 2rem;
    }
    .animate-fade-in {
        animation: fadeIn 0.3s ease-out;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .ql-editor {
        min-height: 120px;
        font-size: 0.95rem;
    }
    .ql-toolbar.ql-snow {
        border-top-left-radius: 0.75rem;
        border-top-right-radius: 0.75rem;
        background: #fafbfc;
    }
    .ql-container.ql-snow {
        border-bottom-left-radius: 0.75rem;
        border-bottom-right-radius: 0.75rem;
    }
</style>

<div x-data="contractBuilder()" x-init="initQuillWatcher" class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-100 py-8 px-4">
    <div class="max-w-7xl mx-auto">
        <!-- Header Section – only Contract Title now -->
        <div class="mb-8 animate-fade-in">
            <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 p-6 mb-6 border border-slate-100">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2 flex items-center gap-2">
                        <i class="bi bi-file-text-fill text-indigo-500"></i>
                        Contract Title
                    </label>
                    <input x-model="contractData.title" type="text" placeholder="Enter contract title..." 
                           class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition shadow-sm">
                </div>
            </div>
        </div>

        <!-- Main Editor Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Editor Panel -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 overflow-hidden border border-slate-100">
                    <!-- Toolbar with Select Dropdown -->
                    <div class="border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white px-6 py-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <i class="bi bi-pencil-square text-indigo-500 text-lg"></i>
                                <span class="text-sm font-semibold text-slate-600">Document Builder</span>
                                <span x-show="sections.length > 0" class="text-xs bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full" x-text="sections.length + ' sections'"></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <select x-model="newSectionType" class="px-4 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 bg-white">
                                    <option value="title">📌 Title</option>
                                    <option value="paragraph">📝 Rich Paragraph</option>
                                    <option value="list">📋 List</option>
                                    <option value="checkbox">✅ Checkbox</option>
                                </select>
                                <button @click="addSelectedSection()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium transition shadow-sm">
                                    <i class="bi bi-plus-lg"></i> Add Section
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Sections Container -->
                    <div class="p-6 min-h-[500px] space-y-4 bg-white">
                        <template x-if="sections.length === 0">
                            <div class="text-center py-16 animate-fade-in">
                                <div class="inline-flex items-center justify-center w-20 h-20 bg-slate-100 rounded-2xl mb-4">
                                    <i class="bi bi-file-earmark-text text-3xl text-slate-400"></i>
                                </div>
                                <p class="text-slate-400 text-lg mb-4">No sections added yet</p>
                                <p class="text-slate-400 text-sm mb-6">Select a section type from the dropdown and click "Add Section"</p>
                            </div>
                        </template>

                        <template x-for="(section, index) in sections" :key="index">
                            <div class="section-card bg-white border-2 border-slate-200 rounded-xl p-5 hover:border-indigo-300 hover:bg-indigo-50/10 transition-all animate-fade-in">
                                <!-- Section Header -->
                                <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold uppercase tracking-wide px-2 py-1 rounded-lg"
                                              :class="{
                                                  'bg-indigo-100 text-indigo-700': section.type === 'title',
                                                  'bg-emerald-100 text-emerald-700': section.type === 'paragraph',
                                                  'bg-teal-100 text-teal-700': section.type === 'list',
                                                  'bg-purple-100 text-purple-700': section.type === 'checkbox'
                                              }">
                                            <i class="bi" :class="{
                                                'bi-type-h1': section.type === 'title',
                                                'bi-file-text': section.type === 'paragraph',
                                                'bi-list-ul': section.type === 'list',
                                                'bi-check2-square': section.type === 'checkbox'
                                            }"></i>
                                            <span x-text="section.type" class="ml-1"></span>
                                        </span>
                                        <span x-show="section.label" x-text="section.label" class="text-xs text-slate-500"></span>
                                    </div>
                                    <div class="flex gap-1">
                                        <button @click="moveSection(index, -1)" :disabled="index === 0" 
                                                class="p-1.5 hover:bg-slate-100 rounded-lg transition disabled:opacity-30">
                                            <i class="bi bi-arrow-up-short text-slate-600"></i>
                                        </button>
                                        <button @click="moveSection(index, 1)" :disabled="index === sections.length - 1"
                                                class="p-1.5 hover:bg-slate-100 rounded-lg transition disabled:opacity-30">
                                            <i class="bi bi-arrow-down-short text-slate-600"></i>
                                        </button>
                                        <button @click="removeSection(index)" class="p-1.5 hover:bg-red-50 rounded-lg transition">
                                            <i class="bi bi-trash3 text-red-500"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Title Section -->
                                <div x-show="section.type === 'title'">
                                    <input x-model="section.content" type="text" placeholder="Enter title..." 
                                           class="w-full text-2xl font-bold text-slate-800 bg-transparent border-b-2 border-slate-200 focus:border-indigo-500 focus:outline-none pb-2 placeholder:text-slate-300">
                                </div>

                                <!-- Paragraph Section with Quill -->
                                <div x-show="section.type === 'paragraph'" class="space-y-3">
                                    <input x-model="section.label" type="text" placeholder="Section label (optional, e.g., '1.1 Background')" 
                                           class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <div class="quill-wrapper" :id="'quill-' + index" x-ref="quillContainer" @quill-mounted="initQuillForSection(index)"></div>
                                    <input type="hidden" x-model="section.content">
                                </div>

                                <!-- List Section (Bullet/Numbered/Checklist) -->
                                <div x-show="section.type === 'list'" class="space-y-3">
                                    <input x-model="section.label" type="text" placeholder="List title (optional)" 
                                           class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <div class="flex gap-2">
                                        <select x-model="section.listStyle" class="px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white">
                                            <option value="bullet">• Bullet list</option>
                                            <option value="numbered">1. Numbered list</option>
                                            <option value="checked">☐ Checklist</option>
                                        </select>
                                    </div>
                                    <div class="space-y-2">
                                        <template x-for="(item, itemIdx) in section.items" :key="itemIdx">
                                            <div class="flex items-center gap-2">
                                                <span x-show="section.listStyle === 'bullet'" class="text-slate-400 w-5">•</span>
                                                <span x-show="section.listStyle === 'numbered'" class="text-slate-400 text-sm w-5" x-text="(itemIdx + 1) + '.'"></span>
                                                <span x-show="section.listStyle === 'checked'" class="text-slate-400">
                                                    <input type="checkbox" x-model="item.checked" class="w-4 h-4 rounded border-slate-300">
                                                </span>
                                                <input x-model="item.text" type="text" placeholder="List item" 
                                                       class="flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                                <button @click="removeListItem(index, itemIdx)" class="text-red-400 hover:text-red-600">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            </div>
                                        </template>
                                        <button @click="addListItem(index)" class="text-sm text-indigo-600 hover:text-indigo-700 flex items-center gap-1 mt-2">
                                            <i class="bi bi-plus-circle"></i> Add item
                                        </button>
                                    </div>
                                </div>

                                <!-- Checkbox Section -->
                                <div x-show="section.type === 'checkbox'" class="space-y-3">
                                    <input x-model="section.label" type="text" placeholder="Checkbox label (e.g., 'I agree to the terms')" 
                                           class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <label class="flex items-center gap-3 cursor-pointer">
                                        <input x-model="section.checked" type="checkbox" class="w-5 h-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                        <span class="text-slate-700" x-text="section.label || 'Checkbox item'"></span>
                                    </label>
                                </div>
                            </div>
                        </template>

                        <!-- Inline "Add Section" after sections -->
                        <template x-if="sections.length > 0">
                            <div class="flex flex-wrap gap-2 items-center justify-center py-4 border-t border-slate-100 mt-4">
                                <span class="text-xs font-medium text-slate-500">Add another:</span>
                                <select x-model="newSectionType" class="px-3 py-1.5 border border-slate-200 rounded-lg text-sm bg-white">
                                    <option value="title">Title</option>
                                    <option value="paragraph">Paragraph</option>
                                    <option value="list">List</option>
                                    <option value="checkbox">Checkbox</option>
                                </select>
                                <button @click="addSelectedSection()" class="px-3 py-1.5 bg-indigo-100 hover:bg-indigo-200 text-indigo-700 rounded-lg text-xs font-medium transition">
                                    <i class="bi bi-plus"></i> Add
                                </button>
                            </div>
                        </template>
                    </div>

                    <!-- Submit Section -->
                    <div class="bg-slate-50 border-t border-slate-200 px-6 py-5">
                        <div class="flex gap-4 justify-end">
                            <a href="<?= BASE_URL ?>/contracts" class="px-6 py-2.5 border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 rounded-xl font-medium transition shadow-sm">
                                <i class="bi bi-x-lg"></i> Cancel
                            </a>
                            <button @click="submitContract()" class="px-8 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-medium transition shadow-md hover:shadow-lg">
                                <i class="bi bi-file-earmark-check"></i> Generate Draft
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Preview Sidebar (simplified) -->
            <div class="lg:col-span-1">
                <div class="preview-sticky bg-white rounded-2xl shadow-xl shadow-slate-200/50 p-6 border border-slate-100">
                    <div class="flex items-center gap-2 mb-5 pb-3 border-b border-slate-100">
                        <i class="bi bi-eye-fill text-indigo-500 text-xl"></i>
                        <h3 class="text-lg font-bold text-slate-800">Document Preview</h3>
                    </div>
                    
                    <div class="bg-gradient-to-r from-slate-50 to-white rounded-xl p-4 space-y-3">
                        <div>
                            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Title</span>
                            <p class="text-slate-800 font-medium mt-1" x-text="contractData.title || 'Untitled'"></p>
                        </div>
                        <div class="border-t border-slate-100 pt-3">
                            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Total Sections</span>
                            <p class="text-2xl font-bold text-indigo-600 mt-1" x-text="sections.length"></p>
                        </div>
                    </div>

                    <!-- Section Summary -->
                    <div class="mt-6 pt-4">
                        <h4 class="font-semibold text-slate-700 mb-3 flex items-center gap-2">
                            <i class="bi bi-list-ul text-indigo-500"></i>
                            Section Summary
                        </h4>
                        <div class="space-y-2 max-h-80 overflow-y-auto pr-2">
                            <template x-for="(section, index) in sections" :key="index">
                                <div class="flex items-center gap-2 text-sm text-slate-600 p-2 hover:bg-slate-50 rounded-lg transition">
                                    <span class="inline-block w-2 h-2 rounded-full"
                                          :class="{
                                              'bg-indigo-500': section.type === 'title',
                                              'bg-emerald-500': section.type === 'paragraph',
                                              'bg-teal-500': section.type === 'list',
                                              'bg-purple-500': section.type === 'checkbox'
                                          }"></span>
                                    <span class="text-xs text-slate-400 w-5" x-text="index + 1"></span>
                                    <span class="capitalize font-medium" x-text="section.type"></span>
                                    <span class="text-xs text-slate-400 truncate flex-1" x-show="section.label" x-text="': ' + section.label"></span>
                                </div>
                            </template>
                            <template x-if="sections.length === 0">
                                <p class="text-slate-400 italic text-sm text-center py-4">No sections added yet</p>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function contractBuilder() {
    return {
        contractData: {
            title: 'New Contract'
        },
        sections: [],
        newSectionType: 'title',
        quillInstances: new Map(), // store Quill instances by section index
        
        addSelectedSection() {
            this.addSection(this.newSectionType);
        },
        
        addSection(type) {
            let newSection = {
                type: type,
                content: '',
                label: '',
                checked: false
            };
            if (type === 'list') {
                newSection.items = [{ text: 'New list item', checked: false }];
                newSection.listStyle = 'bullet';
            }
            this.sections.push(newSection);
            // scroll to new section
            setTimeout(() => {
                const lastSection = document.querySelector('.section-card:last-child');
                if (lastSection) lastSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 100);
        },
        
        addListItem(sectionIndex) {
            this.sections[sectionIndex].items.push({ text: '', checked: false });
        },
        
        removeListItem(sectionIndex, itemIndex) {
            this.sections[sectionIndex].items.splice(itemIndex, 1);
        },
        
        removeSection(index) {
            // destroy Quill instance if exists
            if (this.quillInstances.has(index)) {
                this.quillInstances.get(index).destroy();
                this.quillInstances.delete(index);
            }
            this.sections.splice(index, 1);
            // re-index Quill instances after removal (simple reinit)
            setTimeout(() => this.initQuillWatcher(), 50);
        },
        
        moveSection(index, direction) {
            const newIndex = index + direction;
            if (newIndex >= 0 && newIndex < this.sections.length) {
                // swap
                [this.sections[index], this.sections[newIndex]] = [this.sections[newIndex], this.sections[index]];
                // Quill instances become invalid -> destroy all and reinit
                this.quillInstances.forEach((quill, idx) => quill.destroy());
                this.quillInstances.clear();
                setTimeout(() => this.initQuillWatcher(), 50);
            }
        },
        
        initQuillForSection(index) {
            const section = this.sections[index];
            if (section.type !== 'paragraph') return;
            const containerId = `quill-${index}`;
            const container = document.getElementById(containerId);
            if (!container) return;
            // avoid double init
            if (this.quillInstances.has(index)) return;
            
            const quill = new Quill(container, {
                theme: 'snow',
                placeholder: 'Write your paragraph content here...',
                modules: {
                    toolbar: [
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        ['clean']
                    ]
                }
            });
            // set initial content if any
            if (section.content) {
                quill.root.innerHTML = section.content;
            }
            quill.on('text-change', () => {
                section.content = quill.root.innerHTML;
            });
            this.quillInstances.set(index, quill);
        },
        
        initQuillWatcher() {
            // Initialize Quill for all paragraph sections that don't have it yet
            this.$nextTick(() => {
                for (let i = 0; i < this.sections.length; i++) {
                    if (this.sections[i].type === 'paragraph' && !this.quillInstances.has(i)) {
                        this.initQuillForSection(i);
                    }
                }
            });
        },
        
        submitContract() {
            if (!this.contractData.title.trim()) {
                alert('Please enter a contract title');
                return;
            }
            if (this.sections.length === 0) {
                alert('Please add at least one section to your contract');
                return;
            }
            // validate list sections have items
            for (let i = 0; i < this.sections.length; i++) {
                if (this.sections[i].type === 'list' && (!this.sections[i].items || this.sections[i].items.length === 0)) {
                    alert(`List section "${this.sections[i].label || 'Untitled'}" has no items. Please add at least one item.`);
                    return;
                }
            }
            
            const contractPayload = {
                title: this.contractData.title,
                sections: this.sections
            };
            
            console.log('📄 Contract Payload (Request Body):');
            console.log(JSON.stringify(contractPayload, null, 2));
            console.log('💡 You can copy this object and use it in your API tests.');
            
            alert('✓ Contract draft ready!\n\nOpen the browser console (F12) to see the full request body.\n\nTitle: ' + this.contractData.title + '\nSections: ' + this.sections.length);
            
            // Uncomment when backend is ready
            /*
            fetch('<?= BASE_URL ?>/api/contracts', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(contractPayload)
            }).then(response => response.json())
              .then(data => {
                  if (data.success) window.location.href = '<?= BASE_URL ?>/contracts/' + data.id;
              });
            */
        }
    }
}
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/app.php';
?>