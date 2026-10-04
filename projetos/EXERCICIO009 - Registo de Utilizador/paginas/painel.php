<?php
/** Painel inicial: resumo diferente para cada perfil. */
if (!defined('APP')) { http_response_code(403); exit; }

$u = utilizador();
$perfil = $u['perfil'];
$titulo = t('ola', explode(' ', $u['nome'])[0]);
$dia = (int) date('N');
$cartoes = [];   // [rótulo, valor, ligação|null]
$avisos = array_slice(avisosParaMim(), 0, 3);
$aulasHoje = [];

// O Conselho Geral tem um painel próprio: quem dirige a escola, o histórico recente e o resultado financeiro
if ($perfil === 'conselho') {
    $db = bd();
    $dirAtual = $db->query("SELECT nome FROM utilizadores WHERE perfil = 'direcao' AND ativo = 1 LIMIT 1")->fetchColumn();
    $res = resumoFinanceiro();
    $recentes = $db->query('SELECT h.*, u.nome AS por FROM historico_direcao h JOIN utilizadores u ON u.id = h.por_id ORDER BY h.criado_em DESC, h.id DESC LIMIT 5')->fetchAll();
    ?>
<section class="kpis">
    <a class="kpi" href="<?= e(ligacao('conselho')) ?>"><span class="kpi-valor kpi-texto"><?= e($dirAtual ?: '—') ?></span><span class="kpi-rotulo"><?= e(t('diretor_em_funcoes')) ?></span></a>
    <a class="kpi" href="<?= e(ligacao('financeiro')) ?>"><span class="kpi-valor"><?= e(euro($res['total']['receitas'])) ?></span><span class="kpi-rotulo"><?= e(t('total_receitas')) ?></span></a>
    <a class="kpi" href="<?= e(ligacao('financeiro')) ?>"><span class="kpi-valor"><?= e(euro($res['total']['custos'])) ?></span><span class="kpi-rotulo"><?= e(t('total_custos')) ?></span></a>
    <a class="kpi <?= $res['atraso_n'] ? 'kpi-aviso' : '' ?>" href="<?= e(ligacao('financeiro')) ?>"><span class="kpi-valor"><?= e(euro($res['atraso_valor'])) ?></span><span class="kpi-rotulo"><?= e(t('valor_em_atraso')) ?></span></a>
</section>
<?php if (!$dirAtual): ?><div class="mensagem erro"><?= icone('alerta') ?><span><?= e(t('sem_direcao_aviso')) ?></span></div><?php endif; ?>
<section class="cartao">
    <h2><?= e(t('historico_recente')) ?></h2>
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th><?= e(t('data')) ?></th><th><?= e(t('acao')) ?></th><th><?= e(t('nome')) ?></th><th><?= e(t('motivo')) ?></th></tr></thead>
        <tbody><?php foreach ($recentes as $h): ?>
            <tr><td><?= e(dataFmt($h['data'])) ?></td><td><span class="etiqueta <?= $h['acao'] === 'nomeacao' ? 'pos' : ($h['acao'] === 'destituicao' ? 'neg' : '') ?>"><?= e(t('hist_' . $h['acao'])) ?></span></td><td><?= e($h['nome']) ?></td><td><?= e($h['motivo'] !== '' ? $h['motivo'] : '—') ?></td></tr>
        <?php endforeach; ?></tbody>
    </table></div>
    <p class="barra-acoes"><a class="botao" href="<?= e(ligacao('conselho')) ?>"><?= icone('conselho') ?><span><?= e(t('menu_conselho')) ?></span></a>
        <a class="botao secundario" href="<?= e(ligacao('financeiro')) ?>"><?= icone('financeiro') ?><span><?= e(t('menu_financeiro')) ?></span></a></p>
</section>
<?php
    return;
}

// A contabilidade vê o essencial do dinheiro e o que há por fazer neste mês
if ($perfil === 'contabilidade') {
    $db = bd();
    $res = resumoFinanceiro();
    $mesAtual = mesValido(substr(hojeISO(), 0, 7)) ?? MESES_LETIVOS[0];
    $st = $db->prepare('SELECT COUNT(*) FROM salarios WHERE mes = ?');
    $st->execute([$mesAtual]);
    $processados = (int) $st->fetchColumn();
    $st = $db->prepare('SELECT COUNT(*) FROM salarios WHERE mes = ? AND pago_em IS NULL');
    $st->execute([$mesAtual]);
    $porPagarSal = (int) $st->fetchColumn();
    $st = $db->prepare('SELECT COUNT(*) FROM propinas WHERE mes = ?');
    $st->execute([$mesAtual]);
    $propinasEmitidas = (int) $st->fetchColumn();
    ?>
<section class="kpis">
    <a class="kpi" href="<?= e(ligacao('financeiro')) ?>"><span class="kpi-valor"><?= e(euro($res['total']['receitas'])) ?></span><span class="kpi-rotulo"><?= e(t('total_receitas')) ?></span></a>
    <a class="kpi" href="<?= e(ligacao('financeiro')) ?>"><span class="kpi-valor"><?= e(euro($res['total']['custos'])) ?></span><span class="kpi-rotulo"><?= e(t('total_custos')) ?></span></a>
    <a class="kpi <?= $res['total']['resultado'] < 0 ? 'kpi-aviso' : '' ?>" href="<?= e(ligacao('financeiro')) ?>"><span class="kpi-valor"><?= e(euro($res['total']['resultado'])) ?></span><span class="kpi-rotulo"><?= e(t('resultado_fin')) ?></span></a>
    <a class="kpi <?= $res['atraso_n'] ? 'kpi-aviso' : '' ?>" href="<?= e(ligacao('propinas', ['estado' => 'atraso', 'mes' => ''])) ?>"><span class="kpi-valor"><?= e(euro($res['atraso_valor'])) ?></span><span class="kpi-rotulo"><?= e(t('valor_em_atraso')) ?> (<?= (int) $res['atraso_n'] ?>)</span></a>
</section>
<section class="cartao">
    <h2><?= e(t('por_fazer_mes', nomeMes($mesAtual))) ?></h2>
    <ul class="lista-aulas">
        <li><span><strong><?= e(t('propinas_do_mes')) ?></strong><small><?= e($propinasEmitidas > 0 ? t('propinas_emitidas_n', $propinasEmitidas) : t('propinas_por_emitir')) ?></small></span></li>
        <li><span><strong><?= e(t('salarios_do_mes')) ?></strong><small><?= e($processados === 0 ? t('salarios_por_processar') : t('salarios_processados_n', $processados, $porPagarSal)) ?></small></span></li>
        <li><span><strong><?= e(t('iva_titulo')) ?></strong><small><?= e(($res['total']['iva_pagar'] >= 0 ? t('iva_a_entregar') : t('iva_a_recuperar')) . ': ' . euro(abs($res['total']['iva_pagar']))) ?></small></span></li>
    </ul>
    <p class="barra-acoes">
        <a class="botao" href="<?= e(ligacao('financeiro')) ?>"><?= icone('financeiro') ?><span><?= e(t('menu_financeiro')) ?></span></a>
        <a class="botao secundario" href="<?= e(ligacao('salarios')) ?>"><?= icone('salarios') ?><span><?= e(t('menu_salarios')) ?></span></a>
        <a class="botao secundario" href="<?= e(ligacao('propinas')) ?>"><?= icone('propinas') ?><span><?= e(t('menu_propinas')) ?></span></a>
        <a class="botao secundario" href="<?= e(ligacao('lancamentos')) ?>"><?= icone('lancamentos') ?><span><?= e(t('menu_lancamentos')) ?></span></a>
    </p>
</section>
<?php
    return;
}

// A portaria vê quem está no edifício, as visitas de hoje e a equipa de turno
if ($perfil === 'portaria') {
    $db = bd();
    $dentro = $db->query('SELECT v.*, u.nome AS por FROM visitas v JOIN utilizadores u ON u.id = v.registado_por WHERE v.saida IS NULL ORDER BY v.entrada')->fetchAll();
    $st = $db->prepare("SELECT COUNT(*) FROM visitas WHERE substr(entrada, 1, 10) = ?");
    $st->execute([hojeISO()]);
    $hoje = (int) $st->fetchColumn();
    $agora = date('H:i');
    $deTurno = array_values(array_filter(
        $db->query("SELECT u.nome, f.area, f.cargo, f.turno FROM funcionarios f JOIN utilizadores u ON u.id = f.utilizador_id WHERE u.ativo = 1 ORDER BY f.area, u.nome")->fetchAll(),
        static fn ($f) => $agora >= TURNOS[$f['turno']][0] && $agora < TURNOS[$f['turno']][1]
    ));
    ?>
<section class="kpis kpis-3">
    <a class="kpi" href="<?= e(ligacao('visitas')) ?>"><span class="kpi-valor"><?= $hoje ?></span><span class="kpi-rotulo"><?= e(t('visitas_no_dia', dataFmt(hojeISO()))) ?></span></a>
    <a class="kpi <?= $dentro ? 'kpi-aviso' : '' ?>" href="<?= e(ligacao('visitas')) ?>"><span class="kpi-valor"><?= count($dentro) ?></span><span class="kpi-rotulo"><?= e(t('no_edificio_agora')) ?></span></a>
    <a class="kpi" href="<?= e(ligacao('equipa')) ?>"><span class="kpi-valor"><?= count($deTurno) ?></span><span class="kpi-rotulo"><?= e(t('de_turno_agora')) ?></span></a>
</section>
<div class="duas-colunas">
    <section class="cartao">
        <h2><?= e(t('no_edificio_agora')) ?></h2>
        <?php if (!$dentro): ?><p class="suave"><?= e(t('ninguem_no_edificio')) ?></p><?php else: ?>
        <ul class="lista-aulas"><?php foreach ($dentro as $v): ?>
            <li><span class="hora"><?= e(substr($v['entrada'], 11, 5)) ?></span><span><strong><?= e($v['visitante']) ?></strong><small><?= e(t('motivo_visita_' . $v['motivo'])) ?><?= $v['destino'] !== '' ? ' · ' . e($v['destino']) : '' ?></small></span></li>
        <?php endforeach; ?></ul>
        <?php endif; ?>
        <p><a class="botao" href="<?= e(ligacao('visitas')) ?>"><?= icone('visitas') ?><span><?= e(t('menu_visitas')) ?></span></a></p>
    </section>
    <section class="cartao">
        <h2><?= e(t('de_turno_agora')) ?> · <?= e($agora) ?></h2>
        <?php if (!$deTurno): ?><p class="suave"><?= e(t('ninguem_de_turno')) ?></p><?php else: ?>
        <ul class="lista-aulas"><?php foreach ($deTurno as $f): ?>
            <li><span><strong><?= e($f['nome']) ?></strong><small><?= e(t('area_' . $f['area'])) ?> · <?= e($f['cargo']) ?></small></span></li>
        <?php endforeach; ?></ul>
        <?php endif; ?>
        <p><a href="<?= e(ligacao('equipa')) ?>"><?= e(t('ver_todos')) ?></a></p>
    </section>
</div>
<?php
    return;
}

if ($perfil === 'aluno') {
    $a = alunoPorId((int) $u['id']);
    $medias = mediasDoAluno((int) $u['id']);
    $finais = [];
    foreach ($medias as $porPeriodo) {
        if (isset($porPeriodo[1])) {
            $finais[] = notaFinal($porPeriodo[1]);
        }
    }
    $mediaGeral = $finais ? array_sum($finais) / count($finais) : null;
    $negativas = count(array_filter($finais, static fn ($f) => $f < NOTA_POSITIVA));
    [$totalFaltas, $injust] = totalFaltas((int) $u['id']);
    $cartoes = [
        [t('turma'), $a['turma'], null],
        [t('media_geral_p1'), numFmt($mediaGeral, 1), ligacao('boletim')],
        [t('negativas'), (string) $negativas, ligacao('boletim')],
        [t('faltas_injustificadas'), (string) $injust, ligacao('faltas')],
    ];
    $grelha = horarioDaTurma((int) $a['turma_id']);
    $notasRecentes = bd()->prepare(
        'SELECT av.titulo, av.data, n.valor, d.codigo, d.nome FROM notas n
           JOIN avaliacoes av ON av.id = n.avaliacao_id JOIN disciplinas d ON d.id = av.disciplina_id
          WHERE n.aluno_id = ? ORDER BY av.data DESC, av.id DESC LIMIT 5'
    );
    $notasRecentes->execute([(int) $u['id']]);
    $notasRecentes = $notasRecentes->fetchAll();
} elseif ($perfil === 'professor') {
    $lec = lecionacoesDoProfessor((int) $u['id']);
    $turmasIds = array_unique(array_column($lec, 'turma_id'));
    $nAlunos = 0;
    foreach ($turmasIds as $tid) {
        $nAlunos += count(alunosDaTurma((int) $tid));
    }
    $st = bd()->prepare(
        'SELECT COUNT(*) FROM avaliacoes av JOIN lecionacao l ON l.turma_id = av.turma_id AND l.disciplina_id = av.disciplina_id
          WHERE l.professor_id = ? AND (SELECT COUNT(*) FROM notas n WHERE n.avaliacao_id = av.id) = 0'
    );
    $st->execute([(int) $u['id']]);
    $porLancar = (int) $st->fetchColumn();
    $cartoes = [
        [t('turmas_que_leciona'), (string) count($turmasIds), null],
        [t('alunos'), (string) $nAlunos, null],
        [t('avaliacoes_por_lancar'), (string) $porLancar, ligacao('notas')],
        [t('relatorios_guardados'), (string) (int) bd()->query('SELECT COUNT(*) FROM relatorios WHERE professor_id = ' . (int) $u['id'])->fetchColumn(), ligacao('relatorio')],
    ];
    $grelha = horarioDoProfessor((int) $u['id']);
} else {
    $est = estatisticasEscola();
    $cartoes = [
        [t('alunos'), (string) $est['alunos'], ligacao('alunos')],
        [t('professores'), (string) $est['professores'], ligacao('professores')],
        [t('turmas'), (string) $est['turmas'], ligacao('turmas')],
        [t('faltas_injustificadas'), (string) $est['faltasInjust'], ligacao('faltas')],
    ];
    $grelha = [];
}
$diaAulas = $dia <= 5 ? $dia : 1; // ao fim de semana mostra as aulas de segunda
$aulasHoje = $grelha[$diaAulas] ?? [];
ksort($aulasHoje);
?>
<section class="kpis">
    <?php foreach ($cartoes as [$rotulo, $valor, $url]): ?>
        <?php if ($url): ?><a class="kpi" href="<?= e($url) ?>"><?php else: ?><div class="kpi"><?php endif; ?>
            <span class="kpi-valor"><?= e($valor) ?></span>
            <span class="kpi-rotulo"><?= e($rotulo) ?></span>
        <?php if ($url): ?></a><?php else: ?></div><?php endif; ?>
    <?php endforeach; ?>
</section>

<div class="duas-colunas">
    <section class="cartao">
        <h2><?= e($perfil === 'secretaria' || $perfil === 'direcao' ? t('menu_horario') : ($dia <= 5 ? t('aulas_hoje') : t('proximas_aulas') . ' · ' . t('dia_1'))) ?></h2>
        <?php if ($perfil === 'secretaria' || $perfil === 'direcao'): ?>
            <p class="suave"><?= e(t('ver_horarios_texto')) ?></p>
            <p><a class="botao secundario" href="<?= e(ligacao('horario')) ?>"><?= e(t('menu_horario')) ?></a></p>
        <?php elseif (!$aulasHoje): ?>
            <p class="suave"><?= e(t('sem_aulas_hoje')) ?></p>
        <?php else: ?>
            <ul class="lista-aulas">
                <?php foreach ($aulasHoje as $n => $aula): ?>
                    <li>
                        <span class="hora"><?= e(HORAS_AULA[$n][0]) ?></span>
                        <span><strong><?= e(nomeDisciplina($aula)) ?></strong>
                        <small><?= e(isset($aula['turma']) ? $aula['turma'] . ' · ' : '') ?><?= e(t('sala_' . $aula['sala'])) ?></small></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="cartao">
        <h2><?= e(t('menu_avisos')) ?></h2>
        <?php if (!$avisos): ?>
            <p class="suave"><?= e(t('sem_avisos')) ?></p>
        <?php else: foreach ($avisos as $v): ?>
            <article class="aviso-mini">
                <strong><?= e($v['titulo']) ?></strong>
                <small><?= e(dataFmt(substr($v['criado_em'], 0, 10))) ?> · <?= e($v['autor']) ?></small>
            </article>
        <?php endforeach; endif; ?>
        <p><a href="<?= e(ligacao('avisos')) ?>"><?= e(t('ver_todos')) ?></a></p>
    </section>
</div>

<?php if ($perfil === 'aluno' && !empty($notasRecentes)): ?>
<section class="cartao">
    <h2><?= e(t('notas_recentes')) ?></h2>
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th><?= e(t('disciplina')) ?></th><th><?= e(t('avaliacao')) ?></th><th><?= e(t('data')) ?></th><th class="num"><?= e(t('nota')) ?></th></tr></thead>
        <tbody>
        <?php foreach ($notasRecentes as $n): ?>
            <tr><td><?= e(nomeDisciplina($n)) ?></td><td><?= e($n['titulo']) ?></td><td><?= e(dataFmt($n['data'])) ?></td>
                <td class="num"><span class="nota <?= (float) $n['valor'] >= NOTA_POSITIVA ? 'pos' : 'neg' ?>"><?= e(numFmt((float) $n['valor'], 1)) ?></span></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</section>
<?php endif; ?>

<?php if ($perfil === 'professor'): ?>
<section class="cartao">
    <h2><?= e(t('as_minhas_turmas')) ?></h2>
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th><?= e(t('turma')) ?></th><th><?= e(t('disciplina')) ?></th><th></th></tr></thead>
        <tbody>
        <?php foreach ($lec as $l): ?>
            <tr><td><?= e($l['turma']) ?></td><td><?= e(nomeDisciplina($l)) ?></td>
                <td class="acoes"><a href="<?= e(ligacao('notas', ['t' => $l['turma_id'] . '-' . $l['disciplina_id']])) ?>"><?= e(t('lancar_notas')) ?></a></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</section>
<?php endif; ?>

<?php if ($perfil === 'direcao'): ?>
<p><a class="botao" href="<?= e(ligacao('direcao')) ?>"><?= icone('direcao') ?><span><?= e(t('menu_direcao')) ?></span></a></p>
<?php endif; ?>

<?php
// ----- resumo das finanças de cada perfil -----
if ($perfil === 'aluno'):
    $st = bd()->prepare('SELECT * FROM propinas WHERE aluno_id = ? AND pago_em IS NULL ORDER BY mes LIMIT 1');
    $st->execute([(int) $u['id']]);
    $proxima = $st->fetch();
?>
<section class="cartao">
    <h2><?= e(t('menu_propinas')) ?></h2>
    <?php if ($proxima): $est = estadoPropina($proxima); ?>
        <p><?= e(t('proxima_propina')) ?>: <strong><?= e(nomeMes($proxima['mes'])) ?> · <?= e(euro((int) $proxima['valor_cents'])) ?></strong>
            <span class="etiqueta <?= $est === 'atraso' ? 'neg' : '' ?>"><?= e(t('estado_' . $est)) ?></span><br><span class="suave"><?= e(t('vencimento')) ?>: <?= e(dataFmt($proxima['vencimento'])) ?></span></p>
    <?php else: ?><p class="suave"><?= e(t('propinas_em_dia')) ?></p><?php endif; ?>
    <p><a href="<?= e(ligacao('propinas')) ?>"><?= e(t('ver_propinas')) ?></a></p>
</section>
<?php elseif ($perfil === 'professor'):
    $st = bd()->prepare('SELECT * FROM salarios WHERE utilizador_id = ? ORDER BY mes DESC LIMIT 1');
    $st->execute([(int) $u['id']]);
    $ultimoSal = $st->fetch();
?>
<section class="cartao">
    <h2><?= e(t('menu_salarios')) ?></h2>
    <?php if ($ultimoSal): ?>
        <p><?= e(t('ultimo_liquido')) ?> (<?= e(nomeMes($ultimoSal['mes'])) ?>): <strong><?= e(euro((int) $ultimoSal['liquido_cents'])) ?></strong>
            <span class="etiqueta <?= $ultimoSal['pago_em'] ? 'pos' : '' ?>"><?= e($ultimoSal['pago_em'] ? t('pago') : t('por_pagar')) ?></span></p>
    <?php else: ?><p class="suave"><?= e(t('sem_recibos')) ?></p><?php endif; ?>
    <p><a href="<?= e(ligacao('salarios')) ?>"><?= e(t('os_meus_recibos')) ?></a></p>
</section>
<?php elseif ($perfil === 'secretaria'):
    $resFin = resumoFinanceiro();
    $st = bd()->prepare('SELECT COUNT(*) AS n, COALESCE(SUM(CASE WHEN pago_em IS NOT NULL THEN 1 ELSE 0 END), 0) AS pagos FROM salarios WHERE mes = (SELECT MAX(mes) FROM salarios)');
    $st->execute();
    $eq = $st->fetch();
?>
<section class="cartao">
    <h2><?= e(t('menu_propinas')) ?></h2>
    <dl class="dados-linha">
        <div><dt><?= e(t('propinas_em_atraso')) ?></dt><dd><?= (int) $resFin['atraso_n'] ?></dd></div>
        <div><dt><?= e(t('equipa_ja_paga')) ?></dt><dd><?= (int) $eq['pagos'] ?> / <?= (int) $eq['n'] ?></dd></div>
    </dl>
    <p class="barra-acoes"><a class="botao secundario" href="<?= e(ligacao('propinas')) ?>"><?= icone('propinas') ?><span><?= e(t('menu_propinas')) ?></span></a>
        <a class="botao secundario" href="<?= e(ligacao('salarios')) ?>"><?= icone('salarios') ?><span><?= e(t('menu_salarios')) ?></span></a></p>
</section>
<?php elseif ($perfil === 'direcao'):
    $resFin = resumoFinanceiro();
?>
<section class="cartao">
    <h2><?= e(t('menu_financeiro')) ?></h2>
    <dl class="dados-linha">
        <div><dt><?= e(t('total_receitas')) ?></dt><dd><?= e(euro($resFin['total']['receitas'])) ?></dd></div>
        <div><dt><?= e(t('total_custos')) ?></dt><dd><?= e(euro($resFin['total']['custos'])) ?></dd></div>
        <div><dt><?= e(t('resultado_fin')) ?></dt><dd><?= e(euro($resFin['total']['resultado'])) ?></dd></div>
        <div><dt><?= e(t('propinas_em_atraso')) ?></dt><dd><?= (int) $resFin['atraso_n'] ?> · <?= e(euro($resFin['atraso_valor'])) ?></dd></div>
    </dl>
    <p><a class="botao secundario" href="<?= e(ligacao('financeiro')) ?>"><?= icone('financeiro') ?><span><?= e(t('menu_financeiro')) ?></span></a></p>
</section>
<?php endif; ?>
