/* Curiosidades da Wikipédia (API MediaWiki, no idioma do jogo).
   Só texto simples e imagens de upload.wikimedia.org entram na página; tudo é escrito com textContent.
   Se a Wikipédia não responder, o jogo continua sem curiosidade (as funções devolvem null). */
(function () {
    'use strict';

    var cache = {};
    var TEMPO_MAX = 10000;

    // palavras comuns nos nomes dos países que não devem ser tapadas
    var PARADAS = {};
    ('republic republica republique republiek kingdom reino royaume united unidos unis states estados etats state estado etat ' +
        'federal federation democratic democratica democratique democratico islands island ilhas islas iles plurinational ' +
        'north norte nord south south sul sur western occidental central equatorial people popular peoples').split(' ').forEach(function (p) { PARADAS[p] = true; });

    function norm(s) {
        return String(s).normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
    }

    function radicais(termos) {
        var saida = [];
        termos.forEach(function (termo) {
            norm(termo).split(/[^a-z0-9]+/).forEach(function (w) {
                if (w.length < 4 || PARADAS[w]) { return; }
                var r = w.length <= 4 ? w : (w.length <= 6 ? w.slice(0, w.length - 1) : w.slice(0, 6));
                if (saida.indexOf(r) === -1) { saida.push(r); }
            });
        });
        return saida;
    }

    /** Troca por ___ qualquer palavra que comece como um dos nomes (apanha também "português", "francês"…). */
    function mascarar(texto, termos) {
        var rad = radicais(termos);
        return String(texto).replace(/[\p{L}\p{N}][\p{L}\p{N}'’-]*/gu, function (tok) {
            var n = norm(tok);
            for (var i = 0; i < rad.length; i++) {
                if (n.indexOf(rad[i]) === 0) { return '___'; }
            }
            return tok;
        });
    }

    function limparExtrato(texto) {
        var t = String(texto || '')
            .replace(/\s*\([^)]*\)/g, '')
            .replace(/\s*\[[^\]]*\]/g, '')
            .replace(/\s+/g, ' ')
            .trim();
        if (t.length > 380) {
            var corte = t.lastIndexOf('. ', 380);
            t = corte > 120 ? t.slice(0, corte + 1) : t.slice(0, 380).replace(/\s+\S*$/, '') + '…';
        }
        return t;
    }

    function pedir(lingua, parametros) {
        var base = {
            action: 'query', format: 'json', formatversion: '2', origin: '*', redirects: '1',
            prop: 'extracts|pageimages|pageprops|info', exintro: '1', explaintext: '1', exsentences: '3', exlimit: 'max',
            piprop: 'thumbnail', pithumbsize: '520', ppprop: 'disambiguation', inprop: 'url'
        };
        for (var k in parametros) { base[k] = parametros[k]; }
        var url = 'https://' + lingua + '.wikipedia.org/w/api.php?' + new URLSearchParams(base).toString();
        var controlo = new AbortController();
        var relogio = setTimeout(function () { controlo.abort(); }, TEMPO_MAX);
        return fetch(url, { signal: controlo.signal })
            .then(function (r) { if (!r.ok) { throw new Error('http'); } return r.json(); })
            .then(function (j) { clearTimeout(relogio); return (j && j.query && j.query.pages) || []; })
            .catch(function () { clearTimeout(relogio); return []; });
    }

    function escolherPagina(paginas, radicaisEsperados) {
        var ordenadas = paginas.slice().sort(function (a, b) { return (a.index || 0) - (b.index || 0); });
        for (var i = 0; i < ordenadas.length; i++) {
            var p = ordenadas[i];
            if (p.missing || !p.extract || p.extract.length < 80) { continue; }
            if (p.pageprops && p.pageprops.disambiguation !== undefined) { continue; }
            if (radicaisEsperados && radicaisEsperados.length) {
                var alvo = norm(p.title + ' ' + p.extract);
                if (!radicaisEsperados.some(function (r) { return alvo.indexOf(r) !== -1; })) { continue; }
            }
            return p;
        }
        return null;
    }

    function urlSeguro(lingua, pagina) {
        var u = pagina.fullurl || '';
        try {
            var o = new URL(u);
            if (o.protocol === 'https:' && /(^|\.)wikipedia\.org$/.test(o.hostname)) { return o.href; }
        } catch (e) { /* cai para o endereço montado */ }
        return 'https://' + lingua + '.wikipedia.org/wiki/' + encodeURIComponent(String(pagina.title).replace(/ /g, '_'));
    }

    function resultado(lingua, pagina) {
        var img = pagina.thumbnail && pagina.thumbnail.source;
        if (typeof img !== 'string' || img.indexOf('https://upload.wikimedia.org/') !== 0) { img = null; }
        return { titulo: String(pagina.title), texto: limparExtrato(pagina.extract), imagem: img, url: urlSeguro(lingua, pagina) };
    }

    function memorizar(chave, fabrica) {
        if (!cache[chave]) { cache[chave] = fabrica(); }
        return cache[chave];
    }

    /** Artigo do país no idioma pedido. */
    function sobrePais(lingua, nome, capital, outrosNomes) {
        return memorizar('p|' + lingua + '|' + nome, function () {
            var esperados = radicais([nome].concat(outrosNomes || []));
            return pedir(lingua, { titles: nome }).then(function (paginas) {
                var p = escolherPagina(paginas, esperados);
                if (p) { return resultado(lingua, p); }
                return pedir(lingua, { generator: 'search', gsrsearch: nome + ' ' + capital, gsrlimit: '3', gsrnamespace: '0' })
                    .then(function (achadas) {
                        var q = escolherPagina(achadas, esperados);
                        return q ? resultado(lingua, q) : null;
                    });
            });
        });
    }

    /** Artigo da capital, procurado com o nome da cidade e do país. */
    function sobreCapital(lingua, cidade, pais, outrosNomes) {
        return memorizar('c|' + lingua + '|' + cidade + '|' + pais, function () {
            var esperados = radicais([pais].concat(outrosNomes || []));
            return pedir(lingua, { generator: 'search', gsrsearch: cidade + ' ' + pais, gsrlimit: '3', gsrnamespace: '0' })
                .then(function (paginas) {
                    var p = escolherPagina(paginas, esperados);
                    return p ? resultado(lingua, p) : null;
                });
        });
    }

    window.Wiki = { sobrePais: sobrePais, sobreCapital: sobreCapital, mascarar: mascarar, radicais: radicais, norm: norm };
})();
