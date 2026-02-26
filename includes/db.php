<?php
/**
 * Connessione database PDO - Singleton
 */

// Prevenire accesso diretto
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Accesso diretto non consentito');
}

class Database {
    private static ?PDO $instance = null;
    
    /**
     * Ottiene istanza singola della connessione PDO
     */
    public static function getInstance(): PDO {
        if (self::$instance === null) {
            $dsn = sprintf(
                "mysql:host=%s;charset=%s",
                DB_HOST,
                DB_CHARSET
            );
            
            // Se DB_NAME è definito, aggiungilo al DSN
            if (defined('DB_NAME') && DB_NAME) {
                $dsn = sprintf(
                    "mysql:host=%s;dbname=%s;charset=%s",
                    DB_HOST,
                    DB_NAME,
                    DB_CHARSET
                );
            }
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            
            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                error_log("DB Connection Error: " . $e->getMessage());
                // Non mostrare dettagli errore in produzione
                if (defined('APP_ROOT')) {
                    http_response_code(500);
                    exit('Errore di connessione al database');
                }
                throw $e;
            }
        }
        
        return self::$instance;
    }
    
    /**
     * Ottiene istanza senza selezionare database (per creazione DB)
     */
    public static function getInstanceWithoutDb(): PDO {
        if (self::$instance === null) {
            $dsn = sprintf(
                "mysql:host=%s;charset=%s",
                DB_HOST,
                DB_CHARSET
            );
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false,
            ];
            
            self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
        }
        
        return self::$instance;
    }
    
    // Prevent cloning
    private function __clone() {}
    
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
    
    // Prevent instantiation
    private function __construct() {}
}

/**
 * Helper function per ottenere connessione
 */
function db(): PDO {
    return Database::getInstance();
}

/**
 * Helper function per ottenere connessione senza database
 */
function db_no_db(): PDO {
    return Database::getInstanceWithoutDb();
}
