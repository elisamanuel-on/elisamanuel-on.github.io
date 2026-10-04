<?php
/** Painel da direção: números da escola e gráficos (1.º período). */
if (!defined('APP')) { http_response_code(403); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
    if (($_POST['acao'] ?? '') === 'repor') {
        reporDemonstracao();
        terminarSessao();
        redirecionar('?');
    }
    redirecionar(ligacao('direcao'));
}

$est = estatisticasEscola();
$totalAlunos = $est['semNegativas'] + $est['comNegativas'];
$taxa = $totalAlunos ? round(100 * $est['semNegativas'] / $totalAlunos) : 0;
?>
<section class="kpis">
    <div class="kpi"><span class="kpi-valor"><?= e(numFmt($est['mediaGeral'], 1)) ?></span><span class="kpi-rotulo"><?= e(t('media_geral_escola')) ?></span></div>
    <div class="kpi"><span class="kpi-valor"><?= $taxa ?>%</span><span class="kpi-rotulo"><?= e(t('alunos_sem_negativas')) ?></span></div>
    <div class="kpi"><span class="kpi-valor"><?= count($est['risco']) ?></span><span class="kpi-rotulo"><?= e(t('alunos_em_risco')) ?></span></div>
    <div class="kpi"><span class="kpi-valor"><?= (int) $est['faltasInjust'] ?></span><span class="kpi-rotulo"><?= e(t('faltas_injustificadas')) ?></span></div>
</section>

<div class="duas-colunas">
    <section class="cartao">
        <h2><?= e(t('media_por_turma')) ?></h2>
        <p class="suave"><?= e(t('legenda_escala', NOTA_POSITIVA)) ?></p>
        <?= graficoBarras($est['mediasTurma'], t('media_por_turma'), 20.0, (float) NOTA_POSITIVA) ?>
    </section>
    <section class="cartao">
        <h2><?= e(t('resultados_alunos')) ?></h2>
        <p class="suave"><?= e(t('legenda_rosca')) ?></p>
        <?= graficoRosca([
            ['rotulo' => t('sem_negativas'), 'valor' => $est['semNegativas'], 'classe' => 'g-rosca-1'],
            ['rotulo' => t('com_negativas'), 'valor' => $est['comNegativas'], 'classe' => 'g-rosca-2'],
        ], t('resultados_alunos'), (string) $totalAlunos, t('alunos')) ?>
    </section>
</div>

<section class="cartao">
    <h2><?= e(t('media_por_disciplina')) ?></h2>
    <?= graficoBarras($est['mediasDisc'], t('media_por_disciplina'), 20.0, (float) NOTA_POSITIVA) ?>
</section>

<section class="cartao">
    <h2><?= e(t('alunos_em_risco')) ?></h2>
    <p class="suave"><?= e(t('criterio_risco', FALTAS_RISCO)) ?></p>
    <?php if (!$est['risco']): ?><p class="suave"><?= e(t('sem_risco')) ?></p><?php else: ?>
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th><?= e(t('aluno')) ?></th><th><?= e(t('turma')) ?></th><th class="num"><?= e(t('negativas')) ?></th><th class="num"><?= e(t('injustificadas')) ?></th></tr></thead>
        <tbody><?php foreach (array_slice($est['risco'], 0, 12) as $r): ?>
            <tr><td><?= e($r['nome']) ?></td><td><?= e($r['turma']) ?></td><td class="num"><?= (int) $r['negativas'] ?></td><td class="num"><?= (int) $r['faltas'] ?></td></tr>
        <?php endforeach; ?></tbody>
    </table></div>
    <?php endif; ?>
</section>

<section class="cartao zona-demo">
    <h2><?= e(t('repor_demonstracao')) ?></h2>
    <p class="suave"><?= e(t('repor_texto')) ?></p>
    <form method="post" data-confirmar="<?= e(t('confirmar_repor_demo')) ?>">
        <?= csrfCampo() ?><input type="hidden" name="acao" value="repor">
        <button type="submit" class="botao secundario"><?= e(t('repor_botao')) ?></button>
    </form>
</section>
