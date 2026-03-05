/*
 * Voice Parser
 * Legge una stringa proveniente dalla trascrizione vocale e la converte
 * in un oggetto comando standardizzato per l'applicazione.
 *
 * Comandi riconosciuti (lingua italiana):
 *   - "consegna chiave a [nome]"  => { action: 'checkout', target: '[nome]' }
 *   - "rientro chiave"           => { action: 'checkin', target: null }
 *   - "annulla"                  => { action: 'cancel', target: null }
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

        // checkout con consegna a ...
        var deliveryMatch = normalized.match(/^consegna chiave a\s+(.+)$/i);
        if (deliveryMatch) {
            return {
                action: 'checkout',
                target: deliveryMatch[1].trim()
            };
        }

        // checkin semplice
        if (/^rientro chiave$/i.test(normalized)) {
            return {
                action: 'checkin',
                target: null
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
