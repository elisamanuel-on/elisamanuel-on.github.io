// ============================================
// EXERCÍCIO 014 - JOGO DA ADIVINHA
// ============================================

console.log('Jogo da Adivinha carregado!');

// 1. VARIÁVEIS DO JOGO
let numeroSecreto = 0;
let tentativas = 0;
let jogoAtivo = true;

// 2. CAPTURAR ELEMENTOS
const palpiteInput = document.getElementById('palpite');
const adivinharBtn = document.getElementById('adivinharBtn');
const reiniciarBtn = document.getElementById('reiniciarBtn');
const erroDiv = document.getElementById('erro');

const numeroSecretoEl = document.getElementById('numeroSecreto');
const tentativasEl = document.getElementById('tentativas');
const dicaTexto = document.getElementById('dicaTexto');
const listaTentativas = document.getElementById('listaTentativas');

// 3. FUNÇÃO PARA GERAR NÚMERO ALEATÓRIO
function gerarNumeroSecreto() {
    return Math.floor(Math.random() * 50) + 1;
}

// 4. FUNÇÃO PARA INICIAR JOGO
function iniciarJogo() {
    numeroSecreto = gerarNumeroSecreto();
    tentativas = 0;
    jogoAtivo = true;
    
    numeroSecretoEl.textContent = '???';
    numeroSecretoEl.style.color = '#667eea';
    tentativasEl.textContent = '0';
    dicaTexto.textContent = 'Digite um número e tente adivinhar!';
    dicaTexto.className = '';
    listaTentativas.innerHTML = '<span style="color: #4a5568;">Nenhuma tentativa ainda...</span>';
    palpiteInput.value = '';
    palpiteInput.disabled = false;
    adivinharBtn.disabled = false;
    adivinharBtn.textContent = 'Adivinhar';
    
    console.log(`Novo número secreto gerado: ${numeroSecreto}`);
    console.log('Dica: está entre 1 e 50!');
}

// 5. FUNÇÃO PARA MOSTRAR ERRO
function mostrarErro(msg) {
    erroDiv.textContent = msg;
    erroDiv.classList.add('show');
    setTimeout(() => erroDiv.classList.remove('show'), 4000);
}

// 6. FUNÇÃO PARA ADIVINHAR
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

    // Tentativa válida
    tentativas++;
    tentativasEl.textContent = tentativas;

    // Verificar palpite
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
        adivinharBtn.textContent = 'Venceu!';
        acertou = true;
        console.log(`Acertou! Número: ${numeroSecreto}, Tentativas: ${tentativas}`);
    } else if (palpite < numeroSecreto) {
        mensagem = 'Mais acima! Tente um número maior.';
        dicaTexto.textContent = mensagem;
        dicaTexto.className = '';
        console.log(`Palpite ${palpite} é menor que ${numeroSecreto}`);
    } else {
        mensagem = 'Mais abaixo! Tente um número menor.';
        dicaTexto.textContent = mensagem;
        dicaTexto.className = '';
        console.log(`Palpite ${palpite} é maior que ${numeroSecreto}`);
    }

    // Adicionar ao histórico
    adicionarHistorico(palpite, acertou);
    palpiteInput.value = '';
    palpiteInput.focus();
}

// 7. FUNÇÃO PARA ADICIONAR AO HISTÓRICO
function adicionarHistorico(palpite, acertou) {
    const item = document.createElement('span');
    item.className = `tentativa-item ${acertou ? 'correto' : 'errado'}`;
    item.textContent = `${palpite}`;
    
    // Remove a mensagem "Nenhuma tentativa..."
    if (listaTentativas.children.length === 1 && listaTentativas.children[0].tagName === 'SPAN') {
        listaTentativas.innerHTML = '';
    }
    
    listaTentativas.appendChild(item);
    // Scroll para o último item
    item.scrollIntoView({ behavior: 'smooth', block: 'end' });
}

// 8. FUNÇÃO PARA REINICIAR
function reiniciarJogo() {
    iniciarJogo();
    console.log('Jogo reiniciado!');
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
    reiniciarJogo();
});

// 10. INICIAR JOGO
iniciarJogo();
console.log('Jogo pronto para usar!');
console.log('Digite um número entre 1 e 50 e clique em "Adivinhar"');