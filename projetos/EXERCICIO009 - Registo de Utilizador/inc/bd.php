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
    perfil        TEXT NOT NULL CHECK (perfil IN ('aluno', 'professor', 'secretaria', 'direcao')),
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
}
