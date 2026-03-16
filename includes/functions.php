<?php
/**
 * Funzioni utility generali
 */

// Prevenire accesso diretto
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Accesso diretto non consentito');
}

// Costanti stato chiavi
define('KEY_AVAILABLE',   'available');
define('KEY_IN_DELIVERY', 'in_delivery');
define('KEY_DISMISED',    'dismised');

/**
 * Verifica se l'applicazione è installata (esiste il file .installed)
 */
function is_installed(): bool {
    return file_exists(BASE_PATH . '/.installed');
}

/**
 * Redirect sicuro
 */
function redirect(string $url): void {
    // Prevenire redirect aperti
    if (filter_var($url, FILTER_VALIDATE_URL) === false && strpos($url, '/') !== 0) {
        $url = APP_URL . '/' . ltrim($url, '/');
    }
    header('Location: ' . $url);
    exit;
}

/**
 * Formatta data per visualizzazione (italiano)
 * Le date nel database sono già nel timezone configurato (TIMEZONE)
 */
function format_date(?string $date, string $format = 'd/m/Y'): string {
    if (!$date || $date === '0000-00-00 00:00:00' || $date === '0000-00-00') {
        return '-';
    }

    try {
        $dt = new DateTime($date);
        return $dt->format($format);
    } catch (Exception $e) {
        return $date;
    }
}

/**
 * Formatta datetime per visualizzazione (italiano)
 * Le date nel database sono già nel timezone configurato (TIMEZONE)
 */
function format_datetime(?string $datetime): string {
    if (!$datetime || $datetime === '0000-00-00 00:00:00') {
        return '-';
    }

    try {
        $dt = new DateTime($datetime);
        return $dt->format('d/m/Y H:i');
    } catch (Exception $e) {
        return $datetime;
    }
}

/**
 * Formatta data relativa (es: "2 ore fa", "ieri")
 * Le date nel database sono già nel timezone configurato (TIMEZONE)
 */
function format_time_ago(?string $datetime): string {
    if (!$datetime) return '-';

    try {
        $dt = new DateTime($datetime);
        $now = new DateTime();
        $diff = $now->diff($dt);
        
        if ($diff->days === 0) {
            if ($diff->h > 0) {
                return $diff->h . ' ora/e fa';
            }
            if ($diff->i > 0) {
                return $diff->i . ' minuto/i fa';
            }
            return 'Adesso';
        }
        
        if ($diff->days === 1) {
            return 'Ieri';
        }
        
        if ($diff->days < 7) {
            return $diff->days . ' giorno/i fa';
        }
        
        if ($diff->days < 30) {
            return round($diff->days / 7) . ' settimana/e fa';
        }
        
        return $dt->format('d/m/Y');
        
    } catch (Exception $e) {
        return $datetime;
    }
}

/**
 * Tronca stringa a lunghezza massima
 */
function truncate(string $string, int $length = 50, string $suffix = '...'): string {
    if (strlen($string) <= $length) {
        return $string;
    }
    return substr($string, 0, $length - strlen($suffix)) . $suffix;
}

/**
 * Genera URL sicuro
 */
function url(string $path): string {
    return APP_URL . '/' . ltrim($path, '/');
}

/**
 * Ottieni IP client (considera proxy)
 */
function get_client_ip(): string {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    
    // Controlla header per proxy
    $headers = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED'];
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = explode(',', $_SERVER[$header])[0];
            break;
        }
    }
    
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

/**
 * Ottieni user agent
 */
function get_user_agent(): string {
    return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
}

/**
 * Verifica se richiesta è HTTPS
 */
function is_https(): bool {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
           (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
}

/**
 * Log custom (file separato)
 */
function custom_log(string $message, string $level = 'info'): void {
    $logFile = BASE_PATH . '/logs/' . date('Y-m-d') . '.log';
    
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $ip = get_client_ip();
    $userId = current_user_id() ?? 'guest';
    
    $logEntry = "[$timestamp] [$level] [IP:$ip] [User:$userId] $message" . PHP_EOL;
    
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

/**
 * Pulisci input da caratteri pericolosi
 */
function clean_input(string $input): string {
    $search = ["\\", "\x00", "\n", "\r", "'", '"', "\x1a"];
    $replace = ["\\\\", "\\0", "\\n", "\\r", "\'", '\"', "\\Z"];
    return str_replace($search, $replace, $input);
}

/**
 * Genera slug da stringa
 */
function slugify(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'n-a' : $text;
}

/**
 * Formatta numero con separatori italiani
 */
function format_number(float|int $number, int $decimals = 0): string {
    return number_format($number, $decimals, ',', '.');
}

/**
 * Formatta dimensione file (bytes a human readable)
 */
function format_file_size(int $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Verifica se stringa contiene solo caratteri alfanumerici
 */
function is_alphanumeric(string $string): bool {
    return ctype_alnum($string);
}

/**
 * Verifica se stringa è JSON valido
 */
function is_json(string $string): bool {
    json_decode($string);
    return json_last_error() === JSON_ERROR_NONE;
}

/**
 * Ottieni estensione file da nome
 */
function get_file_extension(string $filename): string {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Verifica se file è immagine
 */
function is_image_file(string $filename): bool {
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'];
    return in_array(get_file_extension($filename), $allowed);
}
