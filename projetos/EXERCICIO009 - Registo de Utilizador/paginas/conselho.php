<?php
/**
 * Conselho Geral: destitui e nomeia a direção, com data, motivo e histórico.
 * Nada se apaga: o diretor destituído perde o acesso (conta desativada), mas o registo, os avisos e os recibos mantêm-se.
 * O Conselho pode corrigir os dados de qualquer diretor e nomear outro quando não há ninguém em funções.
 */
if (!defined('APP')) { http_response_code(403); exit; }

$u = utilizador();
$db = bd();

function diretorPorId(int $id): ?array
{
    $st = bd()->prepare("SELECT id, nome, email, ativo FROM utilizadores WHERE id = ? AND perfil = 'direcao'");
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

function registarHistorico(int $diretor, string $nome, string $acao, string $data, string $motivo): void
{
    bd()->prepare('INSERT INTO historico_direcao (utilizador_id, nome, acao, data, motivo, por_id, criado_em) VALUES (?, ?, ?, ?, ?, ?, ?)')
        ->execute([$diretor, $nome, $acao, $data, $motivo, (int) utilizador()['id'], date('Y-m-d H:i:s')]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
    $acao = $_POST['acao'] ?? '';
    $id = inteiro($_POST['id'] ?? '');

    if ($acao === 'destituir') {
        $d = diretorPorId($id);
        $data = dataValida($_POST['data'] ?? null);
        $motivo = limpar($_POST['motivo'] ?? '', 300);
        if (!$d || !$d['ativo'] || $data === null) {
            aviso('erro', 'dados_invalidos');
        } elseif (mb_strlen($motivo) < 8) {
            aviso('erro', 'motivo_curto');
        } else {
            $db->beginTransaction();
            $db->prepare('UPDATE utilizadores SET ativo = 0 WHERE id = ?')->execute([$id]);
            registarHistorico($id, $d['nome'], 'destituicao', $data, $motivo);
            $db->commit();
            aviso('ok', 'diretor_destituido', $d['nome']);
        }
    } elseif ($acao === 'nomear') {
        $nome = limpar($_POST['nome'] ?? '', 80);
        $email = mb_strtolower(limpar($_POST['email'] ?? '', 120));
        $data = dataValida($_POST['data'] ?? null);
        $emFuncoes = (int) $db->query("SELECT COUNT(*) FROM utilizadores WHERE perfil = 'direcao' AND ativo = 1")->fetchColumn();
        if ($emFuncoes > 0) {
            aviso('erro', 'ja_existe_direcao');
        } elseif ($nome === '' || $data === null) {
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
                $db->prepare("INSERT INTO utilizadores (nome, email, password_hash, perfil, criado_em) VALUES (?, ?, ?, 'direcao', ?)")
                   ->execute([$nome, $email, password_hash($pw, PASSWORD_DEFAULT), date('Y-m-d H:i:s')]);
                registarHistorico((int) $db->lastInsertId(), $nome, 'nomeacao', $data, '');
                $db->commit();
                aviso('ok', 'diretor_nomeado', $nome);
                aviso('ok', 'conta_criada', $email, $pw);
            }
        }
    } elseif ($acao === 'editar') {
        $d = diretorPorId($id);
        $nome = limpar($_POST['nome'] ?? '', 80);
        $email = mb_strtolower(limpar($_POST['email'] ?? '', 120));
        $motivo = limpar($_POST['motivo'] ?? '', 300);
        if (!$d || $nome === '') {
            aviso('erro', 'dados_invalidos');
        } else {
            if (in_array($d['email'], CONTAS_DEMO, true)) {
                $email = $d['email'];     // o email da conta de demonstração não muda (é o que o botão "Entrar como" usa)
            }
            $outro = $db->prepare('SELECT 1 FROM utilizadores WHERE email = ? AND id <> ?');
            $outro->execute([$email, $id]);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                aviso('erro', 'email_invalido');
            } elseif ($outro->fetchColumn()) {
                aviso('erro', 'email_existe');
            } else {
                $db->beginTransaction();
                $db->prepare('UPDATE utilizadores SET nome = ?, email = ? WHERE id = ?')->execute([$nome, $email, $id]);
                registarHistorico($id, $nome, 'edicao', hojeISO(), $motivo !== '' ? $motivo : t('dados_alterados'));
                $db->commit();
                aviso('ok', 'dados_guardados');
                redirecionar(ligacao('conselho'));
            }
        }
        redirecionar(ligacao('conselho', ['editar' => $id]));
    } elseif ($acao === 'password') {
        $d = diretorPorId($id);
        if (!$d || !$d['ativo']) {
            aviso('erro', 'dados_invalidos');
        } elseif (in_array($d['email'], CONTAS_DEMO, true)) {
            aviso('erro', 'conta_demo_protegida');
        } else {
            $pw = gerarPassword();
            $db->prepare('UPDATE utilizadores SET password_hash = ? WHERE id = ?')->execute([password_hash($pw, PASSWORD_DEFAULT), $id]);
            aviso('ok', 'password_reposta', $d['email'], $pw);
        }
    } elseif ($acao === 'repor') {
        reporDemonstracao();
        terminarSessao();
        redirecionar('?');
    }
    redirecionar(ligacao('conselho'));
}

$diretores = $db->query("SELECT id, nome, email, ativo FROM utilizadores WHERE perfil = 'direcao' ORDER BY ativo DESC, id DESC")->fetchAll();
$emFuncoes = null;
foreach ($diretores as $d) {
    if ($d['ativo']) {
        $emFuncoes = $d;
    }
}
$desde = [];
foreach ($db->query("SELECT utilizador_id, MAX(data) AS d FROM historico_direcao WHERE acao = 'nomeacao' GROUP BY utilizador_id")->fetchAll() as $l) {
    $desde[(int) $l['utilizador_id']] = $l['d'];
}
$fim = [];
foreach ($db->query("SELECT utilizador_id, MAX(data) AS d FROM historico_direcao WHERE acao = 'destituicao' GROUP BY utilizador_id")->fetchAll() as $l) {
    $fim[(int) $l['utilizador_id']] = $l['d'];
}
$historico = $db->query('SELECT h.*, u.nome AS por FROM historico_direcao h JOIN utilizadores u ON u.id = h.por_id ORDER BY h.criado_em DESC, h.id DESC')->fetchAll();
$destituirId = inteiro($_GET['destituir'] ?? '');
$editarId = inteiro($_GET['editar'] ?? '');
$aDestituir = $destituirId ? diretorPorId($destituirId) : null;
$aEditar = $editarId ? diretorPorId($editarId) : null;
$nomeacoes = count(array_filter($historico, static fn ($h) => $h['acao'] === 'nomeacao'));
$destituicoes = count(array_filter($historico, static fn ($h) => $h['acao'] === 'destituicao'));
?>
<section class="kpis">
    <div class="kpi"><span class="kpi-valor kpi-texto"><?= e($emFuncoes['nome'] ?? '—') ?></span><span class="kpi-rotulo"><?= e(t('diretor_em_funcoes')) ?></span></div>
    <div class="kpi"><span class="kpi-valor"><?= $nomeacoes ?></span><span class="kpi-rotulo"><?= e(t('nomeacoes')) ?></span></div>
    <div class="kpi"><span class="kpi-valor"><?= $destituicoes ?></span><span class="kpi-rotulo"><?= e(t('destituicoes')) ?></span></div>
    <a class="kpi" href="<?= e(ligacao('financeiro')) ?>"><span class="kpi-valor"><?= e(euro(resumoFinanceiro()['total']['resultado'])) ?></span><span class="kpi-rotulo"><?= e(t('resultado_fin')) ?></span></a>
</section>

<div class="duas-colunas largo-esq">
<section class="cartao">
    <h2><?= e(t('direcao_do_colegio')) ?></h2>
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th><?= e(t('nome')) ?></th><th><?= e(t('email')) ?></th><th><?= e(t('mandato')) ?></th><th><?= e(t('estado')) ?></th><th></th></tr></thead>
        <tbody>
        <?php foreach ($diretores as $d): ?>
            <tr class="<?= $d['ativo'] ? '' : 'inativo' ?>">
                <td><?= e($d['nome']) ?></td><td class="pequeno"><?= e($d['email']) ?></td>
                <td class="pequeno"><?= e(dataFmt($desde[(int) $d['id']] ?? null)) ?><?= isset($fim[(int) $d['id']]) && !$d['ativo'] ? ' → ' . e(dataFmt($fim[(int) $d['id']])) : '' ?></td>
                <td><span class="etiqueta <?= $d['ativo'] ? 'pos' : 'neg' ?>"><?= e($d['ativo'] ? t('em_funcoes') : t('destituido')) ?></span></td>
                <td class="acoes">
                    <a class="botao secundario pequeno" href="<?= e(ligacao('conselho', ['editar' => $d['id']])) ?>"><?= e(t('editar')) ?></a>
                    <?php if ($d['ativo']): ?>
                        <a class="botao pequeno botao-perigo" href="<?= e(ligacao('conselho', ['destituir' => $d['id']])) ?>"><?= e(t('destituir')) ?></a>
                        <form method="post" class="form-linha"><?= csrfCampo() ?><input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
                            <button type="submit" name="acao" value="password" class="botao secundario pequeno" data-confirmar-botao="<?= e(t('confirmar_repor_password')) ?>"><?= e(t('repor_password')) ?></button></form>
                    <?php endif; ?>
                </td></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <p class="suave pequeno"><?= e(t('nota_sem_apagar')) ?></p>
</section>

<section class="cartao">
<?php if ($aDestituir && $aDestituir['ativo']): ?>
    <h2><?= e(t('destituir_diretor')) ?></h2>
    <p><strong><?= e($aDestituir['nome']) ?></strong><br><span class="suave"><?= e($aDestituir['email']) ?></span></p>
    <form method="post" class="formulario" data-confirmar="<?= e(t('confirmar_destituir')) ?>">
        <?= csrfCampo() ?><input type="hidden" name="acao" value="destituir"><input type="hidden" name="id" value="<?= (int) $aDestituir['id'] ?>">
        <label><span><?= e(t('data_destituicao')) ?></span><input type="date" name="data" value="<?= e(hojeISO()) ?>" max="<?= e(hojeISO()) ?>" required></label>
        <label><span><?= e(t('motivo')) ?></span><textarea name="motivo" rows="4" maxlength="300" minlength="8" required></textarea></label>
        <p class="suave pequeno"><?= e(t('ajuda_destituir')) ?></p>
        <p class="barra-acoes"><button type="submit" class="botao botao-perigo"><?= e(t('destituir')) ?></button> <a class="botao secundario" href="<?= e(ligacao('conselho')) ?>"><?= e(t('cancelar')) ?></a></p>
    </form>
<?php elseif ($aEditar): ?>
    <h2><?= e(t('editar_diretor')) ?></h2>
    <form method="post" class="formulario">
        <?= csrfCampo() ?><input type="hidden" name="acao" value="editar"><input type="hidden" name="id" value="<?= (int) $aEditar['id'] ?>">
        <label><span><?= e(t('nome')) ?></span><input type="text" name="nome" value="<?= e($aEditar['nome']) ?>" maxlength="80" required></label>
        <label><span><?= e(t('email')) ?></span><input type="email" name="email" value="<?= e($aEditar['email']) ?>" maxlength="120" required<?= in_array($aEditar['email'], CONTAS_DEMO, true) ? ' readonly' : '' ?>></label>
        <label><span><?= e(t('motivo')) ?> <small>(<?= e(t('opcional')) ?>)</small></span><input type="text" name="motivo" maxlength="300"></label>
        <?php if (!$aEditar['ativo']): ?><p class="nota-info"><?= e(t('edicao_destituido')) ?></p><?php endif; ?>
        <p class="barra-acoes"><button type="submit" class="botao"><?= e(t('guardar')) ?></button> <a class="botao secundario" href="<?= e(ligacao('conselho')) ?>"><?= e(t('cancelar')) ?></a></p>
    </form>
<?php else: ?>
    <h2><?= e(t('nomear_diretor')) ?></h2>
    <?php if ($emFuncoes): ?>
        <p class="suave"><?= e(t('ja_existe_direcao_info', $emFuncoes['nome'])) ?></p>
    <?php else: ?>
    <form method="post" class="formulario">
        <?= csrfCampo() ?><input type="hidden" name="acao" value="nomear">
        <label><span><?= e(t('nome')) ?></span><input type="text" name="nome" maxlength="80" required></label>
        <label><span><?= e(t('email')) ?> <small>(<?= e(t('opcional')) ?>)</small></span><input type="email" name="email" maxlength="120"></label>
        <label><span><?= e(t('data_inicio')) ?></span><input type="date" name="data" value="<?= e(hojeISO()) ?>" max="<?= e(hojeISO()) ?>" required></label>
        <button type="submit" class="botao"><?= icone('mais') ?><span><?= e(t('nomear')) ?></span></button>
        <p class="suave"><?= e(t('ajuda_nova_conta')) ?></p>
    </form>
    <?php endif; ?>
<?php endif; ?>
</section>
</div>

<section class="cartao">
    <h2><?= e(t('historico_direcao')) ?></h2>
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th><?= e(t('data')) ?></th><th><?= e(t('acao')) ?></th><th><?= e(t('nome')) ?></th><th><?= e(t('motivo')) ?></th><th><?= e(t('registado_por')) ?></th></tr></thead>
        <tbody>
        <?php foreach ($historico as $h): ?>
            <tr><td><?= e(dataFmt($h['data'])) ?></td>
                <td><span class="etiqueta <?= $h['acao'] === 'nomeacao' ? 'pos' : ($h['acao'] === 'destituicao' ? 'neg' : '') ?>"><?= e(t('hist_' . $h['acao'])) ?></span></td>
                <td><?= e($h['nome']) ?></td><td><?= e($h['motivo'] !== '' ? $h['motivo'] : '—') ?></td><td class="pequeno"><?= e($h['por']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</section>

<section class="cartao zona-demo">
    <h2><?= e(t('repor_demonstracao')) ?></h2>
    <p class="suave"><?= e(t('repor_texto')) ?></p>
    <form method="post" data-confirmar="<?= e(t('confirmar_repor_demo')) ?>">
        <?= csrfCampo() ?><input type="hidden" name="acao" value="repor">
        <button type="submit" class="botao secundario"><?= e(t('repor_botao')) ?></button>
    </form>
</section>
