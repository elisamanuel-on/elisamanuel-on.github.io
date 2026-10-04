// Comodidades comuns a todas as páginas. O PHP já trata de tudo sem JavaScript:
// aqui só se poupa um clique (mudar de idioma logo ao escolher).
(function () {
    'use strict';

    var idioma = document.getElementById('idioma');
    if (idioma && idioma.form) {
        idioma.addEventListener('change', function () {
            idioma.form.submit();
        });
    }
})();
