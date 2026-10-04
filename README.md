# Portfólio | Elisama Manuel

Portfólio pessoal de desenvolvedora, com identidade de programadora (terminal interativo, boot animado), 12 projetos práticos e um formulário de contacto ligado diretamente ao Gmail.

**Site ao vivo:** https://elisamanuel-on.github.io/
**LinkedIn:** https://www.linkedin.com/in/elisama-manuel-49025117a/
**GitHub:** https://github.com/elisamanuel-on

## Stack técnica

- **Frontend:** HTML + CSS + JavaScript puro (sem frameworks), com sistema de temas (dark/light) via CSS custom properties
- **Internacionalização:** sistema de tradução próprio (`i18n.js`) - o site está disponível em Português, Inglês, Espanhol e Francês, com deteção automática do idioma do browser
- **Terminal interativo:** motor próprio (`terminal-core.js`) - animação de arranque na página inicial e uma consola funcional (`terminal.html`) com comandos reais (`help`, `sobre`, `skills`, `experiencia`, `projetos`, `contacto`, `cv`, `github`, `linkedin`, entre outros), também traduzidos por idioma
- **Formulário de contacto:** envia diretamente para o Gmail via Google Apps Script (`MailApp.sendEmail`), sem serviços de terceiros
- **Analítica:** [GoatCounter](https://www.goatcounter.com/) - estatísticas de visitas simples e sem cookies
- **Alojamento:** GitHub Pages

## Estrutura do site

```
├── index.html          → página de perfil (hero, experiência, formação)
├── portfolio.html       → grelha com os 12 projetos
├── contacto.html        → formulário de contacto
├── terminal.html        → terminal interativo
├── style.css             → tema visual (verde/âmbar, inspirado em terminal clássico)
├── script.js             → dados (experiência, projetos, competências) e lógica de UI
├── i18n.js               → traduções e deteção de idioma
├── terminal-core.js      → motor do boot e do terminal interativo
├── cv/                    → PDF do currículo
├── imagens/               → fotos e capturas dos projetos
└── projetos/              → pastas com o código-fonte de cada exercício
```

## Projetos em destaque

Os 12 projetos cobrem desde exercícios de lógica em JavaScript/HTML/CSS até aplicações full-stack com backend próprio:

- 10 exercícios práticos (calculadoras, jogos, formulários, responsividade) — código incluído neste mesmo repositório, em `projetos/`
- **[Controlo de Gastos](https://github.com/elisamanuel-on/controlo-de-gastos)** — aplicação full-stack (API em FastAPI + SQLite), com testes automáticos e deployment contínuo, publicada em [controlo-de-gastos.onrender.com](https://controlo-de-gastos.onrender.com)
- **[Delivery Orientado a Eventos](https://github.com/elisamanuel-on/delivery-eventos)** — simulação de delivery com arquitetura orientada a eventos (Pagamento → Cozinha → Entrega) e acompanhamento em tempo real via WebSocket, publicada em [delivery-eventos.onrender.com](https://delivery-eventos.onrender.com)

## Como correr localmente

Não precisa de build nem de instalação — é só abrir `index.html` num browser, ou servir a pasta com um servidor estático simples:

```bash
python -m http.server 8000
```

E aceder a `http://localhost:8000`.

---
Desenvolvido por Elisama Manuel - Técnica Especialista em Tecnologias e Programação de Sistemas de Informação (IEFP), em transição de Finanças & Contabilidade para Tecnologia.
