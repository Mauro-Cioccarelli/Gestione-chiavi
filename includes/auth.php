<?php
/**
 * Funzioni di autenticazione e autorizzazione
 */

// Prevenire accesso diretto
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Accesso diretto non consentito');
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/logger.php';

/**
 * Autentica utente con username e password
 * 
 * @param string $username Username
 * @param string $password Password (in chiaro)
 * @return array ['success' => bool, 'error' => ?string, 'force_password_change' => bool, 'role' => ?string]
 */
function authenticate(string $username, string $password): array {
    $db = db();
    
    try {
        $stmt = $db->prepare("
            SELECT id, username, email, password_hash, role, force_password_change, deleted_at
            FROM users
            WHERE username = ? AND deleted_at IS NULL
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if (!$user) {
            audit_log('login_failed', 'user', null, ['username' => $username], 'Username non trovato');
            return ['success' => false, 'error' => 'Credenziali non valide'];
        }
        
        // Verifica password
        $passwordValid = false;
        
        // Verifica con password_hash (nuovo sistema)
        if (password_verify($password, $user['password_hash'])) {
            $passwordValid = true;
        } 
        // Fallback per MD5 legacy (solo durante migrazione)
        elseif (strlen($user['password_hash']) === 32 && md5($password) === $user['password_hash']) {
            $passwordValid = true;
            // Migra a password_hash
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $update = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $update->execute([$newHash, $user['id']]);
        }
        
        if (!$passwordValid) {
            audit_log('login_failed', 'user', $user['id'], ['username' => $username], 'Password errata');
            return ['success' => false, 'error' => 'Credenziali non valide'];
        }
        
        // Aggiorna last_login
        $update = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $update->execute([$user['id']]);
        
        // Set sessione
        session_set_user($user);
        
        audit_log('login_success', 'user', $user['id'], ['username' => $username]);
        
        return [
            'success' => true,
            'force_password_change' => (bool)$user['force_password_change'],
            'role' => $user['role']
        ];
        
    } catch (PDOException $e) {
        error_log("Auth error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Errore di sistema'];
    }
}

/**
 * Logout utente
 */
function logout(): void {
    $userId = current_user_id();
    $username = current_username();
    
    audit_log('logout', 'user', $userId, ['username' => $username]);
    
    session_destroy_secure();
}

/**
 * Registra nuovo utente
 * 
 * @param array $data Dati utente ['username', 'email', 'password', 'role']
 * @return array ['success' => bool, 'error' => ?string, 'user_id' => ?int]
 */
function register_user(array $data): array {
    $db = db();

    try {
        // Verifica username esistente (esclusi eliminati)
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ? AND deleted_at IS NULL");
        $stmt->execute([$data['username']]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'Username già esistente'];
        }

        // Email può essere duplicata - nessun controllo di univocità

        // Hash password
        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);

        // Inserisci utente con force_password_change = 1 (cambio password al primo accesso)
        $stmt = $db->prepare("
            INSERT INTO users (username, email, password_hash, role, force_password_change, created_at)
            VALUES (?, ?, ?, ?, 1, NOW())
        ");
        $stmt->execute([
            $data['username'],
            $data['email'],
            $passwordHash,
            $data['role'] ?? ROLE_OPERATOR
        ]);
        
        $userId = (int)$db->lastInsertId();
        
        audit_log('user_created', 'user', $userId, ['username' => $data['username']]);
        
        return ['success' => true, 'user_id' => $userId];
        
    } catch (PDOException $e) {
        error_log("Register error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Errore di sistema'];
    }
}

/**
 * Aggiorna password utente
 * 
 * @param int $userId ID utente
 * @param string $newPassword Nuova password
 * @return array ['success' => bool, 'error' => ?string]
 */
function update_password(int $userId, string $newPassword): array {
    $db = db();
    
    try {
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("
            UPDATE users 
            SET password_hash = ?, force_password_change = 0, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$passwordHash, $userId]);
        
        audit_log('password_changed', 'user', $userId);
        
        return ['success' => true];
        
    } catch (PDOException $e) {
        error_log("Password update error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Errore di sistema'];
    }
}

/**
 * Verifica vecchia password e aggiorna
 * 
 * @param int $userId ID utente
 * @param string $oldPassword Vecchia password (in chiaro)
 * @param string $newPassword Nuova password (in chiaro)
 * @return array ['success' => bool, 'error' => ?string]
 */
function change_password(int $userId, string $oldPassword, string $newPassword): array {
    $db = db();
    
    try {
        // Recupera hash corrente
        $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'error' => 'Utente non trovato'];
        }
        
        // Verifica vecchia password
        if (!password_verify($oldPassword, $user['password_hash'])) {
            return ['success' => false, 'error' => 'Password attuale non corretta'];
        }
        
        return update_password($userId, $newPassword);
        
    } catch (PDOException $e) {
        error_log("Change password error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Errore di sistema'];
    }
}

/**
 * Genera token per reset password
 *
 * @param string $username Username utente
 * @return array ['success' => bool, 'error' => ?string, 'token' => ?string]
 */
function generate_password_reset_token(string $username): array {
    $db = db();

    try {
        $stmt = $db->prepare("SELECT id, username FROM users WHERE LOWER(username) = LOWER(?) AND deleted_at IS NULL");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user) {
            // Non rivelare se username esiste o meno (security by obscurity)
            return ['success' => true, 'message' => 'Se l\'utente è registrato, riceverai le istruzioni'];
        }

        // Genera token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $stmt = $db->prepare("
            UPDATE users
            SET password_reset_token = ?, password_reset_expires = ?
            WHERE id = ?
        ");
        $stmt->execute([$token, $expires, $user['id']]);

        audit_log('password_reset_requested', 'user', $user['id'], ['username' => $user['username']]);

        // In produzione: inviare email con link
        // Per ora, ritorniamo il token (solo sviluppo)
        return [
            'success' => true,
            'token' => $token,
            'username' => $user['username'],
            'message' => 'Token generato (in produzione verrà inviata un\'email)'
        ];

    } catch (PDOException $e) {
        error_log("Reset token error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Errore di sistema'];
    }
}

/**
 * Reset password con token
 * 
 * @param string $token Token di reset
 * @param string $newPassword Nuova password
 * @return array ['success' => bool, 'error' => ?string]
 */
function reset_password_with_token(string $token, string $newPassword): array {
    $db = db();
    
    try {
        $stmt = $db->prepare("
            SELECT id FROM users 
            WHERE password_reset_token = ? 
            AND password_reset_expires > NOW()
            AND deleted_at IS NULL
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'error' => 'Token non valido o scaduto'];
        }
        
        $result = update_password($user['id'], $newPassword);
        
        if ($result['success']) {
            // Invalida token
            $stmt = $db->prepare("
                UPDATE users 
                SET password_reset_token = NULL, password_reset_expires = NULL
                WHERE id = ?
            ");
            $stmt->execute([$user['id']]);
        }
        
        return $result;
        
    } catch (PDOException $e) {
        error_log("Reset password error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Errore di sistema'];
    }
}

/**
 * Ottieni tutti gli utenti (per admin)
 */
function get_all_users(): array {
    $db = db();
    
    $stmt = $db->query("
        SELECT id, username, email, role, force_password_change, last_login, created_at
        FROM users
        WHERE deleted_at IS NULL
        ORDER BY username ASC
    ");
    
    return $stmt->fetchAll();
}

/**
 * Ottieni utente per ID
 */
function get_user_by_id(int $id): ?array {
    $db = db();
    
    $stmt = $db->prepare("
        SELECT id, username, email, role, force_password_change, last_login, created_at
        FROM users
        WHERE id = ? AND deleted_at IS NULL
    ");
    $stmt->execute([$id]);
    
    return $stmt->fetch() ?: null;
}

/**
 * Aggiorna utente (admin)
 */
function update_user(int $userId, array $data): array {
    $db = db();
    
    try {
        $fields = [];
        $params = [];
        
        if (isset($data['email'])) {
            $fields[] = "email = ?";
            $params[] = $data['email'];
        }
        
        if (isset($data['role'])) {
            $fields[] = "role = ?";
            $params[] = $data['role'];
        }
        
        if (isset($data['force_password_change'])) {
            $fields[] = "force_password_change = ?";
            $params[] = $data['force_password_change'] ? 1 : 0;
        }
        
        if (empty($fields)) {
            return ['success' => false, 'error' => 'Nessun campo da aggiornare'];
        }
        
        $fields[] = "updated_at = NOW()";
        $params[] = $userId;
        
        $stmt = $db->prepare("UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?");
        $stmt->execute($params);
        
        audit_log('user_updated', 'user', $userId, $data);
        
        return ['success' => true];
        
    } catch (PDOException $e) {
        error_log("Update user error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Errore di sistema'];
    }
}

/**
 * Elimina utente (soft delete)
 */
function delete_user(int $userId): array {
    $db = db();

    try {
        // Non permettere eliminazione di se stessi
        if ($userId === current_user_id()) {
            return ['success' => false, 'error' => 'Non puoi eliminare il tuo account'];
        }

        // Ottieni username prima di eliminare
        $stmt = $db->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        $deletedUsername = $user ? $user['username'] : 'Sconosciuto';

        $stmt = $db->prepare("UPDATE users SET deleted_at = NOW() WHERE id = ?");
        $stmt->execute([$userId]);

        audit_log('user_deleted', 'user', $userId, [
            'deleted_user' => $deletedUsername
        ]);

        return ['success' => true];

    } catch (PDOException $e) {
        error_log("Delete user error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Errore di sistema'];
    }
}

/**
 * Ripristina utente eliminato (soft delete restore)
 *
 * @param int $userId ID utente da ripristinare
 * @return array ['success' => bool, 'error' => ?string]
 */
function restore_user(int $userId): array {
    $db = db();

    try {
        // Verifica utente esistente ed eliminato
        $stmt = $db->prepare("SELECT id, username FROM users WHERE id = ? AND deleted_at IS NOT NULL");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'error' => 'Utente non trovato o non eliminato'];
        }

        // Verifica che lo username non sia già in uso da un utente attivo
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ? AND deleted_at IS NULL AND id != ?");
        $stmt->execute([$user['username'], $userId]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'Username già in uso da un altro utente attivo'];
        }

        // Ripristina utente
        $stmt = $db->prepare("UPDATE users SET deleted_at = NULL WHERE id = ?");
        $stmt->execute([$userId]);

        audit_log('user_restored', 'user', $userId, ['username' => $user['username']]);

        return ['success' => true];

    } catch (PDOException $e) {
        error_log("Restore user error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Errore di sistema'];
    }
}
