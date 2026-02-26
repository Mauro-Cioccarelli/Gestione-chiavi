<?php
/**
 * Bootstrap dell'applicazione
 *
 * Questo file inizializza l'applicazione e fornisce funzioni di protezione
 * per le pagine. Da includere all'inizio di ogni script PHP.
 *
 * Uso:
 *   define('APP_ROOT', true);
 *   require_once __DIR__ . '/includes/bootstrap.php';
 */

// Definisci costante per prevenire accesso diretto ai file inclusi
if (!defined('APP_ROOT')) {
    define('APP_ROOT', true);
}

// FORZA VISUALIZZAZIONE ERRORI (SVILUPPO)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Carica configurazione
require_once __DIR__ . '/config.php';

// Carica dipendenze base
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/validator.php';
require_once __DIR__ . '/functions.php';

/**
 * Verifica che l'utente sia loggato
 * Redirect a login se non autenticato
 */
function require_login(): void {
    if (!is_logged_in()) {
        // Per richieste AJAX, ritorna JSON
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode([
                'error' => 'Unauthorized',
                'redirect' => APP_URL . '/login.php'
            ]);
            exit;
        }
        
        // Salva URL di destinazione per redirect dopo login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }
    
    // Aggiorna timestamp attività
    session_touch();
}

/**
 * Verifica che l'utente abbia il ruolo minimo richiesto
 * 
 * @param string $role Ruolo minimo richiesto (operator, admin, god)
 */
function require_role(string $role): void {
    require_login();
    
    if (!has_role($role)) {
        http_response_code(403);
        
        // Per richieste AJAX
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Forbidden: permessi insufficienti']);
            exit;
        }
        
        // Pagina errore 403
        $pageTitle = 'Accesso Negato';
        $errorMessage = 'Non hai i permessi necessari per accedere a questa pagina.<br>';
        $errorMessage .= 'Ruolo richiesto: <strong>' . htmlspecialchars($role) . '</strong>';
        $errorMessage .= '<br>Il tuo ruolo: <strong>' . htmlspecialchars(current_role() ?? 'nessuno') . '</strong>';
        
        include BASE_PATH . '/error/403.php';
        exit;
    }
}

/**
 * Verifica che l'applicazione sia installata
 */
function require_installed(): void {
    if (!is_installed()) {
        header('Location: ' . APP_URL . '/migrations/start.php');
        exit;
    }
}

/**
 * Verifica che l'applicazione NON sia installata (per setup iniziale)
 */
function require_not_installed(): void {
    if (is_installed()) {
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }
}

/**
 * Middleware: richiede ruolo admin o superiore
 */
function require_admin(): void {
    require_role(ROLE_ADMIN);
}

/**
 * Middleware: richiede ruolo god
 */
function require_god(): void {
    require_role(ROLE_GOD);
}

/**
 * Verifica e valida richiesta POST
 * 
 * @param array $requiredFields Campi richiesti
 * @return array Dati validati
 */
function require_post(array $requiredFields = []): array {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        
        if (is_ajax_request()) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        
        die('Metodo non consentito');
    }
    
    // Verifica CSRF se presente nel form
    if (isset($_POST['csrf_token'])) {
        require_csrf($_POST['csrf_token']);
    }
    
    // Verifica campi richiesti
    $errors = [];
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
            $errors[] = "Campo '$field' richiesto";
        }
    }
    
    if (!empty($errors)) {
        http_response_code(400);
        
        if (is_ajax_request()) {
            header('Content-Type: application/json');
            echo json_encode(['error' => implode(', ', $errors)]);
            exit;
        }
        
        die(implode(', ', $errors));
    }
    
    return $_POST;
}

/**
 * Verifica se richiesta è AJAX
 */
function is_ajax_request(): bool {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Invia risposta JSON
 */
function json_response(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Invia risposta JSON di successo
 */
function json_success(string $message, ?array $data = null): void {
    json_response([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
}

/**
 * Invia risposta JSON di errore
 */
function json_error(string $message, int $statusCode = 400): void {
    json_response([
        'success' => false,
        'error' => $message
    ], $statusCode);
}
