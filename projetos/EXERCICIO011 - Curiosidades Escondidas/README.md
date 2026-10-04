# Curiosidades do Mundo · Exercício 011 · v2.0.0

Curiosidades **reais da Wikipédia**, por áreas, com mais dados escondidos para revelar ao clicar. Só HTML, CSS e JavaScript (funciona na GitHub Pages, sem servidor nem chave de API).

## Como funciona

1. Escolhe uma área: Ciência, Espaço e Matemática; Natureza, Animais e Corpo humano; História, Geografia e Cultura; Arte, Música, Cinema, Desporto, Comida e Tecnologia; Teologia e Religiões; ou "Surpreende-me".
2. O programa sorteia uma palavra da área e uma posição nos resultados, e pede à API da Wikipédia (`action=query`, `generator=search`) o resumo e a imagem de um artigo. Cada clique dá uma curiosidade diferente.
3. **Revelar mais dados** mostra o texto maior do artigo, a descrição curta, em quantos idiomas existe, a última edição, o tamanho, as categorias e temas relacionados (clicáveis).
4. **Mais uma** traz outra curiosidade da mesma área; **Ler na Wikipédia** abre o artigo.

O idioma (PT, EN, ES, FR) decide também de que Wikipédia vêm os artigos.

## Segurança

Todo o texto vindo da API entra na página com `textContent` (nunca `innerHTML`), as imagens só são aceites de `upload.wikimedia.org` e as ligações só de `*.wikipedia.org`. Respostas atrasadas são ignoradas e os pedidos têm limite de tempo.

## Ficheiros

`index.html`, `style.css`, `idiomas.js` (textos e palavras de pesquisa por idioma), `script.js` (pedidos e interface), `icone.svg`.

## Versões

- **2.0.0** Dados reais da Wikipédia, áreas, dados escondidos, 4 idiomas.
- **1.0** Três curiosidades fixas reveladas com um botão.

Textos e imagens da Wikipédia, licença CC BY-SA 4.0.
