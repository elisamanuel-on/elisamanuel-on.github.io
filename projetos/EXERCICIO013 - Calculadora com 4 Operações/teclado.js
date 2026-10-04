// O teclado, o histórico e a memória da calculadora científica são só comodidades:
// o cálculo é feito em PHP. Sem JavaScript continua a ser possível escrever a
// expressão no campo e carregar em Enter.
(function () {
    'use strict';

    var campo = document.getElementById('expressao');
    if (!campo) return;

    function inserir(texto) {
        var inicio = campo.selectionStart;
        var fim = campo.selectionEnd;
        campo.value = campo.value.slice(0, inicio) + texto + campo.value.slice(fim);
        campo.focus();
        campo.setSelectionRange(inicio + texto.length, inicio + texto.length);
        campo.dispatchEvent(new Event('input', { bubbles: true }));
    }

    document.querySelectorAll('[data-insere]').forEach(function (botao) {
        botao.addEventListener('click', function () {
            inserir(botao.getAttribute('data-insere'));
        });
    });

    document.querySelector('[data-limpar]').addEventListener('click', function () {
        campo.value = '';
        campo.focus();
        campo.dispatchEvent(new Event('input', { bubbles: true }));
    });
    document.querySelector('[data-apagar]').addEventListener('click', function () {
        campo.value = campo.value.slice(0, -1);
        campo.focus();
        campo.dispatchEvent(new Event('input', { bubbles: true }));
    });

    // ---------- histórico: clicar num cálculo volta a pô-lo no campo ----------
    document.querySelectorAll('[data-expr]').forEach(function (item) {
        item.addEventListener('click', function () {
            campo.value = item.getAttribute('data-expr');
            campo.focus();
            campo.setSelectionRange(campo.value.length, campo.value.length);
            campo.dispatchEvent(new Event('input', { bubbles: true }));
        });
    });

    // ---------- memória (guardada só neste navegador) ----------
    var CHAVE = 'calc013_memoria';
    var estado = document.getElementById('memoriaEstado');
    var formulario = document.getElementById('formCientifica');

    function lerMemoria() {
        try {
            var v = parseFloat(window.localStorage.getItem(CHAVE));
            return isFinite(v) ? v : null;
        } catch (erro) {
            return null;
        }
    }

    function guardarMemoria(valor) {
        try {
            if (valor === null) window.localStorage.removeItem(CHAVE);
            else window.localStorage.setItem(CHAVE, String(valor));
        } catch (erro) { /* armazenamento bloqueado: a memória só dura esta visita */ }
    }

    function arredondar(n) {
        return Math.round(n * 1e10) / 1e10;
    }

    function mostrarMemoria() {
        if (!estado) return;
        var m = lerMemoria();
        estado.textContent = estado.getAttribute('data-rotulo') + ': ' + (m === null ? estado.getAttribute('data-vazia') : arredondar(m));
        estado.classList.toggle('cheia', m !== null);
    }

    document.querySelectorAll('[data-mem]').forEach(function (botao) {
        botao.addEventListener('click', function () {
            var acao = botao.getAttribute('data-mem');
            var ans = parseFloat(formulario ? formulario.getAttribute('data-ans') : '0') || 0;
            var atual = lerMemoria();

            if (acao === 'mc') {
                guardarMemoria(null);
            } else if (acao === 'mmais') {
                guardarMemoria(arredondar((atual || 0) + ans));
            } else if (acao === 'mmenos') {
                guardarMemoria(arredondar((atual || 0) - ans));
            } else if (acao === 'mr' && atual !== null) {
                inserir(atual < 0 ? '(' + arredondar(atual) + ')' : String(arredondar(atual)));
            }
            mostrarMemoria();
        });
    });

    mostrarMemoria();
})();
