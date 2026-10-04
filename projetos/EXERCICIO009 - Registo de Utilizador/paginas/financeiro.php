<?php
/**
 * Financeiro: receitas, custos, impostos e contribuições do ano letivo, com gráficos, relatórios em Excel e PDF
 * e os parâmetros (propinas, Segurança Social). Só a contabilidade altera os parâmetros; a direção e o Conselho Geral
 * só consultam.
 */
if (!defined('APP')) { http_response_code(403); exit; }

$u = utilizador();
$podeEditar = ehContabilista();
$db = bd();

// ---------- descarregar Excel ou PDF ----------
if (isset($_GET['excel'])) {
    $qual = is_string($_GET['excel']) && in_array($_GET['excel'], RELATORIOS_FIN, true) ? $_GET['excel'] : null;
    if ($qual === null) {
        redirecionar(ligacao('financeiro'));
    }
    enviarXlsx(excelFinanceiro($qual), 'colegio_horizonte_' . ($qual === 'tudo' ? 'financas' : $qual));
}
if (($_GET['pdf'] ?? '') === 'relatorio') {
    enviarPdf(pdfRelatorioFinanceiro(resumoFinanceiro()), 'relatorio_financeiro_' . str_replace('/', '_', ANO_LETIVO));
}

// ---------- alterar parâmetros (só a direção) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
    if (!$podeEditar) {
        http_response_code(403);
        exit(t('erro_403_texto'));
    }
    $novos = [];
    foreach ([7, 8, 9] as $ano) {
        $c = lerDinheiro($_POST['propina_' . $ano] ?? null, 500000);
        if ($c === false || $c <= 0) {
            $novos = null;
            break;
        }
        $novos['propina_' . $ano] = numCents($c);
    }
    $dia = inteiro($_POST['dia_vencimento'] ?? '');
    $ssT = lerPercentagem($_POST['ss_trab'] ?? null, 30.0);
    $ssE = lerPercentagem($_POST['ss_ent'] ?? null, 40.0);
    if ($novos === null || $dia < 1 || $dia > 28 || $ssT === false || $ssE === false) {
        aviso('erro', 'dados_invalidos');
    } else {
        $novos += ['dia_vencimento' => (string) $dia, 'ss_trab' => (string) $ssT, 'ss_ent' => (string) $ssE];
        $st = $db->prepare('INSERT INTO parametros (chave, valor) VALUES (?, ?) ON CONFLICT (chave) DO UPDATE SET valor = excluded.valor');
        foreach ($novos as $chave => $valor) {
            $st->execute([$chave, $valor]);
        }
        aviso('ok', 'parametros_guardados');
    }
    redirecionar(ligacao('financeiro'));
}

$r = resumoFinanceiro();
$T = $r['total'];

// Gráfico de colunas: receitas e custos por mês
$colunas = [];
foreach ($r['meses'] as $mes => $v) {
    $colunas[] = ['rotulo' => nomeMes($mes, true), 'a' => $v['receitas'], 'b' => $v['custos']];
}

// Composição dos custos: pessoal, Segurança Social, três maiores categorias e o resto
$partes = [
    ['rotulo' => t('salarios_brutos'), 'valor' => $T['pessoal'], 'texto' => euro($T['pessoal']), 'classe' => 'g-rosca-1'],
    ['rotulo' => t('ss_entidade'), 'valor' => $T['ss_ent'], 'texto' => euro($T['ss_ent']), 'classe' => 'g-rosca-2'],
];
$cats = $r['custos_cat'];
$k = 3;
$resto = 0;
$n = 0;
foreach ($cats as $cat => $valor) {
    if ($n < 3) {
        $partes[] = ['rotulo' => nomeCategoria($cat), 'valor' => $valor, 'texto' => euro($valor), 'classe' => 'g-rosca-' . $k++];
    } else {
        $resto += $valor;
    }
    $n++;
}
if ($resto > 0) {
    $partes[] = ['rotulo' => t('outras_despesas'), 'valor' => $resto, 'texto' => euro($resto), 'classe' => 'g-rosca-6'];
}

$porTurma = array_map(static fn ($l) => ['rotulo' => $l['turma'], 'total' => (int) $l['emitido'], 'parte' => (int) $l['pago']], propinasPorTurma());
$p = parametros();
?>
<section class="kpis">
    <div class="kpi"><span class="kpi-valor"><?= e(euro($T['receitas'])) ?></span><span class="kpi-rotulo"><?= e(t('total_receitas')) ?></span></div>
    <div class="kpi"><span class="kpi-valor"><?= e(euro($T['custos'])) ?></span><span class="kpi-rotulo"><?= e(t('total_custos')) ?></span></div>
    <div class="kpi <?= $T['resultado'] < 0 ? 'kpi-aviso' : '' ?>"><span class="kpi-valor"><?= e(euro($T['resultado'])) ?></span><span class="kpi-rotulo"><?= e(t('resultado_fin')) ?></span></div>
    <div class="kpi <?= $r['atraso_n'] ? 'kpi-aviso' : '' ?>"><span class="kpi-valor"><?= e(euro($r['atraso_valor'])) ?></span><span class="kpi-rotulo"><?= e(t('valor_em_atraso')) ?> (<?= (int) $r['atraso_n'] ?>)</span></div>
</section>

<section class="cartao">
    <h2><?= e(t('receitas_vs_custos')) ?></h2>
    <p class="suave"><?= e(t('legenda_receitas_custos')) ?></p>
    <?= graficoColunas($colunas, t('receitas_vs_custos'), t('total_receitas'), t('total_custos')) ?>
</section>

<div class="duas-colunas">
    <section class="cartao">
        <h2><?= e(t('composicao_custos')) ?></h2>
        <?= graficoRosca($partes, t('composicao_custos'), eixoDinheiro($T['custos']), t('total_custos')) ?>
    </section>
    <section class="cartao">
        <h2><?= e(t('propinas_por_turma')) ?></h2>
        <p class="suave"><?= e(t('legenda_propinas_turma')) ?></p>
        <?= graficoProgresso($porTurma, t('propinas_por_turma')) ?>
    </section>
</div>

<div class="duas-colunas">
    <section class="cartao">
        <h2><?= e(t('impostos_titulo')) ?></h2>
        <div class="tabela-rolagem"><table class="tabela">
            <tbody>
                <tr><td><?= e(t('iva_liquidado')) ?></td><td class="num"><?= e(euro($T['iva_liq'])) ?></td></tr>
                <tr><td><?= e(t('iva_dedutivel')) ?></td><td class="num"><?= e(euro($T['iva_ded'])) ?></td></tr>
                <tr><td><strong><?= e($T['iva_pagar'] >= 0 ? t('iva_a_entregar') : t('iva_a_recuperar')) ?></strong></td><td class="num"><strong><?= e(euro(abs($T['iva_pagar']))) ?></strong></td></tr>
                <tr><td><?= e(t('irs_retido')) ?></td><td class="num"><?= e(euro($T['irs'])) ?></td></tr>
                <tr><td><?= e(t('ss_trabalhador')) ?> (<?= e(numFmt(parametro('ss_trab'), 2)) ?>%)</td><td class="num"><?= e(euro($T['ss_trab'])) ?></td></tr>
                <tr><td><?= e(t('ss_entidade')) ?> (<?= e(numFmt(parametro('ss_ent'), 2)) ?>%)</td><td class="num"><?= e(euro($T['ss_ent'])) ?></td></tr>
                <tr><td><strong><?= e(t('total_entregar_estado')) ?></strong></td><td class="num"><strong><?= e(euro($T['irs'] + $T['ss_trab'] + $T['ss_ent'])) ?></strong></td></tr>
            </tbody>
        </table></div>
        <p class="suave pequeno"><?= e(t('nota_resumo_fin')) ?></p>
    </section>

    <section class="cartao">
        <h2><?= e(t('relatorios_excel')) ?></h2>
        <p class="suave"><?= e(t('ajuda_relatorios')) ?></p>
        <ul class="lista-relatorios">
            <?php foreach (['resumo', 'propinas', 'salarios', 'seguranca', 'lancamentos', 'custos'] as $rel): ?>
                <li><span><strong><?= e(t('relfin_' . $rel)) ?></strong><small><?= e(t('relfin_' . $rel . '_d')) ?></small></span>
                    <a class="botao secundario pequeno" href="<?= e(ligacao('financeiro', ['excel' => $rel])) ?>"><?= icone('excel') ?><span>Excel</span></a></li>
            <?php endforeach; ?>
        </ul>
        <p class="barra-acoes">
            <a class="botao verde" href="<?= e(ligacao('financeiro', ['excel' => 'tudo'])) ?>"><?= icone('excel') ?><span><?= e(t('baixar_excel_tudo')) ?></span></a>
            <a class="botao secundario" href="<?= e(ligacao('financeiro', ['pdf' => 'relatorio'])) ?>"><?= icone('baixar') ?><span><?= e(t('baixar_pdf_financeiro')) ?></span></a>
        </p>
    </section>
</div>

<section class="cartao">
    <h2><?= e(t('parametros_titulo')) ?></h2>
    <p class="suave"><?= e($podeEditar ? t('ajuda_parametros') : t('parametros_so_direcao')) ?></p>
    <form method="post" class="formulario">
        <?= csrfCampo() ?>
        <div class="grelha-campos">
            <?php foreach ([7, 8, 9] as $ano): ?>
                <label><span><?= e(t('propina_ano', $ano)) ?> (€)</span><input type="text" inputmode="decimal" name="propina_<?= $ano ?>" value="<?= e(numCents(propinaDoAno($ano))) ?>" maxlength="9"<?= $podeEditar ? ' required' : ' disabled' ?>></label>
            <?php endforeach; ?>
            <label><span><?= e(t('dia_vencimento')) ?></span><input type="number" name="dia_vencimento" min="1" max="28" value="<?= (int) $p['dia_vencimento'] ?>"<?= $podeEditar ? ' required' : ' disabled' ?>></label>
            <label><span><?= e(t('taxa_ss_trab')) ?> (%)</span><input type="text" inputmode="decimal" name="ss_trab" value="<?= e(rtrim(rtrim(number_format((float) $p['ss_trab'], 2, '.', ''), '0'), '.')) ?>" maxlength="5"<?= $podeEditar ? ' required' : ' disabled' ?>></label>
            <label><span><?= e(t('taxa_ss_ent')) ?> (%)</span><input type="text" inputmode="decimal" name="ss_ent" value="<?= e(rtrim(rtrim(number_format((float) $p['ss_ent'], 2, '.', ''), '0'), '.')) ?>" maxlength="5"<?= $podeEditar ? ' required' : ' disabled' ?>></label>
        </div>
        <?php if ($podeEditar): ?><p class="barra-acoes"><button type="submit" class="botao"><?= icone('guardar') ?><span><?= e(t('guardar')) ?></span></button></p><?php endif; ?>
        <p class="suave pequeno"><?= e(t('nota_taxas_legais')) ?></p>
    </form>
</section>
