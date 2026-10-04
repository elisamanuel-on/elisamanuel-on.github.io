// Modo Aprender: a tabuada original, agora com respostas que se podem tapar para treinar.
(function () {
    'use strict';

    var T = window.Tabuada;
    var numeroAtual = null;
    var tapadas = false;

    function abrir(corpo) {
        corpo.innerHTML =
            '<p class="instrucao">' + T.t('ap_instr') + '</p>' +
            '<div class="painel">' +
            '<div class="chips" id="apChips">' + [1, 2, 3, 4, 5, 6, 7, 8, 9, 10].map(function (n) {
                return '<button type="button" class="chip" data-n="' + n + '">' + n + '</button>';
            }).join('') + '</div>' +
            '<form class="ap-form" id="apForm" autocomplete="off" novalidate>' +
            '<label for="apNumero" class="so-leitor">' + T.t('ap_numero') + '</label>' +
            '<input type="text" inputmode="numeric" id="apNumero" placeholder="' + T.t('ap_numero') + '" maxlength="3">' +
            '<button type="submit" class="btn btn-azul">' + T.t('ap_ver') + '</button></form>' +
            '<p class="ap-erro" id="apErro" role="alert"></p></div>' +
            '<div class="painel ap-resultado" id="apResultado"><p class="dica-vazia">' + T.t('ap_aguardando') + '</p></div>';

        var campo = corpo.querySelector('#apNumero');
        var erro = corpo.querySelector('#apErro');

        function mostrarErro(msg) {
            erro.textContent = msg;
            erro.classList.add('mostrar');
        }

        function ver(n) {
            numeroAtual = n;
            tapadas = false;
            erro.classList.remove('mostrar');
            campo.value = n;
            corpo.querySelectorAll('#apChips .chip').forEach(function (c) {
                c.classList.toggle('ativo', Number(c.getAttribute('data-n')) === n);
            });
            desenhar();
        }

        function desenhar() {
            var linhas = '';
            for (var i = 1; i <= 10; i++) {
                linhas += '<button type="button" class="ap-linha" style="--i:' + i + '"' + (tapadas ? ' data-tapada="1"' : '') + '>' +
                    '<span class="ap-conta">' + numeroAtual + ' × ' + i + '</span>' +
                    '<span class="ap-res">= <b>' + (numeroAtual * i) + '</b></span></button>';
            }
            corpo.querySelector('#apResultado').innerHTML =
                '<div class="ap-cabeca"><h3>' + T.t('ap_tabuada_do', numeroAtual) + '</h3>' +
                '<button type="button" class="btn btn-claro btn-pequeno" id="apTapar" aria-pressed="' + tapadas + '">' + (tapadas ? T.t('ap_mostrar') : T.t('ap_tapar')) + '</button></div>' +
                (tapadas ? '<p class="instrucao">' + T.t('ap_espreitar') + '</p>' : '') +
                '<div class="ap-lista">' + linhas + '</div>';

            corpo.querySelector('#apTapar').addEventListener('click', function () {
                tapadas = !tapadas;
                desenhar();
            });
            corpo.querySelectorAll('.ap-linha').forEach(function (linha) {
                linha.addEventListener('click', function () {
                    if (linha.getAttribute('data-tapada')) {
                        linha.removeAttribute('data-tapada');
                        T.som('virar');
                    }
                });
            });
        }

        corpo.querySelector('#apChips').addEventListener('click', function (ev) {
            var b = ev.target.closest('.chip');
            if (b) { T.som('passo'); ver(Number(b.getAttribute('data-n'))); }
        });

        corpo.querySelector('#apForm').addEventListener('submit', function (ev) {
            ev.preventDefault();
            var texto = campo.value.trim();
            if (texto === '') { mostrarErro(T.t('ap_erro_vazio')); return; }
            if (!/^\d{1,3}$/.test(texto) || Number(texto) > 100) { mostrarErro(T.t('ap_erro_invalido')); return; }
            ver(Number(texto));
        });

        if (numeroAtual !== null) ver(numeroAtual);
    }

    T.registar('aprender', { abrir: abrir, fechar: function () {} });
})();
