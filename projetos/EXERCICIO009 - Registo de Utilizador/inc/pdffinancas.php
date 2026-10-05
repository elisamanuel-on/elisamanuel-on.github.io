<?php
/** PDFs financeiros (com a classe Pdf): recibo de propina, recibo de vencimento e relatório financeiro. */

declare(strict_types=1);

if (!defined('APP')) {
    http_response_code(403);
    exit;
}

/** Envia um PDF para descarregar e termina. */
function enviarPdf(string $conteudo, string $nome): never
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    $nome = trim(preg_replace('/[^a-z0-9]+/', '_', semAcentos($nome)) ?: 'documento', '_');
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $nome . '.pdf"');
    header('Content-Length: ' . strlen($conteudo));
    header('Cache-Control: private, max-age=0');
    echo $conteudo;
    exit;
}

/** Cabeçalho comum dos documentos em A4 vertical. Devolve o y onde o conteúdo começa. */
function pdfCabecaVertical(Pdf $pdf, string $titulo, string $subtitulo): float
{
    $azul = [0.145, 0.388, 0.922];
    $azulEscuro = [0.114, 0.255, 0.600];
    $cinza = [0.42, 0.45, 0.50];
    $m = 48.0;
    $pdf->texto($m, 62, t('escola'), 18, true, 'e', 0, $azulEscuro);
    $pdf->texto($m, 80, t('ano_letivo', ANO_LETIVO), 9.5, false, 'e', 0, $cinza);
    $pdf->texto($m, 112, $titulo, 15, true);
    $pdf->texto($m, 129, $subtitulo, 10.5, false, 'e', 0, $cinza);
    $pdf->linha($m, 138, $pdf->largura - $m, 138, 1.2, $azul);
    return 160.0;
}

/** Linha "rótulo ........ valor" para os recibos. */
function pdfLinhaValor(Pdf $pdf, float $y, string $rotulo, string $valor, bool $negrito = false, array $cor = [0, 0, 0]): void
{
    $m = 48.0;
    $larg = $pdf->largura - 2 * $m;
    $pdf->texto($m, $y, $rotulo, 10.5, $negrito, 'e', 0, $cor);
    $pdf->texto($m, $y, $valor, 10.5, $negrito, 'd', $larg, $cor);
    $pdf->linha($m, $y + 6, $m + $larg, $y + 6, 0.4, [0.88, 0.9, 0.94]);
}

/** Recibo de uma propina paga. $p: linha de propinas + nome, turma. */
function pdfReciboPropina(array $p): string
{
    $pdf = new Pdf(t('recibo_propina'));
    $pdf->largura = 595.28;
    $pdf->altura = 841.89;
    $y = pdfCabecaVertical($pdf, t('recibo_propina'), t('recibo_numero', (int) $p['id']));
    $linhas = [
        [t('aluno'), $p['nome']],
        [t('turma'), $p['turma']],
        ...(($p['encarregado'] ?? '') !== '' ? [[t('encarregado_paga'), $p['encarregado']]] : []),
        ...(($p['nif'] ?? '') !== '' ? [[t('nif'), $p['nif']]] : []),
        [t('mes'), nomeMes($p['mes'])],
        [t('vencimento'), dataFmt($p['vencimento'])],
        [t('pago_em'), dataFmt($p['pago_em'])],
        [t('metodo'), $p['metodo'] ? t('metodo_' . $p['metodo']) : '—'],
    ];
    foreach ($linhas as [$r, $v]) {
        pdfLinhaValor($pdf, $y, $r, $v);
        $y += 26;
    }
    $y += 8;
    pdfLinhaValor($pdf, $y, t('valor_pago'), euro((int) $p['valor_cents']), true, [0.08, 0.5, 0.24]);
    $y += 40;
    $pdf->texto(48, $y, t('iva_isento_ensino'), 9, false, 'e', 0, [0.42, 0.45, 0.5]);
    $pdf->texto(48, $y + 16, t('doc_exemplo'), 9, false, 'e', 0, [0.78, 0.13, 0.13]);
    $pdf->texto(48, $pdf->altura - 40, t('gerado_em', dataFmt(hojeISO())), 8.5, false, 'e', 0, [0.42, 0.45, 0.5]);
    return $pdf->saida();
}

/** Recibo de vencimento. $s: linha de salarios + nome, cargo. */
function pdfReciboVencimento(array $s): string
{
    $pdf = new Pdf(t('recibo_vencimento'));
    $pdf->largura = 595.28;
    $pdf->altura = 841.89;
    $y = pdfCabecaVertical($pdf, t('recibo_vencimento'), nomeMes($s['mes']));
    $m = 48.0;
    $pdf->texto($m, $y, mb_strtoupper(t('nome')), 7.5, true, 'e', 0, [0.42, 0.45, 0.5]);
    $pdf->texto($m, $y + 14, $s['nome'], 11.5, true);
    $pdf->texto($m + 260, $y, mb_strtoupper(t('cargo')), 7.5, true, 'e', 0, [0.42, 0.45, 0.5]);
    $pdf->texto($m + 260, $y + 14, $s['cargo'], 11.5);
    $y += 42;
    if (($s['nif'] ?? '') !== '' || ($s['niss'] ?? '') !== '') {
        $pdf->texto($m, $y, mb_strtoupper(t('nif')), 7.5, true, 'e', 0, [0.42, 0.45, 0.5]);
        $pdf->texto($m, $y + 14, ($s['nif'] ?? '') !== '' ? $s['nif'] : '—', 11);
        $pdf->texto($m + 260, $y, mb_strtoupper(t('niss')), 7.5, true, 'e', 0, [0.42, 0.45, 0.5]);
        $pdf->texto($m + 260, $y + 14, ($s['niss'] ?? '') !== '' ? $s['niss'] : '—', 11);
        $y += 42;
    }
    $y += 10;

    $pdf->texto($m, $y, mb_strtoupper(t('remuneracoes')), 8, true, 'e', 0, [0.114, 0.255, 0.6]);
    $y += 20;
    pdfLinhaValor($pdf, $y, t('bruto'), euro((int) $s['bruto_cents']), true);
    $y += 34;
    $pdf->texto($m, $y, mb_strtoupper(t('descontos')), 8, true, 'e', 0, [0.114, 0.255, 0.6]);
    $y += 20;
    $bruto = max(1, (int) $s['bruto_cents']);
    pdfLinhaValor($pdf, $y, t('irs_retido') . ' (' . numFmt(100 * (int) $s['irs_cents'] / $bruto, 1) . '%)', '− ' . euro((int) $s['irs_cents']));
    $y += 26;
    pdfLinhaValor($pdf, $y, t('ss_trabalhador') . ' (' . numFmt(100 * (int) $s['ss_trab_cents'] / $bruto, 2) . '%)', '− ' . euro((int) $s['ss_trab_cents']));
    $y += 38;
    $pdf->retangulo($m, $y - 18, $pdf->largura - 2 * $m, 34, [0.91, 0.94, 0.996]);
    $pdf->texto($m + 10, $y + 3, t('liquido'), 12, true, 'e', 0, [0.114, 0.255, 0.6]);
    $pdf->texto($m, $y + 3, euro((int) $s['liquido_cents']), 12, true, 'd', $pdf->largura - 2 * $m - 10, [0.114, 0.255, 0.6]);
    $y += 52;
    $pdf->texto($m, $y, mb_strtoupper(t('encargos_entidade')), 8, true, 'e', 0, [0.114, 0.255, 0.6]);
    $y += 20;
    pdfLinhaValor($pdf, $y, t('ss_entidade') . ' (' . numFmt(100 * (int) $s['ss_ent_cents'] / $bruto, 2) . '%)', euro((int) $s['ss_ent_cents']));
    $y += 26;
    pdfLinhaValor($pdf, $y, t('custo_total'), euro((int) $s['bruto_cents'] + (int) $s['ss_ent_cents']), true);
    $y += 40;
    $pdf->texto($m, $y, $s['pago_em'] ? t('pago_em') . ': ' . dataFmt($s['pago_em']) . (!empty($s['metodo']) ? ' · ' . t('metodo_' . $s['metodo']) : '') : t('por_pagar'), 10, true);
    $pdf->texto($m, $y + 18, t('nota_salarios'), 8.5, false, 'e', 0, [0.42, 0.45, 0.5]);
    $pdf->texto($m, $y + 34, t('doc_exemplo'), 9, false, 'e', 0, [0.78, 0.13, 0.13]);
    $pdf->texto($m, $pdf->altura - 40, t('gerado_em', dataFmt(hojeISO())), 8.5, false, 'e', 0, [0.42, 0.45, 0.5]);
    return $pdf->saida();
}

/** Relatório financeiro do ano letivo (A4 horizontal): resumo por mês, impostos, pessoal e custos. */
function pdfRelatorioFinanceiro(array $r): string
{
    $pdf = new Pdf(t('rel_resumo_titulo'));
    $m = 36.0;
    $larg = $pdf->largura - 2 * $m;
    $azul = [0.145, 0.388, 0.922];
    $azulEscuro = [0.114, 0.255, 0.6];
    $cinza = [0.42, 0.45, 0.5];
    $branco = [1, 1, 1];
    $T = $r['total'];

    $pdf->texto($m, 54, t('escola'), 17, true, 'e', 0, $azulEscuro);
    $pdf->texto($m, 74, t('rel_resumo_titulo') . ' · ' . t('ano_letivo', ANO_LETIVO), 12.5, true);
    $pdf->linha($m, 84, $pdf->largura - $m, 84, 1.2, $azul);

    // Quatro números principais
    $caixas = [[t('total_receitas'), $T['receitas']], [t('total_custos'), $T['custos']], [t('resultado_fin'), $T['resultado']], [t('iva_a_entregar'), $T['iva_pagar']]];
    $w = ($larg - 3 * 10) / 4;
    foreach ($caixas as $i => [$rotulo, $valor]) {
        $x = $m + $i * ($w + 10);
        $pdf->retangulo($x, 98, $w, 48, [0.96, 0.97, 0.99], [0.88, 0.9, 0.94]);
        $pdf->texto($x + 10, 114, mb_strtoupper($pdf->ajustar($rotulo, $w - 20, 7.5, true)), 7.5, true, 'e', 0, $cinza);
        $pdf->texto($x + 10, 135, euro((int) $valor), 14, true, 'e', 0, $valor < 0 ? [0.78, 0.13, 0.13] : $azulEscuro);
    }

    // Tabela por mês
    $cols = [[t('mes'), 70, 'e'], [t('propinas_recebidas'), 78, 'd'], [t('outras_receitas'), 70, 'd'], [t('total_receitas'), 72, 'd'], [t('salarios_brutos'), 74, 'd'],
        [t('ss_entidade'), 70, 'd'], [t('outras_despesas'), 66, 'd'], [t('total_custos'), 70, 'd'], [t('resultado_fin'), 74, 'd']];
    $soma = array_sum(array_column($cols, 1));
    $escala = $larg / $soma;
    $y = 168.0;
    $pdf->retangulo($m, $y, $larg, 28, $azulEscuro);
    $x = $m;
    foreach ($cols as [$titulo, $wc, $al]) {
        $wc *= $escala;
        $linhasTitulo = array_slice($pdf->quebrar($titulo, $wc - 8, 7, true), 0, 2);
        foreach ($linhasTitulo as $k => $parte) {
            $pdf->texto($x + 4, $y + 12 + $k * 8.5, $parte, 7, true, $al, $wc - 8, $branco);
        }
        $x += $wc;
    }
    $y += 28;
    $linhas = [];
    foreach ($r['meses'] as $mes => $v) {
        $linhas[] = [nomeMes($mes), $v['propinas'], $v['outras_receitas'], $v['receitas'], $v['pessoal'], $v['ss_ent'], $v['despesas'], $v['custos'], $v['resultado'], false];
    }
    $linhas[] = [t('total'), $T['propinas'], $T['outras_receitas'], $T['receitas'], $T['pessoal'], $T['ss_ent'], $T['despesas'], $T['custos'], $T['resultado'], true];
    foreach ($linhas as $k => $l) {
        $negrito = $l[9];
        if ($negrito) {
            $pdf->retangulo($m, $y, $larg, 18, [0.91, 0.94, 0.996]);
        } elseif ($k % 2 === 1) {
            $pdf->retangulo($m, $y, $larg, 18, [0.975, 0.98, 0.99]);
        }
        $x = $m;
        foreach ($cols as $j => [$titulo, $wc, $al]) {
            $wc *= $escala;
            $texto = $j === 0 ? (string) $l[0] : euro((int) $l[$j]);
            $cor = $j === 8 && $l[8] < 0 ? [0.78, 0.13, 0.13] : [0, 0, 0];
            $pdf->texto($x + 4, $y + 12.5, $pdf->ajustar($texto, $wc - 8, 8.5, $negrito), 8.5, $negrito, $al, $wc - 8, $cor);
            $x += $wc;
        }
        $y += 18;
    }

    // Impostos e contribuições
    $y += 22;
    $pdf->texto($m, $y, mb_strtoupper(t('impostos_titulo')), 8.5, true, 'e', 0, $azulEscuro);
    $y += 8;
    $blocos = [
        [t('iva_liquidado'), $T['iva_liq']], [t('iva_dedutivel'), $T['iva_ded']], [t('iva_a_entregar'), $T['iva_pagar']],
        [t('irs_retido'), $T['irs']], [t('ss_trabalhador'), $T['ss_trab']], [t('ss_entidade'), $T['ss_ent']],
    ];
    $wb = ($larg - 5 * 8) / 6;
    foreach ($blocos as $i => [$rotulo, $valor]) {
        $x = $m + $i * ($wb + 8);
        $pdf->retangulo($x, $y, $wb, 40, null, [0.88, 0.9, 0.94]);
        $pdf->texto($x + 8, $y + 14, $pdf->ajustar($rotulo, $wb - 16, 8, false), 8, false, 'e', 0, $cinza);
        $pdf->texto($x + 8, $y + 31, euro((int) $valor), 11.5, true);
    }
    $y += 56;
    $pdf->texto($m, $y, $pdf->ajustar(t('nota_resumo_fin'), $larg, 8), 8, false, 'e', 0, $cinza);
    $pdf->texto($m, $y + 12, $pdf->ajustar(t('nota_seguranca'), $larg, 8), 8, false, 'e', 0, $cinza);
    $pdf->texto($m, $pdf->altura - 24, t('gerado_em', dataFmt(hojeISO())) . ' · ' . t('doc_exemplo'), 8, false, 'e', 0, $cinza);
    return $pdf->saida();
}
