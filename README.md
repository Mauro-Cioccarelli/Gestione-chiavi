# Gestione Chiavi v2.0

Applicazione web per la gestione di chiavi di condomini, con tracciamento consegne, rientri e storico movimenti.

## Requisiti

- PHP 8.2+
- MySQL 8.0+ / MariaDB 10.6+
- Apache con mod_rewrite
- Estensioni PHP: PDO, MySQL, JSON, MBString

## Installazione

### 1. Configurazione Database

Modifica il file `includes/config.php` con le credenziali del tuo database:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'chiavi_new');
define('DB_USER', 'chiavi_user');
define('DB_PASS', 'tua_password_sicura');
```

### 2. Installazione Iniziale

1. Apri il browser e naviga su: `http://tuodominio.it/chiavi.test/`
2. Verrai redirectato automaticamente a `migrations/start.php`
3. Conferma l'installazione
4. Il sistema creerà:
   - Il database
   - Tutte le tabelle necessarie
   - L'utente admin iniziale

### 3. Primo Accesso

**Credenziali admin iniziali:**
- Username: `admin`
- Password: `admin`

⚠️ **Importante:** Al primo accesso verrà richiesto di cambiare la password.

## Struttura Cartelle

```
/var/www/chiavi.test/
├── .htaccess                 # Regole Apache e sicurezza
├── index.php                 # Redirect a login
├── login.php                 # Pagina di login
├── logout.php                # Logout
├── dashboard.php             # Dashboard principale
├── chiavi/                   # Gestione chiavi
│   ├── index.php             # Inventario chiavi
│   └── storia.php            # Storico movimenti chiave
├── utenti/                   # Gestione utenti
│   ├── index.php             # Lista utenti (admin)
│   ├── profilo.php           # Profilo utente
│   ├── cambio-password.php   # Cambio password forzato
│   └── recupera-password.php # Recupero password
├── ajax/                     # Endpoint AJAX
│   ├── chiavi/               # API chiavi
│   ├── utenti/               # API utenti
│   └── auth/                 # API autenticazione
├── includes/                 # Librerie PHP
│   ├── config.php            # Configurazione
│   ├── db.php                # Connessione DB
│   ├── bootstrap.php         # Bootstrap e middleware
│   ├── auth.php              # Funzioni auth
│   ├── session.php           # Gestione sessione
│   ├── csrf.php              # Protezione CSRF
│   ├── logger.php            # Audit logging
│   ├── validator.php         # Validazione input
│   └── layout/               # Template layout
├── migrations/               # Migrazioni database
│   ├── start.php             # Installazione iniziale
│   ├── index.php             # Gestione migrazioni (god)
│   └── 0*.sql                # File SQL migrazione
├── assets/                   # Risorse statiche
│   ├── bootstrap-5.3.8-dist/
│   ├── bootstrap-icons-1.13.1/
│   ├── tabulator-master/
│   ├── css/
│   └── js/
├── error/                    # Pagine errore
└── old/                      # Legacy (da rimuovere)
```

## Ruoli Utente

| Ruolo      | Permessi |
|------------|----------|
| `operator` | Visualizzare chiavi, effettuare consegne e rientri |
| `admin`    | Tutti i permessi operator + gestione utenti |
| `god`      | Tutti i permessi admin + gestione migrazioni e ruoli |

## Stati Chiave

| Stato         | Descrizione |
|---------------|-------------|
| `available`   | Chiave disponibile in carico |
| `in_delivery` | Chiave consegnata a terzi |
| `dismised`    | Chiave dismessa (eliminata logicamente) |

## Funzionalità

### Gestione Chiavi
- ✅ Inserimento nuove chiavi con categoria
- ✅ Inventario con ricerca e filtri
- ✅ Consegna chiavi a operatori/manutentori
- ✅ Rientro chiavi con note
- ✅ Storico movimenti per chiave
- ✅ Eliminazione logica (soft delete)

### Gestione Utenti
- ✅ Creazione utenti (admin)
- ✅ Modifica ruoli e email (god)
- ✅ Forza cambio password
- ✅ Recupero password via email (token)

### Sicurezza
- ✅ Password hash con `password_hash()` (bcrypt)
- ✅ Protezione CSRF su tutti i form
- ✅ Sessioni secure (httponly, timeout)
- ✅ Audit log di tutte le operazioni
- ✅ Protezione cartelle con .htaccess
- ✅ Prepared statements (SQL injection safe)

### Migrazione da Legacy
- ✅ Script migrazione automatico da `chiavi_old`
- ✅ Import utenti, categorie, chiavi, movimenti
- ✅ Migrazione password MD5 → bcrypt (al primo login)

## API AJAX

### Chiavi
| Endpoint | Metodo | Descrizione |
|----------|--------|-------------|
| `/ajax/chiavi/list.php` | GET | Lista chiavi (Tabulator server-side) |
| `/ajax/chiavi/create.php` | POST | Crea nuova chiave |
| `/ajax/chiavi/update.php` | POST | Aggiorna chiave |
| `/ajax/chiavi/delete.php` | POST | Elimina chiave (soft) |
| `/ajax/chiavi/checkout.php` | POST | Consegna chiave |
| `/ajax/chiavi/checkin.php` | POST | Rientro chiave |
| `/ajax/chiavi/movements.php` | GET | Storico movimenti |

### Utenti
| Endpoint | Metodo | Descrizione |
|----------|--------|-------------|
| `/ajax/utenti/list.php` | GET | Lista utenti |
| `/ajax/utenti/create.php` | POST | Crea utente |
| `/ajax/utenti/update.php` | POST | Aggiorna utente |
| `/ajax/utenti/delete.php` | POST | Elimina utente |

### Auth
| Endpoint | Metodo | Descrizione |
|----------|--------|-------------|
| `/ajax/auth/login.php` | POST | Login |
| `/ajax/auth/change-password.php` | POST | Cambio password |
| `/ajax/auth/reset-password.php` | POST | Richiesta reset password |

## Database Schema

### Tabelle Principali

- `users` - Utenti del sistema
- `key_categories` - Categorie chiavi (condomini)
- `keys` - Chiavi
- `key_movements` - Movimenti chiavi (consegne/rientri)
- `audit_log` - Log audit operazioni
- `migrations` - Tracciamento migrazioni eseguite

## Log Audit

Tutte le operazioni significative vengono registrate:
- Login/logout
- Creazione/modifica/eliminazione utenti
- Creazione/modifica/eliminazione chiavi
- Consegne e rientri chiavi
- Richieste reset password

## Personalizzazione

### Logo e Branding
Modifica `includes/layout/header.php` e `sidebar.php` per personalizzare logo e nome applicazione.

### Email Reset Password
Per abilitare l'invio email per il reset password, modifica `includes/auth.php` nella funzione `generate_password_reset_token()`.

### Timeout Sessione
Modifica `includes/session.php` per cambiare il timeout (default: 1 ora).

## Troubleshooting

### Errore di connessione al database
Verifica le credenziali in `includes/config.php`.

### Pagina bianca dopo installazione
Controlla i log errori di PHP e Apache.

### CSRF token non valido
Assicurati che i cookie siano abilitati nel browser.

### Migrazione legacy fallita
Verifica che il database `chiavi_old` esista e sia accessibile.

## Licenza

Applicazione proprietaria - Tutti i diritti riservati.

## Supporto

Per assistenza tecnica, contattare l'amministratore di sistema.
