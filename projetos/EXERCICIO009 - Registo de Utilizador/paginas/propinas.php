<?php
/**
 * Propinas. Só a contabilidade emite, edita, regista pagamentos e apaga (sempre com justificação, que fica no registo de alterações). A secretaria e a direção consultam (e descarregam
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
        'SELECT p.*, u.nome, t.nome AS turma, a.numero, a.nif, a.encarregado FROM propinas p
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
    } elseif ($acao === 'nova') {
        // adicionar a propina de um aluno (por exemplo, um aluno que entrou a meio do ano), com pagamento opcional
        $alunoId = inteiro($_POST['aluno_id'] ?? '');
        $mesNova = mesValido($_POST['mes'] ?? null);
        $valor = lerDinheiro($_POST['valor'] ?? null);
        $venc = dataValida($_POST['vencimento'] ?? null, true);
        $dataPag = ($_POST['data_pagamento'] ?? '') !== '' ? dataValida($_POST['data_pagamento'] ?? null) : '';
        $metodo = is_string($_POST['metodo'] ?? null) && in_array($_POST['metodo'], metodosPagamento(), true) ? $_POST['metodo'] : null;
        $aluno = $db->prepare('SELECT 1 FROM alunos a JOIN utilizadores u ON u.id = a.utilizador_id WHERE u.id = ? AND u.ativo = 1');
        $aluno->execute([$alunoId]);
        $existe = $db->prepare('SELECT 1 FROM propinas WHERE aluno_id = ? AND mes = ?');
        $existe->execute([$alunoId, $mesNova]);
        if (!$aluno->fetchColumn() || $mesNova === null || $valor === false || $valor <= 0 || $venc === null || $dataPag === null || ($dataPag !== '' && $metodo === null)) {
            aviso('erro', 'dados_invalidos');
        } elseif ($existe->fetchColumn()) {
            aviso('erro', 'propina_existe');
        } else {
            $db->prepare('INSERT INTO propinas (aluno_id, mes, valor_cents, vencimento, pago_em, metodo) VALUES (?, ?, ?, ?, ?, ?)')
               ->execute([$alunoId, $mesNova, $valor, $venc, $dataPag !== '' ? $dataPag : null, $dataPag !== '' ? $metodo : null]);
            aviso('ok', 'propina_adicionada');
        }
        $irPara = ['mes' => $mesNova ?? ''];
    } elseif ($acao === 'massa') {
        // atualizar de uma vez as propinas ainda por pagar (nunca as já pagas): novo valor e/ou novo dia de vencimento
        $mesDe = mesValido($_POST['mes'] ?? null);
        $seguintes = !empty($_POST['seguintes']);
        $ano = inteiro($_POST['ano'] ?? '');
        $valor = ($_POST['valor'] ?? '') !== '' ? lerDinheiro($_POST['valor'] ?? null) : null;
        $dia = ($_POST['dia'] ?? '') !== '' ? inteiro($_POST['dia'] ?? '') : null;
        $motivo = motivoValido($_POST['motivo'] ?? '');
        $guardarPadrao = !empty($_POST['guardar_padrao']) && in_array($ano, [7, 8, 9], true) && $valor !== null && $valor !== false;
        if ($mesDe === null || !in_array($ano, [0, 7, 8, 9], true) || $valor === false || ($valor !== null && $valor <= 0) || ($dia !== null && ($dia < 1 || $dia > 28)) || ($valor === null && $dia === null)) {
            aviso('erro', 'dados_invalidos');
        } elseif ($motivo === null) {
            aviso('erro', 'motivo_curto');
        } else {
            $sql = 'SELECT p.id, p.mes, p.valor_cents FROM propinas p JOIN alunos a ON a.utilizador_id = p.aluno_id JOIN turmas t ON t.id = a.turma_id
                     WHERE p.pago_em IS NULL AND ' . ($seguintes ? 'p.mes >= ?' : 'p.mes = ?') . ($ano > 0 ? ' AND t.ano = ?' : '');
            $st = $db->prepare($sql);
            $st->execute($ano > 0 ? [$mesDe, $ano] : [$mesDe]);
            $alvo = $st->fetchAll();
            if (!$alvo) {
                aviso('erro', 'massa_nada');
            } else {
                $db->beginTransaction();
                $upd = $db->prepare('UPDATE propinas SET valor_cents = COALESCE(?, valor_cents), vencimento = CASE WHEN ? IS NULL THEN vencimento ELSE substr(mes, 1, 7) || \'-\' || printf(\'%02d\', ?) END WHERE id = ?');
                foreach ($alvo as $l) {
                    $upd->execute([$valor, $dia, $dia, (int) $l['id']]);
                }
                if ($guardarPadrao) {
                    $db->prepare('INSERT INTO parametros (chave, valor) VALUES (?, ?) ON CONFLICT (chave) DO UPDATE SET valor = excluded.valor')
                       ->execute(['propina_' . $ano, numCents($valor)]);
                }
                $db->commit();
                $descricao = count($alvo) . ' · ' . $mesDe . ($seguintes ? '+' : '') . ' · ' . ($ano > 0 ? $ano . '.º' : '*')
                    . ($valor !== null ? ' · → ' . euro($valor) : '') . ($dia !== null ? ' · ' . $dia : '') . ($guardarPadrao ? ' · ' . t('valor_padrao') : '');
                registarAuditoria('propina', 'editar', $descricao, $motivo);
                aviso('ok', 'propinas_atualizadas', count($alvo));
            }
        }
        $irPara = ['mes' => $mesDe ?? ''];
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
    } elseif (in_array($acao, ['anular', 'editar', 'apagar'], true)) {
        // corrigir ou apagar dados já registados: o motivo é obrigatório quando a propina já foi paga, e sempre ao apagar ou anular
        $motivo = motivoValido($_POST['motivo'] ?? '');
        $paga = $p && !empty($p['pago_em']);
        $pedeMotivo = $acao !== 'editar' || $paga;
        $voltaPainel = ['mes' => is_string($_POST['f_mes'] ?? null) ? $_POST['f_mes'] : null, 'turma' => inteiro($_POST['f_turma'] ?? '') ?: null, 'estado' => is_string($_POST['f_estado'] ?? null) ? $_POST['f_estado'] : null];
        $voltaPainel = array_filter($voltaPainel, static fn ($v) => $v !== null) + [($acao === 'editar' ? 'editar' : $acao) => $id];
        if (!$p) {
            aviso('erro', 'dados_invalidos');
        } elseif ($acao === 'anular' && !$paga) {
            aviso('erro', 'dados_invalidos');
        } elseif ($pedeMotivo && $motivo === null) {
            aviso('erro', 'motivo_curto');
            redirecionar(ligacao('propinas', $voltaPainel));
        } elseif ($acao === 'anular') {
            $db->prepare('UPDATE propinas SET pago_em = NULL, metodo = NULL WHERE id = ?')->execute([$id]);
            registarAuditoria('propina', 'anular', resumoPropinaTexto($p), (string) $motivo);
            aviso('ok', 'pagamento_anulado');
        } elseif ($acao === 'apagar') {
            $db->prepare('DELETE FROM propinas WHERE id = ?')->execute([$id]);
            registarAuditoria('propina', 'apagar', resumoPropinaTexto($p), (string) $motivo);
            aviso('ok', 'propina_apagada');
        } else {
            $valor = lerDinheiro($_POST['valor'] ?? null);
            $venc = dataValida($_POST['vencimento'] ?? null, true);
            $dataPag = $paga ? dataValida($_POST['data_pagamento'] ?? null) : null;
            $metodo = $paga && is_string($_POST['metodo'] ?? null) && in_array($_POST['metodo'], metodosPagamento(), true) ? $_POST['metodo'] : null;
            $encarregado = limpar($_POST['encarregado'] ?? '', 80);   // quem paga: nome e NIF aparecem no recibo
            $nifPaga = lerNif($_POST['nif'] ?? '');
            if ($nifPaga === null) {
                aviso('erro', 'nif_invalido');
                redirecionar(ligacao('propinas', $voltaPainel));
            } elseif ($valor === false || $valor <= 0 || $venc === null || ($paga && ($dataPag === null || $metodo === null))) {
                aviso('erro', 'dados_invalidos');
            } else {
                $db->prepare('UPDATE alunos SET encarregado = ?, nif = ? WHERE utilizador_id = ?')->execute([$encarregado, $nifPaga, (int) $p['aluno_id']]);
                if ($paga) {
                    $db->prepare('UPDATE propinas SET valor_cents = ?, vencimento = ?, pago_em = ?, metodo = ? WHERE id = ?')->execute([$valor, $venc, $dataPag, $metodo, $id]);
                } else {
                    $db->prepare('UPDATE propinas SET valor_cents = ?, vencimento = ? WHERE id = ?')->execute([$valor, $venc, $id]);
                }
                $depois = propinaCompleta($id);
                registarAuditoria('propina', 'editar', resumoPropinaTexto($p) . ' → ' . resumoPropinaTexto($depois), (string) $motivo);
                aviso('ok', 'dados_guardados');
            }
        }
    }
    $volta = isset($irPara) ? $irPara : array_filter([
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
$apagarId = $edita ? inteiro($_GET['apagar'] ?? '') : 0;
$anularId = $edita ? inteiro($_GET['anular'] ?? '') : 0;

$sql = 'SELECT p.*, u.nome, t.nome AS turma, a.numero, a.nif, a.encarregado FROM propinas p
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
$aApagar = $apagarId ? propinaCompleta($apagarId) : null;
$mesPadrao = in_array(substr(hojeISO(), 0, 7), MESES_LETIVOS, true) ? substr(hojeISO(), 0, 7) : MESES_LETIVOS[0];
$alunosLista = $edita ? $db->query('SELECT u.id, u.nome, t.nome AS turma FROM alunos a JOIN utilizadores u ON u.id = a.utilizador_id JOIN turmas t ON t.id = a.turma_id WHERE u.ativo = 1 ORDER BY t.ano, t.nome, a.numero')->fetchAll() : [];
$aAnular = $anularId ? propinaCompleta($anularId) : null;
$filtros = array_filter(['turma' => $filtroTurma ?: null, 'mes' => $mesFiltro ?? '', 'estado' => $estadoFiltro ?: null, 'q' => $procura ?: null], static fn ($v) => $v !== null);
echo separadoresFinancas('propinas');
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
    <div class="tabela-rolagem"><table class="tabela densa">
        <thead><tr><th><?= e(t('aluno')) ?> · <?= e(t('turma')) ?></th><?php if ($mesFiltro === null): ?><th><?= e(t('mes')) ?></th><?php endif; ?><th class="num"><?= e(t('valor')) ?></th><th><?= e(t('vencimento')) ?></th><th><?= e(t('estado')) ?></th><th></th></tr></thead>
        <tbody>
        <?php foreach ($lista as $p): $estado = estadoPropina($p); ?>
            <tr><td><?= e($p['nome']) ?><small class="motivo"><?= e($p['turma']) ?><?= $p['nif'] !== '' ? ' · NIF ' . e($p['nif']) : '' ?></small></td><?php if ($mesFiltro === null): ?><td><?= e(nomeMes($p['mes'], true)) ?></td><?php endif; ?>
                <td class="num"><?= e(euro((int) $p['valor_cents'])) ?></td><td><?= e(dataFmt($p['vencimento'])) ?></td>
                <td><span class="etiqueta <?= $estado === 'paga' ? 'pos' : ($estado === 'atraso' ? 'neg' : '') ?>"><?= e(t('estado_' . $estado)) ?></span>
                    <?php if ($p['pago_em']): ?><small class="motivo"><?= e(dataFmt($p['pago_em'])) ?></small><?php endif; ?></td>
                <td class="acoes compactas">
                <?php if ($estado === 'paga'): ?>
                    <?= botaoIcone('baixar', t('recibo'), ligacao('propinas', ['pdf' => $p['id']])) ?>
                <?php elseif ($edita): ?>
                    <?= botaoIcone('certo', t('registar_pagamento'), ligacao('propinas', $filtros + ['pagar' => $p['id']]), 'verde') ?>
                <?php endif; ?>
                <?php if ($edita): ?>
                    <?= botaoIcone('editar', t('editar'), ligacao('propinas', $filtros + ['editar' => $p['id']])) ?>
                    <?php if ($estado === 'paga'): ?><?= botaoIcone('desfazer', t('anular_pagamento'), ligacao('propinas', $filtros + ['anular' => $p['id']])) ?><?php endif; ?>
                    <?= botaoIcone('lixo', t('apagar'), ligacao('propinas', $filtros + ['apagar' => $p['id']]), 'perigo') ?>
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
<?php elseif ($aEditar): $editaPaga = !empty($aEditar['pago_em']); ?>
    <h2><?= e(t('editar_propina')) ?></h2>
    <p><strong><?= e($aEditar['nome']) ?></strong><br><span class="suave"><?= e($aEditar['turma']) ?> · <?= e(nomeMes($aEditar['mes'])) ?></span></p>
    <?php if ($editaPaga): ?><p class="aviso-perigo"><?= e(t('aviso_editar_pago')) ?></p><?php endif; ?>
    <form method="post" class="formulario">
        <?= csrfCampo() ?><input type="hidden" name="acao" value="editar"><input type="hidden" name="id" value="<?= (int) $aEditar['id'] ?>">
        <input type="hidden" name="f_turma" value="<?= (int) $filtroTurma ?>"><input type="hidden" name="f_mes" value="<?= e($mesFiltro ?? '') ?>"><input type="hidden" name="f_estado" value="<?= e($estadoFiltro) ?>">
        <label><span><?= e(t('valor')) ?> (€)</span><input type="text" inputmode="decimal" name="valor" value="<?= e(numCents((int) $aEditar['valor_cents'])) ?>" maxlength="12" required></label>
        <label><span><?= e(t('vencimento')) ?></span><input type="date" name="vencimento" value="<?= e($aEditar['vencimento']) ?>" required></label>
        <div class="linha-campos">
            <label><span><?= e(t('encarregado_paga')) ?> <small>(<?= e(t('opcional')) ?>)</small></span><input type="text" name="encarregado" value="<?= e($aEditar['encarregado']) ?>" maxlength="80" autocomplete="off"></label>
            <label><span><?= e(t('nif')) ?> <small>(<?= e(t('opcional')) ?>)</small></span><input type="text" inputmode="numeric" name="nif" value="<?= e($aEditar['nif']) ?>" maxlength="14" autocomplete="off" placeholder="9 <?= e(t('digitos')) ?>"></label>
        </div>
        <p class="suave pequeno"><?= e(t('ajuda_nif_propina')) ?></p>
        <?php if ($editaPaga): ?>
        <label><span><?= e(t('pago_em')) ?></span><input type="date" name="data_pagamento" value="<?= e($aEditar['pago_em']) ?>" max="<?= e(hojeISO()) ?>" required></label>
        <label><span><?= e(t('metodo')) ?></span><select name="metodo"><?php foreach (metodosPagamento() as $mp): ?><option value="<?= e($mp) ?>"<?= $mp === $aEditar['metodo'] ? ' selected' : '' ?>><?= e(t('metodo_' . $mp)) ?></option><?php endforeach; ?></select></label>
        <label><span><?= e(t('justificacao')) ?></span><textarea name="motivo" rows="3" maxlength="300" minlength="8" required placeholder="<?= e(t('justificacao_exemplo')) ?>"></textarea></label>
        <?php else: ?>
        <label><span><?= e(t('motivo_alteracao')) ?> <small>(<?= e(t('opcional')) ?>)</small></span><input type="text" name="motivo" maxlength="300"></label>
        <?php endif; ?>
        <p class="barra-acoes"><button type="submit" class="botao"><?= e(t('guardar')) ?></button> <a class="botao secundario" href="<?= e(ligacao('propinas', $filtros)) ?>"><?= e(t('cancelar')) ?></a></p>
    </form>
<?php elseif ($aApagar || $aAnular): $alvo = $aApagar ?? $aAnular; $modoApagar = $aApagar !== null; ?>
    <h2><?= e($modoApagar ? t('apagar_registo') : t('anular_pagamento')) ?></h2>
    <p class="aviso-perigo"><?= e($modoApagar ? t('aviso_apagar_propina') : t('aviso_anular_pagamento')) ?></p>
    <p><strong><?= e($alvo['nome']) ?></strong><br><span class="suave"><?= e($alvo['turma']) ?> · <?= e(nomeMes($alvo['mes'])) ?> · <?= e(euro((int) $alvo['valor_cents'])) ?><?= $alvo['pago_em'] ? ' · ' . e(t('estado_paga')) . ' ' . e(dataFmt($alvo['pago_em'])) : '' ?></span></p>
    <form method="post" class="formulario">
        <?= csrfCampo() ?><input type="hidden" name="acao" value="<?= $modoApagar ? 'apagar' : 'anular' ?>"><input type="hidden" name="id" value="<?= (int) $alvo['id'] ?>">
        <input type="hidden" name="f_turma" value="<?= (int) $filtroTurma ?>"><input type="hidden" name="f_mes" value="<?= e($mesFiltro ?? '') ?>"><input type="hidden" name="f_estado" value="<?= e($estadoFiltro) ?>">
        <label><span><?= e(t('justificacao')) ?></span><textarea name="motivo" rows="3" maxlength="300" minlength="8" required placeholder="<?= e(t('justificacao_exemplo')) ?>"></textarea></label>
        <p class="barra-acoes"><button type="submit" class="botao botao-perigo"><?= $modoApagar ? icone('lixo') . '<span>' . e(t('apagar_definitivamente')) . '</span>' : e(t('anular_pagamento')) ?></button> <a class="botao secundario" href="<?= e(ligacao('propinas', $filtros)) ?>"><?= e(t('cancelar')) ?></a></p>
    </form>
<?php else: ?>
    <div class="bloco-form">
    <h2><?= e(t('emitir_propinas')) ?></h2>
    <form method="post" class="formulario">
        <?= csrfCampo() ?><input type="hidden" name="acao" value="emitir">
        <label><span><?= e(t('mes')) ?></span><select name="mes"><?php foreach (MESES_LETIVOS as $m): ?><option value="<?= e($m) ?>"<?= $m === $mesPadrao ? ' selected' : '' ?>><?= e(nomeMes($m)) ?></option><?php endforeach; ?></select></label>
        <button type="submit" class="botao"><?= icone('mais') ?><span><?= e(t('emitir_propinas')) ?></span></button>
        <p class="suave"><?= e(t('ajuda_emitir', euro(propinaDoAno(7)), euro(propinaDoAno(8)), euro(propinaDoAno(9)), (int) parametro('dia_vencimento'))) ?></p>
    </form>
    </div>

    <div class="bloco-form">
    <h3><?= e(t('adicionar_propina')) ?></h3>
    <form method="post" class="formulario">
        <?= csrfCampo() ?><input type="hidden" name="acao" value="nova">
        <label><span><?= e(t('aluno')) ?></span><select name="aluno_id" required>
            <?php foreach ($alunosLista as $al): ?><option value="<?= (int) $al['id'] ?>"><?= e($al['turma'] . ' · ' . $al['nome']) ?></option><?php endforeach; ?></select></label>
        <div class="linha-campos">
            <label><span><?= e(t('mes')) ?></span><select name="mes"><?php foreach (MESES_LETIVOS as $m): ?><option value="<?= e($m) ?>"<?= $m === $mesPadrao ? ' selected' : '' ?>><?= e(nomeMes($m)) ?></option><?php endforeach; ?></select></label>
            <label><span><?= e(t('valor')) ?> (€)</span><input type="text" inputmode="decimal" name="valor" maxlength="12" required placeholder="<?= e(numCents(propinaDoAno(7))) ?>"></label>
        </div>
        <label><span><?= e(t('vencimento')) ?></span><input type="date" name="vencimento" value="<?= e(($mesPadrao ?? '2026-09') . '-' . sprintf('%02d', (int) parametro('dia_vencimento'))) ?>" required></label>
        <div class="linha-campos">
            <label><span><?= e(t('pago_em')) ?> <small>(<?= e(t('opcional')) ?>)</small></span><input type="date" name="data_pagamento" max="<?= e(hojeISO()) ?>"></label>
            <label><span><?= e(t('metodo')) ?></span><select name="metodo"><?php foreach (metodosPagamento() as $mp): ?><option value="<?= e($mp) ?>"><?= e(t('metodo_' . $mp)) ?></option><?php endforeach; ?></select></label>
        </div>
        <button type="submit" class="botao"><?= icone('mais') ?><span><?= e(t('adicionar_propina')) ?></span></button>
        <p class="suave pequeno"><?= e(t('ajuda_adicionar_propina')) ?></p>
    </form>
    </div>

    <div class="bloco-form">
    <h3><?= e(t('atualizar_em_massa')) ?></h3>
    <form method="post" class="formulario">
        <?= csrfCampo() ?><input type="hidden" name="acao" value="massa">
        <div class="linha-campos">
            <label><span><?= e(t('mes')) ?></span><select name="mes"><?php foreach (MESES_LETIVOS as $m): ?><option value="<?= e($m) ?>"<?= $m === $mesPadrao ? ' selected' : '' ?>><?= e(nomeMes($m)) ?></option><?php endforeach; ?></select></label>
            <label><span><?= e(t('ano_escolaridade')) ?></span><select name="ano"><option value="0"><?= e(t('todos')) ?></option><?php foreach ([7, 8, 9] as $an): ?><option value="<?= $an ?>"><?= $an ?>.º</option><?php endforeach; ?></select></label>
        </div>
        <label class="linha-check"><input type="checkbox" name="seguintes" value="1"> <span><?= e(t('e_meses_seguintes')) ?></span></label>
        <div class="linha-campos">
            <label><span><?= e(t('novo_valor')) ?> (€)</span><input type="text" inputmode="decimal" name="valor" maxlength="12" placeholder="<?= e(t('deixar_vazio')) ?>"></label>
            <label><span><?= e(t('novo_dia_vencimento')) ?></span><input type="number" name="dia" min="1" max="28" placeholder="<?= e(t('deixar_vazio')) ?>"></label>
        </div>
        <label class="linha-check"><input type="checkbox" name="guardar_padrao" value="1"> <span><?= e(t('guardar_como_padrao')) ?></span></label>
        <label><span><?= e(t('justificacao')) ?></span><textarea name="motivo" rows="2" maxlength="300" minlength="8" required placeholder="<?= e(t('justificacao_exemplo_massa')) ?>"></textarea></label>
        <button type="submit" class="botao" data-confirmar-botao="<?= e(t('confirmar_massa')) ?>"><?= e(t('atualizar_propinas')) ?></button>
        <p class="suave pequeno"><?= e(t('ajuda_massa')) ?></p>
    </form>
    </div>
    <?php if (temPerfil(...PAGINAS['financeiro'])): ?><p><a class="botao secundario" href="<?= e(ligacao('financeiro', ['excel' => 'propinas'])) ?>"><?= icone('excel') ?><span><?= e(t('baixar_excel')) ?></span></a></p><?php endif; ?>
<?php endif; ?>
</section>
</div>
