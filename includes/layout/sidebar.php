<?php
/**
 * Layout - Sidebar menu
 */

// Prevenire accesso diretto
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Accesso diretto non consentito');
}

// Determina pagina corrente
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
?>

<!-- Sidebar Brand -->
<div class="sidebar-brand">
    <i class="bi bi-key-fill"></i>
    <h6 class="mb-0"><?= htmlspecialchars(APP_NAME) ?></h6>
    <small class="text-white-50">v<?= APP_VERSION ?></small>
</div>

<!-- Navigation -->
<ul class="nav flex-column mt-3">
    <!-- Dashboard -->
    <li class="nav-item">
        <a class="nav-link <?= str_ends_with($currentPath, 'dashboard.php') ? 'active' : '' ?>" 
           href="<?= APP_URL ?>/dashboard.php">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
        </a>
    </li>
    
    <!-- Chiavi -->
    <li class="nav-item">
        <a class="nav-link <?= str_contains($currentPath, '/chiavi/') ? 'active' : '' ?>" 
           href="<?= APP_URL ?>/chiavi/index.php">
            <i class="bi bi-key"></i>
            <span>Chiavi</span>
        </a>
    </li>
    
    <!-- Categorie (admin) -->
    <?php if (has_role(ROLE_ADMIN)): ?>
    <li class="nav-item">
        <a class="nav-link <?= str_contains($currentPath, 'categorie') ? 'active' : '' ?>" 
           href="<?= APP_URL ?>/chiavi/categorie.php">
            <i class="bi bi-folder"></i>
            <span>Categorie</span>
        </a>
    </li>
    <?php endif; ?>
    
    <!-- Utenti (admin) -->
    <?php if (has_role(ROLE_ADMIN)): ?>
    <li class="nav-item">
        <a class="nav-link <?= str_contains($currentPath, '/utenti/') && !str_contains($currentPath, 'profilo') ? 'active' : '' ?>" 
           href="<?= APP_URL ?>/utenti/index.php">
            <i class="bi bi-people"></i>
            <span>Utenti</span>
        </a>
    </li>
    <?php endif; ?>
    
    <!-- Log (admin) -->
    <?php if (has_role(ROLE_ADMIN)): ?>
    <li class="nav-item">
        <a class="nav-link <?= str_contains($currentPath, 'log') ? 'active' : '' ?>" 
           href="<?= APP_URL ?>/log.php">
            <i class="bi bi-journal-text"></i>
            <span>Log</span>
        </a>
    </li>
    <?php endif; ?>
    
    <!-- Migrazioni (god) -->
    <?php if (has_role(ROLE_GOD)): ?>
    <li class="nav-item">
        <a class="nav-link <?= str_contains($currentPath, 'migrations') ? 'active' : '' ?>" 
           href="<?= APP_URL ?>/migrations/index.php">
            <i class="bi bi-database-gear"></i>
            <span>Migrazioni</span>
        </a>
    </li>
    <?php endif; ?>
</ul>

<!-- User Info -->
<div class="sidebar-user">
    <div class="username">
        <i class="bi bi-person-circle me-1"></i>
        <?= htmlspecialchars(current_username() ?? 'Guest') ?>
    </div>
    <div class="role">
        <span class="badge bg-secondary"><?= htmlspecialchars(ucfirst(current_role() ?? 'guest')) ?></span>
    </div>
</div>
