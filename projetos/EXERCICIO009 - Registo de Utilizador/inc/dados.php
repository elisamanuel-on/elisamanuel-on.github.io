<?php
/** Consultas e cálculos usados em várias páginas (médias, turmas, lecionação). */

declare(strict_types=1);

if (!defined('APP')) {
    http_response_code(403);
    exit;
}

function turmasTodas(): array
{
    return bd()->query('SELECT id, nome, ano FROM turmas ORDER BY ano, nome')->fetchAll();
}

function turmaPorId(int $id): ?array
{
    $st = bd()->prepare('SELECT id, nome, ano FROM turmas WHERE id = ?');
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

function disciplinasTodas(): array
{
    return bd()->query('SELECT id, codigo, nome FROM disciplinas ORDER BY id')->fetchAll();
}

function disciplinaPorId(int $id): ?array
{
    $st = bd()->prepare('SELECT id, codigo, nome FROM disciplinas WHERE id = ?');
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

/** Nome da disciplina no idioma ativo (a base de dados guarda o nome em português). */
function nomeDisciplina(array $d): string
{
    $chave = 'disc_' . $d['codigo'];
    return existeTexto($chave) ? t($chave) : $d['nome'];
}

/** Turmas e disciplinas que um professor leciona. */
function lecionacoesDoProfessor(int $professorId): array
{
    $st = bd()->prepare(
        'SELECT l.turma_id, t.nome AS turma, l.disciplina_id, d.codigo, d.nome AS disciplina
           FROM lecionacao l
           JOIN turmas t ON t.id = l.turma_id
           JOIN disciplinas d ON d.id = l.disciplina_id
          WHERE l.professor_id = ?
          ORDER BY t.ano, t.nome, d.id'
    );
    $st->execute([$professorId]);
    return $st->fetchAll();
}

function professorLeciona(int $professorId, int $turmaId, int $disciplinaId): bool
{
    $st = bd()->prepare('SELECT 1 FROM lecionacao WHERE professor_id = ? AND turma_id = ? AND disciplina_id = ?');
    $st->execute([$professorId, $turmaId, $disciplinaId]);
    return (bool) $st->fetchColumn();
}

/** Alunos ativos de uma turma, por número. */
function alunosDaTurma(int $turmaId): array
{
    $st = bd()->prepare(
        'SELECT u.id, u.nome, a.numero
           FROM alunos a JOIN utilizadores u ON u.id = a.utilizador_id
          WHERE a.turma_id = ? AND u.ativo = 1
          ORDER BY a.numero'
    );
    $st->execute([$turmaId]);
    return $st->fetchAll();
}

/** Dados de um aluno com a sua turma. */
function alunoPorId(int $id): ?array
{
    $st = bd()->prepare(
        'SELECT u.id, u.nome, u.email, u.ativo, a.numero, a.nascimento, a.turma_id, t.nome AS turma, t.ano
           FROM alunos a JOIN utilizadores u ON u.id = a.utilizador_id JOIN turmas t ON t.id = a.turma_id
          WHERE u.id = ?'
    );
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

/** Média ponderada pelos pesos: lista de [valor, peso]. null se não houver notas. */
function mediaPonderada(array $pares): ?float
{
    $soma = 0.0;
    $pesos = 0;
    foreach ($pares as [$valor, $peso]) {
        $soma += $valor * $peso;
        $pesos += $peso;
    }
    return $pesos > 0 ? $soma / $pesos : null;
}

/** Nota do período arredondada às unidades (a partir de x,5 sobe), como nas pautas. */
function notaFinal(?float $media): ?int
{
    return $media === null ? null : (int) floor($media + 0.5);
}

/**
 * Médias de um aluno: [disciplina_id][periodo] => média (float) ou null.
 * Só entram avaliações com nota lançada.
 */
function mediasDoAluno(int $alunoId): array
{
    $st = bd()->prepare(
        'SELECT a.disciplina_id, a.periodo, a.peso, n.valor
           FROM notas n JOIN avaliacoes a ON a.id = n.avaliacao_id
          WHERE n.aluno_id = ?'
    );
    $st->execute([$alunoId]);
    $grupos = [];
    foreach ($st->fetchAll() as $l) {
        $grupos[(int) $l['disciplina_id']][(int) $l['periodo']][] = [(float) $l['valor'], (int) $l['peso']];
    }
    $medias = [];
    foreach ($grupos as $d => $porPeriodo) {
        foreach ($porPeriodo as $p => $pares) {
            $medias[$d][$p] = mediaPonderada($pares);
        }
    }
    return $medias;
}

/** Faltas de um aluno: [total, injustificadas]. */
function totalFaltas(int $alunoId): array
{
    $st = bd()->prepare('SELECT COUNT(*) AS total, COALESCE(SUM(CASE WHEN justificada = 0 THEN 1 ELSE 0 END), 0) AS injust FROM faltas WHERE aluno_id = ?');
    $st->execute([$alunoId]);
    $l = $st->fetch();
    return [(int) $l['total'], (int) $l['injust']];
}

/** Um professor só vê e mexe nas turmas que leciona; secretaria e direção veem tudo. */
function turmasPermitidas(): array
{
    if (!temPerfil('professor')) {
        return turmasTodas();
    }
    $ids = array_unique(array_column(lecionacoesDoProfessor((int) utilizador()['id']), 'turma_id'));
    return array_values(array_filter(turmasTodas(), static fn ($t) => in_array($t['id'], $ids, false)));
}

function avisosParaMim(): array
{
    $u = utilizador();
    $turmasIds = [];
    if ($u['perfil'] === 'aluno') {
        $a = alunoPorId((int) $u['id']);
        $turmasIds[] = (int) ($a['turma_id'] ?? 0);
    } elseif ($u['perfil'] === 'professor') {
        foreach (lecionacoesDoProfessor((int) $u['id']) as $l) {
            $turmasIds[] = (int) $l['turma_id'];
        }
    }
    $alvos = ['todos', $u['perfil']];
    foreach (array_unique($turmasIds) as $t) {
        $alvos[] = 'turma:' . $t;
    }
    $secretariaVeTudo = in_array($u['perfil'], ['secretaria', 'direcao'], true);
    $st = bd()->query(
        'SELECT v.id, v.titulo, v.texto, v.para, v.criado_em, v.autor_id, u.nome AS autor, u.perfil AS autor_perfil
           FROM avisos v JOIN utilizadores u ON u.id = v.autor_id
          ORDER BY v.criado_em DESC, v.id DESC'
    );
    $lista = [];
    foreach ($st->fetchAll() as $l) {
        if ($secretariaVeTudo || in_array($l['para'], $alvos, true) || (int) $l['autor_id'] === (int) $u['id']) {
            $lista[] = $l;
        }
    }
    return $lista;
}

/** Texto do destinatário de um aviso. */
function destinoAviso(string $para): string
{
    if (str_starts_with($para, 'turma:')) {
        $t = turmaPorId((int) substr($para, 6));
        return $t ? t('para_turma', $t['nome']) : t('para_todos');
    }
    return match ($para) {
        'aluno'      => t('para_alunos'),
        'professor'  => t('para_professores'),
        'secretaria' => t('para_secretaria'),
        default      => t('para_todos'),
    };
}

/** Combinações turma + disciplina que o utilizador pode abrir (o professor só as suas). */
function lecionacoesPermitidas(): array
{
    if (temPerfil('professor')) {
        return lecionacoesDoProfessor((int) utilizador()['id']);
    }
    return bd()->query(
        'SELECT l.turma_id, t.nome AS turma, l.disciplina_id, d.codigo, d.nome AS disciplina
           FROM lecionacao l JOIN turmas t ON t.id = l.turma_id JOIN disciplinas d ON d.id = l.disciplina_id
          ORDER BY t.ano, t.nome, d.id'
    )->fetchAll();
}

function podeAbrirLecionacao(int $turmaId, int $disciplinaId): bool
{
    foreach (lecionacoesPermitidas() as $l) {
        if ((int) $l['turma_id'] === $turmaId && (int) $l['disciplina_id'] === $disciplinaId) {
            return true;
        }
    }
    return false;
}

/** Pauta de uma turma, disciplina e período: avaliações, notas por aluno, médias e resumo. */
function pautaDe(int $turmaId, int $disciplinaId, int $periodo): array
{
    $alunos = alunosDaTurma($turmaId);
    $st = bd()->prepare('SELECT id, titulo, tipo, peso, data FROM avaliacoes WHERE turma_id = ? AND disciplina_id = ? AND periodo = ? ORDER BY data, id');
    $st->execute([$turmaId, $disciplinaId, $periodo]);
    $avaliacoes = $st->fetchAll();

    $st = bd()->prepare(
        'SELECT n.avaliacao_id, n.aluno_id, n.valor
           FROM notas n JOIN avaliacoes a ON a.id = n.avaliacao_id
          WHERE a.turma_id = ? AND a.disciplina_id = ? AND a.periodo = ?'
    );
    $st->execute([$turmaId, $disciplinaId, $periodo]);
    $mapa = [];
    foreach ($st->fetchAll() as $l) {
        $mapa[(int) $l['aluno_id']][(int) $l['avaliacao_id']] = (float) $l['valor'];
    }

    $linhas = [];
    $positivas = $negativas = 0;
    $somaFinais = 0;
    $comNota = 0;
    foreach ($alunos as $a) {
        $notas = [];
        $pares = [];
        foreach ($avaliacoes as $av) {
            $v = $mapa[(int) $a['id']][(int) $av['id']] ?? null;
            $notas[(int) $av['id']] = $v;
            if ($v !== null) {
                $pares[] = [$v, (int) $av['peso']];
            }
        }
        $media = mediaPonderada($pares);
        $final = notaFinal($media);
        if ($final !== null) {
            $comNota++;
            $somaFinais += $final;
            $final >= NOTA_POSITIVA ? $positivas++ : $negativas++;
        }
        $linhas[] = ['aluno' => $a, 'notas' => $notas, 'media' => $media, 'final' => $final];
    }
    return [
        'avaliacoes' => $avaliacoes,
        'linhas'     => $linhas,
        'resumo'     => ['positivas' => $positivas, 'negativas' => $negativas, 'media' => $comNota ? $somaFinais / $comNota : null],
    ];
}

/** Horário de uma turma: [dia][aula] => disciplina e sala. */
function horarioDaTurma(int $turmaId): array
{
    $st = bd()->prepare(
        'SELECT h.dia, h.aula, h.sala, d.codigo, d.nome FROM horarios h JOIN disciplinas d ON d.id = h.disciplina_id WHERE h.turma_id = ?'
    );
    $st->execute([$turmaId]);
    $grelha = [];
    foreach ($st->fetchAll() as $l) {
        $grelha[(int) $l['dia']][(int) $l['aula']] = $l;
    }
    return $grelha;
}

/** Horário de um professor: as aulas das turmas e disciplinas que leciona. */
function horarioDoProfessor(int $professorId): array
{
    $st = bd()->prepare(
        'SELECT h.dia, h.aula, h.sala, d.codigo, d.nome, t.nome AS turma
           FROM lecionacao l
           JOIN horarios h ON h.turma_id = l.turma_id AND h.disciplina_id = l.disciplina_id
           JOIN disciplinas d ON d.id = l.disciplina_id
           JOIN turmas t ON t.id = l.turma_id
          WHERE l.professor_id = ?'
    );
    $st->execute([$professorId]);
    $grelha = [];
    foreach ($st->fetchAll() as $l) {
        $grelha[(int) $l['dia']][(int) $l['aula']] = $l;
    }
    return $grelha;
}

/** Números da escola no 1.º período, para o painel da direção. */
function estatisticasEscola(): array
{
    $db = bd();
    $turmas = turmasTodas();
    $disciplinas = disciplinasTodas();

    $linhas = $db->query(
        'SELECT n.aluno_id, a.turma_id, a.disciplina_id, a.peso, n.valor
           FROM notas n JOIN avaliacoes a ON a.id = n.avaliacao_id
          WHERE a.periodo = 1'
    )->fetchAll();
    $grupos = [];
    foreach ($linhas as $l) {
        $grupos[(int) $l['aluno_id']][(int) $l['disciplina_id']]['pares'][] = [(float) $l['valor'], (int) $l['peso']];
        $grupos[(int) $l['aluno_id']][(int) $l['disciplina_id']]['turma'] = (int) $l['turma_id'];
    }

    $porTurma = [];
    $porDisciplina = [];
    $negativasAluno = [];
    foreach ($grupos as $aluno => $discs) {
        foreach ($discs as $d => $info) {
            $final = notaFinal(mediaPonderada($info['pares']));
            if ($final === null) {
                continue;
            }
            $porTurma[$info['turma']][] = $final;
            $porDisciplina[$d][] = $final;
            if ($final < NOTA_POSITIVA) {
                $negativasAluno[$aluno] = ($negativasAluno[$aluno] ?? 0) + 1;
            }
        }
    }
    $media = static fn (array $v): ?float => $v ? array_sum($v) / count($v) : null;

    $mediasTurma = [];
    foreach ($turmas as $t) {
        $mediasTurma[] = ['rotulo' => $t['nome'], 'valor' => $media($porTurma[$t['id']] ?? [])];
    }
    $mediasDisc = [];
    foreach ($disciplinas as $d) {
        $mediasDisc[] = ['rotulo' => nomeDisciplina($d), 'valor' => $media($porDisciplina[$d['id']] ?? [])];
    }

    $alunosAtivos = $db->query('SELECT u.id, u.nome, a.numero, t.nome AS turma FROM alunos a JOIN utilizadores u ON u.id = a.utilizador_id JOIN turmas t ON t.id = a.turma_id WHERE u.ativo = 1')->fetchAll();
    $injust = [];
    foreach ($db->query('SELECT aluno_id, COUNT(*) AS n FROM faltas WHERE justificada = 0 GROUP BY aluno_id')->fetchAll() as $l) {
        $injust[(int) $l['aluno_id']] = (int) $l['n'];
    }

    $semNegativas = 0;
    $comNegativas = 0;
    $risco = [];
    foreach ($alunosAtivos as $a) {
        $id = (int) $a['id'];
        $neg = $negativasAluno[$id] ?? 0;
        $neg === 0 ? $semNegativas++ : $comNegativas++;
        $f = $injust[$id] ?? 0;
        if ($neg >= 2 || $f >= FALTAS_RISCO) {
            $risco[] = ['nome' => $a['nome'], 'turma' => $a['turma'], 'negativas' => $neg, 'faltas' => $f];
        }
    }
    usort($risco, static fn ($x, $y) => [$y['negativas'], $y['faltas']] <=> [$x['negativas'], $x['faltas']]);

    $contar = static fn (string $sql): int => (int) $db->query($sql)->fetchColumn();
    return [
        'alunos'       => count($alunosAtivos),
        'professores'  => $contar("SELECT COUNT(*) FROM utilizadores WHERE perfil = 'professor' AND ativo = 1"),
        'turmas'       => count($turmas),
        'faltasInjust' => $contar('SELECT COUNT(*) FROM faltas WHERE justificada = 0'),
        'mediaGeral'   => $media(array_merge(...array_values($porTurma ?: [[]]))),
        'mediasTurma'  => $mediasTurma,
        'mediasDisc'   => $mediasDisc,
        'semNegativas' => $semNegativas,
        'comNegativas' => $comNegativas,
        'risco'        => $risco,
    ];
}
