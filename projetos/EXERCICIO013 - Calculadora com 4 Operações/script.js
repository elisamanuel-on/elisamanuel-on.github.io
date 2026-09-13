// ============================================
// EXERCÍCIO 013 - CALCULADORA 4 OPERAÇÕES
// ============================================

console.log('Calculadora 4 Operações carregada!');

// 1. CAPTURAR ELEMENTOS
const num1Input = document.getElementById('num1');
const num2Input = document.getElementById('num2');
const calcularBtn = document.getElementById('calcularBtn');
const erroDiv = document.getElementById('erro');

const somaResult = document.getElementById('somaResult');
const subtracaoResult = document.getElementById('subtracaoResult');
const multiplicacaoResult = document.getElementById('multiplicacaoResult');
const divisaoResult = document.getElementById('divisaoResult');

// 2. FUNÇÃO PARA MOSTRAR ERRO
function mostrarErro(msg) {
    erroDiv.textContent = ` ${msg}`;
    erroDiv.classList.add('show');
    setTimeout(() => erroDiv.classList.remove('show'), 4000);
}

// 3. FUNÇÃO PARA ESCONDER ERRO
function esconderErro() {
    erroDiv.classList.remove('show');
}

// 4. FUNÇÃO PARA VALIDAR INPUTS
function validarInputs() {
    const valor1 = num1Input.value.trim();
    const valor2 = num2Input.value.trim();

    if (valor1 === '' || valor2 === '') {
        mostrarErro('Por favor, preencha ambos os números!');
        return null;
    }

    const num1 = Number(valor1);
    const num2 = Number(valor2);

    if (isNaN(num1) || isNaN(num2)) {
        mostrarErro('Por favor, insira números válidos!');
        return null;
    }

    esconderErro();
    return { num1, num2 };
}

// 5. FUNÇÃO PARA FORMATAR NÚMEROS
function formatarNumero(num) {
    if (Number.isInteger(num)) {
        return num.toString();
    }
    return num.toFixed(2);
}

// 6. FUNÇÃO PRINCIPAL
function calcular() {
    console.log('Botão clicado!');

    const validacao = validarInputs();
    if (!validacao) {
        console.log('Validação falhou');
        return;
    }

    const { num1, num2 } = validacao;
    console.log(`Números: ${num1} e ${num2}`);

    // CÁLCULOS
    const soma = num1 + num2;
    const subtracao = num1 - num2;
    const multiplicacao = num1 * num2;
    let divisao = '';

    // Verificar divisão por zero
    if (num2 === 0) {
        divisao = 'Divisão por zero!';
        console.log('Divisão por zero detectada');
    } else {
        divisao = formatarNumero(num1 / num2);
        console.log(`Divisão: ${num1} / ${num2} = ${divisao}`);
    }

    // EXIBIR RESULTADOS
    somaResult.innerHTML = `
        <span class="valor">${formatarNumero(soma)}</span>
        <span style="color: #4a5568; font-size: 0.8rem; margin-left: 8px;">
            (${formatarNumero(num1)} + ${formatarNumero(num2)})
        </span>
    `;

    subtracaoResult.innerHTML = `
        <span class="valor">${formatarNumero(subtracao)}</span>
        <span style="color: #4a5568; font-size: 0.8rem; margin-left: 8px;">
            (${formatarNumero(num1)} - ${formatarNumero(num2)})
        </span>
    `;

    multiplicacaoResult.innerHTML = `
        <span class="valor">${formatarNumero(multiplicacao)}</span>
        <span style="color: #4a5568; font-size: 0.8rem; margin-left: 8px;">
            (${formatarNumero(num1)} × ${formatarNumero(num2)})
        </span>
    `;

    divisaoResult.innerHTML = `
        <span class="valor">${divisao}</span>
        <span style="color: #4a5568; font-size: 0.8rem; margin-left: 8px;">
            (${formatarNumero(num1)} ÷ ${formatarNumero(num2)})
        </span>
    `;

    console.log('Resultados exibidos com sucesso!');
}

// 7. EVENTOS
calcularBtn.addEventListener('click', function(e) {
    e.preventDefault();
    console.log('Clique detectado');
    calcular();
});

num1Input.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        num2Input.focus();
    }
});

num2Input.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        calcularBtn.click();
    }
});

num1Input.addEventListener('input', esconderErro);
num2Input.addEventListener('input', esconderErro);

console.log('Calculadora pronta para usar!');
console.log('Digite dois números e clique em "Calcular"');