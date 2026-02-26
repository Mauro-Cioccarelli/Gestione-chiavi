# Gestione Duplicati Chiavi

## Problema
Durante l'importazione dal database legacy possono esistere chiavi duplicate con stesso identificativo (es: "Chiave Rossi") e categoria.

**Situazione rilevata:**
- 69 gruppi con stati misti
- 49 gruppi con più chiavi attive duplicate
- 20 gruppi con 1 attiva + N dismesse
- 0 gruppi con chiavi in consegna (nessun dato perso)

## Soluzione Implementata

### Regola Fondamentale
**Le chiavi dismesse NON vengono MAI ripristinate in produzione.**

**In migrazione:** Duplicati vengono deduplicati con merge dello storico.

---

### 1. Durante la Migrazione (`migrations/index.php`)

#### Logica di Deduplicazione

```
PER OGNI GRUPPO DI CHIAVI CON STESSO IDENTIFIER+CATEGORIA:

1. Identifica la "vincitrice":
   ├─ Priorità 1: Chiave ATTIVA (k_out = 0)
   ├─ Priorità 2: ID più alto (più recente)
   └─ Risultato: 1 chiave vincitrice, N-1 deduplicate

2. Crea solo la vincitrice nel nuovo DB

3. Migra TUTTI i movimenti (anche delle deduplicate) sulla vincitrice

4. Registra audit log della deduplicazione
```

#### Criteri di Priorità

```sql
ORDINA PER:
1. Stato: ATTIVA (k_out = 0) PRIMA di DISMESSA
2. ID: Più alto PRIMA (più recente)
```

**Esempio:**
```
Gruppo: "Chiave Rossi" + "Condominio A"
- ID 15: ATTIVA (k_out = 0)
- ID 87: DISMESSA (k_out = 2020-05-10)
- ID 102: DISMESSA (k_out = 2018-03-20)

Vincitrice: ID 15 (ATTIVA, priorità massima)
Deduplicate: ID 87, 102

Risultato:
- Chiave ID 15: "Chiave Rossi" (ATTIVA)
  - Movimenti: da ID 15 + 87 + 102 (tutti uniti)
- Audit log: "Chiave deduplicata. Mantenuto ID 15, deduplicati 87, 102"
```

#### SQL Utilizzato

```sql
-- Tabella temporanea di mapping
CREATE TEMPORARY TABLE _key_id_mapping (
    old_id INT,
    new_id INT,
    is_deduplicated TINYINT(1),
    kept_id INT
);

-- Identifica vincitrice per ogni gruppo
INSERT INTO _key_id_mapping ...
SELECT 
    k_id,
    CASE WHEN k_id = (
        SELECT k2.k_id FROM keys_k k2
        WHERE k2.k_cat = k.k_cat AND k2.k_name = k.k_name
        ORDER BY 
            CASE WHEN k2.k_out = 0 THEN 0 ELSE 1 END ASC,
            k2.k_id DESC
        LIMIT 1
    ) THEN k_id ELSE NULL END,
    ...
FROM keys_k k;

-- Migra solo vincitrici
INSERT INTO keys (...)
SELECT m.new_id, ...
FROM keys_k k
INNER JOIN _key_id_mapping m ON k.k_id = m.old_id
WHERE m.is_deduplicated = 0;

-- Migra movimenti con mapping
INSERT INTO key_movements (key_id, ...)
SELECT COALESCE(m.new_id, log_kid), ...
FROM keys_log log
LEFT JOIN _key_id_mapping m ON log.log_kid = m.old_id;

-- Audit log deduplicazione
INSERT INTO audit_log (action, details, message)
SELECT 
    'deduplication',
    JSON_OBJECT('deduplicated_ids', GROUP_CONCAT(old_id)),
    CONCAT('Mantenuto ID ', kept_id)
FROM _key_id_mapping
WHERE is_deduplicated = 1
GROUP BY kept_id;
```

---

### 2. Durante la Creazione Manuale (`ajax/chiavi/create.php`)

```
1. Cerco se esiste chiave ATTIVA con stesso identifier + categoria
   ├─ SI → ERRORE
   │  - Messaggio: "Esiste già una chiave attiva..."
   │  - Link alla scheda esistente
   │
2. Cerco se esiste chiave DISMESSA con stesso identifier + categoria
   ├─ SI → CHIEDI CONFERMA
   │  - Modal: "Esiste una chiave dismessa. Vuoi ripristinarla?"
   │  ├─ SI → Chiama /ajax/chiavi/restore.php
   │  │  - Ripristina chiave (stesso ID)
   │  │  - Alert giallo: "Chiave ripristinata"
   │  └─ NO → Annulla operazione
   │
3. Nessuna chiave esistente → CREA NUOVA
   - Alert verde: "Chiave creata con successo"
```

**Conferma richiesta per ripristino.**

---

### 3. Feedback all'Utente

**Migrazione completata:**
```
✅ Migrazione dati completata: 15 utenti, 120 chiavi importate, 49 chiavi deduplicate
```

**Chiave attiva già esistente (produzione):**
```
ℹ️ La chiave esiste già. [Vai alla scheda]
```

**Creazione nuova:**
```
✅ Chiave creata con successo
```

---

## Vantaggi

1. ✅ **Nessun duplicato attivo**: Inventario pulito dopo migrazione
2. ✅ **Storico preservato**: Tutti i movimenti uniti sulla chiave vincitrice
3. ✅ **Audit log completo**: Tracciato quali chiavi sono state deduplicate
4. ✅ **Sicurezza**: Chiavi in consegna non perse (verifica preliminare)
5. ✅ **Produzione sicura**: Nessun ripristino accidentale

---

## Note Tecniche

### Verifica Preliminare Migrazione

Prima di migrare, esegui queste query per verificare la situazione:

```sql
-- 1. Trova duplicati con più chiavi attive in consegna (CRITICO)
SELECT k_cat, k_name, COUNT(*)
FROM keys_k
WHERE (k_out = 0 OR k_out IS NULL) AND k_cons_to != ''
GROUP BY k_cat, k_name
HAVING COUNT(*) > 1;

-- Se questa query torna 0 righe, puoi procedere con deduplicazione sicura
```

### Cosa Succede ai Dati

**Prima (Legacy):**
```
ID 15: "Chiave Rossi", Cat A, ATTIVA
  - Movimenti: 5
ID 87: "Chiave Rossi", Cat A, DISMESSA
  - Movimenti: 3
```

**Dopo (Nuovo DB):**
```
ID 15: "Chiave Rossi", Cat A, ATTIVA
  - Movimenti: 8 (5 originali + 3 da ID 87)
  
Audit Log:
  - "Deduplication: mantenuto ID 15, deduplicati 87"
```

---

## Esempi

### Scenario 1: Migrazione con Deduplicazione

```
DB Legacy:
- ID 15: "Chiave Rossi", Cat A, ATTIVA, 5 movimenti
- ID 87: "Chiave Rossi", Cat A, DISMESSA, 3 movimenti
- ID 102: "Chiave Rossi", Cat A, DISMESSA, 2 movimenti

DB Nuovo (dopo migrazione):
- ID 15: "Chiave Rossi", Cat A, ATTIVA
  - Movimenti: 10 (5+3+2 uniti)
- Audit Log: "Chiave deduplicata. Mantenuto ID 15, deduplicati 87, 102"
```

### Scenario 2: Creazione Manuale con Dismessa Esistente

```
DB:
- ID 42: "Chiave Bianchi", Cat B, DISMESSA

Utente crea: "Chiave Bianchi", Cat B

Risultato:
- ID 42: "Chiave Bianchi", DISMESSA (intoccata)
- ID 156: "Chiave Bianchi", DISPONIBILE (NUOVA)
```

### Scenario 3: Creazione Manuale con Attiva Esistente

```
DB:
- ID 78: "Chiave Verdi", Cat C, DISPONIBILE

Utente crea: "Chiave Verdi", Cat C

Risultato:
- ERRORE: "La chiave esiste già"
- Link: /chiavi/storia.php?id=78
```

---

## Riepilogo

| Contesto | Duplicati | Azione |
|----------|-----------|--------|
| Migrazione | Più chiavi attive | Deduplica, unisci movimenti |
| Migrazione | 1 attiva + N dismesse | Tieni attiva, unisci movimenti |
| Migrazione | Solo dismesse | Tieni più recente (ID alto) |
| Produzione | Esiste attiva | ERRORE, no creazione |
| Produzione | Esiste dismessa | CHIEDI: "Vuoi ripristinare?" |
| Produzione | Nessuna esistente | CREA NUOVA |
