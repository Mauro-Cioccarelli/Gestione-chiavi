<?php
/**
 * Layout - Header HTML
 * 
 * Variabili richieste:
 *   - $pageTitle: Titolo della pagina
 * 
 * Variabili opzionali:
 *   - $hideSidebar: Nascondi sidebar (default: false)
 *   - $hideHeader: Nascondi header superiore (default: false)
 *   - $extraCss: Array di CSS aggiuntivi da includere
 */

// Prevenire accesso diretto
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Accesso diretto non consentito');
}

$pageTitle = $pageTitle ?? 'Dashboard';
$hideSidebar = $hideSidebar ?? false;
$hideHeader = $hideHeader ?? false;
$extraCss = $extraCss ?? [];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <title><?= htmlspecialchars($pageTitle) ?> - <?= htmlspecialchars(APP_NAME) ?></title>
    
    <!-- Favicon -->
    <link rel="icon" href="<?= asset('images/favicon.ico') ?>" type="image/x-icon">
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?= asset('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="<?= asset('bootstrap-icons-1.13.1/font/bootstrap-icons.min.css') ?>">
    
    <!-- Tabulator CSS -->
    <link rel="stylesheet" href="<?= asset('tabulator-master/dist/css/tabulator.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('tabulator-master/dist/css/tabulator_bootstrap5.min.css') ?>">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= asset('css/main.css') ?>">
    
    <!-- CSS aggiuntivi -->
    <?php foreach ($extraCss as $css): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($css) ?>">
    <?php endforeach; ?>
    
    <style>
        :root {
            --sidebar-width: 260px;
            --sidebar-bg: linear-gradient(180deg, #2c3e50 0%, #1a252f 100%);
            --header-height: 60px;
        }
        
        body {
            min-height: 100vh;
            background: #f5f6fa;
        }
        
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            overflow-y: auto;
            z-index: 1000;
            transition: transform 0.3s ease;
        }
        
        .sidebar-brand {
            padding: 1.25rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }
        
        .sidebar-brand i {
            font-size: 2.5rem;
            color: #3498db;
        }
        
        .sidebar-brand h6 {
            color: #fff;
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.7);
            padding: 0.875rem 1.25rem;
            margin: 0.125rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        
        .sidebar .nav-link:hover {
            color: #fff;
            background: rgba(255,255,255,0.1);
        }
        
        .sidebar .nav-link.active {
            color: #fff;
            background: rgba(52, 152, 219, 0.2);
        }
        
        .sidebar .nav-link i {
            margin-right: 0.625rem;
            width: 1.25rem;
            text-align: center;
        }
        
        .sidebar-user {
            padding: 1rem;
            margin: 1rem 0.5rem;
            background: rgba(255,255,255,0.05);
            border-radius: 0.375rem;
        }
        
        .sidebar-user .username {
            color: #fff;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .sidebar-user .role {
            color: rgba(255,255,255,0.6);
            font-size: 0.75rem;
        }
        
        .main-wrapper {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }
        
        .top-header {
            height: var(--header-height);
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.5rem;
            position: sticky;
            top: 0;
            z-index: 999;
        }
        
        .main-content {
            padding: 1.5rem;
        }
        
        .card {
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-radius: 0.5rem;
        }
        
        .card-header {
            background: #fff;
            border-bottom: 1px solid #eee;
            font-weight: 600;
        }
        
        /* Mobile responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-wrapper {
                margin-left: 0;
            }
        }
        
        /* Flash messages */
        .flash-message {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 1050;
            min-width: 300px;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body>
<?php if (!$hideSidebar): ?>
<!-- Sidebar -->
<nav class="sidebar" id="sidebar">
    <?php include __DIR__ . '/sidebar.php'; ?>
</nav>
<?php endif; ?>

<!-- Main Wrapper -->
<div class="main-wrapper" id="main-wrapper">
    <?php if (!$hideHeader): ?>
    <!-- Top Header -->
    <header class="top-header">
        <div class="d-flex align-items-center">
            <?php if ($hideSidebar): ?>
            <a href="<?= APP_URL ?>" class="text-decoration-none me-3">
                <i class="bi bi-key-fill text-primary" style="font-size: 1.5rem;"></i>
            </a>
            <?php endif; ?>
            
            <!-- Mobile sidebar toggle -->
            <button class="btn btn-link d-md-none me-2" id="sidebar-toggle">
                <i class="bi bi-list fs-4"></i>
            </button>
            
            <h5 class="mb-0 text-muted"><?= htmlspecialchars($pageTitle) ?></h5>
        </div>
        
        <div class="d-flex align-items-center">
            <!-- User dropdown -->
            <div class="dropdown">
                <button class="btn btn-link text-decoration-none dropdown-toggle" 
                        type="button" id="user-dropdown" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle fs-5"></i>
                    <span class="d-none d-sm-inline ms-1"><?= htmlspecialchars(current_username() ?? 'Guest') ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="<?= APP_URL ?>/utenti/profilo.php">
                            <i class="bi bi-person me-2"></i>Profilo
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="<?= APP_URL ?>/logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i>Esci
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </header>
    <?php endif; ?>
    
    <!-- Main Content -->
    <main class="main-content">
        <!-- Flash messages -->
        <?php include __DIR__ . '/alerts.php'; ?>
