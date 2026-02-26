<?php
/**
 * Validazione e sanitizzazione input
 */

// Prevenire accesso diretto
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Accesso diretto non consentito');
}

/**
 * Sanitizza stringa (rimuove tag HTML e fa escape)
 */
function sanitize_string(string $input): string {
    return trim(htmlspecialchars(strip_tags($input), ENT_QUOTES, 'UTF-8'));
}

/**
 * Sanitizza email
 */
function sanitize_email(string $email): string {
    return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
}

/**
 * Valida email
 */
function is_valid_email(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Valida intero
 * 
 * @param mixed $value Valore da validare
 * @param int|null $min Valore minimo (opzionale)
 * @param int|null $max Valore massimo (opzionale)
 */
function is_valid_int(mixed $value, ?int $min = null, ?int $max = null): bool {
    if (!is_numeric($value) || (int)$value != $value) {
        return false;
    }
    $int = (int)$value;
    if ($min !== null && $int < $min) return false;
    if ($max !== null && $int > $max) return false;
    return true;
}

/**
 * Valida password
 * 
 * @return array ['valid' => bool, 'errors' => array]
 */
function is_valid_password(string $password): array {
    $errors = [];
    
    if (strlen($password) < MIN_PASSWORD_LENGTH) {
        $errors[] = "La password deve essere di almeno " . MIN_PASSWORD_LENGTH . " caratteri";
    }
    
    // Opzionale: richiedi almeno una maiuscola, un numero, etc.
    // if (!preg_match('/[A-Z]/', $password)) $errors[] = "La password deve contenere almeno una lettera maiuscola";
    // if (!preg_match('/[0-9]/', $password)) $errors[] = "La password deve contenere almeno un numero";
    
    return ['valid' => empty($errors), 'errors' => $errors];
}

/**
 * Valida stringa non vuota
 */
function is_valid_string(?string $value, int $minLength = 1, ?int $maxLength = null): bool {
    if ($value === null || $value === '') return false;
    $len = strlen($value);
    if ($len < $minLength) return false;
    if ($maxLength !== null && $len > $maxLength) return false;
    return true;
}

/**
 * Valida data (formato Y-m-d)
 */
function is_valid_date(string $date, string $format = 'Y-m-d'): bool {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Valida datetime (formato Y-m-d H:i:s)
 */
function is_valid_datetime(string $datetime): bool {
    $d = DateTime::createFromFormat('Y-m-d H:i:s', $datetime);
    return $d && $d->format('Y-m-d H:i:s') === $datetime;
}

/**
 * Classe per validazione form
 * 
 * Esempio:
 *   $validator = new Validator($_POST);
 *   $validator
 *       ->required('username', 'Username')
 *       ->email('email', 'Email')
 *       ->minLength('password', 'Password', 6);
 *   
 *   if (!$validator->validate()) { ... }
 */
class Validator {
    private array $data;
    private array $errors = [];
    private array $sanitized = [];
    
    public function __construct(array $data) {
        $this->data = $data;
    }
    
    /**
     * Campo obbligatorio
     */
    public function required(string $field, string $label): self {
        if (!isset($this->data[$field]) || trim($this->data[$field]) === '') {
            $this->errors[$field] = "$label è obbligatorio";
        }
        return $this;
    }
    
    /**
     * Campo email
     */
    public function email(string $field, string $label): self {
        if (!empty($this->data[$field]) && !is_valid_email($this->data[$field])) {
            $this->errors[$field] = "$label non è un'email valida";
        }
        return $this;
    }
    
    /**
     * Campo intero
     */
    public function int(string $field, string $label, ?int $min = null, ?int $max = null): self {
        if (!empty($this->data[$field]) && !is_valid_int($this->data[$field], $min, $max)) {
            $this->errors[$field] = "$label non è un numero valido";
        }
        return $this;
    }
    
    /**
     * Lunghezza minima stringa
     */
    public function minLength(string $field, string $label, int $length): self {
        if (!empty($this->data[$field]) && strlen($this->data[$field]) < $length) {
            $this->errors[$field] = "$label deve essere di almeno $length caratteri";
        }
        return $this;
    }
    
    /**
     * Lunghezza massima stringa
     */
    public function maxLength(string $field, string $label, int $length): self {
        if (!empty($this->data[$field]) && strlen($this->data[$field]) > $length) {
            $this->errors[$field] = "$label deve essere di massimo $length caratteri";
        }
        return $this;
    }
    
    /**
     * Campo password
     */
    public function password(string $field, string $label): self {
        if (!empty($this->data[$field])) {
            $result = is_valid_password($this->data[$field]);
            if (!$result['valid']) {
                $this->errors[$field] = implode(', ', $result['errors']);
            }
        }
        return $this;
    }
    
    /**
     * Campo deve corrispondere a un altro (es: conferma password)
     */
    public function matches(string $field, string $label, string $otherField, string $otherLabel): self {
        if (isset($this->data[$field]) && isset($this->data[$otherField])) {
            if ($this->data[$field] !== $this->data[$otherField]) {
                $this->errors[$field] = "$label non corrisponde a $otherLabel";
            }
        }
        return $this;
    }
    
    /**
     * Campo deve essere uno dei valori consentiti
     */
    public function in(string $field, string $label, array $allowed): self {
        if (!empty($this->data[$field]) && !in_array($this->data[$field], $allowed, true)) {
            $this->errors[$field] = "$label non è un valore valido";
        }
        return $this;
    }
    
    /**
     * Campo data
     */
    public function date(string $field, string $label, string $format = 'Y-m-d'): self {
        if (!empty($this->data[$field]) && !is_valid_date($this->data[$field], $format)) {
            $this->errors[$field] = "$label non è una data valida";
        }
        return $this;
    }
    
    /**
     * Sanitizza campo come stringa
     */
    public function sanitize(string $field): self {
        if (isset($this->data[$field])) {
            $this->sanitized[$field] = sanitize_string($this->data[$field]);
        }
        return $this;
    }
    
    /**
     * Sanitizza campo come email
     */
    public function sanitizeEmail(string $field): self {
        if (isset($this->data[$field])) {
            $this->sanitized[$field] = sanitize_email($this->data[$field]);
        }
        return $this;
    }
    
    /**
     * Verifica se validazione è passata
     */
    public function validate(): bool {
        return empty($this->errors);
    }
    
    /**
     * Ottieni tutti gli errori
     */
    public function errors(): array {
        return $this->errors;
    }
    
    /**
     * Ottieni primo errore
     */
    public function firstError(): ?string {
        return empty($this->errors) ? null : reset($this->errors);
    }
    
    /**
     * Ottieni valore sanitizzato
     */
    public function getSanitized(string $field, $default = null) {
        return $this->sanitized[$field] ?? $default;
    }
    
    /**
     * Ottieni tutti i valori sanitizzati
     */
    public function getSanitizedData(): array {
        return $this->sanitized;
    }
    
    /**
     * Ottieni valore originale (non sanitizzato)
     */
    public function get(string $field, $default = null) {
        return $this->data[$field] ?? $default;
    }
}
