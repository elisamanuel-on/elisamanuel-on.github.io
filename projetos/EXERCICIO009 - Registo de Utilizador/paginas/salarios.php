<?php
/**
 * Salários. Só a contabilidade define contratos, processa o mês, corrige recibos, marca pagamentos e apaga (sempre com justificação)
 * (nunca o seu próprio contrato).
 * A direção consulta tudo. A secretaria só vê quem da equipa já foi pago, sem valores.
 * Cada pessoa (professor, portaria) só vê os seus recibos de vencimento.
 */
if (!defined('APP')) { http_response_code(403); exit; }

$u = utilizador();
$completa = temPerfil('direcao', 'contabilidade');   // vê tudo, com valores
$limitada = temPerfil('secretaria');                  // só o estado do pagamento
$edita = ehContabilista();                            // altera dados financeiros
$gere = $completa;
$db = bd();

function salarioCompleto(int $id): ?array
{
    $st = bd()->prepare(
        "SELECT s.*, u.nome, u.perfil, c.nif, c.niss, COALESCE(NULLIF(c.cargo, ''), f.cargo, u.perfil) AS cargo
           FROM salarios s JOIN utilizadores u ON u.id = s.utilizador_id LEFT JOIN contratos c ON c.utilizador_id = s.utilizador_id
           LEFT JOIN funcionarios f ON f.utilizador_id = s.utilizador_id WHERE s.id = ?"
    );
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

/** As opções do método de pagamento dos salários (transferência, numerário, cheque). */
function opcoesMetodoSalario(?string $atual = null): string
{
    $html = '';
    foreach (METODOS_SALARIO as $m) {
        $html .= '<option value="' . e($m) . '"' . ($m === $atual ? ' selected' : '') . '>' . e(t('metodo_' . $m)) . '</option>';
    }
    return $html;
}

/** Só a contabilidade altera contratos, e nunca o seu próprio. */
function podeEditarContrato(array $alvo): bool
{
    return ehContabilista() && (int) $alvo['id'] !== (int) utilizador()['id'];
}

// ---------- recibo de vencimento em PDF ----------
if (isset($_GET['pdf'])) {
    $s = salarioCompleto(inteiro($_GET['pdf']));
    if (!$s || (!$completa && (int) $s['utilizador_id'] !== (int) $u['id'])) {
        aviso('erro', 'recibo_nao_encontrado');
        redirecionar(ligacao('salarios'));
    }
    enviarPdf(pdfReciboVencimento($s), 'recibo_vencimento_' . $s['nome'] . '_' . $s['mes']);
}

// ---------- ações ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
    if (!$edita) {
        http_response_code(403);
        exit(t('erro_403_texto'));
    }
    $acao = $_POST['acao'] ?? '';
    $mes = mesValido($_POST['mes'] ?? null);

    if ($acao === 'contrato') {
        $id = inteiro($_POST['id'] ?? '');
        $alvo = null;
        foreach (pessoalComContrato() as $p) {
            if ((int) $p['id'] === $id) {
                $alvo = $p;
            }
        }
        $bruto = lerDinheiro($_POST['bruto'] ?? null, 2000000);
        $irs = lerPercentagem($_POST['irs'] ?? null, 50.0);
        $cargo = limpar($_POST['cargo'] ?? '', 40);
        // NIF e NISS aparecem no recibo de vencimento (a edição rápida na tabela não os envia: ficam como estão)
        $nif = array_key_exists('nif', $_POST) ? lerNif($_POST['nif']) : (string) ($alvo['nif'] ?? '');
        $niss = array_key_exists('niss', $_POST) ? lerNiss($_POST['niss']) : (string) ($alvo['niss'] ?? '');
        if (!$alvo || $bruto === false || $bruto <= 0 || $irs === false || $cargo === '') {
            aviso('erro', 'dados_invalidos');
        } elseif (!podeEditarContrato($alvo)) {
            aviso('erro', 'contrato_sem_permissao');
        } elseif ($nif === null || $niss === null) {
            aviso('erro', $nif === null ? 'nif_invalido' : 'niss_invalido');
            redirecionar(ligacao('salarios', ['contrato' => $id]));
        } else {
            $db->prepare('INSERT INTO contratos (utilizador_id, cargo, bruto_cents, irs_taxa, nif, niss) VALUES (?, ?, ?, ?, ?, ?)
                          ON CONFLICT (utilizador_id) DO UPDATE SET cargo = excluded.cargo, bruto_cents = excluded.bruto_cents, irs_taxa = excluded.irs_taxa, nif = excluded.nif, niss = excluded.niss')
               ->execute([$id, $cargo, $bruto, $irs, $nif, $niss]);
            aviso('ok', 'contrato_guardado');
        }
    } elseif ($acao === 'processar') {
        if ($mes === null) {
            aviso('erro', 'dados_invalidos');
        } else {
            $n = processarSalarios($db, $mes);
            aviso($n > 0 ? 'ok' : 'erro', $n > 0 ? 'salarios_processados' : 'salarios_ja_processados', $n);
        }
    } elseif ($acao === 'novo_recibo') {
        // criar o recibo de uma pessoa num mês (por exemplo, um funcionário que entrou a meio do ano), com pagamento opcional
        $pessoaId = inteiro($_POST['pessoa_id'] ?? '');
        $bruto = lerDinheiro($_POST['bruto'] ?? null, 2000000);
        $irs = lerPercentagem($_POST['irs'] ?? null, 50.0);
        $dataPag = ($_POST['data_pagamento'] ?? '') !== '' ? dataValida($_POST['data_pagamento'] ?? null) : '';
        $metodo = lerMetodoSalario($_POST['metodo'] ?? null);
        $existe = $db->prepare('SELECT 1 FROM salarios WHERE utilizador_id = ? AND mes = ?');
        $existe->execute([$pessoaId, (string) $mes]);
        $pessoaOk = in_array($pessoaId, array_map(static fn ($x) => (int) $x['id'], pessoalComContrato()), true);
        if (!$pessoaOk || $mes === null || $bruto === false || $bruto <= 0 || $irs === false || $dataPag === null || ($dataPag !== '' && $metodo === null)) {
            aviso('erro', 'dados_invalidos');
        } elseif ($pessoaId === (int) $u['id']) {
            aviso('erro', 'recibo_proprio');
        } elseif ($existe->fetchColumn()) {
            aviso('erro', 'recibo_existe');
        } else {
            $c = calcularSalario($bruto, $irs);
            $db->prepare('INSERT INTO salarios (utilizador_id, mes, bruto_cents, irs_cents, ss_trab_cents, ss_ent_cents, liquido_cents, pago_em, metodo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
               ->execute([$pessoaId, $mes, $c['bruto'], $c['irs'], $c['ss_trab'], $c['ss_ent'], $c['liquido'], $dataPag !== '' ? $dataPag : null, $dataPag !== '' ? $metodo : null]);
            aviso('ok', 'recibo_adicionado');
        }
    } elseif ($acao === 'pagar_todos') {
        $dataPag = ($_POST['data_pagamento'] ?? '') !== '' ? dataValida($_POST['data_pagamento'] ?? null) : hojeISO();
        $metodo = lerMetodoSalario($_POST['metodo'] ?? 'transferencia');
        if ($mes !== null && $dataPag !== null && $metodo !== null) {
            $db->prepare('UPDATE salarios SET pago_em = ?, metodo = ? WHERE mes = ? AND pago_em IS NULL')->execute([$dataPag, $metodo, $mes]);
            aviso('ok', 'salarios_pagos');
        } else {
            aviso('erro', 'dados_invalidos');
        }
    } elseif (in_array($acao, ['pagar', 'anular', 'apagar', 'editar_recibo'], true)) {
        $s = salarioCompleto(inteiro($_POST['id'] ?? ''));
        $paga = $s && !empty($s['pago_em']);
        $motivo = motivoValido($_POST['motivo'] ?? '');
        // anular e apagar pedem sempre justificação; corrigir um recibo já pago também
        $pedeMotivo = in_array($acao, ['anular', 'apagar'], true) || ($acao === 'editar_recibo' && $paga);
        $painel = ['editar_recibo' => 'editar'][$acao] ?? $acao;
        if (!$s) {
            aviso('erro', 'dados_invalidos');
        } elseif ($acao !== 'pagar' && $pedeMotivo && $motivo === null) {
            aviso('erro', 'motivo_curto');
            redirecionar(ligacao('salarios', ['mes' => $s['mes'], $painel => (int) $s['id']]));
        } elseif ($acao !== 'pagar' && (int) $s['utilizador_id'] === (int) $u['id']) {
            aviso('erro', 'recibo_proprio');        // a contabilista não altera nem apaga o seu próprio recibo
        } elseif ($acao === 'pagar' && !$paga) {
            $dataPag = ($_POST['data_pagamento'] ?? '') !== '' ? dataValida($_POST['data_pagamento'] ?? null) : hojeISO();
            $metodo = lerMetodoSalario($_POST['metodo'] ?? 'transferencia');
            if ($dataPag === null || $metodo === null) {
                aviso('erro', 'dados_invalidos');
            } else {
                $db->prepare('UPDATE salarios SET pago_em = ?, metodo = ? WHERE id = ?')->execute([$dataPag, $metodo, (int) $s['id']]);
                aviso('ok', 'salario_pago');
            }
        } elseif ($acao === 'anular' && $paga) {
            $db->prepare('UPDATE salarios SET pago_em = NULL, metodo = NULL WHERE id = ?')->execute([(int) $s['id']]);
            registarAuditoria('salario', 'anular', resumoSalarioTexto($s), (string) $motivo);
            aviso('ok', 'pagamento_anulado');
        } elseif ($acao === 'apagar') {
            $db->prepare('DELETE FROM salarios WHERE id = ?')->execute([(int) $s['id']]);
            registarAuditoria('salario', 'apagar', resumoSalarioTexto($s), (string) $motivo);
            aviso('ok', 'salario_apagado');
        } elseif ($acao === 'editar_recibo') {
            // o recibo é recalculado em PHP com os parâmetros atuais (IRS pela taxa escrita, Segurança Social pelos parâmetros)
            $bruto = lerDinheiro($_POST['bruto'] ?? null, 2000000);
            $irs = lerPercentagem($_POST['irs'] ?? null, 50.0);
            $dataPag = $paga ? dataValida($_POST['data_pagamento'] ?? null) : null;
            $metodo = $paga ? lerMetodoSalario($_POST['metodo'] ?? null) : null;
            if ($bruto === false || $bruto <= 0 || $irs === false || ($paga && ($dataPag === null || $metodo === null))) {
                aviso('erro', 'dados_invalidos');
            } else {
                $c = calcularSalario($bruto, $irs);
                $db->prepare('UPDATE salarios SET bruto_cents = ?, irs_cents = ?, ss_trab_cents = ?, ss_ent_cents = ?, liquido_cents = ?, pago_em = ?, metodo = ? WHERE id = ?')
                   ->execute([$c['bruto'], $c['irs'], $c['ss_trab'], $c['ss_ent'], $c['liquido'], $paga ? $dataPag : null, $paga ? $metodo : null, (int) $s['id']]);
                registarAuditoria('salario', 'editar', resumoSalarioTexto($s) . ' → ' . resumoSalarioTexto(salarioCompleto((int) $s['id'])), (string) $motivo);
                aviso('ok', 'dados_guardados');
            }
        } else {
            aviso('erro', 'dados_invalidos');
        }
        $mes = $s['mes'] ?? $mes;
    }
    redirecionar(ligacao('salarios', $mes !== null ? ['mes' => $mes] : []));
}

// ---------- vista da secretaria: só quem já foi pago, sem valores ----------
if ($limitada) {
    $mesesComDados = array_column($db->query('SELECT DISTINCT mes FROM salarios ORDER BY mes DESC')->fetchAll(), 'mes');
    $mesVer = mesValido($_GET['mes'] ?? null) ?? ($mesesComDados[0] ?? MESES_LETIVOS[0]);
    $st = $db->prepare(
        "SELECT s.pago_em, u.nome, u.perfil, f.area FROM salarios s JOIN utilizadores u ON u.id = s.utilizador_id LEFT JOIN funcionarios f ON f.utilizador_id = u.id
          WHERE s.mes = ? ORDER BY CASE WHEN s.pago_em IS NULL THEN 1 ELSE 0 END, u.nome"
    );
    $st->execute([$mesVer]);
    $equipa = $st->fetchAll();
    $pagos = count(array_filter($equipa, static fn ($l) => $l['pago_em'] !== null));
    ?>
<section class="kpis kpis-3">
    <div class="kpi"><span class="kpi-valor"><?= $pagos ?> / <?= count($equipa) ?></span><span class="kpi-rotulo"><?= e(t('equipa_ja_paga')) ?> · <?= e(nomeMes($mesVer, true)) ?></span></div>
    <div class="kpi <?= count($equipa) - $pagos > 0 ? 'kpi-aviso' : '' ?>"><span class="kpi-valor"><?= count($equipa) - $pagos ?></span><span class="kpi-rotulo"><?= e(t('por_pagar')) ?></span></div>
</section>
<section class="cartao">
    <form method="get" class="filtros sem-cartao" data-auto>
        <input type="hidden" name="p" value="salarios">
        <label><span><?= e(t('mes')) ?></span><select name="mes"><?php foreach (MESES_LETIVOS as $m): ?><option value="<?= e($m) ?>"<?= $m === $mesVer ? ' selected' : '' ?>><?= e(nomeMes($m)) ?></option><?php endforeach; ?></select></label>
        <button type="submit" class="botao secundario"><?= e(t('filtrar')) ?></button>
    </form>
    <p class="suave"><?= e(t('equipa_paga_info')) ?></p>
    <?php if (!$equipa): ?><p class="suave"><?= e(t('mes_sem_salarios')) ?></p><?php else: ?>
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th><?= e(t('nome')) ?></th><th><?= e(t('area_funcao')) ?></th><th><?= e(t('estado')) ?></th><th><?= e(t('pago_em')) ?></th></tr></thead>
        <tbody>
        <?php foreach ($equipa as $l): ?>
            <tr><td><?= e($l['nome']) ?></td><td class="pequeno"><?= e(rotuloPessoa($l)) ?></td>
                <td><span class="etiqueta <?= $l['pago_em'] ? 'pos' : '' ?>"><?= e($l['pago_em'] ? t('pago') : t('por_pagar')) ?></span></td>
                <td><?= e($l['pago_em'] ? dataFmt($l['pago_em']) : '—') ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</section>
<?php
    return;
}

// ---------- vista de cada pessoa: os seus recibos ----------
if (!$completa) {
    $st = $db->prepare("SELECT s.*, u.nome, COALESCE(NULLIF(c.cargo, ''), f.cargo, u.perfil) AS cargo FROM salarios s JOIN utilizadores u ON u.id = s.utilizador_id
                         LEFT JOIN contratos c ON c.utilizador_id = s.utilizador_id LEFT JOIN funcionarios f ON f.utilizador_id = s.utilizador_id WHERE s.utilizador_id = ? ORDER BY s.mes DESC");
    $st->execute([(int) $u['id']]);
    $meus = $st->fetchAll();
    $contrato = $db->prepare('SELECT * FROM contratos WHERE utilizador_id = ?');
    $contrato->execute([(int) $u['id']]);
    $contrato = $contrato->fetch() ?: null;
    $ultimo = $meus[0] ?? null;
    $somaBruto = array_sum(array_map(static fn ($s) => (int) $s['bruto_cents'], $meus));
    $somaLiq = array_sum(array_map(static fn ($s) => (int) $s['liquido_cents'], $meus));
    ?>
<section class="kpis kpis-3">
    <div class="kpi"><span class="kpi-valor"><?= e($ultimo ? euro((int) $ultimo['liquido_cents']) : '—') ?></span><span class="kpi-rotulo"><?= e(t('ultimo_liquido')) ?></span></div>
    <div class="kpi"><span class="kpi-valor"><?= e(euro($somaBruto)) ?></span><span class="kpi-rotulo"><?= e(t('bruto_acumulado')) ?></span></div>
    <div class="kpi"><span class="kpi-valor"><?= e(euro($somaLiq)) ?></span><span class="kpi-rotulo"><?= e(t('liquido_acumulado')) ?></span></div>
</section>
<div class="duas-colunas largo-esq">
<section class="cartao">
    <h2><?= e(t('os_meus_recibos')) ?></h2>
    <?php if (!$meus): ?><p class="suave"><?= e(t('sem_recibos')) ?></p><?php else: ?>
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th><?= e(t('mes')) ?></th><th class="num"><?= e(t('bruto')) ?></th><th class="num"><?= e(t('irs_retido')) ?></th><th class="num"><?= e(t('ss_trabalhador')) ?></th><th class="num"><?= e(t('liquido')) ?></th><th><?= e(t('estado')) ?></th><th></th></tr></thead>
        <tbody>
        <?php foreach ($meus as $s): ?>
            <tr><td><?= e(nomeMes($s['mes'])) ?></td><td class="num"><?= e(euro((int) $s['bruto_cents'])) ?></td><td class="num"><?= e(euro((int) $s['irs_cents'])) ?></td>
                <td class="num"><?= e(euro((int) $s['ss_trab_cents'])) ?></td><td class="num"><strong><?= e(euro((int) $s['liquido_cents'])) ?></strong></td>
                <td><span class="etiqueta <?= $s['pago_em'] ? 'pos' : '' ?>"><?= e($s['pago_em'] ? t('pago') : t('por_pagar')) ?></span></td>
                <td class="acoes"><a class="botao secundario pequeno" href="<?= e(ligacao('salarios', ['pdf' => $s['id']])) ?>"><?= icone('baixar') ?><span><?= e(t('recibo')) ?></span></a></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</section>
<section class="cartao">
    <h2><?= e(t('o_meu_contrato')) ?></h2>
    <?php if (!$contrato): ?><p class="suave"><?= e(t('sem_contrato')) ?></p><?php else: ?>
    <dl class="dados">
        <dt><?= e(t('cargo')) ?></dt><dd><?= e($contrato['cargo']) ?></dd>
        <dt><?= e(t('bruto')) ?></dt><dd><?= e(euro((int) $contrato['bruto_cents'])) ?></dd>
        <dt><?= e(t('taxa_irs')) ?></dt><dd><?= e(numFmt((float) $contrato['irs_taxa'], 1)) ?>%</dd>
        <dt><?= e(t('taxa_ss_trab')) ?></dt><dd><?= e(numFmt(parametro('ss_trab'), 2)) ?>%</dd>
        <?php if ($contrato['nif'] !== ''): ?><dt><?= e(t('nif')) ?></dt><dd><?= e($contrato['nif']) ?></dd><?php endif; ?>
        <?php if ($contrato['niss'] !== ''): ?><dt><?= e(t('niss')) ?></dt><dd><?= e($contrato['niss']) ?></dd><?php endif; ?>
    </dl>
    <?php endif; ?>
    <p class="suave pequeno"><?= e(t('nota_salarios')) ?></p>
</section>
</div>
<?php
    return;
}

// ---------- vista completa: contabilidade (edita) e direção (só consulta) ----------
$mesesComDados = array_column($db->query('SELECT DISTINCT mes FROM salarios ORDER BY mes DESC')->fetchAll(), 'mes');
$mesVer = mesValido($_GET['mes'] ?? null) ?? ($mesesComDados[0] ?? MESES_LETIVOS[0]);
$st = $db->prepare(
    "SELECT s.*, u.nome, u.perfil, f.area, COALESCE(NULLIF(c.cargo, ''), f.cargo, u.perfil) AS cargo FROM salarios s JOIN utilizadores u ON u.id = s.utilizador_id
       LEFT JOIN contratos c ON c.utilizador_id = s.utilizador_id LEFT JOIN funcionarios f ON f.utilizador_id = s.utilizador_id
      WHERE s.mes = ? ORDER BY CASE u.perfil WHEN 'direcao' THEN 0 WHEN 'contabilidade' THEN 1 WHEN 'secretaria' THEN 2 WHEN 'professor' THEN 3 ELSE 4 END, f.area, u.nome"
);
$st->execute([$mesVer]);
$recibos = $st->fetchAll();
$tot = ['b' => 0, 'i' => 0, 'st' => 0, 'se' => 0, 'l' => 0];
$porPagar = 0;
foreach ($recibos as $s) {
    $tot['b'] += (int) $s['bruto_cents'];
    $tot['i'] += (int) $s['irs_cents'];
    $tot['st'] += (int) $s['ss_trab_cents'];
    $tot['se'] += (int) $s['ss_ent_cents'];
    $tot['l'] += (int) $s['liquido_cents'];
    $porPagar += $s['pago_em'] ? 0 : 1;
}
$pessoal = pessoalComContrato();
$editarRecibo = $edita ? salarioCompleto(inteiro($_GET['editar'] ?? '')) : null;
$apagarRecibo = $edita ? salarioCompleto(inteiro($_GET['apagar'] ?? '')) : null;
$anularRecibo = $edita ? salarioCompleto(inteiro($_GET['anular'] ?? '')) : null;
$contratoId = $edita ? inteiro($_GET['contrato'] ?? '') : 0;
$contratoAlvo = null;
foreach ($pessoal as $pe) {
    if ($contratoId && (int) $pe['id'] === $contratoId && podeEditarContrato($pe)) {
        $contratoAlvo = $pe;
    }
}
$pagarRecibo = $edita ? salarioCompleto(inteiro($_GET['pagar'] ?? '')) : null;
if ($pagarRecibo && !empty($pagarRecibo['pago_em'])) { $pagarRecibo = null; }
echo separadoresFinancas('salarios');
$ssT = parametro('ss_trab');
$ssE = parametro('ss_ent');
$dadosSs = ' data-ss-trab="' . e((string) $ssT) . '" data-ss-ent="' . e((string) $ssE) . '"';
?>
<?php if ($editarRecibo): $pagoRec = !empty($editarRecibo['pago_em']);
    $taxaRec = (int) $editarRecibo['bruto_cents'] > 0 ? round((int) $editarRecibo['irs_cents'] * 100 / (int) $editarRecibo['bruto_cents'], 2) : 0; ?>
<section class="cartao painel-acao">
    <h2><?= e(t('corrigir_recibo')) ?> · <?= e($editarRecibo['nome']) ?> · <?= e(nomeMes($editarRecibo['mes'])) ?></h2>
    <?php if ($pagoRec): ?><p class="aviso-perigo"><?= e(t('aviso_editar_pago')) ?></p><?php endif; ?>
    <form method="post" class="formulario" data-calc-salario<?= $dadosSs ?>>
        <?= csrfCampo() ?><input type="hidden" name="acao" value="editar_recibo"><input type="hidden" name="id" value="<?= (int) $editarRecibo['id'] ?>">
        <div class="grelha-campos">
            <label><span><?= e(t('bruto')) ?> (€)</span><input type="text" inputmode="decimal" name="bruto" value="<?= e(numCents((int) $editarRecibo['bruto_cents'])) ?>" maxlength="12" required data-bruto></label>
            <label><span><?= e(t('taxa_irs')) ?> (%)</span><input type="text" inputmode="decimal" name="irs" value="<?= e(rtrim(rtrim(number_format((float) $taxaRec, 2, '.', ''), '0'), '.')) ?>" maxlength="5" required data-irs></label>
            <?php if ($pagoRec): ?><label><span><?= e(t('pago_em')) ?></span><input type="date" name="data_pagamento" value="<?= e($editarRecibo['pago_em']) ?>" max="<?= e(hojeISO()) ?>" required></label>
            <label><span><?= e(t('metodo')) ?></span><select name="metodo"><?= opcoesMetodoSalario($editarRecibo['metodo'] ?? 'transferencia') ?></select></label><?php endif; ?>
        </div>
        <dl class="calculo" aria-live="polite">
            <div><dt><?= e(t('irs_retido')) ?></dt><dd data-out="irs">—</dd></div>
            <div><dt><?= e(t('ss_trabalhador')) ?> (<?= e(numFmt($ssT, 2)) ?>%)</dt><dd data-out="ss_trab">—</dd></div>
            <div><dt><?= e(t('liquido')) ?></dt><dd data-out="liquido">—</dd></div>
            <div><dt><?= e(t('ss_entidade')) ?> (<?= e(numFmt($ssE, 2)) ?>%)</dt><dd data-out="ss_ent">—</dd></div>
            <div><dt><?= e(t('custo_escola')) ?></dt><dd data-out="custo">—</dd></div>
        </dl>
        <label><span><?= e(t('justificacao')) ?><?php if (!$pagoRec): ?> <small>(<?= e(t('opcional')) ?>)</small><?php endif; ?></span><textarea name="motivo" rows="2" maxlength="300"<?= $pagoRec ? ' minlength="8" required' : '' ?> placeholder="<?= e(t('justificacao_exemplo')) ?>"></textarea></label>
        <p class="barra-acoes"><button type="submit" class="botao"><?= e(t('guardar')) ?></button> <a class="botao secundario" href="<?= e(ligacao('salarios', ['mes' => $editarRecibo['mes']])) ?>"><?= e(t('cancelar')) ?></a></p>
        <p class="suave pequeno"><?= e(t('ajuda_recalculo')) ?></p>
    </form>
</section>
<?php elseif ($contratoAlvo): $ca = $contratoAlvo; ?>
<section class="cartao painel-acao">
    <h2><?= e(t('editar_contrato')) ?> · <?= e($ca['nome']) ?></h2>
    <form method="post" class="formulario" data-calc-salario<?= $dadosSs ?>>
        <?= csrfCampo() ?><input type="hidden" name="acao" value="contrato"><input type="hidden" name="id" value="<?= (int) $ca['id'] ?>">
        <div class="grelha-campos">
            <label><span><?= e(t('cargo')) ?></span><input type="text" name="cargo" value="<?= e($ca['cargo'] ?? rotuloPessoa($ca)) ?>" maxlength="40" required></label>
            <label><span><?= e(t('bruto')) ?> (€)</span><input type="text" inputmode="decimal" name="bruto" value="<?= e($ca['bruto_cents'] !== null ? numCents((int) $ca['bruto_cents']) : '') ?>" maxlength="12" required data-bruto></label>
            <label><span><?= e(t('taxa_irs')) ?> (%)</span><input type="text" inputmode="decimal" name="irs" value="<?= e($ca['irs_taxa'] !== null ? rtrim(rtrim(number_format((float) $ca['irs_taxa'], 2, '.', ''), '0'), '.') : '15') ?>" maxlength="5" required data-irs></label>
            <label><span><?= e(t('nif')) ?> <small>(<?= e(t('opcional')) ?>)</small></span><input type="text" inputmode="numeric" name="nif" value="<?= e((string) ($ca['nif'] ?? '')) ?>" maxlength="14" autocomplete="off" placeholder="9 <?= e(t('digitos')) ?>"></label>
            <label><span><?= e(t('niss')) ?> <small>(<?= e(t('opcional')) ?>)</small></span><input type="text" inputmode="numeric" name="niss" value="<?= e((string) ($ca['niss'] ?? '')) ?>" maxlength="14" autocomplete="off" placeholder="11 <?= e(t('digitos')) ?>"></label>
        </div>
        <dl class="calculo" aria-live="polite">
            <div><dt><?= e(t('irs_retido')) ?></dt><dd data-out="irs">—</dd></div>
            <div><dt><?= e(t('ss_trabalhador')) ?> (<?= e(numFmt($ssT, 2)) ?>%)</dt><dd data-out="ss_trab">—</dd></div>
            <div><dt><?= e(t('liquido')) ?></dt><dd data-out="liquido">—</dd></div>
            <div><dt><?= e(t('ss_entidade')) ?> (<?= e(numFmt($ssE, 2)) ?>%)</dt><dd data-out="ss_ent">—</dd></div>
            <div><dt><?= e(t('custo_escola')) ?></dt><dd data-out="custo">—</dd></div>
        </dl>
        <p class="barra-acoes"><button type="submit" class="botao"><?= e(t('guardar')) ?></button> <a class="botao secundario" href="<?= e(ligacao('salarios')) ?>"><?= e(t('cancelar')) ?></a></p>
        <p class="suave pequeno"><?= e(t('ajuda_contrato_fiscal')) ?></p>
    </form>
</section>
<?php elseif ($pagarRecibo): ?>
<section class="cartao painel-acao">
    <h2><?= e(t('registar_pagamento')) ?> · <?= e($pagarRecibo['nome']) ?> · <?= e(nomeMes($pagarRecibo['mes'])) ?></h2>
    <p class="suave"><?= e(t('liquido_a_pagar')) ?>: <strong><?= e(euro((int) $pagarRecibo['liquido_cents'])) ?></strong></p>
    <form method="post" class="formulario">
        <?= csrfCampo() ?><input type="hidden" name="acao" value="pagar"><input type="hidden" name="id" value="<?= (int) $pagarRecibo['id'] ?>">
        <div class="linha-campos">
            <label><span><?= e(t('pago_em')) ?></span><input type="date" name="data_pagamento" value="<?= e(hojeISO()) ?>" max="<?= e(hojeISO()) ?>" required></label>
            <label><span><?= e(t('metodo')) ?></span><select name="metodo"><?= opcoesMetodoSalario('transferencia') ?></select></label>
        </div>
        <p class="barra-acoes"><button type="submit" class="botao verde"><?= e(t('registar_pagamento')) ?></button> <a class="botao secundario" href="<?= e(ligacao('salarios', ['mes' => $pagarRecibo['mes']])) ?>"><?= e(t('cancelar')) ?></a></p>
    </form>
</section>
<?php elseif ($apagarRecibo || $anularRecibo): $alvoRec = $apagarRecibo ?? $anularRecibo; $modoApagarRec = $apagarRecibo !== null; ?>
<section class="cartao painel-acao perigo">
    <h2><?= e($modoApagarRec ? t('apagar_registo') : t('anular_pagamento')) ?> · <?= e($alvoRec['nome']) ?> · <?= e(nomeMes($alvoRec['mes'])) ?></h2>
    <p class="aviso-perigo"><?= e($modoApagarRec ? t('aviso_apagar_salario') : t('aviso_anular_pagamento')) ?></p>
    <p class="suave"><?= e(t('bruto')) ?> <?= e(euro((int) $alvoRec['bruto_cents'])) ?> · <?= e(t('liquido')) ?> <?= e(euro((int) $alvoRec['liquido_cents'])) ?><?= $alvoRec['pago_em'] ? ' · ' . e(t('pago')) . ' ' . e(dataFmt($alvoRec['pago_em'])) : '' ?></p>
    <form method="post" class="formulario">
        <?= csrfCampo() ?><input type="hidden" name="acao" value="<?= $modoApagarRec ? 'apagar' : 'anular' ?>"><input type="hidden" name="id" value="<?= (int) $alvoRec['id'] ?>">
        <label><span><?= e(t('justificacao')) ?></span><textarea name="motivo" rows="2" maxlength="300" minlength="8" required placeholder="<?= e(t('justificacao_exemplo')) ?>"></textarea></label>
        <p class="barra-acoes"><button type="submit" class="botao botao-perigo"><?= $modoApagarRec ? icone('lixo') . '<span>' . e(t('apagar_definitivamente')) . '</span>' : e(t('anular_pagamento')) ?></button> <a class="botao secundario" href="<?= e(ligacao('salarios', ['mes' => $alvoRec['mes']])) ?>"><?= e(t('cancelar')) ?></a></p>
    </form>
</section>
<?php endif; ?>
<section class="kpis">
    <div class="kpi"><span class="kpi-valor"><?= e(euro($tot['b'])) ?></span><span class="kpi-rotulo"><?= e(t('salarios_brutos')) ?> · <?= e(nomeMes($mesVer, true)) ?></span></div>
    <div class="kpi"><span class="kpi-valor"><?= e(euro($tot['l'])) ?></span><span class="kpi-rotulo"><?= e(t('liquido_a_pagar')) ?></span></div>
    <div class="kpi"><span class="kpi-valor"><?= e(euro($tot['st'] + $tot['se'])) ?></span><span class="kpi-rotulo"><?= e(t('ss_total')) ?></span></div>
    <div class="kpi"><span class="kpi-valor"><?= e(euro($tot['i'])) ?></span><span class="kpi-rotulo"><?= e(t('irs_retido')) ?></span></div>
</section>

<section class="cartao">
    <form method="get" class="filtros sem-cartao" data-auto>
        <input type="hidden" name="p" value="salarios">
        <label><span><?= e(t('mes')) ?></span><select name="mes"><?php foreach (MESES_LETIVOS as $m): ?><option value="<?= e($m) ?>"<?= $m === $mesVer ? ' selected' : '' ?>><?= e(nomeMes($m)) ?></option><?php endforeach; ?></select></label>
        <button type="submit" class="botao secundario"><?= e(t('filtrar')) ?></button>
        <?php if (temPerfil(...PAGINAS['financeiro'])): ?><a class="botao secundario" href="<?= e(ligacao('financeiro', ['excel' => 'salarios'])) ?>"><?= icone('excel') ?><span><?= e(t('baixar_excel')) ?></span></a><?php endif; ?>
    </form>
    <?php if (!$recibos): ?>
        <p class="suave"><?= e(t('mes_sem_salarios')) ?></p>
    <?php else: ?>
    <div class="tabela-rolagem"><table class="tabela densa">
        <thead><tr><th><?= e(t('nome')) ?> · <?= e(t('cargo')) ?></th><th class="num"><?= e(t('bruto')) ?></th><th class="num"><?= e(t('irs_retido')) ?></th><th class="num"><?= e(t('ss_trabalhador')) ?></th><th class="num"><?= e(t('liquido')) ?></th><th class="num"><?= e(t('ss_entidade')) ?></th><th><?= e(t('estado')) ?></th><th></th></tr></thead>
        <tbody>
        <?php foreach ($recibos as $s): $rot = rotuloPessoa($s); ?>
            <tr><td><?= e($s['nome']) ?><small class="motivo"><?= e($rot) ?><?= $s['cargo'] !== '' && $s['cargo'] !== $rot ? ' · ' . e($s['cargo']) : '' ?></small></td>
                <td class="num"><?= e(euro((int) $s['bruto_cents'])) ?></td><td class="num"><?= e(euro((int) $s['irs_cents'])) ?></td><td class="num"><?= e(euro((int) $s['ss_trab_cents'])) ?></td>
                <td class="num"><strong><?= e(euro((int) $s['liquido_cents'])) ?></strong></td><td class="num"><?= e(euro((int) $s['ss_ent_cents'])) ?></td>
                <td><span class="etiqueta <?= $s['pago_em'] ? 'pos' : '' ?>"><?= e($s['pago_em'] ? t('pago') : t('por_pagar')) ?></span><?php if ($s['pago_em']): ?><small class="motivo"><?= e(dataFmt($s['pago_em'])) ?><?= !empty($s['metodo']) ? ' · ' . e(t('metodo_' . $s['metodo'])) : '' ?></small><?php endif; ?></td>
                <td class="acoes compactas">
                    <?= botaoIcone('baixar', t('recibo'), ligacao('salarios', ['pdf' => $s['id']])) ?>
                    <?php if ($edita): ?>
                        <?= botaoIcone('editar', t('editar'), ligacao('salarios', ['mes' => $mesVer, 'editar' => $s['id']])) ?>
                        <?php if ($s['pago_em']): ?>
                            <?= botaoIcone('desfazer', t('anular_pagamento'), ligacao('salarios', ['mes' => $mesVer, 'anular' => $s['id']])) ?>
                        <?php else: ?>
                            <?= botaoIcone('certo', t('marcar_pago'), ligacao('salarios', ['mes' => $mesVer, 'pagar' => $s['id']]), 'verde') ?>
                        <?php endif; ?>
                        <?= botaoIcone('lixo', t('apagar'), ligacao('salarios', ['mes' => $mesVer, 'apagar' => $s['id']]), 'perigo') ?>
                    <?php endif; ?></td></tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr><td><?= e(t('total')) ?></td><td class="num"><?= e(euro($tot['b'])) ?></td><td class="num"><?= e(euro($tot['i'])) ?></td><td class="num"><?= e(euro($tot['st'])) ?></td><td class="num"><?= e(euro($tot['l'])) ?></td><td class="num"><?= e(euro($tot['se'])) ?></td><td colspan="2"></td></tr></tfoot>
    </table></div>
    <?php if ($porPagar > 0 && $edita): ?>
        <form method="post" class="barra-acoes"><?= csrfCampo() ?><input type="hidden" name="acao" value="pagar_todos"><input type="hidden" name="mes" value="<?= e($mesVer) ?>">
            <label class="linha-check"><span><?= e(t('pago_em')) ?></span><input type="date" name="data_pagamento" value="<?= e(hojeISO()) ?>" max="<?= e(hojeISO()) ?>" required></label>
            <label class="linha-check"><span><?= e(t('metodo')) ?></span><select name="metodo"><?= opcoesMetodoSalario('transferencia') ?></select></label>
            <button type="submit" class="botao verde" data-confirmar-botao="<?= e(t('confirmar_pagar_todos')) ?>"><?= e(t('marcar_todos_pagos', $porPagar)) ?></button></form>
    <?php endif; ?>
    <?php endif; ?>
</section>

<div class="duas-colunas<?= $edita ? ' largo-esq' : '' ?>">
<section class="cartao">
    <h2><?= e(t('contratos')) ?></h2>
    <p class="suave pequeno"><?= e(t('ajuda_contratos')) ?></p>
    <div class="tabela-rolagem"><table class="tabela densa">
        <thead><tr><th><?= e(t('nome')) ?> · <?= e(t('cargo')) ?></th><th class="num"><?= e(t('bruto')) ?> (€)</th><th class="num"><?= e(t('taxa_irs')) ?> (%)</th><th class="num"><?= e(t('liquido')) ?></th><th class="num"><?= e(t('custo_escola')) ?></th><th></th></tr></thead>
        <tbody>
        <?php foreach ($pessoal as $p): $pode = podeEditarContrato($p);
            $calc = $p['bruto_cents'] !== null ? calcularSalario((int) $p['bruto_cents'], (float) $p['irs_taxa']) : null; ?>
            <tr><td><?= e($p['nome']) ?><small class="motivo"><?= e(($p['cargo'] ?? '') !== '' ? $p['cargo'] : rotuloPessoa($p)) ?><?= ($p['nif'] ?? '') !== '' ? ' · NIF ' . e($p['nif']) : '' ?></small></td>
                <td class="num"><?= e($p['bruto_cents'] !== null ? euro((int) $p['bruto_cents']) : '—') ?></td>
                <td class="num"><?= e($p['irs_taxa'] !== null ? numFmt((float) $p['irs_taxa'], 1) . '%' : '—') ?></td>
                <td class="num"><?= e($calc ? euro($calc['liquido']) : '—') ?></td>
                <td class="num"><?= e($calc ? euro($calc['custo']) : '—') ?></td>
                <td class="acoes compactas"><?php if ($pode): ?><?= botaoIcone('editar', t('editar_contrato'), ligacao('salarios', ['contrato' => $p['id']])) ?>
                <?php elseif ($edita): ?><span class="suave pequeno"><?= e(t('o_seu_contrato')) ?></span><?php endif; ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</section>

<?php if ($edita): ?>
<section class="cartao">
    <h2><?= e(t('processar_salarios')) ?></h2>
    <form method="post" class="formulario">
        <?= csrfCampo() ?><input type="hidden" name="acao" value="processar">
        <label><span><?= e(t('mes')) ?></span><select name="mes"><?php foreach (MESES_LETIVOS as $m): ?><option value="<?= e($m) ?>"<?= $m === $mesVer ? ' selected' : '' ?>><?= e(nomeMes($m)) ?></option><?php endforeach; ?></select></label>
        <button type="submit" class="botao"><?= icone('mais') ?><span><?= e(t('processar_salarios')) ?></span></button>
        <p class="suave"><?= e(t('ajuda_processar', numFmt(parametro('ss_trab'), 2), numFmt(parametro('ss_ent'), 2))) ?></p>
    </form>

    <div class="bloco-form">
    <h3><?= e(t('adicionar_recibo')) ?></h3>
    <form method="post" class="formulario" data-calc-salario<?= $dadosSs ?>>
        <?= csrfCampo() ?><input type="hidden" name="acao" value="novo_recibo">
        <label><span><?= e(t('pessoa')) ?></span><select name="pessoa_id" data-preencher required>
            <?php foreach ($pessoal as $pe): if ((int) $pe['id'] === (int) $u['id']) { continue; } ?>
                <option value="<?= (int) $pe['id'] ?>" data-bruto="<?= e($pe['bruto_cents'] !== null ? numCents((int) $pe['bruto_cents']) : '') ?>" data-irs="<?= e($pe['irs_taxa'] !== null ? rtrim(rtrim(number_format((float) $pe['irs_taxa'], 2, '.', ''), '0'), '.') : '') ?>"><?= e($pe['nome'] . ' · ' . rotuloPessoa($pe)) ?></option>
            <?php endforeach; ?></select></label>
        <label><span><?= e(t('mes')) ?></span><select name="mes"><?php foreach (MESES_LETIVOS as $m): ?><option value="<?= e($m) ?>"<?= $m === $mesVer ? ' selected' : '' ?>><?= e(nomeMes($m)) ?></option><?php endforeach; ?></select></label>
        <div class="linha-campos">
            <label><span><?= e(t('bruto')) ?> (€)</span><input type="text" inputmode="decimal" name="bruto" maxlength="12" required data-bruto></label>
            <label><span><?= e(t('taxa_irs')) ?> (%)</span><input type="text" inputmode="decimal" name="irs" maxlength="5" required data-irs></label>
        </div>
        <dl class="calculo" aria-live="polite">
            <div><dt><?= e(t('irs_retido')) ?></dt><dd data-out="irs">—</dd></div>
            <div><dt><?= e(t('ss_trabalhador')) ?></dt><dd data-out="ss_trab">—</dd></div>
            <div><dt><?= e(t('liquido')) ?></dt><dd data-out="liquido">—</dd></div>
            <div><dt><?= e(t('custo_escola')) ?></dt><dd data-out="custo">—</dd></div>
        </dl>
        <div class="linha-campos">
            <label><span><?= e(t('pago_em')) ?> <small>(<?= e(t('opcional')) ?>)</small></span><input type="date" name="data_pagamento" max="<?= e(hojeISO()) ?>"></label>
            <label><span><?= e(t('metodo')) ?></span><select name="metodo"><?= opcoesMetodoSalario('transferencia') ?></select></label>
        </div>
        <button type="submit" class="botao"><?= icone('mais') ?><span><?= e(t('adicionar_recibo')) ?></span></button>
        <p class="suave pequeno"><?= e(t('ajuda_novo_recibo')) ?></p>
    </form>
    </div>
</section>
<?php endif; ?>
</div>
