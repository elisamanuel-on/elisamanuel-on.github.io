console.log('Registo carregado!');

// 1. CAPTURAR ELEMENTOS
const nomeInput = document.getElementById('nome');
const idadeInput = document.getElementById('idade');
const cursoInput = document.getElementById('curso');
const escolaInput = document.getElementById('escola');
const registarBtn = document.getElementById('registar');
const erroDiv = document.getElementById('erro');

const displayNome = document.getElementById('displayNome');
const displayIdade = document.getElementById('displayIdade');
const displayCurso = document.getElementById('displayCurso');
const displayEscola = document.getElementById('displayEscola');

// 2. FUNÇÃO PARA MOSTRAR ERRO
function mostrarErro(msg) {
    erroDiv.textContent = msg;
    erroDiv.classList.add('show');
    setTimeout(() => erroDiv.classList.remove('show'), 4000);
}

// 3. FUNÇÃO PARA REGISTAR
function registar() {
    const nome = nomeInput.value.trim();
    const idade = idadeInput.value.trim();
    const curso = cursoInput.value.trim();
    const escola = escolaInput.value.trim();

    // Validar
    if (!nome || !idade || !curso || !escola) {
        mostrarErro('Preencha todos os campos!');
        return;
    }

    if (isNaN(Number(idade)) || Number(idade) < 0) {
        mostrarErro('Idade inválida!');
        return;
    }

    // Mostrar dados
    displayNome.textContent = nome;
    displayNome.className = 'dado-valor preencher';
    
    displayIdade.textContent = idade + ' anos';
    displayIdade.className = 'dado-valor preencher';
    
    displayCurso.textContent = curso;
    displayCurso.className = 'dado-valor preencher';
    
    displayEscola.textContent = escola;
    displayEscola.className = 'dado-valor preencher';

    console.log(`Utilizador registado: ${nome}`);
}

// 4. EVENTOS
registarBtn.addEventListener('click', registar);

// Enter em qualquer campo
document.querySelectorAll('input').forEach(input => {
    input.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') registarBtn.click();
    });
});

console.log('Preencha os dados e clique em "Registar"');