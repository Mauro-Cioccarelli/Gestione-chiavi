-- Migrazione: decodifica entità HTML salvate erroneamente nel database
-- Causa: sanitize_string() applicava htmlspecialchars() prima del salvataggio
-- Le entità interessate: &#039; → '   &amp; → &   &quot; → "   &lt; → <   &gt; → >

UPDATE `keys`
SET identifier = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
    identifier,
    '&#039;', ''''),
    '&amp;', '&'),
    '&quot;', '"'),
    '&lt;', '<'),
    '&gt;', '>')
WHERE identifier LIKE '%&#039;%'
   OR identifier LIKE '%&amp;%'
   OR identifier LIKE '%&quot;%'
   OR identifier LIKE '%&lt;%'
   OR identifier LIKE '%&gt;%';

UPDATE `key_categories`
SET name = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
    name,
    '&#039;', ''''),
    '&amp;', '&'),
    '&quot;', '"'),
    '&lt;', '<'),
    '&gt;', '>')
WHERE name LIKE '%&#039;%'
   OR name LIKE '%&amp;%'
   OR name LIKE '%&quot;%'
   OR name LIKE '%&lt;%'
   OR name LIKE '%&gt;%';

UPDATE `key_categories`
SET description = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
    description,
    '&#039;', ''''),
    '&amp;', '&'),
    '&quot;', '"'),
    '&lt;', '<'),
    '&gt;', '>')
WHERE description IS NOT NULL
  AND (description LIKE '%&#039;%'
    OR description LIKE '%&amp;%'
    OR description LIKE '%&quot;%'
    OR description LIKE '%&lt;%'
    OR description LIKE '%&gt;%');

UPDATE `key_movements`
SET recipient_name = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
    recipient_name,
    '&#039;', ''''),
    '&amp;', '&'),
    '&quot;', '"'),
    '&lt;', '<'),
    '&gt;', '>')
WHERE recipient_name IS NOT NULL
  AND (recipient_name LIKE '%&#039;%'
    OR recipient_name LIKE '%&amp;%'
    OR recipient_name LIKE '%&quot;%'
    OR recipient_name LIKE '%&lt;%'
    OR recipient_name LIKE '%&gt;%');

UPDATE `key_movements`
SET notes = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
    notes,
    '&#039;', ''''),
    '&amp;', '&'),
    '&quot;', '"'),
    '&lt;', '<'),
    '&gt;', '>')
WHERE notes IS NOT NULL
  AND (notes LIKE '%&#039;%'
    OR notes LIKE '%&amp;%'
    OR notes LIKE '%&quot;%'
    OR notes LIKE '%&lt;%'
    OR notes LIKE '%&gt;%');

UPDATE `users`
SET username = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
    username,
    '&#039;', ''''),
    '&amp;', '&'),
    '&quot;', '"'),
    '&lt;', '<'),
    '&gt;', '>')
WHERE username LIKE '%&#039;%'
   OR username LIKE '%&amp;%'
   OR username LIKE '%&quot;%'
   OR username LIKE '%&lt;%'
   OR username LIKE '%&gt;%';
