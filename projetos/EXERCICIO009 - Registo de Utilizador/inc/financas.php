<?php
/**
 * Finanças do colégio: dinheiro em cêntimos (inteiros, sem erros de arredondamento), parâmetros, IVA, salários,
 * Segurança Social e resumos. Valores de exemplo, para fins escolares: não substituem um contabilista certificado.
 */

declare(strict_types=1);

if (!defined('APP')) {
    http_response_code(403);
    exit;
}

// ---------- formatação ----------

/** 123456 → "1 234,56 €" (ou "€1,234.56" em inglês). */
function euro(int $cents, bool $sinal = false): string
{
    $negativo = $cents < 0;
    $abs = abs($cents);
    $en = idiomaAtivo() === 'en';
    $txt = number_format($abs / 100, 2, $en ? '.' : ',', $en ? ',' : "\u{00A0}");
    $txt = $en ? '€' . $txt : $txt . "\u{00A0}€";
    return ($negativo ? '−' : ($sinal && $cents > 0 ? '+' : '')) . $txt;
}

/** Texto de um formulário ("1234,56" ou "1 234.56") em cêntimos. false se for inválido. */
function lerDinheiro(mixed $valor, int $maxCents = 100000000): int|false
{
    if (!is_string($valor)) {
        return false;
    }
    $t = str_replace([' ', "\u{00A0}", '€'], '', trim($valor));
    if (preg_match('/^\d{1,3}(\.\d{3})+,\d{1,2}$/', $t) === 1) {   // 1.234,56
        $t = str_replace('.', '', $t);
    }
    $t = str_replace(',', '.', $t);
    if (preg_match('/^\d{1,9}(\.\d{1,2})?$/', $t) !== 1) {
        return false;
    }
    $c = (int) round((float) $t * 100);
    return $c <= $maxCents ? $c : false;
}

/** Percentagem (aceita vírgula). false se não for número entre 0 e $max. */
function lerPercentagem(mixed $valor, float $max = 100.0): float|false
{
    if (!is_string($valor)) {
        return false;
    }
    $t = str_replace(',', '.', trim($valor));
    if ($t === '' || !is_numeric($t)) {
        return false;
    }
    $n = round((float) $t, 2);
    return ($n >= 0 && $n <= $max) ? $n : false;
}

function numCents(int $cents): string
{
    return number_format($cents / 100, 2, '.', '');
}

function nomeMes(string $mes, bool $curto = false): string
{
    [$ano, $m] = array_map('intval', explode('-', $mes) + [0, 0]);
    if ($m < 1 || $m > 12) {
        return $mes;
    }
    return t(($curto ? 'mes_curto_' : 'mes_') . $m) . ($curto ? '' : ' ' . $ano);
}

function mesValido(mixed $v): ?string
{
    return is_string($v) && in_array($v, MESES_LETIVOS, true) ? $v : null;
}

function dataValida(mixed $v, bool $permiteFutura = false): ?string
{
    if (!is_string($v) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) !== 1) {
        return null;
    }
    [$a, $m, $d] = array_map('intval', explode('-', $v));
    if (!checkdate($m, $d, $a) || $a < 2000 || $a > 2100) {
        return null;
    }
    return ($permiteFutura || $v <= hojeISO()) ? $v : null;
}

// ---------- parâmetros ----------

function parametros(): array
{
    static $cache = null;
    if ($cache === null) {
        $cache = PARAMETROS_INICIAIS;
        foreach (bd()->query('SELECT chave, valor FROM parametros')->fetchAll() as $l) {
            $cache[$l['chave']] = $l['valor'];
        }
    }
    return $cache;
}

function parametro(string $chave): float
{
    return (float) (parametros()[$chave] ?? 0);
}

/** Propina mensal de um ano de escolaridade, em cêntimos. */
function propinaDoAno(int $ano): int
{
    return (int) round(parametro('propina_' . $ano) * 100);
}

// ---------- IVA e salários ----------

/** [iva, total] em cêntimos a partir do valor sem IVA. */
function calcularIva(int $base, int $taxa): array
{
    $iva = (int) round($base * $taxa / 100);
    return [$iva, $base + $iva];
}

/** Cálculo de um recibo de vencimento (ilustrativo: 1 pagamento por mês, IRS por taxa fixa). */
function calcularSalario(int $bruto, float $irsTaxa): array
{
    $irs = (int) round($bruto * $irsTaxa / 100);
    $ssTrab = (int) round($bruto * parametro('ss_trab') / 100);
    $ssEnt = (int) round($bruto * parametro('ss_ent') / 100);
    return [
        'bruto' => $bruto, 'irs' => $irs, 'ss_trab' => $ssTrab, 'ss_ent' => $ssEnt,
        'liquido' => $bruto - $irs - $ssTrab, 'custo' => $bruto + $ssEnt,
    ];
}

/** Cria os recibos de vencimento do mês para quem tem contrato e está ativo (não repete). Devolve quantos criou. */
function processarSalarios(PDO $bd, string $mes, ?string $pagoEm = null): int
{
    $contratos = $bd->query(
        'SELECT c.utilizador_id, c.bruto_cents, c.irs_taxa FROM contratos c JOIN utilizadores u ON u.id = c.utilizador_id
          WHERE u.ativo = 1 AND u.perfil IN (' . listaPerfisPessoal() . ') ORDER BY u.id'
    )->fetchAll();
    $existe = $bd->prepare('SELECT 1 FROM salarios WHERE utilizador_id = ? AND mes = ?');
    $novo = $bd->prepare('INSERT INTO salarios (utilizador_id, mes, bruto_cents, irs_cents, ss_trab_cents, ss_ent_cents, liquido_cents, pago_em) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $criados = 0;
    foreach ($contratos as $c) {
        $existe->execute([(int) $c['utilizador_id'], $mes]);
        if ($existe->fetchColumn()) {
            continue;
        }
        $s = calcularSalario((int) $c['bruto_cents'], (float) $c['irs_taxa']);
        $novo->execute([(int) $c['utilizador_id'], $mes, $s['bruto'], $s['irs'], $s['ss_trab'], $s['ss_ent'], $s['liquido'], $pagoEm]);
        $criados++;
    }
    return $criados;
}

// ---------- propinas ----------

/** 'paga', 'atraso' ou 'pendente'. */
function estadoPropina(array $p): string
{
    if (!empty($p['pago_em'])) {
        return 'paga';
    }
    return $p['vencimento'] < hojeISO() ? 'atraso' : 'pendente';
}

function metodosPagamento(): array
{
    return ['multibanco', 'transferencia', 'mbway', 'numerario'];
}

/** Emite as propinas de um mês a todos os alunos ativos que ainda não as têm. Devolve quantas criou. */
function emitirPropinas(PDO $bd, string $mes): int
{
    $dia = max(1, min(28, (int) parametro('dia_vencimento')));
    $venc = $mes . '-' . sprintf('%02d', $dia);
    $alunos = $bd->query('SELECT a.utilizador_id AS id, t.ano FROM alunos a JOIN utilizadores u ON u.id = a.utilizador_id JOIN turmas t ON t.id = a.turma_id WHERE u.ativo = 1')->fetchAll();
    $existe = $bd->prepare('SELECT 1 FROM propinas WHERE aluno_id = ? AND mes = ?');
    $novo = $bd->prepare('INSERT INTO propinas (aluno_id, mes, valor_cents, vencimento) VALUES (?, ?, ?, ?)');
    $criadas = 0;
    foreach ($alunos as $a) {
        $existe->execute([(int) $a['id'], $mes]);
        if (!$existe->fetchColumn()) {
            $novo->execute([(int) $a['id'], $mes, propinaDoAno((int) $a['ano']), $venc]);
            $criadas++;
        }
    }
    return $criadas;
}

// ---------- resumos ----------

/**
 * Números do ano letivo por mês e no total.
 * Receitas = propinas recebidas (pelo mês do pagamento) + outras receitas sem IVA. Despesas = custo do pessoal do mês
 * (bruto + Segurança Social da entidade) + despesas registadas sem IVA. O IVA conta à parte (liquidado − dedutível).
 */
function resumoFinanceiro(): array
{
    $bd = bd();
    $meses = [];
    foreach (MESES_LETIVOS as $m) {
        $meses[$m] = ['propinas' => 0, 'outras_receitas' => 0, 'pessoal' => 0, 'ss_ent' => 0, 'despesas' => 0, 'iva_liq' => 0, 'iva_ded' => 0];
    }
    foreach ($bd->query("SELECT substr(pago_em, 1, 7) AS mes, SUM(valor_cents) AS v FROM propinas WHERE pago_em IS NOT NULL GROUP BY 1")->fetchAll() as $l) {
        if (isset($meses[$l['mes']])) {
            $meses[$l['mes']]['propinas'] = (int) $l['v'];
        }
    }
    foreach ($bd->query('SELECT mes, SUM(bruto_cents) AS b, SUM(ss_ent_cents) AS s FROM salarios GROUP BY mes')->fetchAll() as $l) {
        if (isset($meses[$l['mes']])) {
            $meses[$l['mes']]['pessoal'] = (int) $l['b'];
            $meses[$l['mes']]['ss_ent'] = (int) $l['s'];
        }
    }
    foreach ($bd->query('SELECT substr(data, 1, 7) AS mes, tipo, SUM(base_cents) AS b, SUM(iva_cents) AS i FROM lancamentos GROUP BY 1, 2')->fetchAll() as $l) {
        if (!isset($meses[$l['mes']])) {
            continue;
        }
        if ($l['tipo'] === 'receita') {
            $meses[$l['mes']]['outras_receitas'] = (int) $l['b'];
            $meses[$l['mes']]['iva_liq'] = (int) $l['i'];
        } else {
            $meses[$l['mes']]['despesas'] = (int) $l['b'];
            $meses[$l['mes']]['iva_ded'] = (int) $l['i'];
        }
    }
    $total = ['propinas' => 0, 'outras_receitas' => 0, 'pessoal' => 0, 'ss_ent' => 0, 'despesas' => 0, 'iva_liq' => 0, 'iva_ded' => 0];
    foreach ($meses as $m => &$v) {
        $v['receitas'] = $v['propinas'] + $v['outras_receitas'];
        $v['custos'] = $v['pessoal'] + $v['ss_ent'] + $v['despesas'];
        $v['resultado'] = $v['receitas'] - $v['custos'];
        foreach ($total as $k => $_) {
            $total[$k] += $v[$k];
        }
    }
    unset($v);
    $total['receitas'] = $total['propinas'] + $total['outras_receitas'];
    $total['custos'] = $total['pessoal'] + $total['ss_ent'] + $total['despesas'];
    $total['resultado'] = $total['receitas'] - $total['custos'];
    $total['iva_pagar'] = $total['iva_liq'] - $total['iva_ded'];

    $retencoes = $bd->query('SELECT COALESCE(SUM(irs_cents), 0) AS irs, COALESCE(SUM(ss_trab_cents), 0) AS st, COALESCE(SUM(ss_ent_cents), 0) AS se FROM salarios')->fetch();
    $total['irs'] = (int) $retencoes['irs'];
    $total['ss_trab'] = (int) $retencoes['st'];

    $hoje = hojeISO();
    $st = $bd->prepare("SELECT COUNT(*) AS n, COALESCE(SUM(valor_cents), 0) AS v FROM propinas WHERE pago_em IS NULL AND vencimento < ?");
    $st->execute([$hoje]);
    $atraso = $st->fetch();
    $porPagar = $bd->query('SELECT COALESCE(SUM(valor_cents), 0) FROM propinas WHERE pago_em IS NULL')->fetchColumn();
    $emitido = $bd->query('SELECT COALESCE(SUM(valor_cents), 0) FROM propinas')->fetchColumn();

    // Custos por categoria (para o gráfico de composição)
    $custosCat = [];
    foreach ($bd->query("SELECT categoria, SUM(base_cents) AS v FROM lancamentos WHERE tipo = 'despesa' GROUP BY categoria ORDER BY v DESC")->fetchAll() as $l) {
        $custosCat[$l['categoria']] = (int) $l['v'];
    }

    return [
        'meses' => $meses, 'total' => $total,
        'atraso_n' => (int) $atraso['n'], 'atraso_valor' => (int) $atraso['v'],
        'por_pagar' => (int) $porPagar, 'emitido' => (int) $emitido, 'custos_cat' => $custosCat,
    ];
}

/** Propinas emitidas e cobradas por turma (para o gráfico). */
function propinasPorTurma(): array
{
    return bd()->query(
        'SELECT t.nome AS turma, SUM(p.valor_cents) AS emitido, SUM(CASE WHEN p.pago_em IS NOT NULL THEN p.valor_cents ELSE 0 END) AS pago
           FROM propinas p JOIN alunos a ON a.utilizador_id = p.aluno_id JOIN turmas t ON t.id = a.turma_id
          GROUP BY t.id ORDER BY t.ano, t.nome'
    )->fetchAll();
}

function nomeCategoria(string $cat): string
{
    return t('cat_' . $cat);
}

/** 'a', 'b', ... entre aspas para um IN (...) (os perfis vêm da configuração, não do utilizador). */
function listaPerfisPessoal(): string
{
    return "'" . implode("', '", PERFIS_PESSOAL) . "'";
}

/** Só a contabilidade altera dados financeiros. A direção e o Conselho Geral consultam; a secretaria só vê propinas. */
function ehContabilista(): bool
{
    return temPerfil('contabilidade');
}

/** Texto por baixo do nome: a área (cantina, limpeza…) para o pessoal não docente, ou o perfil. */
function rotuloPessoa(array $p): string
{
    return !empty($p['area']) ? t('area_' . $p['area']) : t('perfil_' . $p['perfil']);
}

/** Todo o pessoal ativo com o contrato (ou sem ele), para a página de salários. */
function pessoalComContrato(): array
{
    return bd()->query(
        "SELECT u.id, u.nome, u.perfil, u.ativo, f.area, COALESCE(NULLIF(c.cargo, ''), f.cargo) AS cargo, c.bruto_cents, c.irs_taxa
           FROM utilizadores u LEFT JOIN contratos c ON c.utilizador_id = u.id LEFT JOIN funcionarios f ON f.utilizador_id = u.id
          WHERE u.perfil IN (" . listaPerfisPessoal() . ") AND u.ativo = 1
          ORDER BY CASE u.perfil WHEN 'direcao' THEN 0 WHEN 'contabilidade' THEN 1 WHEN 'secretaria' THEN 2 WHEN 'professor' THEN 3 ELSE 4 END, f.area, u.nome"
    )->fetchAll();
}
