<?php
$title = 'Contract Signed Successfully';
$showPageHeader = false;

ob_start();
?>  

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0 rounded-4 text-center">
                <div class="card-body p-5">
                    <div class="mb-4">
                        <i class="fas fa-check-circle text-success fa-5x"></i>
                    </div>
                    
                    <h1 class="mb-3">Contract Signed Successfully!</h1>
                    
                    <p class="lead mb-4">
                        Thank you for signing the contract.
                    </p>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        The company representative will now review and add their signature and seal.
                        You will receive the final executed contract via email once completed.
                    </div>
                    
                    <div class="mt-4">
                        <a href="<?= BASE_URL ?>/" class="btn btn-primary btn-lg">
                            <i class="fas fa-home me-2"></i> Return to Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/app.php';