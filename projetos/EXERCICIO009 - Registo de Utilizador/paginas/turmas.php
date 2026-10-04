<?php
/** Gestão de turmas (secretaria e direção). */
if (!defined('APP')) { http_response_code(403); exit; }

$db = bd();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
    $acao = $_POST['acao'] ?? '';
    if ($acao === 'criar') {
        $nome = limpar($_POST['nome'] ?? '', 12);
        $ano = inteiro($_POST['ano'] ?? '');
        $existe = $db->prepare('SELECT 1 FROM turmas WHERE nome = ?');
        $existe->execute([$nome]);
        if ($nome === '' || $ano < 5 || $ano > 12) {
            aviso('erro', 'dados_invalidos');
        } elseif ($existe->fetchColumn()) {
            aviso('erro', 'turma_existe');
        } else {
            $db->prepare('INSERT INTO turmas (nome, ano) VALUES (?, ?)')->execute([$nome, $ano]);
            aviso('ok', 'turma_criada');
        }
    } elseif ($acao === 'apagar') {
        $id = inteiro($_POST['id'] ?? '');
        $n = $db->prepare('SELECT COUNT(*) FROM alunos WHERE turma_id = ?');
        $n->execute([$id]);
        if ((int) $n->fetchColumn() > 0) {
            aviso('erro', 'turma_com_alunos');
        } else {
            $db->prepare('DELETE FROM turmas WHERE id = ?')->execute([$id]);
            aviso('ok', 'turma_apagada');
        }
    }
    redirecionar(ligacao('turmas'));
}

$lista = $db->query(
    'SELECT t.id, t.nome, t.ano,
            (SELECT COUNT(*) FROM alunos a JOIN utilizadores u ON u.id = a.utilizador_id WHERE a.turma_id = t.id AND u.ativo = 1) AS alunos,
            (SELECT COUNT(*) FROM lecionacao l WHERE l.turma_id = t.id) AS aulas
       FROM turmas t ORDER BY t.ano, t.nome'
)->fetchAll();
?>
<div class="duas-colunas largo-esq">
<section class="cartao">
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th><?= e(t('turma')) ?></th><th class="num"><?= e(t('ano')) ?></th><th class="num"><?= e(t('alunos')) ?></th><th class="num"><?= e(t('disciplinas')) ?></th><th></th></tr></thead>
        <tbody>
        <?php foreach ($lista as $tt): ?>
            <tr><td><strong><?= e($tt['nome']) ?></strong></td><td class="num"><?= e($tt['ano']) ?>.º</td><td class="num"><?= (int) $tt['alunos'] ?></td><td class="num"><?= (int) $tt['aulas'] ?></td>
                <td class="acoes">
                    <a class="botao secundario pequeno" href="<?= e(ligacao('alunos', ['turma' => $tt['id']])) ?>"><?= e(t('ver_alunos')) ?></a>
                    <form method="post" class="form-linha" data-confirmar="<?= e(t('confirmar_apagar_turma')) ?>"><?= csrfCampo() ?><input type="hidden" name="acao" value="apagar"><input type="hidden" name="id" value="<?= (int) $tt['id'] ?>">
                        <button type="submit" class="icone-botao perigo" title="<?= e(t('apagar')) ?>" aria-label="<?= e(t('apagar')) ?>"><?= icone('lixo') ?></button></form></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</section>
<section class="cartao">
    <h2><?= e(t('nova_turma')) ?></h2>
    <form method="post" class="formulario">
        <?= csrfCampo() ?><input type="hidden" name="acao" value="criar">
        <label><span><?= e(t('nome')) ?></span><input type="text" name="nome" maxlength="12" required placeholder="7.ºC"></label>
        <label><span><?= e(t('ano')) ?></span><input type="number" name="ano" min="5" max="12" value="7" required></label>
        <button type="submit" class="botao"><?= icone('mais') ?><span><?= e(t('criar_turma')) ?></span></button>
        <p class="suave"><?= e(t('ajuda_nova_turma')) ?></p>
    </form>
</section>
</div>
