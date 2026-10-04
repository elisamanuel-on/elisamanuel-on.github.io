<?php
/** Entrada: login real (email + palavra-passe) e botões "Entrar como…" para a demonstração. */
if (!defined('APP')) { http_response_code(403); exit; }

$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
    if (isset($_POST['demo']) && is_string($_POST['demo'])) {
        if (entrarDemo($_POST['demo'])) {
            redirecionar(ligacao('painel'));
        }
        aviso('erro', 'login_invalido');
    } else {
        $email = limpar($_POST['email'] ?? '', 120);
        $erro = entrar($email, is_string($_POST['password'] ?? null) ? $_POST['password'] : '');
        if ($erro === null) {
            redirecionar(ligacao('painel'));
        }
        aviso('erro', $erro);
    }
    redirecionar('?');
}
?>
<div class="entrada-marca">
    <img src="logo.svg?v=<?= e(VERSAO) ?>" alt="" width="64" height="64">
    <h1><?= e(t('escola')) ?></h1>
    <p><?= e(t('login_subtitulo')) ?></p>
</div>

<form method="post" class="cartao formulario" autocomplete="on">
    <?= csrfCampo() ?>
    <label>
        <span><?= e(t('email')) ?></span>
        <input type="email" name="email" value="<?= e($email) ?>" required autocomplete="username" maxlength="120">
    </label>
    <label>
        <span><?= e(t('password')) ?></span>
        <input type="password" name="password" required autocomplete="current-password" maxlength="200">
    </label>
    <button type="submit" class="botao bloco"><?= e(t('entrar')) ?></button>
</form>

<section class="cartao demo">
    <h2><?= e(t('demo_titulo')) ?></h2>
    <p class="suave"><?= e(t('demo_texto', PASSWORD_DEMO)) ?></p>
    <form method="post" class="demo-botoes">
        <?= csrfCampo() ?>
        <?php foreach (PERFIS as $p): ?>
            <button type="submit" name="demo" value="<?= e($p) ?>" class="botao secundario demo-<?= e($p) ?>">
                <?= e(t('entrar_como', t('perfil_' . $p))) ?>
            </button>
        <?php endforeach; ?>
    </form>
</section>
<p class="rodape-entrada"><?= e(t('versao')) ?> <?= e(VERSAO) ?> · <?= e(t('demo_aviso')) ?></p>
