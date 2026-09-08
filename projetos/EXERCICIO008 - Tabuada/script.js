console.log('Tabuada carregada!');

const numeroInput = document.getElementById('numero');
const calcularBtn = document.getElementById('calcular');
const erroDiv = document.getElementById('erro');
const numTabuada = document.getElementById('numTabuada');
const tabuadaLista = document.getElementById('tabuadaLista');

function mostrarErro(msg) {
    erroDiv.textContent = msg;
    erroDiv.classList.add('show');
    setTimeout(() => erroDiv.classList.remove('show'), 4000);
}

function calcularTabuada() {
    const valor = numeroInput.value.trim();

    if (valor === '') {
        mostrarErro('Digite um número!');
        return;
    }

    const num = Number(valor);

    if (isNaN(num) || num < 0) {
        mostrarErro('Digite um número válido e positivo!');
        return;
    }

    erroDiv.classList.remove('show');
    numTabuada.textContent = num;

    let html = '';
    for (let i = 1; i <= 10; i++) {
        const resultado = num * i;
        html += `
            <div class="tabuada-item">
                <span class="operacao">${num} × ${i}</span>
                <span class="resultado">= ${resultado}</span>
            </div>
        `;
    }

    tabuadaLista.innerHTML = html;
    console.log(`Tabuada do ${num} exibida!`);
}

calcularBtn.addEventListener('click', calcularTabuada);

numeroInput.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') calcularBtn.click();
});

console.log('Digite um número e clique em "Calcular"');