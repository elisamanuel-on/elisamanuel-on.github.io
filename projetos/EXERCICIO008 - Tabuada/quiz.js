// Quiz com tempo: quantas contas dá para acertar antes de o relógio chegar a zero.
(function () {
    'use strict';

    var T = window.Tabuada;
    var DURACAO = { facil: 75, medio: 60, dificil: 45 };

    var corpoAtual = null;
    var s = null;
    var relogio = null;
    var interface_ = null;

    function parar() {
        if (relogio) { window.clearInterval(relogio); relogio = null; }
        if (interface_) { interface_.destruir(); interface_ = null; }
    }

    function intro() {
        parar();
        s = null;
        var rec = T.estado.recordes['quiz_' + T.estado.nivel];
        corpoAtual.innerHTML =
            '<div class="painel centro"><div class="intro-mascote">' + T.mascote('feliz') + '</div>' +
            '<p class="instrucao">' + T.t('qz_instr') + '</p>' +
            '<p class="chip-info">' + T.t('qz_tempo') + ': ' + DURACAO[T.estado.nivel] + ' s</p>' +
            (rec !== undefined ? '<p class="recorde-linha">' + T.t('recorde', rec + ' ' + T.t('qz_pontos').toLowerCase()) + '</p>' : '') +
            '<button type="button" class="btn btn-verde btn-grande" id="qzComecar">' + T.t('qz_comecar') + '</button></div>';
        corpoAtual.querySelector('#qzComecar').addEventListener('click', comecar);
    }

    function comecar() {
        var duracao = DURACAO[T.estado.nivel] * 1000;
        s = { fim: Date.now() + duracao, duracao: duracao, acertos: 0, total: 0, pontos: 0, seq: 0, ativo: true };
        corpoAtual.innerHTML =
            '<div class="qz-barra"><span id="qzBarra"></span></div>' +
            '<div class="lab-status"><span class="chip-info" id="qzTempo"></span><span class="chip-info" id="qzPontos"></span><span class="chip-info" id="qzSeq"></span></div>' +
            '<div id="qzPergunta"></div>';
        atualizar();
        proxima();
        relogio = window.setInterval(function () {
            var resta = s.fim - Date.now();
            if (resta <= 0) { terminar(); return; }
            atualizar();
        }, 100);
    }

    function atualizar() {
        var resta = Math.max(0, s.fim - Date.now());
        var barra = corpoAtual.querySelector('#qzBarra');
        var frac = resta / s.duracao;
        barra.style.width = (frac * 100) + '%';
        barra.classList.toggle('pouco', frac < 0.25);
        corpoAtual.querySelector('#qzTempo').textContent = T.t('qz_tempo') + ': ' + Math.ceil(resta / 1000) + ' s';
        corpoAtual.querySelector('#qzPontos').textContent = T.t('qz_pontos') + ': ' + s.pontos;
        corpoAtual.querySelector('#qzSeq').textContent = T.t('qz_sequencia') + ': ' + s.seq;
    }

    function proxima() {
        if (!s || !s.ativo) return;
        var p = T.gerarPergunta();
        interface_ = T.perguntaUI(corpoAtual.querySelector('#qzPergunta'), p, {
            aoResponder: function (certo) {
                if (!s || !s.ativo) return;
                s.total++;
                if (certo) {
                    s.acertos++;
                    s.seq++;
                    s.pontos += 10 + Math.min(s.seq - 1, 5) * 2;
                } else {
                    s.seq = 0;
                }
                atualizar();
                T.esperar(certo ? 450 : 1100, proxima);
            }
        });
    }

    function terminar() {
        if (!s || !s.ativo) return;
        s.ativo = false;
        parar();
        var estrelas = Math.min(10, Math.floor(s.acertos / 3));
        var bateu = s.pontos > 0 && T.recorde('quiz_' + T.estado.nivel, s.pontos, true);
        T.ganharEstrelas(estrelas);
        var linhas = [T.t('qz_resultado', s.acertos, s.total), T.t('qz_pontuacao', s.pontos)];
        if (bateu) linhas.push('<strong>' + T.t('qz_recorde_novo') + '</strong>');
        T.fim({ titulo: T.t('qz_fim'), linhas: linhas, estrelas: estrelas, humor: s.acertos === 0 ? 'triste' : 'feliz', aoRepetir: intro });
    }

    function abrir(corpo) {
        corpoAtual = corpo;
        intro();
    }

    function fechar() {
        if (s) s.ativo = false;
        parar();
        s = null;
        corpoAtual = null;
    }

    T.registar('quiz', { abrir: abrir, fechar: fechar });
})();
