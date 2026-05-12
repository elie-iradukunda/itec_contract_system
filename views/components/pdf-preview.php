<?php
/**
 * PDF Preview Component
 * 
 * @param string $pdfUrl - URL to the PDF file
 * @param string $id - Unique ID for the preview container (optional)
 * @param int $pageNumber - Page to display (default: 1)
 * @param float $scale - Zoom scale (default: 1.5)
 */
$pdfUrl = $pdfUrl ?? '';
$componentId = $id ?? 'pdf-preview-' . uniqid();
$pageNumber = $pageNumber ?? 1;
$scale = $scale ?? 1.5;
?>
<div class="pdf-preview-container" id="<?= $componentId ?>">
    <div class="pdf-controls mb-3">
        <button type="button" class="btn btn-sm btn-outline-secondary prev-page" disabled>&lt; Previous</button>
        <span class="mx-2">Page <span class="page-num">1</span> of <span class="page-count">0</span></span>
        <button type="button" class="btn btn-sm btn-outline-secondary next-page" disabled>Next &gt;</button>
        <button type="button" class="btn btn-sm btn-outline-secondary zoom-in">Zoom +</button>
        <button type="button" class="btn btn-sm btn-outline-secondary zoom-out">Zoom -</button>
        <button type="button" class="btn btn-sm btn-outline-secondary reset-zoom">Reset</button>
    </div>
    <div class="pdf-canvas-container" style="border: 1px solid #ddd; border-radius: 8px; padding: 10px; background: #f5f5f5; min-height: 500px;">
        <canvas id="pdf-canvas-<?= $componentId ?>" style="width: 100%; height: auto;"></canvas>
    </div>
    <div class="pdf-loading text-center p-5 d-none">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p>Loading PDF preview...</p>
    </div>
    <div class="pdf-error alert alert-danger d-none">
        <strong>Error:</strong> <span class="error-message"></span>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
<script>
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';

(function() {
    const container = document.getElementById('<?= $componentId ?>');
    const canvas = document.getElementById('pdf-canvas-<?= $componentId ?>');
    const ctx = canvas.getContext('2d');
    
    let pdfDoc = null;
    let currentPage = <?= $pageNumber ?>;
    let currentScale = <?= $scale ?>;
    let pdfUrl = '<?= $pdfUrl ?>';
    
    const prevBtn = container.querySelector('.prev-page');
    const nextBtn = container.querySelector('.next-page');
    const pageNumSpan = container.querySelector('.page-num');
    const pageCountSpan = container.querySelector('.page-count');
    const zoomInBtn = container.querySelector('.zoom-in');
    const zoomOutBtn = container.querySelector('.zoom-out');
    const resetZoomBtn = container.querySelector('.reset-zoom');
    const loadingDiv = container.querySelector('.pdf-loading');
    const errorDiv = container.querySelector('.pdf-error');
    const errorMessageSpan = container.querySelector('.error-message');
    
    function showLoading() {
        loadingDiv.classList.remove('d-none');
        canvas.style.display = 'none';
    }
    
    function hideLoading() {
        loadingDiv.classList.add('d-none');
        canvas.style.display = 'block';
    }
    
    function showError(message) {
        loadingDiv.classList.add('d-none');
        canvas.style.display = 'none';
        errorDiv.classList.remove('d-none');
        errorMessageSpan.textContent = message;
    }
    
    function renderPage() {
        if (!pdfDoc) return;
        
        pdfDoc.getPage(currentPage).then(page => {
            const viewport = page.getViewport({ scale: currentScale });
            canvas.height = viewport.height;
            canvas.width = viewport.width;
            
            const renderContext = {
                canvasContext: ctx,
                viewport: viewport
            };
            
            page.render(renderContext);
            
            pageNumSpan.textContent = currentPage;
            prevBtn.disabled = currentPage === 1;
            nextBtn.disabled = currentPage === pdfDoc.numPages;
        }).catch(error => {
            showError('Failed to render page: ' + error.message);
        });
    }
    
    function loadPDF() {
        if (!pdfUrl) {
            showError('No PDF URL provided');
            return;
        }
        
        showLoading();
        
        pdfjsLib.getDocument(pdfUrl).promise.then(doc => {
            pdfDoc = doc;
            pageCountSpan.textContent = pdfDoc.numPages;
            renderPage();
            hideLoading();
        }).catch(error => {
            showError('Failed to load PDF: ' + error.message);
        });
    }
    
    function nextPage() {
        if (currentPage < pdfDoc.numPages) {
            currentPage++;
            renderPage();
        }
    }
    
    function prevPage() {
        if (currentPage > 1) {
            currentPage--;
            renderPage();
        }
    }
    
    function zoomIn() {
        currentScale += 0.25;
        renderPage();
    }
    
    function zoomOut() {
        if (currentScale > 0.5) {
            currentScale -= 0.25;
            renderPage();
        }
    }
    
    function resetZoom() {
        currentScale = <?= $scale ?>;
        renderPage();
    }
    
    // Event listeners
    prevBtn.addEventListener('click', prevPage);
    nextBtn.addEventListener('click', nextPage);
    zoomInBtn.addEventListener('click', zoomIn);
    zoomOutBtn.addEventListener('click', zoomOut);
    resetZoomBtn.addEventListener('click', resetZoom);
    
    // Load PDF on page load
    loadPDF();
})();
</script>