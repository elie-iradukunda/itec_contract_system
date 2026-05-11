<?php
$basePath = BASE_URL;
$editor_config = $editor_config ?? [
    'contract_id' => 0,
    'content' => 'Start typing your contract here...',
    'height' => '500px'
];
$contractId = $editor_config['contract_id'];
$editorContent = htmlspecialchars($editor_config['content'], ENT_QUOTES, 'UTF-8');
$height = $editor_config['height'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contract Editor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Trix CSS -->
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/trix@2.0.8/dist/trix.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    


<main class="container-main">
    <div class="container-fluid px-0">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <i class="bi bi-file-text me-2"></i>
                    <strong>Contract Editor</strong>
                    <span class="badge bg-secondary ms-2" id="contractIdBadge">ID: <?= $contractId ?: 'New' ?></span>
                </div>
                <button type="button" class="btn btn-sm btn-primary" id="saveButton">
                    <i class="bi bi-save"></i> Save Contract
                </button>
            </div>
            <div class="card-body">
                <input id="contractContent" type="hidden" name="content" value="<?= $editorContent ?>">
                <trix-editor input="contractContent" class="trix-content"></trix-editor>
            </div>
            <div class="card-footer text-muted small text-end" id="saveMessage">
                Ready to edit
            </div>
        </div>
    </div>
</main>

<!-- Trix JS -->

<script>
const BASE_URL = '<?= $basePath ?>';
const CONTRACT_ID = <?= (int) $contractId ?>;
const IS_NEW_CONTRACT = CONTRACT_ID === 0;

let isSaving = false;
let saveTimeout = null;
let autoSaveInterval = null;

function getContent() {
    const input = document.getElementById('contractContent');
    return input ? input.value : '';
}

async function saveDocument() {
    if (isSaving) return;
    
    const content = getContent();
    
    isSaving = true;
    const saveBtn = document.getElementById('saveButton');
    const saveMsg = document.getElementById('saveMessage');
    
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';
    saveMsg.innerHTML = 'Saving...';
    
    try {
        let url, method, body;
        
        if (IS_NEW_CONTRACT) {
            url = `${BASE_URL}/api/contracts`;
            method = 'POST';
            body = JSON.stringify({ title: 'New Contract', content: content });
        } else {
            url = `${BASE_URL}/api/contracts/${CONTRACT_ID}/save`;
            method = 'POST';
            body = JSON.stringify({ content: content });
        }
        
        const response = await fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: body
        });
        
        const data = await response.json();
        
        if (data.success) {
            saveMsg.innerHTML = `Saved at ${new Date().toLocaleTimeString()}`;
            
            if (IS_NEW_CONTRACT && data.contract_id) {
                window.location.href = `${BASE_URL}/contracts/${data.contract_id}/edit`;
            }
        } else {
            saveMsg.innerHTML = 'Save failed: ' + (data.message || 'Unknown error');
        }
    } catch (error) {
        console.error('Save error:', error);
        saveMsg.innerHTML = 'Network error while saving';
    } finally {
        isSaving = false;
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="bi bi-save"></i> Save Contract';
        
        setTimeout(() => {
            if (saveMsg.innerHTML !== 'Ready to edit') {
                saveMsg.innerHTML = 'Ready to edit';
            }
        }, 3000);
    }
}

// Auto-save on change
document.addEventListener('trix-change', function() {
    if (saveTimeout) clearTimeout(saveTimeout);
    saveTimeout = setTimeout(() => {
        if (!IS_NEW_CONTRACT) {
            saveDocument();
        }
    }, 3000);
});

// Auto-save every 30 seconds for existing contracts
if (!IS_NEW_CONTRACT) {
    autoSaveInterval = setInterval(() => {
        if (!isSaving) {
            saveDocument();
        }
    }, 30000);
}

// Manual save button
document.getElementById('saveButton').addEventListener('click', saveDocument);

// Cleanup
window.addEventListener('beforeunload', () => {
    if (autoSaveInterval) clearInterval(autoSaveInterval);
    if (saveTimeout) clearTimeout(saveTimeout);
});
</script>
