-- Rimozione del UNIQUE KEY uk_name dalla tabella key_categories.
-- Il vincolo UNIQUE a livello DB non è compatibile con il soft-delete:
-- le righe eliminate (deleted_at IS NOT NULL) occupano comunque il nome
-- nell'indice, impedendo di rinominare o creare categorie attive con lo stesso nome.
-- Il controllo di unicità è già gestito correttamente a livello applicativo (PHP),
-- che esclude le righe eliminate nei controlli duplicati.

ALTER TABLE `key_categories` DROP INDEX `uk_name`;
