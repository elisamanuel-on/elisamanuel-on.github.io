<?php
/** Pauta de uma turma e disciplina, pronta para imprimir. */
if (!defined('APP')) { http_response_code(403); exit; }

$combos = lecionacoesPermitidas();
$escolha = is_string($_GET['t'] ?? null) ? $_GET['t'] : '';
$periodo = max(1, min(3, inteiro($_GET['periodo'] ?? '1') ?: 1));
$turmaId = $discId = 0;
if (preg_match('/^(\d+)-(\d+)$/', $escolha, $m) === 1 && podeAbrirLecionacao((int) $m[1], (int) $m[2])) {
    $turmaId = (int) $m[1];
    $discId = (int) $m[2];
} elseif ($combos) {
    $turmaId = (int) $combos[0]['turma_id'];
    $discId = (int) $combos[0]['disciplina_id'];
    $escolha = $turmaId . '-' . $discId;
}
$pauta = $turmaId ? pautaDe($turmaId, $discId, $periodo) : null;
$turmaAtual = $turmaId ? turmaPorId($turmaId) : null;
$discAtual = $discId ? disciplinaPorId($discId) : null;
?>
<form method="get" class="filtros cartao nao-imprimir" data-auto>
    <input type="hidden" name="p" value="pauta">
    <label><span><?= e(t('turma_disciplina')) ?></span>
        <select name="t">
            <?php $grupo = null; foreach ($combos as $l):
                if (!temPerfil('professor') && $grupo !== $l['turma']) { if ($grupo !== null) { echo '</optgroup>'; } $grupo = $l['turma']; echo '<optgroup label="' . e($l['turma']) . '">'; }
                $v = $l['turma_id'] . '-' . $l['disciplina_id']; ?>
                <option value="<?= e($v) ?>"<?= $v === $escolha ? ' selected' : '' ?>><?= e(temPerfil('professor') ? $l['turma'] . ' · ' . nomeDisciplina($l) : nomeDisciplina($l)) ?></option>
            <?php endforeach; if ($grupo !== null) { echo '</optgroup>'; } ?>
        </select></label>
    <label><span><?= e(t('periodo')) ?></span>
        <select name="periodo"><?php for ($p = 1; $p <= 3; $p++): ?><option value="<?= $p ?>"<?= $p === $periodo ? ' selected' : '' ?>><?= e(t('periodo_n', $p)) ?></option><?php endfor; ?></select></label>
    <button type="submit" class="botao secundario"><?= e(t('ver')) ?></button>
    <?php if ($pauta): ?><button type="button" class="botao" data-imprimir><?= icone('imprimir') ?><span><?= e(t('imprimir')) ?></span></button><?php endif; ?>
</form>

<?php if (!$pauta): ?>
    <div class="cartao"><p class="suave"><?= e(t('sem_lecionacao')) ?></p></div>
<?php else: ?>
<section class="cartao folha">
    <header class="folha-cabeca">
        <div><strong><?= e(t('escola')) ?></strong><span><?= e(t('ano_letivo', ANO_LETIVO)) ?></span></div>
        <div><h2><?= e(t('pauta_titulo')) ?></h2>
            <span><?= e(t('turma')) ?> <?= e($turmaAtual['nome']) ?> · <?= e(nomeDisciplina($discAtual)) ?> · <?= e(t('periodo_n', $periodo)) ?></span></div>
    </header>
    <?php if (!$pauta['linhas']): ?>
        <p class="suave"><?= e(t('sem_alunos')) ?></p>
    <?php else: ?>
    <div class="tabela-rolagem"><table class="tabela pauta">
        <thead><tr>
            <th class="num"><?= e(t('numero_abrev')) ?></th><th><?= e(t('aluno')) ?></th>
            <?php foreach ($pauta['avaliacoes'] as $a): ?><th class="num"><?= e($a['titulo']) ?><small><?= e($a['peso']) ?>%</small></th><?php endforeach; ?>
            <th class="num"><?= e(t('media')) ?></th><th class="num"><?= e(t('nota_final')) ?></th><th><?= e(t('resultado')) ?></th>
        </tr></thead>
        <tbody>
        <?php foreach ($pauta['linhas'] as $l): ?>
            <tr><td class="num"><?= e($l['aluno']['numero']) ?></td><td><?= e($l['aluno']['nome']) ?></td>
                <?php foreach ($pauta['avaliacoes'] as $a): $v = $l['notas'][(int) $a['id']]; ?>
                    <td class="num"><?= $v === null ? '—' : '<span class="nota ' . ($v >= NOTA_POSITIVA ? 'pos' : 'neg') . '">' . e(numFmt($v, 1)) . '</span>' ?></td>
                <?php endforeach; ?>
                <td class="num"><?= e(numFmt($l['media'], 1)) ?></td>
                <td class="num"><?= $l['final'] === null ? '—' : '<strong>' . $l['final'] . '</strong>' ?></td>
                <td><?php if ($l['final'] === null): ?>—<?php elseif ($l['final'] >= NOTA_POSITIVA): ?><span class="etiqueta pos"><?= e(t('positiva')) ?></span><?php else: ?><span class="etiqueta neg"><?= e(t('negativa')) ?></span><?php endif; ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <p class="resumo-pauta">
        <span><?= e(t('positivas')) ?>: <strong><?= $pauta['resumo']['positivas'] ?></strong></span>
        <span><?= e(t('negativas')) ?>: <strong><?= $pauta['resumo']['negativas'] ?></strong></span>
        <span><?= e(t('media_turma')) ?>: <strong><?= e(numFmt($pauta['resumo']['media'], 1)) ?></strong></span>
    </p>
    <div class="assinaturas"><div><span></span><?= e(t('assinatura_professor')) ?></div><div><span></span><?= e(t('assinatura_direcao')) ?></div></div>
    <?php endif; ?>
</section>
<?php endif; ?>
