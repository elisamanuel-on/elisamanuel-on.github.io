// ============================================
// EXERCÍCIO 018 - JOGO DA ADIVINHA COM TOGGLE
// ============================================

console.log('Jogo da Adivinha carregado!');

// Ícones SVG (substituem emojis — cor herdada via currentColor)
const ICONE_SOL = '<svg class="icon" style="width:1em;height:1em;vertical-align:-0.125em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>';
const ICONE_LUA = '<svg class="icon" style="width:1em;height:1em;vertical-align:-0.125em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>';
const ICONE_CHECK = '<svg class="icon" style="width:1em;height:1em;vertical-align:-0.125em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
const ICONE_TROFEU = '<svg class="icon" style="width:1em;height:1em;vertical-align:-0.125em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/></svg>';

// 1. VARIÁVEIS DO JOGO
let numeroSecreto = 0;
let tentativas = 0;
let jogoAtivo = true;
let modoEscuro = true;

// 2. CAPTURAR ELEMENTOS
const palpiteInput = document.getElementById('palpite');
const adivinharBtn = document.getElementById('adivinharBtn');
const reiniciarBtn = document.getElementById('reiniciarBtn');
const toggleBtn = document.getElementById('toggleMode');
const erroDiv = document.getElementById('erro');
const body = document.body;

const numeroSecretoEl = document.getElementById('numeroSecreto');
const tentativasEl = document.getElementById('tentativas');
const dicaTexto = document.getElementById('dicaTexto');
const listaTentativas = document.getElementById('listaTentativas');

// 3. FUNÇÃO PARA ALTERNAR MODO
function alternarModo() {
    modoEscuro = !modoEscuro;
    
    if (modoEscuro) {
        body.classList.remove('light-mode');
        toggleBtn.innerHTML = `<span class="toggle-icone">${ICONE_LUA}</span> Dark Mode`;
        console.log('Mudou para Dark Mode');
    } else {
        body.classList.add('light-mode');
        toggleBtn.innerHTML = `<span class="toggle-icone">${ICONE_SOL}</span> Light Mode`;
        console.log('Mudou para Light Mode');
    }
}

// 4. FUNÇÃO PARA GERAR NÚMERO ALEATÓRIO
function gerarNumeroSecreto() {
    return Math.floor(Math.random() * 50) + 1;
}

// 5. FUNÇÃO PARA INICIAR JOGO
function iniciarJogo() {
    numeroSecreto = gerarNumeroSecreto();
    tentativas = 0;
    jogoAtivo = true;
    
    numeroSecretoEl.textContent = '???';
    numeroSecretoEl.style.color = '#667eea';
    tentativasEl.textContent = '0';
    dicaTexto.textContent = 'Digite um número e tente adivinhar!';
    dicaTexto.className = '';
    listaTentativas.innerHTML = '<span style="color: var(--cor-texto-secundario);">Nenhuma tentativa ainda...</span>';
    palpiteInput.value = '';
    palpiteInput.disabled = false;
    adivinharBtn.disabled = false;
    adivinharBtn.innerHTML = `${ICONE_CHECK} Adivinhar`;

    console.log(`Novo número secreto gerado: ${numeroSecreto}`);
    console.log('Dica: está entre 1 e 50!');
}

// 6. FUNÇÃO PARA MOSTRAR ERRO
function mostrarErro(msg) {
    erroDiv.textContent = msg;
    erroDiv.classList.add('show');
    setTimeout(() => erroDiv.classList.remove('show'), 4000);
}

// 7. FUNÇÃO PARA ADIVINHAR
function adivinhar() {
    if (!jogoAtivo) {
        mostrarErro('O jogo já acabou! Clique em "Novo Jogo" para recomeçar.');
        return;
    }

    const valor = palpiteInput.value.trim();
    
    if (valor === '') {
        mostrarErro('Digite um número!');
        return;
    }

    const palpite = Number(valor);

    if (isNaN(palpite) || palpite < 1 || palpite > 50) {
        mostrarErro('Digite um número entre 1 e 50!');
        palpiteInput.value = '';
        return;
    }

    tentativas++;
    tentativasEl.textContent = tentativas;

    let mensagem = '';
    let acertou = false;

    if (palpite === numeroSecreto) {
        mensagem = `PARABÉNS! Acertou o número ${numeroSecreto} em ${tentativas} tentativas!`;
        dicaTexto.textContent = mensagem;
        dicaTexto.className = 'acertou';
        numeroSecretoEl.textContent = numeroSecreto;
        numeroSecretoEl.style.color = '#48bb78';
        jogoAtivo = false;
        palpiteInput.disabled = true;
        adivinharBtn.disabled = true;
        adivinharBtn.innerHTML = `${ICONE_TROFEU} Venceu!`;
        acertou = true;
        console.log(`Acertou! Número: ${numeroSecreto}, Tentativas: ${tentativas}`);
    } else if (palpite < numeroSecreto) {
        mensagem = 'Mais acima! Tente um número maior.';
        dicaTexto.textContent = mensagem;
        console.log(`Palpite ${palpite} é menor que ${numeroSecreto}`);
    } else {
        mensagem = 'Mais abaixo! Tente um número menor.';
        dicaTexto.textContent = mensagem;
        console.log(`Palpite ${palpite} é maior que ${numeroSecreto}`);
    }

    adicionarHistorico(palpite, acertou);
    palpiteInput.value = '';
    palpiteInput.focus();
}

// 8. FUNÇÃO PARA ADICIONAR AO HISTÓRICO
function adicionarHistorico(palpite, acertou) {
    const item = document.createElement('span');
    item.className = `tentativa-item ${acertou ? 'correto' : 'errado'}`;
    item.textContent = `${palpite}`;
    
    if (listaTentativas.children.length === 1 && listaTentativas.children[0].tagName === 'SPAN') {
        listaTentativas.innerHTML = '';
    }
    
    listaTentativas.appendChild(item);
    item.scrollIntoView({ behavior: 'smooth', block: 'end' });
}

// 9. EVENTOS
adivinharBtn.addEventListener('click', function(e) {
    e.preventDefault();
    adivinhar();
});

palpiteInput.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        adivinhar();
    }
});

reiniciarBtn.addEventListener('click', function(e) {
    e.preventDefault();
    iniciarJogo();
});

toggleBtn.addEventListener('click', function(e) {
    e.preventDefault();
    alternarModo();
});

// 10. INICIAR JOGO
iniciarJogo();
console.log('Jogo pronto para usar!');
console.log('Digite um número entre 1 e 50');
console.log('Clique em "Dark Mode" para alternar para Light Mode');