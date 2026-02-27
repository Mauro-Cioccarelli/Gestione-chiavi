-- ============================================================================
-- 002_add_updated_at_to_categories.sql
-- Aggiunta colonna updated_at alla tabella key_categories
-- ============================================================================

ALTER TABLE `key_categories` 
ADD COLUMN `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;
