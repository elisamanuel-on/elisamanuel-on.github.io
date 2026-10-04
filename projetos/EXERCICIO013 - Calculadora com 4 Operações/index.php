<?php
/**
 * Exercício 013 · Calculadora Científica (com PHP)
 *
 * Evolução da calculadora de 4 operações em JavaScript: agora o cálculo é feito
 * no servidor, com validação, e há um modo científico com expressões completas.
 */

declare(strict_types=1);

require_once __DIR__ . '/calculadora.php';

ini_set('display_errors', '0');
session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
session_start();

/** Escapa texto para HTML. */
function e(string|int|float|null $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Lê um número de texto (aceita vírgula). Devolve null se não for um número válido. */
function lerNumero(mixed $texto): ?float
{
    if (!is_string($texto)) {
        return null;
    }
    $limpo = trim(str_replace(',', '.', $texto));
    if ($limpo === '' || !is_numeric($limpo)) {
        return null;
    }
    $valor = (float) $limpo;
    return is_finite($valor) ? $valor : null;
}

/**
 * Faz uma das operações básicas. Devolve o resultado ou uma frase de erro.
 * (Só calcula e devolve: quem apresenta é a página.)
 */
function operar(string $operacao, float $a, float $b): float|string
{
    switch ($operacao) {
        case 'soma':
            return $a + $b;
        case 'subtracao':
            return $a - $b;
        case 'multiplicacao':
            return $a * $b;
        case 'divisao':
            return $b == 0.0 ? 'Divisão por zero!' : $a / $b;
        case 'modulo':
            return $b == 0.0 ? 'Módulo por zero!' : fmod($a, $b);
        case 'potencia':
            $r = $a ** $b;
            return (is_nan($r) || is_infinite($r)) ? 'Resultado indefinido' : $r;
        default:
            return 'Operação desconhecida';
    }
}

// [chave => [símbolo, nome, símbolo na conta]]
$operacoes = [
    'soma'          => ['+', 'Soma', '+'],
    'subtracao'     => ['−', 'Subtração', '-'],
    'multiplicacao' => ['×', 'Multiplicação', '×'],
    'divisao'       => ['÷', 'Divisão', '÷'],
    'potencia'      => ['xʸ', 'Potência', '^'],
    'modulo'        => ['%', 'Módulo (resto)', 'mod'],
];

$modo = ($_GET['modo'] ?? 'basica') === 'cientifica' ? 'cientifica' : 'basica';

// ---------------- Modo 4 operações ----------------
$num1 = $num2 = '';
$resultadosBasicos = [];
$erroBasico = null;

// ---------------- Modo científico ----------------
$expressao = '';
$angulo = 'deg';
$resultadoCientifico = null;
$erroCientifico = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['formulario'] ?? '') === 'basica') {
        $modo = 'basica';
        $num1 = is_string($_POST['num1'] ?? null) ? trim($_POST['num1']) : '';
        $num2 = is_string($_POST['num2'] ?? null) ? trim($_POST['num2']) : '';
        $a = lerNumero($num1);
        $b = lerNumero($num2);

        if ($num1 === '' || $num2 === '') {
            $erroBasico = 'Por favor, preencha ambos os números!';
        } elseif ($a === null || $b === null) {
            $erroBasico = 'Por favor, insira números válidos!';
        } else {
            foreach ($operacoes as $chave => [$simbolo, $nome, $sinal]) {
                $resultadosBasicos[$chave] = operar($chave, $a, $b);
            }
        }
    } elseif (($_POST['formulario'] ?? '') === 'cientifica') {
        $modo = 'cientifica';
        $expressao = is_string($_POST['expressao'] ?? null) ? trim($_POST['expressao']) : '';
        $angulo = ($_POST['angulo'] ?? 'deg') === 'rad' ? 'rad' : 'deg';
        try {
            $valor = Calculadora::avaliar($expressao, $angulo, (float) ($_SESSION['ans'] ?? 0));
            $resultadoCientifico = Calculadora::formatar($valor);
            $_SESSION['ans'] = $valor;
            $_SESSION['historico'] = array_slice(
                array_merge([$expressao . ' = ' . $resultadoCientifico], $_SESSION['historico'] ?? []),
                0,
                5
            );
        } catch (ErroCalculo $ex) {
            $erroCientifico = $ex->getMessage();
        }
    }
}

$historico = $_SESSION['historico'] ?? [];
$ico = static fn (string $d): string => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>';
$elementos = [
    'agua'    => ['Água',    $ico('<path d="M12 3C12 3 5.5 10.2 5.5 14.5a6.5 6.5 0 0 0 13 0C18.5 10.2 12 3 12 3z"/>')],
    'fogo'    => ['Fogo',    $ico('<path d="M12 3c1 3.5 5 5.5 5 10a5 5 0 0 1-10 0c0-2 1-3.2 2-4.2.2 1.4 1 2.2 1.8 2.2C10.5 8 11 5.5 12 3z"/>')],
    'terra'   => ['Terra',   $ico('<path d="M5 19C5 10 10 5 19 5c0 9-5 14-14 14z"/><path d="M5 19l8-8"/>')],
    'ar'      => ['Ar',      $ico('<path d="M3 9h10a3 3 0 1 0-3-3"/><path d="M3 14h14a3 3 0 1 1-3 3"/><path d="M3 19h5"/>')],
    'energia' => ['Energia', $ico('<polygon points="12 3 14.6 9 21 9.6 16.2 13.8 17.7 20.2 12 16.8 6.3 20.2 7.8 13.8 3 9.6 9.4 9"/>')],
];
// cor (elemento) de cada tecla científica, pelo texto mostrado
$corTecla = [
    'sin' => 'ar', 'cos' => 'ar', 'tan' => 'ar', 'asin' => 'ar', 'acos' => 'ar', 'atan' => 'ar',
    'ln' => 'terra', 'log' => 'terra', 'exp' => 'terra', '√' => 'terra', '∛' => 'terra', '|x|' => 'terra', '1/x' => 'terra', 'x²' => 'terra', 'x!' => 'terra',
    'π' => 'agua', 'e' => 'agua', 'ans' => 'agua', '(' => 'agua', ')' => 'agua',
];
$iconeCalculadora = '<svg class="icon" style="width:1em;height:1em;vertical-align:-0.125em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="6" x2="16" y2="6"/><line x1="8" y1="10" x2="8.01" y2="10"/><line x1="12" y1="10" x2="12.01" y2="10"/><line x1="16" y1="10" x2="16.01" y2="10"/><line x1="8" y1="14" x2="8.01" y2="14"/><line x1="12" y1="14" x2="12.01" y2="14"/><line x1="16" y1="14" x2="16.01" y2="14"/><line x1="8" y1="18" x2="16" y2="18"/></svg>';
?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calculadora Científica · Exercício 013</title>
    <link rel="stylesheet" href="style.css?v=3">
</head>
<body>
    <div class="container">
        <div class="card">
            <h1><?= $iconeCalculadora ?> Calculadora Científica</h1>
            <div class="elementos" aria-hidden="true">
                <?php foreach ($elementos as $chave => [$nome, $svg]): ?>
                    <span class="elemento el-<?= e($chave) ?>" title="<?= e($nome) ?>"><?= $svg ?></span>
                <?php endforeach; ?>
            </div>

            <nav class="abas" aria-label="Modo da calculadora">
                <a class="aba<?= $modo === 'basica' ? ' ativa' : '' ?>" href="?modo=basica"<?= $modo === 'basica' ? ' aria-current="page"' : '' ?>>6 Operações</a>
                <a class="aba<?= $modo === 'cientifica' ? ' ativa' : '' ?>" href="?modo=cientifica"<?= $modo === 'cientifica' ? ' aria-current="page"' : '' ?>>Científica</a>
            </nav>

<?php if ($modo === 'basica'): ?>
            <form method="post" action="?modo=basica" novalidate>
                <input type="hidden" name="formulario" value="basica">
                <div class="input-group">
                    <div class="input-field">
                        <label for="num1">Primeiro Número</label>
                        <input type="text" inputmode="decimal" id="num1" name="num1" value="<?= e($num1) ?>" placeholder="Insira um número" autocomplete="off">
                    </div>
                    <div class="input-field">
                        <label for="num2">Segundo Número</label>
                        <input type="text" inputmode="decimal" id="num2" name="num2" value="<?= e($num2) ?>" placeholder="Insira um número" autocomplete="off">
                    </div>
                </div>

                <button type="submit">Calcular</button>
            </form>

            <?php if ($erroBasico !== null): ?>
                <div class="erro show" role="alert"><?= e($erroBasico) ?></div>
            <?php endif; ?>

            <div class="resultados-area">
                <?php foreach ($operacoes as $chave => [$simbolo, $nome, $sinal]): ?>
                    <?php $r = $resultadosBasicos[$chave] ?? null; ?>
                    <div class="resultado-item <?= e($chave) ?>">
                        <span class="resultado-icon"><?= e($simbolo) ?></span>
                        <div class="resultado-conteudo">
                            <h3><?= e($nome) ?></h3>
                            <?php if ($r === null): ?>
                                <p>Aguardando números...</p>
                            <?php else: ?>
                                <p>
                                    <span class="valor"><?= e(is_float($r) ? Calculadora::formatar($r) : $r) ?></span>
                                    <span class="conta">(<?= e(Calculadora::formatar(lerNumero($num1) ?? 0)) ?> <?= e($sinal) ?> <?= e(Calculadora::formatar(lerNumero($num2) ?? 0)) ?>)</span>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

<?php else: ?>
            <form method="post" action="?modo=cientifica" id="formCientifica" autocomplete="off" novalidate>
                <input type="hidden" name="formulario" value="cientifica">
                <div class="input-field">
                    <label for="expressao">Expressão</label>
                    <input type="text" id="expressao" name="expressao" value="<?= e($expressao) ?>" maxlength="200"
                           placeholder="0" spellcheck="false" class="visor">
                </div>

                <div class="linha-opcoes">
                    <div class="input-field">
                        <label for="angulo">Ângulos</label>
                        <select id="angulo" name="angulo">
                            <option value="deg"<?= $angulo === 'deg' ? ' selected' : '' ?>>Graus</option>
                            <option value="rad"<?= $angulo === 'rad' ? ' selected' : '' ?>>Radianos</option>
                        </select>
                    </div>
                    <p class="dica">Usa <code>ans</code> para o último resultado.</p>
                </div>

                <?php if ($erroCientifico !== null): ?>
                    <div class="erro show" role="alert"><?= e($erroCientifico) ?></div>
                <?php endif; ?>

                <?php
                // [texto mostrado, texto inserido, classe]
                $teclas = [
                    ['sin', 'sin(', ''], ['cos', 'cos(', ''], ['tan', 'tan(', ''], ['ln', 'ln(', ''], ['log', 'log(', ''], ['C', '', 'limpar'],
                    ['asin', 'asin(', ''], ['acos', 'acos(', ''], ['atan', 'atan(', ''], ['√', 'sqrt(', ''], ['∛', 'cbrt(', ''], ['⌫', '', 'limpar'],
                    ['π', 'pi', ''], ['e', 'e', ''], ['ans', 'ans', ''], ['x!', '!', ''], ['xʸ', '^', 'op'], ['mod', '%', 'op'],
                    ['(', '(', ''], [')', ')', ''], ['7', '7', ''], ['8', '8', ''], ['9', '9', ''], ['÷', '/', 'op'],
                    ['exp', 'exp(', ''], ['|x|', 'abs(', ''], ['4', '4', ''], ['5', '5', ''], ['6', '6', ''], ['×', '*', 'op'],
                    ['1/x', '1/(', ''], ['x²', '^2', ''], ['1', '1', ''], ['2', '2', ''], ['3', '3', ''], ['−', '-', 'op'],
                    ['±', '-', ''], ['.', '.', ''], ['0', '0', ''], [',', '.', ''], ['=', '=', 'igual'], ['+', '+', 'op'],
                ];
                ?>
                <div class="teclado" role="group" aria-label="Teclado">
                    <?php foreach ($teclas as [$texto, $insere, $classe]): ?>
                        <?php if ($texto === 'C'): ?>
                            <button type="button" class="tecla limpar" data-limpar>C</button>
                        <?php elseif ($texto === '⌫'): ?>
                            <button type="button" class="tecla limpar" data-apagar aria-label="Apagar último carácter">⌫</button>
                        <?php elseif ($texto === '='): ?>
                            <button type="submit" class="tecla igual">=</button>
                        <?php else: ?>
                            <button type="button" class="tecla <?= e(trim($classe . ' ' . (isset($corTecla[$texto]) ? 'el-' . $corTecla[$texto] : ''))) ?>" data-insere="<?= e($insere) ?>"><?= e($texto) ?></button>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </form>

            <?php if ($resultadoCientifico !== null): ?>
                <div class="resultados-area uma-coluna">
                    <div class="resultado-item">
                        <span class="resultado-icon">=</span>
                        <div class="resultado-conteudo">
                            <h3><?= e($expressao) ?></h3>
                            <p><span class="valor valor-grande"><?= e($resultadoCientifico) ?></span></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($historico !== []): ?>
                <div class="historico">
                    <h3>Últimos cálculos</h3>
                    <ol>
                        <?php foreach ($historico as $linha): ?>
                            <li><?= e($linha) ?></li>
                        <?php endforeach; ?>
                    </ol>
                </div>
            <?php endif; ?>
<?php endif; ?>

        </div>
    </div>

    <script src="teclado.js"></script>
</body>
</html>
