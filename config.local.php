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

defined('DB_HOST') || define('DB_HOST', 'localhost');
defined('DB_USER') || define('DB_USER', 'root');
defined('DB_PASS') || define('DB_PASS', '00054266');
defined('DB_NAME') || define('DB_NAME', 'chiavi');
defined('DB_CHARSET') || define('DB_CHARSET', 'utf8mb4');

// =============================================================================
// URL E PERCORSI
// =============================================================================

defined('APP_URL') || define('APP_URL', 'http://chiavi.test');
defined('BASE_PATH') || define('BASE_PATH', dirname(__DIR__));

// =============================================================================
// SESSIONE E SICUREZZA
// =============================================================================

defined('SESSION_NAME') || define('SESSION_NAME', 'CHIAVI_SESSION');
defined('SESSION_LIFETIME') || define('SESSION_LIFETIME', 36000);
defined('SESSION_TIMEOUT') || define('SESSION_TIMEOUT', 36000);
defined('APP_SECRET') || define('APP_SECRET', bin2hex(random_bytes(32)));

// =============================================================================
// RUOLI UTENTE
// =============================================================================

defined('ROLE_OPERATOR') || define('ROLE_OPERATOR', 'operator');
defined('ROLE_ADMIN') || define('ROLE_ADMIN', 'admin');
defined('ROLE_GOD') || define('ROLE_GOD', 'god');
defined('DEFAULT_ROLE') || define('DEFAULT_ROLE', ROLE_OPERATOR);

// =============================================================================
// EMAIL (SMTP)
// =============================================================================

defined('MAIL_ENABLED') || define('MAIL_ENABLED', false);
defined('MAIL_HOST') || define('MAIL_HOST', 'smtp.gmail.com');
defined('MAIL_PORT') || define('MAIL_PORT', 587);
defined('MAIL_USER') || define('MAIL_USER', '');
defined('MAIL_PASS') || define('MAIL_PASS', '');
defined('MAIL_FROM') || define('MAIL_FROM', 'noreply@chiavi.test');
defined('MAIL_FROM_NAME') || define('MAIL_FROM_NAME', 'Chiavi App');
defined('MAIL_SECURE') || define('MAIL_SECURE', 'tls');

// =============================================================================
// LOGGING
// =============================================================================

defined('LOG_LEVEL') || define('LOG_LEVEL', 3);
defined('LOG_FILE') || define('LOG_FILE', BASE_PATH . '/logs/app.log');

// =============================================================================
// VARIE
// =============================================================================

defined('APP_TIMEZONE') || define('APP_TIMEZONE', 'Europe/Rome');
defined('APP_LOCALE') || define('APP_LOCALE', 'it_IT');
defined('DATE_FORMAT') || define('DATE_FORMAT', 'd/m/Y');
defined('DATETIME_FORMAT') || define('DATETIME_FORMAT', 'd/m/Y H:i');
defined('TIMESTAMP_FORMAT') || define('TIMESTAMP_FORMAT', 'd/m/Y H:i:s');
