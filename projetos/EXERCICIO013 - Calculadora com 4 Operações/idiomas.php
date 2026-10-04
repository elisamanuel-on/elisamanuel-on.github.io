<?php
/**
 * Exercício 013 · Idiomas (PT, EN, ES, FR).
 *
 * O idioma é escolhido por esta ordem:
 *   1. ?lang=xx no endereço (quem clica no seletor);
 *   2. o que ficou guardado num cookie de uma visita anterior;
 *   3. o idioma do navegador (cabeçalho Accept-Language), se for um dos quatro;
 *   4. inglês, para qualquer outro idioma.
 *
 * Aqui só se escolhem e traduzem textos; os cálculos continuam em calculadora.php.
 */

declare(strict_types=1);

const IDIOMAS_SUPORTADOS = ['pt' => 'PT', 'en' => 'EN', 'es' => 'ES', 'fr' => 'FR'];
const IDIOMA_OMISSAO = 'en';

/** Primeiro idioma suportado do cabeçalho Accept-Language, respeitando os valores q. */
function idiomaDoNavegador(?string $cabecalho): ?string
{
    if ($cabecalho === null || $cabecalho === '') {
        return null;
    }
    $candidatos = [];
    foreach (explode(',', $cabecalho) as $posicao => $parte) {
        $parte = trim($parte);
        if ($parte === '') {
            continue;
        }
        $q = 1.0;
        if (preg_match('/;\s*q=([0-9.]+)/i', $parte, $m) === 1) {
            $q = (float) $m[1];
        }
        $codigo = strtolower(substr($parte, 0, 2));
        if (array_key_exists($codigo, IDIOMAS_SUPORTADOS) && $q > 0) {
            $candidatos[] = [$q, -$posicao, $codigo];
        }
    }
    if ($candidatos === []) {
        return null;
    }
    rsort($candidatos);
    return $candidatos[0][2];
}

/** Decide o idioma desta visita e guarda a escolha num cookie. */
function escolherIdioma(): string
{
    $pedido = $_GET['lang'] ?? null;
    if (is_string($pedido) && array_key_exists(strtolower($pedido), IDIOMAS_SUPORTADOS)) {
        $idioma = strtolower($pedido);
        setcookie('calc_lang', $idioma, [
            'expires'  => time() + 60 * 60 * 24 * 365,
            'path'     => '/',
            'samesite' => 'Lax',
            'httponly' => true,
        ]);
        return $idioma;
    }

    $guardado = $_COOKIE['calc_lang'] ?? null;
    if (is_string($guardado) && array_key_exists($guardado, IDIOMAS_SUPORTADOS)) {
        return $guardado;
    }

    $cabecalho = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? (string) $_SERVER['HTTP_ACCEPT_LANGUAGE'] : null;
    return idiomaDoNavegador($cabecalho) ?? IDIOMA_OMISSAO;
}

/** Idioma ativo (definido uma vez por pedido). */
function idiomaAtivo(?string $definir = null): string
{
    static $idioma = IDIOMA_OMISSAO;
    if ($definir !== null) {
        $idioma = $definir;
    }
    return $idioma;
}

/** Textos da interface. Se faltar uma chave num idioma, usa-se a versão em inglês. */
function textos(): array
{
    static $t = null;
    if ($t !== null) {
        return $t;
    }

    $t = [
        'pt' => [
            'html_lang' => 'pt-PT',
            'titulo' => 'Calculadora Científica',
            'marca' => 'Elementos',
            'marca_sub' => 'Calculadora',
            'idioma_aria' => 'Idioma',
            'aba_basica' => '6 Operações', 'aba_cientifica' => 'Científica', 'aba_desafio' => 'Desafio',
            'aria_modo' => 'Modo da calculadora',
            'num1' => 'Primeiro número', 'num2' => 'Segundo número', 'num_ph' => 'Insira um número',
            'calcular' => 'Calcular', 'aguardando' => 'Aguardando números...',
            'err_vazio' => 'Por favor, preencha ambos os números!', 'err_invalido' => 'Por favor, insira números válidos!',
            'op_soma' => 'Soma', 'op_subtracao' => 'Subtração', 'op_multiplicacao' => 'Multiplicação',
            'op_divisao' => 'Divisão', 'op_potencia' => 'Potência', 'op_modulo' => 'Módulo (resto)',
            'err_div0' => 'Divisão por zero!', 'err_mod0' => 'Módulo por zero!',
            'err_indefinido' => 'Resultado indefinido', 'err_operacao' => 'Operação desconhecida',
            'expressao' => 'Expressão', 'angulos' => 'Ângulos', 'graus' => 'Graus', 'radianos' => 'Radianos',
            'dica_ans' => 'Usa %s para o último resultado.',
            'teclado' => 'Teclado', 'apagar' => 'Apagar último carácter',
            'memoria' => 'Memória', 'memoria_vazia' => 'vazia',
            'historico' => 'Histórico', 'historico_limpar' => 'Limpar', 'historico_vazio' => 'Ainda não há cálculos.',
            'historico_dica' => 'Clica num cálculo para o reutilizar.',
            'pontos' => 'Pontos', 'sequencia' => 'Sequência', 'melhor' => 'Melhor', 'nivel' => 'Nível',
            'progresso' => 'Progresso para o próximo nível',
            'mais_acerto_1' => 'Mais %d acerto seguido para o nível %d.',
            'mais_acerto_n' => 'Mais %d acertos seguidos para o nível %d.',
            'resposta' => 'A tua resposta', 'responder' => 'Responder', 'saltar' => 'Saltar', 'reiniciar' => 'Reiniciar pontuação',
            'aviso_numero' => 'Escreve um número para responder.',
            'fb_saltou' => 'Saltaste. %s = %s.',
            'fb_certo' => 'Certo! %s = %s. +%d pontos.',
            'fb_certo_nivel' => 'Certo! %s = %s. +%d pontos e subiste de nível!',
            'fb_errado' => 'Quase! %s = %s.',
            'el_agua' => 'Água', 'el_fogo' => 'Fogo', 'el_terra' => 'Terra', 'el_ar' => 'Ar', 'el_energia' => 'Energia', 'el_magia' => 'Magia',
        ],
        'en' => [
            'html_lang' => 'en',
            'titulo' => 'Scientific Calculator',
            'marca' => 'Elements',
            'marca_sub' => 'Calculator',
            'idioma_aria' => 'Language',
            'aba_basica' => '6 Operations', 'aba_cientifica' => 'Scientific', 'aba_desafio' => 'Challenge',
            'aria_modo' => 'Calculator mode',
            'num1' => 'First number', 'num2' => 'Second number', 'num_ph' => 'Enter a number',
            'calcular' => 'Calculate', 'aguardando' => 'Waiting for numbers...',
            'err_vazio' => 'Please fill in both numbers!', 'err_invalido' => 'Please enter valid numbers!',
            'op_soma' => 'Addition', 'op_subtracao' => 'Subtraction', 'op_multiplicacao' => 'Multiplication',
            'op_divisao' => 'Division', 'op_potencia' => 'Power', 'op_modulo' => 'Modulo (remainder)',
            'err_div0' => 'Division by zero!', 'err_mod0' => 'Modulo by zero!',
            'err_indefinido' => 'Undefined result', 'err_operacao' => 'Unknown operation',
            'expressao' => 'Expression', 'angulos' => 'Angles', 'graus' => 'Degrees', 'radianos' => 'Radians',
            'dica_ans' => 'Use %s for the last result.',
            'teclado' => 'Keypad', 'apagar' => 'Delete last character',
            'memoria' => 'Memory', 'memoria_vazia' => 'empty',
            'historico' => 'History', 'historico_limpar' => 'Clear', 'historico_vazio' => 'No calculations yet.',
            'historico_dica' => 'Click a calculation to reuse it.',
            'pontos' => 'Points', 'sequencia' => 'Streak', 'melhor' => 'Best', 'nivel' => 'Level',
            'progresso' => 'Progress to the next level',
            'mais_acerto_1' => '%d more correct answer in a row for level %d.',
            'mais_acerto_n' => '%d more correct answers in a row for level %d.',
            'resposta' => 'Your answer', 'responder' => 'Answer', 'saltar' => 'Skip', 'reiniciar' => 'Reset score',
            'aviso_numero' => 'Type a number to answer.',
            'fb_saltou' => 'Skipped. %s = %s.',
            'fb_certo' => 'Correct! %s = %s. +%d points.',
            'fb_certo_nivel' => 'Correct! %s = %s. +%d points and you levelled up!',
            'fb_errado' => 'Almost! %s = %s.',
            'el_agua' => 'Water', 'el_fogo' => 'Fire', 'el_terra' => 'Earth', 'el_ar' => 'Air', 'el_energia' => 'Energy', 'el_magia' => 'Magic',
        ],
        'es' => [
            'html_lang' => 'es',
            'titulo' => 'Calculadora Científica',
            'marca' => 'Elementos',
            'marca_sub' => 'Calculadora',
            'idioma_aria' => 'Idioma',
            'aba_basica' => '6 Operaciones', 'aba_cientifica' => 'Científica', 'aba_desafio' => 'Desafío',
            'aria_modo' => 'Modo de la calculadora',
            'num1' => 'Primer número', 'num2' => 'Segundo número', 'num_ph' => 'Introduce un número',
            'calcular' => 'Calcular', 'aguardando' => 'Esperando números...',
            'err_vazio' => '¡Por favor, rellena ambos números!', 'err_invalido' => '¡Por favor, introduce números válidos!',
            'op_soma' => 'Suma', 'op_subtracao' => 'Resta', 'op_multiplicacao' => 'Multiplicación',
            'op_divisao' => 'División', 'op_potencia' => 'Potencia', 'op_modulo' => 'Módulo (resto)',
            'err_div0' => '¡División por cero!', 'err_mod0' => '¡Módulo por cero!',
            'err_indefinido' => 'Resultado indefinido', 'err_operacao' => 'Operación desconocida',
            'expressao' => 'Expresión', 'angulos' => 'Ángulos', 'graus' => 'Grados', 'radianos' => 'Radianes',
            'dica_ans' => 'Usa %s para el último resultado.',
            'teclado' => 'Teclado', 'apagar' => 'Borrar el último carácter',
            'memoria' => 'Memoria', 'memoria_vazia' => 'vacía',
            'historico' => 'Historial', 'historico_limpar' => 'Limpiar', 'historico_vazio' => 'Todavía no hay cálculos.',
            'historico_dica' => 'Haz clic en un cálculo para reutilizarlo.',
            'pontos' => 'Puntos', 'sequencia' => 'Racha', 'melhor' => 'Mejor', 'nivel' => 'Nivel',
            'progresso' => 'Progreso hacia el siguiente nivel',
            'mais_acerto_1' => 'Un acierto más seguido para el nivel %2$d.',
            'mais_acerto_n' => '%1$d aciertos más seguidos para el nivel %2$d.',
            'resposta' => 'Tu respuesta', 'responder' => 'Responder', 'saltar' => 'Saltar', 'reiniciar' => 'Reiniciar puntuación',
            'aviso_numero' => 'Escribe un número para responder.',
            'fb_saltou' => 'Saltaste. %s = %s.',
            'fb_certo' => '¡Correcto! %s = %s. +%d puntos.',
            'fb_certo_nivel' => '¡Correcto! %s = %s. +%d puntos y subiste de nivel.',
            'fb_errado' => '¡Casi! %s = %s.',
            'el_agua' => 'Agua', 'el_fogo' => 'Fuego', 'el_terra' => 'Tierra', 'el_ar' => 'Aire', 'el_energia' => 'Energía', 'el_magia' => 'Magia',
        ],
        'fr' => [
            'html_lang' => 'fr',
            'titulo' => 'Calculatrice scientifique',
            'marca' => 'Éléments',
            'marca_sub' => 'Calculatrice',
            'idioma_aria' => 'Langue',
            'aba_basica' => '6 opérations', 'aba_cientifica' => 'Scientifique', 'aba_desafio' => 'Défi',
            'aria_modo' => 'Mode de la calculatrice',
            'num1' => 'Premier nombre', 'num2' => 'Deuxième nombre', 'num_ph' => 'Saisis un nombre',
            'calcular' => 'Calculer', 'aguardando' => 'En attente de nombres...',
            'err_vazio' => 'Veuillez remplir les deux nombres !', 'err_invalido' => 'Veuillez saisir des nombres valides !',
            'op_soma' => 'Addition', 'op_subtracao' => 'Soustraction', 'op_multiplicacao' => 'Multiplication',
            'op_divisao' => 'Division', 'op_potencia' => 'Puissance', 'op_modulo' => 'Modulo (reste)',
            'err_div0' => 'Division par zéro !', 'err_mod0' => 'Modulo par zéro !',
            'err_indefinido' => 'Résultat indéfini', 'err_operacao' => 'Opération inconnue',
            'expressao' => 'Expression', 'angulos' => 'Angles', 'graus' => 'Degrés', 'radianos' => 'Radians',
            'dica_ans' => 'Utilise %s pour le dernier résultat.',
            'teclado' => 'Clavier', 'apagar' => 'Effacer le dernier caractère',
            'memoria' => 'Mémoire', 'memoria_vazia' => 'vide',
            'historico' => 'Historique', 'historico_limpar' => 'Effacer', 'historico_vazio' => 'Aucun calcul pour le moment.',
            'historico_dica' => 'Clique sur un calcul pour le réutiliser.',
            'pontos' => 'Points', 'sequencia' => 'Série', 'melhor' => 'Meilleure', 'nivel' => 'Niveau',
            'progresso' => 'Progression vers le niveau suivant',
            'mais_acerto_1' => 'Encore %d bonne réponse de suite pour le niveau %d.',
            'mais_acerto_n' => 'Encore %d bonnes réponses de suite pour le niveau %d.',
            'resposta' => 'Ta réponse', 'responder' => 'Répondre', 'saltar' => 'Passer', 'reiniciar' => 'Réinitialiser le score',
            'aviso_numero' => 'Écris un nombre pour répondre.',
            'fb_saltou' => 'Tu as passé. %s = %s.',
            'fb_certo' => 'Bravo ! %s = %s. +%d points.',
            'fb_certo_nivel' => 'Bravo ! %s = %s. +%d points et tu passes au niveau suivant !',
            'fb_errado' => 'Presque ! %s = %s.',
            'el_agua' => 'Eau', 'el_fogo' => 'Feu', 'el_terra' => 'Terre', 'el_ar' => 'Air', 'el_energia' => 'Énergie', 'el_magia' => 'Magie',
        ],
    ];
    return $t;
}

/** Texto traduzido. Aceita argumentos para sprintf. */
function t(string $chave, string|int|float ...$argumentos): string
{
    $todos = textos();
    $idioma = idiomaAtivo();
    $texto = $todos[$idioma][$chave] ?? $todos['en'][$chave] ?? $chave;
    return $argumentos === [] ? $texto : vsprintf($texto, $argumentos);
}

/** Traduz as mensagens de erro do cálculo (escritas em português em calculadora.php). */
function traduzirErro(string $mensagem): string
{
    $idioma = idiomaAtivo();
    if ($idioma === 'pt') {
        return $mensagem;
    }

    // Mensagens fixas: português => [en, es, fr]
    $fixas = [
        'Escreve uma expressão para calcular.' => ['Type an expression to calculate.', 'Escribe una expresión para calcular.', 'Saisis une expression à calculer.'],
        'A expressão é demasiado longa.' => ['The expression is too long.', 'La expresión es demasiado larga.', "L'expression est trop longue."],
        'Número inválido.' => ['Invalid number.', 'Número no válido.', 'Nombre invalide.'],
        'Demasiados parênteses encadeados.' => ['Too many nested parentheses.', 'Demasiados paréntesis anidados.', 'Trop de parenthèses imbriquées.'],
        'Erro: divisão por zero!' => ['Error: division by zero!', '¡Error: división por cero!', 'Erreur : division par zéro !'],
        'Erro: módulo por zero!' => ['Error: modulo by zero!', '¡Error: módulo por cero!', 'Erreur : modulo par zéro !'],
        'Não existe potência real de base negativa com expoente fracionário.' => [
            'No real power exists for a negative base with a fractional exponent.',
            'No existe potencia real de base negativa con exponente fraccionario.',
            "Il n'existe pas de puissance réelle d'une base négative avec un exposant fractionnaire.",
        ],
        'A expressão está incompleta.' => ['The expression is incomplete.', 'La expresión está incompleta.', "L'expression est incomplète."],
        'Falta fechar um parêntese.' => ['A parenthesis is not closed.', 'Falta cerrar un paréntesis.', 'Il manque une parenthèse fermante.'],
        'Função desconhecida.' => ['Unknown function.', 'Función desconocida.', 'Fonction inconnue.'],
        'Não existe raiz quadrada real de um número negativo.' => [
            'There is no real square root of a negative number.',
            'No existe raíz cuadrada real de un número negativo.',
            "Il n'existe pas de racine carrée réelle d'un nombre négatif.",
        ],
        'O logaritmo só existe para números maiores que zero.' => [
            'The logarithm only exists for numbers greater than zero.',
            'El logaritmo solo existe para números mayores que cero.',
            "Le logarithme n'existe que pour les nombres supérieurs à zéro.",
        ],
        'A tangente não está definida para este ângulo.' => [
            'The tangent is not defined for this angle.',
            'La tangente no está definida para este ángulo.',
            "La tangente n'est pas définie pour cet angle.",
        ],
        'O fatorial só existe para inteiros não negativos.' => [
            'The factorial only exists for non-negative integers.',
            'El factorial solo existe para enteros no negativos.',
            "La factorielle n'existe que pour les entiers positifs ou nuls.",
        ],
        'O fatorial é demasiado grande (máximo 170!).' => [
            'The factorial is too large (maximum 170!).',
            'El factorial es demasiado grande (máximo 170!).',
            'La factorielle est trop grande (maximum 170!).',
        ],
        'O resultado é demasiado grande ou indefinido.' => [
            'The result is too large or undefined.',
            'El resultado es demasiado grande o indefinido.',
            'Le résultat est trop grand ou indéfini.',
        ],
    ];
    $coluna = ['en' => 0, 'es' => 1, 'fr' => 2][$idioma] ?? 0;
    if (isset($fixas[$mensagem])) {
        return $fixas[$mensagem][$coluna];
    }

    // Mensagens com partes variáveis: [expressão em português, modelos en/es/fr]
    $variaveis = [
        ['/^Expressão inválida perto de "(.*)"\.$/us', ['Invalid expression near "%s".', 'Expresión no válida cerca de "%s".', 'Expression invalide près de « %s ».']],
        ['/^Carácter não permitido: "(.*)"\.$/us', ['Character not allowed: "%s".', 'Carácter no permitido: "%s".', 'Caractère non autorisé : « %s ».']],
        ['/^Função ou símbolo desconhecido: "(.*)"\.$/us', ['Unknown function or symbol: "%s".', 'Función o símbolo desconocido: "%s".', 'Fonction ou symbole inconnu : « %s ».']],
        ['/^Falta fechar o parêntese de "(.*)"\.$/us', ['Missing closing parenthesis for "%s".', 'Falta cerrar el paréntesis de "%s".', 'Il manque la parenthèse fermante de « %s ».']],
        ['/^O argumento de (.*) tem de estar entre -1 e 1\.$/us', ['The argument of %s must be between -1 and 1.', 'El argumento de %s debe estar entre -1 y 1.', "L'argument de %s doit être compris entre -1 et 1."]],
    ];
    foreach ($variaveis as [$padrao, $modelos]) {
        if (preg_match($padrao, $mensagem, $m) === 1) {
            return sprintf($modelos[$coluna], $m[1]);
        }
    }

    // 'Depois de "sin" abre um parêntese, por exemplo sin(9).'
    if (preg_match('/^Depois de "(.*)" abre um parêntese, por exemplo (.*)\.$/us', $mensagem, $m) === 1) {
        $modelos = [
            'After "%1$s" open a parenthesis, for example %2$s.',
            'Después de "%1$s" abre un paréntesis, por ejemplo %2$s.',
            'Après « %1$s » ouvre une parenthèse, par exemple %2$s.',
        ];
        return sprintf($modelos[$coluna], $m[1], $m[2]);
    }

    return $mensagem;
}
