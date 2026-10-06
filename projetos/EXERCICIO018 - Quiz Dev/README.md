# Quiz Dev

Quiz de estudo em formato de editor de código. HTML, CSS e JavaScript puros, sem dependências.

- Fichas de estudo e 4 níveis por tópico (Reconhecer, Compreender, Aplicar, Escrever), 4 perguntas por nível.
- 2 vidas por nível, 25 XP por nível concluído, medalhas, calendário de actividade e lista de revisão das perguntas erradas.
- O progresso fica guardado no navegador (localStorage, chave `quizdev.v1`).
- Teclado: A–D ou 1–4 para responder, Enter para continuar. No telemóvel, o nível "Escrever" usa botões.

## Ficheiros

- `index.html` — estrutura da página
- `style.css` — aspecto
- `motor.js` — lógica do quiz
- `dados.js` — conteúdo (temas, tópicos, fichas, perguntas)
- `logo.svg` — logótipo

## Como acrescentar conteúdo

Em `dados.js`, `window.QUIZ_DADOS` é uma lista de temas. Cada tema tem tópicos; cada tópico tem uma `ficha` e 4 `niveis` com 4 perguntas. Tipos de pergunta: `mc` (escolha múltipla), `cod` (a partir de código), `lac` (completar a lacuna `___`) e `esc` (escrever de memória, com variantes aceites em `ok`). Basta copiar um tópico existente e alterar.
