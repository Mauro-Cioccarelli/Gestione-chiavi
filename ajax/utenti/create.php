<?php
/**
 * AJAX: Crea nuovo utente
 */

define('APP_ROOT', true);
require_once __DIR__ . '/../../includes/bootstrap.php';

require_admin();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metodo non consentito']);
    exit;
}

require_csrf($_POST['csrf_token'] ?? null);

$db = db();
$input = $_POST;

// Validazione
$validator = new Validator($input);
$validator
    ->required('username', 'Username')
    ->required('email', 'Email')
    ->required('password', 'Password')
    ->email('email', 'Email')
    ->password('password', 'Password')
    ->in('role', 'Ruolo', ['operator', 'admin']);

if (!$validator->validate()) {
    http_response_code(400);
    echo json_encode(['error' => $validator->firstError()]);
    exit;
}

$username = sanitize_string($input['username']);
$email = sanitize_email($input['email']);
$password = $input['password'];
$role = $input['role'] ?? ROLE_OPERATOR;

// Verifica username esistente (deve essere univoco)
$stmt = $db->prepare("SELECT id FROM users WHERE username = ? AND deleted_at IS NULL");
$stmt->execute([$username]);
if ($stmt->fetch()) {
    http_response_code(400);
    echo json_encode(['error' => 'Username già esistente']);
    exit;
}

// Email può essere duplicata - nessun controllo di univocità

// Crea utente
$result = register_user([
    'username' => $username,
    'email' => $email,
    'password' => $password,
    'role' => $role
]);

if ($result['success']) {
    echo json_encode([
        'success' => true,
        'message' => 'Utente creato con successo',
        'data' => ['id' => $result['user_id']]
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => $result['error']]);
}
