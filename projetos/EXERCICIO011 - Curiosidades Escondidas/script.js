// ============================================
// EXERCÍCIO 011 - CURIOSIDADES ESCONDIDAS
// ============================================

console.log('Curiosidades carregadas!');

// 1. CAPTURAR ELEMENTOS
const revelarBtn = document.getElementById('revelarBtn');
const erroDiv = document.getElementById('erro');

const curiosidade1 = document.getElementById('curiosidade1');
const curiosidade2 = document.getElementById('curiosidade2');
const curiosidade3 = document.getElementById('curiosidade3');

let curiosidadesReveladas = false;

// 2. FUNÇÃO PARA MOSTRAR ERRO
function mostrarErro(msg) {
    erroDiv.textContent = msg;
    erroDiv.classList.add('show');
    setTimeout(() => erroDiv.classList.remove('show'), 4000);
}

// 3. FUNÇÃO PARA REVELAR CURIOSIDADES
function revelarCuriosidades() {
    console.log('Botão clicado!');

    if (curiosidadesReveladas) {
        mostrarErro('As curiosidades já estão visíveis!');
        return;
    }

    // Revelar cada curiosidade com delay
    setTimeout(() => {
        curiosidade1.style.display = 'flex';
        console.log('Curiosidade 1 revelada');
    }, 100);

    setTimeout(() => {
        curiosidade2.style.display = 'flex';
        console.log('Curiosidade 2 revelada');
    }, 300);

    setTimeout(() => {
        curiosidade3.style.display = 'flex';
        console.log('Curiosidade 3 revelada');
        curiosidadesReveladas = true;
        revelarBtn.textContent = 'Curiosidades Reveladas!';
        revelarBtn.style.background = 'linear-gradient(135deg, #48bb78, #38a169)';
        console.log('Todas as curiosidades reveladas!');
    }, 500);
}

// 4. EVENTOS
revelarBtn.addEventListener('click', revelarCuriosidades);

console.log('Clica no botão para revelar as curiosidades!');