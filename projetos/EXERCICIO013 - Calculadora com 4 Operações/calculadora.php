<?php
/**
 * calculadora.php — interpretador seguro de expressões matemáticas (modo científico).
 *
 * Nunca usa eval(): a expressão é dividida em tokens e avaliada por descida
 * recursiva, respeitando a precedência (parênteses, !, ^, unário, * / %, + -).
 * Só reconhece números, operadores, constantes e as funções da lista branca.
 */

declare(strict_types=1);

final class ErroCalculo extends RuntimeException
{
}

final class Calculadora
{
    private const MAX_TAMANHO = 200;
    private const MAX_PROFUNDIDADE = 40;

    private const CONSTANTES = ['pi', 'e'];
    private const FUNCOES = [
        'sqrt', 'cbrt', 'abs', 'exp', 'ln', 'log',
        'sin', 'cos', 'tan', 'asin', 'acos', 'atan',
    ];

    /** @var array<int, array{0:string,1:mixed}> */
    private array $tokens = [];
    private int $pos = 0;
    private int $profundidade = 0;

    private function __construct(private string $modoAngulo, private float $ans)
    {
    }

    /**
     * Avalia a expressão e devolve o resultado.
     *
     * @param string $modoAngulo 'deg' (graus) ou 'rad' (radianos)
     * @throws ErroCalculo se a expressão for inválida ou o resultado indefinido
     */
    public static function avaliar(string $expressao, string $modoAngulo = 'deg', float $ans = 0.0): float
    {
        $expressao = trim($expressao);
        if ($expressao === '') {
            throw new ErroCalculo('Escreve uma expressão para calcular.');
        }
        if (mb_strlen($expressao) > self::MAX_TAMANHO) {
            throw new ErroCalculo('A expressão é demasiado longa.');
        }

        $calc = new self($modoAngulo === 'rad' ? 'rad' : 'deg', $ans);
        $calc->tokens = $calc->tokenizar($expressao);
        $valor = $calc->expressao();

        if ($calc->pos < count($calc->tokens)) {
            throw new ErroCalculo('Expressão inválida perto de "' . $calc->descrever($calc->tokens[$calc->pos]) . '".');
        }
        return self::validarResultado($valor);
    }

    /** Formata o resultado sem zeros inúteis (e em notação científica se for enorme). */
    public static function formatar(float $valor): string
    {
        if ($valor == 0.0) {
            return '0';
        }
        if (abs($valor) >= 1e15 || abs($valor) < 1e-9) {
            return str_replace('E+', 'e', sprintf('%.8E', $valor));
        }
        $texto = number_format($valor, 10, '.', '');
        $texto = rtrim(rtrim($texto, '0'), '.');
        return $texto === '-0' ? '0' : $texto;
    }

    // ------------------------------------------------------------
    // Tokens
    // ------------------------------------------------------------

    private function tokenizar(string $texto): array
    {
        $texto = strtolower(str_replace(['×', '÷', 'π', '−', ','], ['*', '/', 'pi', '-', '.'], $texto));
        $tokens = [];
        $tamanho = strlen($texto);
        $i = 0;

        while ($i < $tamanho) {
            $c = $texto[$i];
            if (ctype_space($c)) {
                $i++;
            } elseif (ctype_digit($c) || $c === '.') {
                if (!preg_match('/\G(\d+\.?\d*|\.\d+)/', $texto, $m, 0, $i)) {
                    throw new ErroCalculo('Número inválido.');
                }
                $tokens[] = ['num', (float) $m[1]];
                $i += strlen($m[1]);
            } elseif (ctype_alpha($c)) {
                preg_match('/\G[a-z]+/', $texto, $m, 0, $i);
                $tokens[] = ['id', $m[0]];
                $i += strlen($m[0]);
            } elseif (strpos('+-*/%^()!', $c) !== false) {
                $tokens[] = ['op', $c];
                $i++;
            } else {
                throw new ErroCalculo('Carácter não permitido: "' . $c . '".');
            }
        }
        return $tokens;
    }

    private function descrever(array $token): string
    {
        return is_float($token[1]) ? self::formatar($token[1]) : (string) $token[1];
    }

    private function atual(): ?array
    {
        return $this->tokens[$this->pos] ?? null;
    }

    private function eOperador(string $op): bool
    {
        $t = $this->atual();
        return $t !== null && $t[0] === 'op' && $t[1] === $op;
    }

    // ------------------------------------------------------------
    // Gramática (da menor para a maior precedência)
    // ------------------------------------------------------------

    private function expressao(): float
    {
        if (++$this->profundidade > self::MAX_PROFUNDIDADE) {
            throw new ErroCalculo('Demasiados parênteses encadeados.');
        }
        $valor = $this->termo();
        while ($this->eOperador('+') || $this->eOperador('-')) {
            $op = $this->tokens[$this->pos++][1];
            $direita = $this->termo();
            $valor = $op === '+' ? $valor + $direita : $valor - $direita;
        }
        $this->profundidade--;
        return $valor;
    }

    private function termo(): float
    {
        $valor = $this->unario();
        while (true) {
            if ($this->eOperador('*') || $this->eOperador('/') || $this->eOperador('%')) {
                $op = $this->tokens[$this->pos++][1];
                $direita = $this->unario();
                if ($op === '*') {
                    $valor *= $direita;
                } elseif ($direita == 0.0) {
                    throw new ErroCalculo($op === '/' ? 'Erro: divisão por zero!' : 'Erro: módulo por zero!');
                } elseif ($op === '/') {
                    $valor /= $direita;
                } else {
                    $valor = fmod($valor, $direita);
                }
                continue;
            }
            // multiplicação implícita: 2pi, 3(4+1), 2sin(30)
            $t = $this->atual();
            if ($t !== null && ($t[0] === 'id' || ($t[0] === 'op' && $t[1] === '('))) {
                $valor *= $this->unario();
                continue;
            }
            return $valor;
        }
    }

    private function unario(): float
    {
        if ($this->eOperador('-')) {
            $this->pos++;
            return -$this->unario();
        }
        if ($this->eOperador('+')) {
            $this->pos++;
            return $this->unario();
        }
        return $this->potencia();
    }

    private function potencia(): float
    {
        $base = $this->posfixo();
        if ($this->eOperador('^')) {
            $this->pos++;
            $expoente = $this->unario(); // associa à direita e aceita 2^-1
            if ($base == 0.0 && $expoente < 0) {
                throw new ErroCalculo('Erro: divisão por zero!');
            }
            if ($base < 0 && floor($expoente) != $expoente) {
                throw new ErroCalculo('Não existe potência real de base negativa com expoente fracionário.');
            }
            return self::validarResultado($base ** $expoente);
        }
        return $base;
    }

    private function posfixo(): float
    {
        $valor = $this->primario();
        while ($this->eOperador('!')) {
            $this->pos++;
            $valor = self::fatorial($valor);
        }
        return $valor;
    }

    private function primario(): float
    {
        $t = $this->atual();
        if ($t === null) {
            throw new ErroCalculo('A expressão está incompleta.');
        }
        $this->pos++;

        if ($t[0] === 'num') {
            return $t[1];
        }
        if ($t[0] === 'op' && $t[1] === '(') {
            $valor = $this->expressao();
            if (!$this->eOperador(')')) {
                throw new ErroCalculo('Falta fechar um parêntese.');
            }
            $this->pos++;
            return $valor;
        }
        if ($t[0] === 'id') {
            return $this->identificador($t[1]);
        }
        throw new ErroCalculo('Expressão inválida perto de "' . $this->descrever($t) . '".');
    }

    private function identificador(string $nome): float
    {
        if ($nome === 'ans') {
            return $this->ans;
        }
        if (in_array($nome, self::CONSTANTES, true)) {
            return $nome === 'pi' ? M_PI : M_E;
        }
        if (!in_array($nome, self::FUNCOES, true)) {
            throw new ErroCalculo('Função ou símbolo desconhecido: "' . $nome . '".');
        }
        if (!$this->eOperador('(')) {
            throw new ErroCalculo('Depois de "' . $nome . '" abre um parêntese, por exemplo ' . $nome . '(9).');
        }
        $this->pos++;
        $argumento = $this->expressao();
        if (!$this->eOperador(')')) {
            throw new ErroCalculo('Falta fechar o parêntese de "' . $nome . '".');
        }
        $this->pos++;
        return $this->aplicar($nome, $argumento);
    }

    // ------------------------------------------------------------
    // Funções matemáticas
    // ------------------------------------------------------------

    private function aplicar(string $nome, float $x): float
    {
        $paraRad = fn(float $v): float => $this->modoAngulo === 'deg' ? deg2rad($v) : $v;
        $deRad = fn(float $v): float => $this->modoAngulo === 'deg' ? rad2deg($v) : $v;

        switch ($nome) {
            case 'sqrt':
                if ($x < 0) {
                    throw new ErroCalculo('Não existe raiz quadrada real de um número negativo.');
                }
                return sqrt($x);
            case 'cbrt':
                return $x < 0 ? -((-$x) ** (1 / 3)) : $x ** (1 / 3);
            case 'abs':
                return abs($x);
            case 'exp':
                return exp($x);
            case 'ln':
            case 'log':
                if ($x <= 0) {
                    throw new ErroCalculo('O logaritmo só existe para números maiores que zero.');
                }
                return $nome === 'ln' ? log($x) : log10($x);
            case 'sin':
                return self::limpar(sin($paraRad($x)));
            case 'cos':
                return self::limpar(cos($paraRad($x)));
            case 'tan':
                $rad = $paraRad($x);
                if (abs(cos($rad)) < 1e-12) {
                    throw new ErroCalculo('A tangente não está definida para este ângulo.');
                }
                return self::limpar(tan($rad));
            case 'asin':
            case 'acos':
                if ($x < -1 || $x > 1) {
                    throw new ErroCalculo('O argumento de ' . $nome . ' tem de estar entre -1 e 1.');
                }
                return self::limpar($deRad($nome === 'asin' ? asin($x) : acos($x)));
            case 'atan':
                return self::limpar($deRad(atan($x)));
        }
        throw new ErroCalculo('Função desconhecida.');
    }

    private static function fatorial(float $n): float
    {
        if ($n < 0 || floor($n) != $n) {
            throw new ErroCalculo('O fatorial só existe para inteiros não negativos.');
        }
        if ($n > 170) {
            throw new ErroCalculo('O fatorial é demasiado grande (máximo 170!).');
        }
        $resultado = 1.0;
        for ($i = 2; $i <= (int) $n; $i++) {
            $resultado *= $i;
        }
        return $resultado;
    }

    /** Remove ruído de vírgula flutuante (sin(180°) = 1.2e-16 → 0). */
    private static function limpar(float $valor): float
    {
        $arredondado = round($valor, 12);
        return $arredondado == 0.0 ? 0.0 : $arredondado;
    }

    private static function validarResultado(float $valor): float
    {
        if (is_nan($valor) || is_infinite($valor)) {
            throw new ErroCalculo('O resultado é demasiado grande ou indefinido.');
        }
        return $valor;
    }
}
