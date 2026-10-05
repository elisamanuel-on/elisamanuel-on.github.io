<?php
/**
 * Registo de visitas à portaria: entrada, saída e histórico. A portaria regista; a secretaria e a direção consultam.
 * Guardam-se só os dados necessários: nome, motivo, destino e horas; e, se vier por uma empresa (fornecedor, manutenção),
 * o nome da empresa, o NIF dela e um contacto. Tudo isto é opcional, exceto o nome e o motivo.
 */
if (!defined('APP')) { http_response_code(403); exit; }

$u = utilizador();
$db = bd();
$regista = temPerfil('portaria');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
    if (!$regista) {
        http_response_code(403);
        exit(t('erro_403_texto'));
    }
    $acao = $_POST['acao'] ?? '';
    $id = inteiro($_POST['id'] ?? '');

    if ($acao === 'entrada') {
        $nome = limpar($_POST['visitante'] ?? '', 80);
        $motivo = is_string($_POST['motivo'] ?? null) && in_array($_POST['motivo'], MOTIVOS_VISITA, true) ? $_POST['motivo'] : null;
        $destino = limpar($_POST['destino'] ?? '', 60);
        $empresa = limpar($_POST['empresa'] ?? '', 80);
        $nif = lerNif($_POST['nif'] ?? '');                    // NIF da empresa: '' se vazio, null se estiver errado
        $contacto = limpar($_POST['contacto'] ?? '', 20);
        if ($nome === '' || $motivo === null || preg_match('/^[0-9 +]*$/', $contacto) !== 1) {
            aviso('erro', 'dados_invalidos');
        } elseif ($nif === null) {
            aviso('erro', 'nif_invalido');
        } else {
            $db->prepare('INSERT INTO visitas (visitante, motivo, destino, empresa, nif, contacto, entrada, registado_por) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
               ->execute([$nome, $motivo, $destino, $empresa, $nif, $contacto, date('Y-m-d H:i:s'), (int) $u['id']]);
            aviso('ok', 'visita_entrada', $nome);
        }
    } elseif ($acao === 'saida') {
        $st = $db->prepare('UPDATE visitas SET saida = ? WHERE id = ? AND saida IS NULL');
        $st->execute([date('Y-m-d H:i:s'), $id]);
        aviso($st->rowCount() ? 'ok' : 'erro', $st->rowCount() ? 'visita_saida' : 'dados_invalidos');
    } elseif ($acao === 'apagar') {
        // só se apaga uma entrada registada por engano: ainda sem saída e feita hoje
        $st = $db->prepare("DELETE FROM visitas WHERE id = ? AND saida IS NULL AND substr(entrada, 1, 10) = ?");
        $st->execute([$id, hojeISO()]);
        aviso($st->rowCount() ? 'ok' : 'erro', $st->rowCount() ? 'visita_apagada' : 'visita_nao_apaga');
    }
    redirecionar(ligacao('visitas'));
}

$data = dataValida($_GET['data'] ?? null) ?? hojeISO();
$dentro = $db->query('SELECT v.*, u.nome AS por FROM visitas v JOIN utilizadores u ON u.id = v.registado_por WHERE v.saida IS NULL ORDER BY v.entrada')->fetchAll();
$st = $db->prepare('SELECT v.*, u.nome AS por FROM visitas v JOIN utilizadores u ON u.id = v.registado_por WHERE substr(v.entrada, 1, 10) = ? ORDER BY v.entrada DESC');
$st->execute([$data]);
$dia = $st->fetchAll();
$duracoes = [];
foreach ($dia as $v) {
    if ($v['saida']) {
        $duracoes[] = (strtotime($v['saida']) - strtotime($v['entrada'])) / 60;
    }
}
$media = $duracoes ? (int) round(array_sum($duracoes) / count($duracoes)) : null;
$hora = static fn (?string $dh): string => $dh ? substr($dh, 11, 5) : '—';
/** Empresa, NIF e contacto por baixo do nome (só o que foi preenchido). */
$detalheVisita = static function (array $v): string {
    $partes = array_filter([$v['empresa'], $v['nif'] !== '' ? 'NIF ' . $v['nif'] : '', $v['contacto']], static fn ($x) => $x !== '');
    return $partes ? '<span class="entidade-linha">' . e(implode(' · ', $partes)) . '</span>' : '';
};
$minutos = static function (array $v): string {
    $fim = $v['saida'] ? strtotime($v['saida']) : time();
    $m = max(0, (int) round(($fim - strtotime($v['entrada'])) / 60));
    return $m >= 60 ? intdiv($m, 60) . ' h ' . ($m % 60) . ' min' : $m . ' min';
};
?>
<section class="kpis kpis-3">
    <div class="kpi"><span class="kpi-valor"><?= count($dia) ?></span><span class="kpi-rotulo"><?= e(t('visitas_no_dia', dataFmt($data))) ?></span></div>
    <div class="kpi <?= $dentro ? 'kpi-aviso' : '' ?>"><span class="kpi-valor"><?= count($dentro) ?></span><span class="kpi-rotulo"><?= e(t('no_edificio_agora')) ?></span></div>
    <div class="kpi"><span class="kpi-valor"><?= e($media !== null ? $media . ' min' : '—') ?></span><span class="kpi-rotulo"><?= e(t('duracao_media')) ?></span></div>
</section>

<div class="<?= $regista ? 'duas-colunas largo-esq' : '' ?>">
<div>
<section class="cartao">
    <h2><?= e(t('no_edificio_agora')) ?></h2>
    <?php if (!$dentro): ?><p class="suave"><?= e(t('ninguem_no_edificio')) ?></p><?php else: ?>
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th><?= e(t('visitante')) ?></th><th><?= e(t('motivo')) ?></th><th><?= e(t('destino')) ?></th><th><?= e(t('entrada')) ?></th><?php if ($regista): ?><th></th><?php endif; ?></tr></thead>
        <tbody>
        <?php foreach ($dentro as $v): ?>
            <tr><td><?= e($v['visitante']) ?><?= $detalheVisita($v) ?></td><td class="pequeno"><?= e(t('motivo_visita_' . $v['motivo'])) ?></td><td class="pequeno"><?= e($v['destino'] !== '' ? $v['destino'] : '—') ?></td>
                <td class="pequeno"><?= e(substr($v['entrada'], 0, 10) === hojeISO() ? $hora($v['entrada']) : dataFmt(substr($v['entrada'], 0, 10)) . ' ' . $hora($v['entrada'])) ?><small class="motivo"><?= e($minutos($v)) ?></small></td>
                <?php if ($regista): ?><td class="acoes"><form method="post" class="form-linha"><?= csrfCampo() ?><input type="hidden" name="id" value="<?= (int) $v['id'] ?>">
                    <button type="submit" name="acao" value="saida" class="botao verde pequeno"><?= e(t('registar_saida')) ?></button>
                    <?php if (substr($v['entrada'], 0, 10) === hojeISO()): ?><button type="submit" name="acao" value="apagar" class="icone-botao perigo pequeno" title="<?= e(t('apagar')) ?>" aria-label="<?= e(t('apagar')) ?>" data-confirmar-botao="<?= e(t('confirmar_apagar_visita')) ?>"><?= icone('lixo') ?></button><?php endif; ?></form></td><?php endif; ?></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</section>

<section class="cartao">
    <h2><?= e(t('historico_visitas')) ?></h2>
    <form method="get" class="filtros sem-cartao" data-auto>
        <input type="hidden" name="p" value="visitas">
        <label><span><?= e(t('data')) ?></span><input type="date" name="data" value="<?= e($data) ?>" max="<?= e(hojeISO()) ?>"></label>
        <button type="submit" class="botao secundario"><?= e(t('filtrar')) ?></button>
    </form>
    <?php if (!$dia): ?><p class="suave"><?= e(t('sem_visitas')) ?></p><?php else: ?>
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th><?= e(t('visitante')) ?></th><th><?= e(t('motivo')) ?></th><th><?= e(t('destino')) ?></th><th><?= e(t('entrada')) ?></th><th><?= e(t('saida')) ?></th><th><?= e(t('registado_por')) ?></th></tr></thead>
        <tbody>
        <?php foreach ($dia as $v): ?>
            <tr><td><?= e($v['visitante']) ?><?= $detalheVisita($v) ?></td><td class="pequeno"><?= e(t('motivo_visita_' . $v['motivo'])) ?></td><td class="pequeno"><?= e($v['destino'] !== '' ? $v['destino'] : '—') ?></td>
                <td><?= e($hora($v['entrada'])) ?></td><td><?= $v['saida'] ? e($hora($v['saida'])) . '<small class="motivo">' . e($minutos($v)) . '</small>' : '<span class="etiqueta">' . e(t('no_edificio')) . '</span>' ?></td>
                <td class="pequeno"><?= e($v['por']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</section>
</div>

<?php if ($regista): ?>
<section class="cartao">
    <h2><?= e(t('registar_entrada')) ?></h2>
    <form method="post" class="formulario">
        <?= csrfCampo() ?><input type="hidden" name="acao" value="entrada">
        <label><span><?= e(t('visitante')) ?></span><input type="text" name="visitante" maxlength="80" required autocomplete="off"></label>
        <label><span><?= e(t('empresa_representa')) ?> <small>(<?= e(t('opcional')) ?>)</small></span><input type="text" name="empresa" maxlength="80" autocomplete="off" placeholder="<?= e(t('entidade_exemplo')) ?>"></label>
        <div class="linha-campos">
            <label><span><?= e(t('nif_empresa')) ?> <small>(<?= e(t('opcional')) ?>)</small></span><input type="text" inputmode="numeric" name="nif" maxlength="14" autocomplete="off" placeholder="9 <?= e(t('digitos')) ?>"></label>
            <label><span><?= e(t('contacto')) ?> <small>(<?= e(t('opcional')) ?>)</small></span><input type="text" inputmode="tel" name="contacto" maxlength="20" autocomplete="off"></label>
        </div>
        <label><span><?= e(t('motivo')) ?></span><select name="motivo"><?php foreach (MOTIVOS_VISITA as $m): ?><option value="<?= e($m) ?>"><?= e(t('motivo_visita_' . $m)) ?></option><?php endforeach; ?></select></label>
        <label><span><?= e(t('destino')) ?> <small>(<?= e(t('opcional')) ?>)</small></span><input type="text" name="destino" maxlength="60" placeholder="<?= e(t('destino_exemplo')) ?>"></label>
        <button type="submit" class="botao"><?= icone('mais') ?><span><?= e(t('registar_entrada')) ?></span></button>
        <p class="suave pequeno"><?= e(t('ajuda_visitas')) ?></p>
    </form>
</section>
<?php endif; ?>
</div>
