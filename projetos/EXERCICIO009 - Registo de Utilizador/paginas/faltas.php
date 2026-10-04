<?php
/** Faltas: o professor regista, a secretaria e a direção justificam, o aluno consulta. */
if (!defined('APP')) { http_response_code(403); exit; }

$u = utilizador();
$perfil = $u['perfil'];

// ---------- Aluno: só vê as suas ----------
if ($perfil === 'aluno') {
    $st = bd()->prepare('SELECT f.data, f.justificada, f.motivo, d.codigo, d.nome FROM faltas f JOIN disciplinas d ON d.id = f.disciplina_id WHERE f.aluno_id = ? ORDER BY f.data DESC');
    $st->execute([(int) $u['id']]);
    $lista = $st->fetchAll();
    [$total, $injust] = totalFaltas((int) $u['id']);
    ?>
    <section class="kpis kpis-3">
        <div class="kpi"><span class="kpi-valor"><?= $total ?></span><span class="kpi-rotulo"><?= e(t('faltas')) ?></span></div>
        <div class="kpi"><span class="kpi-valor"><?= $total - $injust ?></span><span class="kpi-rotulo"><?= e(t('justificadas')) ?></span></div>
        <div class="kpi <?= $injust >= FALTAS_RISCO ? 'kpi-aviso' : '' ?>"><span class="kpi-valor"><?= $injust ?></span><span class="kpi-rotulo"><?= e(t('injustificadas')) ?></span></div>
    </section>
    <?php if ($injust >= FALTAS_RISCO): ?><div class="mensagem erro"><?= icone('alerta') ?><span><?= e(t('aviso_faltas_risco', FALTAS_RISCO)) ?></span></div><?php endif; ?>
    <section class="cartao">
        <?php if (!$lista): ?><p class="suave"><?= e(t('sem_faltas')) ?></p><?php else: ?>
        <div class="tabela-rolagem"><table class="tabela">
            <thead><tr><th><?= e(t('data')) ?></th><th><?= e(t('disciplina')) ?></th><th><?= e(t('estado')) ?></th><th><?= e(t('motivo')) ?></th></tr></thead>
            <tbody><?php foreach ($lista as $f): ?>
                <tr><td><?= e(dataFmt($f['data'])) ?></td><td><?= e(nomeDisciplina($f)) ?></td>
                    <td><span class="etiqueta <?= $f['justificada'] ? 'pos' : 'neg' ?>"><?= e(t($f['justificada'] ? 'justificada' : 'injustificada')) ?></span></td>
                    <td><?= e($f['motivo'] ?? '') ?></td></tr>
            <?php endforeach; ?></tbody>
        </table></div>
        <?php endif; ?>
    </section>
    <?php
    return;
}

// ---------- Professor: registar as faltas de uma aula ----------
if ($perfil === 'professor') {
    $lec = lecionacoesDoProfessor((int) $u['id']);
    $origem = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
    $escolha = is_string($origem['t'] ?? null) ? $origem['t'] : '';
    $data = is_string($origem['data'] ?? null) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $origem['data']) === 1 ? $origem['data'] : hojeISO();
    $turmaId = $discId = 0;
    if (preg_match('/^(\d+)-(\d+)$/', $escolha, $m) === 1 && professorLeciona((int) $u['id'], (int) $m[1], (int) $m[2])) {
        $turmaId = (int) $m[1];
        $discId = (int) $m[2];
    } elseif ($lec) {
        $turmaId = (int) $lec[0]['turma_id'];
        $discId = (int) $lec[0]['disciplina_id'];
        $escolha = $turmaId . '-' . $discId;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verificarCsrf();
        if ($turmaId === 0 || $data > hojeISO()) {
            aviso('erro', $turmaId === 0 ? 'sem_permissao_turma' : 'data_futura');
        } else {
            $marcados = is_array($_POST['faltou'] ?? null) ? array_map('intval', array_keys($_POST['faltou'])) : [];
            $sel = bd()->prepare('SELECT justificada FROM faltas WHERE aluno_id = ? AND disciplina_id = ? AND data = ?');
            $ins = bd()->prepare('INSERT INTO faltas (aluno_id, disciplina_id, data, justificada, registado_por) VALUES (?, ?, ?, 0, ?)');
            $del = bd()->prepare('DELETE FROM faltas WHERE aluno_id = ? AND disciplina_id = ? AND data = ?');
            bd()->beginTransaction();
            foreach (alunosDaTurma($turmaId) as $al) {   // só alunos desta turma
                $id = (int) $al['id'];
                $sel->execute([$id, $discId, $data]);
                $existe = $sel->fetch();
                if (in_array($id, $marcados, true) && !$existe) {
                    $ins->execute([$id, $discId, $data, (int) $u['id']]);
                } elseif (!in_array($id, $marcados, true) && $existe) {
                    $del->execute([$id, $discId, $data]);
                }
            }
            bd()->commit();
            aviso('ok', 'faltas_guardadas');
        }
        redirecionar(ligacao('faltas', ['t' => $escolha, 'data' => $data]));
    }

    $presentes = [];
    if ($turmaId) {
        $st = bd()->prepare('SELECT aluno_id, justificada FROM faltas WHERE disciplina_id = ? AND data = ?');
        $st->execute([$discId, $data]);
        foreach ($st->fetchAll() as $f) {
            $presentes[(int) $f['aluno_id']] = (int) $f['justificada'];
        }
    }
    ?>
    <form method="get" class="filtros cartao" data-auto>
        <input type="hidden" name="p" value="faltas">
        <label><span><?= e(t('turma_disciplina')) ?></span><select name="t">
            <?php foreach ($lec as $l): $v = $l['turma_id'] . '-' . $l['disciplina_id']; ?>
                <option value="<?= e($v) ?>"<?= $v === $escolha ? ' selected' : '' ?>><?= e($l['turma'] . ' · ' . nomeDisciplina($l)) ?></option>
            <?php endforeach; ?></select></label>
        <label><span><?= e(t('data')) ?></span><input type="date" name="data" value="<?= e($data) ?>" max="<?= e(hojeISO()) ?>"></label>
        <button type="submit" class="botao secundario"><?= e(t('ver')) ?></button>
    </form>
    <?php if ($turmaId): $alunos = alunosDaTurma($turmaId); ?>
    <section class="cartao">
        <h2><?= e(t('registar_faltas')) ?></h2>
        <p class="suave"><?= e(t('ajuda_faltas')) ?></p>
        <form method="post">
            <?= csrfCampo() ?><input type="hidden" name="t" value="<?= e($escolha) ?>"><input type="hidden" name="data" value="<?= e($data) ?>">
            <ul class="lista-presencas">
                <?php foreach ($alunos as $al): $id = (int) $al['id']; ?>
                    <li><label class="caixa-falta">
                        <input type="checkbox" name="faltou[<?= $id ?>]" value="1"<?= isset($presentes[$id]) ? ' checked' : '' ?>>
                        <span class="num-aluno"><?= e($al['numero']) ?></span><span><?= e($al['nome']) ?></span>
                        <?php if (isset($presentes[$id]) && $presentes[$id]): ?><small class="etiqueta pos"><?= e(t('justificada')) ?></small><?php endif; ?>
                    </label></li>
                <?php endforeach; ?>
            </ul>
            <p class="barra-acoes"><button type="submit" class="botao"><?= icone('guardar') ?><span><?= e(t('guardar_faltas')) ?></span></button></p>
        </form>
    </section>
    <?php else: ?><div class="cartao"><p class="suave"><?= e(t('sem_lecionacao')) ?></p></div><?php endif;
    return;
}

// ---------- Secretaria e direção: consultar e justificar ----------
$turmasLista = turmasTodas();
$turmaId = inteiro($_GET['turma'] ?? '');
$soInjust = ($_GET['injust'] ?? '') === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
    $id = inteiro($_POST['falta'] ?? '');
    $acao = $_POST['acao'] ?? '';
    if ($acao === 'justificar') {
        $st = bd()->prepare('UPDATE faltas SET justificada = 1, motivo = ? WHERE id = ?');
        $st->execute([limpar($_POST['motivo'] ?? '', 80) ?: t('motivo_omissao'), $id]);
        aviso('ok', 'falta_justificada');
    } elseif ($acao === 'anular') {
        $st = bd()->prepare('UPDATE faltas SET justificada = 0, motivo = NULL WHERE id = ?');
        $st->execute([$id]);
        aviso('ok', 'justificacao_anulada');
    }
    redirecionar(ligacao('faltas', array_filter(['turma' => $turmaId ?: null, 'injust' => $soInjust ? '1' : null])));
}

$sql = 'SELECT f.id, f.data, f.justificada, f.motivo, d.codigo, d.nome AS disc, u.nome AS aluno, a.numero, t.nome AS turma
          FROM faltas f JOIN disciplinas d ON d.id = f.disciplina_id JOIN utilizadores u ON u.id = f.aluno_id
          JOIN alunos a ON a.utilizador_id = u.id JOIN turmas t ON t.id = a.turma_id WHERE 1 = 1';
$args = [];
if ($turmaId) { $sql .= ' AND t.id = ?'; $args[] = $turmaId; }
if ($soInjust) { $sql .= ' AND f.justificada = 0'; }
$sql .= ' ORDER BY f.data DESC, t.nome, a.numero LIMIT 200';
$st = bd()->prepare($sql);
$st->execute($args);
$lista = $st->fetchAll();

$resumo = bd()->query(
    'SELECT u.nome, t.nome AS turma, COUNT(*) AS total, SUM(CASE WHEN f.justificada = 0 THEN 1 ELSE 0 END) AS injust
       FROM faltas f JOIN utilizadores u ON u.id = f.aluno_id JOIN alunos a ON a.utilizador_id = u.id JOIN turmas t ON t.id = a.turma_id
      GROUP BY f.aluno_id HAVING injust >= ' . FALTAS_RISCO . ' ORDER BY injust DESC, u.nome LIMIT 8'
)->fetchAll();
?>
<?php if ($resumo): ?>
<section class="cartao">
    <h2><?= e(t('alunos_em_risco_faltas', FALTAS_RISCO)) ?></h2>
    <div class="etiquetas"><?php foreach ($resumo as $r): ?><span class="etiqueta neg"><?= e($r['nome']) ?> · <?= e($r['turma']) ?> · <?= (int) $r['injust'] ?></span><?php endforeach; ?></div>
</section>
<?php endif; ?>
<form method="get" class="filtros cartao" data-auto>
    <input type="hidden" name="p" value="faltas">
    <label><span><?= e(t('turma')) ?></span><select name="turma"><option value="0"><?= e(t('todas')) ?></option>
        <?php foreach ($turmasLista as $t): ?><option value="<?= (int) $t['id'] ?>"<?= (int) $t['id'] === $turmaId ? ' selected' : '' ?>><?= e($t['nome']) ?></option><?php endforeach; ?></select></label>
    <label class="caixa-simples"><input type="checkbox" name="injust" value="1"<?= $soInjust ? ' checked' : '' ?>><span><?= e(t('so_injustificadas')) ?></span></label>
    <button type="submit" class="botao secundario"><?= e(t('filtrar')) ?></button>
</form>
<section class="cartao">
    <?php if (!$lista): ?><p class="suave"><?= e(t('sem_faltas')) ?></p><?php else: ?>
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th><?= e(t('data')) ?></th><th><?= e(t('aluno')) ?></th><th><?= e(t('turma')) ?></th><th><?= e(t('disciplina')) ?></th><th><?= e(t('estado')) ?></th><th></th></tr></thead>
        <tbody><?php foreach ($lista as $f): ?>
            <tr><td><?= e(dataFmt($f['data'])) ?></td><td><?= e($f['numero'] . ' · ' . $f['aluno']) ?></td><td><?= e($f['turma']) ?></td>
                <td><?= e(nomeDisciplina(['codigo' => $f['codigo'], 'nome' => $f['disc']])) ?></td>
                <td><span class="etiqueta <?= $f['justificada'] ? 'pos' : 'neg' ?>"><?= e(t($f['justificada'] ? 'justificada' : 'injustificada')) ?></span><?php if ($f['motivo']): ?><small class="motivo"><?= e($f['motivo']) ?></small><?php endif; ?></td>
                <td class="acoes">
                    <form method="post" class="form-linha">
                        <?= csrfCampo() ?><input type="hidden" name="falta" value="<?= (int) $f['id'] ?>">
                        <?php if ($f['justificada']): ?>
                            <input type="hidden" name="acao" value="anular"><button type="submit" class="botao secundario pequeno"><?= e(t('anular_justificacao')) ?></button>
                        <?php else: ?>
                            <input type="hidden" name="acao" value="justificar"><input type="text" name="motivo" maxlength="80" placeholder="<?= e(t('motivo')) ?>" aria-label="<?= e(t('motivo')) ?>">
                            <button type="submit" class="botao secundario pequeno"><?= e(t('justificar')) ?></button>
                        <?php endif; ?>
                    </form></td></tr>
        <?php endforeach; ?></tbody>
    </table></div>
    <?php endif; ?>
</section>
