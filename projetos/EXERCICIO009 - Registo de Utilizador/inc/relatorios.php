<?php
/**
 * Relatórios (pautas livres): o professor ou formador preenche com os seus alunos, colunas e notas, e descarrega em PDF.
 * Cada relatório guarda as colunas [{titulo, peso}] e as linhas [{nome, numero, notas: [valor|null, …]}] em JSON.
 */

declare(strict_types=1);

if (!defined('APP')) {
    http_response_code(403);
    exit;
}

/** Relatório com os campos já prontos a usar (JSON descodificado). Só o dono o pode abrir. */
function relatorioPorId(int $id): ?array
{
    $st = bd()->prepare('SELECT * FROM relatorios WHERE id = ? AND professor_id = ?');
    $st->execute([$id, (int) utilizador()['id']]);
    $r = $st->fetch();
    if (!$r) {
        return null;
    }
    $r['colunas'] = json_decode((string) $r['colunas'], true) ?: [];
    $r['linhas'] = json_decode((string) $r['linhas'], true) ?: [];
    return $r;
}

function relatorioEmBranco(): array
{
    $linhas = [];
    for ($i = 1; $i <= 5; $i++) {
        $linhas[] = ['nome' => '', 'numero' => (string) $i, 'notas' => [null, null]];
    }
    return [
        'id'            => 0,
        'titulo'        => t('pauta_titulo'),
        'instituicao'   => t('escola'),
        'curso'         => '',
        'disciplina'    => '',
        'formador'      => utilizador()['nome'],
        'data'          => hojeISO(),
        'periodo_texto' => '',
        'observacoes'   => '',
        'colunas'       => [['titulo' => t('avaliacao') . ' 1', 'peso' => 50], ['titulo' => t('avaliacao') . ' 2', 'peso' => 50]],
        'linhas'        => $linhas,
    ];
}

/** Cria um relatório a partir das avaliações e notas lançadas numa turma, disciplina e período. */
function relatorioDaTurma(int $turmaId, int $discId, int $periodo): array
{
    $pauta = pautaDe($turmaId, $discId, $periodo);
    $turma = turmaPorId($turmaId);
    $disc = disciplinaPorId($discId);
    $r = relatorioEmBranco();
    $r['curso'] = $turma['nome'] ?? '';
    $r['disciplina'] = $disc ? nomeDisciplina($disc) : '';
    $r['periodo_texto'] = t('periodo_n', $periodo) . ' · ' . ANO_LETIVO;
    $r['colunas'] = [];
    foreach (array_slice($pauta['avaliacoes'], 0, MAX_RELATORIO_COLUNAS) as $a) {
        $r['colunas'][] = ['titulo' => mb_substr((string) $a['titulo'], 0, 30), 'peso' => (int) $a['peso']];
    }
    $r['linhas'] = [];
    foreach (array_slice($pauta['linhas'], 0, MAX_RELATORIO_LINHAS) as $l) {
        $notas = [];
        foreach (array_slice($pauta['avaliacoes'], 0, MAX_RELATORIO_COLUNAS) as $a) {
            $notas[] = $l['notas'][(int) $a['id']];
        }
        $r['linhas'][] = ['nome' => $l['aluno']['nome'], 'numero' => (string) $l['aluno']['numero'], 'notas' => $notas];
    }
    return $r;
}

/**
 * Lê o formulário do editor. Devolve [relatório, erro]: erro é a chave de um texto ou null.
 * Mesmo com erro devolve o relatório lido, para o formulário voltar a aparecer como estava.
 */
function relatorioLerFormulario(array $post): array
{
    $texto = static fn (string $campo, int $max): string => limpar($post[$campo] ?? '', $max);
    $erro = null;

    $data = is_string($post['data'] ?? null) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $post['data']) === 1 && strtotime($post['data']) !== false ? $post['data'] : hojeISO();
    $r = [
        'id'            => inteiro($post['id'] ?? ''),
        'titulo'        => $texto('titulo', 80),
        'instituicao'   => $texto('instituicao', 80),
        'curso'         => $texto('curso', 80),
        'disciplina'    => $texto('disciplina', 80),
        'formador'      => $texto('formador', 80),
        'data'          => $data,
        'periodo_texto' => $texto('periodo_texto', 40),
        'observacoes'   => limparTexto($post['observacoes'] ?? '', 600),
        'colunas'       => [],
        'linhas'        => [],
    ];
    if ($r['titulo'] === '') {
        $erro = 'relatorio_titulo_vazio';
    }

    $titulos = is_array($post['col_titulo'] ?? null) ? array_values($post['col_titulo']) : [];
    $pesos = is_array($post['col_peso'] ?? null) ? array_values($post['col_peso']) : [];
    $nCol = min(count($titulos), MAX_RELATORIO_COLUNAS);
    for ($j = 0; $j < $nCol; $j++) {
        $peso = is_string($pesos[$j] ?? null) && ctype_digit(trim($pesos[$j])) ? (int) trim($pesos[$j]) : 1;
        $r['colunas'][] = [
            'titulo' => limpar($titulos[$j] ?? '', 30) ?: t('avaliacao') . ' ' . ($j + 1),
            'peso'   => max(1, min(100, $peso)),
        ];
    }

    $nomes = is_array($post['aluno_nome'] ?? null) ? array_values($post['aluno_nome']) : [];
    $numeros = is_array($post['aluno_numero'] ?? null) ? array_values($post['aluno_numero']) : [];
    $notas = is_array($post['nota'] ?? null) ? array_values($post['nota']) : [];
    $invalidas = 0;
    $nomesVazios = false;
    for ($i = 0; $i < min(count($nomes), MAX_RELATORIO_LINHAS); $i++) {
        $nome = limpar($nomes[$i] ?? '', 80);
        $linhaNotas = [];
        $temNota = false;
        $ficheiro = is_array($notas[$i] ?? null) ? array_values($notas[$i]) : [];
        for ($j = 0; $j < $nCol; $j++) {
            $bruto = $ficheiro[$j] ?? '';
            $v = lerNota($bruto);
            if ($v === false) {
                $invalidas++;
                $linhaNotas[] = null;
                continue;
            }
            $linhaNotas[] = $v;
            $temNota = $temNota || $v !== null;
        }
        $numero = limpar($numeros[$i] ?? '', 6);
        if ($nome === '' && !$temNota && $numero === '') {
            continue; // linha totalmente vazia: ignora
        }
        if ($nome === '') {
            $nomesVazios = true;
        }
        $r['linhas'][] = ['nome' => $nome, 'numero' => $numero, 'notas' => $linhaNotas];
    }
    if ($erro === null && $nomesVazios) {
        $erro = 'relatorio_nome_vazio';
    }
    if ($erro === null && $invalidas > 0) {
        $erro = 'notas_invalidas';
        $r['_invalidas'] = $invalidas;
    }
    return [$r, $erro];
}

/** Guarda (cria ou atualiza) e devolve o id. Só o dono pode atualizar. */
function relatorioGuardar(array $r): int
{
    $agora = date('Y-m-d H:i:s');
    $db = bd();
    $dono = (int) utilizador()['id'];
    $colunas = json_encode($r['colunas'], JSON_UNESCAPED_UNICODE);
    $linhas = json_encode($r['linhas'], JSON_UNESCAPED_UNICODE);
    $campos = [$r['titulo'], $r['instituicao'], $r['curso'], $r['disciplina'], $r['formador'], $r['data'], $r['periodo_texto'], $r['observacoes'], $colunas, $linhas];

    if ((int) $r['id'] > 0) {
        $st = $db->prepare(
            'UPDATE relatorios SET titulo = ?, instituicao = ?, curso = ?, disciplina = ?, formador = ?, data = ?, periodo_texto = ?, observacoes = ?, colunas = ?, linhas = ?, atualizado_em = ?
              WHERE id = ? AND professor_id = ?'
        );
        $st->execute([...$campos, $agora, (int) $r['id'], $dono]);
        if ($st->rowCount() > 0) {
            return (int) $r['id'];
        }
    }
    $st = $db->prepare(
        'INSERT INTO relatorios (titulo, instituicao, curso, disciplina, formador, data, periodo_texto, observacoes, colunas, linhas, professor_id, criado_em, atualizado_em)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $st->execute([...$campos, $dono, $agora, $agora]);
    return (int) $db->lastInsertId();
}

/** Média ponderada, nota final e resultado de uma linha do relatório (só entram as notas preenchidas). */
function relatorioCalcularLinha(array $colunas, array $linha): array
{
    $pares = [];
    foreach ($colunas as $j => $c) {
        $v = $linha['notas'][$j] ?? null;
        if ($v !== null) {
            $pares[] = [(float) $v, (int) $c['peso']];
        }
    }
    $media = mediaPonderada($pares);
    return ['media' => $media, 'final' => notaFinal($media)];
}
