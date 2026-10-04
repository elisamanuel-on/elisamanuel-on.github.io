# Imagem Escondida (Exercício 012) — versão 2.0.0

Jogo de geografia em JavaScript puro, com 3 níveis, cronómetro, vidas, mapa e ficha de cada país.
Funciona em GitHub Pages (não precisa de servidor nem de chaves).

## Como se joga

Cada nível tem 10 perguntas e 3 vidas. Cada pergunta tem um cronómetro: quem acerta mais depressa ganha mais pontos.
A imagem (bandeira ou fotografia) começa desfocada e fica nítida com o passar do tempo — daí o nome do projeto.
Errar ou deixar o tempo acabar custa uma vida. Cada nível só abre depois de completar o anterior.

1. **Continentes** — clicar num país do continente pedido, escolher o continente de uma bandeira ou de um país e escrever o continente.
2. **Países e bandeiras** — adivinhar o país pela bandeira (escolher e escrever), escolher a bandeira certa e clicar no país no mapa.
3. **Capitais e curiosidades** — escolher e escrever capitais, clicar no país de uma capital, línguas oficiais, moedas e curiosidades reais da Wikipédia (com o nome do país tapado).

Pontos por resposta certa: 100 + até 100 pela rapidez + 20 se for escrita + bónus de sequência (até 50). No fim de um nível completo há 50 pontos por cada vida que sobra.

## Identidades

- **Jogador:** nome e avatar, recordes e estrelas por nível, ranking local (guardados no `localStorage` deste navegador, máximo 8 jogadores).
- **País:** depois de cada resposta aparece a ficha do país (capital, continente, línguas, moeda, área, domínio, indicativo e fronteiras), a curiosidade da Wikipédia e os botões «Ver no Google Maps» e «Ler na Wikipédia».
- **Visual:** logótipo próprio (`icone.svg`) e as cores do projeto (fundo preto, cartão #1a1a1a, degradê roxo).

## Idiomas

Português, inglês, espanhol e francês. Escolhe-se no topo (fica guardado) e, à primeira visita, usa-se o idioma do navegador.
Nomes de países e capitais, línguas, moedas e curiosidades seguem o idioma escolhido.

## Mapa e Google Maps

O mapa é o Leaflet com as fronteiras locais (sem nomes, para as perguntas de clicar não terem a resposta escrita). Depois de cada resposta mostra-se por baixo o mapa do OpenStreetMap, para dar contexto.
O Google Maps entra como ligação (`https://www.google.com/maps/search/?api=1&query=...`), que não precisa de chave.
Pôr o mapa do Google dentro do jogo exige uma conta Google Cloud com faturação e uma chave restringida ao domínio; fica como evolução possível.

## Ficheiros

- `index.html`, `style.css` — página e desenho.
- `script.js` — jogo (níveis, perguntas, cronómetro, pontuação, ecrãs).
- `idiomas.js` — textos nos 4 idiomas.
- `perfil.js` — jogadores, recordes e ranking.
- `mapa.js` — mapa (Leaflet) e fronteiras.
- `wiki.js` — curiosidades (API MediaWiki; só texto simples e imagens de `upload.wikimedia.org`).
- `dados/paises.js`, `dados/mundo.js` — países (nomes, capitais, línguas, moedas) e fronteiras.
- `dados/bandeiras.js` — 194 bandeiras em SVG.
- `vendor/` — Leaflet e topojson-client.
- `icone.svg` — logótipo.

## Segurança

Todo o texto vindo de fora (Wikipédia, nomes de jogadores) é escrito com `textContent`; nomes de jogadores só aceitam letras, números, espaços, hífen e apóstrofo.
As ligações externas abrem com `rel="noopener noreferrer"` e só para `google.com/maps` e `*.wikipedia.org`. Não há `eval`, nem scripts ou estilos escritos no HTML.

## Fontes e licenças

- Países, capitais, línguas, moedas: [world-countries](https://github.com/mledoze/countries) (ODbL).
- Fronteiras: [Natural Earth](https://www.naturalearthdata.com/) através de [world-atlas](https://github.com/topojson/world-atlas) (domínio público; ISC).
- Bandeiras: [country-flag-icons](https://gitlab.com/catamphetamine/country-flag-icons) (MIT).
- Mapa: [Leaflet](https://leafletjs.com/) (BSD-2) e dados © colaboradores do [OpenStreetMap](https://www.openstreetmap.org/copyright).
- Ícones: Feather/Lucide (MIT).
- Curiosidades: [Wikipédia](https://www.wikipedia.org/) (CC BY-SA).

## Versões

- **2.0.0** — jogo de geografia com 3 níveis, cronómetro, vidas, mapa, jogadores e ficha de país (substitui a versão 1.x, que revelava uma imagem ao passar o rato).
