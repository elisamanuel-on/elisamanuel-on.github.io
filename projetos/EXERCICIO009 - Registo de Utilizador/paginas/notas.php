<?php
/** Lançamento de notas (professor): criar avaliações e lançar as notas de cada aluno. */
if (!defined('APP')) { http_response_code(403); exit; }

$u = utilizador();
$lec = lecionacoesDoProfessor((int) $u['id']);

// Seleção: "turma-disciplina" e período (no endereço ou no formulário)
$origem = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
$escolha = is_string($origem['t'] ?? null) ? $origem['t'] : '';
$periodo = max(1, min(3, inteiro($origem['periodo'] ?? '1') ?: 1));
$turmaId = $discId = 0;
if (preg_match('/^(\d+)-(\d+)$/', $escolha, $m) === 1) {
    $turmaId = (int) $m[1];
    $discId = (int) $m[2];
}
if (!professorLeciona((int) $u['id'], $turmaId, $discId)) {
    $turmaId = $discId = 0;
    if ($lec && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        $turmaId = (int) $lec[0]['turma_id'];
        $discId = (int) $lec[0]['disciplina_id'];
        $escolha = $turmaId . '-' . $discId;
    }
}
$destino = ligacao('notas', ['t' => $turmaId . '-' . $discId, 'periodo' => $periodo]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
    if ($turmaId === 0) {
        aviso('erro', 'sem_permissao_turma');
        redirecionar(ligacao('notas'));
    }
    $acao = is_string($_POST['acao'] ?? null) ? $_POST['acao'] : '';

    if ($acao === 'nova_avaliacao') {
        $titulo = limpar($_POST['titulo'] ?? '', 60);
        $tipo = in_array($_POST['tipo'] ?? '', ['teste', 'trabalho', 'participacao'], true) ? $_POST['tipo'] : 'teste';
        $peso = inteiro($_POST['peso'] ?? '');
        $data = is_string($_POST['data'] ?? null) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['data']) === 1 ? $_POST['data'] : hojeISO();
        if ($titulo === '' || $peso < 1 || $peso > 100) {
            aviso('erro', 'avaliacao_invalida');
        } else {
            $st = bd()->prepare('INSERT INTO avaliacoes (turma_id, disciplina_id, periodo, titulo, tipo, peso, data, criado_por) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $st->execute([$turmaId, $discId, $periodo, $titulo, $tipo, $peso, $data, (int) $u['id']]);
            aviso('ok', 'avaliacao_criada');
        }
    } elseif ($acao === 'apagar_avaliacao') {
        $id = inteiro($_POST['avaliacao'] ?? '');
        $st = bd()->prepare('DELETE FROM avaliacoes WHERE id = ? AND turma_id = ? AND disciplina_id = ? AND periodo = ?');
        $st->execute([$id, $turmaId, $discId, $periodo]);
        aviso('ok', 'avaliacao_apagada');
    } elseif ($acao === 'guardar_notas') {
        $pauta = pautaDe($turmaId, $discId, $periodo);
        $alunosIds = array_map(static fn ($l) => (int) $l['aluno']['id'], $pauta['linhas']);
        $avIds = array_map(static fn ($a) => (int) $a['id'], $pauta['avaliacoes']);
        $enviadas = is_array($_POST['nota'] ?? null) ? $_POST['nota'] : [];
        $ins = bd()->prepare('INSERT INTO notas (avaliacao_id, aluno_id, valor) VALUES (?, ?, ?) ON CONFLICT (avaliacao_id, aluno_id) DO UPDATE SET valor = excluded.valor');
        $del = bd()->prepare('DELETE FROM notas WHERE avaliacao_id = ? AND aluno_id = ?');
        $invalidas = 0;
        bd()->beginTransaction();
        foreach ($avIds as $av) {
            foreach ($alunosIds as $al) {
                $bruto = $enviadas[$av][$al] ?? null; // só avaliações e alunos desta turma entram
                if ($bruto === null) {
                    continue;
                }
                $valor = lerNota($bruto);
                if ($valor === false) {
                    $invalidas++;
                } elseif ($valor === null) {
                    $del->execute([$av, $al]);
                } else {
                    $ins->execute([$av, $al, $valor]);
                }
            }
        }
        bd()->commit();
        $invalidas > 0 ? aviso('erro', 'notas_invalidas', $invalidas) : aviso('ok', 'notas_guardadas');
    }
    redirecionar($destino);
}

$pauta = $turmaId ? pautaDe($turmaId, $discId, $periodo) : null;
$turmaAtual = $turmaId ? turmaPorId($turmaId) : null;
$discAtual = $discId ? disciplinaPorId($discId) : null;
$somaPesos = $pauta ? array_sum(array_map(static fn ($a) => (int) $a['peso'], $pauta['avaliacoes'])) : 0;
?>
<form method="get" class="filtros cartao" data-auto>
    <input type="hidden" name="p" value="notas">
    <label><span><?= e(t('turma_disciplina')) ?></span>
        <select name="t">
            <?php foreach ($lec as $l): $v = $l['turma_id'] . '-' . $l['disciplina_id']; ?>
                <option value="<?= e($v) ?>"<?= $v === $escolha ? ' selected' : '' ?>><?= e($l['turma'] . ' · ' . nomeDisciplina($l)) ?></option>
            <?php endforeach; ?>
        </select></label>
    <label><span><?= e(t('periodo')) ?></span>
        <select name="periodo">
            <?php for ($p = 1; $p <= 3; $p++): ?>
                <option value="<?= $p ?>"<?= $p === $periodo ? ' selected' : '' ?>><?= e(t('periodo_n', $p)) ?></option>
            <?php endfor; ?>
        </select></label>
    <button type="submit" class="botao secundario"><?= e(t('ver')) ?></button>
</form>

<?php if (!$pauta): ?>
    <div class="cartao"><p class="suave"><?= e(t('sem_lecionacao')) ?></p></div>
<?php else: ?>
<div class="duas-colunas largo-esq">
<section class="cartao">
    <h2><?= e(t('avaliacoes')) ?> · <?= e($turmaAtual['nome']) ?> · <?= e(nomeDisciplina($discAtual)) ?></h2>
    <?php if (!$pauta['avaliacoes']): ?>
        <p class="suave"><?= e(t('sem_avaliacoes')) ?></p>
    <?php else: ?>
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th><?= e(t('avaliacao')) ?></th><th><?= e(t('tipo')) ?></th><th><?= e(t('data')) ?></th><th class="num"><?= e(t('peso')) ?></th><th></th></tr></thead>
        <tbody>
        <?php foreach ($pauta['avaliacoes'] as $a): ?>
            <tr><td><?= e($a['titulo']) ?></td><td><?= e(t('tipo_' . $a['tipo'])) ?></td><td><?= e(dataFmt($a['data'])) ?></td><td class="num"><?= e($a['peso']) ?>%</td>
                <td class="acoes">
                    <form method="post" data-confirmar="<?= e(t('confirmar_apagar_avaliacao')) ?>">
                        <?= csrfCampo() ?><input type="hidden" name="t" value="<?= e($turmaId . '-' . $discId) ?>"><input type="hidden" name="periodo" value="<?= $periodo ?>">
                        <input type="hidden" name="acao" value="apagar_avaliacao"><input type="hidden" name="avaliacao" value="<?= (int) $a['id'] ?>">
                        <button type="submit" class="icone-botao perigo" title="<?= e(t('apagar')) ?>" aria-label="<?= e(t('apagar')) ?>"><?= icone('lixo') ?></button>
                    </form></td></tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr><td colspan="3"><?= e(t('soma_pesos')) ?></td><td class="num"><span class="<?= $somaPesos === 100 ? 'texto-ok' : 'texto-aviso' ?>"><?= $somaPesos ?>%</span></td><td></td></tr></tfoot>
    </table></div>
    <?php if ($somaPesos !== 100): ?><p class="nota-info"><?= e(t('aviso_soma_pesos')) ?></p><?php endif; ?>
    <?php endif; ?>
</section>

<section class="cartao">
    <h2><?= e(t('nova_avaliacao')) ?></h2>
    <form method="post" class="formulario">
        <?= csrfCampo() ?><input type="hidden" name="t" value="<?= e($turmaId . '-' . $discId) ?>"><input type="hidden" name="periodo" value="<?= $periodo ?>">
        <input type="hidden" name="acao" value="nova_avaliacao">
        <label><span><?= e(t('titulo')) ?></span><input type="text" name="titulo" maxlength="60" required placeholder="<?= e(t('exemplo_teste')) ?>"></label>
        <div class="linha-campos">
            <label><span><?= e(t('tipo')) ?></span><select name="tipo">
                <?php foreach (['teste', 'trabalho', 'participacao'] as $tp): ?><option value="<?= $tp ?>"><?= e(t('tipo_' . $tp)) ?></option><?php endforeach; ?>
            </select></label>
            <label><span><?= e(t('peso')) ?> (%)</span><input type="number" name="peso" min="1" max="100" value="<?= max(1, min(100, 100 - $somaPesos)) ?>" required></label>
            <label><span><?= e(t('data')) ?></span><input type="date" name="data" value="<?= e(hojeISO()) ?>" required></label>
        </div>
        <button type="submit" class="botao"><?= icone('mais') ?><span><?= e(t('criar_avaliacao')) ?></span></button>
    </form>
</section>
</div>

<?php if ($pauta['avaliacoes'] && $pauta['linhas']): ?>
<section class="cartao">
    <h2><?= e(t('lancar_notas')) ?></h2>
    <p class="suave"><?= e(t('ajuda_lancar_notas')) ?></p>
    <form method="post" data-lancamento>
        <?= csrfCampo() ?><input type="hidden" name="t" value="<?= e($turmaId . '-' . $discId) ?>"><input type="hidden" name="periodo" value="<?= $periodo ?>">
        <input type="hidden" name="acao" value="guardar_notas">
        <div class="tabela-rolagem"><table class="tabela notas-tabela">
            <thead><tr>
                <th class="num"><?= e(t('numero_abrev')) ?></th><th><?= e(t('aluno')) ?></th>
                <?php foreach ($pauta['avaliacoes'] as $a): ?><th class="num"><?= e($a['titulo']) ?><small><?= e($a['peso']) ?>%</small></th><?php endforeach; ?>
                <th class="num"><?= e(t('media')) ?></th><th class="num"><?= e(t('nota_final')) ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ($pauta['linhas'] as $l): $al = $l['aluno']; ?>
                <tr data-linha>
                    <td class="num"><?= e($al['numero']) ?></td><td><?= e($al['nome']) ?></td>
                    <?php foreach ($pauta['avaliacoes'] as $a): $v = $l['notas'][(int) $a['id']]; ?>
                        <td class="num"><input type="text" inputmode="decimal" class="campo-nota" name="nota[<?= (int) $a['id'] ?>][<?= (int) $al['id'] ?>]"
                            value="<?= $v === null ? '' : e(numFmt($v, 1)) ?>" data-peso="<?= (int) $a['peso'] ?>" maxlength="4" aria-label="<?= e($al['nome'] . ' · ' . $a['titulo']) ?>"></td>
                    <?php endforeach; ?>
                    <td class="num js-media"><?= e(numFmt($l['media'], 1)) ?></td>
                    <td class="num js-final"><?= $l['final'] === null ? '—' : '<span class="nota ' . ($l['final'] >= NOTA_POSITIVA ? 'pos' : 'neg') . '">' . $l['final'] . '</span>' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <p class="barra-acoes"><button type="submit" class="botao"><?= icone('guardar') ?><span><?= e(t('guardar_notas')) ?></span></button>
            <a class="botao secundario" href="<?= e(ligacao('pauta', ['t' => $turmaId . '-' . $discId, 'periodo' => $periodo])) ?>"><?= e(t('ver_pauta')) ?></a></p>
    </form>
</section>
<?php endif; ?>
<?php endif; ?>
