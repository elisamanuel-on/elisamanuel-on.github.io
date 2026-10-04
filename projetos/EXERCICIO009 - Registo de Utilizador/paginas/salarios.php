<?php
/**
 * Salários. Só a contabilidade define contratos, processa o mês e marca os pagamentos (nunca o seu próprio contrato).
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
        "SELECT s.*, u.nome, u.perfil, COALESCE(NULLIF(c.cargo, ''), f.cargo, u.perfil) AS cargo
           FROM salarios s JOIN utilizadores u ON u.id = s.utilizador_id LEFT JOIN contratos c ON c.utilizador_id = s.utilizador_id
           LEFT JOIN funcionarios f ON f.utilizador_id = s.utilizador_id WHERE s.id = ?"
    );
    $st->execute([$id]);
    return $st->fetch() ?: null;
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
        if (!$alvo || $bruto === false || $bruto <= 0 || $irs === false || $cargo === '') {
            aviso('erro', 'dados_invalidos');
        } elseif (!podeEditarContrato($alvo)) {
            aviso('erro', 'contrato_sem_permissao');
        } else {
            $db->prepare('INSERT INTO contratos (utilizador_id, cargo, bruto_cents, irs_taxa) VALUES (?, ?, ?, ?)
                          ON CONFLICT (utilizador_id) DO UPDATE SET cargo = excluded.cargo, bruto_cents = excluded.bruto_cents, irs_taxa = excluded.irs_taxa')
               ->execute([$id, $cargo, $bruto, $irs]);
            aviso('ok', 'contrato_guardado');
        }
    } elseif ($acao === 'processar') {
        if ($mes === null) {
            aviso('erro', 'dados_invalidos');
        } else {
            $n = processarSalarios($db, $mes);
            aviso($n > 0 ? 'ok' : 'erro', $n > 0 ? 'salarios_processados' : 'salarios_ja_processados', $n);
        }
    } elseif ($acao === 'pagar_todos') {
        if ($mes !== null) {
            $db->prepare('UPDATE salarios SET pago_em = ? WHERE mes = ? AND pago_em IS NULL')->execute([hojeISO(), $mes]);
            aviso('ok', 'salarios_pagos');
        }
    } elseif (in_array($acao, ['pagar', 'anular', 'apagar'], true)) {
        $s = salarioCompleto(inteiro($_POST['id'] ?? ''));
        if (!$s) {
            aviso('erro', 'dados_invalidos');
        } elseif ($acao === 'pagar' && empty($s['pago_em'])) {
            $db->prepare('UPDATE salarios SET pago_em = ? WHERE id = ?')->execute([hojeISO(), (int) $s['id']]);
            aviso('ok', 'salario_pago');
        } elseif ($acao === 'anular' && !empty($s['pago_em'])) {
            $db->prepare('UPDATE salarios SET pago_em = NULL WHERE id = ?')->execute([(int) $s['id']]);
            aviso('ok', 'pagamento_anulado');
        } elseif ($acao === 'apagar' && empty($s['pago_em'])) {
            $db->prepare('DELETE FROM salarios WHERE id = ?')->execute([(int) $s['id']]);
            aviso('ok', 'salario_apagado');
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
?>
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
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th><?= e(t('nome')) ?></th><th><?= e(t('cargo')) ?></th><th class="num"><?= e(t('bruto')) ?></th><th class="num"><?= e(t('irs_retido')) ?></th><th class="num"><?= e(t('ss_trabalhador')) ?></th><th class="num"><?= e(t('liquido')) ?></th><th class="num"><?= e(t('ss_entidade')) ?></th><th><?= e(t('estado')) ?></th><th></th></tr></thead>
        <tbody>
        <?php foreach ($recibos as $s): ?>
            <tr><td><?= e($s['nome']) ?><small class="motivo"><?= e(rotuloPessoa($s)) ?></small></td><td class="pequeno"><?= e($s['cargo']) ?></td>
                <td class="num"><?= e(euro((int) $s['bruto_cents'])) ?></td><td class="num"><?= e(euro((int) $s['irs_cents'])) ?></td><td class="num"><?= e(euro((int) $s['ss_trab_cents'])) ?></td>
                <td class="num"><strong><?= e(euro((int) $s['liquido_cents'])) ?></strong></td><td class="num"><?= e(euro((int) $s['ss_ent_cents'])) ?></td>
                <td><span class="etiqueta <?= $s['pago_em'] ? 'pos' : '' ?>"><?= e($s['pago_em'] ? t('pago') : t('por_pagar')) ?></span><?php if ($s['pago_em']): ?><small class="motivo"><?= e(dataFmt($s['pago_em'])) ?></small><?php endif; ?></td>
                <td class="acoes">
                    <a class="botao secundario pequeno" href="<?= e(ligacao('salarios', ['pdf' => $s['id']])) ?>"><?= icone('baixar') ?><span><?= e(t('recibo')) ?></span></a>
                    <?php if ($edita): ?><form method="post" class="form-linha"><?= csrfCampo() ?><input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                    <?php if ($s['pago_em']): ?>
                        <button type="submit" name="acao" value="anular" class="botao secundario pequeno" data-confirmar-botao="<?= e(t('confirmar_anular_pagamento')) ?>"><?= e(t('anular_pagamento')) ?></button>
                    <?php else: ?>
                        <button type="submit" name="acao" value="pagar" class="botao pequeno"><?= e(t('marcar_pago')) ?></button>
                        <button type="submit" name="acao" value="apagar" class="icone-botao perigo pequeno" title="<?= e(t('apagar')) ?>" aria-label="<?= e(t('apagar')) ?>" data-confirmar-botao="<?= e(t('confirmar_apagar_salario')) ?>"><?= icone('lixo') ?></button>
                    <?php endif; ?>
                    </form><?php endif; ?></td></tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr><td colspan="2"><?= e(t('total')) ?></td><td class="num"><?= e(euro($tot['b'])) ?></td><td class="num"><?= e(euro($tot['i'])) ?></td><td class="num"><?= e(euro($tot['st'])) ?></td><td class="num"><?= e(euro($tot['l'])) ?></td><td class="num"><?= e(euro($tot['se'])) ?></td><td colspan="2"></td></tr></tfoot>
    </table></div>
    <?php if ($porPagar > 0 && $edita): ?>
        <form method="post" class="barra-acoes"><?= csrfCampo() ?><input type="hidden" name="acao" value="pagar_todos"><input type="hidden" name="mes" value="<?= e($mesVer) ?>">
            <button type="submit" class="botao verde" data-confirmar-botao="<?= e(t('confirmar_pagar_todos')) ?>"><?= e(t('marcar_todos_pagos', $porPagar)) ?></button></form>
    <?php endif; ?>
    <?php endif; ?>
</section>

<div class="duas-colunas<?= $edita ? ' largo-esq' : '' ?>">
<section class="cartao">
    <h2><?= e(t('contratos')) ?></h2>
    <p class="suave pequeno"><?= e(t('ajuda_contratos')) ?></p>
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th><?= e(t('nome')) ?></th><th><?= e(t('cargo')) ?></th><th class="num"><?= e(t('bruto')) ?> (€)</th><th class="num"><?= e(t('taxa_irs')) ?> (%)</th><th class="num"><?= e(t('liquido')) ?></th><th></th></tr></thead>
        <tbody>
        <?php foreach ($pessoal as $p): $pode = podeEditarContrato($p); $fid = 'c' . (int) $p['id'];
            $calc = $p['bruto_cents'] !== null ? calcularSalario((int) $p['bruto_cents'], (float) $p['irs_taxa']) : null; ?>
            <tr><td><?= e($p['nome']) ?><small class="motivo"><?= e(rotuloPessoa($p)) ?></small></td>
            <?php if ($pode): ?>
                <td><input form="<?= e($fid) ?>" type="text" name="cargo" value="<?= e($p['cargo'] ?? rotuloPessoa($p)) ?>" maxlength="40" required aria-label="<?= e(t('cargo')) ?>"></td>
                <td class="num"><input form="<?= e($fid) ?>" type="text" inputmode="decimal" name="bruto" value="<?= e($p['bruto_cents'] !== null ? numCents((int) $p['bruto_cents']) : '') ?>" maxlength="12" required aria-label="<?= e(t('bruto')) ?>"></td>
                <td class="num"><input form="<?= e($fid) ?>" type="text" inputmode="decimal" name="irs" value="<?= e($p['irs_taxa'] !== null ? rtrim(rtrim(number_format((float) $p['irs_taxa'], 2, '.', ''), '0'), '.') : '15') ?>" maxlength="5" required aria-label="<?= e(t('taxa_irs')) ?>"></td>
            <?php else: ?>
                <td class="pequeno"><?= e($p['cargo'] ?? '—') ?></td>
                <td class="num"><?= e($p['bruto_cents'] !== null ? euro((int) $p['bruto_cents']) : '—') ?></td>
                <td class="num"><?= e($p['irs_taxa'] !== null ? numFmt((float) $p['irs_taxa'], 1) . '%' : '—') ?></td>
            <?php endif; ?>
                <td class="num"><?= e($calc ? euro($calc['liquido']) : '—') ?></td>
                <td class="acoes"><?php if ($pode): ?><form id="<?= e($fid) ?>" method="post" class="form-linha"><?= csrfCampo() ?><input type="hidden" name="acao" value="contrato"><input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                    <button type="submit" class="botao secundario pequeno"><?= e(t('guardar')) ?></button></form>
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
</section>
<?php endif; ?>
</div>
