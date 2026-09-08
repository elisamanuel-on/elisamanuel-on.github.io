// ============================================
// EXERCÍCIO 012 - IMAGEM ESCONDIDA
// ============================================

console.log('Imagem Escondida carregada!');

// 1. CAPTURAR ELEMENTOS
const areaImagem = document.getElementById('areaImagem');
const imagem = document.getElementById('imagemRevelar');
const estadoTexto = document.getElementById('estadoTexto');

let imagemRevelada = false;

// 2. FUNÇÃO PARA ATUALIZAR ESTADO
function atualizarEstado(revelada) {
    if (revelada) {
        estadoTexto.textContent = 'Imagem revelada!';
        estadoTexto.className = 'visivel';
        console.log('Imagem revelada!');
    } else {
        estadoTexto.textContent = 'Imagem escondida';
        estadoTexto.className = 'escondido';
        console.log('Imagem escondida');
    }
}

// 3. FUNÇÃO PARA REVELAR IMAGEM
function revelarImagem() {
    if (!imagemRevelada) {
        imagem.classList.add('visivel');
        imagemRevelada = true;
        atualizarEstado(true);
        console.log('Rato passou sobre a área');
    }
}

// 4. FUNÇÃO PARA ESCONDER IMAGEM
function esconderImagem() {
    if (imagemRevelada) {
        imagem.classList.remove('visivel');
        imagemRevelada = false;
        atualizarEstado(false);
        console.log('Rato saiu da área');
    }
}

// 5. EVENTOS

// Quando o rato entra na área
areaImagem.addEventListener('mouseenter', function() {
    console.log('Mouse entrou na área');
    revelarImagem();
});

// Quando o rato sai da área
areaImagem.addEventListener('mouseleave', function() {
    console.log('Mouse saiu da área');
    esconderImagem();
});

// 6. MENSAGEM INICIAL
atualizarEstado(false);
console.log('Passa o rato sobre a área para revelar a imagem!');
console.log('Quando saíres, a imagem esconde-se novamente.');