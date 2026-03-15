<?php
/**
 * Configurazione globale applicazione - Gestione Chiavi
 * 
 * @version 2.0.0
 */

// Prevenire accesso diretto
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Accesso diretto non consentito');
}

// Error reporting per debug (PRODUZIONE - da disabilitare dopo)
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../error_php.log');
error_reporting(E_ALL);

// Timezone
define('TIMEZONE', 'Europe/Rome');
date_default_timezone_set(TIMEZONE);

// Encoding
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// Sessioni
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // HTTPS attivo
ini_set('session.use_strict_mode', 1);
ini_set('session.gc_maxlifetime', 10400);

// Costanti percorso
define('BASE_PATH', dirname(__DIR__));
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('ASSETS_URL', '/assets');
define('APP_URL', ''); // Viene sovrascritto da config.local.php se esiste

// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'chiavi');
define('DB_USER', 'agenzi43');
define('DB_PASS', '00054266');
define('DB_CHARSET', 'utf8mb4');

// Applicazione
define('APP_NAME', 'Gestione Chiavi');
define('APP_VERSION', '2.0.0');
define('MIN_PASSWORD_LENGTH', 6);

// Ruoli utente
define('ROLE_OPERATOR', 'operator');
define('ROLE_ADMIN', 'admin');
define('ROLE_GOD', 'god');

// Stato chiavi
define('KEY_AVAILABLE', 'available');
define('KEY_IN_DELIVERY', 'in_delivery');
define('KEY_DISMISED', 'dismised');

// Azioni movimento
define('ACTION_CHECKOUT', 'checkout');
define('ACTION_CHECKIN', 'checkin');
define('ACTION_CREATE', 'create');
define('ACTION_UPDATE', 'update');
define('ACTION_DISMISE', 'dismise');

// Flag installazione
define('INSTALLED_FILE', BASE_PATH . '/.installed');

// Configurazione SMTP per invio email
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls'); // 'tls' o 'ssl'
define('SMTP_AUTH', true);
define('SMTP_USERNAME', ''); // Da configurare
define('SMTP_PASSWORD', ''); // Da configurare
define('SMTP_FROM_EMAIL', 'noreply@chiavi.test');
define('SMTP_FROM_NAME', 'Gestione Chiavi');

// =============================================================================
// CARICA CONFIGURAZIONE LOCALE (OPZIONALE)
// =============================================================================
// Se esiste config.local.php nella root, sovrascrivi i valori definiti sopra
// Questo file è ignorato da git e contiene configurazioni specifiche per ambiente

$localConfig = dirname(__DIR__) . '/config.local.php';
if (file_exists($localConfig)) {
    include $localConfig;
}

/**
 * Verifica se applicazione è installata
 */
function is_installed(): bool {
    return file_exists(INSTALLED_FILE);
}
