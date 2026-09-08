// ============================================
// TERMINAL-CORE.JS
// Motor partilhado: animação de boot (index) + terminal interativo (terminal.html)
// Totalmente traduzido (PT / EN / ES / FR) — depende de i18n.js (t, L, idiomaAtual)
// ============================================

/* ---------- Utilitário: escreve texto letra a letra ---------- */
function escreverLinha(el, texto, velocidade = 18) {
    return new Promise(resolve => {
        let i = 0;
        el.textContent = '';
        const intervalo = setInterval(() => {
            el.textContent += texto[i];
            i++;
            if (i >= texto.length) {
                clearInterval(intervalo);
                resolve();
            }
        }, velocidade);
    });
}

function pausa(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

/* Locale para a data/hora consoante o idioma */
const LOCALES = { pt: 'pt-PT', en: 'en-US', es: 'es-ES', fr: 'fr-FR' };

/* ============================================
   BOOT SEQUENCE (index.html) — traduzida
   ============================================ */
const BOOT_DADOS = [
    {
        comando: { pt: 'whoami', en: 'whoami', es: 'whoami', fr: 'whoami' },
        saida: {
            pt: ['Elisama Manuel — Desenvolvedora Web Júnior (Full-Stack)'],
            en: ['Elisama Manuel — Junior Full-Stack Web Developer'],
            es: ['Elisama Manuel — Desarrolladora Web Júnior (Full-Stack)'],
            fr: ['Elisama Manuel — Développeuse Web Junior (Full-Stack)']
        }
    },
    {
        comando: { pt: 'cat sobre.txt', en: 'cat about.txt', es: 'cat sobre.txt', fr: 'cat apropos.txt' },
        saida: {
            pt: ['Python · RPA · Análise de Dados · Desenvolvimento Web', 'Em transição de Finanças & Contabilidade para Tecnologia.'],
            en: ['Python · RPA · Data Analysis · Web Development', 'Transitioning from Finance & Accounting into Tech.'],
            es: ['Python · RPA · Análisis de Datos · Desarrollo Web', 'En transición de Finanzas y Contabilidad hacia la Tecnología.'],
            fr: ['Python · RPA · Analyse de Données · Développement Web', 'En transition de la Finance & Comptabilité vers la Technologie.']
        }
    },
    {
        comando: { pt: 'ls projetos/', en: 'ls projects/', es: 'ls proyectos/', fr: 'ls projets/' },
        saida: {
            pt: ['12 projetos encontrados. A carregar portfólio...'],
            en: ['12 projects found. Loading portfolio...'],
            es: ['12 proyectos encontrados. Cargando portafolio...'],
            fr: ['12 projets trouvés. Chargement du portfolio...']
        }
    },
    {
        comando: { pt: './iniciar_site.sh', en: './start_site.sh', es: './iniciar_sitio.sh', fr: './demarrer_site.sh' },
        saida: {
            pt: ['[██████████████████████████] 100%', 'Portfólio pronto.'],
            en: ['[██████████████████████████] 100%', 'Portfolio ready.'],
            es: ['[██████████████████████████] 100%', 'Portafolio listo.'],
            fr: ['[██████████████████████████] 100%', 'Portfolio prêt.']
        }
    }
];

async function correrBoot() {
    const overlay = document.getElementById('bootOverlay');
    const corpo = document.getElementById('bootCorpo');
    if (!overlay || !corpo) return;

    // Visitantes que já viram o boot nesta sessão não esperam de novo
    if (sessionStorage.getItem('bootVisto') === 'sim') {
        overlay.remove();
        document.body.classList.add('pagina-pronta');
        return;
    }

    document.body.classList.add('boot-ativo');

    for (const linha of BOOT_DADOS) {
        const linhaPrompt = document.createElement('div');
        linhaPrompt.className = 'boot-linha';
        const spanPrompt = document.createElement('span');
        spanPrompt.className = 'boot-prompt';
        spanPrompt.textContent = 'elisama@portfolio:~$ ';
        const spanComando = document.createElement('span');
        linhaPrompt.appendChild(spanPrompt);
        linhaPrompt.appendChild(spanComando);
        corpo.appendChild(linhaPrompt);

        await escreverLinha(spanComando, L(linha.comando), 28);
        await pausa(150);

        for (const saida of L(linha.saida)) {
            const linhaSaida = document.createElement('div');
            linhaSaida.className = 'boot-saida';
            linhaSaida.textContent = saida;
            corpo.appendChild(linhaSaida);
            await pausa(180);
        }
        await pausa(220);
    }

    await pausa(350);
    overlay.classList.add('boot-fade');
    sessionStorage.setItem('bootVisto', 'sim');
    setTimeout(() => {
        overlay.remove();
        document.body.classList.remove('boot-ativo');
        document.body.classList.add('pagina-pronta');
    }, 500);
}

function configurarBotaoSaltar() {
    const btn = document.getElementById('bootSaltar');
    if (!btn) return;
    btn.addEventListener('click', () => {
        const overlay = document.getElementById('bootOverlay');
        if (!overlay) return;
        sessionStorage.setItem('bootVisto', 'sim');
        overlay.remove();
        document.body.classList.remove('boot-ativo');
        document.body.classList.add('pagina-pronta');
    });
}

/* ============================================
   TERMINAL INTERATIVO (terminal.html) — totalmente traduzido
   ============================================ */

/* Palavra que aciona cada comando, por idioma. "id" é o identificador interno (não muda). */
const TRIGGERS = {
    pt: { help: 'ajuda', sobre: 'sobre', skills: 'skills', experiencia: 'experiencia', projetos: 'projetos', contacto: 'contacto', cv: 'cv', github: 'github', linkedin: 'linkedin', whoami: 'whoami', date: 'date', clear: 'clear', banner: 'banner', 'sudo contrata-me': 'easter' },
    en: { help: 'ajuda', about: 'sobre', skills: 'skills', experience: 'experiencia', projects: 'projetos', contact: 'contacto', cv: 'cv', github: 'github', linkedin: 'linkedin', whoami: 'whoami', date: 'date', clear: 'clear', banner: 'banner', 'sudo hire-me': 'easter' },
    es: { help: 'ajuda', sobre: 'sobre', skills: 'skills', experiencia: 'experiencia', proyectos: 'projetos', contacto: 'contacto', cv: 'cv', github: 'github', linkedin: 'linkedin', whoami: 'whoami', date: 'date', clear: 'clear', banner: 'banner', 'sudo contratame': 'easter' },
    fr: { help: 'ajuda', apropos: 'sobre', competences: 'skills', experience: 'experiencia', projets: 'projetos', contact: 'contacto', cv: 'cv', github: 'github', linkedin: 'linkedin', whoami: 'whoami', date: 'date', clear: 'clear', banner: 'banner', 'sudo recrutemoi': 'easter' }
};

/* Lista (id + descrição) para o comando "help", pela ordem em que deve aparecer */
const AJUDA_LISTA = [
    { id: 'sobre', desc: { pt: 'quem sou eu', en: 'who I am', es: 'quién soy', fr: 'qui je suis' } },
    { id: 'skills', desc: { pt: 'tecnologias que domino', en: 'technologies I use', es: 'tecnologías que domino', fr: 'technologies que je maîtrise' } },
    { id: 'experiencia', desc: { pt: 'percurso profissional', en: 'professional background', es: 'trayectoria profesional', fr: 'parcours professionnel' } },
    { id: 'projetos', desc: { pt: 'lista de projetos', en: 'list of projects', es: 'lista de proyectos', fr: 'liste de projets' } },
    { id: 'contacto', desc: { pt: 'como falar comigo', en: 'how to reach me', es: 'cómo hablar conmigo', fr: 'comment me contacter' } },
    { id: 'cv', desc: { pt: 'descarregar o meu CV', en: 'download my CV', es: 'descargar mi CV', fr: 'télécharger mon CV' } },
    { id: 'clear', desc: { pt: 'limpar o ecrã', en: 'clear the screen', es: 'limpiar la pantalla', fr: "effacer l'écran" } }
];

const TEXTOS_TERMINAL = {
    sobre: {
        pt: ['Elisama Manuel — Desenvolvedora Web Júnior (Full-Stack).', 'Transição de Finanças &amp; Contabilidade para Tecnologia.', 'Foco em Python, Automação (RPA), Análise de Dados e Web.'],
        en: ['Elisama Manuel — Junior Full-Stack Web Developer.', 'Transitioning from Finance &amp; Accounting into Tech.', 'Focused on Python, Automation (RPA), Data Analysis and Web.'],
        es: ['Elisama Manuel — Desarrolladora Web Júnior (Full-Stack).', 'Transición de Finanzas y Contabilidad hacia la Tecnología.', 'Enfocada en Python, Automatización (RPA), Análisis de Datos y Web.'],
        fr: ['Elisama Manuel — Développeuse Web Junior (Full-Stack).', 'Transition de la Finance &amp; Comptabilité vers la Technologie.', 'Axée sur Python, Automatisation (RPA), Analyse de Données et Web.']
    },
    skills: {
        pt: ['Python · RPA · Pandas · FastAPI · Flask · Dash', 'SQLite · MongoDB · Git · HTML/CSS · JavaScript · Machine Learning'],
        en: ['Python · RPA · Pandas · FastAPI · Flask · Dash', 'SQLite · MongoDB · Git · HTML/CSS · JavaScript · Machine Learning'],
        es: ['Python · RPA · Pandas · FastAPI · Flask · Dash', 'SQLite · MongoDB · Git · HTML/CSS · JavaScript · Machine Learning'],
        fr: ['Python · RPA · Pandas · FastAPI · Flask · Dash', 'SQLite · MongoDB · Git · HTML/CSS · JavaScript · Machine Learning']
    },
    experiencia: {
        pt: '7 experiências profissionais — ver perfil completo em',
        en: '7 professional roles — see full profile at',
        es: '7 experiencias profesionales — ver perfil completo en',
        fr: '7 expériences professionnelles — voir le profil complet sur'
    },
    projetos: {
        pt: '12 projetos disponíveis →',
        en: '12 projects available →',
        es: '12 proyectos disponibles →',
        fr: '12 projets disponibles →'
    },
    contacto: {
        pt: ['email: elisamanueljob@gmail.com', 'tel:&nbsp;&nbsp;&nbsp;+351 913 516 395'],
        en: ['email: elisamanueljob@gmail.com', 'phone: +351 913 516 395'],
        es: ['email: elisamanueljob@gmail.com', 'tel:&nbsp;&nbsp;&nbsp;+351 913 516 395'],
        fr: ['email: elisamanueljob@gmail.com', 'tél:&nbsp;&nbsp;&nbsp;+351 913 516 395']
    },
    contacto_mais: {
        pt: 'mais em',
        en: 'more at',
        es: 'más en',
        fr: 'plus sur'
    },
    cv_a_descarregar: {
        pt: 'A descarregar CV...',
        en: 'Downloading CV...',
        es: 'Descargando CV...',
        fr: 'Téléchargement du CV...'
    },
    github_abrir: {
        pt: 'A abrir github.com/elisamanuel-on ...',
        en: 'Opening github.com/elisamanuel-on ...',
        es: 'Abriendo github.com/elisamanuel-on ...',
        fr: 'Ouverture de github.com/elisamanuel-on ...'
    },
    linkedin_abrir: {
        pt: 'A abrir LinkedIn...',
        en: 'Opening LinkedIn...',
        es: 'Abriendo LinkedIn...',
        fr: 'Ouverture de LinkedIn...'
    },
    banner_subtitulo: {
        pt: 'escreve "help" para começar',
        en: 'type "help" to begin',
        es: 'escribe "help" para empezar',
        fr: 'tapez "help" pour commencer'
    },
    easter: {
        pt: ['[sudo] password for recrutador: ********', '[OK] Acesso concedido. A processar candidatura...', 'Envia um email para elisamanueljob@gmail.com'],
        en: ['[sudo] password for recruiter: ********', '[OK] Access granted. Processing application...', 'Send an email to elisamanueljob@gmail.com'],
        es: ['[sudo] password para reclutador: ********', '[OK] Acceso concedido. Procesando candidatura...', 'Envía un email a elisamanueljob@gmail.com'],
        fr: ['[sudo] mot de passe pour recruteur : ********', '[OK] Accès accordé. Traitement de la candidature...', 'Envoyez un e-mail à elisamanueljob@gmail.com']
    },
    comando_nao_encontrado: {
        pt: (cmd) => `comando não encontrado: ${cmd}. escreve "help" para a lista de comandos.`,
        en: (cmd) => `command not found: ${cmd}. type "help" for the list of commands.`,
        es: (cmd) => `comando no encontrado: ${cmd}. escribe "help" para ver la lista de comandos.`,
        fr: (cmd) => `commande introuvable : ${cmd}. tapez "help" pour la liste des commandes.`
    },
    idioma_mudou: {
        pt: 'idioma alterado para Português.',
        en: 'language switched to English.',
        es: 'idioma cambiado a Español.',
        fr: 'langue changée en Français.'
    }
};

function iniciarTerminalInterativo() {
    const output = document.getElementById('termOutput');
    const input = document.getElementById('termInput');
    const janela = document.getElementById('termJanela');
    if (!output || !input) return;

    const historico = [];
    let indiceHistorico = -1;

    function imprimir(texto = '', classe = '') {
        const linha = document.createElement('div');
        linha.className = 'term-linha' + (classe ? ' ' + classe : '');
        linha.innerHTML = texto;
        output.appendChild(linha);
        janela.scrollTop = janela.scrollHeight;
    }

    function escaparHtml(texto) {
        const div = document.createElement('div');
        div.textContent = texto;
        return div.innerHTML;
    }

    function imprimirPrompt(comando) {
        imprimir(`<span class="term-prompt">elisama@portfolio:~$</span> ${escaparHtml(comando)}`, 'term-echo');
    }

    function desenharCaixa(linhas) {
        const largura = Math.max(...linhas.map(l => l.length)) + 4;
        imprimir('┌' + '─'.repeat(largura) + '┐');
        linhas.forEach(l => {
            const meio = '  ' + l;
            imprimir('│' + meio.padEnd(largura) + '│');
        });
        imprimir('└' + '─'.repeat(largura) + '┘');
    }

    function mostrarBanner() {
        desenharCaixa([
            'ELISAMA MANUEL — TERMINAL',
            L(TEXTOS_TERMINAL.banner_subtitulo)
        ]);
    }

    const ACOES = {
        ajuda: () => {
            const gatilhos = TRIGGERS[idiomaAtual];
            imprimir(t('term_help_titulo'));
            AJUDA_LISTA.forEach(item => {
                const palavra = Object.keys(gatilhos).find(k => gatilhos[k] === item.id);
                imprimir(`&nbsp;&nbsp;${palavra.padEnd(14, ' ')} — ${L(item.desc)}`);
            });
            const ghKey = Object.keys(gatilhos).find(k => gatilhos[k] === 'github');
            const liKey = Object.keys(gatilhos).find(k => gatilhos[k] === 'linkedin');
            imprimir(`&nbsp;&nbsp;${(ghKey + ' / ' + liKey).padEnd(14, ' ')} — ${t('term_help_perfis')}`);
        },
        sobre: () => L(TEXTOS_TERMINAL.sobre).forEach(l => imprimir(l)),
        skills: () => L(TEXTOS_TERMINAL.skills).forEach(l => imprimir(l)),
        experiencia: () => {
            imprimir(L(TEXTOS_TERMINAL.experiencia));
            imprimir('<a href="index.html#experiencia">index.html#experiencia</a>');
        },
        projetos: () => {
            imprimir(`${L(TEXTOS_TERMINAL.projetos)} <a href="portfolio.html">portfolio.html</a>`);
        },
        contacto: () => {
            L(TEXTOS_TERMINAL.contacto).forEach(l => imprimir(l));
            imprimir(`${L(TEXTOS_TERMINAL.contacto_mais)} <a href="contacto.html">contacto.html</a>`);
        },
        cv: () => {
            imprimir(L(TEXTOS_TERMINAL.cv_a_descarregar));
            const a = document.createElement('a');
            a.href = 'cv/Elisama-Manuel-CV.pdf';
            a.download = '';
            document.body.appendChild(a);
            a.click();
            a.remove();
        },
        github: () => {
            imprimir(L(TEXTOS_TERMINAL.github_abrir));
            window.open('https://github.com/elisamanuel-on', '_blank');
        },
        linkedin: () => {
            imprimir(L(TEXTOS_TERMINAL.linkedin_abrir));
            window.open('https://www.linkedin.com/in/elisama-manuel-49025117a/', '_blank');
        },
        whoami: () => imprimir('elisama'),
        date: () => imprimir(new Date().toLocaleString(LOCALES[idiomaAtual] || 'pt-PT')),
        clear: () => { output.innerHTML = ''; },
        banner: mostrarBanner,
        easter: () => L(TEXTOS_TERMINAL.easter).forEach(l => imprimir(l))
    };

    function executar(comandoBruto) {
        const comando = comandoBruto.trim();
        imprimirPrompt(comandoBruto);
        if (!comando) return;

        historico.push(comandoBruto);
        indiceHistorico = historico.length;

        const chave = comando.toLowerCase();
        const gatilhos = TRIGGERS[idiomaAtual];
        const id = gatilhos[chave];

        if (id && ACOES[id]) {
            ACOES[id]();
        } else {
            imprimir(TEXTOS_TERMINAL.comando_nao_encontrado[idiomaAtual](escaparHtml(comando)), 'term-erro');
        }
    }

    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            executar(input.value);
            input.value = '';
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (indiceHistorico > 0) {
                indiceHistorico--;
                input.value = historico[indiceHistorico];
            }
        } else if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (indiceHistorico < historico.length - 1) {
                indiceHistorico++;
                input.value = historico[indiceHistorico];
            } else {
                indiceHistorico = historico.length;
                input.value = '';
            }
        }
    });

    janela.addEventListener('click', () => input.focus());

    mostrarBanner();
    input.focus();

    // Se o idioma mudar enquanto o terminal está aberto, avisa e atualiza a sugestão
    document.addEventListener('idiomaAlterado', () => {
        imprimir(`[i] ${L(TEXTOS_TERMINAL.idioma_mudou)}`, 'term-echo');
    });
}

/* ---------- Sugestão de comandos por baixo do terminal (terminal.html) ---------- */
function atualizarTermHint() {
    const alvo = document.getElementById('termHint');
    if (!alvo) return;
    const gatilhos = TRIGGERS[idiomaAtual];
    const buscar = id => Object.keys(gatilhos).find(k => gatilhos[k] === id);
    alvo.innerHTML = `${t('term_hint_prefixo')} <strong>${buscar('ajuda')}</strong>, <strong>${buscar('projetos')}</strong>, <strong>${buscar('skills')}</strong>, <strong>${buscar('easter')}</strong>`;
}

document.addEventListener('idiomaAlterado', atualizarTermHint);

document.addEventListener('DOMContentLoaded', function () {
    correrBoot();
    configurarBotaoSaltar();
    iniciarTerminalInterativo();
    atualizarTermHint();
});