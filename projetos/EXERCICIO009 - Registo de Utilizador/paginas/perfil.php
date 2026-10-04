<?php
/** Dados da conta e mudança de palavra-passe. */
if (!defined('APP')) { http_response_code(403); exit; }

$u = utilizador();
$ehDemo = in_array($u['email'], CONTAS_DEMO, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
    $atual = is_string($_POST['atual'] ?? null) ? $_POST['atual'] : '';
    $nova = is_string($_POST['nova'] ?? null) ? $_POST['nova'] : '';
    $confirma = is_string($_POST['confirma'] ?? null) ? $_POST['confirma'] : '';
    $st = bd()->prepare('SELECT password_hash FROM utilizadores WHERE id = ?');
    $st->execute([(int) $u['id']]);
    if ($ehDemo) {
        aviso('erro', 'perfil_demo_bloqueada');
    } elseif (!password_verify($atual, (string) $st->fetchColumn())) {
        aviso('erro', 'perfil_atual_errada');
    } elseif (mb_strlen($nova) < 8) {
        aviso('erro', 'perfil_curta');
    } elseif ($nova !== $confirma) {
        aviso('erro', 'perfil_diferentes');
    } else {
        $st = bd()->prepare('UPDATE utilizadores SET password_hash = ? WHERE id = ?');
        $st->execute([password_hash($nova, PASSWORD_DEFAULT), (int) $u['id']]);
        aviso('ok', 'perfil_alterada');
    }
    redirecionar(ligacao('perfil'));
}
?>
<div class="duas-colunas">
    <section class="cartao">
        <h2><?= e(t('os_meus_dados')) ?></h2>
        <dl class="dados">
            <dt><?= e(t('nome')) ?></dt><dd><?= e($u['nome']) ?></dd>
            <dt><?= e(t('email')) ?></dt><dd><?= e($u['email']) ?></dd>
            <dt><?= e(t('perfil')) ?></dt><dd><?= e(t('perfil_' . $u['perfil'])) ?></dd>
            <?php if ($u['perfil'] === 'aluno'): $a = alunoPorId((int) $u['id']); ?>
                <dt><?= e(t('turma')) ?></dt><dd><?= e($a['turma']) ?> · <?= e(t('numero_abrev')) ?> <?= e($a['numero']) ?></dd>
                <dt><?= e(t('nascimento')) ?></dt><dd><?= e(dataFmt($a['nascimento'])) ?></dd>
            <?php endif; ?>
        </dl>
    </section>
    <section class="cartao">
        <h2><?= e(t('mudar_password')) ?></h2>
        <?php if ($ehDemo): ?>
            <p class="suave"><?= e(t('perfil_demo_bloqueada')) ?></p>
        <?php else: ?>
        <form method="post" class="formulario">
            <?= csrfCampo() ?>
            <label><span><?= e(t('password_atual')) ?></span><input type="password" name="atual" required autocomplete="current-password"></label>
            <label><span><?= e(t('password_nova')) ?></span><input type="password" name="nova" required minlength="8" autocomplete="new-password"></label>
            <label><span><?= e(t('password_confirmar')) ?></span><input type="password" name="confirma" required minlength="8" autocomplete="new-password"></label>
            <button type="submit" class="botao"><?= e(t('guardar')) ?></button>
        </form>
        <?php endif; ?>
    </section>
</div>
