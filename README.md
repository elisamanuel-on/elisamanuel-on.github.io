# Portfólio | Elisama Manuel

Portfólio pessoal de desenvolvedora, com identidade de programadora (site dentro de uma janela de terminal, boot animado, base de dados interativa), **18 projetos** organizados em 4 grupos e um formulário de contacto ligado diretamente ao Gmail. Disponível em português, inglês, espanhol e francês.

**Site ao vivo:** https://elisamanuel-on.github.io/
**LinkedIn:** https://www.linkedin.com/in/elisama-manuel-49025117a/
**GitHub:** https://github.com/elisamanuel-on

## Stack técnica do site

- **Frontend:** HTML + CSS + JavaScript puro (sem frameworks), com temas escuro e claro via CSS custom properties.
- **Internacionalização:** sistema de tradução próprio (`i18n.js`), com deteção automática do idioma do navegador. Textos, projetos e a base de dados interativa estão nos 4 idiomas.
- **Janela de terminal:** o site inteiro vive dentro de uma janela, com barra de título fixa; os três pontos (vermelho, âmbar, verde) são os botões de Perfil, Portfólio e Contacto. Animação de arranque na página inicial (`terminal-core.js`).
- **Base de dados interativa:** no Perfil, uma consola ao estilo sqlite (`consola.js`) com separadores (projetos, tecnologias, experiência, formação) e comandos como `.help`, `.tables` e `select ... from ...;`, construída a partir dos dados do próprio site.
- **Formulário de contacto:** envia diretamente para o Gmail via Google Apps Script (`MailApp.sendEmail`), sem serviços de terceiros.
- **Analítica:** [GoatCounter](https://www.goatcounter.com/), estatísticas de visitas simples e sem cookies.
- **Alojamento:** GitHub Pages para o site e para os projetos em JavaScript; Render (Docker com PHP 8.3 + Apache, ver `Dockerfile` e `render.yaml`) para os projetos em PHP, que precisam de servidor.
- **Cache:** os ficheiros levam `?v=` com a data da versão, para o navegador não mostrar versões antigas.

## Estrutura

```
├── index.html          → perfil (apresentação, competências, experiência, formação)
├── portfolio.html      → os 18 projetos, por grupos
├── contacto.html       → formulário de contacto
├── consola.js          → consola "base de dados" do Perfil
├── terminal.html       → redireciona para index.html#base-de-dados
├── style.css           → tema visual (verde e âmbar, inspirado num terminal clássico)
├── script.js           → dados (competências, experiência, formação, projetos) e interface
├── i18n.js             → traduções e deteção de idioma
├── terminal-core.js    → animação de arranque (boot)
├── Dockerfile          → servidor PHP + Apache para os projetos em PHP (Render)
├── render.yaml         → configuração do serviço no Render
├── cv/                 → PDF do currículo
├── imagens/            → fotografia e capturas dos projetos
└── projetos/           → código-fonte dos projetos que vivem neste repositório
```

## Projetos

Cada cartão do portfólio mostra a captura, uma descrição e as tecnologias usadas. Os números (projetos, tecnologias e experiências no topo do perfil, ) saem das listas em `script.js`, por isso nunca ficam desatualizados.

### Aplicações completas em Python (6)

- **[Sistema de Gestão de Utentes e Cuidados de Saúde](https://sistema-gestao-saude.onrender.com)** — cuidados continuados (UCC, ERPI, SAD) e ambulatório, com avaliação de risco por machine learning (diabetes, cardiovascular e queda), acesso por perfil (Enfermeiro, Médico, Receção, Admin) e relatórios em PDF e Excel. *Python, Dash, Machine Learning, PDF, Excel.*
- **[Controlo de Gastos](https://controlo-de-gastos.onrender.com)** ([código](https://github.com/elisamanuel-on/controlo-de-gastos)) — despesas e receitas pessoais, com utilizadores autenticados, categorias personalizáveis, gráficos, testes automáticos e deployment contínuo. *Python, FastAPI, PostgreSQL, SQLite, SQLAlchemy, JWT, pytest, CI/CD.*
- **[Delivery Orientado a Eventos](https://delivery-eventos.onrender.com)** ([código](https://github.com/elisamanuel-on/delivery-eventos)) — simulação de delivery com arquitetura orientada a eventos (Pagamento, Cozinha, Entrega) e acompanhamento em tempo real. *Python, FastAPI, WebSocket, asyncio, pytest.*
- **[Monitor de Vagas e Notícias](https://monitor-vagas-noticias.onrender.com)** — painel diário com vagas reais da API da ITJobs e notícias de tecnologia, estatísticas de evolução, respostas a candidaturas e um executável nativo para Windows; robôs automáticos com GitHub Actions. *Python, FastAPI, MongoDB, API ITJobs, GitHub Actions.*
- **[Análise de Preços de Habitação em Portugal](https://analise-precos-habitacao.onrender.com)** — dashboard com dados reais do INE: mapa de preços por concelho, pesquisa, comparação de regiões, evolução do índice desde 2009 e exportação em CSV e Excel. *Python, Dash, Pandas.*
- **[Portal de Automação Interna](https://portal-automacao-interna.onrender.com)** — portal para automatizar tarefas de escritório (organizar ficheiros, gerar relatórios, fazer backups), com histórico de execuções e agendamento. *Python, FastAPI, cron, RPA.*

### Sistemas e jogos web em PHP e JavaScript (5)

- **[Sistema de Gestão Escolar](https://portfolio-php-p3pd.onrender.com/projetos/EXERCICIO009%20-%20Registo%20de%20Utilizador/)** — colégio com 7 perfis (aluno, professor, secretaria, direção, Conselho Geral, contabilidade e portaria): notas, pautas, faltas, horários e a parte financeira (propinas, salários, impostos, despesas e receitas), com cálculos automáticos, registo de alterações e relatórios em Excel e PDF feitos em PHP puro, em 4 idiomas. *PHP, SQLite, PDO, JavaScript, CSS.*
- **[Calculadora Científica](https://portfolio-php-p3pd.onrender.com/projetos/EXERCICIO013%20-%20Calculadora%20com%204%20Opera%C3%A7%C3%B5es/)** — seis operações, modo científico, desafios por níveis e manual; as contas são feitas no servidor, sem `eval()`. *PHP, HTML, CSS, JavaScript.*
- **[Imagem Escondida](projetos/EXERCICIO012%20-%20Imagem%20Escondida/index.html)** — jogo de geografia em 3 níveis com cronómetro e vidas (continentes, bandeiras, capitais e curiosidades), mapa clicável com os nomes na língua do jogo, ficha de cada país com o seu nome verdadeiro, jogadores e ranking. *JavaScript, Leaflet, TopoJSON, OpenStreetMap, API da Wikipédia.*
- **[Tabuada em Jogos](projetos/EXERCICIO008%20-%20Tabuada/index.html)** — jogos para crianças treinarem a tabuada: labirinto, quiz com tempo, caça aos múltiplos e memória. *JavaScript, HTML, CSS, SVG, Web Audio.*
- **[Curiosidades do Mundo](projetos/EXERCICIO011%20-%20Curiosidades%20Escondidas/index.html)** — curiosidades reais da Wikipédia por áreas, com mais dados para revelar ao clicar. *JavaScript, HTML, CSS, API da Wikipédia.*

### Templates e quiosques (3)

Interfaces prontas a adaptar a um negócio. *HTML, CSS, JavaScript.*

- **[Aro Alto](https://template-interativo.onrender.com)** — template com catálogo interativo, calculadora de orçamento, marcação de horários e formulário de contacto, aqui como site de uma academia de basquetebol fictícia.
- **[Sole House](https://sole-house.onrender.com)** — template institucional de uma página, aqui como loja de sneakers fictícia.
- **[Quiosques Interativos: Museu Aurora e Mesa Lume](https://demo-wingsys.onrender.com)** — duas demonstrações para ecrãs táteis: guia de museu e menu de restaurante com pedidos e botão para chamar o empregado, em PT e EN.

### Exercícios de base (4)

Os primeiros passos em JavaScript, HTML e CSS, durante a formação em Programação Web (Client-Side).

- **[Jogo da Adivinha](projetos/EXERCICIO014%20-%20Jogo%20da%20Adivinha/index.html)** — adivinhar um número entre 1 e 50, com dicas e contagem de tentativas.
- **[Jogo da Adivinha com Toggle](projetos/EXERCICIO018%20-%20Jogo%20da%20Adivinha%20com%20Toggle/index.html)** — o mesmo jogo, com modo escuro e modo claro.
- **[Números Primos](projetos/EXERCICIO017%20-%20N%C3%BAmeros%20Primos/index.html)** — verifica se um número é primo e lista todos os primos até ele.
- **[Breakpoints](projetos/EXERCICIO019%20-%20Breakpoints/index.html)** — página responsiva com media queries e indicador do breakpoint atual.

> Os projetos alojados no Render gratuito adormecem quando ninguém os usa: a primeira abertura pode demorar cerca de 50 segundos.

## Competências

- **Linguagens:** Python, JavaScript, PHP, Java, HTML e CSS
- **Frameworks:** FastAPI, Flask, Dash
- **Dados e automação:** Pandas, RPA, Machine Learning
- **Bases de dados:** MySQL, PostgreSQL, SQL Server, SQLite, MongoDB
- **Sistemas e ferramentas:** Git, Linux, Ubuntu, Proxmox, VS Code
- **Gestão e negócio:** Excel Avançado, SAP (ERP), Canva, Gestão de Projetos, Atendimento ao Cliente

## Como correr localmente

O site e os projetos em JavaScript não precisam de build nem de instalação: abre `index.html` num navegador, ou serve a pasta:

```bash
python -m http.server 8000
```

Os projetos em PHP (`projetos/EXERCICIO009…` e `EXERCICIO013…`) precisam do PHP instalado. Dentro da pasta do projeto:

```bash
php -S localhost:8000
```

## Como acrescentar um projeto

1. Põe a captura em `imagens/` e a pasta do projeto em `projetos/` (ou usa o endereço online).
2. Em `script.js`, acrescenta um item à lista `projetos` com `grupo` (`python`, `web`, `templates` ou `base`), o título e a descrição nos 4 idiomas e a lista `tecnologias`.
3. Sobe o `?v=` nos `.html` para o navegador buscar os ficheiros novos.

Os números do site atualizam-se sozinhos.

---
Desenvolvido por Elisama Manuel, Técnica Especialista em Tecnologias e Programação de Sistemas de Informação (IEFP), em transição de Finanças e Contabilidade para Tecnologia.
