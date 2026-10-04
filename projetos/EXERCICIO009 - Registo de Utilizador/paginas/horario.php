<?php
/** Horário semanal: do aluno (a sua turma), do professor, ou de qualquer turma/professor para a secretaria e a direção. */
if (!defined('APP')) { http_response_code(403); exit; }

$u = utilizador();
$perfil = $u['perfil'];
$subtitulo = '';
$grelha = [];
$mostrarTurma = false;
$seletor = false;

if ($perfil === 'aluno') {
    $a = alunoPorId((int) $u['id']);
    $grelha = horarioDaTurma((int) $a['turma_id']);
    $subtitulo = t('turma') . ' ' . $a['turma'];
} elseif ($perfil === 'professor') {
    $grelha = horarioDoProfessor((int) $u['id']);
    $mostrarTurma = true;
    $subtitulo = $u['nome'];
} else {
    $seletor = true;
    $escolha = is_string($_GET['ver'] ?? null) ? $_GET['ver'] : '';
    $turmasLista = turmasTodas();
    $profs = bd()->query("SELECT id, nome FROM utilizadores WHERE perfil = 'professor' AND ativo = 1 ORDER BY nome")->fetchAll();
    if (preg_match('/^p(\d+)$/', $escolha, $m) === 1 && in_array((int) $m[1], array_map('intval', array_column($profs, 'id')), true)) {
        $grelha = horarioDoProfessor((int) $m[1]);
        $mostrarTurma = true;
        foreach ($profs as $p) { if ((int) $p['id'] === (int) $m[1]) { $subtitulo = $p['nome']; } }
    } else {
        $tid = preg_match('/^t(\d+)$/', $escolha, $m) === 1 && turmaPorId((int) $m[1]) ? (int) $m[1] : (int) ($turmasLista[0]['id'] ?? 0);
        $escolha = 't' . $tid;
        $tt = $tid ? turmaPorId($tid) : null;
        $grelha = $tid ? horarioDaTurma($tid) : [];
        $subtitulo = $tt ? t('turma') . ' ' . $tt['nome'] : '';
    }
}
$hoje = (int) date('N');
?>
<?php if ($seletor): ?>
<form method="get" class="filtros cartao" data-auto>
    <input type="hidden" name="p" value="horario">
    <label><span><?= e(t('horario_de')) ?></span><select name="ver">
        <optgroup label="<?= e(t('turmas')) ?>"><?php foreach ($turmasLista as $t): ?><option value="t<?= (int) $t['id'] ?>"<?= $escolha === 't' . $t['id'] ? ' selected' : '' ?>><?= e($t['nome']) ?></option><?php endforeach; ?></optgroup>
        <optgroup label="<?= e(t('professores')) ?>"><?php foreach ($profs as $p): ?><option value="p<?= (int) $p['id'] ?>"<?= $escolha === 'p' . $p['id'] ? ' selected' : '' ?>><?= e($p['nome']) ?></option><?php endforeach; ?></optgroup>
    </select></label>
    <button type="submit" class="botao secundario"><?= e(t('ver')) ?></button>
</form>
<?php endif; ?>
<section class="cartao">
    <h2><?= e($subtitulo) ?></h2>
    <div class="tabela-rolagem"><table class="tabela horario">
        <thead><tr><th><?= e(t('hora')) ?></th>
            <?php for ($d = 1; $d <= 5; $d++): ?><th<?= $d === $hoje ? ' class="hoje"' : '' ?>><?= e(t('dia_' . $d)) ?></th><?php endfor; ?></tr></thead>
        <tbody>
        <?php foreach (HORAS_AULA as $n => [$ini, $fim]): ?>
            <?php if ($n === 5): ?><tr class="almoco"><td colspan="6"><?= e(t('almoco')) ?></td></tr><?php endif; ?>
            <tr><th class="hora-celula"><?= e($ini) ?><small><?= e($fim) ?></small></th>
                <?php for ($d = 1; $d <= 5; $d++): $c = $grelha[$d][$n] ?? null; ?>
                    <td class="<?= $d === $hoje ? 'hoje' : '' ?>"><?php if ($c): ?>
                        <strong><?= e(nomeDisciplina($c)) ?></strong>
                        <small><?= e(($mostrarTurma ? $c['turma'] . ' · ' : '') . t('sala_' . $c['sala'])) ?></small>
                    <?php endif; ?></td>
                <?php endfor; ?></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</section>
