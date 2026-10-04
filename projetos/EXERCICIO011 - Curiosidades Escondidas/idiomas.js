// Curiosidades do Mundo · textos em PT, EN, ES e FR.
// O idioma abre como o do navegador (ou o que foi escolhido antes); inglês para os restantes.
// O idioma também decide de que Wikipédia vêm os artigos (pt.wikipedia.org, en.wikipedia.org…).
(function () {
    'use strict';

    var C = window.Curiosidades = window.Curiosidades || {};

    C.VERSAO = '2.0.0';
    C.SUPORTADOS = ['pt', 'en', 'es', 'fr'];
    C.NOMES = { pt: 'Português', en: 'English', es: 'Español', fr: 'Français' };
    C.LOCALES = { pt: 'pt-PT', en: 'en-GB', es: 'es-ES', fr: 'fr-FR' };
    var CHAVE = 'curiosidades_idioma';

    var TEXTOS = {
        pt: {
            titulo: 'Curiosidades do Mundo',
            subtitulo: 'Escolhe uma área e descobre algo real da Wikipédia',
            idioma: 'Idioma',
            escolhe_area: 'Escolhe uma área',
            cat_ciencia: 'Ciência, Espaço e Matemática', cat_ciencia_d: 'Física, química, astronomia, números',
            cat_natureza: 'Natureza, Animais e Corpo humano', cat_natureza_d: 'Bichos, plantas, oceanos, biologia',
            cat_historia: 'História, Geografia e Cultura', cat_historia_d: 'Povos, países, cidades, descobertas',
            cat_artes: 'Arte, Música, Cinema, Desporto, Comida e Tecnologia', cat_artes_d: 'Criação, entretenimento e invenções',
            cat_teologia: 'Teologia e Religiões', cat_teologia_d: 'Crenças, textos sagrados, mitologia',
            cat_surpresa: 'Surpreende-me', cat_surpresa_d: 'Uma área ao acaso',
            a_carregar: 'A procurar uma curiosidade…',
            curiosidade_de: 'Curiosidade · %1',
            revelar: 'Revelar mais dados',
            esconder: 'Esconder dados',
            mais_uma: 'Mais uma',
            ler: 'Ler na Wikipédia',
            dados_rapidos: 'Dados rápidos',
            sobre: 'Sobre este tema',
            descricao: 'Em poucas palavras',
            idiomas: 'Idiomas disponíveis',
            ultima_edicao: 'Última edição',
            tamanho: 'Tamanho do artigo',
            tamanho_valor: '%1 mil caracteres',
            tamanho_pequeno: 'Menos de mil caracteres',
            categorias: 'Categorias',
            relacionados: 'Relacionados',
            sem_relacionados: 'Sem temas relacionados.',
            sem_dados: 'Não foi possível carregar mais dados agora.',
            erro_rede: 'Não foi possível falar com a Wikipédia. Verifica a ligação e tenta outra vez.',
            sem_resultados: 'Não encontrei uma curiosidade desta vez. Tenta outra vez.',
            tentar: 'Tentar outra vez',
            descobertas: 'Descobertas',
            fonte: 'Textos e imagens da Wikipédia, sob licença CC BY-SA 4.0.',
            versao: 'Versão',
            sem_imagem: 'Sem imagem'
        },
        en: {
            titulo: 'Curiosities of the World',
            subtitulo: 'Pick an area and discover something real from Wikipedia',
            idioma: 'Language',
            escolhe_area: 'Pick an area',
            cat_ciencia: 'Science, Space and Maths', cat_ciencia_d: 'Physics, chemistry, astronomy, numbers',
            cat_natureza: 'Nature, Animals and the Human body', cat_natureza_d: 'Creatures, plants, oceans, biology',
            cat_historia: 'History, Geography and Culture', cat_historia_d: 'Peoples, countries, cities, discoveries',
            cat_artes: 'Art, Music, Film, Sport, Food and Technology', cat_artes_d: 'Creativity, entertainment and inventions',
            cat_teologia: 'Theology and Religions', cat_teologia_d: 'Beliefs, sacred texts, mythology',
            cat_surpresa: 'Surprise me', cat_surpresa_d: 'A random area',
            a_carregar: 'Looking for a curiosity…',
            curiosidade_de: 'Curiosity · %1',
            revelar: 'Reveal more facts',
            esconder: 'Hide facts',
            mais_uma: 'Another one',
            ler: 'Read on Wikipedia',
            dados_rapidos: 'Quick facts',
            sobre: 'About this topic',
            descricao: 'In a few words',
            idiomas: 'Languages available',
            ultima_edicao: 'Last edited',
            tamanho: 'Article size',
            tamanho_valor: '%1 thousand characters',
            tamanho_pequeno: 'Under a thousand characters',
            categorias: 'Categories',
            relacionados: 'Related',
            sem_relacionados: 'No related topics.',
            sem_dados: 'Could not load more facts right now.',
            erro_rede: 'Could not reach Wikipedia. Check your connection and try again.',
            sem_resultados: 'I could not find a curiosity this time. Try again.',
            tentar: 'Try again',
            descobertas: 'Discovered',
            fonte: 'Text and images from Wikipedia, under the CC BY-SA 4.0 licence.',
            versao: 'Version',
            sem_imagem: 'No image'
        },
        es: {
            titulo: 'Curiosidades del Mundo',
            subtitulo: 'Elige un área y descubre algo real de Wikipedia',
            idioma: 'Idioma',
            escolhe_area: 'Elige un área',
            cat_ciencia: 'Ciencia, Espacio y Matemáticas', cat_ciencia_d: 'Física, química, astronomía, números',
            cat_natureza: 'Naturaleza, Animales y Cuerpo humano', cat_natureza_d: 'Criaturas, plantas, océanos, biología',
            cat_historia: 'Historia, Geografía y Cultura', cat_historia_d: 'Pueblos, países, ciudades, descubrimientos',
            cat_artes: 'Arte, Música, Cine, Deporte, Comida y Tecnología', cat_artes_d: 'Creación, entretenimiento e inventos',
            cat_teologia: 'Teología y Religiones', cat_teologia_d: 'Creencias, textos sagrados, mitología',
            cat_surpresa: 'Sorpréndeme', cat_surpresa_d: 'Un área al azar',
            a_carregar: 'Buscando una curiosidad…',
            curiosidade_de: 'Curiosidad · %1',
            revelar: 'Revelar más datos',
            esconder: 'Ocultar datos',
            mais_uma: 'Otra más',
            ler: 'Leer en Wikipedia',
            dados_rapidos: 'Datos rápidos',
            sobre: 'Sobre este tema',
            descricao: 'En pocas palabras',
            idiomas: 'Idiomas disponibles',
            ultima_edicao: 'Última edición',
            tamanho: 'Tamaño del artículo',
            tamanho_valor: '%1 mil caracteres',
            tamanho_pequeno: 'Menos de mil caracteres',
            categorias: 'Categorías',
            relacionados: 'Relacionados',
            sem_relacionados: 'Sin temas relacionados.',
            sem_dados: 'No se pudieron cargar más datos ahora.',
            erro_rede: 'No se pudo contactar con Wikipedia. Revisa la conexión e inténtalo de nuevo.',
            sem_resultados: 'No encontré una curiosidad esta vez. Inténtalo de nuevo.',
            tentar: 'Intentar de nuevo',
            descobertas: 'Descubiertas',
            fonte: 'Textos e imágenes de Wikipedia, con licencia CC BY-SA 4.0.',
            versao: 'Versión',
            sem_imagem: 'Sin imagen'
        },
        fr: {
            titulo: 'Curiosités du Monde',
            subtitulo: 'Choisis un domaine et découvre quelque chose de réel sur Wikipédia',
            idioma: 'Langue',
            escolhe_area: 'Choisis un domaine',
            cat_ciencia: 'Science, Espace et Mathématiques', cat_ciencia_d: 'Physique, chimie, astronomie, nombres',
            cat_natureza: 'Nature, Animaux et Corps humain', cat_natureza_d: 'Créatures, plantes, océans, biologie',
            cat_historia: 'Histoire, Géographie et Culture', cat_historia_d: 'Peuples, pays, villes, découvertes',
            cat_artes: 'Art, Musique, Cinéma, Sport, Cuisine et Technologie', cat_artes_d: 'Création, divertissement et inventions',
            cat_teologia: 'Théologie et Religions', cat_teologia_d: 'Croyances, textes sacrés, mythologie',
            cat_surpresa: 'Surprends-moi', cat_surpresa_d: 'Un domaine au hasard',
            a_carregar: 'Recherche d\'une curiosité…',
            curiosidade_de: 'Curiosité · %1',
            revelar: 'Révéler plus de données',
            esconder: 'Masquer les données',
            mais_uma: 'Une autre',
            ler: 'Lire sur Wikipédia',
            dados_rapidos: 'Données rapides',
            sobre: 'À propos de ce sujet',
            descricao: 'En quelques mots',
            idiomas: 'Langues disponibles',
            ultima_edicao: 'Dernière modification',
            tamanho: 'Taille de l\'article',
            tamanho_valor: '%1 mille caractères',
            tamanho_pequeno: 'Moins de mille caractères',
            categorias: 'Catégories',
            relacionados: 'Liés',
            sem_relacionados: 'Aucun sujet lié.',
            sem_dados: 'Impossible de charger plus de données maintenant.',
            erro_rede: 'Impossible de joindre Wikipédia. Vérifie ta connexion et réessaie.',
            sem_resultados: 'Je n\'ai pas trouvé de curiosité cette fois. Réessaie.',
            tentar: 'Réessayer',
            descobertas: 'Découvertes',
            fonte: 'Textes et images de Wikipédia, sous licence CC BY-SA 4.0.',
            versao: 'Version',
            sem_imagem: 'Pas d\'image'
        }
    };

    // Palavras de pesquisa de cada área, por idioma. A cada pedido sorteia-se uma delas e uma posição
    // nos resultados, por isso a curiosidade muda sempre.
    C.SEMENTES = {
        pt: {
            ciencia: ['astronomia', 'física', 'química', 'matemática', 'buraco negro', 'planeta', 'exploração espacial', 'teorema', 'tabela periódica', 'galáxia'],
            natureza: ['animal', 'mamífero', 'ave', 'oceano', 'floresta tropical', 'planta', 'corpo humano', 'cérebro', 'doença', 'inseto'],
            historia: ['império', 'guerra', 'revolução', 'civilização antiga', 'descobrimentos', 'cidade', 'rio', 'país', 'monumento', 'arqueologia'],
            artes: ['pintura', 'música', 'cinema', 'futebol', 'culinária', 'instrumento musical', 'invenção', 'computador', 'internet', 'escultura'],
            teologia: ['teologia', 'Bíblia', 'islão', 'budismo', 'hinduísmo', 'mitologia', 'cristianismo', 'mosteiro', 'judaísmo', 'religião']
        },
        en: {
            ciencia: ['astronomy', 'physics', 'chemistry', 'mathematics', 'black hole', 'planet', 'space exploration', 'theorem', 'periodic table', 'galaxy'],
            natureza: ['animal', 'mammal', 'bird', 'ocean', 'rainforest', 'plant', 'human body', 'brain', 'disease', 'insect'],
            historia: ['empire', 'war', 'revolution', 'ancient civilization', 'age of discovery', 'city', 'river', 'country', 'monument', 'archaeology'],
            artes: ['painting', 'music', 'film', 'football', 'cuisine', 'musical instrument', 'invention', 'computer', 'internet', 'sculpture'],
            teologia: ['theology', 'Bible', 'Islam', 'Buddhism', 'Hinduism', 'mythology', 'Christianity', 'monastery', 'Judaism', 'religion']
        },
        es: {
            ciencia: ['astronomía', 'física', 'química', 'matemáticas', 'agujero negro', 'planeta', 'exploración espacial', 'teorema', 'tabla periódica', 'galaxia'],
            natureza: ['animal', 'mamífero', 'ave', 'océano', 'selva', 'planta', 'cuerpo humano', 'cerebro', 'enfermedad', 'insecto'],
            historia: ['imperio', 'guerra', 'revolución', 'civilización antigua', 'descubrimientos', 'ciudad', 'río', 'país', 'monumento', 'arqueología'],
            artes: ['pintura', 'música', 'cine', 'fútbol', 'gastronomía', 'instrumento musical', 'invención', 'computadora', 'internet', 'escultura'],
            teologia: ['teología', 'Biblia', 'islam', 'budismo', 'hinduismo', 'mitología', 'cristianismo', 'monasterio', 'judaísmo', 'religión']
        },
        fr: {
            ciencia: ['astronomie', 'physique', 'chimie', 'mathématiques', 'trou noir', 'planète', 'exploration spatiale', 'théorème', 'tableau périodique', 'galaxie'],
            natureza: ['animal', 'mammifère', 'oiseau', 'océan', 'forêt tropicale', 'plante', 'corps humain', 'cerveau', 'maladie', 'insecte'],
            historia: ['empire', 'guerre', 'révolution', 'civilisation antique', 'grandes découvertes', 'ville', 'fleuve', 'pays', 'monument', 'archéologie'],
            artes: ['peinture', 'musique', 'cinéma', 'football', 'cuisine', 'instrument de musique', 'invention', 'ordinateur', 'internet', 'sculpture'],
            teologia: ['théologie', 'Bible', 'islam', 'bouddhisme', 'hindouisme', 'mythologie', 'christianisme', 'monastère', 'judaïsme', 'religion']
        }
    };

    function detetar() {
        var guardado = null;
        try { guardado = window.localStorage.getItem(CHAVE); } catch (e) { /* sem armazenamento: segue em frente */ }
        if (guardado && TEXTOS[guardado]) { return guardado; }
        var lista = (navigator.languages && navigator.languages.length) ? navigator.languages : [navigator.language || 'en'];
        for (var i = 0; i < lista.length; i++) {
            var cod = String(lista[i]).slice(0, 2).toLowerCase();
            if (TEXTOS[cod]) { return cod; }
        }
        return 'en';
    }

    C.idioma = detetar();

    C.definirIdioma = function (cod) {
        if (!TEXTOS[cod]) { return; }
        C.idioma = cod;
        try { window.localStorage.setItem(CHAVE, cod); } catch (e) { /* ignora */ }
    };

    // Texto traduzido; %1, %2… são substituídos pelos argumentos.
    C.t = function (chave) {
        var args = Array.prototype.slice.call(arguments, 1);
        var texto = (TEXTOS[C.idioma] && TEXTOS[C.idioma][chave]) || TEXTOS.en[chave] || chave;
        return texto.replace(/%(\d)/g, function (_, n) { return args[Number(n) - 1] !== undefined ? args[Number(n) - 1] : ''; });
    };
})();
