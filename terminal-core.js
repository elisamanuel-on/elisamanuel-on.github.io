// ============================================
// TERMINAL-CORE.JS
// Animação de arranque (boot) da página inicial. O antigo terminal interativo
// passou para o Perfil como consola de base de dados (consola.js)
// Traduzido (PT / EN / ES / FR), depende de i18n.js (t, L, idiomaAtual)
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

// Os números dos textos saem das listas do site (script.js), para nunca ficarem desatualizados
function numeros(texto) {
    const nProjetos = typeof projetos !== 'undefined' ? projetos.length : 18;
    const nExperiencias = typeof experiencias !== 'undefined' ? experiencias.length : 7;
    return String(texto).replace('{projetos}', nProjetos).replace('{experiencias}', nExperiencias);
}

/* ============================================
   BOOT SEQUENCE (index.html), traduzida
   ============================================ */
const BOOT_DADOS = [
    {
        comando: { pt: 'whoami', en: 'whoami', es: 'whoami', fr: 'whoami' },
        saida: {
            pt: ['Elisama Manuel: Desenvolvedora Web Júnior (Full-Stack)'],
            en: ['Elisama Manuel: Junior Full-Stack Web Developer'],
            es: ['Elisama Manuel: Desarrolladora Web Júnior (Full-Stack)'],
            fr: ['Elisama Manuel: Développeuse Web Junior (Full-Stack)']
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
            pt: ['{projetos} projetos encontrados. A carregar portfólio...'],
            en: ['{projetos} projects found. Loading portfolio...'],
            es: ['{projetos} proyectos encontrados. Cargando portafolio...'],
            fr: ['{projetos} projets trouvés. Chargement du portfolio...']
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

        for (const saida of L(linha.saida).map(numeros)) {
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

document.addEventListener('DOMContentLoaded', function () {
    correrBoot();
    configurarBotaoSaltar();
});
