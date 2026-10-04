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
                e($p['rotulo'] . ': ' . ($p['texto'] ?? $p['valor']))
            );
            $deslocamento += $comprimento;
        }
    }
    $svg .= '<text class="g-centro" x="90" y="92" text-anchor="middle">' . e($centro) . '</text>';
    $svg .= '<text class="g-centro-leg" x="90" y="112" text-anchor="middle">' . e($legendaCentro) . '</text></svg>';

    $legenda = '<ul class="g-legenda">';
    foreach ($partes as $p) {
        $legenda .= '<li><span class="g-chave ' . e($p['classe']) . '"></span><span>' . e($p['rotulo']) . '</span><strong>' . e($p['texto'] ?? (int) $p['valor']) . '</strong></li>';
    }
    $legenda .= '</ul>';
    return '<div class="rosca-caixa">' . $svg . $legenda . '</div>';
}

/** Texto curto para o eixo do dinheiro (1 200 € → "1,2 k€"). */
function eixoDinheiro(int $cents): string
{
    $euros = $cents / 100;
    return abs($euros) >= 1000 ? numFmt($euros / 1000, 1) . ' k€' : numFmt($euros, 0) . ' €';
}

/**
 * Colunas lado a lado por mês, para duas séries (por exemplo receitas e custos).
 * Cada item: ['rotulo' => 'Set', 'a' => cêntimos, 'b' => cêntimos]. Leva legenda e uma tabela alternativa.
 */
function graficoColunas(array $itens, string $titulo, string $nomeA, string $nomeB): string
{
    $largura = 600;
    $altura = 270;
    $esq = 58;
    $dir = 10;
    $topo = 14;
    $base = $altura - 34;
    $util = $largura - $esq - $dir;
    $maximo = 1;
    foreach ($itens as $i) {
        $maximo = max($maximo, $i['a'], $i['b']);
    }
    // eixo com 4 intervalos e um máximo "redondo"
    $passo = 10 ** max(0, strlen((string) intdiv($maximo, 4)) - 1);
    $passo = max($passo, (int) (ceil(($maximo / 4) / $passo) * $passo));
    $maximo = $passo * 4;
    $svg = '<svg class="grafico" viewBox="0 0 ' . $largura . ' ' . $altura . '" role="img" aria-label="' . e($titulo) . '">';
    for ($k = 0; $k <= 4; $k++) {
        $y = $base - ($base - $topo) * $k / 4;
        $svg .= '<line class="g-grelha" x1="' . $esq . '" y1="' . round($y, 1) . '" x2="' . ($largura - $dir) . '" y2="' . round($y, 1) . '"/>';
        $svg .= '<text class="g-eixo" x="' . ($esq - 8) . '" y="' . round($y + 4, 1) . '" text-anchor="end">' . e(eixoDinheiro($passo * $k)) . '</text>';
    }
    $n = max(1, count($itens));
    $faixa = $util / $n;
    $largBarra = min(20.0, ($faixa - 8) / 2);
    foreach ($itens as $i => $item) {
        $x0 = $esq + $i * $faixa + ($faixa - 2 * $largBarra - 2) / 2;
        foreach (['a' => 0, 'b' => 1] as $serie => $pos) {
            $h = ($base - $topo) * min($item[$serie], $maximo) / $maximo;
            if ($h <= 0) {
                continue;
            }
            $svg .= '<rect class="g-col-' . $serie . '" x="' . round($x0 + $pos * ($largBarra + 2), 1) . '" y="' . round($base - $h, 1) . '" width="' . round($largBarra, 1) . '" height="' . round($h, 1) . '" rx="3">'
                . '<title>' . e($item['rotulo'] . ' · ' . ($serie === 'a' ? $nomeA : $nomeB) . ': ' . euro((int) $item[$serie])) . '</title></rect>';
        }
        $svg .= '<text class="g-rotulo g-mes" x="' . round($esq + $i * $faixa + $faixa / 2, 1) . '" y="' . ($altura - 12) . '" text-anchor="middle">' . e($item['rotulo']) . '</text>';
    }
    $svg .= '</svg>';
    $legenda = '<ul class="g-legenda g-legenda-linha"><li><span class="g-chave g-col-a"></span><span>' . e($nomeA) . '</span></li><li><span class="g-chave g-col-b"></span><span>' . e($nomeB) . '</span></li></ul>';
    $tabela = '<details class="g-tabela"><summary>' . e(t('ver_tabela')) . '</summary><table class="tabela"><thead><tr><th></th><th class="num">' . e($nomeA) . '</th><th class="num">' . e($nomeB) . '</th></tr></thead><tbody>';
    foreach ($itens as $i) {
        $tabela .= '<tr><td>' . e($i['rotulo']) . '</td><td class="num">' . e(euro((int) $i['a'])) . '</td><td class="num">' . e(euro((int) $i['b'])) . '</td></tr>';
    }
    return '<div class="tabela-rolagem">' . $svg . '</div>' . $legenda . $tabela . '</tbody></table></details>';
}

/** Barras horizontais de duas partes (emitido e recebido) por grupo, por exemplo por turma. Cada item: ['rotulo', 'total', 'parte'] em cêntimos. */
function graficoProgresso(array $itens, string $titulo): string
{
    $html = '<div class="progressos" role="img" aria-label="' . e($titulo) . '">';
    foreach ($itens as $i) {
        $pct = $i['total'] > 0 ? (int) round(100 * $i['parte'] / $i['total']) : 0;
        $html .= '<div class="progresso-linha"><span class="progresso-nome">' . e($i['rotulo']) . '</span>'
            . '<svg class="progresso-barra" viewBox="0 0 100 10" preserveAspectRatio="none" aria-hidden="true"><rect class="g-prog-fundo" width="100" height="10" rx="5"/><rect class="g-prog-cheio" width="' . max($pct, $pct > 0 ? 3 : 0) . '" height="10" rx="5"/></svg>'
            . '<span class="progresso-valor">' . $pct . '%<small>' . e(euro((int) $i['parte'])) . ' / ' . e(euro((int) $i['total'])) . '</small></span></div>';
    }
    return $html . '</div>';
}
