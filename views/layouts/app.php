<?php
// Load constants if not already loaded
if (!defined('BASE_URL')) {
    require_once dirname(__DIR__, 2) . '/config/constants.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Contract Management System' ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        body {
            background-color: #f5f7fb;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            padding: 0.75rem 0;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.35rem;
        }
        
        .navbar-brand i {
            font-size: 1.5rem;
        }
        
        .nav-link {
            font-weight: 500;
            padding: 0.5rem 1rem;
            transition: all 0.2s;
        }
        
        .nav-link:hover {
            background-color: rgba(13, 110, 253, 0.05);
            border-radius: 0.5rem;
        }
        
        .nav-link.active {
            color: #0d6efd !important;
            background-color: rgba(13, 110, 253, 0.1);
            border-radius: 0.5rem;
        }
        
        .user-dropdown .dropdown-toggle {
            background-color: #e9ecef;
            border-radius: 2rem;
            padding: 0.35rem 0.75rem;
        }
        
        .container-main {
            padding: 1.5rem;
            min-height: calc(100vh - 140px);
        }
        
        .card {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid #e9ecef;
            font-weight: 600;
            padding: 1rem 1.25rem;
        }
        
        .btn-primary {
            background-color: #0d6efd;
            border-radius: 0.5rem;
            padding: 0.5rem 1rem;
            font-weight: 500;
        }
        
        footer {
            margin-top: 2rem;
            padding: 1.5rem;
            text-align: center;
            color: #6c757d;
            border-top: 1px solid #e9ecef;
            background-color: white;
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.65rem;
            border-radius: 2rem;
        }
        .contract-editor-component textarea:focus {
    box-shadow: none;
    border-color: #86b7fe;
}

.contract-editor-component .list-group-item:hover {
    background-color: #f8f9fa;
}

.changes-list {
    max-height: calc(100vh - 200px);
    overflow-y: auto;
}

    </style>
</head>
<body>

<!-- Main Navigation -->
<nav class="navbar navbar-expand-lg bg-white">
    <div class="container-fluid">
        <!-- Brand -->
        <a class="navbar-brand" href="<?= BASE_URL ?>/contracts">
            <i class="bi bi-file-text-fill text-primary"></i> 
            <span class="text-dark">ITEC</span>
            <span class="text-primary">Contract System</span>
        </a>
        
        <!-- Mobile Toggle -->
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
            <i class="bi bi-list fs-3"></i>
        </button>
        
        <!-- Navbar Links -->
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <!-- Dashboard -->
                <li class="nav-item">
                    <a class="nav-link <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/contracts') && !str_contains($_SERVER['REQUEST_URI'] ?? '', '/create') ? 'active' : '' ?>" 
                       href="<?= BASE_URL ?>/contracts">
                        <i class="bi bi-grid-3x3-gap-fill me-1"></i> Dashboard
                    </a>
                </li>
                
                <!-- Create New -->
                <li class="nav-item">
                    <a class="nav-link <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/create') ? 'active' : '' ?>" 
                       href="<?= BASE_URL ?>/contracts/create">
                        <i class="bi bi-plus-circle me-1"></i> New Contract
                    </a>
                </li>
                
                <!-- Divider (visual) -->
                <li class="nav-item mx-2 d-flex align-items-center">
                    <span class="text-muted">|</span>
                </li>
                
                <!-- Drafts -->
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/contracts?status=DRAFT">
                        <i class="bi bi-pencil-square me-1"></i> Drafts
                    </a>
                </li>
                
                <!-- Awaiting Client -->
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/contracts?status=AWAITING_CLIENT">
                        <i class="bi bi-envelope me-1"></i> Awaiting Client
                    </a>
                </li>
                
                <!-- Awaiting Company -->
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/contracts?status=AWAITING_COMPANY">
                        <i class="bi bi-building me-1"></i> Awaiting Company
                    </a>
                </li>
                
                <!-- Completed -->
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/contracts?status=FULLY_SIGNED">
                        <i class="bi bi-check2-all me-1"></i> Completed
                    </a>
                </li>
            </ul>
            
            <!-- Right side - User Menu (No Auth for now) -->
            <div class="dropdown user-dropdown">
                <a href="#" class="dropdown-toggle text-decoration-none d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white" 
                         style="width: 36px; height: 36px;">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <span class="text-dark d-none d-lg-inline">Demo User</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2">
                    <li>
                        <a class="dropdown-item" href="<?= BASE_URL ?>/contracts">
                            <i class="bi bi-grid me-2"></i> Dashboard
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="<?= BASE_URL ?>/contracts/audit/all">
                            <i class="bi bi-list-check me-2"></i> All Audit Trails
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-danger" href="#">
                            <i class="bi bi-box-arrow-right me-2"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<!-- Main Content -->
<main class="container-main">
    <div class="container-fluid px-0">
        <!-- Success/Error Messages can go here -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> <?= $_SESSION['success'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $_SESSION['error'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?= $content ?? '' ?>
    </div>
</main>

<!-- Footer -->
<footer>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <p class="mb-0 small">
                    <i class="bi bi-shield-check me-1"></i> 
                    Digitally signed contracts are legally binding
                </p>
                <p class="mb-0 mt-1 small text-muted">
                    © 2026 ITEC LTD. All rights reserved. | 
                    <a href="#" class="text-muted">Terms</a> | 
                    <a href="#" class="text-muted">Privacy</a>
                </p>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<!-- Global Alpine Component for Notifications (optional) -->
<script>
document.addEventListener('alpine:init', () => {
    Alpine.store('app', {
        loading: false,
        showToast: false,
        toastMessage: '',
        toastType: 'success'
    })
})
</script>
</body>
</html>