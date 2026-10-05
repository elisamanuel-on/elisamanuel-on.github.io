// ============================================
// CONSOLA.JS
// Consola de "base de dados" do Perfil (substitui o antigo terminal).
// Lê as mesmas listas do script.js, por isso nunca fica desatualizada.
// Comandos: .help  .tables  .schema <tabela>  select ... from <tabela> [where col='valor']  clear
// ============================================

(function () {
    'use strict';

    const NOMES_TABELAS = ['projetos', 'tecnologias', 'experiencia', 'formacao'];
    let tabAtiva = 'projetos';

    function esc(texto) {
        return String(texto).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    // Constrói as tabelas a partir dos dados do site, no idioma atual
    function tabelas() {
        const areaPorTecnologia = {};
        gruposCompetencias.forEach(grupo => {
            if (grupo.itens) grupo.itens.forEach(nome => { areaPorTecnologia[nome] = L(grupo.nome); });
        });
        return {
            projetos: {
                cols: ['id', 'titulo', 'grupo', 'tecnologias', 'url'],
                rows: projetos.map(p => ({
                    id: p.id, titulo: L(p.titulo), grupo: p.grupo,
                    tecnologias: p.tecnologias.join(', '),
                    url: (p.url || '').replace(/^https?:\/\//, '')
                }))
            },
            tecnologias: {
                cols: ['id', 'nome', 'area'],
                rows: competencias.map((c, i) => ({ id: String(i + 1), nome: L(c), area: areaPorTecnologia[c.pt] || '' }))
            },
            experiencia: {
                cols: ['id', 'cargo', 'empresa', 'periodo'],
                rows: experiencias.map((e, i) => ({ id: String(i + 1), cargo: L(e.titulo), empresa: e.empresa, periodo: L(e.periodo) }))
            },
            formacao: {
                cols: ['id', 'curso', 'instituicao', 'periodo'],
                rows: formacao.map((f, i) => ({ id: String(i + 1), curso: L(f.curso), instituicao: f.instituicao, periodo: f.periodo }))
            }
        };
    }

    function htmlTabela(cols, rows) {
        const cabecalho = cols.map(c => `<th>${esc(c)}</th>`).join('');
        const linhas = rows.map(r => `<tr>${cols.map(c => `<td>${esc(r[c] ?? '')}</td>`).join('')}</tr>`).join('');
        return `<div class="db-scroll"><table><thead><tr>${cabecalho}</tr></thead><tbody>${linhas}</tbody></table></div><div class="db-meta">(${rows.length} ${esc(t('db_linhas'))})</div>`;
    }

    // Devolve o HTML do resultado, ou null para limpar a consola
    function executar(comando) {
        const tabs = tabelas();
        const c = comando.trim().replace(/;$/, '');
        if (!c) return '';
        if (/^(clear|\.clear)$/i.test(c)) return null;
        if (c === '.help') return `<div class="db-meta">${esc(t('db_help'))}</div>`;
        if (c === '.tables') return `<div class="db-ok">${Object.keys(tabs).join('   ')}</div>`;

        let m = c.match(/^\.schema\s+(\w+)$/i);
        if (m) {
            const nome = m[1].toLowerCase();
            return tabs[nome]
                ? `<div class="db-ok">CREATE TABLE ${nome} (${tabs[nome].cols.map(x => x + ' TEXT').join(', ')});</div>`
                : `<div class="db-err">${esc(t('db_notab'))}</div>`;
        }

        m = c.match(/^select\s+(\*|count\(\*\)|[\w,\s]+?)\s+from\s+(\w+)(?:\s+where\s+(\w+)\s*(=|like)\s*'?%?([^'%]*)%?'?)?$/i);
        if (!m) return `<div class="db-err">${esc(t('db_unk'))}</div>`;

        const nome = m[2].toLowerCase();
        if (!tabs[nome]) return `<div class="db-err">${esc(t('db_notab'))}</div>`;

        let rows = tabs[nome].rows;
        if (m[3]) {
            const col = m[3].toLowerCase();
            if (!tabs[nome].cols.includes(col)) return `<div class="db-err">${esc(t('db_unk'))}</div>`;
            const valor = m[5].toLowerCase();
            rows = rows.filter(r => {
                const celula = String(r[col]).toLowerCase();
                return m[4] === '=' ? celula === valor : celula.includes(valor);
            });
        }
        if (/^count/i.test(m[1])) return `<div class="db-ok">count(*) = ${rows.length}</div>`;

        let cols = tabs[nome].cols;
        if (m[1] !== '*') {
            const pedidas = m[1].split(',').map(s => s.trim().toLowerCase());
            if (pedidas.some(x => !cols.includes(x))) return `<div class="db-err">${esc(t('db_unk'))}</div>`;
            cols = pedidas;
        }
        return htmlTabela(cols, rows);
    }

    function correr(comando, limpar) {
        const saida = document.getElementById('consolaSaida');
        if (!saida) return;
        const resultado = executar(comando);
        if (resultado === null) { saida.innerHTML = ''; return; }
        const eco = `<div class="db-eco">sqlite&gt; ${esc(comando)}</div>`;
        saida.innerHTML = (limpar ? '' : saida.innerHTML) + eco + resultado;
        saida.scrollTop = limpar ? 0 : saida.scrollHeight;
    }

    function montar() {
        const alvo = document.getElementById('consola');
        if (!alvo) return;
        const tabs = tabelas();
        const separadores = NOMES_TABELAS.map(n =>
            `<button type="button" data-tab="${n}" class="${n === tabAtiva ? 'on' : ''}">${n}<small>${tabs[n].rows.length}</small></button>`
        ).join('');
        alvo.innerHTML = `
            <div class="db-janela">
                <div class="db-tabs" role="tablist">${separadores}</div>
                <div class="db-saida" id="consolaSaida" aria-live="polite"></div>
                <div class="db-entrada">
                    <span>sqlite&gt;</span>
                    <input type="text" id="consolaEntrada" autocomplete="off" spellcheck="false" aria-label="sqlite" placeholder="select * from projetos;">
                </div>
                <div class="db-dica">${esc(t('db_dica'))}</div>
            </div>`;

        alvo.querySelectorAll('[data-tab]').forEach(btn => {
            btn.addEventListener('click', () => {
                tabAtiva = btn.dataset.tab;
                alvo.querySelectorAll('[data-tab]').forEach(b => b.classList.toggle('on', b === btn));
                correr('select * from ' + tabAtiva + ';', true);
            });
        });

        const entrada = document.getElementById('consolaEntrada');
        entrada.addEventListener('keydown', e => {
            if (e.key !== 'Enter') return;
            const comando = entrada.value;
            entrada.value = '';
            correr(comando, false);
        });

        correr('select * from ' + tabAtiva + ';', true);
    }

    document.addEventListener('DOMContentLoaded', montar);
    document.addEventListener('idiomaAlterado', montar);
})();
