<?php
/**
 * Gestione sessione sicura
 */

// Prevenire accesso diretto
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Accesso diretto non consentito');
}

// Avvia sessione se non già avviata
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Rigenera ID sessione e previene fixation
 */
function session_regenerate_secure(): void {
    if (session_id()) {
        session_regenerate_id(true);
    }
}

/**
 * Distrugge sessione corrente in modo sicuro
 */
function session_destroy_secure(): void {
    $_SESSION = [];
    session_destroy();
}

/**
 * Imposta dati utente in sessione dopo login
 */
function session_set_user(array $user): void {
    session_regenerate_secure();
    
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'] ?? '';
    $_SESSION['role'] = $user['role'];
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
}

/**
 * Verifica se utente è loggato
 */
function is_logged_in(): bool {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        return false;
    }

    // Timeout sessione (1 ora di inattività)
    if (time() - ($_SESSION['last_activity'] ?? 0) > 3600) {
        session_destroy_secure();
        return false;
    }

    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Verifica se utente deve cambiare password e reindirizza
 * Da chiamare dopo require_login() nelle pagine
 */
function check_force_password_change(): void {
    if (!is_logged_in()) return;
    
    // Non reindirizzare se siamo già nelle pagine di gestione password/logout
    $currentPage = $_SERVER['PHP_SELF'] ?? '';
    if (str_contains($currentPage, 'cambio-password.php')) return;
    if (str_contains($currentPage, 'change-password.php')) return;
    if (str_contains($currentPage, 'logout.php')) return;
    
    // Controlla se force_password_change è impostato
    $db = db();
    $stmt = $db->prepare("SELECT force_password_change FROM users WHERE id = ?");
    $stmt->execute([current_user_id()]);
    $user = $stmt->fetch();
    
    if ($user && $user['force_password_change']) {
        header('Location: ' . APP_URL . '/utenti/cambio-password.php?force=1');
        exit;
    }
}

/**
 * Ottieni ID utente corrente
 */
function current_user_id(): ?int {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Ottieni username corrente
 */
function current_username(): ?string {
    return $_SESSION['username'] ?? null;
}

/**
 * Ottieni email corrente
 */
function current_email(): ?string {
    return $_SESSION['email'] ?? null;
}

/**
 * Ottieni ruolo corrente
 */
function current_role(): ?string {
    return $_SESSION['role'] ?? null;
}

/**
 * Verifica ruolo minimo richiesto (gerarchia: operator < admin < god)
 */
function has_role(string $requiredRole): bool {
    $roleHierarchy = [
        ROLE_OPERATOR => 1,
        ROLE_ADMIN => 2,
        ROLE_GOD => 3,
    ];
    
    $currentRole = current_role();
    if (!$currentRole) return false;
    
    return ($roleHierarchy[$currentRole] ?? 0) >= ($roleHierarchy[$requiredRole] ?? 0);
}

/**
 * Ottieni dati utente corrente
 */
function current_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    
    return [
        'id' => current_user_id(),
        'username' => current_username(),
        'email' => current_email(),
        'role' => current_role(),
    ];
}

/**
 * Aggiorna timestamp attività sessione
 */
function session_touch(): void {
    $_SESSION['last_activity'] = time();
}
