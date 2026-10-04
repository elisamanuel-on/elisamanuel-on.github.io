# Sistema de Gestão Escolar · Exercício 009 · v2.0.0

Evolução do "Registo de Utilizador" para um sistema prático de colégio (fictício: **Colégio Horizonte**), feito em **PHP + SQLite (PDO)**, com quatro perfis: aluno, professor, secretaria e direção.

## Como experimentar

- **Online:** https://portfolio-php-p3pd.onrender.com/projetos/EXERCICIO009%20-%20Registo%20de%20Utilizador/ (o Render gratuito adormece; a primeira abertura pode demorar cerca de 50 s e os dados voltam ao exemplo quando reinicia).
- **No computador (XAMPP):** copie a pasta para `htdocs` e abra `http://localhost/…/EXERCICIO009 - Registo de Utilizador/`. Ou, com o PHP instalado, `php -S localhost:8000` dentro da pasta.
- A base de dados cria-se e enche-se sozinha na primeira abertura (ficheiro na pasta temporária do servidor; variável `COLEGIO_DB` muda o caminho).

Contas de demonstração (palavra-passe `demo1234`): `aluno@`, `professor@`, `secretaria@` e `direcao@colegio.demo`, ou os botões "Entrar como…".

## O que faz cada perfil

| Perfil | Pode |
|---|---|
| Aluno | ver o boletim (imprimir), faltas, horário, avisos e mudar a palavra-passe |
| Professor | criar avaliações com peso, lançar notas (média ao vivo), registar faltas, pauta, avisos às suas turmas e o separador **Relatório** |
| Secretaria | gerir alunos, professores e turmas, justificar faltas, pautas, boletins, horários e avisos |
| Direção | tudo da secretaria + painel com gráficos (média por turma e disciplina, resultados, alunos em risco) e repor a demonstração |

## Relatório (pauta livre em PDF)

O professor ou formador preenche a sua própria pauta (título, instituição, curso, disciplina ou módulo, formador, período, data), adiciona, edita e apaga alunos, colunas de notas (com título e peso) e notas, vê a média, a nota final e o resultado a atualizarem-se, guarda e descarrega em **PDF** (A4 horizontal, cabeçalho repetido, número de página, assinaturas). Pode começar em branco ou importar uma turma. Cada pessoa só vê os seus relatórios. O PDF é gerado em PHP puro (`inc/pdf.php`), sem bibliotecas.

## Estrutura

```
index.php          router: idioma, sessão, permissões, cabeçalhos de segurança
inc/               configuração, base de dados, login, cálculos, PDF, textos (PT/EN/ES/FR)
paginas/           uma página por ficheiro
app.css, app.js    estilo e comportamento (ficheiros externos por causa da política CSP)
```

## Segurança (resumo)

Palavras-passe com `password_hash`; sessões com `HttpOnly`, `SameSite`, regeneração do ID ao entrar e modo estrito; código **CSRF** em todos os formulários; consultas **preparadas** (PDO); texto escapado com `htmlspecialchars`; limite de tentativas de entrada (5 falhas, 30 s de espera); verificação de perfil em cada página e de propriedade em cada gravação (o professor só mexe nas suas turmas e relatórios); cabeçalho `Content-Security-Policy` sem scripts nem estilos inline; ficheiros de `inc/` e `paginas/` recusam acesso direto.

## Versões

- **2.0.0** Sistema de gestão escolar completo (este), com Relatório em PDF.
- **1.0** Formulário simples de registo (HTML, `index.html` + `script.js` + `style.css`, mantido como versão estática).
