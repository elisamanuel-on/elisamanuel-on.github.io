// ============================================
// EXERCÍCIO 017 - NÚMEROS PRIMOS
// ============================================

console.log('Números Primos carregado!');

// 1. CAPTURAR ELEMENTOS
const numeroInput = document.getElementById('numeroInput');
const calcularBtn = document.getElementById('calcularBtn');
const limparBtn = document.getElementById('limparBtn');
const erroDiv = document.getElementById('erro');

const numeroAnalisado = document.getElementById('numeroAnalisado');
const statusPrimo = document.getElementById('statusPrimo');
const listaPrimos = document.getElementById('listaPrimos');
const resumoPrimos = document.getElementById('resumoPrimos');

// 2. FUNÇÃO PARA MOSTRAR ERRO
function mostrarErro(msg) {
    erroDiv.textContent = msg;
    erroDiv.classList.add('show');
    setTimeout(() => erroDiv.classList.remove('show'), 4000);
}

// 3. FUNÇÃO PARA ESCONDER ERRO
function esconderErro() {
    erroDiv.classList.remove('show');
}

// 4. FUNÇÃO PARA VERIFICAR SE É PRIMO
function isPrimo(num) {
    if (num < 2) return false;
    if (num === 2) return true;
    if (num % 2 === 0) return false;
    
    // Verificar divisores até a raiz quadrada
    for (let i = 3; i <= Math.sqrt(num); i += 2) {
        if (num % i === 0) {
            return false;
        }
    }
    return true;
}

// 5. FUNÇÃO PARA ENCONTRAR TODOS OS PRIMOS ATÉ N
function encontrarPrimos(limite) {
    const primos = [];
    for (let i = 2; i <= limite; i++) {
        if (isPrimo(i)) {
            primos.push(i);
        }
    }
    return primos;
}

// 6. FUNÇÃO PRINCIPAL
function calcularPrimos() {
    console.log('A calcular primos...');

    const valor = numeroInput.value.trim();

    if (valor === '') {
        mostrarErro('Por favor, digite um número!');
        return;
    }

    const num = Number(valor);

    if (isNaN(num) || num < 2) {
        mostrarErro('Digite um número inteiro maior ou igual a 2!');
        numeroInput.value = '';
        return;
    }

    if (!Number.isInteger(num)) {
        mostrarErro('Digite um número inteiro!');
        numeroInput.value = '';
        return;
    }

    esconderErro();
    console.log(`Número analisado: ${num}`);

    // Verificar se o número é primo
    const ehPrimo = isPrimo(num);

    // Encontrar todos os primos até o número
    const primos = encontrarPrimos(num);

    // 1. Número Analisado
    numeroAnalisado.textContent = num;
    numeroAnalisado.style.color = ehPrimo ? '#48bb78' : '#fc8181';

    // 2. Status
    if (ehPrimo) {
        statusPrimo.innerHTML = `<span class="primo">${num} é um número PRIMO!</span>`;
        console.log(`${num} é primo`);
    } else {
        statusPrimo.innerHTML = `<span class="nao-primo">${num} NÃO é um número primo!</span>`;
        console.log(`${num} não é primo`);
    }

    // 3. Lista de Primos
    if (primos.length === 0) {
        listaPrimos.innerHTML = '<span style="color: #4a5568;">Nenhum número primo encontrado.</span>';
    } else {
        let html = '';
        primos.forEach(p => {
            const isDestacado = (p === num);
            html += `<span class="primo-item ${isDestacado ? 'destacado' : ''}">${p}</span>`;
        });
        listaPrimos.innerHTML = html;
    }

    // 4. Resumo
    const totalPrimos = primos.length;
    let mensagemResumo = `Encontrados <strong>${totalPrimos}</strong> números primos`;
    if (ehPrimo) {
        mensagemResumo += `, incluindo o <strong>${num}</strong> que é primo!`;
    } else {
        mensagemResumo += `, mas o <strong>${num}</strong> não está na lista.`;
    }
    resumoPrimos.innerHTML = mensagemResumo;

    console.log(`Total de primos encontrados: ${totalPrimos}`);
}

// 7. FUNÇÃO PARA LIMPAR
function limpar() {
    numeroInput.value = '';
    esconderErro();
    
    numeroAnalisado.textContent = 'Aguardando número...';
    numeroAnalisado.style.color = '#e2e8f0';
    statusPrimo.innerHTML = 'Aguardando número...';
    listaPrimos.innerHTML = '<span style="color: #4a5568;">Aguardando número...</span>';
    resumoPrimos.textContent = 'Aguardando número...';
    
    console.log('Resultados limpos');
}

// 8. EVENTOS
calcularBtn.addEventListener('click', function(e) {
    e.preventDefault();
    calcularPrimos();
});

numeroInput.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        calcularBtn.click();
    }
});

limparBtn.addEventListener('click', function(e) {
    e.preventDefault();
    limpar();
});

numeroInput.addEventListener('input', esconderErro);

console.log('Calculadora de primos pronta para usar!');
console.log('Digite um número e veja todos os primos até ele');