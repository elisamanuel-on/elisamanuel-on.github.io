// ============================================
// EXERCÍCIO 011 - CURIOSIDADES DO MUNDO (v2.0.0)
// Artigos reais da Wikipédia, por áreas, com mais dados escondidos para revelar ao clicar.
// Só JavaScript: a API da Wikipédia aceita pedidos do navegador (origin=*), por isso funciona na GitHub Pages.
// ============================================
(function () {
    'use strict';

    var C = window.Curiosidades;
    var t = C.t;

    var AREAS = ['ciencia', 'natureza', 'historia', 'artes', 'teologia'];
    var CORES = { ciencia: '#667eea', natureza: '#48bb78', historia: '#ed8936', artes: '#f093fb', teologia: '#ecc94b', surpresa: '#a78bfa' };
    var ICONES = {
        ciencia: '<path d="M9 3h6"/><path d="M10 3v6l-5 9a2 2 0 0 0 2 3h10a2 2 0 0 0 2-3l-5-9V3"/><path d="M7.5 15h9"/>',
        natureza: '<path d="M11 20A7 7 0 0 1 4 13c0-6 7-10 16-10 0 9-4 16-9 17z"/><path d="M2 22c3-5 6-8 10-11"/>',
        historia: '<path d="M3 21h18"/><path d="M5 21V10"/><path d="M9 21V10"/><path d="M15 21V10"/><path d="M19 21V10"/><path d="M3 10l9-6 9 6"/>',
        artes: '<path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>',
        teologia: '<path d="M2 4h7a3 3 0 0 1 3 3v14a2 2 0 0 0-2-2H2z"/><path d="M22 4h-7a3 3 0 0 0-3 3v14a2 2 0 0 1 2-2h8z"/>',
        surpresa: '<path d="M12 3l2.5 6.5L21 12l-6.5 2.5L12 21l-2.5-6.5L3 12l6.5-2.5z"/>'
    };
    var PREFIXOS_A_EVITAR = /^(lista|list|liste|anexo|wikipedia|wikipédia|categoria|category|catégorie|portal|portail)\b/i;
    var CHAVE_CONTADOR = 'curiosidades_descobertas';

    var estado = {
        area: null,        // área escolhida (ou 'surpresa')
        areaReal: null,    // área efetivamente usada (a 'surpresa' sorteia uma)
        pagina: null,      // curiosidade que está no ecrã
        pedido: 0,         // número do último pedido: respostas atrasadas são ignoradas
        vistos: {},        // artigos já mostrados (por idioma + id), para não repetir
        descobertas: 0
    };

    // ---------- utilitários ----------
    function $(id) { return document.getElementById(id); }

    function el(tag, atributos, filhos) {
        var e = document.createElement(tag);
        Object.keys(atributos || {}).forEach(function (k) {
            if (k === 'texto') { e.textContent = atributos[k]; }
            else { e.setAttribute(k, atributos[k]); }
        });
        (filhos || []).forEach(function (f) { if (f) { e.appendChild(typeof f === 'string' ? document.createTextNode(f) : f); } });
        return e;
    }

    function svg(nome, tamanho) {
        var s = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        s.setAttribute('viewBox', '0 0 24 24');
        s.setAttribute('class', 'icone');
        s.setAttribute('aria-hidden', 'true');
        if (tamanho) { s.setAttribute('width', tamanho); s.setAttribute('height', tamanho); }
        s.innerHTML = ICONES[nome] || ''; // texto fixo definido acima, nunca vindo da Wikipédia
        return s;
    }

    function aleatorio(max) { return Math.floor(Math.random() * max); }
    function escolher(lista) { return lista[aleatorio(lista.length)]; }

    function lerContador() {
        try { return parseInt(window.localStorage.getItem(CHAVE_CONTADOR), 10) || 0; } catch (e) { return 0; }
    }
    function guardarContador(n) {
        try { window.localStorage.setItem(CHAVE_CONTADOR, String(n)); } catch (e) { /* ignora */ }
    }

    // ---------- pedidos à Wikipédia ----------
    function url(params) {
        var q = new URLSearchParams(params);
        q.set('action', 'query');
        q.set('format', 'json');
        q.set('formatversion', '2');
        q.set('origin', '*');
        q.set('redirects', '1');
        return 'https://' + C.idioma + '.wikipedia.org/w/api.php?' + q.toString();
    }

    function pedir(params) {
        var controlo = new AbortController();
        var tempo = setTimeout(function () { controlo.abort(); }, 12000);
        return fetch(url(params), { signal: controlo.signal })
            .then(function (r) {
                if (!r.ok) { throw new Error('HTTP ' + r.status); }
                return r.json();
            })
            .then(function (dados) {
                if (dados.error) { throw new Error(dados.error.info || 'erro da API'); }
                return dados;
            })
            .finally(function () { clearTimeout(tempo); });
    }

    var PROPS_RESUMO = {
        prop: 'extracts|pageimages|info|description|pageprops',
        exintro: '1', explaintext: '1', exsentences: '2', exlimit: 'max',
        piprop: 'thumbnail', pithumbsize: '720',
        inprop: 'url', ppprop: 'disambiguation'
    };

    function juntar(a, b) { var r = {}; [a, b].forEach(function (o) { Object.keys(o).forEach(function (k) { r[k] = o[k]; }); }); return r; }

    // Só aceita imagens e ligações que venham mesmo da Wikipédia/Wikimedia
    function imagemSegura(p) {
        var s = p.thumbnail && p.thumbnail.source;
        return (typeof s === 'string' && /^https:\/\/upload\.wikimedia\.org\//.test(s)) ? s : null;
    }
    function ligacaoSegura(p) {
        var u = p.fullurl;
        return (typeof u === 'string' && /^https:\/\/[a-z-]+\.wikipedia\.org\//.test(u)) ? u : null;
    }

    function serBoa(p) {
        if (!p || p.missing || p.invalid) { return false; }
        if (p.pageprops && p.pageprops.disambiguation !== undefined) { return false; }
        if (PREFIXOS_A_EVITAR.test(p.title || '')) { return false; }
        if (!p.extract || p.extract.trim().length < 60) { return false; }
        if (estado.vistos[C.idioma + ':' + p.pageid]) { return false; }
        return true;
    }

    // Procura uma curiosidade da área: sorteia uma palavra e uma posição nos resultados.
    function procurarCuriosidade(area) {
        var sementes = C.SEMENTES[C.idioma][area];
        function tentar(n) {
            if (n >= 4) { return Promise.resolve(null); }
            var semente = escolher(sementes);
            var posicao = n < 2 ? aleatorio(150) : 0;
            return pedir(juntar(PROPS_RESUMO, { generator: 'search', gsrsearch: semente, gsrnamespace: '0', gsrlimit: '20', gsroffset: String(posicao) }))
                .then(function (dados) {
                    var paginas = ((dados.query && dados.query.pages) || []).filter(serBoa);
                    if (!paginas.length) { return tentar(n + 1); }
                    var comImagem = paginas.filter(imagemSegura);
                    var lista = (comImagem.length && Math.random() < 0.85) ? comImagem : paginas;
                    return escolher(lista);
                });
        }
        return tentar(0);
    }

    function procurarPorTitulo(titulo) {
        return pedir(juntar(PROPS_RESUMO, { titles: titulo })).then(function (dados) {
            var p = dados.query && dados.query.pages && dados.query.pages[0];
            return (p && !p.missing && p.extract) ? p : null;
        });
    }

    // Dados escondidos: texto maior, idiomas, categorias, tamanho... e temas relacionados
    function carregarDetalhes(p) {
        var detalhe = pedir({
            titles: p.title,
            prop: 'extracts|langlinks|categories|info|description',
            exintro: '1', explaintext: '1', exchars: '1400',
            lllimit: 'max', cllimit: '12', clshow: '!hidden'
        });
        var relacionados = pedir({
            generator: 'search', gsrsearch: 'morelike:' + p.title, gsrnamespace: '0', gsrlimit: '6',
            prop: 'description|pageprops', ppprop: 'disambiguation'
        }).then(function (dados) {
            return ((dados.query && dados.query.pages) || [])
                .filter(function (r) { return r.title !== p.title && !(r.pageprops && r.pageprops.disambiguation !== undefined) && !PREFIXOS_A_EVITAR.test(r.title); })
                .sort(function (a, b) { return (a.index || 0) - (b.index || 0); })
                .slice(0, 5);
        }).catch(function () { return []; });

        return Promise.all([detalhe, relacionados]).then(function (r) {
            var pg = r[0].query && r[0].query.pages && r[0].query.pages[0];
            if (!pg || pg.missing) { throw new Error('sem detalhes'); }
            return { pagina: pg, relacionados: r[1] };
        });
    }

    // ---------- apresentação ----------
    function limparResultado() {
        var r = $('resultado');
        while (r.firstChild) { r.removeChild(r.firstChild); }
        return r;
    }

    function mostrarCarregando() {
        var r = limparResultado();
        r.className = 'resultado a-carregar';
        r.setAttribute('aria-busy', 'true');
        r.appendChild(el('div', { 'class': 'esqueleto' }, [
            el('div', { 'class': 'esq-imagem' }),
            el('div', { 'class': 'esq-linhas' }, [el('div', { 'class': 'esq-linha curta' }), el('div', { 'class': 'esq-linha' }), el('div', { 'class': 'esq-linha' }), el('div', { 'class': 'esq-linha media' })])
        ]));
        r.appendChild(el('p', { 'class': 'estado-texto', texto: t('a_carregar') }));
    }

    function mostrarMensagem(chave) {
        var r = limparResultado();
        r.className = 'resultado';
        r.removeAttribute('aria-busy');
        var botao = el('button', { type: 'button', 'class': 'botao-secundario', texto: t('tentar') });
        botao.addEventListener('click', function () { carregarArea(estado.area || 'surpresa'); });
        r.appendChild(el('div', { 'class': 'mensagem-erro', role: 'alert' }, [el('p', { texto: t(chave) }), botao]));
    }

    function formatarData(iso) {
        var d = new Date(iso);
        if (isNaN(d.getTime())) { return '—'; }
        return d.toLocaleDateString(C.LOCALES[C.idioma], { year: 'numeric', month: 'long', day: 'numeric' });
    }

    function linhaDado(rotulo, valor) {
        return el('div', { 'class': 'dado' }, [el('dt', { texto: rotulo }), el('dd', { texto: valor })]);
    }

    function mostrarCuriosidade(p, area) {
        estado.pagina = p;
        estado.vistos[C.idioma + ':' + p.pageid] = true;
        estado.descobertas++;
        guardarContador(estado.descobertas);
        atualizarContador();

        var cor = CORES[area] || CORES.surpresa;
        var r = limparResultado();
        r.className = 'resultado';
        r.removeAttribute('aria-busy');
        r.style.setProperty('--cor-area', cor);

        var imagem = imagemSegura(p);
        var figura = el('div', { 'class': 'figura' + (imagem ? '' : ' sem-imagem') });
        if (imagem) {
            var img = el('img', { src: imagem, alt: p.title, loading: 'lazy', decoding: 'async' });
            img.addEventListener('error', function () { figura.classList.add('sem-imagem'); img.remove(); });
            figura.appendChild(img);
        } else {
            figura.appendChild(svg(area, '44'));
        }

        var painelId = 'dados-escondidos';
        var botaoRevelar = el('button', { type: 'button', 'class': 'botao-revelar', 'aria-expanded': 'false', 'aria-controls': painelId });
        var rotuloRevelar = el('span', { texto: t('revelar') });
        botaoRevelar.appendChild(rotuloRevelar);
        botaoRevelar.appendChild(el('span', { 'class': 'seta', 'aria-hidden': 'true', texto: '▾' }));

        var painel = el('div', { id: painelId, 'class': 'painel-dados', hidden: '' });

        var botaoMais = el('button', { type: 'button', 'class': 'botao-secundario', texto: t('mais_uma') });
        botaoMais.prepend(svg('surpresa', '16'));
        botaoMais.addEventListener('click', function () { carregarArea(estado.area); });

        var ligacao = ligacaoSegura(p);
        var acoes = el('div', { 'class': 'acoes' }, [botaoMais]);
        if (ligacao) {
            acoes.appendChild(el('a', { 'class': 'botao-link', href: ligacao, target: '_blank', rel: 'noopener noreferrer', texto: t('ler') }));
        }

        var cartao = el('article', { 'class': 'curiosidade' }, [
            figura,
            el('div', { 'class': 'corpo' }, [
                el('p', { 'class': 'etiqueta-area' }, [svg(area, '14'), el('span', { texto: t('curiosidade_de', t('cat_' + area)) })]),
                el('h2', { texto: p.title }),
                el('p', { 'class': 'resumo', texto: p.extract.trim() }),
                botaoRevelar
            ])
        ]);
        r.appendChild(cartao);
        r.appendChild(painel);
        r.appendChild(acoes);

        var carregado = false;
        botaoRevelar.addEventListener('click', function () {
            var abrir = painel.hasAttribute('hidden');
            if (abrir) { painel.removeAttribute('hidden'); } else { painel.setAttribute('hidden', ''); }
            botaoRevelar.setAttribute('aria-expanded', abrir ? 'true' : 'false');
            botaoRevelar.classList.toggle('aberto', abrir);
            rotuloRevelar.textContent = t(abrir ? 'esconder' : 'revelar');
            if (abrir && !carregado) {
                carregado = true;
                preencherPainel(painel, p, area);
            }
        });
    }

    function preencherPainel(painel, p, area) {
        painel.appendChild(el('p', { 'class': 'estado-texto', texto: t('a_carregar') }));
        var meuPedido = estado.pedido;
        carregarDetalhes(p).then(function (res) {
            if (meuPedido !== estado.pedido || estado.pagina !== p) { return; }
            while (painel.firstChild) { painel.removeChild(painel.firstChild); }
            var pg = res.pagina;

            if (pg.extract && pg.extract.trim().length > p.extract.trim().length) {
                painel.appendChild(el('h3', { texto: t('sobre') }));
                pg.extract.trim().split(/\n+/).forEach(function (par) { painel.appendChild(el('p', { 'class': 'texto-longo', texto: par })); });
            }

            painel.appendChild(el('h3', { texto: t('dados_rapidos') }));
            var lista = el('dl', { 'class': 'dados' });
            var descricao = pg.description || p.description;
            if (descricao) { lista.appendChild(linhaDado(t('descricao'), descricao.charAt(0).toUpperCase() + descricao.slice(1))); }
            var nIdiomas = ((pg.langlinks && pg.langlinks.length) || 0) + 1;
            lista.appendChild(linhaDado(t('idiomas'), String(nIdiomas)));
            if (pg.touched) { lista.appendChild(linhaDado(t('ultima_edicao'), formatarData(pg.touched))); }
            if (typeof pg.length === 'number') {
                var mil = Math.round(pg.length / 1000);
                lista.appendChild(linhaDado(t('tamanho'), mil < 1 ? t('tamanho_pequeno') : t('tamanho_valor', mil.toLocaleString(C.LOCALES[C.idioma]))));
            }
            painel.appendChild(lista);

            var cats = ((pg.categories || []).map(function (c) { return String(c.title || '').replace(/^[^:]+:/, ''); })).filter(Boolean).slice(0, 8);
            if (cats.length) {
                painel.appendChild(el('h3', { texto: t('categorias') }));
                painel.appendChild(el('ul', { 'class': 'chips' }, cats.map(function (c) { return el('li', { texto: c }); })));
            }

            painel.appendChild(el('h3', { texto: t('relacionados') }));
            if (res.relacionados.length) {
                painel.appendChild(el('ul', { 'class': 'chips relacionados' }, res.relacionados.map(function (rel) {
                    var b = el('button', { type: 'button', 'class': 'chip-botao', title: rel.description || rel.title, texto: rel.title });
                    b.addEventListener('click', function () { abrirTitulo(rel.title, area); });
                    return el('li', {}, [b]);
                })));
            } else {
                painel.appendChild(el('p', { 'class': 'estado-texto', texto: t('sem_relacionados') }));
            }
        }).catch(function () {
            if (meuPedido !== estado.pedido) { return; }
            while (painel.firstChild) { painel.removeChild(painel.firstChild); }
            painel.appendChild(el('p', { 'class': 'estado-texto', texto: t('sem_dados') }));
        });
    }

    // ---------- fluxo ----------
    function realDaArea(area) { return area === 'surpresa' ? escolher(AREAS) : area; }

    function carregarArea(area) {
        estado.area = area;
        var real = realDaArea(area);
        estado.areaReal = real;
        marcarArea(area);
        var meuPedido = ++estado.pedido;
        mostrarCarregando();
        procurarCuriosidade(real).then(function (p) {
            if (meuPedido !== estado.pedido) { return; }
            if (!p) { mostrarMensagem('sem_resultados'); return; }
            mostrarCuriosidade(p, real);
        }).catch(function () {
            if (meuPedido !== estado.pedido) { return; }
            mostrarMensagem('erro_rede');
        });
    }

    function abrirTitulo(titulo, area) {
        var meuPedido = ++estado.pedido;
        mostrarCarregando();
        procurarPorTitulo(titulo).then(function (p) {
            if (meuPedido !== estado.pedido) { return; }
            if (!p) { mostrarMensagem('sem_resultados'); return; }
            mostrarCuriosidade(p, area);
            var topo = $('resultado');
            if (topo && topo.scrollIntoView) { topo.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
        }).catch(function () {
            if (meuPedido !== estado.pedido) { return; }
            mostrarMensagem('erro_rede');
        });
    }

    function marcarArea(area) {
        document.querySelectorAll('.area').forEach(function (b) {
            var ativo = b.getAttribute('data-area') === area;
            b.classList.toggle('ativa', ativo);
            b.setAttribute('aria-pressed', ativo ? 'true' : 'false');
        });
    }

    function atualizarContador() {
        $('descobertas').textContent = t('descobertas') + ': ' + estado.descobertas;
    }

    // ---------- textos da página e idioma ----------
    function construirAreas() {
        var grelha = $('areas');
        while (grelha.firstChild) { grelha.removeChild(grelha.firstChild); }
        AREAS.concat(['surpresa']).forEach(function (a) {
            var b = el('button', { type: 'button', 'class': 'area' + (a === 'surpresa' ? ' surpresa' : ''), 'data-area': a, 'aria-pressed': 'false' }, [
                el('span', { 'class': 'area-icone' }, [svg(a, '22')]),
                el('span', { 'class': 'area-texto' }, [el('strong', { texto: t('cat_' + a) }), el('small', { texto: t('cat_' + a + '_d') })])
            ]);
            b.style.setProperty('--cor-area', CORES[a]);
            b.addEventListener('click', function () { carregarArea(a); });
            grelha.appendChild(b);
        });
        marcarArea(estado.area);
    }

    function aplicarTextos() {
        document.documentElement.setAttribute('lang', C.LOCALES[C.idioma]);
        document.title = t('titulo') + ' · ' + t('versao') + ' ' + C.VERSAO;
        $('titulo').textContent = t('titulo');
        $('subtitulo').textContent = t('subtitulo');
        $('rotulo-idioma').textContent = t('idioma');
        $('rotulo-areas').textContent = t('escolhe_area');
        $('fonte').textContent = t('fonte');
        $('versao').textContent = t('versao') + ' ' + C.VERSAO;
        construirAreas();
        atualizarContador();
    }

    function iniciarSeletor() {
        var sel = $('idioma');
        C.SUPORTADOS.forEach(function (c) {
            sel.appendChild(el('option', { value: c, lang: c, texto: C.NOMES[c] }));
        });
        sel.value = C.idioma;
        sel.addEventListener('change', function () {
            C.definirIdioma(sel.value);
            aplicarTextos();
            if (estado.area) { carregarArea(estado.area); } // a curiosidade passa para a Wikipédia do novo idioma
        });
    }

    estado.descobertas = lerContador();
    iniciarSeletor();
    aplicarTextos();
})();
