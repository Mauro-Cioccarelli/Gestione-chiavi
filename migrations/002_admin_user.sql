-- ============================================================================
-- 002_admin_user.sql
-- Inserimento utente admin iniziale
-- ============================================================================

-- Utente admin con password "admin" (da cambiare al primo accesso)
-- Password hash generato con: password_hash('admin', PASSWORD_DEFAULT)
INSERT INTO users (id, username, email, password_hash, role, force_password_change, created_at)
VALUES (
    1,
    'admin',
    'admin@localhost',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'god',
    1,
    NOW()
)
ON DUPLICATE KEY UPDATE 
    username = VALUES(username),
    email = VALUES(email),
    role = VALUES(role),
    force_password_change = VALUES(force_password_change);
