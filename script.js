// ============================================
// PORTFÓLIO CV - ELISAMA MANUEL
// Dados dinâmicos com JavaScript
// ============================================

console.log('Portfólio CV carregado!');

// ============================================
// ÍCONES SVG (substituem emojis — cor herdada via currentColor)
// ============================================
const ICONES = {
    sol: '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>',
    lua: '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>'
};

// ============================================
// 0. THEME TOGGLE (DARK/LIGHT MODE)
// ============================================

function initTheme() {
    const toggleBtn = document.getElementById('toggleTheme');
    if (!toggleBtn) return;

    function aplicar(tema) {
        const claro = tema === 'light';
        document.body.classList.toggle('light-mode', claro);
        const textoTema = claro ? t('theme_light') : t('theme_dark');
        toggleBtn.innerHTML = `<span class="theme-icon">${claro ? ICONES.sol : ICONES.lua}</span><span class="theme-text">${textoTema}</span>`;
    }

    // guarda a função para o texto poder ser atualizado quando o idioma muda
    toggleBtn._aplicarTema = aplicar;

    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'light' || savedTheme === 'dark') {
        aplicar(savedTheme);
    } else {
        const prefersLight = window.matchMedia('(prefers-color-scheme: light)').matches;
        aplicar(prefersLight ? 'light' : 'dark');
    }

    toggleBtn.addEventListener('click', function (e) {
        e.preventDefault();
        const isLight = document.body.classList.toggle('light-mode');
        aplicar(isLight ? 'light' : 'dark');
        localStorage.setItem('theme', isLight ? 'light' : 'dark');
    });
}

function atualizarTextoTema() {
    const toggleBtn = document.getElementById('toggleTheme');
    if (!toggleBtn || !toggleBtn._aplicarTema) return;
    const claro = document.body.classList.contains('light-mode');
    toggleBtn._aplicarTema(claro ? 'light' : 'dark');
}

// ============================================
// 1. DADOS - COMPETÊNCIAS TÉCNICAS
// ============================================
const competencias = [
    { pt: 'Python', en: 'Python', es: 'Python', fr: 'Python' },
    { pt: 'RPA (Automação)', en: 'RPA (Automation)', es: 'RPA (Automatización)', fr: 'RPA (Automatisation)' },
    { pt: 'Pandas', en: 'Pandas', es: 'Pandas', fr: 'Pandas' },
    { pt: 'FastAPI', en: 'FastAPI', es: 'FastAPI', fr: 'FastAPI' },
    { pt: 'Flask', en: 'Flask', es: 'Flask', fr: 'Flask' },
    { pt: 'Dash', en: 'Dash', es: 'Dash', fr: 'Dash' },
    { pt: 'HTML & CSS', en: 'HTML & CSS', es: 'HTML & CSS', fr: 'HTML & CSS' },
    { pt: 'JavaScript', en: 'JavaScript', es: 'JavaScript', fr: 'JavaScript' },
    { pt: 'SQLite', en: 'SQLite', es: 'SQLite', fr: 'SQLite' },
    { pt: 'MongoDB', en: 'MongoDB', es: 'MongoDB', fr: 'MongoDB' },
    { pt: 'Git', en: 'Git', es: 'Git', fr: 'Git' },
    { pt: 'Machine Learning', en: 'Machine Learning', es: 'Machine Learning', fr: 'Machine Learning' }
];

// Competências complementares (gestão/negócio) — reforça a transição de carreira
const competenciasComplementares = [
    { pt: 'Excel Avançado', en: 'Advanced Excel', es: 'Excel Avanzado', fr: 'Excel Avancé' },
    { pt: 'SAP (ERP)', en: 'SAP (ERP)', es: 'SAP (ERP)', fr: 'SAP (ERP)' },
    { pt: 'Canva (Design)', en: 'Canva (Design)', es: 'Canva (Diseño)', fr: 'Canva (Design)' },
    { pt: 'Gestão de Projetos', en: 'Project Management', es: 'Gestión de Proyectos', fr: 'Gestion de Projets' },
    { pt: 'Atendimento ao Cliente', en: 'Customer Service', es: 'Atención al Cliente', fr: 'Service Client' }
];

// ============================================
// 2. DADOS - EXPERIÊNCIA PROFISSIONAL (completo, do CV)
// ============================================
const experiencias = [
    {
        titulo: {
            pt: 'Estrategista Comercial & Consultora de Imagem Corporativa',
            en: 'Commercial Strategist & Corporate Image Consultant',
            es: 'Estratega Comercial y Consultora de Imagen Corporativa',
            fr: 'Stratège Commerciale & Consultante en Image Corporative'
        },
        empresa: 'Perfil Azul – Prestadora de Serviços',
        periodo: {
            pt: 'Outubro 2025 – Dezembro 2025',
            en: 'October 2025 – December 2025',
            es: 'Octubre 2025 – Diciembre 2025',
            fr: 'Octobre 2025 – Décembre 2025'
        },
        descricao: {
            pt: 'Desenvolvimento de estratégias comerciais para fortalecer a presença e competitividade da marca. Consultoria em imagem corporativa e posicionamento de mercado.',
            en: 'Developed commercial strategies to strengthen brand presence and competitiveness. Consulting on corporate image and market positioning.',
            es: 'Desarrollo de estrategias comerciales para fortalecer la presencia y competitividad de la marca. Consultoría en imagen corporativa y posicionamiento de mercado.',
            fr: 'Développement de stratégies commerciales pour renforcer la présence et la compétitivité de la marque. Conseil en image corporative et positionnement sur le marché.'
        }
    },
    {
        titulo: {
            pt: 'Comercial / Call Center',
            en: 'Sales / Call Center',
            es: 'Comercial / Call Center',
            fr: 'Commercial / Centre d\'Appels'
        },
        empresa: 'Fitness Up, Famalicão',
        periodo: {
            pt: 'Dezembro 2024 – Fevereiro 2025',
            en: 'December 2024 – February 2025',
            es: 'Diciembre 2024 – Febrero 2025',
            fr: 'Décembre 2024 – Février 2025'
        },
        descricao: {
            pt: 'Gestão e desenvolvimento do relacionamento com clientes. Atendimento direto, resolução de reclamações e apoio comercial.',
            en: 'Managed and developed customer relationships. Direct customer support, complaint resolution and sales assistance.',
            es: 'Gestión y desarrollo de la relación con los clientes. Atención directa, resolución de reclamaciones y apoyo comercial.',
            fr: 'Gestion et développement de la relation client. Accueil direct, résolution des réclamations et soutien commercial.'
        }
    },
    {
        titulo: {
            pt: 'Administradora e Editora Adjunta',
            en: 'Administrator & Deputy Editor',
            es: 'Administradora y Editora Adjunta',
            fr: 'Administratrice et Rédactrice Adjointe'
        },
        empresa: 'Di Cuore Eventos, Lda., Luanda',
        periodo: {
            pt: 'Dezembro 2022 – Julho 2024',
            en: 'December 2022 – July 2024',
            es: 'Diciembre 2022 – Julio 2024',
            fr: 'Décembre 2022 – Juillet 2024'
        },
        descricao: {
            pt: 'Suporte à gestão editorial e organização de conteúdos. Supervisão de equipa e coordenação de processos internos.',
            en: 'Supported editorial management and content organization. Supervised the team and coordinated internal processes.',
            es: 'Apoyo a la gestión editorial y organización de contenidos. Supervisión de equipo y coordinación de procesos internos.',
            fr: 'Soutien à la gestion éditoriale et à l\'organisation des contenus. Supervision de l\'équipe et coordination des processus internes.'
        }
    },
    {
        titulo: {
            pt: 'Directora do Departamento de Formação',
            en: 'Head of Training Department',
            es: 'Directora del Departamento de Formación',
            fr: 'Directrice du Département de Formation'
        },
        empresa: 'CEDE – Centro de Desenvolvimento Empresarial, Luanda',
        periodo: {
            pt: 'Fevereiro 2022 – Novembro 2022',
            en: 'February 2022 – November 2022',
            es: 'Febrero 2022 – Noviembre 2022',
            fr: 'Février 2022 – Novembre 2022'
        },
        descricao: {
            pt: 'Supervisão de equipa, gestão de programas e implementação de ações de capacitação.',
            en: 'Supervised the team, managed programs and implemented training initiatives.',
            es: 'Supervisión de equipo, gestión de programas e implementación de acciones de capacitación.',
            fr: 'Supervision de l\'équipe, gestion des programmes et mise en œuvre d\'actions de formation.'
        }
    },
    {
        titulo: {
            pt: 'Estágio de Contabilista Sénior',
            en: 'Senior Accountant Internship',
            es: 'Prácticas de Contable Sénior',
            fr: 'Stage de Comptable Senior'
        },
        empresa: 'Infocontabil Consultoria SU LDA, Luanda',
        periodo: {
            pt: 'Novembro 2021 – Janeiro 2022',
            en: 'November 2021 – January 2022',
            es: 'Noviembre 2021 – Enero 2022',
            fr: 'Novembre 2021 – Janvier 2022'
        },
        descricao: {
            pt: 'Análises financeiras e preparação de demonstrativos contábeis. Apoio na tomada de decisões estratégicas.',
            en: 'Conducted financial analyses and prepared accounting statements. Supported strategic decision-making.',
            es: 'Análisis financieros y preparación de estados contables. Apoyo en la toma de decisiones estratégicas.',
            fr: 'Analyses financières et préparation des états comptables. Soutien à la prise de décisions stratégiques.'
        }
    },
    {
        titulo: {
            pt: 'Assistente Administrativa',
            en: 'Administrative Assistant',
            es: 'Asistente Administrativa',
            fr: 'Assistante Administrative'
        },
        empresa: 'Miragos Empreendimentos LDA, Luanda',
        periodo: {
            pt: 'Junho 2018 – Novembro 2018',
            en: 'June 2018 – November 2018',
            es: 'Junio 2018 – Noviembre 2018',
            fr: 'Juin 2018 – Novembre 2018'
        },
        descricao: {
            pt: 'Atendimento ao cliente, vendas de serviços e gestão de comunicação. Apoio na organização de eventos e coordenação de stock.',
            en: 'Customer service, service sales and communications management. Supported event organization and stock coordination.',
            es: 'Atención al cliente, ventas de servicios y gestión de la comunicación. Apoyo en la organización de eventos y coordinación de stock.',
            fr: 'Service client, vente de services et gestion de la communication. Soutien à l\'organisation d\'événements et à la coordination des stocks.'
        }
    },
    {
        titulo: {
            pt: 'Assistente de Contabilidade',
            en: 'Accounting Assistant',
            es: 'Asistente de Contabilidad',
            fr: 'Assistante Comptable'
        },
        empresa: 'Escritório de Contabilidade e Consultoria Dr. Rui Manuel, Luanda',
        periodo: {
            pt: 'Março 2017 – Abril 2018',
            en: 'March 2017 – April 2018',
            es: 'Marzo 2017 – Abril 2018',
            fr: 'Mars 2017 – Avril 2018'
        },
        descricao: {
            pt: 'Organização financeira, apoio administrativo e controlo de documentos.',
            en: 'Handled financial organization, administrative support and document control.',
            es: 'Organización financiera, apoyo administrativo y control de documentos.',
            fr: 'Organisation financière, soutien administratif et contrôle des documents.'
        }
    }
];

// ============================================
// 3. DADOS - FORMAÇÃO ACADÉMICA
// ============================================
const formacao = [
    {
        curso: {
            pt: 'Técnico Especialista em Tecnologias e Programação de Sistemas de Informação',
            en: 'Specialist Technician in Information Systems Technology and Programming',
            es: 'Técnica Especialista en Tecnologías y Programación de Sistemas de Información',
            fr: 'Technicienne Spécialiste en Technologies et Programmation des Systèmes d\'Information'
        },
        instituicao: 'IEFP – Instituto de Emprego e Formação Profissional',
        periodo: '2025 – 2026'
    },
    {
        curso: {
            pt: 'Licenciatura em Finanças e Contabilidade',
            en: 'Bachelor\'s Degree in Finance and Accounting',
            es: 'Licenciatura en Finanzas y Contabilidad',
            fr: 'Licence en Finance et Comptabilité'
        },
        instituicao: 'Universidade Independente de Angola',
        periodo: '2018 – 2022'
    },
    {
        curso: {
            pt: 'Curso Técnico em Finanças',
            en: 'Technical Course in Finance',
            es: 'Curso Técnico en Finanzas',
            fr: 'Cours Technique en Finance'
        },
        instituicao: 'Instituto Médio de Administração e Gestão, Luanda',
        periodo: '2014 – 2017'
    }
];

// ============================================
// 4. DADOS - PROJETOS (com miniatura real de cada um)
// ============================================
const projetos = [
    {
        id: '007',
        titulo: { pt: 'Calculadora 4 Operações', en: '4-Operation Calculator', es: 'Calculadora de 4 Operaciones', fr: 'Calculatrice à 4 Opérations' },
        descricao: { pt: 'Calculadora com Soma, Subtração, Multiplicação e Divisão', en: 'Calculator with Addition, Subtraction, Multiplication and Division', es: 'Calculadora con Suma, Resta, Multiplicación y División', fr: 'Calculatrice avec Addition, Soustraction, Multiplication et Division' },
        tag: 'JavaScript',
        caminho: 'projetos/EXERCICIO007 - Calculadora/',
        imagem: 'imagens/projetos/007-calculadora.jpg'
    },
    {
        id: '008',
        titulo: { pt: 'Tabuada Interativa', en: 'Interactive Times Table', es: 'Tabla de Multiplicar Interactiva', fr: 'Table de Multiplication Interactive' },
        descricao: { pt: 'Tabuada do 1 ao 10 com design moderno', en: 'Times tables from 1 to 10 with a modern design', es: 'Tablas del 1 al 10 con diseño moderno', fr: 'Tables de 1 à 10 avec un design moderne' },
        tag: 'JavaScript',
        caminho: 'projetos/EXERCICIO008 - Tabuada/',
        imagem: 'imagens/projetos/008-tabuada.jpg'
    },
    {
        id: '009',
        titulo: { pt: 'Registo de Utilizador', en: 'User Registration', es: 'Registro de Usuario', fr: 'Inscription Utilisateur' },
        descricao: { pt: 'Formulário com Nome, Idade, Curso e Escola', en: 'Form with Name, Age, Course and School', es: 'Formulario con Nombre, Edad, Curso y Escuela', fr: 'Formulaire avec Nom, Âge, Cours et École' },
        tag: 'HTML',
        caminho: 'projetos/EXERCICIO009 - Registo de Utilizador/',
        imagem: 'imagens/projetos/009-registo-utilizador.jpg'
    },
    {
        id: '011',
        titulo: { pt: 'Curiosidades Escondidas', en: 'Hidden Facts', es: 'Curiosidades Escondidas', fr: 'Curiosités Cachées' },
        descricao: { pt: '3 curiosidades reveladas com clique', en: '3 fun facts revealed with a click', es: '3 curiosidades reveladas con un clic', fr: '3 anecdotes révélées d\'un clic' },
        tag: 'JavaScript',
        caminho: 'projetos/EXERCICIO011 - Curiosidades Escondidas/',
        imagem: 'imagens/projetos/011-curiosidades.jpg'
    },
    {
        id: '012',
        titulo: { pt: 'Imagem Escondida', en: 'Hidden Image', es: 'Imagen Escondida', fr: 'Image Cachée' },
        descricao: { pt: 'Revelação com hover sobre a área', en: 'Revealed on hover over the area', es: 'Se revela al pasar el cursor sobre el área', fr: 'Révélée au survol de la zone' },
        tag: 'CSS',
        caminho: 'projetos/EXERCICIO012 - Imagem Escondida/',
        imagem: 'imagens/projetos/012-imagem-escondida.jpg'
    },
    {
        id: '013',
        titulo: { pt: 'Calculadora com 4 Operações', en: 'Calculator with 4 Operations', es: 'Calculadora con 4 Operaciones', fr: 'Calculatrice à 4 Opérations' },
        descricao: { pt: 'Soma, Subtração, Multiplicação e Divisão', en: 'Addition, Subtraction, Multiplication and Division', es: 'Suma, Resta, Multiplicación y División', fr: 'Addition, Soustraction, Multiplication et Division' },
        tag: 'JavaScript',
        caminho: 'projetos/EXERCICIO013 - Calculadora com 4 Operações/',
        imagem: 'imagens/projetos/013-calculadora-4-operacoes.jpg'
    },
    {
        id: '014',
        titulo: { pt: 'Jogo da Adivinha', en: 'Guessing Game', es: 'Juego de Adivinanza', fr: 'Jeu de Devinette' },
        descricao: { pt: 'Adivinhe o número entre 1 e 50', en: 'Guess the number between 1 and 50', es: 'Adivina el número entre 1 y 50', fr: 'Devinez le nombre entre 1 et 50' },
        tag: 'JavaScript',
        caminho: 'projetos/EXERCICIO014 - Jogo da Adivinha/',
        imagem: 'imagens/projetos/014-jogo-adivinha.jpg'
    },
    {
        id: '017',
        titulo: { pt: 'Números Primos', en: 'Prime Numbers', es: 'Números Primos', fr: 'Nombres Premiers' },
        descricao: { pt: 'Encontra todos os primos até N', en: 'Finds all primes up to N', es: 'Encuentra todos los primos hasta N', fr: 'Trouve tous les nombres premiers jusqu\'à N' },
        tag: 'JavaScript',
        caminho: 'projetos/EXERCICIO017 - Números Primos/',
        imagem: 'imagens/projetos/017-numeros-primos.jpg'
    },
    {
        id: '018',
        titulo: { pt: 'Jogo da Adivinha com Toggle', en: 'Guessing Game with Toggle', es: 'Juego de Adivinanza con Toggle', fr: 'Jeu de Devinette avec Bascule' },
        descricao: { pt: 'Dark/Light Mode no jogo', en: 'Dark/Light Mode in the game', es: 'Modo Oscuro/Claro en el juego', fr: 'Mode Sombre/Clair dans le jeu' },
        tag: 'CSS',
        caminho: 'projetos/EXERCICIO018 - Jogo da Adivinha com Toggle/',
        imagem: 'imagens/projetos/018-jogo-adivinha-toggle.jpg'
    },
    {
        id: '019',
        titulo: { pt: 'Breakpoints', en: 'Breakpoints', es: 'Breakpoints', fr: 'Breakpoints' },
        descricao: { pt: 'Layout responsivo com Media Queries', en: 'Responsive layout with Media Queries', es: 'Diseño responsivo con Media Queries', fr: 'Mise en page responsive avec Media Queries' },
        tag: 'CSS',
        caminho: 'projetos/EXERCICIO019 - Breakpoints/',
        imagem: 'imagens/projetos/019-breakpoints.jpg'
    },
    {
        id: '020',
        titulo: { pt: 'Controlo de Gastos', en: 'Expense Tracker', es: 'Control de Gastos', fr: 'Suivi des Dépenses' },
        descricao: {
            pt: 'App full-stack com API FastAPI, PostgreSQL, categorias personalizáveis, gráficos e testes automáticos com deployment contínuo',
            en: 'Full-stack app with a FastAPI backend, PostgreSQL, custom categories, charts and automated tests with continuous deployment',
            es: 'App full-stack con API FastAPI, PostgreSQL, categorías personalizables, gráficos y pruebas automáticas con deployment continuo',
            fr: 'Application full-stack avec API FastAPI, PostgreSQL, catégories personnalisables, graphiques et tests automatisés avec déploiement continu'
        },
        tag: 'FastAPI',
        url: 'https://controlo-de-gastos.onrender.com',
        imagem: 'imagens/020-controlo-gastos.jpg'
    },
    {
        id: '021',
        titulo: {
            pt: 'Delivery Orientado a Eventos',
            en: 'Event-Driven Delivery',
            es: 'Delivery Orientado a Eventos',
            fr: 'Livraison Orientée Événements'
        },
        descricao: {
            pt: 'Simulação de delivery com arquitetura orientada a eventos, WebSocket em tempo real e testes automáticos',
            en: 'Delivery simulation with event-driven architecture, real-time WebSocket updates and automated tests',
            es: 'Simulación de delivery con arquitectura orientada a eventos, WebSocket en tiempo real y pruebas automáticas',
            fr: 'Simulation de livraison avec architecture événementielle, WebSocket en temps réel et tests automatisés'
        },
        tag: 'FastAPI',
        url: 'https://delivery-eventos.onrender.com',
        imagem: 'imagens/021-delivery-eventos.jpg'
    }
];

// ============================================
// 5. FUNÇÕES PARA CARREGAR DADOS
// ============================================

function carregarCompetencias() {
    const container = document.getElementById('tagsContainer');
    if (container) {
        container.innerHTML = '';
        competencias.forEach(tech => {
            const tag = document.createElement('span');
            tag.className = 'tag';
            tag.textContent = L(tech);
            container.appendChild(tag);
        });
    }

    const numero = document.getElementById('numeroTech');
    if (numero) numero.textContent = `+${competencias.length}`;

    const extra = document.getElementById('tagsExtraContainer');
    if (extra) {
        extra.innerHTML = '';
        competenciasComplementares.forEach(tech => {
            const tag = document.createElement('span');
            tag.className = 'tag tag-secundaria';
            tag.textContent = L(tech);
            extra.appendChild(tag);
        });
    }
}

function carregarExperiencias() {
    const container = document.getElementById('experienciaContainer');
    if (!container) return;

    container.innerHTML = '';
    experiencias.forEach(exp => {
        const div = document.createElement('div');
        div.className = 'experiencia-item reveal';
        div.innerHTML = `
            <div class="titulo">${L(exp.titulo)}</div>
            <div class="empresa">${exp.empresa}</div>
            <div class="periodo">${L(exp.periodo)}</div>
            <div class="descricao">${L(exp.descricao)}</div>
        `;
        container.appendChild(div);
    });
}

function carregarFormacao() {
    const container = document.getElementById('formacaoContainer');
    if (!container) return;

    container.innerHTML = '';
    formacao.forEach(item => {
        const div = document.createElement('div');
        div.className = 'formacao-item reveal';
        div.innerHTML = `
            <div class="titulo">${L(item.curso)}</div>
            <div class="empresa">${item.instituicao}</div>
            <div class="periodo">${item.periodo}</div>
        `;
        container.appendChild(div);
    });
}

function carregarProjetos() {
    const container = document.getElementById('projetosContainer');
    if (!container) return;

    container.innerHTML = '';
    projetos.forEach(proj => {
        const div = document.createElement('a');
        div.className = 'projeto-card reveal';
        div.href = proj.url || (proj.caminho + 'index.html');
        div.target = '_blank';
        div.innerHTML = `
            <div class="projeto-thumb">
                <img src="${proj.imagem}" alt="Captura do projeto ${L(proj.titulo)}" loading="lazy">
                <span class="projeto-thumb-tag">${proj.tag}</span>
            </div>
            <div class="projeto-body">
                <div class="projeto-numero">#${proj.id}</div>
                <h3>${L(proj.titulo)}</h3>
                <p>${L(proj.descricao)}</p>
                <span class="projeto-link">${t('projeto_ver')}</span>
            </div>
        `;
        container.appendChild(div);
    });
}

// ============================================
// 6. FUNÇÃO PARA FORMULÁRIO DE CONTACTO
// ============================================
// URL da tua Google Apps Script (envia diretamente para o teu Gmail)
const ENDPOINT_CONTACTO = 'https://script.google.com/macros/s/AKfycbzU4jTMhs1rTK_YFIyQLqb0Nx59TZDRwHQRbk-Dc3pU2vOEfGWT-slohAxcQnPinAk6/exec';

function configurarFormulario() {
    const form = document.getElementById('contactoForm');
    const feedback = document.getElementById('feedback');
    if (!form) return;

    const botao = form.querySelector('.btn-enviar');

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const nome = document.getElementById('nome').value.trim();
        const email = document.getElementById('email').value.trim();
        const assunto = document.getElementById('assunto').value.trim();
        const mensagem = document.getElementById('mensagem').value.trim();

        if (!nome || !email || !assunto || !mensagem) {
            feedback.className = 'feedback erro';
            feedback.textContent = t('form_erro_campos');
            return;
        }

        if (botao) {
            botao.disabled = true;
            botao.textContent = t('form_enviando');
        }

        const dados = new FormData();
        dados.append('nome', nome);
        dados.append('email', email);
        dados.append('assunto', assunto);
        dados.append('mensagem', mensagem);

        fetch(ENDPOINT_CONTACTO, {
            method: 'POST',
            body: dados,
            mode: 'no-cors' // o Apps Script não devolve CORS; assumimos sucesso se não houver erro de rede
        })
            .then(() => {
                feedback.className = 'feedback sucesso';
                feedback.textContent = t('form_sucesso').replace('{nome}', nome);
                form.reset();
            })
            .catch(() => {
                feedback.className = 'feedback erro';
                feedback.textContent = t('form_erro_envio');
            })
            .finally(() => {
                if (botao) {
                    botao.disabled = false;
                    botao.textContent = t('form_btn_enviar');
                }
            });
    });
}

// ============================================
// 7. ANIMAÇÃO AO FAZER SCROLL (reveal)
// ============================================
function iniciarScrollReveal() {
    const alvo = document.querySelectorAll('.reveal');
    if (!alvo.length) return;

    if (!('IntersectionObserver' in window)) {
        alvo.forEach(el => el.classList.add('visible'));
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

    alvo.forEach(el => observer.observe(el));
}

// ============================================
// 8. RECARREGAR CONTEÚDO DINÂMICO QUANDO O IDIOMA MUDA
// ============================================
function recarregarConteudoDinamico() {
    carregarCompetencias();
    carregarExperiencias();
    carregarFormacao();
    carregarProjetos();
    atualizarTextoTema();
    iniciarScrollReveal(); // reobserva os novos elementos gerados (experiência/formação/projetos)

    const botao = document.querySelector('#contactoForm .btn-enviar');
    if (botao && !botao.disabled) botao.textContent = t('form_btn_enviar');
}

document.addEventListener('idiomaAlterado', recarregarConteudoDinamico);

// ============================================
// 9. INICIALIZAR TUDO
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    initTheme();
    carregarCompetencias();
    carregarExperiencias();
    carregarFormacao();
    carregarProjetos();
    configurarFormulario();

    // marca as secções estáticas para a animação de scroll também
    document.querySelectorAll('section, .hero-cta, .contactos-grid, .contacto-form').forEach(el => {
        el.classList.add('reveal');
    });

    iniciarScrollReveal();

    document.body.classList.add('pagina-pronta');
});