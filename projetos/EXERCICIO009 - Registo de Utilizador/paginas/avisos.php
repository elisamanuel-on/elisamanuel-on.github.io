<?php
/** Avisos: todos leem; secretaria, direção e professores (às suas turmas) publicam. */
if (!defined('APP')) { http_response_code(403); exit; }

$u = utilizador();
$perfil = $u['perfil'];
$podePublicar = $perfil !== 'aluno';

// Destinos que este utilizador pode escolher
$destinos = [];
if ($perfil === 'secretaria' || $perfil === 'direcao') {
    $destinos['todos'] = t('para_todos');
    $destinos['aluno'] = t('para_alunos');
    $destinos['professor'] = t('para_professores');
    foreach (turmasTodas() as $t) {
        $destinos['turma:' . $t['id']] = t('para_turma', $t['nome']);
    }
} elseif ($perfil === 'professor') {
    foreach (turmasPermitidas() as $t) {
        $destinos['turma:' . $t['id']] = t('para_turma', $t['nome']);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
    $acao = $_POST['acao'] ?? '';
    if ($acao === 'publicar' && $podePublicar) {
        $titulo = limpar($_POST['titulo'] ?? '', 80);
        $texto = limparTexto($_POST['texto'] ?? '', 1000);
        $para = is_string($_POST['para'] ?? null) ? $_POST['para'] : '';
        if ($titulo === '' || $texto === '' || !isset($destinos[$para])) {
            aviso('erro', 'aviso_invalido');
        } else {
            $st = bd()->prepare('INSERT INTO avisos (titulo, texto, autor_id, para, criado_em) VALUES (?, ?, ?, ?, ?)');
            $st->execute([$titulo, $texto, (int) $u['id'], $para, date('Y-m-d H:i:s')]);
            aviso('ok', 'aviso_publicado');
        }
    } elseif ($acao === 'apagar') {
        $id = inteiro($_POST['aviso'] ?? '');
        if ($perfil === 'secretaria' || $perfil === 'direcao') {
            $st = bd()->prepare('DELETE FROM avisos WHERE id = ?');
            $st->execute([$id]);
        } else {   // os professores só apagam os seus
            $st = bd()->prepare('DELETE FROM avisos WHERE id = ? AND autor_id = ?');
            $st->execute([$id, (int) $u['id']]);
        }
        aviso('ok', 'aviso_apagado');
    }
    redirecionar(ligacao('avisos'));
}

$lista = avisosParaMim();
?>
<div class="duas-colunas largo-esq">
<section>
    <?php if (!$lista): ?><div class="cartao"><p class="suave"><?= e(t('sem_avisos')) ?></p></div><?php endif; ?>
    <?php foreach ($lista as $v):
        $meu = (int) $v['autor_id'] === (int) $u['id'];
        $posso = $perfil === 'secretaria' || $perfil === 'direcao' || ($perfil === 'professor' && $meu); ?>
        <article class="cartao aviso">
            <header>
                <h2><?= e($v['titulo']) ?></h2>
                <?php if ($posso): ?>
                <form method="post" data-confirmar="<?= e(t('confirmar_apagar_aviso')) ?>">
                    <?= csrfCampo() ?><input type="hidden" name="acao" value="apagar"><input type="hidden" name="aviso" value="<?= (int) $v['id'] ?>">
                    <button type="submit" class="icone-botao perigo" title="<?= e(t('apagar')) ?>" aria-label="<?= e(t('apagar')) ?>"><?= icone('lixo') ?></button>
                </form>
                <?php endif; ?>
            </header>
            <p class="meta"><?= e(dataFmt(substr($v['criado_em'], 0, 10))) ?> · <?= e($v['autor']) ?> · <span class="etiqueta"><?= e(destinoAviso($v['para'])) ?></span></p>
            <p class="texto-aviso"><?= nl2br(e($v['texto'])) ?></p>
        </article>
    <?php endforeach; ?>
</section>
<?php if ($podePublicar && $destinos): ?>
<section class="cartao">
    <h2><?= e(t('novo_aviso')) ?></h2>
    <form method="post" class="formulario">
        <?= csrfCampo() ?><input type="hidden" name="acao" value="publicar">
        <label><span><?= e(t('titulo')) ?></span><input type="text" name="titulo" maxlength="80" required></label>
        <label><span><?= e(t('para')) ?></span><select name="para">
            <?php foreach ($destinos as $v => $rotulo): ?><option value="<?= e($v) ?>"><?= e($rotulo) ?></option><?php endforeach; ?></select></label>
        <label><span><?= e(t('mensagem')) ?></span><textarea name="texto" rows="5" maxlength="1000" required></textarea></label>
        <button type="submit" class="botao"><?= e(t('publicar')) ?></button>
    </form>
</section>
<?php endif; ?>
</div>
