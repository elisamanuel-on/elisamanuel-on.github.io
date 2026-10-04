<?php
/**
 * Exercício 009 · Sistema de Gestão Escolar (PHP + SQLite)
 * Configuração geral. Os ficheiros em inc/ e paginas/ não se abrem diretamente: só através do index.php.
 */

declare(strict_types=1);

if (!defined('APP')) {
    http_response_code(403);
    exit;
}

const VERSAO = '2.0.0';          // versão da aplicação (também usada nos ficheiros, para a cache do navegador)
const VERSAO_BD = 1;             // muda quando o esquema da base de dados muda
const ANO_LETIVO = '2026/2027';
const NOTA_POSITIVA = 10;        // escala 0 a 20: 10 ou mais é positiva
const FALTAS_RISCO = 3;          // faltas injustificadas a partir das quais o aluno aparece "em risco"
const PASSWORD_DEMO = 'demo1234';

const PERFIS = ['aluno', 'professor', 'secretaria', 'direcao'];

const CONTAS_DEMO = [
    'aluno'      => 'aluno@colegio.demo',
    'professor'  => 'professor@colegio.demo',
    'secretaria' => 'secretaria@colegio.demo',
    'direcao'    => 'direcao@colegio.demo',
];

// Aulas do dia (hora de início e de fim)
const HORAS_AULA = [
    1 => ['08:30', '09:20'],
    2 => ['09:30', '10:20'],
    3 => ['10:40', '11:30'],
    4 => ['11:40', '12:30'],
    5 => ['14:00', '14:50'],
    6 => ['15:00', '15:50'],
];

// Que perfis podem abrir cada página
const PAGINAS = [
    'painel'      => ['aluno', 'professor', 'secretaria', 'direcao'],
    'notas'       => ['professor'],
    'pauta'       => ['professor', 'secretaria', 'direcao'],
    'relatorio'   => ['professor', 'secretaria', 'direcao'],
    'boletim'     => ['aluno', 'secretaria', 'direcao'],
    'faltas'      => ['aluno', 'professor', 'secretaria', 'direcao'],
    'avisos'      => ['aluno', 'professor', 'secretaria', 'direcao'],
    'horario'     => ['aluno', 'professor', 'secretaria', 'direcao'],
    'alunos'      => ['secretaria', 'direcao'],
    'professores' => ['secretaria', 'direcao'],
    'turmas'      => ['secretaria', 'direcao'],
    'direcao'     => ['direcao'],
    'perfil'      => ['aluno', 'professor', 'secretaria', 'direcao'],
];

// Menu lateral de cada perfil (por ordem)
const MENUS = [
    'aluno'      => ['painel', 'boletim', 'faltas', 'horario', 'avisos', 'perfil'],
    'professor'  => ['painel', 'notas', 'pauta', 'relatorio', 'faltas', 'horario', 'avisos', 'perfil'],
    'secretaria' => ['painel', 'alunos', 'professores', 'turmas', 'pauta', 'relatorio', 'boletim', 'faltas', 'horario', 'avisos', 'perfil'],
    'direcao'    => ['painel', 'direcao', 'alunos', 'professores', 'turmas', 'pauta', 'relatorio', 'boletim', 'faltas', 'horario', 'avisos', 'perfil'],
];

const MAX_RELATORIO_LINHAS = 80;   // alunos por relatório
const MAX_RELATORIO_COLUNAS = 12;  // colunas de notas por relatório
