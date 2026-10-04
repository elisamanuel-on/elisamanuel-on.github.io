// O teclado da calculadora científica é só uma comodidade: o cálculo é feito em PHP.
// Sem JavaScript continua a ser possível escrever a expressão no campo e carregar em Enter.
(function () {
    var campo = document.getElementById('expressao');
    if (!campo) return;

    document.querySelectorAll('[data-insere]').forEach(function (botao) {
        botao.addEventListener('click', function () {
            var inicio = campo.selectionStart;
            var fim = campo.selectionEnd;
            var texto = botao.getAttribute('data-insere');
            campo.value = campo.value.slice(0, inicio) + texto + campo.value.slice(fim);
            campo.focus();
            campo.setSelectionRange(inicio + texto.length, inicio + texto.length);
        });
    });

    document.querySelector('[data-limpar]').addEventListener('click', function () {
        campo.value = '';
        campo.focus();
    });
    document.querySelector('[data-apagar]').addEventListener('click', function () {
        campo.value = campo.value.slice(0, -1);
        campo.focus();
    });
})();
