/*
 * Voice Actions
 * Riceve un comando già parsificato (da parseVoiceCommand) e ne simula
 * l'esecuzione sul frontend. Non tocca il backend, limita le azioni a una
 * notifica/alert, log in console e apertura/precompilazione di modal esistenti.
 */
(function () {
    'use strict';

    // Cache categorie (caricata al primo uso, poi riutilizzata)
    var _categoriesCache = null;

    /**
     * Carica le categorie dal server e le mette in cache.
     * Restituisce una Promise con l'array di categorie.
     */
    function _loadCategories() {
        if (_categoriesCache !== null) {
            return Promise.resolve(_categoriesCache);
        }

        return fetch(window.APP_URL + '/ajax/categorie/list.php?size=500&page=1', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (res) { return res.json(); })
            .then(function (json) {
                _categoriesCache = (json.data || []).map(function (c) {
                    return { id: c.id, name: c.name, nameLower: c.name.toLowerCase().trim() };
                });
                return _categoriesCache;
            })
            .catch(function () {
                _categoriesCache = [];
                return _categoriesCache;
            });
    }

    /**
     * Tenta di separare la query in {categoryId, categoryName, keyQuery}.
     * Prova ogni prefisso della query dal più lungo al più corto,
     * cercando una corrispondenza esatta con un nome categoria.
     * Restituisce null se nessuna categoria corrisponde.
     */
    function _matchCategoryPrefix(query, categories) {
        var words = query.toLowerCase().trim().split(/\s+/);

        for (var len = words.length - 1; len >= 1; len--) {
            var prefix = words.slice(0, len).join(' ');
            var remaining = words.slice(len).join(' ').trim();

            if (!remaining) continue;

            for (var i = 0; i < categories.length; i++) {
                if (categories[i].nameLower === prefix) {
                    return {
                        categoryId: categories[i].id,
                        categoryName: categories[i].name,
                        keyQuery: remaining
                    };
                }
            }
        }

        return null;
    }

    /**
     * Cerca una chiave disponibile per query vocale e avvia il checkout.
     * Usa le categorie in cache per separare [categoria] da [chiave].
     * Se trova un unico risultato apre la modal e precompila i campi.
     * Se trova più risultati (o nessuno) esegue la ricerca nella tabella.
     */
    function _voiceCheckout(query, recipient) {
        _loadCategories().then(function (categories) {
            var split = _matchCategoryPrefix(query, categories);
            var url;

            if (split) {
                console.log('[voice] categoria="' + split.categoryName + '" chiave="' + split.keyQuery + '"');
                url = window.APP_URL + '/ajax/chiavi/list.php'
                    + '?search=' + encodeURIComponent(split.keyQuery)
                    + '&category_id=' + split.categoryId
                    + '&status=available'
                    + '&size=10&page=1';
            } else {
                console.log('[voice] nessuna categoria riconosciuta, ricerca generica: "' + query + '"');
                url = window.APP_URL + '/ajax/chiavi/list.php'
                    + '?search=' + encodeURIComponent(query)
                    + '&status=available'
                    + '&size=10&page=1';
            }

            return fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (res) { return res.json(); })
                .then(function (json) {
                    var rows = json.data || [];

                    if (rows.length === 1) {
                        var key = rows[0];
                        if (typeof openCheckout === 'function') {
                            openCheckout(key.id, key.identifier, key.category_name);
                        }
                        if (recipient) {
                            setTimeout(function () {
                                var recipientInput = document.getElementById('checkout-recipient');
                                if (recipientInput) {
                                    recipientInput.value = recipient;
                                }
                            }, 300);
                        }
                    } else {
                        console.log('[voice] ' + rows.length + ' risultati, fallback su ricerca tabella');
                        var statusFilter = document.getElementById('status-filter');
                        if (statusFilter) {
                            statusFilter.value = 'available';
                            statusFilter.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                        var searchInput = document.getElementById('search-input');
                        if (searchInput) {
                            searchInput.value = split ? split.keyQuery : query;
                            searchInput.dispatchEvent(new Event('input', { bubbles: true }));
                            searchInput.focus();
                        }
                    }
                });
        }).catch(function (err) {
            console.error('[voice] checkout_voice error', err);
        });
    }

    /**
     * Cerca una chiave in_delivery per query vocale e avvia il checkin.
     * Logica speculare a _voiceCheckout: usa categorie in cache, stesso fallback tabella.
     * query null → mostra tutte le chiavi in consegna.
     */
    function _voiceCheckin(query) {
        var doSearch = function (categories) {
            var split = query ? _matchCategoryPrefix(query, categories) : null;
            var url = window.APP_URL + '/ajax/chiavi/list.php?status=in_delivery&size=10&page=1';

            if (split) {
                console.log('[voice] checkin categoria="' + split.categoryName + '" chiave="' + split.keyQuery + '"');
                url += '&search=' + encodeURIComponent(split.keyQuery)
                    + '&category_id=' + split.categoryId;
            } else if (query) {
                console.log('[voice] checkin ricerca generica: "' + query + '"');
                url += '&search=' + encodeURIComponent(query);
            }

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (res) { return res.json(); })
                .then(function (json) {
                    var rows = json.data || [];

                    if (rows.length === 1) {
                        var key = rows[0];
                        if (typeof openCheckin === 'function') {
                            openCheckin(key.id, key.identifier, key.category_name);
                        }
                    } else {
                        console.log('[voice] checkin: ' + rows.length + ' risultati, fallback su ricerca tabella');
                        var statusFilter = document.getElementById('status-filter');
                        if (statusFilter) {
                            statusFilter.value = 'in_delivery';
                            statusFilter.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                        if (split || query) {
                            var searchInput = document.getElementById('search-input');
                            if (searchInput) {
                                searchInput.value = split ? split.keyQuery : query;
                                searchInput.dispatchEvent(new Event('input', { bubbles: true }));
                                searchInput.focus();
                            }
                        }
                    }
                })
                .catch(function (err) {
                    console.error('[voice] checkin_voice error', err);
                });
        };

        _loadCategories().then(doSearch).catch(function () { doSearch([]); });
    }

    function executeVoiceCommand(cmd) {
        if (!cmd || !cmd.action) {
            console.warn('[voice] comando vuoto o malformato', cmd);
            return;
        }

        switch (cmd.action) {
            case 'search':
                console.log('[voice] search field=' + cmd.field + ' target=' + cmd.target);
                var searchInput = document.getElementById('search-input');
                if (searchInput) {
                    searchInput.value = cmd.target;
                    searchInput.dispatchEvent(new Event('input', { bubbles: true }));
                    searchInput.focus();
                } else {
                    console.warn('[voice] #search-input non trovato in questa pagina');
                }
                break;

            case 'checkout_voice':
                console.log('[voice] checkout_voice query=' + cmd.query + ' recipient=' + cmd.recipient);
                _voiceCheckout(cmd.query, cmd.recipient);
                break;

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

            case 'checkin_voice':
                console.log('[voice] checkin_voice query=' + cmd.query);
                _voiceCheckin(cmd.query);
                break;

            case 'cancel':
                console.log('[voice] cancel');
                var cancelSearch = document.getElementById('search-input');
                if (cancelSearch) {
                    cancelSearch.value = '';
                }
                var cancelStatus = document.getElementById('status-filter');
                if (cancelStatus) {
                    cancelStatus.value = '';
                    cancelStatus.dispatchEvent(new Event('change', { bubbles: true }));
                } else if (cancelSearch) {
                    cancelSearch.dispatchEvent(new Event('input', { bubbles: true }));
                }
                break;

            default:
                console.warn('[voice] azione sconosciuta', cmd);
                break;
        }
    }

    window.executeVoiceCommand = executeVoiceCommand;
})();
