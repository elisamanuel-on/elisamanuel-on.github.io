// Memória de contas: virar duas cartas e juntar cada conta ao seu resultado.
(function () {
    'use strict';

    var T = window.Tabuada;
    var CONFIG = {
        facil:   { pares: 6,  colunas: 4 },
        medio:   { pares: 8,  colunas: 4 },
        dificil: { pares: 10, colunas: 5 }
    };

    var corpoAtual = null;
    var s = null;
    var relogio = null;

    function parar() {
        if (relogio) { window.clearInterval(relogio); relogio = null; }
    }

    // pares com resultados todos diferentes, para não haver duas cartas "12" ao mesmo tempo
    function gerarPares(quantos) {
        var porProduto = {};
        function juntar(tabuadas, de, ate) {
            tabuadas.forEach(function (a) {
                for (var b = de; b <= ate; b++) {
                    var p = a * b;
                    if (!porProduto[p]) porProduto[p] = [a, b];
                }
            });
        }
        juntar(T.estado.tabuadas, 2, 10);
        if (Object.keys(porProduto).length < quantos) juntar(T.estado.tabuadas, 1, 10);
        if (Object.keys(porProduto).length < quantos) juntar([2, 3, 4, 5, 6, 7, 8, 9], 2, 9);
        var todos = T.baralhar(Object.keys(porProduto));
        return todos.slice(0, quantos).map(function (p) { return { a: porProduto[p][0], b: porProduto[p][1], p: Number(p) }; });
    }

    function comecar() {
        parar();
        var cfg = CONFIG[T.estado.nivel];
        var pares = gerarPares(cfg.pares);
        var cartas = [];
        pares.forEach(function (par, i) {
            cartas.push({ id: i, texto: par.a + ' × ' + par.b, tipo: 'q' });
            cartas.push({ id: i, texto: String(par.p), tipo: 'r' });
        });
        cartas = T.baralhar(cartas);

        s = { pares: pares.length, achados: 0, jogadas: 0, primeira: null, travado: false, inicio: null, ativo: true };

        corpoAtual.innerHTML =
            '<p class="instrucao">' + T.t('me_instr') + '</p>' +
            '<div class="lab-status"><span class="chip-info" id="meJogadas"></span><span class="chip-info" id="meTempo"></span><span class="chip-info" id="mePares"></span></div>' +
            '<div class="mem-grelha" id="memGrelha" style="--cols:' + cfg.colunas + '">' + cartas.map(function (c, i) {
                return '<button type="button" class="carta tipo-' + c.tipo + '" data-i="' + i + '" aria-label="' + T.t('me_carta') + '">' +
                    '<span class="carta-miolo"><span class="carta-frente">' + T.icones.estrela + '</span><span class="carta-verso">' + c.texto + '</span></span></button>';
            }).join('') + '</div>';

        atualizar();
        corpoAtual.querySelectorAll('.carta').forEach(function (el) {
            el.addEventListener('click', function () { virar(el, cartas[Number(el.getAttribute('data-i'))]); });
        });
    }

    function segundos() {
        return s.inicio ? Math.floor((Date.now() - s.inicio) / 1000) : 0;
    }

    function atualizar() {
        corpoAtual.querySelector('#meJogadas').textContent = T.t('me_jogadas') + ': ' + s.jogadas;
        corpoAtual.querySelector('#meTempo').textContent = T.t('me_tempo') + ': ' + segundos() + ' s';
        corpoAtual.querySelector('#mePares').textContent = T.t('me_pares', s.achados, s.pares);
    }

    function virar(el, carta) {
        if (!s || !s.ativo || s.travado || el.classList.contains('virada') || el.classList.contains('achada')) return;
        if (!s.inicio) {
            s.inicio = Date.now();
            relogio = window.setInterval(function () { if (corpoAtual && s) atualizar(); }, 500);
        }
        el.classList.add('virada');
        T.som('virar');

        if (!s.primeira) {
            s.primeira = { el: el, carta: carta };
            return;
        }

        var primeira = s.primeira;
        s.primeira = null;
        s.jogadas++;
        if (primeira.carta.id === carta.id && primeira.carta.tipo !== carta.tipo) {
            primeira.el.classList.add('achada');
            el.classList.add('achada');
            s.achados++;
            T.som('certo');
            atualizar();
            if (s.achados === s.pares) T.esperar(600, vitoria);
        } else {
            s.travado = true;
            atualizar();
            T.esperar(950, function () {
                if (!s) return;
                primeira.el.classList.remove('virada');
                el.classList.remove('virada');
                s.travado = false;
            });
        }
    }

    function vitoria() {
        if (!s || !s.ativo) return;
        s.ativo = false;
        parar();
        var segs = segundos();
        var estrelas = s.jogadas <= s.pares * 1.5 ? 3 : (s.jogadas <= s.pares * 2.2 ? 2 : 1);
        var bateu = T.recorde('memoria_' + T.estado.nivel, s.jogadas, false);
        T.ganharEstrelas(estrelas);
        var linhas = [T.t('me_resultado', s.jogadas, segs)];
        if (bateu) linhas.push('<strong>' + T.t('qz_recorde_novo') + '</strong>');
        T.fim({ titulo: T.t('me_ganhou'), linhas: linhas, estrelas: estrelas, aoRepetir: comecar });
    }

    function abrir(corpo) {
        corpoAtual = corpo;
        comecar();
    }

    function fechar() {
        if (s) s.ativo = false;
        parar();
        s = null;
        corpoAtual = null;
    }

    T.registar('memoria', { abrir: abrir, fechar: fechar });
})();
