<?php
/**
 * Exercício 013 · Calculadora Científica (com PHP)
 *
 * Evolução da calculadora de 4 operações em JavaScript: agora o cálculo é feito
 * no servidor, com validação, e há um modo científico com expressões completas.
 * A interface abre no idioma do navegador (ver idiomas.php).
 */

declare(strict_types=1);

require_once __DIR__ . '/calculadora.php';
require_once __DIR__ . '/extras.php';
require_once __DIR__ . '/idiomas.php';
require_once __DIR__ . '/manual.php';

ini_set('display_errors', '0');
session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
session_start();

idiomaAtivo(escolherIdioma());

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
 * Faz uma das operações básicas. Devolve o resultado ou a CHAVE de uma mensagem de erro
 * (que a página traduz com t()).
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
            return $b == 0.0 ? 'err_div0' : $a / $b;
        case 'modulo':
            return $b == 0.0 ? 'err_mod0' : fmod($a, $b);
        case 'potencia':
            $r = $a ** $b;
            return (is_nan($r) || is_infinite($r)) ? 'err_indefinido' : $r;
        default:
            return 'err_operacao';
    }
}

// [chave => [símbolo, símbolo na conta]]; o nome vem de t('op_' . chave)
$operacoes = [
    'soma'          => ['+', '+'],
    'subtracao'     => ['−', '-'],
    'multiplicacao' => ['×', '×'],
    'divisao'       => ['÷', '÷'],
    'potencia'      => ['xʸ', '^'],
    'modulo'        => ['%', 'mod'],
];

$modo = match ($_GET['modo'] ?? 'basica') {
    'cientifica' => 'cientifica',
    'desafio'    => 'desafio',
    'manual'     => 'manual',
    default      => 'basica',
};

// ---------------- Modo 6 operações ----------------
$num1 = $num2 = '';
$resultadosBasicos = [];
$erroBasico = null;

// ---------------- Modo científico ----------------
$expressao = '';
$angulo = 'deg';
$resultadoCientifico = null;
$erroCientifico = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formulario = $_POST['formulario'] ?? '';

    if ($formulario === 'basica') {
        $modo = 'basica';
        $num1 = is_string($_POST['num1'] ?? null) ? trim($_POST['num1']) : '';
        $num2 = is_string($_POST['num2'] ?? null) ? trim($_POST['num2']) : '';
        $a = lerNumero($num1);
        $b = lerNumero($num2);

        if ($num1 === '' || $num2 === '') {
            $erroBasico = t('err_vazio');
        } elseif ($a === null || $b === null) {
            $erroBasico = t('err_invalido');
        } else {
            foreach ($operacoes as $chave => $info) {
                $resultadosBasicos[$chave] = operar($chave, $a, $b);
            }
        }
    } elseif ($formulario === 'cientifica') {
        $modo = 'cientifica';
        $expressao = is_string($_POST['expressao'] ?? null) ? trim($_POST['expressao']) : '';
        $angulo = ($_POST['angulo'] ?? 'deg') === 'rad' ? 'rad' : 'deg';
        try {
            $valor = Calculadora::avaliar($expressao, $angulo, (float) ($_SESSION['ans'] ?? 0));
            $resultadoCientifico = Calculadora::formatar($valor);
            $_SESSION['ans'] = $valor;
            $_SESSION['historico'] = array_slice(
                array_merge([['e' => $expressao, 'r' => $resultadoCientifico]], $_SESSION['historico'] ?? []),
                0,
                10
            );
        } catch (ErroCalculo $ex) {
            $erroCientifico = traduzirErro($ex->getMessage());
        }
    } elseif ($formulario === 'limpar_historico') {
        unset($_SESSION['historico']);
        header('Location: ?modo=cientifica');
        exit;
    }
}

// ---------------- Modo desafio ----------------
// Pergunta guardada na sessão; cada resposta é validada no servidor e o resultado
// volta como "feedback" de uma só leitura (Post/Redirect/Get).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['formulario'] ?? '') === 'desafio') {
    $accao = is_string($_POST['accao'] ?? null) ? $_POST['accao'] : 'responder';
    $atual = $_SESSION['desafio'] ?? null;
    $sequencia = (int) ($_SESSION['sequencia'] ?? 0);
    $pontos = (int) ($_SESSION['pontos'] ?? 0);
    $novaPergunta = true;
    $mensagemFeedback = null;

    if ($accao === 'reiniciar') {
        unset($_SESSION['desafio'], $_SESSION['pontos'], $_SESSION['sequencia'], $_SESSION['melhor']);
        $novaPergunta = false;
    } elseif (is_array($atual) && isset($atual['pergunta'], $atual['resposta'], $atual['elemento'])) {
        $esperada = (float) $atual['resposta'];
        $textoEsperado = Calculadora::formatar($esperada);
        $elemento = (string) $atual['elemento'];
        $conta = (string) $atual['pergunta'];

        if ($accao === 'saltar') {
            $mensagemFeedback = ['estado' => 'erro', 'mensagem' => t('fb_saltou', $conta, $textoEsperado), 'elemento' => $elemento];
            $sequencia = 0;
        } else {
            $dada = lerNumero($_POST['resposta'] ?? null);
            if ($dada === null) {
                $mensagemFeedback = ['estado' => 'aviso', 'mensagem' => t('aviso_numero'), 'elemento' => $elemento];
                $novaPergunta = false;
            } elseif (respostaCerta($dada, $esperada)) {
                $ganhos = pontosDaResposta($sequencia);
                $pontos += $ganhos;
                $sequencia++;
                $subiu = nivelDesafio($sequencia) > nivelDesafio($sequencia - 1);
                $mensagemFeedback = [
                    'estado'   => 'ok',
                    'mensagem' => t($subiu ? 'fb_certo_nivel' : 'fb_certo', $conta, $textoEsperado, $ganhos),
                    'elemento' => $elemento,
                    'subiu'    => $subiu,
                ];
                $_SESSION['melhor'] = max((int) ($_SESSION['melhor'] ?? 0), $sequencia);
            } else {
                $mensagemFeedback = ['estado' => 'erro', 'mensagem' => t('fb_errado', $conta, $textoEsperado), 'elemento' => $elemento];
                $sequencia = 0;
            }
        }
    }

    $_SESSION['pontos'] = $pontos;
    $_SESSION['sequencia'] = $sequencia;
    if ($novaPergunta) {
        $_SESSION['desafio'] = gerarDesafio($sequencia);
    }
    if ($mensagemFeedback !== null) {
        $_SESSION['feedback'] = $mensagemFeedback;
    }
    header('Location: ?modo=desafio');
    exit;
}

$desafio = null;
$feedback = null;
$pontos = (int) ($_SESSION['pontos'] ?? 0);
$sequencia = (int) ($_SESSION['sequencia'] ?? 0);
$melhor = (int) ($_SESSION['melhor'] ?? 0);
if ($modo === 'desafio') {
    if (!is_array($_SESSION['desafio'] ?? null)) {
        $_SESSION['desafio'] = gerarDesafio($sequencia);
    }
    $desafio = $_SESSION['desafio'];
    $feedback = $_SESSION['feedback'] ?? null;
    unset($_SESSION['feedback']);
}

// ---------------- Estado para os efeitos (certo / errado / poderes usados) ----------------
$estado = 'neutro';
$poderes = [];
if ($modo === 'basica') {
    if ($erroBasico !== null) {
        $estado = 'erro';
    } elseif ($resultadosBasicos !== []) {
        $estado = array_filter($resultadosBasicos, 'is_string') !== [] ? 'erro' : 'ok';
        $poderes = ['terra', 'agua', 'fogo', 'ar', 'energia'];
    }
} elseif ($modo === 'cientifica') {
    $poderes = poderesUsados($expressao);
    if ($erroCientifico !== null) {
        $estado = 'erro';
    } elseif ($resultadoCientifico !== null) {
        $estado = 'ok';
    }
} elseif ($modo === 'desafio') {
    $estado = ['ok' => 'ok', 'erro' => 'erro'][$feedback['estado'] ?? ''] ?? 'neutro';
    $poderes = [(string) ($feedback['elemento'] ?? $desafio['elemento'])];
}

// histórico: só entradas no formato novo
$historico = array_values(array_filter(
    $_SESSION['historico'] ?? [],
    static fn ($linha) => is_array($linha) && isset($linha['e'], $linha['r'])
));

$ico = static fn (string $d): string => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>';
$iconesMenu = [
    'basica'     => $ico('<rect x="5" y="3" width="14" height="18" rx="2.5"/><path d="M8.5 7.5h7M9 12h.01M12 12h.01M15 12h.01M9 16h.01M12 16h.01M15 16h.01"/>'),
    'cientifica' => $ico('<path d="M18 5H7l6 7-6 7h11"/>'),
    'desafio'    => $ico('<path d="M8 4h8v5a4 4 0 0 1-8 0V4z"/><path d="M8 6H5a3 3 0 0 0 3 4M16 6h3a3 3 0 0 1-3 4M12 13v4M9 20h6"/>'),
    'manual'     => $ico('<path d="M5 4.5A1.5 1.5 0 0 1 6.5 3H19v15H6.5A1.5 1.5 0 0 0 5 19.5v-15z"/><path d="M5 19.5A1.5 1.5 0 0 0 6.5 21H19v-3"/>'),
];
$iconeIdioma = $ico('<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3.2 3 14.8 0 18M12 3c-3 3.2-3 14.8 0 18"/>');
$iconeDesfazer = $ico('<path d="M9 14L4 9l5-5"/><path d="M4 9h10a6 6 0 0 1 0 12h-3"/>');
$iconeRefazer = $ico('<path d="M15 14l5-5-5-5"/><path d="M20 9H10a6 6 0 0 0 0 12h3"/>');
$iconeCopiar = $ico('<rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V6a2 2 0 0 1 2-2h9"/>');
$elementos = [
    'agua'    => $ico('<path d="M12 3C12 3 5.5 10.2 5.5 14.5a6.5 6.5 0 0 0 13 0C18.5 10.2 12 3 12 3z"/>'),
    'fogo'    => $ico('<path d="M12 3c1 3.5 5 5.5 5 10a5 5 0 0 1-10 0c0-2 1-3.2 2-4.2.2 1.4 1 2.2 1.8 2.2C10.5 8 11 5.5 12 3z"/>'),
    'terra'   => $ico('<path d="M5 19C5 10 10 5 19 5c0 9-5 14-14 14z"/><path d="M5 19l8-8"/>'),
    'ar'      => $ico('<path d="M3 9h10a3 3 0 1 0-3-3"/><path d="M3 14h14a3 3 0 1 1-3 3"/><path d="M3 19h5"/>'),
    'energia' => $ico('<polygon points="12 3 14.6 9 21 9.6 16.2 13.8 17.7 20.2 12 16.8 6.3 20.2 7.8 13.8 3 9.6 9.4 9"/>'),
];
// cor (elemento) de cada tecla científica, pelo texto mostrado
$corTecla = [
    'sin' => 'ar', 'cos' => 'ar', 'tan' => 'ar', 'asin' => 'ar', 'acos' => 'ar', 'atan' => 'ar',
    'ln' => 'terra', 'log' => 'terra', 'exp' => 'terra', '√' => 'terra', '∛' => 'terra', '|x|' => 'terra', '1/x' => 'terra', 'x²' => 'terra', 'x!' => 'terra',
    'π' => 'agua', 'e' => 'agua', 'ans' => 'agua', '(' => 'agua', ')' => 'agua',
];

/** Endereço de um modo, mantendo o idioma escolhido. */
function ligacao(string $modo, ?string $idioma = null): string
{
    return '?modo=' . rawurlencode($modo) . ($idioma !== null ? '&lang=' . rawurlencode($idioma) : '');
}
?>
<!DOCTYPE html>
<html lang="<?= e(t('html_lang')) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(t('titulo')) ?> · <?= e(t('marca')) ?></title>
    <link rel="icon" type="image/svg+xml" href="logo.svg">
    <link rel="stylesheet" href="style.css?v=<?= e(VERSAO) ?>">
</head>
<body data-modo="<?= e($modo) ?>" data-estado="<?= e($estado) ?>" data-poderes="<?= e(implode(',', $poderes)) ?>"<?= ($feedback['subiu'] ?? false) ? ' data-subiu="1"' : '' ?>>
    <div class="app">
        <aside class="lateral">
            <a class="marca" href="<?= e(ligacao('basica')) ?>">
                <img class="logo" src="logo.svg" width="44" height="44" alt="">
                <span class="marca-texto">
                    <span class="marca-nome"><?= e(t('marca')) ?></span>
                    <span class="marca-sub"><?= e(t('marca_sub')) ?></span>
                </span>
            </a>

            <nav class="menu" aria-label="<?= e(t('menu_aria')) ?>">
                <?php foreach (['basica', 'cientifica', 'desafio', 'manual'] as $entrada): ?>
                    <a class="menu-item<?= $modo === $entrada ? ' ativa' : '' ?>" href="<?= e(ligacao($entrada)) ?>"<?= $modo === $entrada ? ' aria-current="page"' : '' ?>>
                        <?= $iconesMenu[$entrada] ?>
                        <span><?= e(t($entrada === 'basica' ? 'aba_basica' : 'aba_' . $entrada)) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="lateral-base">
                <form method="get" action="" class="idioma-form">
                    <input type="hidden" name="modo" value="<?= e($modo) ?>">
                    <label for="idioma"><?= $iconeIdioma ?> <span><?= e(t('idioma_aria')) ?></span></label>
                    <select id="idioma" name="lang">
                        <?php foreach (IDIOMAS_NOMES as $codigo => $nome): ?>
                            <option value="<?= e($codigo) ?>" lang="<?= e($codigo) ?>"<?= $codigo === idiomaAtivo() ? ' selected' : '' ?>><?= e($nome) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <noscript><button type="submit" class="btn-idioma">OK</button></noscript>
                </form>
                <p class="versao">v<?= e(VERSAO) ?></p>
            </div>
        </aside>

        <main class="principal">
        <div class="card">
            <div class="elementos" aria-hidden="true">
                <?php foreach ($elementos as $chave => $svg): ?>
                    <span class="elemento el-<?= e($chave) ?>" data-el="<?= e($chave) ?>" title="<?= e(nomeElemento($chave)) ?>"><?= $svg ?></span>
                <?php endforeach; ?>
            </div>

<?php if ($modo === 'basica'): ?>
            <form method="post" action="<?= e(ligacao('basica')) ?>" novalidate>
                <input type="hidden" name="formulario" value="basica">
                <div class="input-group">
                    <div class="input-field">
                        <label for="num1"><?= e(t('num1')) ?></label>
                        <input type="text" inputmode="decimal" id="num1" name="num1" value="<?= e($num1) ?>" placeholder="<?= e(t('num_ph')) ?>" autocomplete="off">
                    </div>
                    <div class="input-field">
                        <label for="num2"><?= e(t('num2')) ?></label>
                        <input type="text" inputmode="decimal" id="num2" name="num2" value="<?= e($num2) ?>" placeholder="<?= e(t('num_ph')) ?>" autocomplete="off">
                    </div>
                </div>

                <button type="submit"><?= e(t('calcular')) ?></button>
            </form>

            <?php if ($erroBasico !== null): ?>
                <div class="erro show" role="alert"><?= e($erroBasico) ?></div>
            <?php endif; ?>

            <div class="resultados-area">
                <?php foreach ($operacoes as $chave => [$simbolo, $sinal]): ?>
                    <?php $r = $resultadosBasicos[$chave] ?? null; ?>
                    <div class="resultado-item <?= e($chave) ?><?= is_string($r) ? ' deu-erro' : '' ?>">
                        <span class="resultado-icon"><?= e($simbolo) ?></span>
                        <div class="resultado-conteudo">
                            <h3><?= e(t('op_' . $chave)) ?></h3>
                            <?php if ($r === null): ?>
                                <p><?= e(t('aguardando')) ?></p>
                            <?php elseif (is_string($r)): ?>
                                <p><span class="valor"><?= e(t($r)) ?></span></p>
                            <?php else: ?>
                                <p>
                                    <span class="valor"><?= e(Calculadora::formatar($r)) ?></span>
                                    <span class="conta">(<?= e(Calculadora::formatar(lerNumero($num1) ?? 0)) ?> <?= e($sinal) ?> <?= e(Calculadora::formatar(lerNumero($num2) ?? 0)) ?>)</span>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

<?php elseif ($modo === 'cientifica'): ?>
            <div class="grade-calc">
                <div class="coluna-principal">
                    <form method="post" action="<?= e(ligacao('cientifica')) ?>" id="formCientifica" autocomplete="off" novalidate data-ans="<?= e((string) ($_SESSION['ans'] ?? 0)) ?>">
                        <input type="hidden" name="formulario" value="cientifica">
                        <div class="input-field">
                            <div class="visor-topo">
                                <label for="expressao"><?= e(t('expressao')) ?></label>
                                <div class="visor-ferramentas">
                                    <button type="button" class="chip-angulo" id="anguloBadge" aria-label="<?= e(t('angulo_alternar')) ?>" title="<?= e(t('angulo_alternar')) ?>"><?= $angulo === 'rad' ? 'RAD' : 'DEG' ?></button>
                                    <button type="button" class="btn-icone" id="desfazer" aria-label="<?= e(t('desfazer')) ?>" title="<?= e(t('desfazer')) ?> (Ctrl+Z)" disabled><?= $iconeDesfazer ?></button>
                                    <button type="button" class="btn-icone" id="refazer" aria-label="<?= e(t('refazer')) ?>" title="<?= e(t('refazer')) ?> (Ctrl+Y)" disabled><?= $iconeRefazer ?></button>
                                </div>
                            </div>
                            <input type="text" id="expressao" name="expressao" value="<?= e($expressao) ?>" maxlength="200"
                                   placeholder="0" spellcheck="false" class="visor">
                            <div class="mistura" id="mistura" aria-hidden="true"></div>
                        </div>

                        <?php if ($erroCientifico !== null): ?>
                            <div class="erro show" role="alert"><?= e($erroCientifico) ?></div>
                        <?php endif; ?>

                        <?php if ($resultadoCientifico !== null): ?>
                            <div class="resultados-area uma-coluna">
                                <div class="resultado-item mistura-borda" data-poderes="<?= e(implode(',', $poderes)) ?>">
                                    <span class="resultado-icon">=</span>
                                    <div class="resultado-conteudo">
                                        <h3><?= e($expressao) ?></h3>
                                        <p class="valor-linha">
                                            <span class="valor valor-grande" id="valorResultado"><?= e($resultadoCientifico) ?></span>
                                            <button type="button" class="btn-icone btn-copiar" id="copiar" data-valor="<?= e($resultadoCientifico) ?>" data-copiado="<?= e(t('copiado')) ?>" aria-label="<?= e(t('copiar')) ?>" title="<?= e(t('copiar')) ?>"><?= $iconeCopiar ?></button>
                                        </p>
                                        <?php if ($poderes !== []): ?>
                                            <p class="poderes">
                                                <?php foreach ($poderes as $p): ?>
                                                    <span class="poder el-<?= e($p) ?>"><?= $elementos[$p] ?> <?= e(nomeElemento($p)) ?></span>
                                                <?php endforeach; ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="linha-opcoes">
                            <div class="input-field">
                                <label for="angulo"><?= e(t('angulos')) ?></label>
                                <select id="angulo" name="angulo">
                                    <option value="deg"<?= $angulo === 'deg' ? ' selected' : '' ?>><?= e(t('graus')) ?></option>
                                    <option value="rad"<?= $angulo === 'rad' ? ' selected' : '' ?>><?= e(t('radianos')) ?></option>
                                </select>
                            </div>
                            <p class="dica"><?= sprintf(e(t('dica_ans')), '<code>ans</code>') ?></p>
                        </div>

                        <div class="memoria" role="group" aria-label="<?= e(t('memoria')) ?>">
                            <button type="button" data-mem="mc">MC</button>
                            <button type="button" data-mem="mr">MR</button>
                            <button type="button" data-mem="mmais">M+</button>
                            <button type="button" data-mem="mmenos">M−</button>
                            <span class="memoria-estado" id="memoriaEstado" data-vazia="<?= e(t('memoria_vazia')) ?>" data-rotulo="<?= e(t('memoria')) ?>"></span>
                        </div>

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
                        <div class="teclado" role="group" aria-label="<?= e(t('teclado')) ?>">
                            <?php foreach ($teclas as [$texto, $insere, $classe]): ?>
                                <?php if ($texto === 'C'): ?>
                                    <button type="button" class="tecla limpar" data-limpar>C</button>
                                <?php elseif ($texto === '⌫'): ?>
                                    <button type="button" class="tecla limpar" data-apagar aria-label="<?= e(t('apagar')) ?>">⌫</button>
                                <?php elseif ($texto === '='): ?>
                                    <button type="submit" class="tecla igual">=</button>
                                <?php else: ?>
                                    <button type="button" class="tecla <?= e(trim($classe . ' ' . (isset($corTecla[$texto]) ? 'el-' . $corTecla[$texto] : ''))) ?>" data-insere="<?= e($insere) ?>"><?= e($texto) ?></button>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </form>
                </div>

                <aside class="coluna-lateral" aria-label="<?= e(t('historico')) ?>">
                    <div class="historico-topo">
                        <h2><?= e(t('historico')) ?></h2>
                        <?php if ($historico !== []): ?>
                            <form method="post" action="<?= e(ligacao('cientifica')) ?>">
                                <input type="hidden" name="formulario" value="limpar_historico">
                                <button type="submit" class="btn-limpar"><?= e(t('historico_limpar')) ?></button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <?php if ($historico === []): ?>
                        <p class="historico-vazio"><?= e(t('historico_vazio')) ?></p>
                    <?php else: ?>
                        <ol class="historico-lista">
                            <?php foreach ($historico as $linha): ?>
                                <li>
                                    <button type="button" class="hist-item" data-expr="<?= e($linha['e']) ?>">
                                        <span class="hist-exp"><?= e($linha['e']) ?></span>
                                        <span class="hist-res">= <?= e($linha['r']) ?></span>
                                    </button>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                        <p class="historico-dica"><?= e(t('historico_dica')) ?></p>
                    <?php endif; ?>
                </aside>
            </div>
<?php elseif ($modo === 'manual'): ?>
            <?php $secoes = secoesDoManual(); ?>
            <article class="manual">
                <h2 class="manual-titulo"><?= e(t('aba_manual')) ?></h2>
                <nav class="manual-indice" aria-label="<?= e(t('manual_indice')) ?>">
                    <?php foreach ($secoes as $secao): ?>
                        <a href="#<?= e($secao['id']) ?>"><?= e($secao['titulo']) ?></a>
                    <?php endforeach; ?>
                </nav>

                <?php foreach ($secoes as $posicao => $secao): ?>
                    <section class="manual-secao el-<?= e(['agua', 'fogo', 'terra', 'ar', 'energia'][$posicao % 5]) ?>" id="<?= e($secao['id']) ?>">
                        <h3><?= e($secao['titulo']) ?></h3>
                        <?php if (isset($secao['texto'])): ?>
                            <p><?= e($secao['texto']) ?></p>
                        <?php endif; ?>
                        <?php if (isset($secao['itens'])): ?>
                            <dl>
                                <?php foreach ($secao['itens'] as [$termo, $descricao]): ?>
                                    <div class="manual-item">
                                        <dt><?= e($termo) ?></dt>
                                        <dd><?= e($descricao) ?></dd>
                                    </div>
                                <?php endforeach; ?>
                            </dl>
                        <?php endif; ?>
                    </section>
                <?php endforeach; ?>
            </article>
<?php else: ?>
            <?php $nivel = (int) $desafio['nivel']; $faltam = 3 - ($sequencia % 3); ?>
            <div class="placar">
                <div><span><?= e(t('pontos')) ?></span><strong><?= e($pontos) ?></strong></div>
                <div><span><?= e(t('sequencia')) ?></span><strong><?= e($sequencia) ?></strong></div>
                <div><span><?= e(t('melhor')) ?></span><strong><?= e($melhor) ?></strong></div>
                <div><span><?= e(t('nivel')) ?></span><strong><?= e($nivel) ?></strong></div>
            </div>
            <?php if ($nivel < 4): ?>
                <div class="progresso" role="progressbar" aria-label="<?= e(t('progresso')) ?>" aria-valuemin="0" aria-valuemax="3" aria-valuenow="<?= e($sequencia % 3) ?>">
                    <span style="width: <?= e((string) round(($sequencia % 3) / 3 * 100)) ?>%"></span>
                </div>
                <p class="dica dica-desafio"><?= e(t($faltam === 1 ? 'mais_acerto_1' : 'mais_acerto_n', $faltam, $nivel + 1)) ?></p>
            <?php endif; ?>

            <?php if ($feedback !== null): ?>
                <div class="feedback <?= e($feedback['estado']) ?>" role="status"><?= e($feedback['mensagem']) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= e(ligacao('desafio')) ?>" autocomplete="off" novalidate>
                <input type="hidden" name="formulario" value="desafio">
                <div class="pergunta el-<?= e($desafio['elemento']) ?>">
                    <span class="pergunta-poder"><?= $elementos[$desafio['elemento']] ?> <?= e(nomeElemento($desafio['elemento'])) ?></span>
                    <div class="pergunta-conta"><?= e($desafio['pergunta']) ?> = ?</div>
                </div>
                <div class="input-field">
                    <label for="resposta"><?= e(t('resposta')) ?></label>
                    <input type="text" inputmode="decimal" id="resposta" name="resposta" placeholder="0" class="visor" autofocus>
                </div>
                <button type="submit" name="accao" value="responder"><?= e(t('responder')) ?></button>
                <div class="acoes-desafio">
                    <button type="submit" name="accao" value="saltar" class="secundario"><?= e(t('saltar')) ?></button>
                    <button type="submit" name="accao" value="reiniciar" class="secundario"><?= e(t('reiniciar')) ?></button>
                </div>
            </form>
<?php endif; ?>

        </div>
        </main>
    </div>

    <script src="geral.js?v=<?= e(VERSAO) ?>"></script>
    <script src="teclado.js?v=<?= e(VERSAO) ?>"></script>
    <script src="efeitos.js?v=<?= e(VERSAO) ?>"></script>
</body>
</html>
