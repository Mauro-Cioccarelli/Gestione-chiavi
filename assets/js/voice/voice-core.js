/**
 * Voice Core
 * Gestisce il lifecycle del riconoscimento vocale via Web Speech API.
 */
(function () {
    'use strict';

    function VoiceCore(options) {
        this.options = options || {};
        this.lang = this.options.lang || 'it-IT';
        this._isListening = false;

        this.RecognitionCtor = window.SpeechRecognition || window.webkitSpeechRecognition;

        if (!this.RecognitionCtor) {
            if (typeof this.options.onUnsupported === 'function') {
                this.options.onUnsupported();
            }
            return;
        }

        this.recognition = new this.RecognitionCtor();
        this.recognition.lang = this.lang;
        this.recognition.continuous = false;
        this.recognition.interimResults = false;

        this._bindEvents();
    }

    VoiceCore.prototype._bindEvents = function () {
        var self = this;

        this.recognition.onstart = function () {
            self._isListening = true;
            if (typeof self.options.onStart === 'function') {
                self.options.onStart();
            }
        };

        this.recognition.onend = function () {
            self._isListening = false;
            if (typeof self.options.onEnd === 'function') {
                self.options.onEnd();
            }
        };

        this.recognition.onresult = function (event) {
            var transcript = '';

            for (var i = event.resultIndex; i < event.results.length; i++) {
                if (event.results[i].isFinal) {
                    transcript += event.results[i][0].transcript;
                }
            }

            transcript = transcript.trim();
            if (transcript && typeof self.options.onResult === 'function') {
                self.options.onResult(transcript);
            }
        };

        this.recognition.onerror = function (event) {
            var errorType = event && event.error ? event.error : 'unknown';
            var messageByType = {
                'not-allowed': 'Microfono negato. Consenti l\'accesso al microfono e riprova.',
                'audio-capture': 'Nessun microfono disponibile o accessibile.',
                'no-speech': 'Nessun parlato rilevato. Riprova parlando dopo il beep.',
                'network': 'Errore di rete durante il riconoscimento vocale.',
                'aborted': 'Ascolto annullato.'
            };

            var message = messageByType[errorType] || 'Errore nel riconoscimento vocale: ' + errorType;

            if (typeof self.options.onError === 'function') {
                self.options.onError(errorType, message);
            }
        };
    };

    VoiceCore.prototype.start = function () {
        if (!this.recognition || this._isListening) {
            return false;
        }

        try {
            this.recognition.start();
            return true;
        } catch (e) {
            if (typeof this.options.onError === 'function') {
                this.options.onError('start-failed', 'Impossibile avviare il riconoscimento vocale.');
            }
            return false;
        }
    };

    VoiceCore.prototype.stop = function () {
        if (!this.recognition || !this._isListening) {
            return false;
        }

        this.recognition.stop();
        return true;
    };

    VoiceCore.prototype.isListening = function () {
        return this._isListening;
    };

    window.VoiceCore = VoiceCore;
})();
