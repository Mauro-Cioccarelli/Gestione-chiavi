<?php
/**
 * Configurazione applicazione Chiavi
 *
 * Copia questo file in config.local.php e personalizza i valori.
 * config.local.php è ignorato da git e non dovrebbe essere committato.
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Accesso diretto non consentito');
}

// =============================================================================
// DATABASE
// =============================================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'chiavi');
define('DB_CHARSET', 'utf8mb4');

// =============================================================================
// URL E PERCORSI
// =============================================================================

// URL base dell'applicazione (senza trailing slash)
define('APP_URL', 'http://chiavi.test');

// Percorso assoluto filesystem (senza trailing slash)
define('BASE_PATH', dirname(__DIR__));

// =============================================================================
// SESSIONE E SICUREZZA
// =============================================================================

// Nome della sessione PHP
define('SESSION_NAME', 'CHIAVI_SESSION');

// Durata sessione in secondi (default: 2 ore)
define('SESSION_LIFETIME', 7200);

// Timeout inattività in secondi (default: 30 minuti)
define('SESSION_TIMEOUT', 1800);

// Segreto per CSRF e hashing (genera stringa casuale unica per installazione)
define('APP_SECRET', 'cambia-questo-segreto-con-una-stringa-casuale-unica');

// =============================================================================
// RUOLI UTENTE
// =============================================================================

define('ROLE_OPERATOR', 'operator');
define('ROLE_ADMIN', 'admin');
define('ROLE_GOD', 'god');

// Ruolo default per nuovi utenti
define('DEFAULT_ROLE', ROLE_OPERATOR);

// =============================================================================
// EMAIL (SMTP)
// =============================================================================

// Abilita/disabilita invio email
define('MAIL_ENABLED', false);

// Configurazione SMTP
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USER', 'tua.email@gmail.com');
define('MAIL_PASS', 'tua-password-app');
define('MAIL_FROM', 'noreply@chiavi.test');
define('MAIL_FROM_NAME', 'Chiavi App');
define('MAIL_SECURE', 'tls'); // 'tls' o 'ssl'

// =============================================================================
// LOGGING
// =============================================================================

// Livello di logging: 0=off, 1=error, 2=warning, 3=info, 4=debug
define('LOG_LEVEL', 3);

// Percorso file di log (assicurati che sia scrivibile)
define('LOG_FILE', BASE_PATH . '/logs/app.log');

// =============================================================================
// VARIE
// =============================================================================

// Timezone dell'applicazione
define('APP_TIMEZONE', 'Europe/Rome');

// Locale per formattazione date/numeri
define('APP_LOCALE', 'it_IT');

// Formato data italiano
define('DATE_FORMAT', 'd/m/Y');
define('DATETIME_FORMAT', 'd/m/Y H:i');
define('TIMESTAMP_FORMAT', 'd/m/Y H:i:s');

// =============================================================================
// CARICA CONFIGURAZIONE LOCALE (OPZIONALE)
// =============================================================================
// Se esiste config.local.php, sovrascrivi i valori definiti sopra

$localConfig = __DIR__ . '/config.local.php';
if (file_exists($localConfig)) {
    include $localConfig;
}
