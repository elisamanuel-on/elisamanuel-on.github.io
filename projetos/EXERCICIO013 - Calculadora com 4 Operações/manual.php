<?php
/**
 * Exercício 013 · Texto do manual (PT, EN, ES, FR).
 *
 * Cada secção tem: id (âncora), titulo, texto (opcional) e itens (opcional),
 * onde cada item é [termo, descrição]. A página só apresenta; o texto vive aqui.
 */

declare(strict_types=1);

function secoesDoManual(): array
{
    static $m = null;
    if ($m === null) {
        $m = [
            'pt' => [
                ['id' => 'comecar', 'titulo' => 'Começar', 'texto' => 'A calculadora tem quatro entradas na barra lateral.', 'itens' => [
                    ['6 Operações', 'Escreves dois números e vês logo a soma, a subtração, a multiplicação, a divisão, a potência e o módulo.'],
                    ['Científica', 'Escreves uma expressão completa, como sin(30)+sqrt(16)*2, ou usas o teclado.'],
                    ['Desafio', 'Contas de cabeça, com pontos e níveis.'],
                    ['Manual', 'Esta página.'],
                ]],
                ['id' => 'seis', 'titulo' => '6 Operações', 'texto' => 'Preenche os dois números (aceita vírgula ou ponto) e carrega em Calcular. O módulo é o resto da divisão. Dividir por zero não é possível: essa conta mostra um aviso a vermelho e as outras continuam a funcionar.'],
                ['id' => 'cientifica', 'titulo' => 'Modo Científica', 'texto' => 'Escreve a expressão no visor ou usa o teclado e carrega em = ou Enter. O cálculo é feito no servidor, em PHP, sem usar eval(). O sinal × pode omitir-se: 2(3+4) dá 14.', 'itens' => [
                    ['sin, cos, tan', 'Seno, cosseno e tangente, em graus ou radianos.'],
                    ['asin, acos, atan', 'Funções inversas.'],
                    ['ln, log', 'Logaritmo natural e logaritmo de base 10.'],
                    ['exp', 'e elevado a x.'],
                    ['sqrt, cbrt, abs', 'Raiz quadrada, raiz cúbica e valor absoluto (√, ∛ e |x| no teclado).'],
                    ['!', 'Fatorial, de números inteiros até 170: 5! = 120.'],
                    ['^  e  % (mod)', 'Potência (2^10 = 1024) e resto da divisão (17 % 5 = 2).'],
                    ['pi, e, ans', 'As constantes π e e, e o último resultado.'],
                ]],
                ['id' => 'ferramentas', 'titulo' => 'Ângulos, memória e histórico', 'itens' => [
                    ['Ângulos', 'Escolhe Graus ou Radianos. O emblema DEG/RAD no visor também alterna.'],
                    ['MC, MR, M+, M−', 'Memória: apaga, devolve ao visor, soma o último resultado e subtrai-o. Fica guardada só neste navegador.'],
                    ['Histórico', 'Os últimos 10 cálculos. Clica num para o reutilizar; Limpar apaga a lista.'],
                    ['Copiar', 'O botão de copiar, ao lado do resultado, copia-o para a área de transferência.'],
                    ['Desfazer e refazer', 'Os botões de seta no visor desfazem e refazem o que escreveste.'],
                ]],
                ['id' => 'atalhos', 'titulo' => 'Atalhos de teclado', 'itens' => [
                    ['Enter', 'Calcula.'],
                    ['Esc', 'Limpa o visor.'],
                    ['Ctrl + Z / Ctrl + Y', 'Desfaz e refaz.'],
                ]],
                ['id' => 'elementos', 'titulo' => 'Os cinco elementos', 'texto' => 'Cada função pertence a um elemento. Enquanto escreves, os ícones acendem; misturar três ou mais elementos liberta a Energia.', 'itens' => [
                    ['Ar', 'sin, cos, tan.'],
                    ['Terra', 'ln, log, exp, sqrt, cbrt, abs e o fatorial.'],
                    ['Água', 'pi, e, ans e os parênteses.'],
                    ['Fogo', 'As operações + − × ÷ ^ %.'],
                    ['Energia', 'A mistura de três ou mais elementos.'],
                    ['Reações', 'Um cálculo certo faz o cartão brilhar e soltar faíscas. Um errado faz o cartão tremer.'],
                ]],
                ['id' => 'desafio', 'titulo' => 'Desafio', 'texto' => 'O servidor propõe uma conta de cabeça. Cada resposta certa vale 10 pontos, mais 2 por cada acerto seguido anterior (até mais 10). A cada 3 acertos seguidos sobes de nível, até ao nível 4, e as contas ficam mais difíceis. Errar ou saltar quebra a sequência. Reiniciar pontuação volta tudo a zero.'],
                ['id' => 'erros', 'titulo' => 'Mensagens de erro', 'itens' => [
                    ['Falta fechar um parêntese', 'Confere se cada ( tem o seu ).'],
                    ['Divisão por zero', 'Não é possível dividir por 0.'],
                    ['Raiz de número negativo', 'Não existe raiz quadrada real de um número negativo.'],
                    ['Fatorial', 'Só existe para inteiros de 0 a 170.'],
                ]],
                ['id' => 'dados', 'titulo' => 'Idioma e dados', 'texto' => 'A calculadora abre no idioma do navegador (PT, EN, ES ou FR; em qualquer outro usa inglês). Podes mudá-lo no fundo da barra lateral, e a escolha fica guardada. A memória fica só neste navegador. O histórico fica na sessão do servidor e desaparece quando a sessão termina.'],
            ],

            'en' => [
                ['id' => 'comecar', 'titulo' => 'Getting started', 'texto' => 'The calculator has four entries in the sidebar.', 'itens' => [
                    ['6 Operations', 'Type two numbers and instantly see addition, subtraction, multiplication, division, power and modulo.'],
                    ['Scientific', 'Type a full expression, such as sin(30)+sqrt(16)*2, or use the keypad.'],
                    ['Challenge', 'Mental maths with points and levels.'],
                    ['Manual', 'This page.'],
                ]],
                ['id' => 'seis', 'titulo' => '6 Operations', 'texto' => 'Fill in the two numbers (comma or dot both work) and press Calculate. Modulo is the remainder of the division. Dividing by zero is not possible: that one shows a red warning and the others keep working.'],
                ['id' => 'cientifica', 'titulo' => 'Scientific mode', 'texto' => 'Type the expression in the display or use the keypad, then press = or Enter. The calculation is done on the server, in PHP, without using eval(). The × sign can be left out: 2(3+4) gives 14.', 'itens' => [
                    ['sin, cos, tan', 'Sine, cosine and tangent, in degrees or radians.'],
                    ['asin, acos, atan', 'Inverse functions.'],
                    ['ln, log', 'Natural logarithm and base-10 logarithm.'],
                    ['exp', 'e raised to x.'],
                    ['sqrt, cbrt, abs', 'Square root, cube root and absolute value (√, ∛ and |x| on the keypad).'],
                    ['!', 'Factorial of whole numbers up to 170: 5! = 120.'],
                    ['^  and  % (mod)', 'Power (2^10 = 1024) and remainder of a division (17 % 5 = 2).'],
                    ['pi, e, ans', 'The constants π and e, and the last result.'],
                ]],
                ['id' => 'ferramentas', 'titulo' => 'Angles, memory and history', 'itens' => [
                    ['Angles', 'Choose Degrees or Radians. The DEG/RAD badge on the display also toggles it.'],
                    ['MC, MR, M+, M−', 'Memory: clear, recall to the display, add the last result and subtract it. It is kept only in this browser.'],
                    ['History', 'The last 10 calculations. Click one to reuse it; Clear empties the list.'],
                    ['Copy', 'The copy button next to the result copies it to the clipboard.'],
                    ['Undo and redo', 'The arrow buttons on the display undo and redo what you typed.'],
                ]],
                ['id' => 'atalhos', 'titulo' => 'Keyboard shortcuts', 'itens' => [
                    ['Enter', 'Calculates.'],
                    ['Esc', 'Clears the display.'],
                    ['Ctrl + Z / Ctrl + Y', 'Undo and redo.'],
                ]],
                ['id' => 'elementos', 'titulo' => 'The five elements', 'texto' => 'Each function belongs to an element. As you type, the icons light up; mixing three or more elements releases Energy.', 'itens' => [
                    ['Air', 'sin, cos, tan.'],
                    ['Earth', 'ln, log, exp, sqrt, cbrt, abs and the factorial.'],
                    ['Water', 'pi, e, ans and the parentheses.'],
                    ['Fire', 'The operators + − × ÷ ^ %.'],
                    ['Energy', 'The mix of three or more elements.'],
                    ['Reactions', 'A correct calculation makes the card glow and release sparks. A wrong one makes the card shake.'],
                ]],
                ['id' => 'desafio', 'titulo' => 'Challenge', 'texto' => 'The server sets a mental-maths question. Each correct answer is worth 10 points, plus 2 for every previous correct answer in a row (up to 10 more). Every 3 correct answers in a row you level up, up to level 4, and the questions get harder. A wrong answer or a skip breaks the streak. Reset score takes everything back to zero.'],
                ['id' => 'erros', 'titulo' => 'Error messages', 'itens' => [
                    ['A parenthesis is not closed', 'Check that every ( has its ).'],
                    ['Division by zero', 'You cannot divide by 0.'],
                    ['Root of a negative number', 'There is no real square root of a negative number.'],
                    ['Factorial', 'It only exists for whole numbers from 0 to 170.'],
                ]],
                ['id' => 'dados', 'titulo' => 'Language and data', 'texto' => 'The calculator opens in your browser language (PT, EN, ES or FR; any other uses English). You can change it at the bottom of the sidebar, and your choice is remembered. Memory stays only in this browser. History lives in the server session and disappears when the session ends.'],
            ],

            'es' => [
                ['id' => 'comecar', 'titulo' => 'Empezar', 'texto' => 'La calculadora tiene cuatro entradas en la barra lateral.', 'itens' => [
                    ['6 Operaciones', 'Escribes dos números y ves al instante la suma, la resta, la multiplicación, la división, la potencia y el módulo.'],
                    ['Científica', 'Escribes una expresión completa, como sin(30)+sqrt(16)*2, o usas el teclado.'],
                    ['Desafío', 'Cálculo mental, con puntos y niveles.'],
                    ['Manual', 'Esta página.'],
                ]],
                ['id' => 'seis', 'titulo' => '6 Operaciones', 'texto' => 'Rellena los dos números (acepta coma o punto) y pulsa Calcular. El módulo es el resto de la división. Dividir por cero no es posible: esa cuenta muestra un aviso en rojo y las demás siguen funcionando.'],
                ['id' => 'cientifica', 'titulo' => 'Modo Científica', 'texto' => 'Escribe la expresión en la pantalla o usa el teclado y pulsa = o Enter. El cálculo se hace en el servidor, en PHP, sin usar eval(). El signo × se puede omitir: 2(3+4) da 14.', 'itens' => [
                    ['sin, cos, tan', 'Seno, coseno y tangente, en grados o radianes.'],
                    ['asin, acos, atan', 'Funciones inversas.'],
                    ['ln, log', 'Logaritmo natural y logaritmo de base 10.'],
                    ['exp', 'e elevado a x.'],
                    ['sqrt, cbrt, abs', 'Raíz cuadrada, raíz cúbica y valor absoluto (√, ∛ y |x| en el teclado).'],
                    ['!', 'Factorial de números enteros hasta 170: 5! = 120.'],
                    ['^  y  % (mod)', 'Potencia (2^10 = 1024) y resto de la división (17 % 5 = 2).'],
                    ['pi, e, ans', 'Las constantes π y e, y el último resultado.'],
                ]],
                ['id' => 'ferramentas', 'titulo' => 'Ángulos, memoria e historial', 'itens' => [
                    ['Ángulos', 'Elige Grados o Radianes. La insignia DEG/RAD de la pantalla también alterna.'],
                    ['MC, MR, M+, M−', 'Memoria: borra, devuelve a la pantalla, suma el último resultado y lo resta. Se guarda solo en este navegador.'],
                    ['Historial', 'Los últimos 10 cálculos. Haz clic en uno para reutilizarlo; Limpiar vacía la lista.'],
                    ['Copiar', 'El botón de copiar junto al resultado lo copia al portapapeles.'],
                    ['Deshacer y rehacer', 'Los botones de flecha de la pantalla deshacen y rehacen lo que escribiste.'],
                ]],
                ['id' => 'atalhos', 'titulo' => 'Atajos de teclado', 'itens' => [
                    ['Enter', 'Calcula.'],
                    ['Esc', 'Limpia la pantalla.'],
                    ['Ctrl + Z / Ctrl + Y', 'Deshace y rehace.'],
                ]],
                ['id' => 'elementos', 'titulo' => 'Los cinco elementos', 'texto' => 'Cada función pertenece a un elemento. Mientras escribes, los iconos se encienden; mezclar tres o más elementos libera la Energía.', 'itens' => [
                    ['Aire', 'sin, cos, tan.'],
                    ['Tierra', 'ln, log, exp, sqrt, cbrt, abs y el factorial.'],
                    ['Agua', 'pi, e, ans y los paréntesis.'],
                    ['Fuego', 'Las operaciones + − × ÷ ^ %.'],
                    ['Energía', 'La mezcla de tres o más elementos.'],
                    ['Reacciones', 'Un cálculo correcto hace brillar la tarjeta y soltar chispas. Uno incorrecto la hace temblar.'],
                ]],
                ['id' => 'desafio', 'titulo' => 'Desafío', 'texto' => 'El servidor propone una cuenta mental. Cada respuesta correcta vale 10 puntos, más 2 por cada acierto seguido anterior (hasta 10 más). Cada 3 aciertos seguidos subes de nivel, hasta el nivel 4, y las cuentas se vuelven más difíciles. Fallar o saltar rompe la racha. Reiniciar puntuación lo pone todo a cero.'],
                ['id' => 'erros', 'titulo' => 'Mensajes de error', 'itens' => [
                    ['Falta cerrar un paréntesis', 'Comprueba que cada ( tiene su ).'],
                    ['División por cero', 'No se puede dividir entre 0.'],
                    ['Raíz de un número negativo', 'No existe raíz cuadrada real de un número negativo.'],
                    ['Factorial', 'Solo existe para enteros de 0 a 170.'],
                ]],
                ['id' => 'dados', 'titulo' => 'Idioma y datos', 'texto' => 'La calculadora se abre en el idioma del navegador (PT, EN, ES o FR; en cualquier otro usa inglés). Puedes cambiarlo al final de la barra lateral y la elección queda guardada. La memoria queda solo en este navegador. El historial vive en la sesión del servidor y desaparece cuando termina la sesión.'],
            ],

            'fr' => [
                ['id' => 'comecar', 'titulo' => 'Pour commencer', 'texto' => 'La calculatrice a quatre entrées dans la barre latérale.', 'itens' => [
                    ['6 opérations', "Tu saisis deux nombres et tu vois aussitôt l'addition, la soustraction, la multiplication, la division, la puissance et le modulo."],
                    ['Scientifique', 'Tu saisis une expression complète, comme sin(30)+sqrt(16)*2, ou tu utilises le clavier.'],
                    ['Défi', 'Calcul mental, avec des points et des niveaux.'],
                    ['Manuel', 'Cette page.'],
                ]],
                ['id' => 'seis', 'titulo' => '6 opérations', 'texto' => "Remplis les deux nombres (virgule ou point acceptés) et appuie sur Calculer. Le modulo est le reste de la division. Diviser par zéro est impossible : ce calcul affiche un avertissement en rouge et les autres continuent de fonctionner."],
                ['id' => 'cientifica', 'titulo' => 'Mode scientifique', 'texto' => "Saisis l'expression sur l'écran ou utilise le clavier, puis appuie sur = ou Entrée. Le calcul est fait sur le serveur, en PHP, sans eval(). Le signe × peut être omis : 2(3+4) donne 14.", 'itens' => [
                    ['sin, cos, tan', 'Sinus, cosinus et tangente, en degrés ou en radians.'],
                    ['asin, acos, atan', 'Fonctions inverses.'],
                    ['ln, log', 'Logarithme népérien et logarithme de base 10.'],
                    ['exp', 'e puissance x.'],
                    ['sqrt, cbrt, abs', 'Racine carrée, racine cubique et valeur absolue (√, ∛ et |x| sur le clavier).'],
                    ['!', "Factorielle des entiers jusqu'à 170 : 5! = 120."],
                    ['^  et  % (mod)', 'Puissance (2^10 = 1024) et reste de la division (17 % 5 = 2).'],
                    ['pi, e, ans', 'Les constantes π et e, et le dernier résultat.'],
                ]],
                ['id' => 'ferramentas', 'titulo' => 'Angles, mémoire et historique', 'itens' => [
                    ['Angles', "Choisis Degrés ou Radians. Le badge DEG/RAD de l'écran bascule aussi."],
                    ['MC, MR, M+, M−', "Mémoire : efface, rappelle à l'écran, ajoute le dernier résultat et le soustrait. Elle reste seulement dans ce navigateur."],
                    ['Historique', 'Les 10 derniers calculs. Clique sur un calcul pour le réutiliser ; Effacer vide la liste.'],
                    ['Copier', "Le bouton de copie à côté du résultat le copie dans le presse-papiers."],
                    ['Annuler et rétablir', "Les boutons fléchés de l'écran annulent et rétablissent ce que tu as saisi."],
                ]],
                ['id' => 'atalhos', 'titulo' => 'Raccourcis clavier', 'itens' => [
                    ['Entrée', 'Calcule.'],
                    ['Échap', "Efface l'écran."],
                    ['Ctrl + Z / Ctrl + Y', 'Annule et rétablit.'],
                ]],
                ['id' => 'elementos', 'titulo' => 'Les cinq éléments', 'texto' => "Chaque fonction appartient à un élément. Pendant que tu écris, les icônes s'allument ; mélanger trois éléments ou plus libère l'Énergie.", 'itens' => [
                    ['Air', 'sin, cos, tan.'],
                    ['Terre', 'ln, log, exp, sqrt, cbrt, abs et la factorielle.'],
                    ['Eau', 'pi, e, ans et les parenthèses.'],
                    ['Feu', 'Les opérations + − × ÷ ^ %.'],
                    ['Énergie', 'Le mélange de trois éléments ou plus.'],
                    ['Réactions', 'Un calcul juste fait briller la carte et jaillir des étincelles. Un calcul faux la fait trembler.'],
                ]],
                ['id' => 'desafio', 'titulo' => 'Défi', 'texto' => "Le serveur propose un calcul mental. Chaque bonne réponse vaut 10 points, plus 2 par bonne réponse de suite précédente (jusqu'à 10 de plus). Toutes les 3 bonnes réponses de suite, tu passes au niveau suivant, jusqu'au niveau 4, et les calculs se compliquent. Se tromper ou passer casse la série. Réinitialiser le score remet tout à zéro."],
                ['id' => 'erros', 'titulo' => "Messages d'erreur", 'itens' => [
                    ['Il manque une parenthèse', 'Vérifie que chaque ( a sa ).'],
                    ['Division par zéro', 'On ne peut pas diviser par 0.'],
                    ['Racine d\'un nombre négatif', "Il n'existe pas de racine carrée réelle d'un nombre négatif."],
                    ['Factorielle', "Elle n'existe que pour les entiers de 0 à 170."],
                ]],
                ['id' => 'dados', 'titulo' => 'Langue et données', 'texto' => "La calculatrice s'ouvre dans la langue du navigateur (PT, EN, ES ou FR ; sinon anglais). Tu peux la changer en bas de la barre latérale et ton choix est mémorisé. La mémoire reste seulement dans ce navigateur. L'historique vit dans la session du serveur et disparaît quand la session se termine."],
            ],
        ];
    }
    return $m[idiomaAtivo()] ?? $m['en'];
}
