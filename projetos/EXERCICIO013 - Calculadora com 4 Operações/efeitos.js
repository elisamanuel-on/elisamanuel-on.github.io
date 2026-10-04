// Efeitos da calculadora: poderes (elementos), mistura de cores e reações a certo / errado.
// É só apresentação: quem decide se um cálculo está certo ou errado é o PHP (ver data-estado no <body>).
(function () {
    'use strict';

    var corpo = document.body;
    var cartao = document.querySelector('.card');
    if (!cartao) return;

    var modo = corpo.getAttribute('data-modo') || 'basica';
    var estado = corpo.getAttribute('data-estado') || 'neutro';
    var poderesServidor = (corpo.getAttribute('data-poderes') || '').split(',').filter(Boolean);
    var subiu = corpo.hasAttribute('data-subiu');
    var semMovimento = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    var ELEMENTOS = ['agua', 'fogo', 'terra', 'ar', 'energia'];
    var COR = {
        agua: 'var(--agua)',
        fogo: 'var(--fogo)',
        terra: 'var(--terra)',
        ar: 'var(--ar)',
        energia: 'var(--energia)'
    };

    // Mesmos grupos que extras.php (poderesUsados)
    var PADROES = {
        ar: /(sin|cos|tan)/,
        terra: /(ln|log|exp|sqrt|cbrt|abs|!)/,
        agua: /(pi|\be\b|ans|[()])/,
        fogo: /[+\-*\/^%×÷]/
    };

    var faixa = document.querySelector('.elementos');
    var icones = {};
    document.querySelectorAll('.elemento[data-el]').forEach(function (el, i) {
        icones[el.getAttribute('data-el')] = el;
        el.style.setProperty('--k', i);
    });

    // ---------- poderes acesos ----------
    function gradiente(lista, direcao) {
        var cores = lista.length ? lista : ['energia'];
        if (cores.length === 1) cores = [cores[0], cores[0]];
        return 'linear-gradient(' + direcao + ', ' + cores.map(function (p) { return COR[p]; }).join(', ') + ')';
    }

    function acender(lista) {
        var completa = lista.filter(function (p) { return p !== 'energia'; });
        // misturar três ou mais poderes liberta a energia
        if (completa.length >= 3 && lista.indexOf('energia') === -1) lista = lista.concat('energia');
        ELEMENTOS.forEach(function (p) {
            if (!icones[p]) return;
            var ligado = lista.indexOf(p) !== -1;
            icones[p].classList.toggle('acesa', ligado);
        });
        if (faixa) faixa.classList.toggle('tem-poderes', lista.length > 0);
        return lista;
    }

    function pulsar(poder) {
        var el = icones[poder];
        if (!el || semMovimento) return;
        el.classList.remove('pulso');
        void el.offsetWidth; // reinicia a animação
        el.classList.add('pulso');
    }

    // ---------- faíscas em forma de estrela ----------
    function faiscas(origem, cores, quantidade) {
        if (semMovimento) return;
        var caixaCartao = cartao.getBoundingClientRect();
        var caixa = origem.getBoundingClientRect();
        var x0 = caixa.left - caixaCartao.left + caixa.width / 2;
        var y0 = caixa.top - caixaCartao.top + caixa.height / 2;

        for (var i = 0; i < quantidade; i++) {
            var f = document.createElement('span');
            f.className = 'faisca';
            var angulo = (Math.PI * 2 * i) / quantidade + Math.random() * 0.6;
            var distancia = 50 + Math.random() * 90;
            f.style.left = x0 + 'px';
            f.style.top = y0 + 'px';
            f.style.setProperty('--x', Math.cos(angulo) * distancia + 'px');
            f.style.setProperty('--y', Math.sin(angulo) * distancia - 20 + 'px');
            f.style.setProperty('--rot', (Math.random() * 360 | 0) + 'deg');
            f.style.setProperty('--tam', (8 + Math.random() * 10 | 0) + 'px');
            f.style.background = cores[i % cores.length];
            f.addEventListener('animationend', function (ev) { ev.target.remove(); });
            cartao.appendChild(f);
        }
    }

    function coresDaReacao() {
        var base = poderesServidor.length ? poderesServidor : ['terra', 'agua', 'fogo', 'ar', 'energia'];
        return base.map(function (p) { return COR[p] || COR.energia; });
    }

    // ---------- reação certo / errado ao carregar a página ----------
    function reagir() {
        var itens = Array.prototype.slice.call(document.querySelectorAll('.resultado-item'));
        itens.forEach(function (el, i) { el.style.setProperty('--i', i); });

        var temCertos = document.querySelector('.resultado-item:not(.deu-erro) .valor') !== null;
        var final = estado;
        // nas 6 operações, uma conta impossível (ex.: ÷ 0) não estraga as que correram bem
        if (modo === 'basica' && estado === 'erro' && temCertos) final = 'ok';

        if (final === 'ok') {
            cartao.classList.add('reacao-ok');
            if (modo === 'desafio' && poderesServidor[0]) {
                cartao.style.setProperty('--reacao', COR[poderesServidor[0]]);
            }
            var alvo = document.querySelector('.resultados-area, .feedback, .pergunta') || cartao;
            faiscas(alvo, coresDaReacao(), subiu ? 26 : 14);
            if (subiu) {
                cartao.classList.add('subiu');
                ELEMENTOS.forEach(function (p, i) { setTimeout(function () { pulsar(p); }, i * 110); });
            }
        } else if (final === 'erro') {
            cartao.classList.add('reacao-erro');
        } else if (document.querySelector('.feedback.aviso')) {
            cartao.classList.add('reacao-aviso');
        }
    }

    // ---------- modo científico: mistura em direto ----------
    var campo = document.getElementById('expressao');
    var barra = document.getElementById('mistura');

    function atualizarMistura() {
        var texto = campo.value.toLowerCase();
        var usados = ['ar', 'terra', 'agua', 'fogo'].filter(function (p) { return PADROES[p].test(texto); });
        var acesos = acender(usados);
        if (barra) {
            barra.style.background = acesos.length ? gradiente(acesos, '90deg') : 'transparent';
            barra.style.opacity = acesos.length ? '1' : '0';
        }
        return acesos;
    }

    // colore a faixa lateral do resultado com a mistura dos poderes usados
    var resultadoMistura = document.querySelector('.mistura-borda');
    if (resultadoMistura) {
        var lista = (resultadoMistura.getAttribute('data-poderes') || '').split(',').filter(Boolean);
        resultadoMistura.style.setProperty('--faixa', gradiente(lista, 'to bottom'));
    }

    if (campo && modo === 'cientifica') {
        // o gradiente do campo e dos ícones começa com o que já lá está
        atualizarMistura();
        campo.addEventListener('input', atualizarMistura);

        document.querySelectorAll('.teclado .tecla').forEach(function (tecla) {
            tecla.addEventListener('click', function () {
                tecla.classList.remove('pressionada');
                void tecla.offsetWidth;
                tecla.classList.add('pressionada');

                var poder = 'energia';
                ELEMENTOS.forEach(function (p) { if (tecla.classList.contains('el-' + p)) poder = p; });
                if (tecla.classList.contains('op')) poder = 'fogo';
                if (tecla.classList.contains('limpar')) poder = 'energia';
                pulsar(poder);
                setTimeout(atualizarMistura, 0); // depois de teclado.js inserir o texto
            });
        });
    } else {
        acender(poderesServidor);
    }

    reagir();
})();
