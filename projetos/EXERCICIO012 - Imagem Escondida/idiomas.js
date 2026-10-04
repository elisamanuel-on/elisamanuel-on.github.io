/* Idiomas do jogo: português, inglês, espanhol e francês.
   Cada texto tem as quatro traduções, pela ordem pt, en, es, fr. %1, %2… são substituídos pelos argumentos.
   O idioma escolhe-se por esta ordem: guardado no navegador, idioma do navegador (se for um dos quatro), inglês. */
(function () {
    'use strict';

    var VERSAO = '2.0.0';
    var SUPORTADOS = ['pt', 'en', 'es', 'fr'];
    var NOMES = { pt: 'Português', en: 'English', es: 'Español', fr: 'Français' };
    var LOCALES = { pt: 'pt-PT', en: 'en-GB', es: 'es-ES', fr: 'fr-FR' };
    var CHAVE = 'imagem_escondida_idioma';

    var TEXTOS = {
        app_titulo: ['Imagem Escondida', 'Hidden Image', 'Imagen Escondida', 'Image Cachée'],
        app_sub: [
            'Jogo de geografia: continentes, países, capitais e curiosidades',
            'Geography game: continents, countries, capitals and curiosities',
            'Juego de geografía: continentes, países, capitales y curiosidades',
            'Jeu de géographie : continents, pays, capitales et curiosités'
        ],
        idioma: ['Idioma', 'Language', 'Idioma', 'Langue'],
        quem_joga: ['Quem vai jogar?', 'Who is playing?', '¿Quién va a jugar?', 'Qui va jouer ?'],
        dica_mapa: ['Podes arrastar e aproximar o mapa.', 'You can drag and zoom the map.', 'Puedes arrastrar y acercar el mapa.', 'Tu peux déplacer et zoomer la carte.'],
        dica_escrever: ['Escreve e carrega em Enter.', 'Type and press Enter.', 'Escribe y pulsa Enter.', 'Écris et appuie sur Entrée.'],
        aria_bandeira: ['Bandeira %1', 'Flag %1', 'Bandera %1', 'Drapeau %1'],
        sair_confirmar: ['Sair mesmo?', 'Really leave?', '¿Salir de verdad?', 'Vraiment quitter ?'],
        estrelas_de: ['%1 de 3 estrelas', '%1 of 3 stars', '%1 de 3 estrellas', '%1 étoiles sur 3'],
        jogar_nivel: ['Jogar o nível %1', 'Play level %1', 'Jugar el nivel %1', 'Jouer le niveau %1'],
        // ----- jogador -----
        jogador: ['Jogador', 'Player', 'Jugador', 'Joueur'],
        mudar_jogador: ['Jogadores', 'Players', 'Jugadores', 'Joueurs'],
        novo_jogador: ['Novo jogador', 'New player', 'Nuevo jugador', 'Nouveau joueur'],
        nome_jogador: ['Nome do jogador', 'Player name', 'Nombre del jugador', 'Nom du joueur'],
        escolher_avatar: ['Escolhe um avatar', 'Choose an avatar', 'Elige un avatar', 'Choisis un avatar'],
        criar: ['Criar jogador', 'Create player', 'Crear jugador', 'Créer le joueur'],
        escolher: ['Escolher', 'Select', 'Elegir', 'Choisir'],
        em_uso: ['A jogar', 'Playing', 'Jugando', 'En jeu'],
        apagar: ['Apagar', 'Delete', 'Borrar', 'Supprimer'],
        apagar_confirmar: ['Apagar mesmo?', 'Really delete?', '¿Borrar de verdad?', 'Vraiment supprimer ?'],
        cancelar: ['Cancelar', 'Cancel', 'Cancelar', 'Annuler'],
        fechar: ['Fechar', 'Close', 'Cerrar', 'Fermer'],
        ranking: ['Ranking neste navegador', 'Ranking on this browser', 'Ranking en este navegador', 'Classement sur ce navigateur'],
        ranking_vazio: ['Ainda ninguém tem pontos. Joga o nível 1!', 'Nobody has points yet. Play level 1!', 'Todavía nadie tiene puntos. ¡Juega el nivel 1!', 'Personne n’a encore de points. Joue le niveau 1 !'],
        jogadores_info: [
            'Os jogadores e os recordes ficam guardados só neste navegador.',
            'Players and records are stored only in this browser.',
            'Los jugadores y los récords se guardan solo en este navegador.',
            'Les joueurs et les records sont enregistrés uniquement dans ce navigateur.'
        ],
        perfil_cheio: ['Já tens o máximo de 8 jogadores.', 'You already have the maximum of 8 players.', 'Ya tienes el máximo de 8 jugadores.', 'Tu as déjà le maximum de 8 joueurs.'],
        nome_curto: ['Escreve um nome com 1 a 16 letras.', 'Type a name with 1 to 16 letters.', 'Escribe un nombre de 1 a 16 letras.', 'Écris un nom de 1 à 16 lettres.'],
        jogador_padrao: ['Explorador', 'Explorer', 'Explorador', 'Explorateur'],
        // ----- níveis -----
        niveis: ['Níveis', 'Levels', 'Niveles', 'Niveaux'],
        nivel: ['Nível', 'Level', 'Nivel', 'Niveau'],
        nivel1_nome: ['Continentes', 'Continents', 'Continentes', 'Continents'],
        nivel2_nome: ['Países e bandeiras', 'Countries and flags', 'Países y banderas', 'Pays et drapeaux'],
        nivel3_nome: ['Capitais e curiosidades', 'Capitals and curiosities', 'Capitales y curiosidades', 'Capitales et curiosités'],
        nivel1_desc: [
            'Encontra os continentes no mapa e reconhece onde ficam os países.',
            'Find the continents on the map and tell where countries are.',
            'Encuentra los continentes en el mapa y reconoce dónde están los países.',
            'Trouve les continents sur la carte et reconnais où se trouvent les pays.'
        ],
        nivel2_desc: [
            'Bandeiras e países: escolhe, escreve e clica no mapa.',
            'Flags and countries: choose, type and click on the map.',
            'Banderas y países: elige, escribe y haz clic en el mapa.',
            'Drapeaux et pays : choisis, écris et clique sur la carte.'
        ],
        nivel3_desc: [
            'Capitais, línguas, moedas e curiosidades reais da Wikipédia.',
            'Capitals, languages, currencies and real Wikipedia curiosities.',
            'Capitales, lenguas, monedas y curiosidades reales de Wikipedia.',
            'Capitales, langues, monnaies et curiosités réelles de Wikipédia.'
        ],
        melhor: ['Melhor', 'Best', 'Mejor', 'Meilleur'],
        jogar: ['Jogar', 'Play', 'Jugar', 'Jouer'],
        bloqueado: ['Bloqueado', 'Locked', 'Bloqueado', 'Verrouillé'],
        desbloqueia: ['Completa o nível %1 para desbloquear', 'Complete level %1 to unlock', 'Completa el nivel %1 para desbloquear', 'Termine le niveau %1 pour débloquer'],
        como_jogar: ['Como se joga', 'How to play', 'Cómo se juega', 'Comment jouer'],
        regras: [
            '10 perguntas por nível e 3 vidas. Cada pergunta tem um cronómetro: quanto mais depressa acertas, mais pontos ganhas. A imagem começa desfocada e fica nítida com o passar do tempo. Se erras ou o tempo acaba, perdes uma vida.',
            '10 questions per level and 3 lives. Every question has a timer: the faster you get it right, the more points you earn. The image starts blurred and gets sharper as time passes. If you get it wrong or time runs out, you lose a life.',
            '10 preguntas por nivel y 3 vidas. Cada pregunta tiene un cronómetro: cuanto más rápido aciertas, más puntos ganas. La imagen empieza desenfocada y se vuelve nítida con el tiempo. Si fallas o se acaba el tiempo, pierdes una vida.',
            '10 questions par niveau et 3 vies. Chaque question a un chronomètre : plus tu réponds vite, plus tu gagnes de points. L’image commence floue et devient nette avec le temps. Si tu te trompes ou si le temps est écoulé, tu perds une vie.'
        ],
        // ----- jogo -----
        pergunta_n: ['Pergunta %1 de %2', 'Question %1 of %2', 'Pregunta %1 de %2', 'Question %1 sur %2'],
        vidas: ['Vidas', 'Lives', 'Vidas', 'Vies'],
        pontos: ['Pontos', 'Points', 'Puntos', 'Points'],
        sequencia: ['Sequência', 'Streak', 'Racha', 'Série'],
        tempo: ['Tempo', 'Time', 'Tiempo', 'Temps'],
        segundos: ['s', 's', 's', 's'],
        sair: ['Sair do nível', 'Leave level', 'Salir del nivel', 'Quitter le niveau'],
        q_mapa_continente: ['Clica num país de %1', 'Click a country in %1', 'Haz clic en un país de %1', 'Clique sur un pays du continent : %1'],
        q_bandeira_continente: ['De que continente é esta bandeira?', 'Which continent is this flag from?', '¿De qué continente es esta bandera?', 'De quel continent vient ce drapeau ?'],
        q_pais_continente: ['Em que continente fica %1?', 'Which continent is %1 in?', '¿En qué continente está %1?', 'Sur quel continent se trouve %1 ?'],
        q_escrever_continente: ['Escreve o continente de %1', 'Type the continent of %1', 'Escribe el continente de %1', 'Écris le continent de %1'],
        q_bandeira_pais: ['A que país pertence esta bandeira?', 'Which country does this flag belong to?', '¿A qué país pertenece esta bandera?', 'À quel pays appartient ce drapeau ?'],
        q_mapa_pais: ['Clica no mapa: onde fica %1?', 'Click on the map: where is %1?', 'Haz clic en el mapa: ¿dónde está %1?', 'Clique sur la carte : où se trouve %1 ?'],
        q_escrever_bandeira: ['Escreve o nome do país desta bandeira', 'Type the name of the country with this flag', 'Escribe el nombre del país de esta bandera', 'Écris le nom du pays de ce drapeau'],
        q_nome_bandeira: ['Qual é a bandeira de %1?', 'Which is the flag of %1?', '¿Cuál es la bandera de %1?', 'Quel est le drapeau de %1 ?'],
        q_capital_escolha: ['Qual é a capital de %1?', 'What is the capital of %1?', '¿Cuál es la capital de %1?', 'Quelle est la capitale de %1 ?'],
        q_capital_escrever: ['Escreve a capital de %1', 'Type the capital of %1', 'Escribe la capital de %1', 'Écris la capitale de %1'],
        q_mapa_capital: ['Clica no país cuja capital é %1', 'Click the country whose capital is %1', 'Haz clic en el país cuya capital es %1', 'Clique sur le pays dont la capitale est %1'],
        q_curiosidade_pais: ['De que país estamos a falar?', 'Which country are we talking about?', '¿De qué país estamos hablando?', 'De quel pays parle-t-on ?'],
        q_curiosidade_cidade: ['De que país é esta cidade?', 'Which country is this city in?', '¿De qué país es esta ciudad?', 'Dans quel pays se trouve cette ville ?'],
        q_lingua: ['Que língua se fala oficialmente em %1?', 'Which language is officially spoken in %1?', '¿Qué lengua se habla oficialmente en %1?', 'Quelle langue est parlée officiellement en %1 ?'],
        q_moeda: ['Qual é a moeda de %1?', 'What is the currency of %1?', '¿Cuál es la moneda de %1?', 'Quelle est la monnaie de %1 ?'],
        escrever_ph: ['Escreve a resposta', 'Type your answer', 'Escribe la respuesta', 'Écris ta réponse'],
        responder: ['Responder', 'Answer', 'Responder', 'Répondre'],
        seguinte: ['Seguinte', 'Next', 'Siguiente', 'Suivant'],
        ver_resultado: ['Ver resultado', 'See result', 'Ver resultado', 'Voir le résultat'],
        a_carregar: ['A carregar a pergunta…', 'Loading the question…', 'Cargando la pregunta…', 'Chargement de la question…'],
        dica_teclas: ['Dica: usa as teclas 1 a 4 e Enter.', 'Tip: use keys 1 to 4 and Enter.', 'Pista: usa las teclas 1 a 4 y Enter.', 'Astuce : utilise les touches 1 à 4 et Entrée.'],
        // ----- resposta -----
        certo: ['Certo! +%1 pontos', 'Correct! +%1 points', '¡Correcto! +%1 puntos', 'Bravo ! +%1 points'],
        errado: ['Errado. Perdeste uma vida.', 'Wrong. You lost a life.', 'Incorrecto. Has perdido una vida.', 'Faux. Tu as perdu une vie.'],
        tempo_esgotado: ['Tempo esgotado. Perdeste uma vida.', 'Time is up. You lost a life.', 'Tiempo agotado. Has perdido una vida.', 'Temps écoulé. Tu as perdu une vie.'],
        resposta_certa: ['Resposta certa: %1', 'Right answer: %1', 'Respuesta correcta: %1', 'Bonne réponse : %1'],
        clicaste_em: ['Clicaste em %1.', 'You clicked %1.', 'Hiciste clic en %1.', 'Tu as cliqué sur %1.'],
        territorio: ['Esse território não conta. Tenta noutro país.', 'That territory does not count. Try another country.', 'Ese territorio no cuenta. Prueba con otro país.', 'Ce territoire ne compte pas. Essaie un autre pays.'],
        // ----- identidade do país -----
        ficha_titulo: ['Identidade do país', 'Country identity', 'Identidad del país', 'Identité du pays'],
        f_capital: ['Capital', 'Capital', 'Capital', 'Capitale'],
        f_continente: ['Continente', 'Continent', 'Continente', 'Continent'],
        f_linguas: ['Línguas', 'Languages', 'Lenguas', 'Langues'],
        f_moeda: ['Moeda', 'Currency', 'Moneda', 'Monnaie'],
        f_area: ['Área', 'Area', 'Área', 'Superficie'],
        f_dominio: ['Domínio', 'Domain', 'Dominio', 'Domaine'],
        f_indicativo: ['Indicativo', 'Calling code', 'Prefijo', 'Indicatif'],
        f_fronteiras: ['Fronteiras', 'Borders', 'Fronteras', 'Frontières'],
        f_sem_fronteiras: ['Sem fronteiras terrestres', 'No land borders', 'Sin fronteras terrestres', 'Sans frontières terrestres'],
        f_interior: ['Sem acesso ao mar', 'Landlocked', 'Sin acceso al mar', 'Sans accès à la mer'],
        km2: ['km²', 'km²', 'km²', 'km²'],
        sabias: ['Sabias que…', 'Did you know…', '¿Sabías que…?', 'Le savais-tu ?'],
        a_procurar: ['A procurar na Wikipédia…', 'Searching Wikipedia…', 'Buscando en Wikipedia…', 'Recherche sur Wikipédia…'],
        sem_curiosidade: [
            'A Wikipédia não respondeu agora. Podes continuar o jogo.',
            'Wikipedia did not answer right now. You can keep playing.',
            'Wikipedia no respondió ahora. Puedes seguir jugando.',
            'Wikipédia n’a pas répondu pour le moment. Tu peux continuer à jouer.'
        ],
        ver_google: ['Ver no Google Maps', 'Open in Google Maps', 'Ver en Google Maps', 'Voir sur Google Maps'],
        ver_wiki: ['Ler na Wikipédia', 'Read on Wikipedia', 'Leer en Wikipedia', 'Lire sur Wikipédia'],
        mapa_ctx: ['Mapa © colaboradores do OpenStreetMap', 'Map © OpenStreetMap contributors', 'Mapa © colaboradores de OpenStreetMap', 'Carte © contributeurs OpenStreetMap'],
        // ----- fim -----
        nivel_completo: ['Nível completo!', 'Level complete!', '¡Nivel completado!', 'Niveau terminé !'],
        fim_jogo: ['Fim de jogo', 'Game over', 'Fin del juego', 'Partie terminée'],
        sem_vidas: ['Ficaste sem vidas. Tenta outra vez, desta vez com mais calma.', 'You ran out of lives. Try again, a bit more calmly this time.', 'Te quedaste sin vidas. Inténtalo otra vez, con más calma.', 'Tu n’as plus de vies. Réessaie, un peu plus calmement.'],
        acertos: ['%1 de %2 respostas certas', '%1 of %2 answers correct', '%1 de %2 respuestas correctas', '%1 réponses justes sur %2'],
        tempo_medio: ['Tempo médio por resposta: %1 s', 'Average time per answer: %1 s', 'Tiempo medio por respuesta: %1 s', 'Temps moyen par réponse : %1 s'],
        bonus_vidas: ['Bónus de vidas: +%1', 'Lives bonus: +%1', 'Bonus de vidas: +%1', 'Bonus de vies : +%1'],
        pontos_nivel: ['Pontos neste nível', 'Points this level', 'Puntos en este nivel', 'Points sur ce niveau'],
        novo_recorde: ['Novo recorde!', 'New record!', '¡Nuevo récord!', 'Nouveau record !'],
        repetir: ['Jogar outra vez', 'Play again', 'Jugar otra vez', 'Rejouer'],
        proximo: ['Próximo nível', 'Next level', 'Siguiente nivel', 'Niveau suivant'],
        menu: ['Voltar ao menu', 'Back to menu', 'Volver al menú', 'Retour au menu'],
        todos_completos: ['Completaste os 3 níveis. És um(a) verdadeiro(a) explorador(a)!', 'You completed all 3 levels. A true explorer!', 'Completaste los 3 niveles. ¡Un verdadero explorador!', 'Tu as terminé les 3 niveaux. Un vrai explorateur !'],
        total: ['Total', 'Total', 'Total', 'Total'],
        jogos: ['Jogos', 'Games', 'Partidas', 'Parties'],
        // ----- rodapé -----
        versao: ['Versão %1', 'Version %1', 'Versión %1', 'Version %1'],
        fontes: [
            'Fontes: world-countries, Natural Earth, country-flag-icons, OpenStreetMap e Wikipédia.',
            'Sources: world-countries, Natural Earth, country-flag-icons, OpenStreetMap and Wikipedia.',
            'Fuentes: world-countries, Natural Earth, country-flag-icons, OpenStreetMap y Wikipedia.',
            'Sources : world-countries, Natural Earth, country-flag-icons, OpenStreetMap et Wikipédia.'
        ],
        carregar_erro: ['Não foi possível carregar o jogo. Recarrega a página.', 'The game could not be loaded. Reload the page.', 'No se pudo cargar el juego. Recarga la página.', 'Le jeu n’a pas pu être chargé. Recharge la page.']
    };

    var atual = 'en';

    function lerGuardado() {
        try { return window.localStorage.getItem(CHAVE); } catch (e) { return null; }
    }
    function guardar(codigo) {
        try { window.localStorage.setItem(CHAVE, codigo); } catch (e) { /* sem armazenamento: continua em memória */ }
    }
    function doNavegador() {
        var lista = (navigator.languages && navigator.languages.length) ? navigator.languages : [navigator.language || ''];
        for (var i = 0; i < lista.length; i++) {
            var c = String(lista[i]).slice(0, 2).toLowerCase();
            if (SUPORTADOS.indexOf(c) !== -1) { return c; }
        }
        return null;
    }
    function definirIdioma(codigo, gravar) {
        if (SUPORTADOS.indexOf(codigo) === -1) { codigo = 'en'; }
        atual = codigo;
        if (gravar) { guardar(codigo); }
        document.documentElement.lang = codigo === 'pt' ? 'pt-PT' : codigo;
        return codigo;
    }
    function indice() { return SUPORTADOS.indexOf(atual); }

    function t(chave) {
        var linha = TEXTOS[chave];
        if (!linha) { return chave; }
        var texto = linha[indice()] || linha[1] || chave;
        for (var i = 1; i < arguments.length; i++) {
            texto = texto.split('%' + i).join(String(arguments[i]));
        }
        return texto;
    }

    definirIdioma(lerGuardado() || doNavegador() || 'en', false);

    window.Jogo = {
        VERSAO: VERSAO,
        SUPORTADOS: SUPORTADOS,
        NOMES: NOMES,
        LOCALES: LOCALES,
        idioma: function () { return atual; },
        indice: indice,
        locale: function () { return LOCALES[atual]; },
        definirIdioma: function (c) { return definirIdioma(c, true); },
        t: t,
        TEXTOS: TEXTOS
    };
})();
