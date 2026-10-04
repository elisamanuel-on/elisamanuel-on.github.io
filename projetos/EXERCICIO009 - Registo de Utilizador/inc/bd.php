<?php
/**
 * Base de dados SQLite (PDO): esquema e dados de demonstração.
 *
 * O ficheiro fica na pasta temporária do servidor, por isso funciona no XAMPP e no Render sem configurar nada.
 * Se o ficheiro desaparecer (por exemplo, o Render gratuito reinicia), é recriado sozinho com os dados de exemplo.
 */

declare(strict_types=1);

if (!defined('APP')) {
    http_response_code(403);
    exit;
}

function caminhoBD(): string
{
    $definido = getenv('COLEGIO_DB');
    if (is_string($definido) && $definido !== '') {
        return $definido;
    }
    return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'colegio_horizonte_v' . VERSAO_BD . '.sqlite';
}

/** Ligação à base de dados (a mesma durante todo o pedido). */
function bd(): PDO
{
    static $ligacao = null;
    if ($ligacao instanceof PDO) {
        return $ligacao;
    }

    $ligacao = new PDO('sqlite:' . caminhoBD(), null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $ligacao->exec('PRAGMA foreign_keys = ON');
    $ligacao->exec('PRAGMA busy_timeout = 5000');

    if (!existeEsquema($ligacao)) {
        $ligacao->exec('BEGIN IMMEDIATE');
        try {
            if (!existeEsquema($ligacao)) { // outro pedido pode ter criado entretanto
                criarEsquema($ligacao);
                semear($ligacao);
            }
            $ligacao->exec('COMMIT');
        } catch (Throwable $erro) {
            $ligacao->exec('ROLLBACK');
            throw $erro;
        }
    }
    return $ligacao;
}

function existeEsquema(PDO $bd): bool
{
    return (bool) $bd->query("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = 'utilizadores'")->fetchColumn();
}

/** Apaga o ficheiro e deixa que o próximo pedido recrie tudo com os dados de exemplo. */
function reporDemonstracao(): void
{
    $ficheiro = caminhoBD();
    foreach ([$ficheiro, $ficheiro . '-wal', $ficheiro . '-shm', $ficheiro . '-journal'] as $f) {
        if (is_file($f)) {
            @unlink($f);
        }
    }
}

function criarEsquema(PDO $bd): void
{
    $bd->exec(<<<'SQL'
CREATE TABLE utilizadores (
    id            INTEGER PRIMARY KEY,
    nome          TEXT NOT NULL,
    email         TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    perfil        TEXT NOT NULL CHECK (perfil IN ('aluno', 'professor', 'secretaria', 'direcao', 'conselho', 'contabilidade', 'portaria', 'funcionario')),
    ativo         INTEGER NOT NULL DEFAULT 1,
    criado_em     TEXT NOT NULL
);
CREATE TABLE turmas (
    id   INTEGER PRIMARY KEY,
    nome TEXT NOT NULL UNIQUE,
    ano  INTEGER NOT NULL
);
CREATE TABLE disciplinas (
    id     INTEGER PRIMARY KEY,
    codigo TEXT NOT NULL UNIQUE,
    nome   TEXT NOT NULL
);
CREATE TABLE alunos (
    utilizador_id INTEGER PRIMARY KEY REFERENCES utilizadores (id) ON DELETE CASCADE,
    turma_id      INTEGER NOT NULL REFERENCES turmas (id),
    numero        INTEGER NOT NULL,
    nascimento    TEXT
);
CREATE TABLE lecionacao (
    turma_id      INTEGER NOT NULL REFERENCES turmas (id) ON DELETE CASCADE,
    disciplina_id INTEGER NOT NULL REFERENCES disciplinas (id),
    professor_id  INTEGER NOT NULL REFERENCES utilizadores (id),
    PRIMARY KEY (turma_id, disciplina_id)
);
CREATE TABLE avaliacoes (
    id            INTEGER PRIMARY KEY,
    turma_id      INTEGER NOT NULL REFERENCES turmas (id) ON DELETE CASCADE,
    disciplina_id INTEGER NOT NULL REFERENCES disciplinas (id),
    periodo       INTEGER NOT NULL CHECK (periodo BETWEEN 1 AND 3),
    titulo        TEXT NOT NULL,
    tipo          TEXT NOT NULL CHECK (tipo IN ('teste', 'trabalho', 'participacao')),
    peso          INTEGER NOT NULL CHECK (peso BETWEEN 1 AND 100),
    data          TEXT NOT NULL,
    criado_por    INTEGER NOT NULL REFERENCES utilizadores (id)
);
CREATE TABLE notas (
    avaliacao_id INTEGER NOT NULL REFERENCES avaliacoes (id) ON DELETE CASCADE,
    aluno_id     INTEGER NOT NULL REFERENCES utilizadores (id) ON DELETE CASCADE,
    valor        REAL NOT NULL CHECK (valor BETWEEN 0 AND 20),
    PRIMARY KEY (avaliacao_id, aluno_id)
);
CREATE TABLE faltas (
    id            INTEGER PRIMARY KEY,
    aluno_id      INTEGER NOT NULL REFERENCES utilizadores (id) ON DELETE CASCADE,
    disciplina_id INTEGER NOT NULL REFERENCES disciplinas (id),
    data          TEXT NOT NULL,
    justificada   INTEGER NOT NULL DEFAULT 0,
    motivo        TEXT,
    registado_por INTEGER NOT NULL REFERENCES utilizadores (id),
    UNIQUE (aluno_id, disciplina_id, data)
);
CREATE TABLE avisos (
    id        INTEGER PRIMARY KEY,
    titulo    TEXT NOT NULL,
    texto     TEXT NOT NULL,
    autor_id  INTEGER NOT NULL REFERENCES utilizadores (id),
    para      TEXT NOT NULL,
    criado_em TEXT NOT NULL
);
CREATE TABLE horarios (
    turma_id      INTEGER NOT NULL REFERENCES turmas (id) ON DELETE CASCADE,
    dia           INTEGER NOT NULL CHECK (dia BETWEEN 1 AND 5),
    aula          INTEGER NOT NULL CHECK (aula BETWEEN 1 AND 6),
    disciplina_id INTEGER NOT NULL REFERENCES disciplinas (id),
    sala          TEXT NOT NULL,
    PRIMARY KEY (turma_id, dia, aula)
);
CREATE TABLE relatorios (
    id            INTEGER PRIMARY KEY,
    professor_id  INTEGER NOT NULL REFERENCES utilizadores (id) ON DELETE CASCADE,
    titulo        TEXT NOT NULL,
    instituicao   TEXT NOT NULL,
    curso         TEXT NOT NULL DEFAULT '',
    disciplina    TEXT NOT NULL DEFAULT '',
    formador      TEXT NOT NULL DEFAULT '',
    data          TEXT NOT NULL,
    periodo_texto TEXT NOT NULL DEFAULT '',
    observacoes   TEXT NOT NULL DEFAULT '',
    colunas       TEXT NOT NULL,
    linhas        TEXT NOT NULL,
    criado_em     TEXT NOT NULL,
    atualizado_em TEXT NOT NULL
);
CREATE TABLE parametros (
    chave TEXT PRIMARY KEY,
    valor TEXT NOT NULL
);
CREATE TABLE propinas (
    id          INTEGER PRIMARY KEY,
    aluno_id    INTEGER NOT NULL REFERENCES utilizadores (id) ON DELETE CASCADE,
    mes         TEXT NOT NULL,                 -- AAAA-MM
    valor_cents INTEGER NOT NULL CHECK (valor_cents >= 0),
    vencimento  TEXT NOT NULL,
    pago_em     TEXT,                          -- data do pagamento (vazio = por pagar)
    metodo      TEXT CHECK (metodo IN ('multibanco', 'transferencia', 'numerario', 'mbway')),
    UNIQUE (aluno_id, mes)
);
CREATE TABLE contratos (
    utilizador_id INTEGER PRIMARY KEY REFERENCES utilizadores (id) ON DELETE CASCADE,
    cargo         TEXT NOT NULL DEFAULT '',
    bruto_cents   INTEGER NOT NULL CHECK (bruto_cents >= 0),
    irs_taxa      REAL NOT NULL CHECK (irs_taxa BETWEEN 0 AND 60)   -- retenção na fonte (%), valor ilustrativo
);
CREATE TABLE salarios (
    id            INTEGER PRIMARY KEY,
    utilizador_id INTEGER NOT NULL REFERENCES utilizadores (id) ON DELETE CASCADE,
    mes           TEXT NOT NULL,
    bruto_cents   INTEGER NOT NULL,
    irs_cents     INTEGER NOT NULL,
    ss_trab_cents INTEGER NOT NULL,
    ss_ent_cents  INTEGER NOT NULL,
    liquido_cents INTEGER NOT NULL,
    pago_em       TEXT,
    UNIQUE (utilizador_id, mes)
);
CREATE TABLE lancamentos (
    id          INTEGER PRIMARY KEY,
    tipo        TEXT NOT NULL CHECK (tipo IN ('despesa', 'receita')),
    categoria   TEXT NOT NULL,
    descricao   TEXT NOT NULL,
    data        TEXT NOT NULL,
    base_cents  INTEGER NOT NULL CHECK (base_cents >= 0),
    iva_taxa    INTEGER NOT NULL CHECK (iva_taxa IN (0, 6, 13, 23)),
    iva_cents   INTEGER NOT NULL,
    total_cents INTEGER NOT NULL,
    criado_por  INTEGER NOT NULL REFERENCES utilizadores (id),
    criado_em   TEXT NOT NULL
);
-- Registo de alterações financeiras: quem mudou ou apagou o quê, quando e porquê (o motivo é obrigatório ao apagar)
CREATE TABLE auditoria (
    id             INTEGER PRIMARY KEY,
    quando         TEXT NOT NULL,
    utilizador_id  INTEGER REFERENCES utilizadores (id) ON DELETE SET NULL,
    utilizador_nome TEXT NOT NULL,
    entidade       TEXT NOT NULL CHECK (entidade IN ('lancamento', 'propina', 'salario')),
    acao           TEXT NOT NULL CHECK (acao IN ('editar', 'apagar', 'anular')),
    resumo         TEXT NOT NULL,
    motivo         TEXT NOT NULL DEFAULT ''
);
CREATE TABLE historico_direcao (
    id            INTEGER PRIMARY KEY,
    utilizador_id INTEGER NOT NULL REFERENCES utilizadores (id),
    nome          TEXT NOT NULL,               -- nome na altura (o histórico não muda se o nome for editado)
    acao          TEXT NOT NULL CHECK (acao IN ('nomeacao', 'destituicao', 'edicao')),
    data          TEXT NOT NULL,
    motivo        TEXT NOT NULL DEFAULT '',
    por_id        INTEGER NOT NULL REFERENCES utilizadores (id),
    criado_em     TEXT NOT NULL
);
CREATE TABLE funcionarios (
    utilizador_id INTEGER PRIMARY KEY REFERENCES utilizadores (id) ON DELETE CASCADE,
    area          TEXT NOT NULL CHECK (area IN ('cantina', 'limpeza', 'vigilancia', 'portaria')),
    cargo         TEXT NOT NULL,
    turno         TEXT NOT NULL CHECK (turno IN ('manha', 'tarde', 'integral')),
    telefone      TEXT NOT NULL DEFAULT ''
);
CREATE TABLE visitas (
    id            INTEGER PRIMARY KEY,
    visitante     TEXT NOT NULL,
    motivo        TEXT NOT NULL,
    destino       TEXT NOT NULL DEFAULT '',
    entrada       TEXT NOT NULL,
    saida         TEXT,
    registado_por INTEGER NOT NULL REFERENCES utilizadores (id)
);
CREATE INDEX idx_visitas_entrada ON visitas (entrada);
CREATE INDEX idx_propinas_mes ON propinas (mes);
CREATE INDEX idx_salarios_mes ON salarios (mes);
CREATE INDEX idx_auditoria_quando ON auditoria (quando);
CREATE INDEX idx_lancamentos_data ON lancamentos (data);
CREATE INDEX idx_relatorios_prof ON relatorios (professor_id);
CREATE INDEX idx_alunos_turma ON alunos (turma_id);
CREATE INDEX idx_avaliacoes ON avaliacoes (turma_id, disciplina_id, periodo);
CREATE INDEX idx_faltas_aluno ON faltas (aluno_id);
SQL);
}

/** Tira acentos para criar emails simples. */
function semAcentos(string $texto): string
{
    $mapa = ['á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'é' => 'e', 'ê' => 'e', 'í' => 'i', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ú' => 'u', 'ç' => 'c',
             'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', 'Ç' => 'c', 'ñ' => 'n'];
    return strtolower(strtr($texto, $mapa));
}

/** Dados de exemplo: turmas, professores, alunos, avaliações, notas, faltas, avisos e horários. */
function semear(PDO $bd): void
{
    mt_srand(2026); // dados sempre iguais, para a demonstração ser previsível
    $hash = password_hash(PASSWORD_DEMO, PASSWORD_DEFAULT);
    $agora = date('Y-m-d H:i:s');

    $novoUtilizador = $bd->prepare('INSERT INTO utilizadores (nome, email, password_hash, perfil, criado_em) VALUES (?, ?, ?, ?, ?)');
    $criar = static function (string $nome, string $email, string $perfil) use ($novoUtilizador, $hash, $agora, $bd): int {
        $novoUtilizador->execute([$nome, $email, $hash, $perfil, $agora]);
        return (int) $bd->lastInsertId();
    };

    // Direção e secretaria
    $direcao = $criar('António Carvalho', CONTAS_DEMO['direcao'], 'direcao');
    $secretaria = $criar('Helena Sousa', CONTAS_DEMO['secretaria'], 'secretaria');
    $conselho = $criar('Conselho Geral', CONTAS_DEMO['conselho'], 'conselho');
    $contabilista = $criar('Teresa Brandão', CONTAS_DEMO['contabilidade'], 'contabilidade');
    $portaria = $criar('Joaquim Pires', CONTAS_DEMO['portaria'], 'portaria');
    // Um diretor anterior (já destituído), para o histórico não começar vazio
    $exDiretor = $criar('Fernando Matos', 'fernando.matos@colegio.demo', 'direcao');
    $bd->prepare('UPDATE utilizadores SET ativo = 0 WHERE id = ?')->execute([$exDiretor]);
    $stHist = $bd->prepare('INSERT INTO historico_direcao (utilizador_id, nome, acao, data, motivo, por_id, criado_em) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stHist->execute([$exDiretor, 'Fernando Matos', 'nomeacao', '2018-09-01', '', $conselho, '2018-09-01 10:00:00']);
    $stHist->execute([$exDiretor, 'Fernando Matos', 'destituicao', '2024-08-30', 'Fim de mandato: o Conselho Geral não renovou o contrato.', $conselho, '2024-08-30 17:30:00']);
    $stHist->execute([$direcao, 'António Carvalho', 'nomeacao', '2024-09-01', '', $conselho, '2024-08-31 11:00:00']);

    // Turmas e disciplinas
    $turmas = [['7.ºA', 7], ['7.ºB', 7], ['8.ºA', 8], ['9.ºA', 9]];
    $idTurma = [];
    $st = $bd->prepare('INSERT INTO turmas (nome, ano) VALUES (?, ?)');
    foreach ($turmas as $i => [$nome, $ano]) {
        $st->execute([$nome, $ano]);
        $idTurma[$i] = (int) $bd->lastInsertId();
    }

    // A ordem importa: é usada para o horário não ter professores em duas turmas ao mesmo tempo
    $disciplinas = [
        ['port', 'Português'], ['mat', 'Matemática'], ['ing', 'Inglês'], ['cn', 'Ciências Naturais'],
        ['hist', 'História'], ['ef', 'Educação Física'], ['geo', 'Geografia'], ['tic', 'TIC'],
    ];
    $idDisc = [];
    $st = $bd->prepare('INSERT INTO disciplinas (codigo, nome) VALUES (?, ?)');
    foreach ($disciplinas as $i => [$codigo, $nome]) {
        $st->execute([$codigo, $nome]);
        $idDisc[$i] = (int) $bd->lastInsertId();
    }

    // Professores (o da demonstração dá Matemática a todas as turmas)
    $professores = [
        ['Ana Duarte', 'ana.duarte@colegio.demo', [0]],
        ['Marta Ribeiro', CONTAS_DEMO['professor'], [1]],
        ['Rui Teixeira', 'rui.teixeira@colegio.demo', [2]],
        ['Sofia Lemos', 'sofia.lemos@colegio.demo', [3, 6]],
        ['Paulo Antunes', 'paulo.antunes@colegio.demo', [4]],
        ['Carla Neves', 'carla.neves@colegio.demo', [5]],
        ['Hugo Faria', 'hugo.faria@colegio.demo', [7]],
    ];
    $professorDe = [];
    foreach ($professores as [$nome, $email, $lista]) {
        $id = $criar($nome, $email, 'professor');
        foreach ($lista as $d) {
            $professorDe[$d] = $id;
        }
    }
    $st = $bd->prepare('INSERT INTO lecionacao (turma_id, disciplina_id, professor_id) VALUES (?, ?, ?)');
    foreach ($idTurma as $t) {
        foreach ($idDisc as $d => $iddisc) {
            $st->execute([$t, $iddisc, $professorDe[$d]]);
        }
    }

    // Alunos: 32, oito por turma
    $proprios = ['Beatriz', 'Tiago', 'Inês', 'Miguel', 'Leonor', 'Rafael', 'Matilde', 'Diogo', 'Carolina', 'Gonçalo', 'Mariana', 'Duarte', 'Sara', 'André', 'Joana', 'Pedro',
                 'Catarina', 'Francisco', 'Rita', 'João', 'Margarida', 'Afonso', 'Lara', 'Martim', 'Bruna', 'Simão', 'Alice', 'Vasco', 'Núria', 'Bernardo', 'Teresa', 'Lucas'];
    $apelidos = ['Almeida', 'Pereira', 'Costa', 'Ferreira', 'Martins', 'Oliveira', 'Santos', 'Rodrigues', 'Gomes', 'Carvalho', 'Mendes', 'Lopes', 'Nunes', 'Pinto', 'Correia', 'Marques',
                 'Teixeira', 'Moreira', 'Cardoso', 'Fonseca', 'Barros', 'Pacheco', 'Ramos', 'Vieira', 'Tavares', 'Reis', 'Cunha', 'Melo', 'Araújo', 'Neves', 'Batista', 'Rocha'];
    $stAluno = $bd->prepare('INSERT INTO alunos (utilizador_id, turma_id, numero, nascimento) VALUES (?, ?, ?, ?)');
    $alunos = []; // [id, índice da turma, nível geral]
    $contagem = [0, 0, 0, 0];
    $emails = [];
    for ($i = 0; $i < 32; $i++) {
        $nome = $proprios[$i] . ' ' . $apelidos[($i * 7 + 3) % 32];
        $email = $i === 0 ? CONTAS_DEMO['aluno'] : semAcentos($proprios[$i] . '.' . $apelidos[($i * 7 + 3) % 32]) . '@colegio.demo';
        if (isset($emails[$email])) {
            $email = str_replace('@', $i . '@', $email);
        }
        $emails[$email] = true;
        $id = $criar($nome, $email, 'aluno');
        $ti = ($i + 2) % 4; // a primeira aluna (conta de demonstração) fica no 8.ºA
        $contagem[$ti]++;
        $ano = $turmas[$ti][1];
        $nasc = sprintf('%d-%02d-%02d', 2026 - 6 - $ano, mt_rand(1, 12), mt_rand(1, 28));
        $stAluno->execute([$id, $idTurma[$ti], $contagem[$ti], $nasc]);
        $nivel = (mt_rand(0, 1000) + mt_rand(0, 1000) + mt_rand(0, 1000)) / 3000 * 9 + 8.5; // 8,5 a 17,5
        $alunos[] = [$id, $ti, $nivel];
    }

    // Avaliações do 1.º período e notas (a "Teste 2" fica por lançar, para o professor usar)
    $stAv = $bd->prepare('INSERT INTO avaliacoes (turma_id, disciplina_id, periodo, titulo, tipo, peso, data, criado_por) VALUES (?, ?, 1, ?, ?, ?, ?, ?)');
    $stNota = $bd->prepare('INSERT INTO notas (avaliacao_id, aluno_id, valor) VALUES (?, ?, ?)');
    foreach ($idTurma as $ti => $t) {
        foreach ($idDisc as $d => $iddisc) {
            $prof = $professorDe[$d];
            $lista = [['Teste 1', 'teste', 40, '2026-09-30', true], ['Trabalho de grupo', 'trabalho', 30, '2026-10-02', true], ['Teste 2', 'teste', 30, '2026-10-20', false]];
            foreach ($lista as [$titulo, $tipo, $peso, $data, $lancada]) {
                $stAv->execute([$t, $iddisc, $titulo, $tipo, $peso, $data, $prof]);
                $idAv = (int) $bd->lastInsertId();
                if (!$lancada) {
                    continue;
                }
                foreach ($alunos as [$idAluno, $tiAluno, $nivel]) {
                    if ($tiAluno !== $ti) {
                        continue;
                    }
                    $desvio = (mt_rand(0, 100) - 50) / 12 + (($d * 3 + $idAluno) % 5 - 2) * 0.5;
                    $valor = round(max(3.0, min(20.0, $nivel + $desvio)), 1);
                    $stNota->execute([$idAv, $idAluno, $valor]);
                }
            }
        }
    }

    // Faltas entre 14 de setembro e 2 de outubro
    $dias = [];
    for ($dia = strtotime('2026-09-14'); $dia <= strtotime('2026-10-02'); $dia += 86400) {
        if ((int) date('N', $dia) <= 5) {
            $dias[] = date('Y-m-d', $dia);
        }
    }
    $stFalta = $bd->prepare('INSERT OR IGNORE INTO faltas (aluno_id, disciplina_id, data, justificada, motivo, registado_por) VALUES (?, ?, ?, ?, ?, ?)');
    foreach ($alunos as [$idAluno, $ti, $nivel]) {
        $quantas = mt_rand(0, 100) < 30 ? 0 : mt_rand(1, $nivel < 11 ? 6 : 3);
        for ($k = 0; $k < $quantas; $k++) {
            $d = mt_rand(0, 7);
            $justificada = mt_rand(0, 100) < 35 ? 1 : 0;
            $stFalta->execute([$idAluno, $idDisc[$d], $dias[mt_rand(0, count($dias) - 1)], $justificada, $justificada ? 'Doença' : null, $professorDe[$d]]);
        }
    }

    // Avisos
    $stAviso = $bd->prepare('INSERT INTO avisos (titulo, texto, autor_id, para, criado_em) VALUES (?, ?, ?, ?, ?)');
    $stAviso->execute(['Bom ano letivo 2026/2027', "Damos as boas-vindas a todos os alunos, professores e funcionários.\nO 1.º período termina a 18 de dezembro.", $direcao, 'todos', '2026-09-14 09:00:00']);
    $stAviso->execute(['Reunião com encarregados de educação', 'A reunião realiza-se a 15 de outubro, às 18h00, no auditório. A presença é muito importante.', $direcao, 'todos', '2026-09-28 11:30:00']);
    $stAviso->execute(['Entrega de documentos', 'Os alunos devem entregar na secretaria o comprovativo de vacinação e a fotografia atualizada até 20 de outubro.', $secretaria, 'aluno', '2026-09-30 10:15:00']);
    $stAviso->execute(['Plano de aulas do 1.º período', 'Os professores devem entregar o plano de aulas e os critérios de avaliação à direção até sexta-feira.', $direcao, 'professor', '2026-10-01 08:45:00']);
    $stAviso->execute(['Teste de Matemática', 'O Teste 2 de Matemática realiza-se a 20 de outubro. Matéria: números racionais e equações.', $professorDe[1], 'turma:' . $idTurma[2], '2026-10-02 14:20:00']);

    // Horários (cada turma tem as disciplinas em dias e horas diferentes das outras)
    $stHor = $bd->prepare('INSERT INTO horarios (turma_id, dia, aula, disciplina_id, sala) VALUES (?, ?, ?, ?, ?)');
    foreach ($idTurma as $ti => $t) {
        for ($dia = 1; $dia <= 5; $dia++) {
            for ($aula = 1; $aula <= 6; $aula++) {
                $d = (($dia - 1) * 6 + ($aula - 1) + 2 * $ti) % 8;
                $sala = $d === 5 ? 'pavilhao' : ($d === 7 ? 'laboratorio' : 'turma');
                $stHor->execute([$t, $dia, $aula, $idDisc[$d], $sala]);
            }
        }
    }

    $pessoal = semearPessoal($bd, $portaria);
    semearFinancas($bd, $direcao, $secretaria, $contabilista, $professorDe, $alunos, $pessoal);
    semearVisitas($bd, $portaria, $professorDe);
}

/** Pessoal não docente (sem acesso à aplicação). Devolve [id => salário bruto em cêntimos, IRS %]. O porteiro de demonstração também tem ficha. */
function semearPessoal(PDO $bd, int $portaria): array
{
    $ficha = $bd->prepare('INSERT INTO funcionarios (utilizador_id, area, cargo, turno, telefone) VALUES (?, ?, ?, ?, ?)');
    $ficha->execute([$portaria, 'portaria', 'Porteiro', 'manha', '912 555 010']);
    $novo = $bd->prepare("INSERT INTO utilizadores (nome, email, password_hash, perfil, criado_em) VALUES (?, ?, '!', 'funcionario', ?)");
    $lista = [
        ['Lurdes Pinto',     'cantina',    'Cozinheira',                'integral', '912 555 011', 128000, 9.0],
        ['Rui Gouveia',      'cantina',    'Ajudante de cozinha',       'manha',    '912 555 012', 104000, 7.0],
        ['Sónia Matos',      'cantina',    'Empregada de refeitório',   'tarde',    '912 555 013', 98000, 6.5],
        ['Adelaide Franco',  'limpeza',    'Auxiliar de limpeza',       'manha',    '912 555 014', 95000, 6.0],
        ['Fátima Cruz',      'limpeza',    'Auxiliar de limpeza',       'tarde',    '912 555 015', 95000, 6.0],
        ['Manuel Esteves',   'limpeza',    'Encarregado de limpeza',    'integral', '912 555 016', 118000, 8.0],
        ['Carlos Neto',      'vigilancia', 'Vigilante',                 'manha',    '912 555 017', 102000, 7.0],
        ['Paula Serra',      'vigilancia', 'Assistente operacional',    'tarde',    '912 555 018', 100000, 7.0],
        ['Nuno Vidal',       'vigilancia', 'Assistente operacional',    'manha',    '912 555 019', 100000, 7.0],
        ['Armindo Dias',     'portaria',   'Porteiro',                  'tarde',    '912 555 020', 99000, 6.5],
    ];
    $agora = date('Y-m-d H:i:s');
    $pessoal = [$portaria => [105000, 7.5]];
    foreach ($lista as $k => [$nome, $area, $cargo, $turno, $tel, $bruto, $irs]) {
        $novo->execute([$nome, 'funcionario.' . ($k + 1) . '@interno.colegio.demo', $agora]);
        $id = (int) $bd->lastInsertId();
        $ficha->execute([$id, $area, $cargo, $turno, $tel]);
        $pessoal[$id] = [$bruto, $irs];
    }
    return $pessoal;
}

/** Algumas visitas dos últimos dias (todas já saíram). */
function semearVisitas(PDO $bd, int $portaria, array $professorDe): void
{
    $st = $bd->prepare('INSERT INTO visitas (visitante, motivo, destino, entrada, saida, registado_por) VALUES (?, ?, ?, ?, ?, ?)');
    $lista = [
        ['Paula Mendes (encarregada de educação)', 'reuniao',    'Diretor de turma do 7.ºA', '2026-09-28 09:05:00', '2026-09-28 09:50:00'],
        ['Transportes Lusitânia',                 'fornecedor', 'Cantina',                  '2026-09-29 08:10:00', '2026-09-29 08:30:00'],
        ['Jorge Almeida (encarregado de educação)','documentos', 'Secretaria',               '2026-09-30 10:20:00', '2026-09-30 10:45:00'],
        ['Eletricidade Norte, Lda.',              'manutencao', 'Pavilhão desportivo',      '2026-10-01 14:00:00', '2026-10-01 16:40:00'],
        ['Marta Correia (encarregada de educação)','recolha',    'Secretaria',               '2026-10-01 12:25:00', '2026-10-01 12:35:00'],
        ['Rui Tavares (encarregado de educação)', 'reuniao',    'Direção',                  '2026-10-02 11:00:00', '2026-10-02 11:50:00'],
        ['Papelaria Central',                     'fornecedor', 'Secretaria',               '2026-10-02 15:10:00', '2026-10-02 15:25:00'],
    ];
    foreach ($lista as [$nome, $motivo, $destino, $entrada, $saida]) {
        $st->execute([$nome, $motivo, $destino, $entrada, $saida, $portaria]);
    }
}

/** Dados de exemplo das finanças: parâmetros, propinas de setembro e outubro, contratos, salários de setembro e lançamentos. */
function semearFinancas(PDO $bd, int $direcao, int $secretaria, int $contabilista, array $professorDe, array $alunos, array $pessoal): void
{
    foreach (PARAMETROS_INICIAIS as $chave => $valor) {
        $bd->prepare('INSERT INTO parametros (chave, valor) VALUES (?, ?)')->execute([$chave, $valor]);
    }

    // Propinas de setembro e outubro. Quase todos pagaram setembro; poucos pagaram outubro (vence a 8).
    $anoDe = [];
    foreach ($bd->query('SELECT a.utilizador_id AS id, t.ano FROM alunos a JOIN turmas t ON t.id = a.turma_id')->fetchAll() as $l) {
        $anoDe[(int) $l['id']] = (int) $l['ano'];
    }
    $metodos = ['multibanco', 'transferencia', 'mbway', 'numerario'];
    $st = $bd->prepare('INSERT INTO propinas (aluno_id, mes, valor_cents, vencimento, pago_em, metodo) VALUES (?, ?, ?, ?, ?, ?)');
    foreach ($alunos as $k => [$idAluno]) {
        $valor = (int) round((float) PARAMETROS_INICIAIS['propina_' . $anoDe[$idAluno]] * 100);
        $setPago = ($k % 9) !== 4;                       // cerca de 11% não pagaram setembro (em atraso)
        $dia = 1 + ($k * 3) % 8;
        $st->execute([$idAluno, '2026-09', $valor, '2026-09-08', $setPago ? sprintf('2026-09-%02d', $dia) : null, $setPago ? $metodos[$k % 4] : null]);
        $outPago = ($k % 4) === 0;                       // 25% já pagaram outubro
        $st->execute([$idAluno, '2026-10', $valor, '2026-10-08', $outPago ? sprintf('2026-10-%02d', 1 + $k % 3) : null, $outPago ? $metodos[($k + 1) % 4] : null]);
    }

    // Contratos (vencimento bruto e retenção de IRS ilustrativos)
    $contrato = $bd->prepare('INSERT INTO contratos (utilizador_id, cargo, bruto_cents, irs_taxa) VALUES (?, ?, ?, ?)');
    $contrato->execute([$direcao, 'Diretor', 330000, 23.0]);
    $contrato->execute([$secretaria, 'Secretária', 152000, 12.0]);
    $contrato->execute([$contabilista, 'Contabilista certificada', 245000, 18.0]);
    $cargoDe = $bd->prepare('SELECT cargo FROM funcionarios WHERE utilizador_id = ?');
    foreach ($pessoal as $idPessoa => [$brutoP, $irsP]) {
        $cargoDe->execute([$idPessoa]);
        $contrato->execute([$idPessoa, (string) ($cargoDe->fetchColumn() ?: 'Funcionário'), $brutoP, $irsP]);
    }
    $vistos = [];
    $brutos = [0 => [201000, 15.0], 1 => [214000, 16.0], 2 => [196000, 14.5], 3 => [208000, 15.5], 4 => [199000, 14.5], 5 => [188000, 13.5], 7 => [192000, 14.0]];
    foreach ($professorDe as $d => $idProf) {
        if (isset($vistos[$idProf])) {
            continue;
        }
        $vistos[$idProf] = true;
        [$bruto, $irs] = $brutos[$d] ?? [195000, 14.0];
        $contrato->execute([$idProf, 'Professor', $bruto, $irs]);
    }

    // Salários de setembro, já pagos a 30 de setembro
    processarSalarios($bd, '2026-09', '2026-09-30');

    // Despesas e receitas de setembro e outubro
    $lanc = $bd->prepare('INSERT INTO lancamentos (tipo, categoria, descricao, data, base_cents, iva_taxa, iva_cents, total_cents, criado_por, criado_em) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $itens = [
        ['despesa', 'material',   'Material escolar e papelaria',          '2026-09-05', 184000, 23],
        ['despesa', 'energia',    'Eletricidade (setembro)',               '2026-09-28', 142000, 23],
        ['despesa', 'energia',    'Água e saneamento (setembro)',          '2026-09-28',  38500, 6],
        ['despesa', 'limpeza',    'Serviço de limpeza (setembro)',         '2026-09-30', 120000, 23],
        ['despesa', 'software',   'Licenças de software de gestão',        '2026-09-12',  64000, 23],
        ['despesa', 'seguros',    'Seguro escolar e do edifício',          '2026-09-10',  95000, 0],
        ['despesa', 'taxas',      'Taxa municipal de ocupação',            '2026-09-15',  21000, 0],
        ['despesa', 'manutencao', 'Reparação do aquecimento do pavilhão',  '2026-10-02',  76000, 23],
        ['receita', 'cantina',    'Refeições da cantina (setembro)',       '2026-09-30', 168000, 13],
        ['receita', 'aluguer',    'Aluguer do pavilhão ao clube local',    '2026-09-20',  60000, 23],
        ['receita', 'loja',       'Venda de uniformes e material',         '2026-09-18',  92000, 23],
        ['receita', 'donativos',  'Donativo da associação de pais',        '2026-10-01',  50000, 0],
    ];
    foreach ($itens as [$tipo, $categoria, $descricao, $data, $base, $taxa]) {
        [$iva, $total] = calcularIva($base, $taxa);
        $lanc->execute([$tipo, $categoria, $descricao, $data, $base, $taxa, $iva, $total, $contabilista, $data . ' 10:00:00']);
    }

    // Dois exemplos no registo de alterações (o motivo é sempre obrigatório ao apagar)
    $nomeContab = (string) $bd->query('SELECT nome FROM utilizadores WHERE id = ' . (int) $contabilista)->fetchColumn();
    $aud = $bd->prepare('INSERT INTO auditoria (quando, utilizador_id, utilizador_nome, entidade, acao, resumo, motivo) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $aud->execute(['2026-09-29 16:12:00', $contabilista, $nomeContab, 'lancamento', 'apagar', 'despesa · energia · Eletricidade (setembro) · 2026-09-28 · 1 420,00 € + IVA 23 %', 'Registo duplicado: a fatura já estava lançada.']);
    $aud->execute(['2026-10-01 09:40:00', $contabilista, $nomeContab, 'propina', 'editar', '8.ºA · 2026-09 · 190,00 € → 185,00 €', 'Desconto de irmãos aprovado pela direção.']);
}
