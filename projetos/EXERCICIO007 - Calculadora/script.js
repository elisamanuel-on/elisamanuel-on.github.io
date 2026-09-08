console.log('Calculadora carregada!');

// 1. CAPTURAR ELEMENTOS
const num1Input = document.getElementById('num1');
const num2Input = document.getElementById('num2');
const calcularBtn = document.getElementById('calcularBtn');
const errorMessage = document.getElementById('errorMessage');

const somaResult = document.getElementById('somaResult');
const multiplicacaoResult = document.getElementById('multiplicacaoResult');
const comparacaoResult = document.getElementById('comparacaoResult');

console.log('Elementos encontrados');

// 2. FUNÇÃO PARA MOSTRAR ERRO
function mostrarErro(mensagem) {
    errorMessage.textContent = mensagem;
    errorMessage.classList.add('show');
    console.log('Erro: ' + mensagem);
    
    setTimeout(() => {
        errorMessage.classList.remove('show');
    }, 4000);
}

// 3. FUNÇÃO PARA ESCONDER ERRO
function esconderErro() {
    errorMessage.classList.remove('show');
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
    console.log('Números válidos: ' + num1 + ' e ' + num2);
    return { num1, num2 };
}

// 5. FUNÇÃO PARA FORMATAR NÚMEROS
function formatarNumero(num) {
    if (Number.isInteger(num)) {
        return num.toString();
    }
    return num.toFixed(2);
}

// 6. FUNÇÃO PRINCIPAL PARA CALCULAR
function calcular() {
    console.log('Botão clicado!');

    const validacao = validarInputs();
    if (!validacao) {
        console.log('Validação falhou');
        return;
    }

    const { num1, num2 } = validacao;

    // CÁLCULOS
    const soma = num1 + num2;
    const multiplicacao = num1 * num2;
    
    let comparacao = '';
    if (num1 > num2) {
        comparacao = num1 + ' é maior que ' + num2;
    } else if (num1 < num2) {
        comparacao = num1 + ' é menor que ' + num2;
    } else {
        comparacao = num1 + ' é igual a ' + num2;
    }

    console.log(' Resultados: Soma=' + soma + ', Multiplicação=' + multiplicacao);

    // EXIBIR RESULTADOS
    somaResult.innerHTML = `
        <span class="result-value">${formatarNumero(soma)}</span>
        <span style="color: #4a5568; font-size: 0.85rem; margin-left: 10px;">
            (${formatarNumero(num1)} + ${formatarNumero(num2)})
        </span>
    `;

    multiplicacaoResult.innerHTML = `
        <span class="result-value">${formatarNumero(multiplicacao)}</span>
        <span style="color: #4a5568; font-size: 0.85rem; margin-left: 10px;">
            (${formatarNumero(num1)} × ${formatarNumero(num2)})
        </span>
    `;

    comparacaoResult.innerHTML = `
        <span class="result-value">${comparacao}</span>
    `;

    console.log(' Resultados exibidos com sucesso!');
}

// 7. ADICIONAR EVENTOS

// Clique no botão
calcularBtn.addEventListener('click', function(e) {
    e.preventDefault();
    console.log(' Clique detectado no botão');
    calcular();
});

// Tecla Enter
num1Input.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        console.log(' Enter no campo 1');
        num2Input.focus();
    }
});

num2Input.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        console.log('⌨️ Enter no campo 2');
        calcularBtn.click();
    }
});

// Limpar erro ao digitar
num1Input.addEventListener('input', esconderErro);
num2Input.addEventListener('input', esconderErro);

console.log(' Calculadora pronta para usar!');
console.log(' Digite números e clique em "Calcular"');