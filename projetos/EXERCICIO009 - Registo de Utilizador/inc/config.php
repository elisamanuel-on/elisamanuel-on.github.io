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

const VERSAO = '2.1.0';          // versão da aplicação (também usada nos ficheiros, para a cache do navegador)
const VERSAO_BD = 2;             // muda quando o esquema da base de dados muda
const ANO_LETIVO = '2026/2027';
const NOTA_POSITIVA = 10;        // escala 0 a 20: 10 ou mais é positiva
const FALTAS_RISCO = 3;          // faltas injustificadas a partir das quais o aluno aparece "em risco"
const PASSWORD_DEMO = 'demo1234';

const PERFIS = ['aluno', 'professor', 'secretaria', 'direcao', 'conselho', 'contabilidade', 'portaria'];
// 'funcionario' (cantina, limpeza, vigilância) existe na base de dados mas não tem acesso: só ficha e salário.
// Quem recebe salário pela escola:
const PERFIS_PESSOAL = ['professor', 'secretaria', 'direcao', 'contabilidade', 'portaria', 'funcionario'];

const CONTAS_DEMO = [
    'aluno'      => 'aluno@colegio.demo',
    'professor'  => 'professor@colegio.demo',
    'secretaria' => 'secretaria@colegio.demo',
    'direcao'    => 'direcao@colegio.demo',
    'conselho'   => 'conselho@colegio.demo',
    'contabilidade' => 'contabilidade@colegio.demo',
    'portaria'   => 'portaria@colegio.demo',
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
    'painel'      => ['aluno', 'professor', 'secretaria', 'direcao', 'conselho', 'contabilidade', 'portaria'],
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
    'equipa'      => ['secretaria', 'direcao', 'portaria'],
    'visitas'     => ['portaria', 'secretaria', 'direcao'],
    'direcao'     => ['direcao'],
    // Finanças: só a contabilidade edita. A direção e o Conselho Geral consultam. A secretaria vê as propinas
    // e só se a equipa já foi paga (sem valores).
    'financeiro'  => ['contabilidade', 'direcao', 'conselho'],
    'propinas'    => ['aluno', 'secretaria', 'direcao', 'contabilidade'],
    'salarios'    => ['professor', 'secretaria', 'direcao', 'contabilidade', 'portaria'],
    'lancamentos' => ['contabilidade', 'direcao'],
    'conselho'    => ['conselho'],
    'perfil'      => ['aluno', 'professor', 'secretaria', 'direcao', 'conselho', 'contabilidade', 'portaria'],
];

// Menu lateral de cada perfil (por ordem)
const MENUS = [
    'aluno'      => ['painel', 'boletim', 'faltas', 'horario', 'propinas', 'avisos', 'perfil'],
    'professor'  => ['painel', 'notas', 'pauta', 'relatorio', 'faltas', 'horario', 'salarios', 'avisos', 'perfil'],
    'secretaria' => ['painel', 'alunos', 'professores', 'equipa', 'turmas', 'pauta', 'relatorio', 'boletim', 'faltas', 'horario', 'visitas', 'propinas', 'salarios', 'avisos', 'perfil'],
    'direcao'    => ['painel', 'direcao', 'alunos', 'professores', 'equipa', 'turmas', 'pauta', 'relatorio', 'boletim', 'faltas', 'horario', 'visitas', 'financeiro', 'propinas', 'salarios', 'lancamentos', 'avisos', 'perfil'],
    'conselho'   => ['painel', 'conselho', 'financeiro', 'perfil'],
    'contabilidade' => ['painel', 'financeiro', 'propinas', 'salarios', 'lancamentos', 'perfil'],
    'portaria'   => ['painel', 'visitas', 'equipa', 'salarios', 'perfil'],
];

// Páginas do grupo "Finanças" (o menu mostra um título quando há duas ou mais)
const MENU_FINANCAS = ['financeiro', 'propinas', 'salarios', 'lancamentos'];

// ---------- Finanças (valores de exemplo, para fins escolares) ----------
const TAXAS_IVA = [0, 6, 13, 23];           // % de IVA em Portugal continental (0 = isento ou sem IVA)
const MESES_LETIVOS = ['2026-09', '2026-10', '2026-11', '2026-12', '2027-01', '2027-02', '2027-03', '2027-04', '2027-05', '2027-06'];
// Pessoal não docente
const AREAS_PESSOAL = ['cantina', 'limpeza', 'vigilancia', 'portaria'];
const TURNOS = ['manha' => ['07:30', '15:30'], 'tarde' => ['12:00', '20:00'], 'integral' => ['08:00', '18:00']];
// Portaria: motivos de visita
const MOTIVOS_VISITA = ['reuniao', 'documentos', 'recolha', 'fornecedor', 'manutencao', 'outro'];
const CATEGORIAS = [
    'despesa' => ['material', 'manutencao', 'energia', 'limpeza', 'software', 'seguros', 'taxas', 'outras_d'],
    'receita' => ['cantina', 'aluguer', 'donativos', 'loja', 'outras_r'],
];
// Valores iniciais dos parâmetros (a direção pode alterá-los na página Financeiro)
const PARAMETROS_INICIAIS = [
    'propina_7'  => '180.00',   // propina mensal do 7.º ano (€)
    'propina_8'  => '190.00',
    'propina_9'  => '200.00',
    'dia_vencimento' => '8',    // dia do mês em que a propina vence
    'ss_trab'    => '11',       // Segurança Social paga pelo trabalhador (%)
    'ss_ent'     => '23.75',    // Segurança Social paga pela entidade empregadora (%)
];

const MAX_RELATORIO_LINHAS = 80;   // alunos por relatório
const MAX_RELATORIO_COLUNAS = 12;  // colunas de notas por relatório
