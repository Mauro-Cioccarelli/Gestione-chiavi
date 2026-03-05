/*
 * Voice Parser
 * Legge una stringa proveniente dalla trascrizione vocale e la converte
 * in un oggetto comando standardizzato per l'applicazione.
 *
 * Comandi riconosciuti (lingua italiana):
 *   - "cerca [termine]"                    => { action: 'search', target, field: 'all' }
 *   - "cerca chiave [termine]"             => { action: 'search', target, field: 'chiave' }
 *   - "cerca categoria [termine]"          => { action: 'search', target, field: 'categoria' }
 *   - "consegna [chiave] a [nome]"         => { action: 'checkout_voice', query, recipient }
 *   - "rientro [chiave]"                   => { action: 'checkin_voice', query }
 *   - "rientro" / "rientro chiave"         => { action: 'checkin_voice', query: null }
 *   - "annulla"                            => { action: 'cancel', target: null }
 *
 * Se la frase non corrisponde a nessuno di questi modelli restituisce null.
 */
(function () {
    'use strict';

    function parseVoiceCommand(text) {
        if (!text || typeof text !== 'string') {
            return null;
        }

        var normalized = text.trim().toLowerCase();

        // cerca chiave [termine] — specifico per identificativo chiave
        var searchKeyMatch = normalized.match(/^cerca chiav(?:e|i)\s+(.+)$/i);
        if (searchKeyMatch) {
            return {
                action: 'search',
                target: searchKeyMatch[1].trim(),
                field: 'chiave'
            };
        }

        // cerca categoria [termine] — specifico per categoria
        var searchCatMatch = normalized.match(/^cerca categor(?:ia|ie)\s+(.+)$/i);
        if (searchCatMatch) {
            return {
                action: 'search',
                target: searchCatMatch[1].trim(),
                field: 'categoria'
            };
        }

        // cerca [termine] — ricerca generica
        var searchMatch = normalized.match(/^cerca\s+(.+)$/i);
        if (searchMatch) {
            return {
                action: 'search',
                target: searchMatch[1].trim(),
                field: 'all'
            };
        }

        // consegna [chiave] [query] a [nome] — con destinatario, "chiave" opzionale
        // Match greedy: l'ultima occorrenza di " a " è il separatore verso il destinatario.
        var checkoutVoiceMatch = normalized.match(/^consegna(?:\s+chiave)?\s+(.+)\s+a\s+(.+)$/i);
        if (checkoutVoiceMatch) {
            return {
                action: 'checkout_voice',
                query: checkoutVoiceMatch[1].trim(),
                recipient: checkoutVoiceMatch[2].trim()
            };
        }

        // consegna [chiave] [query] — senza destinatario, "chiave" opzionale
        var checkoutOnlyMatch = normalized.match(/^consegna(?:\s+chiave)?\s+(.+)$/i);
        if (checkoutOnlyMatch) {
            return {
                action: 'checkout_voice',
                query: checkoutOnlyMatch[1].trim(),
                recipient: null
            };
        }

        // rientro [query] — "chiave" opzionale come keyword dopo "rientro"
        var checkinVoiceMatch = normalized.match(/^rientro(?:\s+chiave)?\s+(.+)$/i);
        if (checkinVoiceMatch) {
            return {
                action: 'checkin_voice',
                query: checkinVoiceMatch[1].trim()
            };
        }

        // rientro / rientro chiave senza argomenti — mostra tutte le chiavi in consegna
        if (/^rientro(?:\s+chiave)?$/i.test(normalized)) {
            return {
                action: 'checkin_voice',
                query: null
            };
        }

        // comando annulla
        if (/^annulla$/i.test(normalized)) {
            return {
                action: 'cancel',
                target: null
            };
        }

        return null;
    }

    // rendiamo disponibile globalmente per l'inizializzazione della pagina
    window.parseVoiceCommand = parseVoiceCommand;
})();
