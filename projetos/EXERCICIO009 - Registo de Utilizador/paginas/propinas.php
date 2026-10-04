<?php
/**
 * Propinas. Só a contabilidade emite, edita e regista pagamentos. A secretaria e a direção consultam (e descarregam
 * recibos); o aluno (ou encarregado) só vê as suas e descarrega o recibo das que já pagou.
 */
if (!defined('APP')) { http_response_code(403); exit; }

$u = utilizador();
$gere = !temPerfil('aluno');      // vê as propinas de todos
$edita = ehContabilista();         // altera dados financeiros
$db = bd();

/** Propina com o nome e a turma do aluno (null se não existir). */
function propinaCompleta(int $id): ?array
{
    $st = bd()->prepare(
        'SELECT p.*, u.nome, t.nome AS turma, a.numero FROM propinas p
           JOIN utilizadores u ON u.id = p.aluno_id JOIN alunos a ON a.utilizador_id = u.id JOIN turmas t ON t.id = a.turma_id WHERE p.id = ?'
    );
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

// ---------- recibo em PDF ----------
if (isset($_GET['pdf'])) {
    $p = propinaCompleta(inteiro($_GET['pdf']));
    // o aluno só abre os seus próprios recibos, e só de propinas pagas
    if (!$p || empty($p['pago_em']) || (!$gere && (int) $p['aluno_id'] !== (int) $u['id'])) {
        aviso('erro', 'recibo_nao_encontrado');
        redirecionar(ligacao('propinas'));
    }
    enviarPdf(pdfReciboPropina($p), 'recibo_propina_' . $p['nome'] . '_' . $p['mes']);
}

// ---------- ações (só a contabilidade) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
    if (!$edita) {
        http_response_code(403);
        exit(t('erro_403_texto'));
    }
    $acao = $_POST['acao'] ?? '';
    $id = inteiro($_POST['id'] ?? '');
    $p = $id ? propinaCompleta($id) : null;

    if ($acao === 'emitir') {
        $mes = mesValido($_POST['mes'] ?? null);
        if ($mes === null) {
            aviso('erro', 'dados_invalidos');
        } else {
            $n = emitirPropinas($db, $mes);
            aviso($n > 0 ? 'ok' : 'erro', $n > 0 ? 'propinas_emitidas' : 'propinas_ja_emitidas', $n);
        }
    } elseif ($acao === 'pagar') {
        $data = dataValida($_POST['data'] ?? null);
        $metodo = is_string($_POST['metodo'] ?? null) && in_array($_POST['metodo'], metodosPagamento(), true) ? $_POST['metodo'] : null;
        if (!$p || $data === null || $metodo === null) {
            aviso('erro', 'dados_invalidos');
        } elseif (!empty($p['pago_em'])) {
            aviso('erro', 'propina_ja_paga');
        } else {
            $db->prepare('UPDATE propinas SET pago_em = ?, metodo = ? WHERE id = ?')->execute([$data, $metodo, $id]);
            aviso('ok', 'pagamento_registado');
        }
    } elseif ($acao === 'anular') {
        if ($p && !empty($p['pago_em'])) {
            $db->prepare('UPDATE propinas SET pago_em = NULL, metodo = NULL WHERE id = ?')->execute([$id]);
            aviso('ok', 'pagamento_anulado');
        } else {
            aviso('erro', 'dados_invalidos');
        }
    } elseif ($acao === 'editar') {
        $valor = lerDinheiro($_POST['valor'] ?? null);
        $venc = dataValida($_POST['vencimento'] ?? null, true);
        if (!$p || $valor === false || $valor <= 0 || $venc === null) {
            aviso('erro', 'dados_invalidos');
        } elseif (!empty($p['pago_em'])) {
            aviso('erro', 'propina_paga_nao_edita');
        } else {
            $db->prepare('UPDATE propinas SET valor_cents = ?, vencimento = ? WHERE id = ?')->execute([$valor, $venc, $id]);
            aviso('ok', 'dados_guardados');
        }
    } elseif ($acao === 'apagar') {
        if ($p && empty($p['pago_em'])) {
            $db->prepare('DELETE FROM propinas WHERE id = ?')->execute([$id]);
            aviso('ok', 'propina_apagada');
        } else {
            aviso('erro', 'propina_paga_nao_edita');
        }
    }
    $volta = array_filter([
        'turma' => inteiro($_POST['f_turma'] ?? '') ?: null,
        'mes' => is_string($_POST['f_mes'] ?? null) ? $_POST['f_mes'] : null,
        'estado' => is_string($_POST['f_estado'] ?? null) ? $_POST['f_estado'] : null,
    ], static fn ($v) => $v !== null);
    redirecionar(ligacao('propinas', $volta));
}

// ---------- vista do aluno ----------
if (!$gere) {
    $st = $db->prepare('SELECT * FROM propinas WHERE aluno_id = ? ORDER BY mes');
    $st->execute([(int) $u['id']]);
    $minhas = $st->fetchAll();
    $pago = $porPagar = $atraso = 0;
    foreach ($minhas as $p) {
        $estado = estadoPropina($p);
        $pago += $estado === 'paga' ? (int) $p['valor_cents'] : 0;
        $porPagar += $estado !== 'paga' ? (int) $p['valor_cents'] : 0;
        $atraso += $estado === 'atraso' ? (int) $p['valor_cents'] : 0;
    }
    ?>
<section class="kpis kpis-3">
    <div class="kpi"><span class="kpi-valor"><?= e(euro($pago)) ?></span><span class="kpi-rotulo"><?= e(t('total_pago')) ?></span></div>
    <div class="kpi"><span class="kpi-valor"><?= e(euro($porPagar)) ?></span><span class="kpi-rotulo"><?= e(t('total_por_pagar')) ?></span></div>
    <div class="kpi <?= $atraso > 0 ? 'kpi-aviso' : '' ?>"><span class="kpi-valor"><?= e(euro($atraso)) ?></span><span class="kpi-rotulo"><?= e(t('em_atraso')) ?></span></div>
</section>
<section class="cartao">
    <h2><?= e(t('as_minhas_propinas')) ?></h2>
    <?php if (!$minhas): ?><p class="suave"><?= e(t('sem_propinas')) ?></p><?php else: ?>
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th><?= e(t('mes')) ?></th><th class="num"><?= e(t('valor')) ?></th><th><?= e(t('vencimento')) ?></th><th><?= e(t('estado')) ?></th><th><?= e(t('pago_em')) ?></th><th></th></tr></thead>
        <tbody>
        <?php foreach ($minhas as $p): $estado = estadoPropina($p); ?>
            <tr><td><?= e(nomeMes($p['mes'])) ?></td><td class="num"><?= e(euro((int) $p['valor_cents'])) ?></td><td><?= e(dataFmt($p['vencimento'])) ?></td>
                <td><span class="etiqueta <?= $estado === 'paga' ? 'pos' : ($estado === 'atraso' ? 'neg' : '') ?>"><?= e(t('estado_' . $estado)) ?></span></td>
                <td><?= e($p['pago_em'] ? dataFmt($p['pago_em']) : '—') ?><?= $p['metodo'] ? ' · <span class="suave pequeno">' . e(t('metodo_' . $p['metodo'])) . '</span>' : '' ?></td>
                <td class="acoes"><?php if ($estado === 'paga'): ?><a class="botao secundario pequeno" href="<?= e(ligacao('propinas', ['pdf' => $p['id']])) ?>"><?= icone('baixar') ?><span><?= e(t('recibo')) ?></span></a><?php endif; ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
    <p class="suave pequeno"><?= e(t('ajuda_propinas_aluno')) ?></p>
</section>
<?php
    return;
}

// ---------- vista de quem vê todas as propinas ----------
$turmasLista = turmasTodas();
$filtroTurma = inteiro($_GET['turma'] ?? '');
$mesFiltro = array_key_exists('mes', $_GET) ? mesValido($_GET['mes']) : mesValido(substr(hojeISO(), 0, 7));
$estadoFiltro = is_string($_GET['estado'] ?? null) && in_array($_GET['estado'], ['paga', 'atraso', 'pendente'], true) ? $_GET['estado'] : '';
$procura = limpar($_GET['q'] ?? '', 40);
$pagarId = $edita ? inteiro($_GET['pagar'] ?? '') : 0;
$editarId = $edita ? inteiro($_GET['editar'] ?? '') : 0;

$sql = 'SELECT p.*, u.nome, t.nome AS turma, a.numero FROM propinas p
          JOIN utilizadores u ON u.id = p.aluno_id JOIN alunos a ON a.utilizador_id = u.id JOIN turmas t ON t.id = a.turma_id WHERE 1 = 1';
$args = [];
if ($filtroTurma) { $sql .= ' AND t.id = ?'; $args[] = $filtroTurma; }
if ($mesFiltro !== null) { $sql .= ' AND p.mes = ?'; $args[] = $mesFiltro; }
if ($procura !== '') { $sql .= ' AND u.nome LIKE ?'; $args[] = '%' . $procura . '%'; }
$sql .= ' ORDER BY p.mes, t.ano, t.nome, a.numero';
$st = $db->prepare($sql);
$st->execute($args);
$lista = array_values(array_filter($st->fetchAll(), static fn ($p) => $estadoFiltro === '' || estadoPropina($p) === $estadoFiltro));
$total = count($lista);
$somaLista = array_sum(array_map(static fn ($p) => (int) $p['valor_cents'], $lista));
$somaPaga = array_sum(array_map(static fn ($p) => $p['pago_em'] ? (int) $p['valor_cents'] : 0, $lista));
$lista = array_slice($lista, 0, 150);

$res = resumoFinanceiro();
$aPagar = $pagarId ? propinaCompleta($pagarId) : null;
$aEditar = $editarId ? propinaCompleta($editarId) : null;
$filtros = array_filter(['turma' => $filtroTurma ?: null, 'mes' => $mesFiltro ?? '', 'estado' => $estadoFiltro ?: null, 'q' => $procura ?: null], static fn ($v) => $v !== null);
?>
<section class="kpis">
    <div class="kpi"><span class="kpi-valor"><?= e(euro($res['total']['propinas'])) ?></span><span class="kpi-rotulo"><?= e(t('propinas_recebidas')) ?></span></div>
    <div class="kpi"><span class="kpi-valor"><?= e(euro($res['por_pagar'])) ?></span><span class="kpi-rotulo"><?= e(t('total_por_receber')) ?></span></div>
    <div class="kpi <?= $res['atraso_n'] ? 'kpi-aviso' : '' ?>"><span class="kpi-valor"><?= (int) $res['atraso_n'] ?></span><span class="kpi-rotulo"><?= e(t('propinas_em_atraso')) ?></span></div>
    <div class="kpi <?= $res['atraso_n'] ? 'kpi-aviso' : '' ?>"><span class="kpi-valor"><?= e(euro($res['atraso_valor'])) ?></span><span class="kpi-rotulo"><?= e(t('valor_em_atraso')) ?></span></div>
</section>

<div class="duas-colunas largo-esq">
<section class="cartao">
    <form method="get" class="filtros sem-cartao" data-auto>
        <input type="hidden" name="p" value="propinas">
        <label><span><?= e(t('mes')) ?></span><select name="mes"><option value=""><?= e(t('todos')) ?></option>
            <?php foreach (MESES_LETIVOS as $m): ?><option value="<?= e($m) ?>"<?= $m === $mesFiltro ? ' selected' : '' ?>><?= e(nomeMes($m)) ?></option><?php endforeach; ?></select></label>
        <label><span><?= e(t('turma')) ?></span><select name="turma"><option value="0"><?= e(t('todas')) ?></option>
            <?php foreach ($turmasLista as $t): ?><option value="<?= (int) $t['id'] ?>"<?= (int) $t['id'] === $filtroTurma ? ' selected' : '' ?>><?= e($t['nome']) ?></option><?php endforeach; ?></select></label>
        <label><span><?= e(t('estado')) ?></span><select name="estado"><option value=""><?= e(t('todos')) ?></option>
            <?php foreach (['paga', 'atraso', 'pendente'] as $e1): ?><option value="<?= e($e1) ?>"<?= $e1 === $estadoFiltro ? ' selected' : '' ?>><?= e(t('estado_' . $e1)) ?></option><?php endforeach; ?></select></label>
        <label><span><?= e(t('procurar')) ?></span><input type="search" name="q" value="<?= e($procura) ?>" maxlength="40"></label>
        <button type="submit" class="botao secundario"><?= e(t('filtrar')) ?></button>
    </form>
    <p class="suave"><?= e(t('n_resultados', $total)) ?> · <?= e(t('total_emitido')) ?>: <strong><?= e(euro($somaLista)) ?></strong> · <?= e(t('total_recebido')) ?>: <strong><?= e(euro($somaPaga)) ?></strong></p>
    <?php if (!$lista): ?><p class="suave"><?= e(t('sem_propinas')) ?></p><?php else: ?>
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th><?= e(t('aluno')) ?></th><th><?= e(t('turma')) ?></th><th><?= e(t('mes')) ?></th><th class="num"><?= e(t('valor')) ?></th><th><?= e(t('vencimento')) ?></th><th><?= e(t('estado')) ?></th><th></th></tr></thead>
        <tbody>
        <?php foreach ($lista as $p): $estado = estadoPropina($p); ?>
            <tr><td><?= e($p['nome']) ?></td><td><?= e($p['turma']) ?></td><td><?= e(nomeMes($p['mes'], true)) ?></td>
                <td class="num"><?= e(euro((int) $p['valor_cents'])) ?></td><td><?= e(dataFmt($p['vencimento'])) ?></td>
                <td><span class="etiqueta <?= $estado === 'paga' ? 'pos' : ($estado === 'atraso' ? 'neg' : '') ?>"><?= e(t('estado_' . $estado)) ?></span>
                    <?php if ($p['pago_em']): ?><small class="motivo"><?= e(dataFmt($p['pago_em'])) ?></small><?php endif; ?></td>
                <td class="acoes">
                <?php if ($estado === 'paga'): ?>
                    <a class="botao secundario pequeno" href="<?= e(ligacao('propinas', ['pdf' => $p['id']])) ?>"><?= icone('baixar') ?><span><?= e(t('recibo')) ?></span></a>
                    <?php if ($edita): ?><form method="post" class="form-linha"><?= csrfCampo() ?><input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                        <input type="hidden" name="f_turma" value="<?= (int) $filtroTurma ?>"><input type="hidden" name="f_mes" value="<?= e($mesFiltro ?? '') ?>"><input type="hidden" name="f_estado" value="<?= e($estadoFiltro) ?>">
                        <button type="submit" name="acao" value="anular" class="botao secundario pequeno" data-confirmar-botao="<?= e(t('confirmar_anular_pagamento')) ?>"><?= e(t('anular_pagamento')) ?></button></form><?php endif; ?>
                <?php elseif ($edita): ?>
                    <a class="botao pequeno" href="<?= e(ligacao('propinas', $filtros + ['pagar' => $p['id']])) ?>"><?= e(t('registar_pagamento')) ?></a>
                    <a class="botao secundario pequeno" href="<?= e(ligacao('propinas', $filtros + ['editar' => $p['id']])) ?>"><?= e(t('editar')) ?></a>
                    <form method="post" class="form-linha"><?= csrfCampo() ?><input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                        <input type="hidden" name="f_turma" value="<?= (int) $filtroTurma ?>"><input type="hidden" name="f_mes" value="<?= e($mesFiltro ?? '') ?>"><input type="hidden" name="f_estado" value="<?= e($estadoFiltro) ?>">
                        <button type="submit" name="acao" value="apagar" class="icone-botao perigo pequeno" title="<?= e(t('apagar')) ?>" aria-label="<?= e(t('apagar')) ?>" data-confirmar-botao="<?= e(t('confirmar_apagar_propina')) ?>"><?= icone('lixo') ?></button></form>
                <?php endif; ?>
                </td></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php if ($total > 150): ?><p class="suave pequeno"><?= e(t('mostradas_150')) ?></p><?php endif; ?>
    <?php endif; ?>
</section>

<section class="cartao">
<?php if (!$edita): ?>
    <h2><?= e(t('so_leitura')) ?></h2>
    <p class="suave"><?= e(t('propinas_so_leitura')) ?></p>
<?php elseif ($aPagar && empty($aPagar['pago_em'])): ?>
    <h2><?= e(t('registar_pagamento')) ?></h2>
    <p><strong><?= e($aPagar['nome']) ?></strong><br><span class="suave"><?= e($aPagar['turma']) ?> · <?= e(nomeMes($aPagar['mes'])) ?> · <?= e(euro((int) $aPagar['valor_cents'])) ?></span></p>
    <form method="post" class="formulario">
        <?= csrfCampo() ?><input type="hidden" name="acao" value="pagar"><input type="hidden" name="id" value="<?= (int) $aPagar['id'] ?>">
        <input type="hidden" name="f_turma" value="<?= (int) $filtroTurma ?>"><input type="hidden" name="f_mes" value="<?= e($mesFiltro ?? '') ?>"><input type="hidden" name="f_estado" value="<?= e($estadoFiltro) ?>">
        <label><span><?= e(t('pago_em')) ?></span><input type="date" name="data" value="<?= e(hojeISO()) ?>" max="<?= e(hojeISO()) ?>" required></label>
        <label><span><?= e(t('metodo')) ?></span><select name="metodo"><?php foreach (metodosPagamento() as $mp): ?><option value="<?= e($mp) ?>"><?= e(t('metodo_' . $mp)) ?></option><?php endforeach; ?></select></label>
        <p class="barra-acoes"><button type="submit" class="botao verde"><?= e(t('registar_pagamento')) ?></button> <a class="botao secundario" href="<?= e(ligacao('propinas', $filtros)) ?>"><?= e(t('cancelar')) ?></a></p>
    </form>
<?php elseif ($aEditar && empty($aEditar['pago_em'])): ?>
    <h2><?= e(t('editar_propina')) ?></h2>
    <p><strong><?= e($aEditar['nome']) ?></strong><br><span class="suave"><?= e($aEditar['turma']) ?> · <?= e(nomeMes($aEditar['mes'])) ?></span></p>
    <form method="post" class="formulario">
        <?= csrfCampo() ?><input type="hidden" name="acao" value="editar"><input type="hidden" name="id" value="<?= (int) $aEditar['id'] ?>">
        <input type="hidden" name="f_turma" value="<?= (int) $filtroTurma ?>"><input type="hidden" name="f_mes" value="<?= e($mesFiltro ?? '') ?>"><input type="hidden" name="f_estado" value="<?= e($estadoFiltro) ?>">
        <label><span><?= e(t('valor')) ?> (€)</span><input type="text" inputmode="decimal" name="valor" value="<?= e(numCents((int) $aEditar['valor_cents'])) ?>" maxlength="12" required></label>
        <label><span><?= e(t('vencimento')) ?></span><input type="date" name="vencimento" value="<?= e($aEditar['vencimento']) ?>" required></label>
        <p class="barra-acoes"><button type="submit" class="botao"><?= e(t('guardar')) ?></button> <a class="botao secundario" href="<?= e(ligacao('propinas', $filtros)) ?>"><?= e(t('cancelar')) ?></a></p>
    </form>
<?php else: ?>
    <h2><?= e(t('emitir_propinas')) ?></h2>
    <form method="post" class="formulario">
        <?= csrfCampo() ?><input type="hidden" name="acao" value="emitir">
        <label><span><?= e(t('mes')) ?></span><select name="mes"><?php foreach (MESES_LETIVOS as $m): ?><option value="<?= e($m) ?>"><?= e(nomeMes($m)) ?></option><?php endforeach; ?></select></label>
        <button type="submit" class="botao"><?= icone('mais') ?><span><?= e(t('emitir_propinas')) ?></span></button>
        <p class="suave"><?= e(t('ajuda_emitir', euro(propinaDoAno(7)), euro(propinaDoAno(8)), euro(propinaDoAno(9)), (int) parametro('dia_vencimento'))) ?></p>
    </form>
    <?php if (temPerfil(...PAGINAS['financeiro'])): ?><p><a class="botao secundario" href="<?= e(ligacao('financeiro', ['excel' => 'propinas'])) ?>"><?= icone('excel') ?><span><?= e(t('baixar_excel')) ?></span></a></p><?php endif; ?>
<?php endif; ?>
</section>
</div>
