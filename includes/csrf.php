<?php
/**
 * Protezione CSRF (Cross-Site Request Forgery)
 */

// Prevenire accesso diretto
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Accesso diretto non consentito');
}

/**
 * Genera token CSRF
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Genera campo hidden CSRF per form HTML
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

/**
 * Verifica token CSRF
 */
function verify_csrf(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Richiede CSRF valido (per endpoint POST)
 * Termina l'esecuzione se il token non è valido
 */
function require_csrf(?string $token): void {
    if (!verify_csrf($token)) {
        http_response_code(403);
        
        // Per richieste AJAX
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Token CSRF non valido']);
            exit;
        }
        
        // Per richieste normali
        $_SESSION['flash_error'] = 'Token CSRF non valido. Riprova.';
        header('Location: ' . $_SERVER['HTTP_REFERER'] ?? APP_URL);
        exit;
    }
}

/**
 * Rigenera token CSRF (dopo login o operazioni sensibili)
 */
function csrf_regenerate(): string {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
