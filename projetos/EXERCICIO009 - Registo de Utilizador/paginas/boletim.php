<?php
/** Boletim de notas de um aluno (o aluno vê o seu; secretaria e direção escolhem o aluno). */
if (!defined('APP')) { http_response_code(403); exit; }

$u = utilizador();
$alunoId = (int) $u['id'];
$lista = [];
if (!temPerfil('aluno')) {
    $lista = bd()->query(
        'SELECT u.id, u.nome, a.numero, t.nome AS turma FROM alunos a JOIN utilizadores u ON u.id = a.utilizador_id JOIN turmas t ON t.id = a.turma_id
          WHERE u.ativo = 1 ORDER BY t.ano, t.nome, a.numero'
    )->fetchAll();
    $pedido = inteiro($_GET['aluno'] ?? '');
    $alunoId = $lista ? (int) $lista[0]['id'] : 0;
    foreach ($lista as $l) {
        if ((int) $l['id'] === $pedido) {
            $alunoId = $pedido;
        }
    }
}
$aluno = $alunoId ? alunoPorId($alunoId) : null;
if ($aluno) {
    $medias = mediasDoAluno($alunoId);
    [$totalFaltas, $injust] = totalFaltas($alunoId);
    $linhas = [];
    $finais = [];
    $negativas = 0;
    foreach (disciplinasTodas() as $d) {
        $porPeriodo = [];
        $anuais = [];
        for ($p = 1; $p <= 3; $p++) {
            $m = $medias[(int) $d['id']][$p] ?? null;
            $porPeriodo[$p] = notaFinal($m);
            if ($porPeriodo[$p] !== null) {
                $anuais[] = $porPeriodo[$p];
            }
        }
        $anual = $anuais ? (int) floor(array_sum($anuais) / count($anuais) + 0.5) : null;
        if ($anual !== null) {
            $finais[] = $anual;
            if ($anual < NOTA_POSITIVA) {
                $negativas++;
            }
        }
        $linhas[] = ['d' => $d, 'p' => $porPeriodo, 'anual' => $anual];
    }
    $mediaGeral = $finais ? array_sum($finais) / count($finais) : null;
}
?>
<?php if ($lista): ?>
<form method="get" class="filtros cartao nao-imprimir" data-auto>
    <input type="hidden" name="p" value="boletim">
    <label><span><?= e(t('aluno')) ?></span>
        <select name="aluno">
            <?php $grupo = null; foreach ($lista as $l):
                if ($grupo !== $l['turma']) { if ($grupo !== null) { echo '</optgroup>'; } $grupo = $l['turma']; echo '<optgroup label="' . e($l['turma']) . '">'; } ?>
                <option value="<?= (int) $l['id'] ?>"<?= (int) $l['id'] === $alunoId ? ' selected' : '' ?>><?= e($l['numero'] . ' · ' . $l['nome']) ?></option>
            <?php endforeach; if ($grupo !== null) { echo '</optgroup>'; } ?>
        </select></label>
    <button type="submit" class="botao secundario"><?= e(t('ver')) ?></button>
    <button type="button" class="botao" data-imprimir><?= icone('imprimir') ?><span><?= e(t('imprimir')) ?></span></button>
</form>
<?php else: ?>
<p class="nao-imprimir"><button type="button" class="botao" data-imprimir><?= icone('imprimir') ?><span><?= e(t('imprimir')) ?></span></button></p>
<?php endif; ?>

<?php if (!$aluno): ?>
    <div class="cartao"><p class="suave"><?= e(t('sem_alunos')) ?></p></div>
<?php else: ?>
<section class="cartao folha">
    <header class="folha-cabeca">
        <div><strong><?= e(t('escola')) ?></strong><span><?= e(t('ano_letivo', ANO_LETIVO)) ?></span></div>
        <div><h2><?= e(t('boletim_titulo')) ?></h2></div>
    </header>
    <dl class="dados dados-linha">
        <div><dt><?= e(t('aluno')) ?></dt><dd><?= e($aluno['nome']) ?></dd></div>
        <div><dt><?= e(t('turma')) ?></dt><dd><?= e($aluno['turma']) ?></dd></div>
        <div><dt><?= e(t('numero')) ?></dt><dd><?= e($aluno['numero']) ?></dd></div>
    </dl>
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th><?= e(t('disciplina')) ?></th>
            <?php for ($p = 1; $p <= 3; $p++): ?><th class="num"><?= e(t('periodo_n', $p)) ?></th><?php endfor; ?>
            <th class="num"><?= e(t('media_anual')) ?></th></tr></thead>
        <tbody>
        <?php foreach ($linhas as $l): ?>
            <tr><td><?= e(nomeDisciplina($l['d'])) ?></td>
                <?php for ($p = 1; $p <= 3; $p++): $f = $l['p'][$p]; ?>
                    <td class="num"><?= $f === null ? '—' : '<span class="nota ' . ($f >= NOTA_POSITIVA ? 'pos' : 'neg') . '">' . $f . '</span>' ?></td>
                <?php endfor; ?>
                <td class="num"><?= $l['anual'] === null ? '—' : '<strong>' . $l['anual'] . '</strong>' ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <p class="resumo-pauta">
        <span><?= e(t('media_geral')) ?>: <strong><?= e(numFmt($mediaGeral, 1)) ?></strong></span>
        <span><?= e(t('negativas')) ?>: <strong><?= $negativas ?></strong></span>
        <span><?= e(t('faltas')) ?>: <strong><?= $totalFaltas ?></strong> (<?= e(t('injustificadas_n', $injust)) ?>)</span>
    </p>
    <p class="suave"><?= e(t('nota_rodape_boletim', NOTA_POSITIVA)) ?></p>
    <div class="assinaturas"><div><span></span><?= e(t('assinatura_diretor_turma')) ?></div><div><span></span><?= e(t('assinatura_encarregado')) ?></div></div>
</section>
<?php endif; ?>
