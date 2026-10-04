# Sistema de Gestão Escolar · Exercício 009 · v2.1.0

Evolução do "Registo de Utilizador" para um sistema prático de colégio (fictício: **Colégio Horizonte**), feito em **PHP + SQLite (PDO)**, com sete perfis: aluno, professor, secretaria, direção, **Conselho Geral**, **contabilidade** e **portaria**; inclui uma parte financeira (propinas, salários, impostos e relatórios em Excel/PDF).

## Como experimentar

- **Online:** https://portfolio-php-p3pd.onrender.com/projetos/EXERCICIO009%20-%20Registo%20de%20Utilizador/ (o Render gratuito adormece; a primeira abertura pode demorar cerca de 50 s e os dados voltam ao exemplo quando reinicia).
- **No computador (XAMPP):** copie a pasta para `htdocs` e abra `http://localhost/…/EXERCICIO009 - Registo de Utilizador/`. Ou, com o PHP instalado, `php -S localhost:8000` dentro da pasta.
- A base de dados cria-se e enche-se sozinha na primeira abertura (ficheiro na pasta temporária do servidor; variável `COLEGIO_DB` muda o caminho).

Contas de demonstração (palavra-passe `demo1234`): `aluno@`, `professor@`, `secretaria@`, `direcao@`, `conselho@`, `contabilidade@` e `portaria@colegio.demo`, ou os botões "Entrar como…".

## O que faz cada perfil

| Perfil | Pode |
|---|---|
| Aluno | ver o boletim (imprimir), faltas, horário, avisos, as suas propinas e recibos |
| Professor | avaliações, notas, faltas, pauta, avisos, Relatório; vê o seu contrato e recibos de vencimento |
| Secretaria | gerir alunos, professores, turmas e a equipa não docente; ver as propinas (só leitura) e **quem já foi pago, sem valores** |
| Direção | tudo da secretaria + painel de resultados; **consulta** o financeiro (não edita) |
| Conselho Geral | destitui e nomeia a Direção (data e motivo, com histórico) e consulta o financeiro; a Direção não se destitui a si própria |
| Contabilidade | **único perfil que edita as finanças**: propinas, salários e contratos, despesas e receitas com IVA, parâmetros, Excel e PDF |
| Portaria | regista entradas e saídas de visitas, vê a equipa de turno e o seu recibo |

## Parte financeira (v2.1.0)

Valores guardados em cêntimos. Propinas por ano de escolaridade, salários de todo o pessoal (professores, secretaria, direção, contabilidade, portaria, cantina, limpeza e vigilância) com IRS, Segurança Social do trabalhador (11 %) e da entidade (23,75 %), despesas e receitas com IVA (0, 6, 13 e 23 %) e painel com gráficos. Relatórios em **Excel** (`.xlsx`, gerado em PHP puro em `inc/xlsx.php`) e em **PDF**: resumo, propinas, salários, Segurança Social e impostos, despesas e receitas, custos. As taxas são valores de exemplo para demonstração, não substituem um contabilista.

## Relatório (pauta livre em PDF)

O professor ou formador preenche a sua própria pauta (título, instituição, curso, disciplina ou módulo, formador, período, data), adiciona, edita e apaga alunos, colunas de notas (com título e peso) e notas, vê a média, a nota final e o resultado a atualizarem-se, guarda e descarrega em **PDF** (A4 horizontal, cabeçalho repetido, número de página, assinaturas). Pode começar em branco ou importar uma turma. Cada pessoa só vê os seus relatórios. O PDF é gerado em PHP puro (`inc/pdf.php`), sem bibliotecas.

## Estrutura

```
index.php          router: idioma, sessão, permissões, cabeçalhos de segurança
inc/               configuração, base de dados, login, cálculos, finanças, Excel, PDF, gráficos, textos (PT/EN/ES/FR)
paginas/           uma página por ficheiro
app.css, app.js    estilo e comportamento (ficheiros externos por causa da política CSP)
```

## Segurança (resumo)

Palavras-passe com `password_hash`; sessões com `HttpOnly`, `SameSite`, regeneração do ID ao entrar e modo estrito; código **CSRF** em todos os formulários; consultas **preparadas** (PDO); texto escapado com `htmlspecialchars`; limite de tentativas de entrada (5 falhas, 30 s de espera); verificação de perfil em cada página e de propriedade em cada gravação (o professor só mexe nas suas turmas e relatórios; só a contabilidade grava dados financeiros); cabeçalho `Content-Security-Policy` sem scripts nem estilos inline; ficheiros de `inc/` e `paginas/` recusam acesso direto.

## Versões

- **2.1.0** Parte financeira, Excel e PDF, Conselho Geral, contabilidade, equipa não docente e portaria com visitas.
- **2.0.0** Sistema de gestão escolar completo (este), com Relatório em PDF.
- **1.0** Formulário simples de registo (HTML, `index.html` + `script.js` + `style.css`, mantido como versão estática).
