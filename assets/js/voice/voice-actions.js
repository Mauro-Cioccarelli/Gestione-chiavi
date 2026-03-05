/*
 * Voice Actions
 * Riceve un comando già parsificato (da parseVoiceCommand) e ne simula
 * l'esecuzione sul frontend. Non tocca il backend, limita le azioni a una
 * notifica/alert, log in console e apertura/precompilazione di modal esistenti.
 */
(function () {
    'use strict';

    function executeVoiceCommand(cmd) {
        if (!cmd || !cmd.action) {
            console.warn('[voice] comando vuoto o malformato', cmd);
            return;
        }

        switch (cmd.action) {
            case 'checkout':
                console.log('[voice] checkout verso', cmd.target);
                alert('Simulazione: consegna chiave a ' + cmd.target);
                // esempio di precompilazione del campo destinatario
                var recipientInput = document.getElementById('checkout-recipient');
                if (recipientInput) {
                    recipientInput.value = cmd.target;
                }
                // se esiste la modal di consegna, apriamola
                var modalEl = document.getElementById('modalCheckout');
                if (modalEl && typeof bootstrap !== 'undefined') {
                    var m = new bootstrap.Modal(modalEl);
                    m.show();
                }
                break;

            case 'checkin':
                console.log('[voice] checkin');
                alert('Simulazione: rientro chiave');
                var modalEl2 = document.getElementById('modalCheckin');
                if (modalEl2 && typeof bootstrap !== 'undefined') {
                    var m2 = new bootstrap.Modal(modalEl2);
                    m2.show();
                }
                break;

            case 'cancel':
                console.log('[voice] cancel');
                alert('Comando annullato');
                break;

            default:
                console.warn('[voice] azione sconosciuta', cmd);
                break;
        }
    }

    window.executeVoiceCommand = executeVoiceCommand;
})();
