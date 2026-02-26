<?php
/**
 * Errore 403 - Accesso Negato
 */
http_response_code(403);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Accesso Negato</title>
    <link rel="stylesheet" href="/assets/bootstrap-5.3.8-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/bootstrap-icons-1.13.1/font/bootstrap-icons.min.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .error-card {
            max-width: 500px;
            text-align: center;
            color: white;
        }
        .error-code {
            font-size: 6rem;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body>
    <div class="error-card p-4">
        <i class="bi bi-lock-fill" style="font-size: 4rem;"></i>
        <div class="error-code">403</div>
        <h3>Accesso Negato</h3>
        <p class="lead">Non hai i permessi necessari per accedere a questa risorsa.</p>
        <a href="/login.php" class="btn btn-light btn-lg mt-3">
            <i class="bi bi-house me-2"></i>Torna al Login
        </a>
    </div>
</body>
</html>
