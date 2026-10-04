<?php
/** Gestão de alunos (secretaria e direção): criar, editar, mudar de turma, desativar e repor a palavra-passe. */
if (!defined('APP')) { http_response_code(403); exit; }

$turmasLista = turmasTodas();
$turmaIds = array_map('intval', array_column($turmasLista, 'id'));
$filtroTurma = inteiro($_GET['turma'] ?? '');
$procura = limpar($_GET['q'] ?? '', 40);
$editarId = inteiro($_GET['editar'] ?? '');

function dataNascimentoValida(mixed $v): ?string
{
    return is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) === 1 && strtotime($v) !== false && $v < hojeISO() ? $v : null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
    $acao = $_POST['acao'] ?? '';
    $id = inteiro($_POST['id'] ?? '');
    $db = bd();

    if ($acao === 'criar') {
        $nome = limpar($_POST['nome'] ?? '', 80);
        $turma = inteiro($_POST['turma'] ?? '');
        $email = mb_strtolower(limpar($_POST['email'] ?? '', 120));
        $nasc = dataNascimentoValida($_POST['nascimento'] ?? null);
        if ($nome === '' || !in_array($turma, $turmaIds, true)) {
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
                $db->beginTransaction();
                $db->prepare("INSERT INTO utilizadores (nome, email, password_hash, perfil, criado_em) VALUES (?, ?, ?, 'aluno', ?)")
                   ->execute([$nome, $email, password_hash($pw, PASSWORD_DEFAULT), date('Y-m-d H:i:s')]);
                $novo = (int) $db->lastInsertId();
                $numero = $db->prepare('SELECT COALESCE(MAX(numero), 0) + 1 FROM alunos WHERE turma_id = ?');
                $numero->execute([$turma]);
                $db->prepare('INSERT INTO alunos (utilizador_id, turma_id, numero, nascimento) VALUES (?, ?, ?, ?)')
                   ->execute([$novo, $turma, (int) $numero->fetchColumn(), $nasc]);
                $db->commit();
                aviso('ok', 'conta_criada', $email, $pw);
            }
        }
    } elseif ($acao === 'editar') {
        $nome = limpar($_POST['nome'] ?? '', 80);
        $turma = inteiro($_POST['turma'] ?? '');
        $nasc = dataNascimentoValida($_POST['nascimento'] ?? null);
        $atual = alunoPorId($id);
        if (!$atual || $nome === '' || !in_array($turma, $turmaIds, true)) {
            aviso('erro', 'dados_invalidos');
        } else {
            $db->beginTransaction();
            $db->prepare('UPDATE utilizadores SET nome = ? WHERE id = ?')->execute([$nome, $id]);
            if ($turma !== (int) $atual['turma_id']) {   // mudou de turma: passa a ter o próximo número da nova turma
                $numero = $db->prepare('SELECT COALESCE(MAX(numero), 0) + 1 FROM alunos WHERE turma_id = ?');
                $numero->execute([$turma]);
                $db->prepare('UPDATE alunos SET turma_id = ?, numero = ?, nascimento = ? WHERE utilizador_id = ?')->execute([$turma, (int) $numero->fetchColumn(), $nasc, $id]);
            } else {
                $db->prepare('UPDATE alunos SET nascimento = ? WHERE utilizador_id = ?')->execute([$nasc, $id]);
            }
            $db->commit();
            aviso('ok', 'dados_guardados');
        }
        redirecionar(ligacao('alunos'));
    } elseif ($acao === 'estado' || $acao === 'password') {
        $a = alunoPorId($id);
        if (!$a) {
            aviso('erro', 'dados_invalidos');
        } elseif (in_array($a['email'], CONTAS_DEMO, true)) {
            aviso('erro', 'conta_demo_protegida');
        } elseif ($acao === 'estado') {
            $db->prepare('UPDATE utilizadores SET ativo = ? WHERE id = ?')->execute([$a['ativo'] ? 0 : 1, $id]);
            aviso('ok', $a['ativo'] ? 'conta_desativada' : 'conta_ativada');
        } else {
            $pw = gerarPassword();
            $db->prepare('UPDATE utilizadores SET password_hash = ? WHERE id = ?')->execute([password_hash($pw, PASSWORD_DEFAULT), $id]);
            aviso('ok', 'password_reposta', $a['email'], $pw);
        }
    }
    redirecionar(ligacao('alunos', array_filter(['turma' => $filtroTurma ?: null, 'q' => $procura ?: null])));
}

$sql = 'SELECT u.id, u.nome, u.email, u.ativo, a.numero, a.nascimento, t.nome AS turma, t.id AS turma_id
          FROM alunos a JOIN utilizadores u ON u.id = a.utilizador_id JOIN turmas t ON t.id = a.turma_id WHERE 1 = 1';
$args = [];
if ($filtroTurma) { $sql .= ' AND t.id = ?'; $args[] = $filtroTurma; }
if ($procura !== '') { $sql .= ' AND (u.nome LIKE ? OR u.email LIKE ?)'; $args[] = '%' . $procura . '%'; $args[] = '%' . $procura . '%'; }
$sql .= ' ORDER BY t.ano, t.nome, a.numero';
$st = bd()->prepare($sql);
$st->execute($args);
$lista = $st->fetchAll();
$editar = $editarId ? alunoPorId($editarId) : null;
?>
<div class="duas-colunas largo-esq">
<section class="cartao">
    <form method="get" class="filtros sem-cartao" data-auto>
        <input type="hidden" name="p" value="alunos">
        <label><span><?= e(t('turma')) ?></span><select name="turma"><option value="0"><?= e(t('todas')) ?></option>
            <?php foreach ($turmasLista as $t): ?><option value="<?= (int) $t['id'] ?>"<?= (int) $t['id'] === $filtroTurma ? ' selected' : '' ?>><?= e($t['nome']) ?></option><?php endforeach; ?></select></label>
        <label><span><?= e(t('procurar')) ?></span><input type="search" name="q" value="<?= e($procura) ?>" maxlength="40"></label>
        <button type="submit" class="botao secundario"><?= e(t('filtrar')) ?></button>
    </form>
    <p class="suave"><?= e(t('n_resultados', count($lista))) ?></p>
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th class="num"><?= e(t('numero_abrev')) ?></th><th><?= e(t('nome')) ?></th><th><?= e(t('turma')) ?></th><th><?= e(t('email')) ?></th><th><?= e(t('estado')) ?></th><th></th></tr></thead>
        <tbody>
        <?php foreach ($lista as $a): ?>
            <tr class="<?= $a['ativo'] ? '' : 'inativo' ?>">
                <td class="num"><?= e($a['numero']) ?></td><td><?= e($a['nome']) ?></td><td><?= e($a['turma']) ?></td><td class="pequeno"><?= e($a['email']) ?></td>
                <td><span class="etiqueta <?= $a['ativo'] ? 'pos' : '' ?>"><?= e(t($a['ativo'] ? 'ativo' : 'inativo')) ?></span></td>
                <td class="acoes">
                    <a class="botao secundario pequeno" href="<?= e(ligacao('alunos', ['editar' => $a['id']])) ?>"><?= e(t('editar')) ?></a>
                    <form method="post" class="form-linha"><?= csrfCampo() ?><input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                        <button type="submit" name="acao" value="password" class="botao secundario pequeno" data-confirmar-botao="<?= e(t('confirmar_repor_password')) ?>"><?= e(t('repor_password')) ?></button>
                        <button type="submit" name="acao" value="estado" class="botao secundario pequeno"><?= e(t($a['ativo'] ? 'desativar' : 'ativar')) ?></button></form>
                </td></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</section>

<section class="cartao">
    <?php if ($editar): ?>
        <h2><?= e(t('editar_aluno')) ?></h2>
        <form method="post" class="formulario">
            <?= csrfCampo() ?><input type="hidden" name="acao" value="editar"><input type="hidden" name="id" value="<?= (int) $editar['id'] ?>">
            <label><span><?= e(t('nome')) ?></span><input type="text" name="nome" value="<?= e($editar['nome']) ?>" maxlength="80" required></label>
            <label><span><?= e(t('email')) ?></span><input type="email" value="<?= e($editar['email']) ?>" disabled></label>
            <label><span><?= e(t('turma')) ?></span><select name="turma"><?php foreach ($turmasLista as $t): ?><option value="<?= (int) $t['id'] ?>"<?= (int) $t['id'] === (int) $editar['turma_id'] ? ' selected' : '' ?>><?= e($t['nome']) ?></option><?php endforeach; ?></select></label>
            <label><span><?= e(t('nascimento')) ?></span><input type="date" name="nascimento" value="<?= e($editar['nascimento'] ?? '') ?>" max="<?= e(hojeISO()) ?>"></label>
            <p class="barra-acoes"><button type="submit" class="botao"><?= e(t('guardar')) ?></button> <a class="botao secundario" href="<?= e(ligacao('alunos')) ?>"><?= e(t('cancelar')) ?></a></p>
        </form>
    <?php else: ?>
        <h2><?= e(t('novo_aluno')) ?></h2>
        <form method="post" class="formulario">
            <?= csrfCampo() ?><input type="hidden" name="acao" value="criar">
            <label><span><?= e(t('nome')) ?></span><input type="text" name="nome" maxlength="80" required></label>
            <label><span><?= e(t('email')) ?> <small>(<?= e(t('opcional')) ?>)</small></span><input type="email" name="email" maxlength="120"></label>
            <label><span><?= e(t('turma')) ?></span><select name="turma"><?php foreach ($turmasLista as $t): ?><option value="<?= (int) $t['id'] ?>"<?= (int) $t['id'] === $filtroTurma ? ' selected' : '' ?>><?= e($t['nome']) ?></option><?php endforeach; ?></select></label>
            <label><span><?= e(t('nascimento')) ?></span><input type="date" name="nascimento" max="<?= e(hojeISO()) ?>"></label>
            <button type="submit" class="botao"><?= icone('mais') ?><span><?= e(t('criar_conta')) ?></span></button>
            <p class="suave"><?= e(t('ajuda_nova_conta')) ?></p>
        </form>
    <?php endif; ?>
</section>
</div>
