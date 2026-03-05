<?php
/**
 * Configurazione locale - Chiavi
 *
 * Questo file è ignorato da git. Inserisci qui i valori specifici
 * per il tuo ambiente di sviluppo/produzione.
 */

// =============================================================================
// DATABASE
// =============================================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '00054266');
define('DB_NAME', 'chiavi');
define('DB_CHARSET', 'utf8mb4');

// =============================================================================
// URL E PERCORSI
// =============================================================================

define('APP_URL', 'http://chiavi.test');
define('BASE_PATH', dirname(__DIR__));

// =============================================================================
// SESSIONE E SICUREZZA
// =============================================================================

define('SESSION_NAME', 'CHIAVI_SESSION');
define('SESSION_LIFETIME', 36000);
define('SESSION_TIMEOUT', 36000);
define('APP_SECRET', bin2hex(random_bytes(32)));

// =============================================================================
// RUOLI UTENTE
// =============================================================================

define('ROLE_OPERATOR', 'operator');
define('ROLE_ADMIN', 'admin');
define('ROLE_GOD', 'god');
define('DEFAULT_ROLE', ROLE_OPERATOR);

// =============================================================================
// EMAIL (SMTP)
// =============================================================================

define('MAIL_ENABLED', false);
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USER', '');
define('MAIL_PASS', '');
define('MAIL_FROM', 'noreply@chiavi.test');
define('MAIL_FROM_NAME', 'Chiavi App');
define('MAIL_SECURE', 'tls');

// =============================================================================
// LOGGING
// =============================================================================

define('LOG_LEVEL', 3);
define('LOG_FILE', BASE_PATH . '/logs/app.log');

// =============================================================================
// VARIE
// =============================================================================

define('APP_TIMEZONE', 'Europe/Rome');
define('APP_LOCALE', 'it_IT');
define('DATE_FORMAT', 'd/m/Y');
define('DATETIME_FORMAT', 'd/m/Y H:i');
define('TIMESTAMP_FORMAT', 'd/m/Y H:i:s');
