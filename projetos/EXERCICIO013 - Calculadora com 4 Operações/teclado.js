// O teclado, o histórico e a memória da calculadora científica são só comodidades:
// o cálculo é feito em PHP. Sem JavaScript continua a ser possível escrever a
// expressão no campo e carregar em Enter.
(function () {
    'use strict';

    var campo = document.getElementById('expressao');
    if (!campo) return;

    function avisar() {
        campo.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function inserir(texto) {
        var inicio = campo.selectionStart;
        var fim = campo.selectionEnd;
        campo.value = campo.value.slice(0, inicio) + texto + campo.value.slice(fim);
        campo.focus();
        campo.setSelectionRange(inicio + texto.length, inicio + texto.length);
        avisar();
    }

    function limpar() {
        campo.value = '';
        campo.focus();
        avisar();
    }

    document.querySelectorAll('[data-insere]').forEach(function (botao) {
        botao.addEventListener('click', function () {
            inserir(botao.getAttribute('data-insere'));
        });
    });

    document.querySelector('[data-limpar]').addEventListener('click', limpar);
    document.querySelector('[data-apagar]').addEventListener('click', function () {
        campo.value = campo.value.slice(0, -1);
        campo.focus();
        avisar();
    });

    // ---------- desfazer e refazer (o que está escrito no visor) ----------
    var pilha = [campo.value];
    var posicao = 0;
    var ultimoToque = 0;
    var botaoDesfazer = document.getElementById('desfazer');
    var botaoRefazer = document.getElementById('refazer');

    function atualizarBotoes() {
        if (botaoDesfazer) botaoDesfazer.disabled = posicao === 0;
        if (botaoRefazer) botaoRefazer.disabled = posicao === pilha.length - 1;
    }

    campo.addEventListener('input', function (ev) {
        if (campo.value === pilha[posicao]) return;
        var agora = Date.now();
        var escrita = ev.isTrusted && ev.inputType === 'insertText';
        pilha = pilha.slice(0, posicao + 1);
        if (escrita && posicao > 0 && agora - ultimoToque < 700) {
            pilha[posicao] = campo.value; // letras seguidas contam como um só passo
        } else {
            pilha.push(campo.value);
            posicao++;
            if (pilha.length > 100) { pilha.shift(); posicao--; }
        }
        ultimoToque = agora;
        atualizarBotoes();
    });

    function ir(novaPosicao) {
        if (novaPosicao < 0 || novaPosicao > pilha.length - 1) return;
        posicao = novaPosicao;
        campo.value = pilha[posicao];
        campo.focus();
        campo.setSelectionRange(campo.value.length, campo.value.length);
        ultimoToque = 0;
        atualizarBotoes();
        // avisa os efeitos (mistura de cores) sem criar um passo novo
        campo.dispatchEvent(new Event('input', { bubbles: true }));
    }

    if (botaoDesfazer) botaoDesfazer.addEventListener('click', function () { ir(posicao - 1); });
    if (botaoRefazer) botaoRefazer.addEventListener('click', function () { ir(posicao + 1); });
    atualizarBotoes();

    // ---------- atalhos de teclado ----------
    campo.addEventListener('keydown', function (ev) {
        var tecla = ev.key.toLowerCase();
        var comando = ev.ctrlKey || ev.metaKey;
        if (comando && tecla === 'z' && !ev.shiftKey) {
            ev.preventDefault();
            ir(posicao - 1);
        } else if (comando && (tecla === 'y' || (tecla === 'z' && ev.shiftKey))) {
            ev.preventDefault();
            ir(posicao + 1);
        } else if (ev.key === 'Escape') {
            limpar();
        }
    });

    // escrever números e operadores com o foco fora do visor leva-os para o visor
    document.addEventListener('keydown', function (ev) {
        if (ev.ctrlKey || ev.metaKey || ev.altKey || ev.key.length !== 1) return;
        var alvo = ev.target;
        var emCampo = alvo && /^(INPUT|SELECT|TEXTAREA)$/.test(alvo.tagName);
        if (emCampo || document.activeElement === campo) return;
        if (/[0-9+\-*\/().^%!,.]/.test(ev.key)) {
            ev.preventDefault();
            inserir(ev.key === ',' ? '.' : ev.key);
        }
    });

    // ---------- DEG / RAD ----------
    var emblema = document.getElementById('anguloBadge');
    var seletor = document.getElementById('angulo');

    function mostrarAngulo() {
        if (emblema && seletor) emblema.textContent = seletor.value === 'rad' ? 'RAD' : 'DEG';
    }

    if (emblema && seletor) {
        emblema.addEventListener('click', function () {
            seletor.value = seletor.value === 'rad' ? 'deg' : 'rad';
            mostrarAngulo();
        });
        seletor.addEventListener('change', mostrarAngulo);
        mostrarAngulo();
    }

    // ---------- copiar o resultado ----------
    var botaoCopiar = document.getElementById('copiar');

    function copiarTexto(texto) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(texto);
        }
        return new Promise(function (resolver, rejeitar) {
            var auxiliar = document.createElement('textarea');
            auxiliar.value = texto;
            auxiliar.setAttribute('readonly', '');
            auxiliar.style.position = 'fixed';
            auxiliar.style.opacity = '0';
            document.body.appendChild(auxiliar);
            auxiliar.select();
            var ok = false;
            try { ok = document.execCommand('copy'); } catch (erro) { ok = false; }
            auxiliar.remove();
            if (ok) resolver(); else rejeitar();
        });
    }

    if (botaoCopiar) {
        botaoCopiar.addEventListener('click', function () {
            copiarTexto(botaoCopiar.getAttribute('data-valor') || '').then(function () {
                botaoCopiar.setAttribute('data-aviso', botaoCopiar.getAttribute('data-copiado'));
                botaoCopiar.classList.add('copiado');
                setTimeout(function () {
                    botaoCopiar.classList.remove('copiado');
                    botaoCopiar.removeAttribute('data-aviso');
                }, 1600);
            }).catch(function () { /* sem permissão para a área de transferência */ });
        });
    }

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
