<?php
/**
 * Registo de alterações financeiras: tudo o que a contabilidade editou, anulou ou apagou, com quem, quando, o que era e porquê.
 * Só leitura (contabilidade, direção e Conselho Geral). Ninguém pode apagar ou mudar este registo.
 *
 * PHP simples: é só um SELECT com filtros opcionais (cada filtro acrescenta uma condição e um valor ao prepare()).
 */
if (!defined('APP')) { http_response_code(403); exit; }

$db = bd();
$entidade = is_string($_GET['entidade'] ?? null) && in_array($_GET['entidade'], ENTIDADES_AUDITORIA, true) ? $_GET['entidade'] : '';
$acao = is_string($_GET['acao'] ?? null) && in_array($_GET['acao'], ACOES_AUDITORIA, true) ? $_GET['acao'] : '';
$procura = limpar($_GET['q'] ?? '', 40);

$sql = 'SELECT * FROM auditoria WHERE 1 = 1';
$args = [];
if ($entidade !== '') { $sql .= ' AND entidade = ?'; $args[] = $entidade; }
if ($acao !== '') { $sql .= ' AND acao = ?'; $args[] = $acao; }
if ($procura !== '') { $sql .= ' AND (resumo LIKE ? OR motivo LIKE ? OR utilizador_nome LIKE ?)'; array_push($args, "%$procura%", "%$procura%", "%$procura%"); }
$sql .= ' ORDER BY quando DESC, id DESC LIMIT 300';
$st = $db->prepare($sql);
$st->execute($args);
$linhas = $st->fetchAll();

$contagem = ['editar' => 0, 'apagar' => 0, 'anular' => 0];
foreach ($db->query('SELECT acao, COUNT(*) AS n FROM auditoria GROUP BY acao')->fetchAll() as $c) {
    $contagem[$c['acao']] = (int) $c['n'];
}

echo separadoresFinancas('alteracoes');
?>
<section class="kpis kpis-3">
    <?php foreach (['editar', 'anular', 'apagar'] as $a): ?>
    <div class="kpi"><span class="kpi-valor"><?= (int) $contagem[$a] ?></span><span class="kpi-rotulo"><?= e(t('acao_' . $a . '_n')) ?></span></div>
    <?php endforeach; ?>
</section>

<section class="cartao">
    <h2><?= e(t('registo_alteracoes')) ?></h2>
    <p class="suave"><?= e(t('ajuda_alteracoes')) ?></p>
    <form method="get" class="filtros sem-cartao" data-auto>
        <input type="hidden" name="p" value="alteracoes">
        <label><span><?= e(t('o_que')) ?></span><select name="entidade"><option value=""><?= e(t('todos')) ?></option>
            <?php foreach (ENTIDADES_AUDITORIA as $en): ?><option value="<?= e($en) ?>"<?= $en === $entidade ? ' selected' : '' ?>><?= e(t('entidade_' . $en)) ?></option><?php endforeach; ?></select></label>
        <label><span><?= e(t('acao')) ?></span><select name="acao"><option value=""><?= e(t('todas')) ?></option>
            <?php foreach (ACOES_AUDITORIA as $ac): ?><option value="<?= e($ac) ?>"<?= $ac === $acao ? ' selected' : '' ?>><?= e(t('acao_' . $ac)) ?></option><?php endforeach; ?></select></label>
        <label><span><?= e(t('procurar')) ?></span><input type="search" name="q" value="<?= e($procura) ?>" maxlength="40"></label>
        <button type="submit" class="botao secundario"><?= e(t('filtrar')) ?></button>
    </form>
    <?php if (!$linhas): ?><p class="suave"><?= e(t('sem_alteracoes')) ?></p><?php else: ?>
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th><?= e(t('quando')) ?></th><th><?= e(t('quem')) ?></th><th><?= e(t('acao')) ?></th><th><?= e(t('o_que')) ?></th><th><?= e(t('registo')) ?></th><th><?= e(t('motivo')) ?></th></tr></thead>
        <tbody>
        <?php foreach ($linhas as $l): ?>
            <tr><td class="pequeno"><?= e(dataFmt(substr($l['quando'], 0, 10))) ?><small class="motivo"><?= e(substr($l['quando'], 11, 5)) ?></small></td>
                <td class="pequeno"><?= e($l['utilizador_nome']) ?></td>
                <td><span class="etiqueta <?= $l['acao'] === 'apagar' ? 'neg' : '' ?>"><?= e(t('acao_' . $l['acao'])) ?></span></td>
                <td class="pequeno"><?= e(t('entidade_' . $l['entidade'])) ?></td>
                <td class="pequeno"><?= e($l['resumo']) ?></td>
                <td class="pequeno"><?= $l['motivo'] !== '' ? e($l['motivo']) : '<span class="suave">—</span>' ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</section>
