// ============================================
// I18N.JS — Sistema de tradução (PT / EN / ES / FR)
// Motor partilhado por todas as páginas do site
// ============================================

const IDIOMAS_SUPORTADOS = ['pt', 'en', 'es', 'fr'];

const TRADUCOES = {
    pt: {
        nav_perfil: 'Perfil',
        nav_portfolio: 'Portfólio',
        nav_contacto: 'Contacto',

        theme_dark: 'Escuro',
        theme_light: 'Claro',

        hero_badge: 'Dev Web Júnior',
        db_titulo: 'Base de dados',
        db_sub: 'Consulta as tabelas do site ou escreve um comando (.help)',
        db_help: 'Comandos: .tables · .schema <tabela> · select * from <tabela>; · select count(*) from <tabela>; · select * from projetos where grupo=\'python\'; · clear',
        db_unk: 'Erro: comando não reconhecido. Escreve .help',
        db_notab: 'Erro: a tabela não existe. Usa .tables',
        db_dica: 'Dica: clica num separador ou escreve uma consulta e carrega em Enter',
        db_linhas: 'linhas',
        hero_subtitulo: 'Desenvolvedora Web Júnior (Full-Stack)',
        hero_p1: 'Com uma base sólida em <strong>Finanças e Contabilidade</strong> e uma transição estratégica para a área tecnológica, atualmente sou Técnica Especialista em <strong>Tecnologias e Programação de Sistemas de Informação</strong> no IEFP.',
        hero_p2: 'Especializo-me em <strong>Python, Automação de Processos (RPA), Análise de Dados e Desenvolvimento Web</strong>. A minha missão é aliar a experiência em gestão e negócios com competências técnicas para criar soluções inovadoras.',
        hero_btn_cv: 'Descarregar CV',
        hero_btn_projetos: 'Ver Projetos',
        stat_projetos: 'Projetos',
        stat_tecnologias: 'Tecnologias',
        stat_experiencias: 'Experiências',
        tags_extra_label: 'Também trago experiência em:',

        titulo_experiencia: 'Experiência Profissional',
        titulo_formacao: 'Formação Académica',

        footer_tagline: 'Tecnologias e Programação de Sistemas de Informação',
        voltar_inicio: 'Voltar ao Início',

        portfolio_titulo: 'MEUS PROJETOS',
        portfolio_subtitulo: 'Clique num projeto para abrir a demonstração. Cada cartão mostra as tecnologias usadas',
        portfolio_footer: 'Os exercícios de base nasceram na formação em <strong>Programação Web (Client-Side)</strong>; os restantes projetos fiz por iniciativa própria',
        projeto_ver: 'Ver Projeto',
        projeto_tecnologias: 'Tecnologias usadas',

        contacto_titulo: 'CONTACTO',
        contacto_subtitulo: 'Entre em contacto para oportunidades profissionais',
        form_label_nome: 'Nome Completo',
        form_label_email: 'Email',
        form_label_assunto: 'Assunto',
        form_label_mensagem: 'Mensagem',
        form_placeholder_nome: 'Pode ser o primeiro e o último',
        form_placeholder_email: 'Escreva o seu email',
        form_placeholder_assunto: 'Escreva o que pretende',
        form_placeholder_mensagem: 'Escreva a sua mensagem...',
        form_btn_enviar: 'Enviar Mensagem',
        form_erro_campos: 'Por favor, preencha todos os campos!',
        form_enviando: 'A enviar...',
        form_sucesso: 'Mensagem enviada com sucesso, {nome}! Entrarei em contacto em breve.',
        form_erro_envio: 'Não foi possível enviar agora. Tenta novamente ou escreve para elisamanueljob@gmail.com.',

        card_email: 'Email',
        card_telefone: 'Telefone',
        card_localizacao: 'Localização',
        card_linkedin: 'LinkedIn',
        card_github: 'GitHub',
        card_instagram: 'Instagram',
        card_ver_perfil: 'Ver Perfil',
        card_ver_repositorios: 'Ver Repositórios',

    },
    en: {
        nav_perfil: 'Profile',
        nav_portfolio: 'Portfolio',
        nav_contacto: 'Contact',

        theme_dark: 'Dark',
        theme_light: 'Light',

        hero_badge: 'Junior Web Dev',
        db_titulo: 'Database',
        db_sub: 'Query the site tables, or type a command (.help)',
        db_help: 'Commands: .tables · .schema <table> · select * from <table>; · select count(*) from <table>; · select * from projetos where grupo=\'python\'; · clear',
        db_unk: 'Error: unknown command. Type .help',
        db_notab: 'Error: no such table. Use .tables',
        db_dica: 'Tip: click a tab or type a query and press Enter',
        db_linhas: 'rows',
        hero_subtitulo: 'Junior Full-Stack Web Developer',
        hero_p1: 'With a solid background in <strong>Finance and Accounting</strong> and a strategic move into tech, I am currently a Specialist Technician in <strong>Information Systems Technology and Programming</strong> at IEFP (Portuguese Institute for Employment and Vocational Training).',
        hero_p2: 'I specialize in <strong>Python, Process Automation (RPA), Data Analysis and Web Development</strong>. My mission is to combine management and business experience with technical skills to build innovative solutions.',
        hero_btn_cv: 'Download CV',
        hero_btn_projetos: 'View Projects',
        stat_projetos: 'Projects',
        stat_tecnologias: 'Technologies',
        stat_experiencias: 'Roles',
        tags_extra_label: 'I also bring experience in:',

        titulo_experiencia: 'Professional Experience',
        titulo_formacao: 'Education',

        footer_tagline: 'Information Systems Technology and Programming',
        voltar_inicio: 'Back to Home',

        portfolio_titulo: 'MY PROJECTS',
        portfolio_subtitulo: 'Click a project to open the live demo. Each card shows the technologies used',
        portfolio_footer: 'The foundation exercises came out of the <strong>Web Programming (Client-Side)</strong> training; I built the other projects on my own initiative',
        projeto_ver: 'View Project',
        projeto_tecnologias: 'Technologies used',

        contacto_titulo: 'CONTACT',
        contacto_subtitulo: 'Get in touch about professional opportunities',
        form_label_nome: 'Full Name',
        form_label_email: 'Email',
        form_label_assunto: 'Subject',
        form_label_mensagem: 'Message',
        form_placeholder_nome: 'First and last name is fine',
        form_placeholder_email: 'Enter your email',
        form_placeholder_assunto: 'What is this about?',
        form_placeholder_mensagem: 'Write your message...',
        form_btn_enviar: 'Send Message',
        form_erro_campos: 'Please fill in all fields!',
        form_enviando: 'Sending...',
        form_sucesso: 'Message sent successfully, {nome}! I will get back to you soon.',
        form_erro_envio: "Couldn't send it right now. Please try again or email elisamanueljob@gmail.com.",

        card_email: 'Email',
        card_telefone: 'Phone',
        card_localizacao: 'Location',
        card_linkedin: 'LinkedIn',
        card_github: 'GitHub',
        card_instagram: 'Instagram',
        card_ver_perfil: 'View Profile',
        card_ver_repositorios: 'View Repositories',

    },
    es: {
        nav_perfil: 'Perfil',
        nav_portfolio: 'Portafolio',
        nav_contacto: 'Contacto',

        theme_dark: 'Oscuro',
        theme_light: 'Claro',

        hero_badge: 'Dev Web Júnior',
        db_titulo: 'Base de datos',
        db_sub: 'Consulta las tablas del sitio o escribe un comando (.help)',
        db_help: 'Comandos: .tables · .schema <tabla> · select * from <tabla>; · select count(*) from <tabla>; · select * from projetos where grupo=\'python\'; · clear',
        db_unk: 'Error: comando no reconocido. Escribe .help',
        db_notab: 'Error: la tabla no existe. Usa .tables',
        db_dica: 'Consejo: haz clic en una pestaña o escribe una consulta y pulsa Enter',
        db_linhas: 'filas',
        hero_subtitulo: 'Desarrolladora Web Júnior (Full-Stack)',
        hero_p1: 'Con una base sólida en <strong>Finanzas y Contabilidad</strong> y una transición estratégica hacia el área tecnológica, actualmente soy Técnica Especialista en <strong>Tecnologías y Programación de Sistemas de Información</strong> en el IEFP (Instituto portugués de Empleo y Formación Profesional).',
        hero_p2: 'Me especializo en <strong>Python, Automatización de Procesos (RPA), Análisis de Datos y Desarrollo Web</strong>. Mi misión es combinar la experiencia en gestión y negocios con competencias técnicas para crear soluciones innovadoras.',
        hero_btn_cv: 'Descargar CV',
        hero_btn_projetos: 'Ver Proyectos',
        stat_projetos: 'Proyectos',
        stat_tecnologias: 'Tecnologías',
        stat_experiencias: 'Experiencias',
        tags_extra_label: 'También aporto experiencia en:',

        titulo_experiencia: 'Experiencia Profesional',
        titulo_formacao: 'Formación Académica',

        footer_tagline: 'Tecnologías y Programación de Sistemas de Información',
        voltar_inicio: 'Volver al Inicio',

        portfolio_titulo: 'MIS PROYECTOS',
        portfolio_subtitulo: 'Haz clic en un proyecto para abrir la demo. Cada tarjeta muestra las tecnologías usadas',
        portfolio_footer: 'Los ejercicios de base nacieron en la formación de <strong>Programación Web (Client-Side)</strong>; los demás proyectos los hice por iniciativa propia',
        projeto_ver: 'Ver Proyecto',
        projeto_tecnologias: 'Tecnologías usadas',

        contacto_titulo: 'CONTACTO',
        contacto_subtitulo: 'Ponte en contacto para oportunidades profesionales',
        form_label_nome: 'Nombre Completo',
        form_label_email: 'Correo Electrónico',
        form_label_assunto: 'Asunto',
        form_label_mensagem: 'Mensaje',
        form_placeholder_nome: 'Puede ser el nombre y el apellido',
        form_placeholder_email: 'Escribe tu correo electrónico',
        form_placeholder_assunto: 'Escribe el motivo',
        form_placeholder_mensagem: 'Escribe tu mensaje...',
        form_btn_enviar: 'Enviar Mensaje',
        form_erro_campos: '¡Por favor, completa todos los campos!',
        form_enviando: 'Enviando...',
        form_sucesso: '¡Mensaje enviado con éxito, {nome}! Me pondré en contacto pronto.',
        form_erro_envio: 'No se pudo enviar en este momento. Inténtalo de nuevo o escribe a elisamanueljob@gmail.com.',

        card_email: 'Correo',
        card_telefone: 'Teléfono',
        card_localizacao: 'Ubicación',
        card_linkedin: 'LinkedIn',
        card_github: 'GitHub',
        card_instagram: 'Instagram',
        card_ver_perfil: 'Ver Perfil',
        card_ver_repositorios: 'Ver Repositorios',

    },
    fr: {
        nav_perfil: 'Profil',
        nav_portfolio: 'Portfolio',
        nav_contacto: 'Contact',

        theme_dark: 'Sombre',
        theme_light: 'Clair',

        hero_badge: 'Dev Web Junior',
        db_titulo: 'Base de données',
        db_sub: 'Interrogez les tables du site ou saisissez une commande (.help)',
        db_help: 'Commandes : .tables · .schema <table> · select * from <table>; · select count(*) from <table>; · select * from projetos where grupo=\'python\'; · clear',
        db_unk: 'Erreur : commande inconnue. Tapez .help',
        db_notab: 'Erreur : la table n\'existe pas. Utilisez .tables',
        db_dica: 'Astuce : cliquez sur un onglet ou saisissez une requête puis Entrée',
        db_linhas: 'lignes',
        hero_subtitulo: 'Développeuse Web Junior (Full-Stack)',
        hero_p1: 'Avec une solide base en <strong>Finance et Comptabilité</strong> et une transition stratégique vers le domaine technologique, je suis actuellement Technicienne Spécialiste en <strong>Technologies et Programmation des Systèmes d\'Information</strong> à l\'IEFP (Institut portugais de l\'Emploi et de la Formation Professionnelle).',
        hero_p2: 'Je me spécialise en <strong>Python, Automatisation des Processus (RPA), Analyse de Données et Développement Web</strong>. Ma mission est d\'allier l\'expérience en gestion et en affaires à des compétences techniques pour créer des solutions innovantes.',
        hero_btn_cv: 'Télécharger le CV',
        hero_btn_projetos: 'Voir les Projets',
        stat_projetos: 'Projets',
        stat_tecnologias: 'Technologies',
        stat_experiencias: 'Expériences',
        tags_extra_label: "J'apporte aussi de l'expérience en :",

        titulo_experiencia: 'Expérience Professionnelle',
        titulo_formacao: 'Formation Académique',

        footer_tagline: 'Technologies et Programmation des Systèmes d\'Information',
        voltar_inicio: "Retour à l'Accueil",

        portfolio_titulo: 'MES PROJETS',
        portfolio_subtitulo: 'Cliquez sur un projet pour ouvrir la démo. Chaque carte montre les technologies utilisées',
        portfolio_footer: 'Les exercices de base sont nés de la formation en <strong>Programmation Web (Client-Side)</strong> ; j\'ai réalisé les autres projets de ma propre initiative',
        projeto_ver: 'Voir le Projet',
        projeto_tecnologias: 'Technologies utilisées',

        contacto_titulo: 'CONTACT',
        contacto_subtitulo: 'Contactez-moi pour des opportunités professionnelles',
        form_label_nome: 'Nom Complet',
        form_label_email: 'E-mail',
        form_label_assunto: 'Sujet',
        form_label_mensagem: 'Message',
        form_placeholder_nome: 'Prénom et nom suffisent',
        form_placeholder_email: 'Entrez votre e-mail',
        form_placeholder_assunto: 'Quel est l\'objet du message ?',
        form_placeholder_mensagem: 'Écrivez votre message...',
        form_btn_enviar: 'Envoyer le Message',
        form_erro_campos: 'Merci de remplir tous les champs !',
        form_enviando: 'Envoi en cours...',
        form_sucesso: 'Message envoyé avec succès, {nome} ! Je vous recontacterai bientôt.',
        form_erro_envio: "Impossible d'envoyer pour le moment. Réessayez ou écrivez à elisamanueljob@gmail.com.",

        card_email: 'E-mail',
        card_telefone: 'Téléphone',
        card_localizacao: 'Localisation',
        card_linkedin: 'LinkedIn',
        card_github: 'GitHub',
        card_instagram: 'Instagram',
        card_ver_perfil: 'Voir le Profil',
        card_ver_repositorios: 'Voir les Dépôts',

    }
};

/* ---------- Título e descrição de cada página (para SEO/partilha) ---------- */
const PAGINAS = {
    index: {
        titulo: {
            pt: 'Elisama Manuel | Portfólio Dev',
            en: 'Elisama Manuel | Developer Portfolio',
            es: 'Elisama Manuel | Portafolio de Desarrolladora',
            fr: 'Elisama Manuel | Portfolio Développeuse'
        },
        descricao: {
            pt: 'Elisama Manuel: Desenvolvedora em formação, especializada em Python, automação (RPA), análise de dados e desenvolvimento web. Veja os meus projetos e percurso.',
            en: 'Elisama Manuel: Developer in training, specializing in Python, process automation (RPA), data analysis and web development. See my projects and career path.',
            es: 'Elisama Manuel: Desarrolladora en formación, especializada en Python, automatización de procesos (RPA), análisis de datos y desarrollo web. Descubre mis proyectos y trayectoria.',
            fr: "Elisama Manuel: Développeuse en formation, spécialisée en Python, automatisation des processus (RPA), analyse de données et développement web. Découvrez mes projets et mon parcours."
        }
    },
    portfolio: {
        titulo: {
            pt: 'Elisama | Portfólio',
            en: 'Elisama | Portfolio',
            es: 'Elisama | Portafolio',
            fr: 'Elisama | Portfolio'
        },
        descricao: {
            pt: 'Projetos de Elisama Manuel: aplicações completas em Python (FastAPI, Dash), sistemas e jogos web em PHP e JavaScript, templates para negócios e exercícios de base.',
            en: 'Projects by Elisama Manuel: complete Python applications (FastAPI, Dash), web systems and games in PHP and JavaScript, business templates and foundation exercises.',
            es: 'Proyectos de Elisama Manuel: aplicaciones completas en Python (FastAPI, Dash), sistemas y juegos web en PHP y JavaScript, plantillas para negocios y ejercicios de base.',
            fr: "Projets d'Elisama Manuel : applications complètes en Python (FastAPI, Dash), systèmes et jeux web en PHP et JavaScript, modèles pour entreprises et exercices de base."
        }
    },
    contacto: {
        titulo: {
            pt: 'Elisama | Contacto',
            en: 'Elisama | Contact',
            es: 'Elisama | Contacto',
            fr: 'Elisama | Contact'
        },
        descricao: {
            pt: 'Entre em contacto com Elisama Manuel para oportunidades profissionais em desenvolvimento web, Python e automação.',
            en: 'Get in touch with Elisama Manuel about professional opportunities in web development, Python and automation.',
            es: 'Ponte en contacto con Elisama Manuel para oportunidades profesionales en desarrollo web, Python y automatización.',
            fr: "Contactez Elisama Manuel pour des opportunités professionnelles en développement web, Python et automatisation."
        }
    }
};

function atualizarMetaPagina() {
    const pagina = document.body.dataset.pagina;
    const dados = PAGINAS[pagina];
    if (!dados) return;

    const titulo = L(dados.titulo);
    const descricao = L(dados.descricao);

    document.title = titulo;

    const metaDesc = document.querySelector('meta[name="description"]');
    if (metaDesc) metaDesc.setAttribute('content', descricao);

    const ogTitulo = document.querySelector('meta[property="og:title"]');
    if (ogTitulo) ogTitulo.setAttribute('content', titulo);

    const ogDescricao = document.querySelector('meta[property="og:description"]');
    if (ogDescricao) ogDescricao.setAttribute('content', descricao);
}

/* ---------- Estado ---------- */
let idiomaAtual = detetarIdioma();

function detetarIdioma() {
    // 1) escolha feita à mão numa visita anterior
    try {
        const guardado = localStorage.getItem('idioma');
        if (guardado && IDIOMAS_SUPORTADOS.includes(guardado)) return guardado;
    } catch (e) { /* armazenamento bloqueado: segue para a deteção */ }

    // 2) idiomas preferidos do navegador, por ordem (ex.: ['fr-CA', 'en-US'])
    const preferidos = (navigator.languages && navigator.languages.length)
        ? navigator.languages
        : [navigator.language || navigator.userLanguage || 'pt'];
    for (const lingua of preferidos) {
        const codigo = String(lingua).slice(0, 2).toLowerCase();
        if (IDIOMAS_SUPORTADOS.includes(codigo)) return codigo;
    }

    // 3) nenhum dos quatro: inglês
    return 'en';
}

/* Devolve a tradução de uma chave no idioma atual (com fallback para PT) */
function t(chave) {
    return (TRADUCOES[idiomaAtual] && TRADUCOES[idiomaAtual][chave]) || TRADUCOES.pt[chave] || chave;
}

/* Escolhe o valor certo de um objeto multi-idioma tipo { pt:'', en:'', es:'', fr:'' } */
function L(obj) {
    if (!obj) return '';
    return obj[idiomaAtual] || obj.pt || '';
}

function aplicarTraducoes() {
    document.documentElement.lang = idiomaAtual;

    document.querySelectorAll('[data-i18n]').forEach(el => {
        const chave = el.getAttribute('data-i18n');
        el.innerHTML = t(chave);
    });

    document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
        el.placeholder = t(el.getAttribute('data-i18n-placeholder'));
    });

    atualizarMetaPagina();
    atualizarBotoesIdioma();
    document.dispatchEvent(new CustomEvent('idiomaAlterado', { detail: idiomaAtual }));
}

function mudarIdioma(novoIdioma) {
    if (!IDIOMAS_SUPORTADOS.includes(novoIdioma) || novoIdioma === idiomaAtual) return;
    idiomaAtual = novoIdioma;
    localStorage.setItem('idioma', novoIdioma);
    aplicarTraducoes();
}

function atualizarBotoesIdioma() {
    document.querySelectorAll('.lang-btn').forEach(btn => {
        btn.classList.toggle('ativo', btn.dataset.lang === idiomaAtual);
    });
}

function configurarSeletorIdioma() {
    document.querySelectorAll('.lang-btn').forEach(btn => {
        btn.addEventListener('click', () => mudarIdioma(btn.dataset.lang));
    });
}

document.addEventListener('DOMContentLoaded', () => {
    configurarSeletorIdioma();
    aplicarTraducoes();
});