/* Sistema de Gestão Escolar · v2.1.0 · comportamento das páginas (sem bibliotecas, ficheiro externo por causa da política de segurança) */
(function () {
    'use strict';

    var decimal = document.documentElement.lang === 'en' ? '.' : ',';

    function lerNumero(texto) {
        var t = String(texto).trim().replace(',', '.');
        if (t === '') { return null; }
        var n = Number(t);
        return isFinite(n) ? n : NaN;
    }
    function formatar(n, casas) { return n.toFixed(casas === undefined ? 1 : casas).replace('.', decimal); }
    function notaFinal(media) { return Math.floor(media + 0.5); }

    /* ---------- botões e formulários gerais ---------- */
    document.addEventListener('click', function (ev) {
        var alvo = ev.target.closest ? ev.target.closest('[data-imprimir], [data-confirmar-botao]') : null;
        if (!alvo) { return; }
        if (alvo.hasAttribute('data-imprimir')) { window.print(); return; }
        if (alvo.hasAttribute('data-confirmar-botao') && !window.confirm(alvo.getAttribute('data-confirmar-botao'))) {
            ev.preventDefault();
        }
    });
    document.addEventListener('submit', function (ev) {
        var msg = ev.target.getAttribute && ev.target.getAttribute('data-confirmar');
        if (msg && !window.confirm(msg)) { ev.preventDefault(); }
    });
    document.querySelectorAll('form[data-auto] select, form[data-auto] input[type="date"], form[data-auto] input[type="checkbox"]').forEach(function (campo) {
        campo.addEventListener('change', function () { campo.form.submit(); });
    });

    /* ---------- lançamento de notas: média e nota final ao escrever ---------- */
    function recalcularLinhaNotas(tr) {
        var pares = [];
        tr.querySelectorAll('.campo-nota').forEach(function (campo) {
            var v = lerNumero(campo.value);
            var mau = v !== null && (isNaN(v) || v < 0 || v > 20);
            campo.classList.toggle('invalida', mau);
            if (v !== null && !mau) { pares.push([v, Number(campo.getAttribute('data-peso')) || 0]); }
        });
        var soma = 0, pesos = 0;
        pares.forEach(function (p) { soma += p[0] * p[1]; pesos += p[1]; });
        var mediaTd = tr.querySelector('.js-media'), finalTd = tr.querySelector('.js-final');
        if (!mediaTd || !finalTd) { return; }
        if (pesos > 0) {
            var m = soma / pesos, f = notaFinal(m);
            mediaTd.textContent = formatar(m, 1);
            finalTd.innerHTML = '<span class="nota ' + (f >= 10 ? 'pos' : 'neg') + '">' + f + '</span>';
        } else {
            mediaTd.textContent = '—';
            finalTd.textContent = '—';
        }
    }
    document.querySelectorAll('[data-lancamento]').forEach(function (form) {
        form.addEventListener('input', function (ev) {
            if (ev.target.classList.contains('campo-nota')) { recalcularLinhaNotas(ev.target.closest('tr')); }
        });
        // Enter passa ao aluno seguinte (mesma avaliação) em vez de submeter
        form.addEventListener('keydown', function (ev) {
            if (ev.key !== 'Enter' || !ev.target.classList.contains('campo-nota')) { return; }
            ev.preventDefault();
            var tr = ev.target.closest('tr'), idx = Array.prototype.indexOf.call(tr.querySelectorAll('.campo-nota'), ev.target);
            var seguinte = tr.nextElementSibling;
            if (seguinte) {
                var campo = seguinte.querySelectorAll('.campo-nota')[idx];
                if (campo) { campo.focus(); campo.select(); }
            }
        });
    });

    /* ---------- despesas e receitas: IVA e total ao escrever ---------- */
    document.querySelectorAll('form[data-iva]').forEach(function (formIva) {
        var base = formIva.querySelector('[data-iva-base]'), taxa = formIva.querySelector('[data-iva-taxa]'), saida = formIva.querySelector('[data-iva-resumo]');
        if (!base || !taxa || !saida) { return; }
        var vazio = saida.textContent;
        var moeda;
        try { moeda = new Intl.NumberFormat(document.documentElement.lang === 'en' ? 'en-IE' : document.documentElement.lang, { style: 'currency', currency: 'EUR' }); } catch (e) { moeda = null; }
        function euros(n) { return moeda ? moeda.format(n) : n.toFixed(2).replace('.', decimal) + ' €'; }
        function atualizar() {
            var texto = String(base.value).replace(/\s/g, '').replace(/\.(?=\d{3},)/g, '').replace(',', '.');
            var v = Number(texto);
            if (texto === '' || !isFinite(v) || v < 0) { saida.textContent = vazio; return; }
            var t = Number(taxa.value) || 0, iva = Math.round(v * t) / 100;
            saida.textContent = saida.getAttribute('data-texto-iva') + ' (' + t + '%): ' + euros(iva) + ' · ' + saida.getAttribute('data-texto-total') + ': ' + euros(Math.round(v * 100) / 100 + iva);
        }
        base.addEventListener('input', atualizar);
        taxa.addEventListener('change', atualizar);
        atualizar();
    });

    /* ---------- editor do relatório ---------- */
    var form = document.querySelector('[data-relatorio]');
    if (!form) { return; }

    var D = form.dataset;
    var cabeca = document.getElementById('cabeca-relatorio');
    var corpo = document.getElementById('corpo-relatorio');
    var maxLinhas = Number(D.maxLinhas), maxColunas = Number(D.maxColunas), positiva = Number(D.positiva);
    var iconeLixo = (document.getElementById('molde-lixo') || {}).innerHTML || '×';

    function colunas() { return Array.prototype.slice.call(cabeca.querySelectorAll('[data-coluna]')); }
    function linhas() { return Array.prototype.slice.call(corpo.querySelectorAll('[data-linha]')); }

    function criar(tag, atributos, html) {
        var el = document.createElement(tag);
        Object.keys(atributos || {}).forEach(function (k) { el.setAttribute(k, atributos[k]); });
        if (html !== undefined) { el.innerHTML = html; }
        return el;
    }

    function renumerar() {
        linhas().forEach(function (tr, i) {
            tr.querySelectorAll('td.col-nota input').forEach(function (campo, j) {
                campo.name = 'nota[' + i + '][' + j + ']';
            });
        });
    }

    function recalcular() {
        var pesos = colunas().map(function (th) { return Number(th.querySelector('[name="col_peso[]"]').value) || 0; });
        var positivas = 0, negativas = 0, somaFinais = 0, comNota = 0;
        linhas().forEach(function (tr) {
            var soma = 0, total = 0;
            tr.querySelectorAll('td.col-nota input').forEach(function (campo, j) {
                var v = lerNumero(campo.value);
                var mau = v !== null && (isNaN(v) || v < 0 || v > 20);
                campo.classList.toggle('invalida', mau);
                campo.classList.toggle('neg', v !== null && !mau && v < positiva);
                if (v !== null && !mau) { soma += v * (pesos[j] || 0); total += (pesos[j] || 0); }
            });
            var mediaTd = tr.querySelector('.js-media'), finalTd = tr.querySelector('.js-final'), resTd = tr.querySelector('.js-res');
            if (total > 0) {
                var m = soma / total, f = notaFinal(m), ok = f >= positiva;
                mediaTd.textContent = formatar(m, 1);
                finalTd.textContent = String(f);
                resTd.innerHTML = '';
                resTd.appendChild(criar('span', { 'class': 'etiqueta ' + (ok ? 'pos' : 'neg') }, ''));
                resTd.firstChild.textContent = ok ? D.tPositiva : D.tNegativa;
                ok ? positivas++ : negativas++;
                somaFinais += f; comNota++;
            } else {
                mediaTd.textContent = '—'; finalTd.textContent = '—'; resTd.innerHTML = '';
            }
        });
        var resumo = document.getElementById('resumo-relatorio');
        resumo.querySelector('[data-resumo="pos"]').textContent = positivas;
        resumo.querySelector('[data-resumo="neg"]').textContent = negativas;
        resumo.querySelector('[data-resumo="media"]').textContent = comNota ? formatar(somaFinais / comNota, 1) : '—';
        var soma = pesos.reduce(function (a, b) { return a + b; }, 0);
        var pesosEl = resumo.querySelector('[data-resumo="pesos"]');
        pesosEl.textContent = soma + '%';
        pesosEl.className = soma === 100 ? 'texto-ok' : 'texto-aviso';

        var addL = form.querySelector('[data-acao="add-linha"]'), addC = form.querySelector('[data-acao="add-coluna"]');
        addL.disabled = linhas().length >= maxLinhas; addL.title = addL.disabled ? D.tLimite : '';
        addC.disabled = colunas().length >= maxColunas; addC.title = addC.disabled ? D.tLimite : '';
    }

    function celulaNota() {
        var td = criar('td', { 'class': 'col-nota' });
        td.appendChild(criar('input', { type: 'text', inputmode: 'decimal', 'class': 'campo-nota', name: 'nota[][]', maxlength: '4', 'aria-label': D.tNota }));
        return td;
    }

    function adicionarLinha() {
        if (linhas().length >= maxLinhas) { return; }
        var tr = criar('tr', { 'data-linha': '' });
        var numero = linhas().length + 1;
        var tdNum = criar('td', { 'class': 'col-num' });
        tdNum.appendChild(criar('input', { type: 'text', name: 'aluno_numero[]', maxlength: '6', value: String(numero), 'aria-label': 'N.º' }));
        var tdNome = criar('td', { 'class': 'col-nome' });
        tdNome.appendChild(criar('input', { type: 'text', name: 'aluno_nome[]', maxlength: '80', 'aria-label': D.tNome }));
        tr.appendChild(tdNum); tr.appendChild(tdNome);
        colunas().forEach(function () { tr.appendChild(celulaNota()); });
        tr.appendChild(criar('td', { 'class': 'col-calc js-media' }, '—'));
        tr.appendChild(criar('td', { 'class': 'col-calc js-final' }, '—'));
        tr.appendChild(criar('td', { 'class': 'col-calc js-res' }));
        var tdAcao = criar('td', { 'class': 'col-acao' });
        var btn = criar('button', { type: 'button', 'class': 'icone-botao perigo', 'data-acao': 'remover-linha', title: D.tRemoverAluno, 'aria-label': D.tRemoverAluno }, iconeLixo);
        tdAcao.appendChild(btn); tr.appendChild(tdAcao);
        corpo.appendChild(tr);
        renumerar(); recalcular();
        tr.querySelector('[name="aluno_nome[]"]').focus();
    }

    function adicionarColuna() {
        if (colunas().length >= maxColunas) { return; }
        var n = colunas().length + 1;
        var th = criar('th', { 'class': 'col-nota', 'data-coluna': '' });
        th.appendChild(criar('input', { type: 'text', name: 'col_titulo[]', maxlength: '30', value: D.tAvaliacao + ' ' + n, 'aria-label': D.tTituloColuna }));
        var peso = criar('span', { 'class': 'peso-linha' });
        peso.appendChild(criar('input', { type: 'number', name: 'col_peso[]', min: '1', max: '100', value: n === 1 ? '100' : '10', 'aria-label': D.tPeso + ' %' }));
        peso.appendChild(document.createTextNode('%'));
        peso.appendChild(criar('button', { type: 'button', 'class': 'icone-botao perigo pequeno', 'data-acao': 'remover-coluna', title: D.tRemoverColuna, 'aria-label': D.tRemoverColuna }, iconeLixo));
        th.appendChild(peso);
        cabeca.insertBefore(th, cabeca.querySelector('th.col-calc'));
        linhas().forEach(function (tr) { tr.insertBefore(celulaNota(), tr.querySelector('.js-media')); });
        renumerar(); recalcular();
        th.querySelector('input').focus(); th.querySelector('input').select();
    }

    function removerColuna(th) {
        var j = colunas().indexOf(th);
        if (j < 0) { return; }
        linhas().forEach(function (tr) { var td = tr.querySelectorAll('td.col-nota')[j]; if (td) { td.remove(); } });
        th.remove();
        renumerar(); recalcular();
    }

    form.addEventListener('click', function (ev) {
        var botao = ev.target.closest ? ev.target.closest('[data-acao]') : null;
        if (!botao || botao.disabled) { return; }
        var acao = botao.getAttribute('data-acao');
        if (acao === 'add-linha') { adicionarLinha(); }
        else if (acao === 'add-coluna') { adicionarColuna(); }
        else if (acao === 'remover-linha') { botao.closest('tr').remove(); renumerar(); recalcular(); }
        else if (acao === 'remover-coluna') { removerColuna(botao.closest('[data-coluna]')); }
        else if (acao === 'limpar-notas') {
            if (window.confirm(D.tConfirmarLimpar)) {
                form.querySelectorAll('.campo-nota').forEach(function (c) { c.value = ''; });
                recalcular();
            }
        }
    });
    form.addEventListener('input', recalcular);
    form.addEventListener('submit', renumerar);
    // Enter passa ao aluno seguinte, na mesma coluna, em vez de submeter o formulário
    form.addEventListener('keydown', function (ev) {
        if (ev.key !== 'Enter' || ev.target.tagName !== 'INPUT') { return; }
        var td = ev.target.closest('td');
        if (!td) { if (ev.target.type !== 'submit') { ev.preventDefault(); } return; }
        ev.preventDefault();
        var tr = td.parentElement, idx = Array.prototype.indexOf.call(tr.children, td);
        var seguinte = tr.nextElementSibling;
        if (seguinte && seguinte.children[idx]) {
            var campo = seguinte.children[idx].querySelector('input');
            if (campo) { campo.focus(); campo.select(); }
        }
    });

    renumerar();
    recalcular();
})();
