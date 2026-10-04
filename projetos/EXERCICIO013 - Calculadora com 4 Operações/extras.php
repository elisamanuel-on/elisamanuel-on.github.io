<?php
/**
 * Exercício 013 · Funções extra: poderes (elementos) e modo Desafio.
 *
 * Tal como em calculadora.php, aqui só se calcula e devolve; quem apresenta é a página.
 */

declare(strict_types=1);

/** Cada elemento tem um grupo de funções/símbolos da calculadora. */
const PODERES_PADROES = [
    'ar'    => '/(sin|cos|tan)/',
    'terra' => '/(ln|log|exp|sqrt|cbrt|abs|!)/',
    'agua'  => '/(pi|\be\b|ans|[()])/',
    'fogo'  => '/[+\-*\/^%×÷]/u',
];

/** Elementos usados numa expressão: ['ar', 'fogo', ...] pela ordem fixa ar, terra, agua, fogo. */
function poderesUsados(string $expressao): array
{
    $texto = mb_strtolower($expressao);
    $usados = [];
    foreach (PODERES_PADROES as $elemento => $padrao) {
        if (preg_match($padrao, $texto) === 1) {
            $usados[] = $elemento;
        }
    }
    return $usados;
}

/** Nome em português de cada elemento. */
function nomeElemento(string $elemento): string
{
    return match ($elemento) {
        'agua'    => 'Água',
        'fogo'    => 'Fogo',
        'terra'   => 'Terra',
        'ar'      => 'Ar',
        'energia' => 'Energia',
        default   => 'Magia',
    };
}

/** Nível do desafio: sobe de 3 em 3 acertos seguidos, até ao 4. */
function nivelDesafio(int $sequencia): int
{
    return min(4, intdiv(max(0, $sequencia), 3) + 1);
}

/**
 * Gera uma pergunta de cálculo mental. O elemento diz de que "poder" é a conta.
 *
 * @return array{pergunta: string, resposta: float, elemento: string, nivel: int}
 */
function gerarDesafio(int $sequencia): array
{
    $nivel = nivelDesafio($sequencia);

    $tipos = match ($nivel) {
        1       => ['soma', 'subtracao'],
        2       => ['soma', 'subtracao', 'multiplicacao'],
        3       => ['multiplicacao', 'divisao', 'potencia'],
        default => ['divisao', 'potencia', 'raiz', 'multiplicacao'],
    };
    $tipo = $tipos[random_int(0, count($tipos) - 1)];
    $limite = 10 + $nivel * 15;

    switch ($tipo) {
        case 'soma':
            $a = random_int(2, $limite);
            $b = random_int(2, $limite);
            return ['pergunta' => "$a + $b", 'resposta' => (float) ($a + $b), 'elemento' => 'terra', 'nivel' => $nivel];

        case 'subtracao':
            $a = random_int(10, $limite + 10);
            $b = random_int(2, $a - 1);
            return ['pergunta' => "$a − $b", 'resposta' => (float) ($a - $b), 'elemento' => 'agua', 'nivel' => $nivel];

        case 'multiplicacao':
            $a = random_int(2, $nivel >= 3 ? 15 : 10);
            $b = random_int(2, 12);
            return ['pergunta' => "$a × $b", 'resposta' => (float) ($a * $b), 'elemento' => 'fogo', 'nivel' => $nivel];

        case 'divisao':
            $b = random_int(2, 12);
            $q = random_int(2, $nivel >= 4 ? 15 : 12);
            $a = $b * $q;
            return ['pergunta' => "$a ÷ $b", 'resposta' => (float) $q, 'elemento' => 'ar', 'nivel' => $nivel];

        case 'potencia':
            $base = random_int(2, $nivel >= 4 ? 9 : 6);
            $exp = random_int(2, 3);
            return ['pergunta' => "$base^$exp", 'resposta' => (float) ($base ** $exp), 'elemento' => 'energia', 'nivel' => $nivel];

        default: // raiz
            $q = random_int(2, 15);
            return ['pergunta' => '√' . ($q * $q), 'resposta' => (float) $q, 'elemento' => 'energia', 'nivel' => $nivel];
    }
}

/** A resposta está certa? Aceita diferenças de arredondamento até meio cêntimo. */
function respostaCerta(float $dada, float $esperada): bool
{
    return abs($dada - $esperada) < 0.005;
}

/** Pontos de uma resposta certa: 10 + 2 por cada acerto seguido anterior (máximo 5). */
function pontosDaResposta(int $sequenciaAntes): int
{
    return 10 + min(5, max(0, $sequenciaAntes)) * 2;
}
