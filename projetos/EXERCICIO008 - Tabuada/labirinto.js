// Labirinto da tabuada: o Tabi percorre um labirinto novo de cada vez.
// Cada porta fechada pede uma conta; acertar abre-a. Há estrelas escondidas nos becos e um tesouro no fim.
(function () {
    'use strict';

    var T = window.Tabuada;

    var N = 1, E = 2, S = 4, W = 8;
    var DIRS = {
        cima:     { bit: N, oposto: S, dx: 0,  dy: -1 },
        direita:  { bit: E, oposto: W, dx: 1,  dy: 0 },
        baixo:    { bit: S, oposto: N, dx: 0,  dy: 1 },
        esquerda: { bit: W, oposto: E, dx: -1, dy: 0 }
    };
    var CONFIG = {
        facil:   { n: 7,  portas: 2, estrelas: 3 },
        medio:   { n: 9,  portas: 3, estrelas: 4 },
        dificil: { n: 11, portas: 4, estrelas: 5 }
    };

    var corpoAtual = null;
    var jogo = null;
    var teclado = null;

    // ---------- geração do labirinto (caminhos aleatórios, sempre com solução) ----------
    function gerar(n) {
        var m = [], visto = [], y, x;
        for (y = 0; y < n; y++) {
            m.push(new Array(n).fill(0));
            visto.push(new Array(n).fill(false));
        }
        var pilha = [[0, 0]];
        visto[0][0] = true;
        while (pilha.length) {
            var atual = pilha[pilha.length - 1];
            var livres = T.baralhar(Object.keys(DIRS)).filter(function (nome) {
                var d = DIRS[nome];
                var nx = atual[0] + d.dx, ny = atual[1] + d.dy;
                return nx >= 0 && ny >= 0 && nx < n && ny < n && !visto[ny][nx];
            });
            if (!livres.length) { pilha.pop(); continue; }
            var d = DIRS[livres[0]];
            var px = atual[0] + d.dx, py = atual[1] + d.dy;
            m[atual[1]][atual[0]] |= d.bit;
            m[py][px] |= d.oposto;
            visto[py][px] = true;
            pilha.push([px, py]);
        }
        return m;
    }

    function caminho(m, n) {
        var anterior = {};
        var fila = [[0, 0]];
        anterior['0,0'] = null;
        while (fila.length) {
            var c = fila.shift();
            if (c[0] === n - 1 && c[1] === n - 1) break;
            Object.keys(DIRS).forEach(function (nome) {
                var d = DIRS[nome];
                if (!(m[c[1]][c[0]] & d.bit)) return;
                var nx = c[0] + d.dx, ny = c[1] + d.dy, k = nx + ',' + ny;
                if (!(k in anterior)) { anterior[k] = c; fila.push([nx, ny]); }
            });
        }
        var rota = [];
        var passo = [n - 1, n - 1];
        while (passo) {
            rota.unshift(passo);
            passo = anterior[passo[0] + ',' + passo[1]];
        }
        return rota;
    }

    function chave(x, y) { return x + ',' + y; }

    function novo() {
        var cfg = CONFIG[T.estado.nivel];
        var n = cfg.n;
        var m = gerar(n);
        var rota = caminho(m, n);
        var noCaminho = {};
        rota.forEach(function (c) { noCaminho[chave(c[0], c[1])] = true; });

        // portas espaçadas pelo caminho certo (nunca no início nem no fim)
        var portas = {};
        for (var k = 1; k <= cfg.portas; k++) {
            var i = Math.round(k * (rota.length - 1) / (cfg.portas + 1));
            var c = rota[Math.min(Math.max(i, 1), rota.length - 2)];
            portas[chave(c[0], c[1])] = false;
        }

        // estrelas: de preferência nos becos sem saída fora do caminho (para compensar explorar)
        var becos = [], outras = [];
        for (var y = 0; y < n; y++) {
            for (var x = 0; x < n; x++) {
                var kk = chave(x, y);
                if (kk === '0,0' || kk === chave(n - 1, n - 1) || kk in portas) continue;
                var aberturas = [N, E, S, W].filter(function (b) { return m[y][x] & b; }).length;
                if (!noCaminho[kk] && aberturas === 1) becos.push(kk);
                else if (!noCaminho[kk]) outras.push(kk);
            }
        }
        var candidatas = T.baralhar(becos).concat(T.baralhar(outras)).slice(0, cfg.estrelas);
        var estrelas = {};
        candidatas.forEach(function (kk) { estrelas[kk] = true; });

        jogo = {
            n: n, m: m, x: 0, y: 0,
            portas: portas, totalPortas: cfg.portas, abertas: 0,
            estrelas: estrelas, apanhadas: 0, pergunta: null, parado: false
        };
    }

    // ---------- desenho ----------
    function celula(x, y) {
        var k = chave(x, y), n = jogo.n, v = jogo.m[y][x];
        var cls = 'c';
        // cada parede interior é desenhada uma só vez (lado de cima e lado esquerdo); a moldura trata do contorno
        if (!(v & N) && y > 0) cls += ' pn';
        if (!(v & W) && x > 0) cls += ' pw';
        var conteudo = '';
        if (k in jogo.portas && !jogo.portas[k]) { cls += ' porta'; conteudo = T.icones.cadeado; }
        else if (jogo.estrelas[k]) { conteudo = '<span class="c-estrela">' + T.icones.estrela + '</span>'; }
        if (x === n - 1 && y === n - 1) { cls += ' fim'; conteudo = T.icones.tesouro; }
        return '<div class="' + cls + '" data-k="' + k + '">' + conteudo + '</div>';
    }

    function desenhar() {
        var n = jogo.n, celulas = '';
        for (var y = 0; y < n; y++) for (var x = 0; x < n; x++) celulas += celula(x, y);

        corpoAtual.innerHTML =
            '<p class="instrucao">' + T.t('lab_instr') + '</p>' +
            '<div class="lab-status"><span class="chip-info" id="labPortas"></span><span class="chip-info" id="labEstrelas"></span></div>' +
            '<div class="lab-area">' +
            '<div class="lab" id="lab" style="--n:' + n + '">' + celulas +
            '<div class="lab-jogador" id="labJogador">' + T.mascote('feliz') + '</div></div>' +
            '<div class="lab-pergunta" id="labPergunta" hidden></div></div>' +
            '<div class="dpad" role="group">' +
            '<button type="button" class="dpad-b dpad-cima" data-dir="cima" aria-label="' + T.t('lab_cima') + '">' + arrow(0) + '</button>' +
            '<button type="button" class="dpad-b dpad-esq" data-dir="esquerda" aria-label="' + T.t('lab_esquerda') + '">' + arrow(270) + '</button>' +
            '<button type="button" class="dpad-b dpad-dir" data-dir="direita" aria-label="' + T.t('lab_direita') + '">' + arrow(90) + '</button>' +
            '<button type="button" class="dpad-b dpad-baixo" data-dir="baixo" aria-label="' + T.t('lab_baixo') + '">' + arrow(180) + '</button></div>';

        corpoAtual.querySelectorAll('.dpad-b').forEach(function (b) {
            b.addEventListener('click', function () { mover(b.getAttribute('data-dir')); });
        });
        ligarDeslizar(corpoAtual.querySelector('#lab'));
        posicionar(false);
        estado();
    }

    function arrow(graus) {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="transform:rotate(' + graus + 'deg)"><path d="M12 19V6M6 11l6-6 6 6"/></svg>';
    }

    function estado() {
        corpoAtual.querySelector('#labPortas').innerHTML = T.t('lab_portas', jogo.abertas, jogo.totalPortas);
        corpoAtual.querySelector('#labEstrelas').innerHTML = T.icones.estrela + ' ' + T.t('lab_estrelinhas', jogo.apanhadas);
    }

    function posicionar(animar) {
        var j = corpoAtual.querySelector('#labJogador');
        j.style.transition = animar && !T.semMovimento ? '' : 'none';
        j.style.transform = 'translate(' + jogo.x * 100 + '%, ' + jogo.y * 100 + '%)';
        if (!animar) { void j.offsetWidth; j.style.transition = ''; }
    }

    // ---------- movimento ----------
    function mover(nome) {
        if (!jogo || jogo.parado || jogo.pergunta) return;
        var d = DIRS[nome];
        var j = corpoAtual.querySelector('#labJogador');
        if (!(jogo.m[jogo.y][jogo.x] & d.bit)) {
            j.classList.remove('bate');
            void j.offsetWidth;
            j.classList.add('bate');
            return;
        }
        var nx = jogo.x + d.dx, ny = jogo.y + d.dy, k = chave(nx, ny);
        if (k in jogo.portas && !jogo.portas[k]) {
            perguntar(k, nx, ny);
            return;
        }
        entrar(nx, ny);
    }

    function entrar(nx, ny) {
        jogo.x = nx;
        jogo.y = ny;
        posicionar(true);
        T.som('passo');
        var k = chave(nx, ny);
        if (jogo.estrelas[k]) {
            delete jogo.estrelas[k];
            jogo.apanhadas++;
            var el = corpoAtual.querySelector('[data-k="' + k + '"] .c-estrela');
            if (el) el.remove();
            T.ganharEstrelas(1);
            T.som('estrela');
            estado();
        }
        if (nx === jogo.n - 1 && ny === jogo.n - 1) vitoria();
    }

    function vitoria() {
        jogo.parado = true;
        T.ganharEstrelas(3);
        var total = jogo.abertas + jogo.apanhadas + 3;
        T.estado.recordes.labirinto = (T.estado.recordes.labirinto || 0) + 1;
        T.guardar();
        var linhas = [T.t('lab_portas', jogo.abertas, jogo.totalPortas), T.t('lab_estrelinhas', jogo.apanhadas), T.t('lab_total', T.estado.recordes.labirinto)];
        T.esperar(350, function () {
            if (!corpoAtual) return;
            T.fim({ titulo: T.t('lab_ganhou'), linhas: linhas, estrelas: total, rotuloRepetir: T.t('lab_novo'), aoRepetir: function () { novo(); desenhar(); } });
        });
    }

    // ---------- pergunta numa porta ----------
    function perguntar(k, nx, ny) {
        var caixa = corpoAtual.querySelector('#labPergunta');
        var p = T.gerarPergunta();
        jogo.pergunta = k;
        caixa.hidden = false;
        caixa.innerHTML =
            '<div class="lab-pergunta-card"><button type="button" class="fechar" aria-label="' + T.t('fechar') + '">×</button>' +
            '<div class="lab-pergunta-topo"><span class="lab-cadeado">' + T.icones.cadeado + '</span><p>' + T.t('lab_porta') + '</p></div>' +
            '<div id="labQuestao"></div><div class="dica-tabela" id="labDica" hidden></div></div>';

        function fechar() {
            jogo.pergunta = null;
            caixa.hidden = true;
            caixa.innerHTML = '';
            var el = corpoAtual.querySelector('.lab');
            if (el) el.focus();
        }

        caixa.querySelector('.fechar').addEventListener('click', fechar);

        T.perguntaUI(caixa.querySelector('#labQuestao'), p, {
            repetir: true,
            aoResponder: function (certo, dada, erros) {
                if (certo) {
                    T.esperar(650, function () {
                        if (!jogo || jogo.pergunta !== k) return;
                        jogo.portas[k] = true;
                        jogo.abertas++;
                        var c = corpoAtual.querySelector('[data-k="' + k + '"]');
                        if (c) { c.classList.remove('porta'); c.classList.add('porta-aberta'); c.innerHTML = ''; }
                        T.ganharEstrelas(1);
                        T.aviso(T.t('lab_aberta'), 'bom');
                        fechar();
                        estado();
                        entrar(nx, ny);
                    });
                } else if (erros >= 2 && p.a) {
                    var dica = caixa.querySelector('#labDica');
                    var linhas = [];
                    for (var i = 1; i <= 10; i++) linhas.push('<span>' + p.a + ' × ' + i + ' = ' + (p.a * i) + '</span>');
                    dica.innerHTML = '<strong>' + T.t('lab_dica', p.a) + '</strong><div class="dica-grelha">' + linhas.join('') + '</div>';
                    dica.hidden = false;
                }
            }
        });
    }

    // ---------- comandos ----------
    function ligarDeslizar(el) {
        var inicio = null;
        el.addEventListener('touchstart', function (ev) {
            var t = ev.changedTouches[0];
            inicio = { x: t.clientX, y: t.clientY };
        }, { passive: true });
        el.addEventListener('touchend', function (ev) {
            if (!inicio) return;
            var t = ev.changedTouches[0];
            var dx = t.clientX - inicio.x, dy = t.clientY - inicio.y;
            inicio = null;
            if (Math.max(Math.abs(dx), Math.abs(dy)) < 24) return;
            if (Math.abs(dx) > Math.abs(dy)) mover(dx > 0 ? 'direita' : 'esquerda');
            else mover(dy > 0 ? 'baixo' : 'cima');
        }, { passive: true });
    }

    function abrir(corpo) {
        corpoAtual = corpo;
        novo();
        desenhar();
        teclado = function (ev) {
            if (ev.ctrlKey || ev.metaKey || ev.altKey) return;
            if (/^(INPUT|SELECT|TEXTAREA)$/.test((ev.target || {}).tagName || '')) return;
            var mapa = { ArrowUp: 'cima', ArrowDown: 'baixo', ArrowLeft: 'esquerda', ArrowRight: 'direita', w: 'cima', s: 'baixo', a: 'esquerda', d: 'direita', W: 'cima', S: 'baixo', A: 'esquerda', D: 'direita' };
            var nome = mapa[ev.key];
            if (!nome) return;
            ev.preventDefault();
            mover(nome);
        };
        document.addEventListener('keydown', teclado);
    }

    function fechar() {
        if (teclado) document.removeEventListener('keydown', teclado);
        teclado = null;
        jogo = null;
        corpoAtual = null;
    }

    T.registar('labirinto', { abrir: abrir, fechar: fechar });
})();
