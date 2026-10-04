<?php
/** Relatórios financeiros em Excel (e as listas que também servem ao PDF): resumo, propinas, salários, Segurança Social e impostos, movimentos e custos. */

declare(strict_types=1);

if (!defined('APP')) {
    http_response_code(403);
    exit;
}

const RELATORIOS_FIN = ['tudo', 'resumo', 'propinas', 'salarios', 'seguranca', 'lancamentos', 'custos', 'alteracoes'];

function dinheiroCelula(int $cents, bool $negrito = false): array
{
    return [round($cents / 100, 2), $negrito ? Xlsx::DINHEIRO_NEGRITO : Xlsx::DINHEIRO];
}

function cabecalhoCelulas(array $titulos): array
{
    return array_map(static fn ($t) => [$t, Xlsx::CABECALHO], $titulos);
}

function folhaResumo(Xlsx $x, array $r): void
{
    $linhas = [
        [[t('rel_resumo_titulo') . ' · ' . t('ano_letivo', ANO_LETIVO), Xlsx::TITULO]],
        [[t('gerado_em', dataFmt(hojeISO())), Xlsx::NORMAL]],
        [],
        cabecalhoCelulas([t('mes'), t('propinas_recebidas'), t('outras_receitas'), t('total_receitas'), t('salarios_brutos'), t('ss_entidade'), t('outras_despesas'), t('total_custos'), t('resultado_fin'), t('iva_liquidado'), t('iva_dedutivel')]),
    ];
    foreach ($r['meses'] as $mes => $v) {
        $linhas[] = [nomeMes($mes), dinheiroCelula($v['propinas']), dinheiroCelula($v['outras_receitas']), dinheiroCelula($v['receitas']), dinheiroCelula($v['pessoal']),
            dinheiroCelula($v['ss_ent']), dinheiroCelula($v['despesas']), dinheiroCelula($v['custos']), dinheiroCelula($v['resultado']), dinheiroCelula($v['iva_liq']), dinheiroCelula($v['iva_ded'])];
    }
    $T = $r['total'];
    $linhas[] = [[t('total'), Xlsx::NEGRITO], dinheiroCelula($T['propinas'], true), dinheiroCelula($T['outras_receitas'], true), dinheiroCelula($T['receitas'], true), dinheiroCelula($T['pessoal'], true),
        dinheiroCelula($T['ss_ent'], true), dinheiroCelula($T['despesas'], true), dinheiroCelula($T['custos'], true), dinheiroCelula($T['resultado'], true), dinheiroCelula($T['iva_liq'], true), dinheiroCelula($T['iva_ded'], true)];
    $linhas[] = [];
    $linhas[] = [[t('nota_resumo_fin'), Xlsx::NORMAL]];
    $x->folha(t('menu_financeiro'), $linhas);
}

function folhaPropinas(Xlsx $x): void
{
    $linhas = [cabecalhoCelulas([t('aluno'), t('turma'), t('numero_abrev'), t('mes'), t('valor'), t('vencimento'), t('estado'), t('pago_em'), t('metodo')])];
    $st = bd()->query(
        'SELECT u.nome, t.nome AS turma, a.numero, p.mes, p.valor_cents, p.vencimento, p.pago_em, p.metodo
           FROM propinas p JOIN utilizadores u ON u.id = p.aluno_id JOIN alunos a ON a.utilizador_id = u.id JOIN turmas t ON t.id = a.turma_id
          ORDER BY p.mes, t.ano, t.nome, a.numero'
    );
    $totalEmitido = 0;
    $totalPago = 0;
    foreach ($st->fetchAll() as $p) {
        $estado = estadoPropina($p);
        $totalEmitido += (int) $p['valor_cents'];
        $totalPago += $p['pago_em'] ? (int) $p['valor_cents'] : 0;
        $linhas[] = [$p['nome'], $p['turma'], (int) $p['numero'], nomeMes($p['mes']), dinheiroCelula((int) $p['valor_cents']), dataFmt($p['vencimento']),
            t('estado_' . $estado), $p['pago_em'] ? dataFmt($p['pago_em']) : '', $p['metodo'] ? t('metodo_' . $p['metodo']) : ''];
    }
    $linhas[] = [];
    $linhas[] = [[t('total_emitido'), Xlsx::NEGRITO], '', '', '', dinheiroCelula($totalEmitido, true)];
    $linhas[] = [[t('total_recebido'), Xlsx::NEGRITO], '', '', '', dinheiroCelula($totalPago, true)];
    $linhas[] = [[t('total_por_receber'), Xlsx::NEGRITO], '', '', '', dinheiroCelula($totalEmitido - $totalPago, true)];
    $x->folha(t('menu_propinas'), $linhas);
}

function folhaSalarios(Xlsx $x): void
{
    $linhas = [cabecalhoCelulas([t('mes'), t('nome'), t('cargo'), t('bruto'), t('irs_retido'), t('ss_trabalhador'), t('liquido'), t('ss_entidade'), t('custo_total'), t('estado'), t('pago_em')])];
    $st = bd()->query(
        'SELECT s.*, u.nome, COALESCE(NULLIF(c.cargo, \'\'), f.cargo, u.perfil) AS cargo
           FROM salarios s JOIN utilizadores u ON u.id = s.utilizador_id LEFT JOIN contratos c ON c.utilizador_id = s.utilizador_id
           LEFT JOIN funcionarios f ON f.utilizador_id = s.utilizador_id
          ORDER BY s.mes, u.nome'
    );
    $tot = ['b' => 0, 'i' => 0, 'st' => 0, 'l' => 0, 'se' => 0];
    foreach ($st->fetchAll() as $s) {
        $tot['b'] += (int) $s['bruto_cents'];
        $tot['i'] += (int) $s['irs_cents'];
        $tot['st'] += (int) $s['ss_trab_cents'];
        $tot['l'] += (int) $s['liquido_cents'];
        $tot['se'] += (int) $s['ss_ent_cents'];
        $linhas[] = [nomeMes($s['mes']), $s['nome'], $s['cargo'], dinheiroCelula((int) $s['bruto_cents']), dinheiroCelula((int) $s['irs_cents']), dinheiroCelula((int) $s['ss_trab_cents']),
            dinheiroCelula((int) $s['liquido_cents']), dinheiroCelula((int) $s['ss_ent_cents']), dinheiroCelula((int) $s['bruto_cents'] + (int) $s['ss_ent_cents']),
            $s['pago_em'] ? t('pago') : t('por_pagar'), $s['pago_em'] ? dataFmt($s['pago_em']) : ''];
    }
    $linhas[] = [];
    $linhas[] = [[t('total'), Xlsx::NEGRITO], '', '', dinheiroCelula($tot['b'], true), dinheiroCelula($tot['i'], true), dinheiroCelula($tot['st'], true), dinheiroCelula($tot['l'], true), dinheiroCelula($tot['se'], true), dinheiroCelula($tot['b'] + $tot['se'], true)];
    $linhas[] = [];
    $linhas[] = [[t('nota_salarios'), Xlsx::NORMAL]];
    $x->folha(t('menu_salarios'), $linhas);
}

function folhaSeguranca(Xlsx $x, array $r): void
{
    $linhas = [
        [[t('rel_seguranca_titulo'), Xlsx::TITULO]],
        [t('taxa_ss_trab') . ':', [parametro('ss_trab') / 100, Xlsx::PERCENT], '', t('taxa_ss_ent') . ':', [parametro('ss_ent') / 100, Xlsx::PERCENT]],
        [],
        cabecalhoCelulas([t('mes'), t('salarios_brutos'), t('ss_trabalhador'), t('ss_entidade'), t('ss_total'), t('irs_retido'), t('total_entregar_estado')]),
    ];
    $porMes = [];
    foreach (bd()->query('SELECT mes, SUM(bruto_cents) AS b, SUM(ss_trab_cents) AS st, SUM(ss_ent_cents) AS se, SUM(irs_cents) AS i FROM salarios GROUP BY mes ORDER BY mes')->fetchAll() as $l) {
        $porMes[$l['mes']] = $l;
    }
    $t = ['b' => 0, 'st' => 0, 'se' => 0, 'i' => 0];
    foreach ($porMes as $mes => $l) {
        foreach ($t as $k => $_) {
            $t[$k] += (int) $l[$k];
        }
        $ss = (int) $l['st'] + (int) $l['se'];
        $linhas[] = [nomeMes($mes), dinheiroCelula((int) $l['b']), dinheiroCelula((int) $l['st']), dinheiroCelula((int) $l['se']), dinheiroCelula($ss), dinheiroCelula((int) $l['i']), dinheiroCelula($ss + (int) $l['i'])];
    }
    $linhas[] = [[t('total'), Xlsx::NEGRITO], dinheiroCelula($t['b'], true), dinheiroCelula($t['st'], true), dinheiroCelula($t['se'], true), dinheiroCelula($t['st'] + $t['se'], true), dinheiroCelula($t['i'], true), dinheiroCelula($t['st'] + $t['se'] + $t['i'], true)];
    $linhas[] = [];
    $linhas[] = [[t('iva_titulo'), Xlsx::NEGRITO]];
    $linhas[] = cabecalhoCelulas([t('mes'), t('iva_liquidado'), t('iva_dedutivel'), t('iva_a_entregar')]);
    foreach ($r['meses'] as $mes => $v) {
        if ($v['iva_liq'] || $v['iva_ded']) {
            $linhas[] = [nomeMes($mes), dinheiroCelula($v['iva_liq']), dinheiroCelula($v['iva_ded']), dinheiroCelula($v['iva_liq'] - $v['iva_ded'])];
        }
    }
    $T = $r['total'];
    $linhas[] = [[t('total'), Xlsx::NEGRITO], dinheiroCelula($T['iva_liq'], true), dinheiroCelula($T['iva_ded'], true), dinheiroCelula($T['iva_pagar'], true)];
    $linhas[] = [];
    $linhas[] = [[t('nota_seguranca'), Xlsx::NORMAL]];
    $x->folha(t('rel_seguranca_folha'), $linhas);
}

function folhaLancamentos(Xlsx $x): void
{
    $linhas = [cabecalhoCelulas([t('data'), t('tipo'), t('categoria'), t('descricao'), t('valor_sem_iva'), t('taxa_iva'), t('iva'), t('total')])];
    $tot = ['receita' => [0, 0, 0], 'despesa' => [0, 0, 0]];
    foreach (bd()->query('SELECT * FROM lancamentos ORDER BY data, id')->fetchAll() as $l) {
        $tot[$l['tipo']][0] += (int) $l['base_cents'];
        $tot[$l['tipo']][1] += (int) $l['iva_cents'];
        $tot[$l['tipo']][2] += (int) $l['total_cents'];
        $linhas[] = [dataFmt($l['data']), t('tipo_' . $l['tipo']), nomeCategoria($l['categoria']), $l['descricao'], dinheiroCelula((int) $l['base_cents']),
            [(int) $l['iva_taxa'] / 100, Xlsx::PERCENT], dinheiroCelula((int) $l['iva_cents']), dinheiroCelula((int) $l['total_cents'])];
    }
    $linhas[] = [];
    foreach (['receita' => 'total_receitas', 'despesa' => 'total_despesas'] as $tipo => $chave) {
        $linhas[] = [[t($chave), Xlsx::NEGRITO], '', '', '', dinheiroCelula($tot[$tipo][0], true), '', dinheiroCelula($tot[$tipo][1], true), dinheiroCelula($tot[$tipo][2], true)];
    }
    $x->folha(t('menu_lancamentos'), $linhas);
}

/** Registo de alterações: quem mudou, anulou ou apagou o quê, quando e porquê. */
function folhaAlteracoes(Xlsx $x): void
{
    $linhas = [cabecalhoCelulas([t('quando'), t('quem'), t('acao'), t('o_que'), t('registo'), t('justificacao')])];
    foreach (bd()->query('SELECT * FROM auditoria ORDER BY quando, id')->fetchAll() as $l) {
        $linhas[] = [dataFmt(substr($l['quando'], 0, 10)) . ' ' . substr($l['quando'], 11, 5), $l['utilizador_nome'], t('acao_' . $l['acao']), t('entidade_' . $l['entidade']), $l['resumo'], $l['motivo']];
    }
    $x->folha(t('menu_alteracoes'), $linhas);
}

function folhaCustos(Xlsx $x, array $r): void
{
    $T = $r['total'];
    $iva = [];
    foreach (bd()->query("SELECT categoria, SUM(iva_cents) AS i, SUM(total_cents) AS t FROM lancamentos WHERE tipo = 'despesa' GROUP BY categoria")->fetchAll() as $l) {
        $iva[$l['categoria']] = [(int) $l['i'], (int) $l['t']];
    }
    $linhas = [
        [[t('rel_custos_titulo'), Xlsx::TITULO]],
        [],
        cabecalhoCelulas([t('categoria'), t('valor_sem_iva'), t('iva'), t('total'), t('peso_custos')]),
    ];
    $custoTotal = max(1, $T['custos']);
    $itens = [[t('salarios_brutos'), $T['pessoal'], 0], [t('ss_entidade'), $T['ss_ent'], 0]];
    foreach ($r['custos_cat'] as $cat => $valor) {
        $itens[] = [nomeCategoria($cat), $valor, $iva[$cat][0] ?? 0];
    }
    foreach ($itens as [$nome, $base, $ivaCents]) {
        $linhas[] = [$nome, dinheiroCelula($base), dinheiroCelula($ivaCents), dinheiroCelula($base + $ivaCents), [$base / $custoTotal, Xlsx::PERCENT]];
    }
    $linhas[] = [[t('total_custos'), Xlsx::NEGRITO], dinheiroCelula($T['custos'], true), dinheiroCelula($T['iva_ded'], true), dinheiroCelula($T['custos'] + $T['iva_ded'], true), [1, Xlsx::PERCENT]];
    $x->folha(t('rel_custos_folha'), $linhas);
}

/** Monta o ficheiro Excel do relatório pedido ('tudo' junta todas as folhas). */
function excelFinanceiro(string $qual): Xlsx
{
    $x = new Xlsx();
    $r = resumoFinanceiro();
    $todas = $qual === 'tudo';
    if ($todas || $qual === 'resumo') {
        folhaResumo($x, $r);
    }
    if ($todas || $qual === 'propinas') {
        folhaPropinas($x);
    }
    if ($todas || $qual === 'salarios') {
        folhaSalarios($x);
    }
    if ($todas || $qual === 'seguranca') {
        folhaSeguranca($x, $r);
    }
    if ($todas || $qual === 'lancamentos') {
        folhaLancamentos($x);
    }
    if ($todas || $qual === 'custos') {
        folhaCustos($x, $r);
    }
    if ($todas || $qual === 'alteracoes') {
        folhaAlteracoes($x);
    }
    return $x;
}
