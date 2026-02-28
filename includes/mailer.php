<?php
/**
 * Invio email con PHPMailer
 */

// Prevenire accesso diretto
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Accesso diretto non consentito');
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Invia email usando PHPMailer
 *
 * @param string $to Destinatario
 * @param string $subject Oggetto
 * @param string $body Corpo HTML
 * @param array $attachments Allegati opzionali [['path' => ..., 'name' => ...]]
 * @return array ['success' => bool, 'error' => ?string]
 */
function send_email(string $to, string $subject, string $body, array $attachments = []): array {
    try {
        $mail = new PHPMailer(true);

        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = SMTP_AUTH;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;

        // Charset
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';

        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        // Allegati
        foreach ($attachments as $attachment) {
            if (isset($attachment['path'])) {
                $name = $attachment['name'] ?? basename($attachment['path']);
                $mail->addAttachment($attachment['path'], $name);
            }
        }

        $mail->send();
        return ['success' => true];

    } catch (Exception $e) {
        error_log("Email sending error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Errore invio email: ' . $e->getMessage()];
    }
}
