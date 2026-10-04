<?php
/**
 * Despesas e receitas da escola (fora das propinas e dos salários), com IVA.
 * Só a contabilidade cria, edita e apaga; a direção consulta.
 *
 * PHP simples, passo a passo (o mesmo padrão serve para propinas e salários):
 *   1. LER      SELECT * FROM lancamentos ...            (com filtros, sempre com ? e prepare())
 *   2. CRIAR    INSERT INTO lancamentos (...) VALUES (?, ?, ...)
 *   3. EDITAR   UPDATE lancamentos SET ... WHERE id = ?   (e fica registado quem mudou, o que era e porquê)
 *   4. APAGAR   DELETE FROM lancamentos WHERE id = ?      (só com justificação; o registo fica guardado)
 *   5. CALCULAR o IVA e o total em PHP (calcularIvaModo), e a página mostra a pré-visualização ao escrever.
 */
if (!defined('APP')) { http_response_code(403); exit; }

$u = utilizador();
$db = bd();
$edita = ehContabilista();
$filtroTipo = is_string($_GET['tipo'] ?? null) && isset(CATEGORIAS[$_GET['tipo']]) ? $_GET['tipo'] : '';
$mesFiltro = mesValido($_GET['mes'] ?? null);
$procura = limpar($_GET['q'] ?? '', 40);
$editarId = $edita ? inteiro($_GET['editar'] ?? '') : 0;
$apagarId = $edita ? inteiro($_GET['apagar'] ?? '') : 0;

function lancamentoPorId(int $id): ?array
{
    $st = bd()->prepare('SELECT * FROM lancamentos WHERE id = ?');
    $st->execute([$id]);
    return $st->fetch() ?: null;
}
$filtros = array_filter(['tipo' => $filtroTipo ?: null, 'mes' => $mesFiltro, 'q' => $procura ?: null], static fn ($v) => $v !== null);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
    if (!$edita) {
        http_response_code(403);
        exit(t('erro_403_texto'));
    }
    $acao = $_POST['acao'] ?? '';
    $id = inteiro($_POST['id'] ?? '');

    if ($acao === 'criar' || $acao === 'editar') {
        $tipo = is_string($_POST['tipo'] ?? null) && isset(CATEGORIAS[$_POST['tipo']]) ? $_POST['tipo'] : null;
        $categoria = is_string($_POST['categoria'] ?? null) ? $_POST['categoria'] : '';
        $descricao = limpar($_POST['descricao'] ?? '', 120);
        $data = dataValida($_POST['data'] ?? null, true);
        $base = lerDinheiro($_POST['base'] ?? null);      // valor escrito (sem IVA, ou com IVA se "modo" = com)
        $taxa = inteiro($_POST['taxa'] ?? '');
        $incluiIva = ($_POST['modo'] ?? 'sem') === 'com';
        if ($tipo === null || !in_array($categoria, CATEGORIAS[$tipo], true) || $descricao === '' || $data === null || $base === false || $base <= 0 || !in_array($taxa, TAXAS_IVA, true)) {
            aviso('erro', 'dados_invalidos');
            redirecionar(ligacao('lancamentos', $filtros + ($acao === 'editar' ? ['editar' => $id] : [])));
        }
        [$base, $iva, $total] = calcularIvaModo($base, $taxa, $incluiIva);
        if ($acao === 'criar') {
            $db->prepare('INSERT INTO lancamentos (tipo, categoria, descricao, data, base_cents, iva_taxa, iva_cents, total_cents, criado_por, criado_em) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
               ->execute([$tipo, $categoria, $descricao, $data, $base, $taxa, $iva, $total, (int) $u['id'], date('Y-m-d H:i:s')]);
            aviso('ok', 'lancamento_criado');
        } else {
            $antes = lancamentoPorId($id);
            if ($antes) {
                $db->prepare('UPDATE lancamentos SET tipo = ?, categoria = ?, descricao = ?, data = ?, base_cents = ?, iva_taxa = ?, iva_cents = ?, total_cents = ? WHERE id = ?')
                   ->execute([$tipo, $categoria, $descricao, $data, $base, $taxa, $iva, $total, $id]);
                $depois = lancamentoPorId($id);
                registarAuditoria('lancamento', 'editar', resumoLancamentoTexto($antes) . ' → ' . resumoLancamentoTexto($depois), limpar($_POST['motivo'] ?? '', 300));
                aviso('ok', 'dados_guardados');
            } else {
                aviso('erro', 'dados_invalidos');
            }
        }
    } elseif ($acao === 'apagar') {
        // apagar um registo errado: a justificação é obrigatória e o que foi apagado fica no registo de alterações
        $antes = lancamentoPorId($id);
        $motivo = motivoValido($_POST['motivo'] ?? '');
        if (!$antes) {
            aviso('erro', 'dados_invalidos');
        } elseif ($motivo === null) {
            aviso('erro', 'motivo_curto');
            redirecionar(ligacao('lancamentos', $filtros + ['apagar' => $id]));
        } else {
            $db->prepare('DELETE FROM lancamentos WHERE id = ?')->execute([$id]);
            registarAuditoria('lancamento', 'apagar', resumoLancamentoTexto($antes), $motivo);
            aviso('ok', 'lancamento_apagado');
        }
    }
    redirecionar(ligacao('lancamentos', $filtros));
}

$sql = 'SELECT * FROM lancamentos WHERE 1 = 1';
$args = [];
if ($filtroTipo !== '') { $sql .= ' AND tipo = ?'; $args[] = $filtroTipo; }
if ($mesFiltro !== null) { $sql .= ' AND substr(data, 1, 7) = ?'; $args[] = $mesFiltro; }
if ($procura !== '') { $sql .= ' AND descricao LIKE ?'; $args[] = '%' . $procura . '%'; }
$sql .= ' ORDER BY data DESC, id DESC';
$st = $db->prepare($sql);
$st->execute($args);
$todos = $st->fetchAll();
$somas = ['receita' => [0, 0], 'despesa' => [0, 0]];
foreach ($todos as $l) {
    $somas[$l['tipo']][0] += (int) $l['base_cents'];
    $somas[$l['tipo']][1] += (int) $l['iva_cents'];
}
$lista = array_slice($todos, 0, 200);
$editar = null;
if ($editarId) {
    $st = $db->prepare('SELECT * FROM lancamentos WHERE id = ?');
    $st->execute([$editarId]);
    $editar = $st->fetch() ?: null;
}
$apagar = $apagarId ? lancamentoPorId($apagarId) : null;
$f = $editar ?? ['tipo' => 'despesa', 'categoria' => '', 'descricao' => '', 'data' => hojeISO(), 'base_cents' => null, 'iva_taxa' => 23];
echo separadoresFinancas('lancamentos');
?>
<section class="kpis kpis-3">
    <div class="kpi"><span class="kpi-valor"><?= e(euro($somas['receita'][0])) ?></span><span class="kpi-rotulo"><?= e(t('total_receitas')) ?> · <?= e(t('sem_iva')) ?></span></div>
    <div class="kpi"><span class="kpi-valor"><?= e(euro($somas['despesa'][0])) ?></span><span class="kpi-rotulo"><?= e(t('total_despesas')) ?> · <?= e(t('sem_iva')) ?></span></div>
    <div class="kpi"><span class="kpi-valor"><?= e(euro($somas['receita'][1] - $somas['despesa'][1], true)) ?></span><span class="kpi-rotulo"><?= e(t('iva_a_entregar')) ?></span></div>
</section>

<div class="duas-colunas largo-esq">
<section class="cartao">
    <form method="get" class="filtros sem-cartao" data-auto>
        <input type="hidden" name="p" value="lancamentos">
        <label><span><?= e(t('tipo')) ?></span><select name="tipo"><option value=""><?= e(t('todos')) ?></option>
            <?php foreach (array_keys(CATEGORIAS) as $tp): ?><option value="<?= e($tp) ?>"<?= $tp === $filtroTipo ? ' selected' : '' ?>><?= e(t('tipo_' . $tp . 's')) ?></option><?php endforeach; ?></select></label>
        <label><span><?= e(t('mes')) ?></span><select name="mes"><option value=""><?= e(t('todos')) ?></option>
            <?php foreach (MESES_LETIVOS as $m): ?><option value="<?= e($m) ?>"<?= $m === $mesFiltro ? ' selected' : '' ?>><?= e(nomeMes($m)) ?></option><?php endforeach; ?></select></label>
        <label><span><?= e(t('procurar')) ?></span><input type="search" name="q" value="<?= e($procura) ?>" maxlength="40"></label>
        <button type="submit" class="botao secundario"><?= e(t('filtrar')) ?></button>
        <a class="botao secundario" href="<?= e(ligacao('financeiro', ['excel' => 'lancamentos'])) ?>"><?= icone('excel') ?><span><?= e(t('baixar_excel')) ?></span></a>
    </form>
    <p class="suave"><?= e(t('n_resultados', count($todos))) ?></p>
    <?php if (!$lista): ?><p class="suave"><?= e(t('sem_lancamentos')) ?></p><?php else: ?>
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th><?= e(t('data')) ?></th><th><?= e(t('categoria')) ?></th><th><?= e(t('descricao')) ?></th><th class="num"><?= e(t('valor_sem_iva')) ?></th><th class="num"><?= e(t('iva')) ?></th><th class="num"><?= e(t('total')) ?></th><th></th></tr></thead>
        <tbody>
        <?php foreach ($lista as $l): ?>
            <tr><td><?= e(dataFmt($l['data'])) ?></td>
                <td><span class="etiqueta <?= $l['tipo'] === 'receita' ? 'pos' : 'neg' ?>"><?= e(nomeCategoria($l['categoria'])) ?></span></td>
                <td><?= e($l['descricao']) ?></td>
                <td class="num"><?= e(euro((int) $l['base_cents'])) ?></td>
                <td class="num"><?= e(euro((int) $l['iva_cents'])) ?> <small>(<?= (int) $l['iva_taxa'] ?>%)</small></td>
                <td class="num"><strong><?= e(euro((int) $l['total_cents'])) ?></strong></td>
                <td class="acoes"><?php if ($edita): ?><a class="botao secundario pequeno" href="<?= e(ligacao('lancamentos', $filtros + ['editar' => $l['id']])) ?>"><?= e(t('editar')) ?></a>
                    <a class="botao secundario pequeno botao-perigo-linha" href="<?= e(ligacao('lancamentos', $filtros + ['apagar' => $l['id']])) ?>"><?= icone('lixo') ?><span><?= e(t('apagar')) ?></span></a><?php endif; ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</section>

<section class="cartao">
<?php if (!$edita): ?>
    <h2><?= e(t('so_leitura')) ?></h2>
    <p class="suave"><?= e(t('financas_so_leitura')) ?></p>
<?php elseif ($apagar): ?>
    <h2><?= e(t('apagar_registo')) ?></h2>
    <p class="aviso-perigo"><?= e(t('aviso_apagar_registo')) ?></p>
    <p><strong><?= e($apagar['descricao']) ?></strong><br><span class="suave"><?= e(nomeCategoria($apagar['categoria'])) ?> · <?= e(dataFmt($apagar['data'])) ?> · <?= e(euro((int) $apagar['total_cents'])) ?></span></p>
    <form method="post" class="formulario">
        <?= csrfCampo() ?><input type="hidden" name="acao" value="apagar"><input type="hidden" name="id" value="<?= (int) $apagar['id'] ?>">
        <label><span><?= e(t('justificacao')) ?></span><textarea name="motivo" rows="3" maxlength="300" minlength="8" required placeholder="<?= e(t('justificacao_exemplo')) ?>"></textarea></label>
        <p class="barra-acoes"><button type="submit" class="botao botao-perigo"><?= icone('lixo') ?><span><?= e(t('apagar_definitivamente')) ?></span></button> <a class="botao secundario" href="<?= e(ligacao('lancamentos', $filtros)) ?>"><?= e(t('cancelar')) ?></a></p>
    </form>
<?php else: ?>
    <h2><?= e($editar ? t('editar_lancamento') : t('novo_lancamento')) ?></h2>
    <form method="post" class="formulario" data-iva>
        <?= csrfCampo() ?><input type="hidden" name="acao" value="<?= $editar ? 'editar' : 'criar' ?>">
        <?php if ($editar): ?><input type="hidden" name="id" value="<?= (int) $editar['id'] ?>"><?php endif; ?>
        <label><span><?= e(t('tipo')) ?></span><select name="tipo">
            <?php foreach (array_keys(CATEGORIAS) as $tp): ?><option value="<?= e($tp) ?>"<?= $tp === $f['tipo'] ? ' selected' : '' ?>><?= e(t('tipo_' . $tp)) ?></option><?php endforeach; ?></select></label>
        <label><span><?= e(t('categoria')) ?></span><select name="categoria">
            <?php foreach (CATEGORIAS as $tp => $cats): ?><optgroup label="<?= e(t('tipo_' . $tp . 's')) ?>">
                <?php foreach ($cats as $c): ?><option value="<?= e($c) ?>"<?= $c === $f['categoria'] ? ' selected' : '' ?>><?= e(nomeCategoria($c)) ?></option><?php endforeach; ?></optgroup><?php endforeach; ?></select></label>
        <label><span><?= e(t('descricao')) ?></span><input type="text" name="descricao" value="<?= e($f['descricao']) ?>" maxlength="120" required></label>
        <div class="linha-campos">
            <label><span><?= e(t('data')) ?></span><input type="date" name="data" value="<?= e($f['data']) ?>" required></label>
            <label><span><span data-iva-rotulo data-sem="<?= e(t('valor_sem_iva')) ?>" data-com="<?= e(t('valor_com_iva')) ?>"><?= e(t('valor_sem_iva')) ?></span> (€)</span><input type="text" inputmode="decimal" name="base" value="<?= e($f['base_cents'] !== null ? numCents((int) $f['base_cents']) : '') ?>" maxlength="12" required data-iva-base></label>
        </div>
        <label><span><?= e(t('valor_escrito')) ?></span><select name="modo" data-iva-modo>
            <option value="sem"><?= e(t('modo_sem_iva')) ?></option><option value="com"><?= e(t('modo_com_iva')) ?></option></select></label>
        <label><span><?= e(t('taxa_iva')) ?></span><select name="taxa" data-iva-taxa>
            <?php foreach (TAXAS_IVA as $tx): ?><option value="<?= $tx ?>"<?= $tx === (int) $f['iva_taxa'] ? ' selected' : '' ?>><?= $tx === 0 ? e(t('isento_sem_iva')) : $tx . '%' ?></option><?php endforeach; ?></select></label>
        <?php if ($editar): ?><label><span><?= e(t('motivo_alteracao')) ?> <small>(<?= e(t('opcional')) ?>)</small></span><input type="text" name="motivo" maxlength="300"></label><?php endif; ?>
        <p class="nota-info" data-iva-resumo data-texto-iva="<?= e(t('iva')) ?>" data-texto-total="<?= e(t('total')) ?>" data-texto-base="<?= e(t('valor_sem_iva')) ?>"><?= e(t('iva_previsao_vazia')) ?></p>
        <p class="barra-acoes"><button type="submit" class="botao"><?= e(t('guardar')) ?></button><?php if ($editar): ?> <a class="botao secundario" href="<?= e(ligacao('lancamentos', $filtros)) ?>"><?= e(t('cancelar')) ?></a><?php endif; ?></p>
        <p class="suave pequeno"><?= e(t('ajuda_iva')) ?></p>
    </form>
<?php endif; ?>
</section>
</div>
