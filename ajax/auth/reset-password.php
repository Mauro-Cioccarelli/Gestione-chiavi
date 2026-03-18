<?php
/**
 * AJAX: Richiesta reset password
 */

define('APP_ROOT', true);
require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metodo non consentito']);
    exit;
}

require_csrf($_POST['csrf_token'] ?? null);

$username = sanitize_string($_POST['username'] ?? '');

if (empty($username)) {
    http_response_code(400);
    echo json_encode(['error' => 'Username non valido']);
    exit;
}

// Genera token
$result = generate_password_reset_token($username);

if ($result['success']) {
    // Se è stato generato un token, invia email
    if (isset($result['token']) && isset($result['username'])) {
        $resetLink = APP_URL . '/utenti/reset-password-confirm.php?token=' . $result['token'];

        // Recupera email dell'utente per inviare email
        $db = db();
        $stmt = $db->prepare("SELECT email FROM users WHERE username = ?");
        $stmt->execute([$result['username']]);
        $userData = $stmt->fetch();
        $email = $userData['email'] ?? '';

        if (!empty($email)) {
            $emailBody = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #0d6efd; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f8f9fa; padding: 30px; border: 1px solid #dee2e6; }
                .button { display: inline-block; background: #0d6efd; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; padding: 20px; font-size: 12px; color: #6c757d; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Reset Password</h1>
                </div>
                <div class="content">
                    <p>Ciao <strong>' . htmlspecialchars($result['username']) . '</strong>,</p>
                    <p>Abbiamo ricevuto una richiesta per reimpostare la tua password.</p>
                    <p>Clicca sul pulsante qui sotto per creare una nuova password:</p>
                    <p style="text-align: center;">
                        <a href="' . htmlspecialchars($resetLink) . '" class="button">Reimposta Password</a>
                    </p>
                    <p>Oppure copia e incolla questo link nel browser:</p>
                    <p style="word-break: break-all; background: #e9ecef; padding: 10px; border-radius: 4px;">' . htmlspecialchars($resetLink) . '</p>
                    <p><strong>Questo link scadrà tra 1 ora.</strong></p>
                    <p>Se non hai richiesto il reset, ignora questa email.</p>
                </div>
                <div class="footer">
                    <p>' . htmlspecialchars(APP_NAME) . ' - Non rispondere a questa email</p>
                </div>
            </div>
        </body>
        </html>
        ';

            send_email($email, 'Reset Password - ' . APP_NAME, $emailBody);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Se l\'utente è registrato, riceverai le istruzioni per il reset'
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => $result['error']]);
}
