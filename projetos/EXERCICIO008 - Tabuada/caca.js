// Caça aos múltiplos: tocar só nos números que pertencem à tabuada pedida. Três vidas, cinco rondas.
(function () {
    'use strict';

    var T = window.Tabuada;
    var RONDAS = 5;
    var GRELHA = {
        facil:   { celulas: 12, colunas: 4, multiplos: 4 },
        medio:   { celulas: 16, colunas: 4, multiplos: 5 },
        dificil: { celulas: 20, colunas: 5, multiplos: 6 }
    };

    var corpoAtual = null;
    var s = null;
    var relogio = null;

    function parar() {
        if (relogio) { window.clearInterval(relogio); relogio = null; }
    }

    function tabuadasValidas() {
        var lista = T.estado.tabuadas.filter(function (n) { return n >= 2; });
        return lista.length ? lista : [2];
    }

    function comecar() {
        parar();
        s = { ronda: 0, vidas: 3, feitas: 0, inicio: Date.now(), ativo: true, anterior: null };
        corpoAtual.innerHTML =
            '<div class="lab-status"><span class="chip-info" id="ccRonda"></span><span class="chip-info" id="ccVidas"></span><span class="chip-info" id="ccTempo"></span></div>' +
            '<p class="caca-pedido" id="ccPedido"></p>' +
            '<div class="caca-faltam" id="ccFaltam"></div>' +
            '<div class="caca-grelha" id="ccGrelha"></div>';
        corpoAtual.querySelector('#ccTempo').textContent = T.t('cc_tempo') + ': 0 s';
        relogio = window.setInterval(function () {
            var el = corpoAtual && corpoAtual.querySelector('#ccTempo');
            if (el) el.textContent = T.t('cc_tempo') + ': ' + Math.floor((Date.now() - s.inicio) / 1000) + ' s';
        }, 500);
        novaRonda();
    }

    function vidasHTML() {
        var h = '';
        for (var i = 0; i < 3; i++) h += '<span class="vida' + (i < s.vidas ? '' : ' perdida') + '">' + T.icones.coracao + '</span>';
        return T.t('cc_vidas') + ' ' + h;
    }

    function novaRonda() {
        if (!s || !s.ativo) return;
        s.ronda++;
        var cfg = GRELHA[T.estado.nivel];
        var opcoes = tabuadasValidas();
        var a = T.escolher(opcoes.length > 1 ? opcoes.filter(function (n) { return n !== s.anterior; }) : opcoes);
        s.anterior = a;

        var ks = T.baralhar([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]).slice(0, cfg.multiplos);
        var multiplos = ks.map(function (k) { return a * k; });
        var maximo = Math.max(a * 10, 32);
        var usados = {};
        multiplos.forEach(function (m) { usados[m] = true; });

        var distratores = [];
        function tentar(n) {
            if (n >= 1 && n <= maximo && n % a !== 0 && !usados[n]) { usados[n] = true; distratores.push(n); }
        }
        if (T.estado.nivel !== 'facil') {
            // números parecidos com os certos (±1, ±2) para tornar a caça mais atenta
            T.baralhar(multiplos).forEach(function (m) {
                if (distratores.length < cfg.celulas - cfg.multiplos) tentar(m + T.escolher([-2, -1, 1, 2]));
            });
        }
        var tentativas = 0;
        while (distratores.length < cfg.celulas - cfg.multiplos && tentativas++ < 500) tentar(T.aleatorio(1, maximo));

        var numeros = T.baralhar(multiplos.concat(distratores));
        s.alvo = a;
        s.faltam = cfg.multiplos;
        s.travado = false;

        corpoAtual.querySelector('#ccRonda').textContent = T.t('cc_ronda', s.ronda, RONDAS);
        corpoAtual.querySelector('#ccVidas').innerHTML = vidasHTML();
        corpoAtual.querySelector('#ccPedido').innerHTML = T.t('cc_instr', '<strong class="caca-numero">' + a + '</strong>');
        corpoAtual.querySelector('#ccFaltam').textContent = T.t('cc_faltam', s.faltam);

        var grelha = corpoAtual.querySelector('#ccGrelha');
        grelha.style.setProperty('--cols', cfg.colunas);
        grelha.innerHTML = numeros.map(function (n) {
            return '<button type="button" class="caca-num" data-n="' + n + '" data-m="' + (n % a === 0 ? 1 : 0) + '">' + n + '</button>';
        }).join('');
        grelha.querySelectorAll('.caca-num').forEach(function (b) {
            b.addEventListener('click', function () { tocar(b); });
        });
    }

    function tocar(botao) {
        if (!s || !s.ativo || s.travado || botao.disabled) return;
        botao.disabled = true;
        if (botao.getAttribute('data-m') === '1') {
            botao.classList.add('certa');
            T.som('certo');
            s.faltam--;
            corpoAtual.querySelector('#ccFaltam').textContent = s.faltam > 0 ? T.t('cc_faltam', s.faltam) : '';
            if (s.faltam === 0) fimDeRonda();
        } else {
            botao.classList.add('errada');
            T.som('errado');
            s.vidas--;
            corpoAtual.querySelector('#ccVidas').innerHTML = vidasHTML();
            if (s.vidas === 0) perder();
        }
    }

    function fimDeRonda() {
        s.travado = true;
        s.feitas++;
        T.ganharEstrelas(1);
        T.aviso(T.tr('certo'), 'bom');
        var rect = corpoAtual.querySelector('#ccGrelha').getBoundingClientRect();
        T.confetes(30, { x: rect.left + rect.width / 2, y: rect.top + rect.height / 2 });
        if (s.ronda < RONDAS) T.esperar(900, novaRonda);
        else T.esperar(700, ganhar);
    }

    function guardarRecorde() {
        return s.feitas > 0 && T.recorde('caca_' + T.estado.nivel, s.feitas, true);
    }

    function ganhar() {
        if (!s || !s.ativo) return;
        s.ativo = false;
        parar();
        T.ganharEstrelas(2);
        var bateu = guardarRecorde();
        var linhas = [T.t('cc_rondas_feitas', s.feitas, RONDAS), T.t('me_tempo') + ': ' + Math.floor((Date.now() - s.inicio) / 1000) + ' s'];
        if (bateu) linhas.push('<strong>' + T.t('qz_recorde_novo') + '</strong>');
        T.fim({ titulo: T.t('cc_ganhou'), linhas: linhas, estrelas: s.feitas + 2, aoRepetir: comecar });
    }

    function perder() {
        s.travado = true;
        s.ativo = false;
        parar();
        // mostra os que faltavam, para a criança aprender
        corpoAtual.querySelectorAll('.caca-num[data-m="1"]:not(.certa)').forEach(function (b) { b.classList.add('revelada'); });
        var bateu = guardarRecorde();
        var linhas = [T.t('cc_rondas_feitas', s.feitas, RONDAS)];
        if (bateu) linhas.push('<strong>' + T.t('qz_recorde_novo') + '</strong>');
        T.esperar(1300, function () {
            if (!corpoAtual) return;
            T.fim({ titulo: T.t('cc_perdeu'), linhas: linhas, estrelas: 0, humor: 'triste', aoRepetir: comecar });
        });
    }

    function abrir(corpo) {
        corpoAtual = corpo;
        corpo.innerHTML = '';
        comecar();
    }

    function fechar() {
        if (s) s.ativo = false;
        parar();
        s = null;
        corpoAtual = null;
    }

    T.registar('caca', { abrir: abrir, fechar: fechar });
})();
