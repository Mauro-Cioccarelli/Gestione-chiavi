<?php
/**
 * Errore 404 - Pagina non trovata
 */
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Pagina non trovata</title>
    <link rel="stylesheet" href="/chiavi.test/assets/bootstrap-5.3.8-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/chiavi.test/assets/bootstrap-icons-1.13.1/font/bootstrap-icons.min.css">
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
        <i class="bi bi-compass" style="font-size: 4rem;"></i>
        <div class="error-code">404</div>
        <h3>Pagina non trovata</h3>
        <p class="lead">La pagina che stai cercando non esiste o è stata spostata.</p>
        <a href="/chiavi.test/login.php" class="btn btn-light btn-lg mt-3">
            <i class="bi bi-house me-2"></i>Torna al Login
        </a>
    </div>
</body>
</html>
