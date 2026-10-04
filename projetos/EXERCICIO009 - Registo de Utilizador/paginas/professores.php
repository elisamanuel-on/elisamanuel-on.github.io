<?php
/** Gestão de professores (secretaria e direção): contas e quem leciona cada disciplina em cada turma. */
if (!defined('APP')) { http_response_code(403); exit; }

$turmasLista = turmasTodas();
$discLista = disciplinasTodas();
$db = bd();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
    $acao = $_POST['acao'] ?? '';
    $id = inteiro($_POST['id'] ?? '');

    if ($acao === 'criar') {
        $nome = limpar($_POST['nome'] ?? '', 80);
        $email = mb_strtolower(limpar($_POST['email'] ?? '', 120));
        if ($nome === '') {
            aviso('erro', 'dados_invalidos');
        } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            aviso('erro', 'email_invalido');
        } else {
            $email = $email !== '' ? $email : emailDisponivel($nome);
            $existe = $db->prepare('SELECT 1 FROM utilizadores WHERE email = ?');
            $existe->execute([$email]);
            if ($existe->fetchColumn()) {
                aviso('erro', 'email_existe');
            } else {
                $pw = gerarPassword();
                $db->prepare("INSERT INTO utilizadores (nome, email, password_hash, perfil, criado_em) VALUES (?, ?, ?, 'professor', ?)")
                   ->execute([$nome, $email, password_hash($pw, PASSWORD_DEFAULT), date('Y-m-d H:i:s')]);
                aviso('ok', 'conta_criada', $email, $pw);
            }
        }
    } elseif ($acao === 'atribuir') {
        $turma = inteiro($_POST['turma'] ?? '');
        $disc = inteiro($_POST['disciplina'] ?? '');
        $st = $db->prepare("SELECT 1 FROM utilizadores WHERE id = ? AND perfil = 'professor' AND ativo = 1");
        $st->execute([$id]);
        if ($st->fetchColumn() && turmaPorId($turma) && disciplinaPorId($disc)) {
            $db->prepare('INSERT INTO lecionacao (turma_id, disciplina_id, professor_id) VALUES (?, ?, ?)
                          ON CONFLICT (turma_id, disciplina_id) DO UPDATE SET professor_id = excluded.professor_id')->execute([$turma, $disc, $id]);
            aviso('ok', 'atribuicao_guardada');
        } else {
            aviso('erro', 'dados_invalidos');
        }
    } elseif ($acao === 'estado' || $acao === 'password') {
        $st = $db->prepare("SELECT id, email, ativo FROM utilizadores WHERE id = ? AND perfil = 'professor'");
        $st->execute([$id]);
        $p = $st->fetch();
        if (!$p) {
            aviso('erro', 'dados_invalidos');
        } elseif (in_array($p['email'], CONTAS_DEMO, true)) {
            aviso('erro', 'conta_demo_protegida');
        } elseif ($acao === 'estado') {
            $tem = $db->prepare('SELECT COUNT(*) FROM lecionacao WHERE professor_id = ?');
            $tem->execute([$id]);
            if ($p['ativo'] && (int) $tem->fetchColumn() > 0) {
                aviso('erro', 'professor_com_aulas');
            } else {
                $db->prepare('UPDATE utilizadores SET ativo = ? WHERE id = ?')->execute([$p['ativo'] ? 0 : 1, $id]);
                aviso('ok', $p['ativo'] ? 'conta_desativada' : 'conta_ativada');
            }
        } else {
            $pw = gerarPassword();
            $db->prepare('UPDATE utilizadores SET password_hash = ? WHERE id = ?')->execute([password_hash($pw, PASSWORD_DEFAULT), $id]);
            aviso('ok', 'password_reposta', $p['email'], $pw);
        }
    }
    redirecionar(ligacao('professores'));
}

$profs = $db->query("SELECT id, nome, email, ativo FROM utilizadores WHERE perfil = 'professor' ORDER BY ativo DESC, nome")->fetchAll();
$aulas = [];
foreach ($db->query('SELECT l.professor_id, t.nome AS turma, d.codigo, d.nome FROM lecionacao l JOIN turmas t ON t.id = l.turma_id JOIN disciplinas d ON d.id = l.disciplina_id ORDER BY t.ano, t.nome, d.id')->fetchAll() as $l) {
    $aulas[(int) $l['professor_id']][] = $l['turma'] . ' · ' . nomeDisciplina($l);
}
$ativos = array_values(array_filter($profs, static fn ($p) => $p['ativo']));
?>
<section class="cartao">
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th><?= e(t('nome')) ?></th><th><?= e(t('email')) ?></th><th><?= e(t('leciona')) ?></th><th><?= e(t('estado')) ?></th><th></th></tr></thead>
        <tbody>
        <?php foreach ($profs as $p): ?>
            <tr class="<?= $p['ativo'] ? '' : 'inativo' ?>">
                <td><?= e($p['nome']) ?></td><td class="pequeno"><?= e($p['email']) ?></td>
                <td class="pequeno"><?php $l = $aulas[(int) $p['id']] ?? []; echo $l ? e(implode(' · ', array_slice($l, 0, 4))) . (count($l) > 4 ? ' … +' . (count($l) - 4) : '') : '—'; ?></td>
                <td><span class="etiqueta <?= $p['ativo'] ? 'pos' : '' ?>"><?= e(t($p['ativo'] ? 'ativo' : 'inativo')) ?></span></td>
                <td class="acoes"><form method="post" class="form-linha"><?= csrfCampo() ?><input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                    <button type="submit" name="acao" value="password" class="botao secundario pequeno" data-confirmar-botao="<?= e(t('confirmar_repor_password')) ?>"><?= e(t('repor_password')) ?></button>
                    <button type="submit" name="acao" value="estado" class="botao secundario pequeno"><?= e(t($p['ativo'] ? 'desativar' : 'ativar')) ?></button></form></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</section>
<div class="duas-colunas">
<section class="cartao">
    <h2><?= e(t('novo_professor')) ?></h2>
    <form method="post" class="formulario">
        <?= csrfCampo() ?><input type="hidden" name="acao" value="criar">
        <label><span><?= e(t('nome')) ?></span><input type="text" name="nome" maxlength="80" required></label>
        <label><span><?= e(t('email')) ?> <small>(<?= e(t('opcional')) ?>)</small></span><input type="email" name="email" maxlength="120"></label>
        <button type="submit" class="botao"><?= icone('mais') ?><span><?= e(t('criar_conta')) ?></span></button>
    </form>
</section>
<section class="cartao">
    <h2><?= e(t('atribuir_disciplina')) ?></h2>
    <form method="post" class="formulario">
        <?= csrfCampo() ?><input type="hidden" name="acao" value="atribuir">
        <label><span><?= e(t('professor')) ?></span><select name="id"><?php foreach ($ativos as $p): ?><option value="<?= (int) $p['id'] ?>"><?= e($p['nome']) ?></option><?php endforeach; ?></select></label>
        <div class="linha-campos">
            <label><span><?= e(t('turma')) ?></span><select name="turma"><?php foreach ($turmasLista as $t): ?><option value="<?= (int) $t['id'] ?>"><?= e($t['nome']) ?></option><?php endforeach; ?></select></label>
            <label><span><?= e(t('disciplina')) ?></span><select name="disciplina"><?php foreach ($discLista as $d): ?><option value="<?= (int) $d['id'] ?>"><?= e(nomeDisciplina($d)) ?></option><?php endforeach; ?></select></label>
        </div>
        <button type="submit" class="botao"><?= e(t('atribuir')) ?></button>
        <p class="suave"><?= e(t('ajuda_atribuir')) ?></p>
    </form>
</section>
</div>
