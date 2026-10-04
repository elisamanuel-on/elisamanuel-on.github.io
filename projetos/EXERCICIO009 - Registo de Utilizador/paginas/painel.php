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
