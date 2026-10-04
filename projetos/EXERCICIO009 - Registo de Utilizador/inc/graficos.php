<?php
/** Gráficos em SVG (sem bibliotecas): barras horizontais e rosca. As cores vêm do app.css. */

declare(strict_types=1);

if (!defined('APP')) {
    http_response_code(403);
    exit;
}

/**
 * Barras horizontais, de 0 a $maximo. Cada item: ['rotulo' => texto, 'valor' => float|null].
 * Leva uma linha de referência (nota positiva) e uma tabela alternativa para leitores de ecrã.
 */
function graficoBarras(array $itens, string $titulo, float $maximo = 20.0, ?float $referencia = null): string
{
    $largura = 560;
    $esq = 168;          // espaço para os nomes
    $dir = 46;           // espaço para o valor
    $linha = 34;
    $espessura = 20;
    $topo = 8;
    $util = $largura - $esq - $dir;
    $altura = $topo * 2 + $linha * max(1, count($itens));

    $svg = '<svg class="grafico" viewBox="0 0 ' . $largura . ' ' . $altura . '" role="img" aria-label="' . e($titulo) . '">';
    foreach ([0, 5, 10, 15, 20] as $marca) {
        $x = $esq + $util * ($marca / $maximo);
        $svg .= '<line class="g-grelha" x1="' . round($x, 1) . '" y1="' . ($topo - 2) . '" x2="' . round($x, 1) . '" y2="' . ($altura - $topo + 2) . '"/>';
    }
    foreach ($itens as $i => $item) {
        $y = $topo + $i * $linha;
        $valor = $item['valor'];
        $svg .= '<text class="g-rotulo" x="' . ($esq - 12) . '" y="' . ($y + $linha / 2 + 4) . '" text-anchor="end">' . e($item['rotulo']) . '</text>';
        if ($valor === null) {
            $svg .= '<text class="g-valor" x="' . ($esq + 6) . '" y="' . ($y + $linha / 2 + 4) . '">—</text>';
            continue;
        }
        $w = max(2.0, $util * min($valor, $maximo) / $maximo);
        $by = $y + ($linha - $espessura) / 2;
        $r = min(4.0, $w / 2);
        $d = sprintf(
            'M%1$s,%2$s h%3$s a%4$s,%4$s 0 0 1 %4$s,%4$s v%5$s a%4$s,%4$s 0 0 1 -%4$s,%4$s h-%3$s z',
            $esq,
            round($by, 1),
            round($w - $r, 1),
            round($r, 1),
            round($espessura - 2 * $r, 1)
        );
        $svg .= '<path class="g-barra" d="' . $d . '"><title>' . e($item['rotulo'] . ': ' . numFmt($valor, 1)) . '</title></path>';
        $svg .= '<text class="g-valor" x="' . round($esq + $w + 8, 1) . '" y="' . ($y + $linha / 2 + 4) . '">' . e(numFmt($valor, 1)) . '</text>';
    }
    if ($referencia !== null) {
        $x = round($esq + $util * ($referencia / $maximo), 1);
        $svg .= '<line class="g-ref" x1="' . $x . '" y1="' . ($topo - 4) . '" x2="' . $x . '" y2="' . ($altura - $topo + 4) . '"/>';
    }
    $svg .= '</svg>';

    $tabela = '<details class="g-tabela"><summary>' . e(t('ver_tabela')) . '</summary><table class="tabela"><tbody>';
    foreach ($itens as $item) {
        $tabela .= '<tr><td>' . e($item['rotulo']) . '</td><td class="num">' . e(numFmt($item['valor'], 1)) . '</td></tr>';
    }
    $tabela .= '</tbody></table></details>';
    return '<div class="tabela-rolagem">' . $svg . '</div>' . $tabela;
}

/**
 * Rosca com duas ou mais partes: ['rotulo' => texto, 'valor' => int, 'classe' => 'g-rosca-1'].
 * Devolve o desenho e a legenda lado a lado.
 */
function graficoRosca(array $partes, string $titulo, string $centro, string $legendaCentro): string
{
    $raio = 62;
    $espessura = 24;
    $circ = 2 * M_PI * $raio;
    $total = array_sum(array_column($partes, 'valor'));
    $folga = count($partes) > 1 ? 3.0 : 0.0; // separação entre partes
    $svg = '<svg class="rosca" viewBox="0 0 180 180" role="img" aria-label="' . e($titulo) . '">';
    $svg .= '<circle class="g-rosca-fundo" cx="90" cy="90" r="' . $raio . '" fill="none" stroke-width="' . $espessura . '"/>';
    $deslocamento = 0.0;
    if ($total > 0) {
        foreach ($partes as $p) {
            if ($p['valor'] <= 0) {
                continue;
            }
            $comprimento = $circ * $p['valor'] / $total;
            $visivel = max(1.0, $comprimento - $folga);
            $svg .= sprintf(
                '<circle class="%s" cx="90" cy="90" r="%d" fill="none" stroke-width="%d" stroke-dasharray="%s %s" stroke-dashoffset="%s" transform="rotate(-90 90 90)"><title>%s</title></circle>',
                e($p['classe']),
                $raio,
                $espessura,
                round($visivel, 2),
                round($circ - $visivel, 2),
                round(-$deslocamento, 2),
                e($p['rotulo'] . ': ' . $p['valor'])
            );
            $deslocamento += $comprimento;
        }
    }
    $svg .= '<text class="g-centro" x="90" y="92" text-anchor="middle">' . e($centro) . '</text>';
    $svg .= '<text class="g-centro-leg" x="90" y="112" text-anchor="middle">' . e($legendaCentro) . '</text></svg>';

    $legenda = '<ul class="g-legenda">';
    foreach ($partes as $p) {
        $legenda .= '<li><span class="g-chave ' . e($p['classe']) . '"></span><span>' . e($p['rotulo']) . '</span><strong>' . (int) $p['valor'] . '</strong></li>';
    }
    $legenda .= '</ul>';
    return '<div class="rosca-caixa">' . $svg . $legenda . '</div>';
}
