/* Imagem Escondida 2.0: jogo de geografia com 3 níveis, cronómetro, vidas e mapa.
   Nível 1: continentes. Nível 2: países e bandeiras. Nível 3: capitais, línguas, moedas e curiosidades.
   A imagem de cada pergunta começa desfocada e fica nítida à medida que o tempo passa. */
(function () {
    'use strict';

    var J = window.Jogo;
    var t = J.t;
    var PAISES = window.PAISES;
    var CONT = window.CONTINENTES;
    var CK = ['Africa', 'Asia', 'Europe', 'NorthAmerica', 'SouthAmerica', 'Oceania'];

    var N_PERGUNTAS = 10;
    var VIDAS = 3;
    var NIVEIS = [
        { n: 1, cor: '#667eea', base: 20 },
        { n: 2, cor: '#f093fb', base: 15 },
        { n: 3, cor: '#ecc94b', base: 12 }
    ];
    var DESFOQUE = 14; // px de desfoque no início de cada pergunta com imagem
    var SVG_NS = 'http://www.w3.org/2000/svg';

    var POR_CC = {};
    PAISES.forEach(function (p) { POR_CC[p.c] = p; });

    function $(id) { return document.getElementById(id); }

    // ---------- utilidades ----------
    function el(tag, atributos, filhos) {
        var e = document.createElement(tag);
        if (atributos) {
            Object.keys(atributos).forEach(function (k) {
                var v = atributos[k];
                if (v === null || v === undefined || v === false) { return; }
                if (k === 'class') { e.className = v; }
                else if (k === 'text') { e.textContent = v; }
                else { e.setAttribute(k, v === true ? '' : v); }
            });
        }
        (filhos || []).forEach(function (f) { if (f) { e.appendChild(f); } });
        return e;
    }

    function icone(id, classe) {
        var s = document.createElementNS(SVG_NS, 'svg');
        s.setAttribute('class', 'ic' + (classe ? ' ' + classe : ''));
        s.setAttribute('aria-hidden', 'true');
        var u = document.createElementNS(SVG_NS, 'use');
        u.setAttribute('href', '#' + id);
        s.appendChild(u);
        return s;
    }

    function limpar(no) { while (no.firstChild) { no.removeChild(no.firstChild); } }

    function baralhar(lista) {
        var a = lista.slice();
        for (var i = a.length - 1; i > 0; i--) {
            var j = Math.floor(Math.random() * (i + 1));
            var tmp = a[i]; a[i] = a[j]; a[j] = tmp;
        }
        return a;
    }

    function norm(s) {
        return String(s).normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase()
            .replace(/[^a-z0-9]+/g, ' ').trim();
    }

    var ARTIGOS = /^(o|a|os|as|the|el|la|los|las|le|les|l|de|da|do|of)\s+/;
    function normResposta(s) {
        var n = norm(s);
        var antes;
        do { antes = n; n = n.replace(ARTIGOS, ''); } while (n !== antes);
        return n;
    }

    function distancia(a, b) {
        if (a === b) { return 0; }
        var prev = [], i, j;
        for (j = 0; j <= b.length; j++) { prev[j] = j; }
        for (i = 1; i <= a.length; i++) {
            var cur = [i];
            for (j = 1; j <= b.length; j++) {
                cur[j] = Math.min(prev[j] + 1, cur[j - 1] + 1, prev[j - 1] + (a[i - 1] === b[j - 1] ? 0 : 1));
            }
            prev = cur;
        }
        return prev[b.length];
    }

    var urlsBandeira = {};
    function urlBandeira(cc) {
        if (!urlsBandeira[cc]) {
            var svg = (window.BANDEIRAS || {})[cc] || '';
            urlsBandeira[cc] = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg);
        }
        return urlsBandeira[cc];
    }

    function nome(p) { return p.n[J.indice()]; }
    function capital(p) { return p.cap[J.indice()]; }
    function nomeCont(k) { return CONT[k][J.indice()]; }

    function capitalizar(texto) {
        return texto ? texto.charAt(0).toLocaleUpperCase(J.locale()) + texto.slice(1) : texto;
    }

    var nomesIntl = {};
    function intl(tipo) {
        var chave = tipo + J.locale();
        if (nomesIntl[chave] === undefined) {
            try { nomesIntl[chave] = new Intl.DisplayNames([J.locale()], { type: tipo }); } catch (e) { nomesIntl[chave] = null; }
        }
        return nomesIntl[chave];
    }
    function nomeLingua(codigo, ingles) {
        var d = intl('language'), r = null;
        try { r = d ? d.of(codigo) : null; } catch (e) { r = null; }
        // alguns navegadores só conhecem o código (devolvem "Dz"): nesse caso usa-se o nome em inglês
        if (!r || r.length <= 3 || norm(r) === norm(codigo)) { r = ingles || codigo; }
        return capitalizar(r);
    }
    function nomeMoeda(codigo, ingles) {
        var d = intl('currency'), r = null;
        try { r = d ? d.of(codigo) : null; } catch (e) { r = null; }
        if (!r || norm(r) === norm(codigo)) { r = ingles || codigo; }
        return capitalizar(r) + ' (' + codigo + ')';
    }

    function tiraEspacosNumero(n) {
        try { return new Intl.NumberFormat(J.locale()).format(n); } catch (e) { return String(n); }
    }

    // ---------- estado ----------
    var estado = null;      // jogo em curso
    var ecra = 'inicio';
    var relogio = { raf: 0, ultimo: 0, restante: 0, total: 0, ativo: false };
    var fichaToken = 0;
    var mapaIniciado = false;
    var sairPendente = 0;

    // ---------- ecrãs ----------
    var ECRAS = ['ecra-jogadores', 'ecra-inicio', 'ecra-jogo', 'ecra-fim'];
    function mostrarEcra(id) {
        ECRAS.forEach(function (e) { $(e).hidden = (e !== id); });
        ecra = id;
        $('idioma').disabled = (id === 'ecra-jogo' || id === 'ecra-fim');
        if (id !== 'ecra-jogo') { pararTempo(); }
        window.scrollTo(0, 0);
    }

    function textosEstaticos() {
        $('titulo').textContent = t('app_titulo');
        $('subtitulo').textContent = t('app_sub');
        $('rotulo-idioma').textContent = t('idioma');
        $('regras-titulo').textContent = t('como_jogar');
        $('regras-texto').textContent = t('regras');
        $('rot-pontos').textContent = t('pontos');
        $('rot-seq').textContent = t('sequencia');
        $('btn-responder').textContent = t('responder');
        $('resposta-escrita').setAttribute('placeholder', t('escrever_ph'));
        $('resposta-escrita').setAttribute('aria-label', t('escrever_ph'));
        $('btn-sair').textContent = t('sair');
        $('jogadores-titulo').textContent = t('mudar_jogador');
        $('jogadores-info').textContent = t('jogadores_info');
        $('form-titulo').textContent = t('novo_jogador');
        $('rotulo-nome').textContent = t('nome_jogador');
        $('rotulo-avatar').textContent = t('escolher_avatar');
        $('btn-criar').textContent = t('criar');
        $('btn-fechar-jogadores').textContent = t('fechar');
        $('ranking-titulo').textContent = t('ranking');
        $('rodape-fontes').textContent = t('fontes');
        $('rodape-versao').textContent = t('versao', J.VERSAO);
        $('ligacao-google').textContent = '';
        document.title = t('app_titulo') + ' - ' + 'Exercício 012';
        var total = window.Perfil.atual() ? window.Perfil.total(window.Perfil.atual()) : 0;
        $('rodape-total').textContent = t('total') + ': ' + tiraEspacosNumero(total);
    }

    // ---------- jogadores ----------
    var avatarEscolhido = 0;

    function avatarEl(indice, grande) {
        var a = window.Perfil.AVATARES[indice] || window.Perfil.AVATARES[0];
        var e = el('span', { class: 'avatar' + (grande ? ' grande' : '') });
        e.style.backgroundColor = a.cor;
        e.appendChild(icone(a.icone));
        return e;
    }

    function renderChip() {
        var b = $('btn-jogador');
        limpar(b);
        var j = window.Perfil.atual();
        if (j) {
            b.appendChild(avatarEl(j.av));
            b.appendChild(el('span', { class: 'chip-nome', text: j.nome }));
        } else {
            b.appendChild(icone('i-utilizador'));
            b.appendChild(el('span', { class: 'chip-nome', text: t('jogador') }));
        }
        b.setAttribute('aria-label', t('mudar_jogador'));
    }

    function renderAvatares() {
        var cx = $('avatares');
        limpar(cx);
        window.Perfil.AVATARES.forEach(function (a, i) {
            var b = el('button', {
                type: 'button', class: 'avatar-opcao', role: 'radio',
                'aria-checked': i === avatarEscolhido ? 'true' : 'false'
            });
            b.appendChild(avatarEl(i, true));
            b.addEventListener('click', function () { avatarEscolhido = i; renderAvatares(); });
            cx.appendChild(b);
        });
    }

    function renderJogadores() {
        var P = window.Perfil;
        var lista = $('lista-jogadores');
        limpar(lista);
        var atual = P.atual();
        P.jogadores().forEach(function (j) {
            var linha = el('li', { class: 'jogador' + (atual && atual.id === j.id ? ' ativo' : '') });
            linha.appendChild(avatarEl(j.av, true));
            linha.appendChild(el('div', { class: 'jogador-info' }, [
                el('strong', { text: j.nome }),
                el('span', { class: 'pequeno', text: t('total') + ': ' + tiraEspacosNumero(P.total(j)) + ' · ' + t('jogos') + ': ' + j.est.jogos })
            ]));
            var acoes = el('div', { class: 'jogador-acoes' });
            if (atual && atual.id === j.id) {
                acoes.appendChild(el('span', { class: 'etiqueta', text: t('em_uso') }));
            } else {
                var esc = el('button', { type: 'button', class: 'botao pequeno-botao', text: t('escolher') });
                esc.addEventListener('click', function () { P.escolher(j.id); renderTudo(); mostrarEcra('ecra-inicio'); });
                acoes.appendChild(esc);
            }
            var ap = el('button', { type: 'button', class: 'botao secundario pequeno-botao', text: t('apagar') });
            var pendente = false, tempo = 0;
            ap.addEventListener('click', function () {
                if (!pendente) {
                    pendente = true;
                    ap.textContent = t('apagar_confirmar');
                    ap.classList.add('perigo');
                    tempo = setTimeout(function () { pendente = false; ap.textContent = t('apagar'); ap.classList.remove('perigo'); }, 3000);
                } else {
                    clearTimeout(tempo);
                    P.apagar(j.id);
                    renderTudo();
                    mostrarEcra(P.atual() ? 'ecra-jogadores' : 'ecra-jogadores');
                }
            });
            acoes.appendChild(ap);
            linha.appendChild(acoes);
            lista.appendChild(linha);
        });

        var rk = $('ranking');
        limpar(rk);
        var ordenados = P.ranking().filter(function (j) { return P.total(j) > 0; });
        if (!ordenados.length) {
            rk.appendChild(el('li', { class: 'ranking-vazio', text: t('ranking_vazio') }));
        }
        ordenados.forEach(function (j, i) {
            var estrelas = j.n.reduce(function (s, n) { return s + n.estrelas; }, 0);
            rk.appendChild(el('li', { class: 'ranking-linha' }, [
                el('span', { class: 'ranking-pos', text: String(i + 1) }),
                avatarEl(j.av),
                el('span', { class: 'ranking-nome', text: j.nome }),
                el('span', { class: 'ranking-estrelas', 'aria-label': t('estrelas_de', Math.min(3, Math.round(estrelas / 3))) }, [
                    icone('i-estrela', 'estrela cheia'), el('span', { text: String(estrelas) })
                ]),
                el('strong', { class: 'ranking-pontos', text: tiraEspacosNumero(P.total(j)) })
            ]));
        });
        $('btn-fechar-jogadores').hidden = !atual;
        $('erro-jogador').hidden = true;
        renderAvatares();
        $('form-titulo').textContent = atual ? t('novo_jogador') : t('quem_joga');
    }

    function abrirJogadores() {
        renderJogadores();
        mostrarEcra('ecra-jogadores');
        $('nome-jogador').focus();
    }

    // ---------- menu ----------
    function estrelasEl(n) {
        var c = el('span', { class: 'estrelas', role: 'img', 'aria-label': t('estrelas_de', n) });
        for (var i = 0; i < 3; i++) { c.appendChild(icone('i-estrela', 'estrela' + (i < n ? ' cheia' : ''))); }
        return c;
    }

    function renderMenu() {
        var P = window.Perfil;
        var j = P.atual();
        var cx = $('niveis');
        limpar(cx);
        NIVEIS.forEach(function (nv) {
            var aberto = P.desbloqueado(nv.n);
            var dados = j ? j.n[nv.n - 1] : { melhor: 0, estrelas: 0 };
            var cartao = el('article', { class: 'nivel' + (aberto ? '' : ' bloqueado') });
            cartao.style.setProperty('--cor-nivel', nv.cor);
            cartao.appendChild(el('div', { class: 'nivel-num', text: String(nv.n) }));
            var corpo = el('div', { class: 'nivel-corpo' }, [
                el('h2', { text: t('nivel1_nome'.replace('1', String(nv.n))) }),
                el('p', { text: t('nivel1_desc'.replace('1', String(nv.n))) })
            ]);
            var rodape = el('div', { class: 'nivel-rodape' });
            if (aberto) {
                rodape.appendChild(estrelasEl(dados.estrelas));
                rodape.appendChild(el('span', { class: 'pequeno', text: t('melhor') + ': ' + tiraEspacosNumero(dados.melhor) }));
                var b = el('button', { type: 'button', class: 'botao', 'aria-label': t('jogar_nivel', nv.n) }, [el('span', { text: t('jogar') }), icone('i-seguinte')]);
                b.addEventListener('click', function () { iniciarNivel(nv.n); });
                rodape.appendChild(b);
            } else {
                rodape.appendChild(icone('i-cadeado'));
                rodape.appendChild(el('span', { class: 'pequeno', text: t('desbloqueia', nv.n - 1) }));
            }
            corpo.appendChild(rodape);
            cartao.appendChild(corpo);
            cx.appendChild(cartao);
        });
    }

    function renderTudo() {
        textosEstaticos();
        renderChip();
        renderMenu();
        if (ecra === 'ecra-jogadores') { renderJogadores(); }
    }

    // ---------- geração de perguntas ----------
    function ambiguo(p) { return !!p.kk; }

    function sorteio(filtro, usados, quantidade) {
        var pool = baralhar(PAISES.filter(function (p) { return !usados[p.c] && (!filtro || filtro(p)); }));
        return pool.slice(0, quantidade);
    }

    function gerarNivel(n) {
        var usados = {};
        var comp = [];
        function adicionar(tipo, quantidade, filtro, extra) {
            var escolhidos = sorteio(filtro, usados, quantidade);
            escolhidos.forEach(function (p) {
                usados[p.c] = true;
                var q = { tipo: tipo, p: p };
                if (extra) { extra(q); }
                comp.push(q);
            });
        }
        var grande = function (p) { return !!p.g; };
        var semAmbiguos = function (p) { return !ambiguo(p); };

        if (n === 1) {
            baralhar(CK).slice(0, 3).forEach(function (k) { comp.push({ tipo: 'mapa-continente', k: k }); });
            adicionar('bandeira-continente', 3, semAmbiguos);
            adicionar('pais-continente', 2, semAmbiguos);
            adicionar('escrever-continente', 2, semAmbiguos);
        } else if (n === 2) {
            adicionar('bandeira-pais', 3);
            adicionar('mapa-pais', 3, grande);
            adicionar('escrever-bandeira', 2);
            adicionar('nome-bandeira', 2);
        } else {
            adicionar('capital-escolha', 2);
            adicionar('capital-escrever', 2);
            adicionar('mapa-capital', 1, grande);
            adicionar('curiosidade', 3, null, function (q) { q.fonte = Math.random() < 0.5 ? 'pais' : 'cidade'; });
            adicionar('lingua', 1);
            adicionar('moeda', 1);
        }
        var ordem = baralhar(comp);
        // as curiosidades vão para o fim, para a Wikipédia ter tempo de responder
        var curiosas = ordem.filter(function (q) { return q.tipo === 'curiosidade'; });
        var outras = ordem.filter(function (q) { return q.tipo !== 'curiosidade'; });
        var final = outras.slice(0, 3);
        var resto = baralhar(outras.slice(3).concat(curiosas));
        return final.concat(resto);
    }

    function opcoesDe(correta, candidatos, quantidade) {
        // devolve a correta mais (quantidade-1) diferentes (por chave e por texto), embaralhadas;
        // os candidatos vêm já por ordem de preferência
        var vistos = {};
        vistos[correta.chave] = true;
        vistos['r:' + norm(correta.rotulo)] = true;
        var extra = [];
        candidatos.forEach(function (c) {
            if (extra.length >= quantidade - 1) { return; }
            var r = 'r:' + norm(c.rotulo);
            if (vistos[c.chave] || vistos[r]) { return; }
            vistos[c.chave] = true;
            vistos[r] = true;
            extra.push(c);
        });
        return baralhar([correta].concat(extra));
    }

    function vizinhos(p, nivel) {
        // no nível 1 os distratores são quaisquer; nos outros, primeiro do mesmo continente
        var mesmo = PAISES.filter(function (q) { return q.c !== p.c && q.k === p.k; });
        var outros = PAISES.filter(function (q) { return q.c !== p.c && q.k !== p.k; });
        return nivel === 1 ? baralhar(outros.concat(mesmo)) : baralhar(mesmo).concat(baralhar(outros));
    }

    // ---------- tempo ----------
    function segundosPara(q) {
        var base = NIVEIS[estado.nivel - 1].base;
        switch (q.tipo) {
            case 'escrever-continente': case 'escrever-bandeira': case 'capital-escrever': return base + 12;
            case 'mapa-continente': case 'mapa-pais': case 'mapa-capital': return base + 12;
            case 'curiosidade': return base + 18;
            default: return base;
        }
    }

    function desfocar(fraccao) {
        var imgs = document.querySelectorAll('#conteudo .revelar');
        for (var i = 0; i < imgs.length; i++) {
            imgs[i].style.filter = fraccao > 0.001 ? 'blur(' + (DESFOQUE * fraccao).toFixed(2) + 'px)' : 'none';
        }
    }

    function pintarTempo() {
        var f = relogio.total ? Math.max(0, relogio.restante / relogio.total) : 0;
        var barra = $('barra-tempo');
        barra.style.transform = 'scaleX(' + f.toFixed(4) + ')';
        barra.classList.toggle('pouco', f < 0.3);
        $('hud-tempo').textContent = String(Math.max(0, Math.ceil(relogio.restante)));
        desfocar(f);
    }

    function passo(agora) {
        if (!relogio.ativo) { return; }
        var dt = Math.min(agora - relogio.ultimo, 250);
        relogio.ultimo = agora;
        relogio.restante -= dt / 1000;
        if (relogio.restante <= 0) {
            relogio.restante = 0;
            pintarTempo();
            resolver({ certa: false, tempo: true });
            return;
        }
        pintarTempo();
        relogio.raf = requestAnimationFrame(passo);
    }

    function iniciarTempo(segundos) {
        pararTempo();
        relogio.total = segundos;
        relogio.restante = segundos;
        relogio.ativo = true;
        relogio.ultimo = performance.now();
        pintarTempo();
        relogio.raf = requestAnimationFrame(passo);
    }

    function pararTempo() {
        relogio.ativo = false;
        if (relogio.raf) { cancelAnimationFrame(relogio.raf); relogio.raf = 0; }
    }

    // ---------- fluxo do nível ----------
    function iniciarNivel(n) {
        if (!window.Perfil.atual() || !window.Perfil.desbloqueado(n)) { return; }
        estado = {
            nivel: n, perguntas: gerarNivel(n), i: 0, vidas: VIDAS, pontos: 0, seq: 0, certas: 0,
            respondidas: 0, tempoMs: 0, aberta: false, atual: null, token: 0, resultados: []
        };
        if (n === 3) { prefetchCuriosidades(); }
        mostrarEcra('ecra-jogo');
        var prog = $('progresso');
        limpar(prog);
        for (var i = 0; i < N_PERGUNTAS; i++) { prog.appendChild(el('span', { class: 'ponto' })); }
        $('ecra-jogo').style.setProperty('--cor-nivel', NIVEIS[n - 1].cor);
        $('hud-nivel').textContent = t('nivel') + ' ' + n + ' · ' + t('nivel' + n + '_nome');
        $('btn-sair').textContent = t('sair');
        atualizarHud();
        proximaPergunta();
    }

    function atualizarHud() {
        $('hud-pontos').textContent = tiraEspacosNumero(estado.pontos);
        $('hud-seq').textContent = String(estado.seq);
        $('hud-pergunta').textContent = t('pergunta_n', Math.min(estado.i + 1, N_PERGUNTAS), N_PERGUNTAS);
        var v = $('hud-vidas');
        limpar(v);
        v.setAttribute('aria-label', t('vidas') + ': ' + estado.vidas);
        for (var i = 0; i < VIDAS; i++) { v.appendChild(icone('i-coracao', 'coracao' + (i < estado.vidas ? '' : ' perdido'))); }
        var pontos = $('progresso').children;
        for (var k = 0; k < pontos.length; k++) {
            var c = 'ponto';
            if (k < estado.resultados.length) { c += estado.resultados[k] ? ' certo' : ' errado'; }
            else if (k === estado.i) { c += ' atual'; }
            pontos[k].className = c;
        }
    }

    function prefetchCuriosidades() {
        estado.perguntas.forEach(function (q) {
            if (q.tipo === 'curiosidade') { q.promessa = pedirCuriosidade(q); }
        });
    }

    function termosNomes(p) {
        return p.n.concat(p.a ? p.a.filter(function (x) { return x.length <= 25; }) : []);
    }

    function pedirCuriosidade(q) {
        var lingua = J.idioma(), p = q.p;
        if (q.fonte === 'cidade') {
            return window.Wiki.sobreCapital(lingua, capital(p), nome(p), termosNomes(p));
        }
        return window.Wiki.sobrePais(lingua, nome(p), capital(p), termosNomes(p));
    }

    function proximaPergunta() {
        if (estado.i >= N_PERGUNTAS || estado.vidas <= 0) { terminar(); return; }
        var q = estado.perguntas[estado.i];
        estado.token += 1;
        var token = estado.token;
        estado.aberta = false;
        estado.atual = q;
        fichaToken += 1;

        $('feedback').hidden = true;
        $('mapa-caixa').hidden = true;
        $('aviso-mapa').hidden = true;
        limpar($('conteudo'));
        $('conteudo').className = 'conteudo';
        limpar($('opcoes'));
        $('form-escrita').hidden = true;
        $('resposta-escrita').value = '';
        $('dica').textContent = '';
        $('pergunta').textContent = '';
        window.Mapa.modoClique(null);
        if (mapaIniciado) { window.Mapa.repor(); }
        atualizarHud();
        $('barra-tempo').style.transform = 'scaleX(1)';
        $('barra-tempo').classList.remove('pouco');

        if (q.tipo === 'curiosidade') {
            $('pergunta').textContent = t('a_carregar');
            q.promessa = q.promessa || pedirCuriosidade(q);
            q.promessa.then(function (r) {
                if (estado.token !== token) { return; }
                var util = r ? prepararCuriosidade(q, r) : null;
                if (!util) {
                    // sem Wikipédia: troca por uma pergunta de capital sobre o mesmo país
                    q.tipo = 'capital-escolha';
                }
                montar(q, token);
            });
            return;
        }
        montar(q, token);
    }

    function prepararCuriosidade(q, r) {
        var termos = q.fonte === 'cidade' ? termosNomes(q.p).concat(q.p.cap) : termosNomes(q.p);
        var texto = window.Wiki.mascarar(r.texto, termos);
        var partes = texto.split(/\s+/);
        var mascaras = partes.filter(function (x) { return x.indexOf('___') !== -1; }).length;
        if (partes.length < 12 || mascaras / partes.length > 0.25) { return null; }
        q.curiosidadeTexto = texto;
        q.curiosidadeBruta = r;
        return q;
    }

    // ---------- montar cada tipo de pergunta ----------
    function bandeiraEl(p, desfocada) {
        var img = el('img', {
            class: 'bandeira' + (desfocada ? ' revelar' : ''), src: urlBandeira(p.c),
            alt: desfocada ? '' : ''
        });
        img.setAttribute('draggable', 'false');
        return img;
    }

    function botaoOpcao(rotulo, id, indice, conteudoExtra) {
        var b = el('button', { type: 'button', class: 'opcao', 'data-id': id }, [
            el('span', { class: 'opcao-num', text: String(indice + 1) }),
            conteudoExtra || el('span', { class: 'opcao-texto', text: rotulo })
        ]);
        if (!conteudoExtra) { b.setAttribute('aria-label', rotulo); }
        return b;
    }

    function mostrarOpcoes(lista, testar) {
        var cx = $('opcoes');
        limpar(cx);
        lista.forEach(function (o, i) {
            var b = botaoOpcao(o.rotulo, o.chave, i, o.extra);
            b.addEventListener('click', function () { escolherOpcao(o, b, testar); });
            cx.appendChild(b);
        });
        estado.opcoes = lista;
    }

    function escolherOpcao(o, botao, testar) {
        if (!estado.aberta) { return; }
        var certa = testar(o);
        var botoes = $('opcoes').children;
        for (var i = 0; i < botoes.length; i++) {
            var op = estado.opcoes[i];
            botoes[i].disabled = true;
            if (testar(op)) { botoes[i].classList.add('certa'); }
        }
        if (!certa) { botao.classList.add('errada'); }
        resolver({ certa: certa });
    }

    function montar(q, token) {
        if (estado.token !== token) { return; }
        var p = q.p;
        var i = J.indice();
        var tipo = q.tipo;
        var pergunta = $('pergunta');
        var conteudo = $('conteudo');
        var correta = null;       // texto da resposta certa para mostrar
        var certaDetalhe = {};

        function pronto() {
            estado.aberta = true;
            estado.respostaCerta = correta;
            iniciarTempo(segundosPara(q));
        }

        if (tipo === 'mapa-continente') {
            pergunta.textContent = t('q_mapa_continente', nomeCont(q.k));
            correta = nomeCont(q.k);
            modoMapa(function (cc) {
                var clicado = POR_CC[cc];
                var ks = [clicado.k].concat(clicado.kk || []);
                var certa = ks.indexOf(q.k) !== -1;
                resolver({ certa: certa, clicado: cc });
            });
        } else if (tipo === 'bandeira-continente' || tipo === 'pais-continente') {
            if (tipo === 'bandeira-continente') {
                pergunta.textContent = t('q_bandeira_continente');
                conteudo.appendChild(bandeiraEl(p, true));
            } else {
                pergunta.textContent = t('q_pais_continente', nome(p));
                conteudo.appendChild(el('p', { class: 'nome-grande', text: nome(p) }));
            }
            correta = nomeCont(p.k);
            var optsC = opcoesDe({ chave: p.k, rotulo: nomeCont(p.k) },
                baralhar(CK.filter(function (k) { return k !== p.k; })).map(function (k) { return { chave: k, rotulo: nomeCont(k) }; }), 4);
            mostrarOpcoes(optsC, function (o) { return o.chave === p.k; });
        } else if (tipo === 'escrever-continente') {
            pergunta.textContent = t('q_escrever_continente', nome(p));
            conteudo.appendChild(bandeiraEl(p, true));
            correta = nomeCont(p.k);
            modoEscrita(function (texto) {
                var aceites = CONT[p.k].map(normResposta);
                var n = normResposta(texto);
                return aceites.some(function (a) { return a === n || (a.length >= 6 && distancia(a, n) <= 1); });
            });
        } else if (tipo === 'bandeira-pais') {
            pergunta.textContent = t('q_bandeira_pais');
            conteudo.appendChild(bandeiraEl(p, true));
            correta = nome(p);
            var optsB = opcoesDe({ chave: p.c, rotulo: nome(p) },
                vizinhos(p, estado.nivel).map(function (v) { return { chave: v.c, rotulo: nome(v) }; }), 4);
            mostrarOpcoes(optsB, function (o) { return o.chave === p.c; });
        } else if (tipo === 'mapa-pais') {
            pergunta.textContent = t('q_mapa_pais', nome(p));
            correta = nome(p);
            modoMapa(function (cc) { resolver({ certa: cc === p.c, clicado: cc }); });
        } else if (tipo === 'escrever-bandeira') {
            pergunta.textContent = t('q_escrever_bandeira');
            conteudo.appendChild(bandeiraEl(p, true));
            correta = nome(p);
            modoEscrita(function (texto) { return corresponde(texto, nomesAceites(p), p.c); });
        } else if (tipo === 'nome-bandeira') {
            pergunta.textContent = t('q_nome_bandeira', nome(p));
            correta = nome(p);
            var optsF = opcoesDe({ chave: p.c, rotulo: nome(p) },
                vizinhos(p, estado.nivel).map(function (v) { return { chave: v.c, rotulo: nome(v) }; }), 4)
                .map(function (o, k) {
                    var img = el('img', { class: 'bandeira opcao-bandeira', src: urlBandeira(o.chave), alt: '' });
                    img.setAttribute('draggable', 'false');
                    return { chave: o.chave, rotulo: t('aria_bandeira', k + 1), extra: img };
                });
            mostrarOpcoes(optsF, function (o) { return o.chave === p.c; });
            conteudo.classList.add('compacto');
        } else if (tipo === 'capital-escolha') {
            pergunta.textContent = t('q_capital_escolha', nome(p));
            conteudo.appendChild(bandeiraEl(p, false));
            correta = capital(p);
            var optsK = opcoesDe({ chave: p.c, rotulo: capital(p) },
                vizinhos(p, estado.nivel).map(function (v) { return { chave: v.c, rotulo: capital(v) }; }), 4);
            mostrarOpcoes(optsK, function (o) { return o.chave === p.c; });
        } else if (tipo === 'capital-escrever') {
            pergunta.textContent = t('q_capital_escrever', nome(p));
            conteudo.appendChild(bandeiraEl(p, false));
            correta = capital(p);
            modoEscrita(function (texto) { return corresponde(texto, capitaisAceites(p), p.c); });
        } else if (tipo === 'mapa-capital') {
            pergunta.textContent = t('q_mapa_capital', capital(p));
            correta = nome(p);
            modoMapa(function (cc) { resolver({ certa: cc === p.c, clicado: cc }); });
        } else if (tipo === 'curiosidade') {
            pergunta.textContent = q.fonte === 'cidade' ? t('q_curiosidade_cidade') : t('q_curiosidade_pais');
            var bloco = el('div', { class: 'curiosidade-pergunta' });
            bloco.appendChild(el('blockquote', { text: q.curiosidadeTexto }));
            if (q.curiosidadeBruta.imagem) {
                var foto = el('img', { class: 'foto revelar', src: q.curiosidadeBruta.imagem, alt: '', referrerpolicy: 'no-referrer' });
                foto.addEventListener('error', function () { foto.remove(); });
                bloco.appendChild(foto);
            }
            conteudo.appendChild(bloco);
            correta = nome(p);
            var optsQ = opcoesDe({ chave: p.c, rotulo: nome(p) },
                vizinhos(p, estado.nivel).map(function (v) { return { chave: v.c, rotulo: nome(v) }; }), 4);
            mostrarOpcoes(optsQ, function (o) { return o.chave === p.c; });
        } else if (tipo === 'lingua') {
            pergunta.textContent = t('q_lingua', nome(p));
            conteudo.appendChild(bandeiraEl(p, false));
            var minhas = p.l.map(function (cod, k) { return nomeLingua(cod, p.ln[k]); });
            correta = minhas.join(' / ');
            var certaL = { chave: 'certa', rotulo: minhas[0] };
            var distL = [];
            var vistosL = {};
            minhas.forEach(function (m) { vistosL[norm(m)] = true; });
            baralhar(PAISES).forEach(function (o) {
                if (o.c === p.c || distL.length >= 3) { return; }
                var k = Math.floor(Math.random() * o.l.length);
                var rot = nomeLingua(o.l[k], o.ln[k]);
                if (vistosL[norm(rot)]) { return; }
                vistosL[norm(rot)] = true;
                distL.push({ chave: 'x' + o.c, rotulo: rot });
            });
            var listaL = baralhar([certaL].concat(distL));
            mostrarOpcoes(listaL, function (o) { return o.chave === 'certa' || minhas.indexOf(o.rotulo) !== -1; });
        } else if (tipo === 'moeda') {
            pergunta.textContent = t('q_moeda', nome(p));
            conteudo.appendChild(bandeiraEl(p, false));
            var minhasM = p.m.map(function (cod, k) { return nomeMoeda(cod, p.mn[k]); });
            correta = minhasM.join(' / ');
            var certaM = { chave: 'certa', rotulo: minhasM[0] };
            var distM = [];
            var vistosM = {};
            minhasM.forEach(function (m) { vistosM[norm(m)] = true; });
            baralhar(PAISES).forEach(function (o) {
                if (o.c === p.c || distM.length >= 3) { return; }
                var rot = nomeMoeda(o.m[0], o.mn[0]);
                if (vistosM[norm(rot)]) { return; }
                vistosM[norm(rot)] = true;
                distM.push({ chave: 'x' + o.c, rotulo: rot });
            });
            var listaM = baralhar([certaM].concat(distM));
            mostrarOpcoes(listaM, function (o) { return o.chave === 'certa' || minhasM.indexOf(o.rotulo) !== -1; });
        }
        void i; void certaDetalhe;
        estado.p = p;
        pronto();
    }

    function modoMapa(aoClicar) {
        var caixa = $('mapa-caixa');
        caixa.hidden = false;
        if (!mapaIniciado) {
            window.Mapa.iniciar($('mapa'));
            mapaIniciado = true;
        }
        window.Mapa.repor();
        window.Mapa.modoClique(function (cc) {
            if (!estado.aberta) { return; }
            if (!cc || !POR_CC[cc]) { avisoMapa(t('territorio')); return; }
            aoClicar(cc);
        });
        $('dica').textContent = t('dica_mapa');
    }

    var avisoTempo = 0;
    function avisoMapa(texto) {
        var a = $('aviso-mapa');
        a.textContent = texto;
        a.hidden = false;
        clearTimeout(avisoTempo);
        avisoTempo = setTimeout(function () { a.hidden = true; }, 2600);
    }

    var testeEscrita = null;
    function modoEscrita(teste) {
        testeEscrita = teste;
        var f = $('form-escrita');
        f.hidden = false;
        $('dica').textContent = t('dica_escrever');
        setTimeout(function () { if (!f.hidden) { $('resposta-escrita').focus(); } }, 30);
    }

    function nomesAceites(p) {
        var lista = p.n.slice();
        (p.a || []).forEach(function (x) { lista.push(x); });
        return lista;
    }
    function capitaisAceites(p) {
        return p.cap.concat(p.ac || []);
    }

    var todosNomes = null;
    function mapaNomes() {
        if (todosNomes) { return todosNomes; }
        todosNomes = {};
        PAISES.forEach(function (p) {
            p.n.concat(p.cap).concat(p.a || []).concat(p.ac || []).forEach(function (x) {
                var k = normResposta(x);
                if (k) { (todosNomes[k] = todosNomes[k] || {})[p.c] = true; }
            });
        });
        return todosNomes;
    }

    function corresponde(texto, aceites, cc) {
        var n = normResposta(texto);
        if (!n) { return false; }
        var normais = aceites.map(normResposta);
        if (normais.indexOf(n) !== -1) { return true; }
        // se escreveu exatamente outro país/capital, é erro mesmo
        var outro = mapaNomes()[n];
        if (outro && !outro[cc]) { return false; }
        return normais.some(function (a) {
            var tol = a.length >= 11 ? 2 : (a.length >= 6 ? 1 : 0);
            return tol > 0 && distancia(a, n) <= tol;
        });
    }

    // ---------- resposta ----------
    function pontosDaResposta(q) {
        var rapidez = relogio.total ? Math.round(100 * Math.max(0, relogio.restante) / relogio.total) : 0;
        var escrita = /^escrever|capital-escrever/.test(q.tipo) ? 20 : 0;
        return 100 + rapidez + escrita + Math.min(estado.seq, 5) * 10;
    }

    function resolver(r) {
        if (!estado || !estado.aberta) { return; }
        estado.aberta = false;
        var q = estado.atual;
        var gasto = Math.max(0, relogio.total - relogio.restante);
        pararTempo();
        desfocar(0);
        window.Mapa.modoClique(null);
        $('form-escrita').hidden = true;
        estado.respondidas += 1;
        estado.tempoMs += gasto * 1000;

        var ganhos = 0;
        if (r.certa) {
            ganhos = pontosDaResposta(q);
            estado.pontos += ganhos;
            estado.seq += 1;
            estado.certas += 1;
        } else {
            estado.vidas -= 1;
            estado.seq = 0;
        }
        estado.resultados.push(!!r.certa);
        atualizarHud();
        // as opções sem resposta (tempo) também mostram a certa
        if (r.tempo) {
            var botoes = $('opcoes').children;
            for (var i = 0; i < botoes.length; i++) {
                botoes[i].disabled = true;
                if (estado.opcoes && estado.opcoes[i] && opcaoCerta(q, estado.opcoes[i])) { botoes[i].classList.add('certa'); }
            }
        }
        mostrarFeedback(q, r, ganhos);
    }

    function opcaoCerta(q, o) {
        var p = q.p;
        if (!p) { return false; }
        if (q.tipo === 'bandeira-continente' || q.tipo === 'pais-continente') { return o.chave === p.k; }
        if (q.tipo === 'lingua' || q.tipo === 'moeda') { return o.chave === 'certa'; }
        return o.chave === p.c;
    }

    function mostrarFeedback(q, r, ganhos) {
        var fb = $('feedback');
        var msg = $('feedback-msg');
        limpar(msg);
        msg.className = 'feedback-msg ' + (r.certa ? 'bom' : 'mau');
        msg.appendChild(icone(r.certa ? 'i-certo' : 'i-errado'));
        var texto = r.certa ? t('certo', ganhos) : (r.tempo ? t('tempo_esgotado') : t('errado'));
        msg.appendChild(el('span', { text: texto }));
        if (!r.certa) {
            var linha = t('resposta_certa', estado.respostaCerta);
            if (r.clicado && POR_CC[r.clicado]) { linha = t('clicaste_em', nome(POR_CC[r.clicado])) + ' ' + linha; }
            msg.appendChild(el('span', { class: 'resposta-certa', text: linha }));
        }

        // mapa: país certo, país clicado e contexto do OpenStreetMap
        var p = q.p || (r.clicado ? POR_CC[r.clicado] : null);
        var mapaCaixa = $('mapa-caixa');
        mapaCaixa.hidden = false;
        if (!mapaIniciado) { window.Mapa.iniciar($('mapa')); mapaIniciado = true; }
        window.Mapa.redimensionar();
        if (q.tipo === 'mapa-continente' || q.tipo === 'bandeira-continente' || q.tipo === 'pais-continente' || q.tipo === 'escrever-continente') {
            var doContinente = PAISES.filter(function (x) { return x.k === (q.k || q.p.k); }).map(function (x) { return x.c; });
            window.Mapa.destacar(doContinente, 'continente');
            if (q.tipo === 'mapa-continente' && r.clicado) { window.Mapa.destacar(r.clicado, r.certa ? 'certo' : 'errado'); }
            window.Mapa.mundo();
        } else {
            if (q.p) { window.Mapa.destacar(q.p.c, r.certa ? 'certo' : 'alvo'); }
            if (r.clicado && (!q.p || r.clicado !== q.p.c)) { window.Mapa.destacar(r.clicado, 'errado'); }
            if (q.p) { window.Mapa.ajustar(q.p.c, q.p.ll); }
        }
        window.Mapa.contexto(true);

        // identidade do país
        var fichaP = q.p || (r.clicado ? POR_CC[r.clicado] : null);
        renderFicha(fichaP);

        // ligações
        var google = $('ligacao-google');
        limpar(google);
        google.appendChild(icone('i-pin'));
        google.appendChild(el('span', { text: t('ver_google') }));
        var alvo = fichaP ? (/capital|mapa-capital/.test(q.tipo) || q.fonte === 'cidade' ? capital(fichaP) + ', ' + nome(fichaP) : nome(fichaP)) : nomeCont(q.k);
        google.href = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(alvo);
        $('ligacao-wiki').hidden = true;

        // curiosidade da Wikipédia
        var cur = $('curiosidade');
        limpar(cur);
        var token = ++fichaToken;
        if (fichaP) {
            cabecalhoCuriosidade(cur, fichaP);
            cur.appendChild(el('p', { class: 'pequeno', text: t('a_procurar') }));
            var lingua = J.idioma();
            var promessa;
            var usaCidade = /capital|mapa-capital/.test(q.tipo) || (q.tipo === 'curiosidade' && q.fonte === 'cidade');
            if (q.tipo === 'curiosidade' && q.curiosidadeBruta) { promessa = Promise.resolve(q.curiosidadeBruta); }
            else if (usaCidade) { promessa = window.Wiki.sobreCapital(lingua, capital(fichaP), nome(fichaP), termosNomes(fichaP)); }
            else { promessa = window.Wiki.sobrePais(lingua, nome(fichaP), capital(fichaP), termosNomes(fichaP)); }
            promessa.then(function (w) {
                if (token !== fichaToken || ecra !== 'ecra-jogo') { return; }
                limpar(cur);
                cabecalhoCuriosidade(cur, fichaP);
                if (!w) { cur.appendChild(el('p', { class: 'pequeno', text: t('sem_curiosidade') })); return; }
                var corpo = el('div', { class: 'curiosidade-corpo' });
                if (w.imagem) {
                    var img = el('img', { class: 'foto-pequena', src: w.imagem, alt: '', referrerpolicy: 'no-referrer' });
                    img.addEventListener('error', function () { img.remove(); });
                    corpo.appendChild(img);
                }
                corpo.appendChild(el('p', { text: w.texto }));
                cur.appendChild(corpo);
                var wiki = $('ligacao-wiki');
                limpar(wiki);
                wiki.appendChild(icone('i-externo'));
                wiki.appendChild(el('span', { text: t('ver_wiki') }));
                wiki.href = w.url;
                wiki.hidden = false;
            });
        }

        var ultimo = estado.i >= N_PERGUNTAS - 1 || estado.vidas <= 0;
        var btn = $('btn-seguinte');
        limpar(btn);
        btn.appendChild(el('span', { text: ultimo ? t('ver_resultado') : t('seguinte') }));
        btn.appendChild(icone('i-seguinte'));
        $('dica').textContent = '';
        fb.hidden = false;
        btn.focus({ preventScroll: true });
        var reduzir = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        fb.scrollIntoView({ block: 'nearest', behavior: reduzir ? 'auto' : 'smooth' });
    }

    /** «Nome verdadeiro»: como o país se chama no próprio território (dados/nomes-locais.js). Só aparece depois da resposta. */
    function blocoNomeLocal(p) {
        var lista = p && window.NOMES_LOCAIS && window.NOMES_LOCAIS[p.c];
        if (!lista || !lista.length) { return null; }
        var idx = J.indice();
        var caixa = el('div', { class: 'nome-local' });
        caixa.appendChild(el('strong', { class: 'nome-local-titulo', text: t('nome_local') }));
        caixa.appendChild(el('span', { class: 'nome-local-ajuda', text: t('nome_local_ajuda') }));
        var ul = el('ul');
        lista.slice(0, 3).forEach(function (e) {
            var comum = e[1] || e[2];
            var li = el('li');
            li.appendChild(el('span', { class: 'nl-nome', dir: 'auto', text: comum }));
            if (e[2] && e[2] !== comum) {
                li.appendChild(document.createTextNode(' · '));
                li.appendChild(el('span', { class: 'nl-oficial', dir: 'auto', text: e[2] }));
            }
            if (e[3] && e[3][idx]) { li.appendChild(el('small', { class: 'nl-lingua', text: ' (' + capitalizar(e[3][idx]) + ')' })); }
            ul.appendChild(li);
        });
        if (lista.length > 3) { ul.appendChild(el('li', { class: 'nl-mais', text: t('nome_local_mais', lista.length - 3) })); }
        caixa.appendChild(ul);
        return caixa;
    }

    function cabecalhoCuriosidade(cur, p) {
        cur.appendChild(el('h3', { text: t('sabias') }));
        var nl = blocoNomeLocal(p);
        if (nl) { cur.appendChild(nl); }
    }

    function renderFicha(p) {
        var f = $('ficha');
        limpar(f);
        if (!p) { return; }
        f.appendChild(el('h3', { text: t('ficha_titulo') }));
        var cab = el('div', { class: 'ficha-cab' }, [
            el('img', { class: 'bandeira ficha-bandeira', src: urlBandeira(p.c), alt: '' }),
            el('strong', { text: nome(p) })
        ]);
        f.appendChild(cab);
        var dl = el('dl');
        function linha(rotulo, valor) {
            dl.appendChild(el('dt', { text: rotulo }));
            dl.appendChild(el('dd', { text: valor }));
        }
        linha(t('f_capital'), capital(p));
        linha(t('f_continente'), nomeCont(p.k) + (p.kk ? ' / ' + nomeCont(p.kk[1] === 'Asia' ? 'Asia' : p.kk[1]) : ''));
        linha(t('f_linguas'), p.l.map(function (c, k) { return nomeLingua(c, p.ln[k]); }).join(', '));
        linha(t('f_moeda'), p.m.map(function (c, k) { return nomeMoeda(c, p.mn[k]); }).join(', '));
        if (p.ar) { linha(t('f_area'), tiraEspacosNumero(p.ar) + ' ' + t('km2')); }
        var extras = [];
        if (p.d) { extras.push(p.d); }
        if (p.t) { extras.push(p.t); }
        if (extras.length) { linha(t('f_dominio') + ' / ' + t('f_indicativo'), extras.join('  ·  ')); }
        if (p.b && p.b.length) {
            linha(t('f_fronteiras'), p.b.map(function (cc) { return POR_CC[cc] ? nome(POR_CC[cc]) : cc; }).join(', '));
        } else {
            linha(t('f_fronteiras'), t('f_sem_fronteiras'));
        }
        if (p.i) { linha('', t('f_interior')); }
        f.appendChild(dl);
    }

    // ---------- fim do nível ----------
    function terminar() {
        pararTempo();
        var concluido = estado.vidas > 0 && estado.i >= N_PERGUNTAS;
        var bonus = concluido ? estado.vidas * 50 : 0;
        var total = estado.pontos + bonus;
        var estrelas = concluido ? Math.max(1, estado.vidas) : 0;
        var medio = estado.respondidas ? (estado.tempoMs / estado.respondidas / 1000) : 0;
        var reg = window.Perfil.registar(estado.nivel, {
            pontos: total, estrelas: estrelas, concluido: concluido,
            perguntas: estado.respondidas, certas: estado.certas, tempo: estado.tempoMs
        });
        var nivel = estado.nivel;
        var cx = $('fim-conteudo');
        limpar(cx);
        cx.style.setProperty('--cor-nivel', NIVEIS[nivel - 1].cor);
        cx.appendChild(el('h2', { class: concluido ? 'fim-bom' : 'fim-mau', text: concluido ? t('nivel_completo') : t('fim_jogo') }));
        if (concluido) { cx.appendChild(estrelasEl(estrelas)); }
        else { cx.appendChild(el('p', { text: t('sem_vidas') })); }
        var lista = el('ul', { class: 'fim-lista' });
        lista.appendChild(el('li', { text: t('pontos_nivel') + ': ' + tiraEspacosNumero(total) }));
        lista.appendChild(el('li', { text: t('acertos', estado.certas, estado.respondidas) }));
        if (concluido) { lista.appendChild(el('li', { text: t('bonus_vidas', bonus) })); }
        lista.appendChild(el('li', { text: t('tempo_medio', medio.toFixed(1)) }));
        cx.appendChild(lista);
        if (reg.recorde) { cx.appendChild(el('p', { class: 'recorde' }, [icone('i-trofeu'), el('span', { text: t('novo_recorde') })])); }
        if (concluido && nivel === 3) { cx.appendChild(el('p', { class: 'nota', text: t('todos_completos') })); }

        var botoes = el('div', { class: 'linha-botoes' });
        if (concluido && nivel < 3) {
            var prox = el('button', { type: 'button', class: 'botao' }, [el('span', { text: t('proximo') }), icone('i-seguinte')]);
            prox.addEventListener('click', function () { iniciarNivel(nivel + 1); });
            botoes.appendChild(prox);
        }
        var rep = el('button', { type: 'button', class: 'botao secundario' }, [icone('i-repetir'), el('span', { text: t('repetir') })]);
        rep.addEventListener('click', function () { iniciarNivel(nivel); });
        botoes.appendChild(rep);
        var menu = el('button', { type: 'button', class: 'botao secundario' }, [el('span', { text: t('menu') })]);
        menu.addEventListener('click', function () { estado = null; renderTudo(); mostrarEcra('ecra-inicio'); });
        botoes.appendChild(menu);
        cx.appendChild(botoes);

        estado.fim = true;
        textosEstaticos();
        mostrarEcra('ecra-fim');
        var primeiro = botoes.querySelector('button');
        if (primeiro) { primeiro.focus({ preventScroll: true }); }
    }

    // ---------- eventos ----------
    function ligar() {
        var sel = $('idioma');
        J.SUPORTADOS.forEach(function (c) { sel.appendChild(el('option', { value: c, text: J.NOMES[c] })); });
        sel.value = J.idioma();
        sel.addEventListener('change', function () { J.definirIdioma(sel.value); renderTudo(); if (window.Mapa && mapaIniciado) { window.Mapa.atualizarNomes(); } });

        $('btn-jogador').addEventListener('click', function () {
            if (ecra === 'ecra-jogo') { return; }
            abrirJogadores();
        });
        $('btn-fechar-jogadores').addEventListener('click', function () { renderTudo(); mostrarEcra('ecra-inicio'); });

        $('form-jogador').addEventListener('submit', function (ev) {
            ev.preventDefault();
            var r = window.Perfil.criar($('nome-jogador').value, avatarEscolhido);
            var erro = $('erro-jogador');
            if (r.erro) { erro.textContent = t(r.erro); erro.hidden = false; return; }
            $('nome-jogador').value = '';
            renderTudo();
            mostrarEcra('ecra-inicio');
        });

        $('form-escrita').addEventListener('submit', function (ev) {
            ev.preventDefault();
            if (!estado || !estado.aberta || !testeEscrita) { return; }
            var v = $('resposta-escrita').value;
            if (!v.trim()) { return; }
            resolver({ certa: testeEscrita(v) });
        });

        $('btn-seguinte').addEventListener('click', function () {
            if (!estado || estado.aberta) { return; }
            estado.i += 1;
            proximaPergunta();
        });

        $('btn-sair').addEventListener('click', function () {
            var b = $('btn-sair');
            if (!sairPendente) {
                b.textContent = t('sair_confirmar');
                sairPendente = setTimeout(function () { sairPendente = 0; b.textContent = t('sair'); }, 3000);
                return;
            }
            clearTimeout(sairPendente);
            sairPendente = 0;
            b.textContent = t('sair');
            estado = null;
            pararTempo();
            renderTudo();
            mostrarEcra('ecra-inicio');
        });

        document.addEventListener('keydown', function (ev) {
            if (ecra !== 'ecra-jogo' || !estado || ev.ctrlKey || ev.metaKey || ev.altKey) { return; }
            var alvo = ev.target;
            var aEscrever = alvo && alvo.tagName === 'INPUT';
            if (estado.aberta && !aEscrever && /^[1-4]$/.test(ev.key) && estado.opcoes) {
                var botoes = $('opcoes').children;
                var k = Number(ev.key) - 1;
                if (botoes[k]) { botoes[k].click(); ev.preventDefault(); }
            } else if (!estado.aberta && ev.key === 'Enter' && !$('feedback').hidden && alvo !== $('btn-seguinte') &&
                !(alvo && (alvo.tagName === 'A' || alvo.tagName === 'BUTTON'))) {
                $('btn-seguinte').click();
                ev.preventDefault();
            }
        });

        window.addEventListener('resize', function () { if (mapaIniciado) { window.Mapa.redimensionar(); } });
    }

    function arrancar() {
        if (typeof L === 'undefined' || !window.PAISES || !window.MUNDO || typeof topojson === 'undefined') {
            document.body.appendChild(el('p', { class: 'erro', text: t('carregar_erro') }));
            return;
        }
        ligar();
        renderTudo();
        if (!window.Perfil.atual()) { abrirJogadores(); }
        else { mostrarEcra('ecra-inicio'); }
        if (/[?&]teste=1\b/.test(window.location.search)) {
            // só para os testes automáticos (precisa de ?teste=1 no endereço)
            window.JogoDebug = { estado: function () { return estado; }, paises: PAISES };
        }
    }

    arrancar();
})();
