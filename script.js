// ============================================
// TEMPLATE "INTERATIVO" Academia de Basquetebol
// ============================================
// Catálogo de programas, simulador de mensalidade em tempo
// real, marcação de aula experimental, e um formulário final
// que junta tudo e envia a inscrição.
//
// Para ligar o envio de emails a sério, cria uma conta grátis em
// https://formspree.io, cria um formulário e troca o valor de
// FORMSPREE_URL aqui em baixo pelo endpoint que eles te derem.
// Enquanto não trocares, o formulário usa "mailto" como alternativa
// (abre o email do visitante já preenchido).
// ============================================

const FORMSPREE_URL = 'https://formspree.io/f/XXXXXXXX'; // <- troca aqui
const EMAIL_NEGOCIO = 'geral@aroalto.pt'; // <- troca aqui (usado no fallback mailto)

// ------------------------------------------------
// Ícones (SVG reutilizáveis, sem emojis)
// ------------------------------------------------
const ICONES_SERVICO = {
    bola: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2v20M4.5 4.5c3 3 3 12 0 15M19.5 4.5c-3 3-3 12 0 15"/></svg>',
    apito: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 8h8a4 4 0 0 1 0 8h-2l-2 3-2-3H8a4 4 0 0 1 0-8z"/><circle cx="16" cy="12" r="1.3"/></svg>',
    camisola: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 4 4 7l2 3 2-1v11h8V9l2 1 2-3-4-3-2 2h-2z"/></svg>',
    cronometro: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="13" r="8"/><path d="M12 9v4l3 2M9 2h6"/></svg>'
};

// ------------------------------------------------
// Catálogo de programas — dados de exemplo
// ------------------------------------------------
const catalogoServicos = [
    {
        id: 1, categoria: 'Formação Jovem', nome: 'Mini-Basket (6-9 anos)',
        preco: 30, tipo: 'mensal', icone: 'bola',
        descricao: 'Iniciação lúdica ao basquetebol — coordenação, regras básicas e muita diversão.'
    },
    {
        id: 2, categoria: 'Formação Jovem', nome: 'Sub-12 (10-12 anos)',
        preco: 35, tipo: 'mensal', icone: 'apito',
        descricao: 'Fundamentos técnicos e táticos, com primeiros jogos e torneios de formação.'
    },
    {
        id: 3, categoria: 'Formação Jovem', nome: 'Sub-16 (13-16 anos)',
        preco: 40, tipo: 'mensal', icone: 'camisola',
        descricao: 'Treino de competição, preparação física e participação no campeonato distrital.'
    },
    {
        id: 4, categoria: 'Adultos', nome: 'Sénior / Adultos',
        preco: 35, tipo: 'mensal', icone: 'bola',
        descricao: 'Treino livre para adultos de todos os níveis — condição física e jogo em equipa.'
    },
    {
        id: 5, categoria: 'Adultos', nome: 'Veteranos 35+',
        preco: 30, tipo: 'mensal', icone: 'apito',
        descricao: 'Ritmo adaptado, foco em bem-estar, técnica e convívio dentro de campo.'
    },
    {
        id: 6, categoria: 'Individual', nome: 'Treino Personalizado 1-a-1',
        preco: 60, tipo: 'mensal', icone: 'cronometro',
        descricao: 'Sessão individual com treinador dedicado — plano de progressão à medida.'
    },
    {
        id: 7, categoria: 'Equipamento', nome: 'Kit Completo Aro Alto',
        preco: 45, tipo: 'unico', icone: 'camisola',
        descricao: 'Camisola oficial + calções do clube, com nome e número personalizados.'
    },
    {
        id: 8, categoria: 'Equipamento', nome: 'Camisola Avulso',
        preco: 28, tipo: 'unico', icone: 'camisola',
        descricao: 'Apenas a camisola oficial, para quem já tem equipamento de treino.'
    }
];

const categorias = ['Todos', ...new Set(catalogoServicos.map(s => s.categoria))];

// ------------------------------------------------
// Estado
// ------------------------------------------------
const estado = {
    servicosSelecionados: new Set(),
    categoriaAtiva: 'Todos',
    dataEscolhida: null,
    horaEscolhida: null,
    inscricaoEnviada: false
};

// ------------------------------------------------
// Ano automático + menu mobile
// ------------------------------------------------
document.getElementById('anoAtual').textContent = new Date().getFullYear();

const btnMenu = document.getElementById('btnMenu');
const navPrincipal = document.getElementById('navPrincipal');
btnMenu.addEventListener('click', () => {
    const aberto = navPrincipal.classList.toggle('aberto');
    btnMenu.setAttribute('aria-expanded', aberto ? 'true' : 'false');
});
navPrincipal.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', () => navPrincipal.classList.remove('aberto'));
});

// ------------------------------------------------
// Animação de entrada ao scroll (dá vida à página)
// ------------------------------------------------
const observadorRevelacao = new IntersectionObserver((entradas) => {
    entradas.forEach(entrada => {
        if (entrada.isIntersecting) {
            entrada.target.classList.add('visivel');
            observadorRevelacao.unobserve(entrada.target);
        }
    });
}, { threshold: 0.12 });

function ativarRevelacao(elementos) {
    elementos.forEach(el => {
        el.classList.add('reveal');
        observadorRevelacao.observe(el);
    });
}

// ------------------------------------------------
// Indicador de progresso (stepper)
// ------------------------------------------------
const stepEls = document.querySelectorAll('.step');
const linhaEls = document.querySelectorAll('.step-linha');

function atualizarStepper() {
    const passo1 = estado.servicosSelecionados.size > 0; // Programas escolhidos
    const passo2 = passo1; // Mensalidade calculada automaticamente junto com o passo 1
    const passo3 = Boolean(estado.dataEscolhida && estado.horaEscolhida); // Aula marcada
    const passo4 = estado.inscricaoEnviada; // Inscrição enviada

    const concluidos = [passo1, passo2, passo3, passo4];
    const atual = concluidos.findIndex(c => !c); // primeiro passo por concluir

    stepEls.forEach((el, i) => {
        el.classList.toggle('concluido', concluidos[i]);
        el.classList.toggle('atual', i === atual);
    });

    linhaEls.forEach((el, i) => {
        el.classList.toggle('concluida', concluidos[i]);
    });
}

// ------------------------------------------------
// Resumo flutuante (fica sempre visível assim que há seleção)
// ------------------------------------------------
const resumoFlutuante = document.getElementById('resumoFlutuante');
const resumoFlutuanteTexto = document.getElementById('resumoFlutuanteTexto');

function renderResumoFlutuante() {
    const selecionados = catalogoServicos.filter(s => estado.servicosSelecionados.has(s.id));

    if (selecionados.length === 0) {
        resumoFlutuante.hidden = true;
        return;
    }

    const totalMensal = totalPorTipo(selecionados, 'mensal');
    const totalUnico = totalPorTipo(selecionados, 'unico');
    const plural = selecionados.length === 1 ? 'programa' : 'programas';
    const partes = [];
    if (totalMensal > 0) partes.push(`${totalMensal} €/mês`);
    if (totalUnico > 0) partes.push(`${totalUnico} € único`);
    resumoFlutuanteTexto.textContent = `${selecionados.length} ${plural} · ${partes.join(' + ')}`;
    resumoFlutuante.hidden = false;

    resumoFlutuante.classList.remove('pulso');
    void resumoFlutuante.offsetWidth;
    resumoFlutuante.classList.add('pulso');
}

resumoFlutuante.addEventListener('click', () => {
    document.getElementById('orcamento').scrollIntoView({ behavior: 'smooth' });
});

// ------------------------------------------------
// Filtros de categoria
// ------------------------------------------------
const filtrosContainer = document.getElementById('filtrosCategoria');

function renderFiltros() {
    filtrosContainer.innerHTML = categorias.map(cat => `
        <button class="filtro-btn ${cat === estado.categoriaAtiva ? 'ativo' : ''}" data-categoria="${cat}">
            ${cat}
        </button>
    `).join('');

    filtrosContainer.querySelectorAll('.filtro-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            estado.categoriaAtiva = btn.dataset.categoria;
            renderFiltros();
            renderCatalogo();
        });
    });
}

// ------------------------------------------------
// Catálogo
// ------------------------------------------------
const catalogoGrid = document.getElementById('catalogoGrid');

function renderCatalogo() {
    const servicosVisiveis = catalogoServicos.filter(s =>
        estado.categoriaAtiva === 'Todos' || s.categoria === estado.categoriaAtiva
    );

    catalogoGrid.innerHTML = servicosVisiveis.map(s => `
        <div class="servico-cartao ${estado.servicosSelecionados.has(s.id) ? 'selecionado' : ''}" data-id="${s.id}">
            <div class="servico-icone">${ICONES_SERVICO[s.icone] || ''}</div>
            <p class="servico-categoria">${s.categoria}</p>
            <h3>${s.nome}</h3>
            <p class="servico-desc">${s.descricao}</p>
            <p class="servico-preco">${s.preco} € ${s.tipo === 'unico' ? '<span class="servico-preco-tipo">pagamento único</span>' : '<span class="servico-preco-tipo">/mês</span>'}</p>
            <label class="servico-checkbox">
                <input type="checkbox" data-id="${s.id}" ${estado.servicosSelecionados.has(s.id) ? 'checked' : ''}>
                Adicionar à inscrição
            </label>
        </div>
    `).join('');

    function alternarSelecao(id) {
        if (estado.servicosSelecionados.has(id)) {
            estado.servicosSelecionados.delete(id);
        } else {
            estado.servicosSelecionados.add(id);
        }
        renderCatalogo();
        renderOrcamento();
        renderResumoFinal();
        renderResumoFlutuante();
        atualizarStepper();
    }

    catalogoGrid.querySelectorAll('input[type="checkbox"]').forEach(cb => {
        cb.addEventListener('change', (e) => {
            const id = Number(e.target.dataset.id);
            if (e.target.checked) {
                estado.servicosSelecionados.add(id);
            } else {
                estado.servicosSelecionados.delete(id);
            }
            renderCatalogo();
            renderOrcamento();
            renderResumoFinal();
            renderResumoFlutuante();
            atualizarStepper();
        });
    });

    // clicar em qualquer ponto do cartão também seleciona (não só na checkbox)
    catalogoGrid.querySelectorAll('.servico-cartao').forEach(cartao => {
        cartao.addEventListener('click', (e) => {
            if (e.target.closest('.servico-checkbox')) return; // deixa a checkbox tratar do próprio clique
            alternarSelecao(Number(cartao.dataset.id));
        });
    });

    ativarRevelacao(catalogoGrid.querySelectorAll('.servico-cartao'));
}

// ------------------------------------------------
// Orçamento (simulador de mensalidade)
// ------------------------------------------------
const listaOrcamento = document.getElementById('listaOrcamento');
const orcamentoTotal = document.getElementById('orcamentoTotal');

function totalPorTipo(itens, tipo) {
    return itens.filter(s => s.tipo === tipo).reduce((soma, s) => soma + s.preco, 0);
}

function renderOrcamento() {
    const selecionados = catalogoServicos.filter(s => estado.servicosSelecionados.has(s.id));

    if (selecionados.length === 0) {
        listaOrcamento.innerHTML = '<li class="orcamento-vazio">Ainda não escolheste nenhum programa.</li>';
        orcamentoTotal.textContent = '0 €';
        return;
    }

    listaOrcamento.innerHTML = selecionados.map(s => `
        <li><span>${s.nome}</span><span class="item-preco">${s.preco} € ${s.tipo === 'unico' ? '(único)' : '/mês'}</span></li>
    `).join('');

    const totalMensal = totalPorTipo(selecionados, 'mensal');
    const totalUnico = totalPorTipo(selecionados, 'unico');

    if (totalMensal > 0 && totalUnico > 0) {
        orcamentoTotal.innerHTML = `${totalMensal} €/mês <span class="orcamento-total-extra">+ ${totalUnico} € equipamento (único)</span>`;
    } else if (totalUnico > 0) {
        orcamentoTotal.textContent = `${totalUnico} € (pagamento único)`;
    } else {
        orcamentoTotal.textContent = `${totalMensal} €/mês`;
    }

    orcamentoTotal.classList.remove('pulso');
    void orcamentoTotal.offsetWidth;
    orcamentoTotal.classList.add('pulso');
}

// ------------------------------------------------
// Marcações (data + horário)
// ------------------------------------------------
const dataMarcacao = document.getElementById('dataMarcacao');
const blocoHorarios = document.getElementById('blocoHorarios');
const horariosGrid = document.getElementById('horariosGrid');
const marcacaoResumo = document.getElementById('marcacaoResumo');

const hoje = new Date().toISOString().split('T')[0];
dataMarcacao.min = hoje;

const HORAS_DISPONIVEIS = ['09:00', '10:00', '11:00', '14:00', '15:00', '16:00', '17:00', '18:00'];

// gera disponibilidade "simulada" mas sempre igual para a mesma data
// (não é um sistema real de reservas — é só para mostrar a interação)
function horasIndisponiveisParaData(dataStr) {
    let soma = 0;
    for (const ch of dataStr) soma += ch.charCodeAt(0);
    const indisponiveis = new Set();
    indisponiveis.add(soma % HORAS_DISPONIVEIS.length);
    indisponiveis.add((soma * 3 + 1) % HORAS_DISPONIVEIS.length);
    return indisponiveis;
}

dataMarcacao.addEventListener('change', () => {
    estado.dataEscolhida = dataMarcacao.value;
    estado.horaEscolhida = null;

    if (!estado.dataEscolhida) {
        blocoHorarios.hidden = true;
        atualizarStepper();
        return;
    }

    const indisponiveis = horasIndisponiveisParaData(estado.dataEscolhida);

    horariosGrid.innerHTML = HORAS_DISPONIVEIS.map((h, i) => `
        <button type="button" class="horario-btn" data-hora="${h}" ${indisponiveis.has(i) ? 'disabled' : ''}>
            ${h}
        </button>
    `).join('');

    horariosGrid.querySelectorAll('.horario-btn:not(:disabled)').forEach(btn => {
        btn.addEventListener('click', () => {
            horariosGrid.querySelectorAll('.horario-btn').forEach(b => b.classList.remove('selecionado'));
            btn.classList.add('selecionado');
            estado.horaEscolhida = btn.dataset.hora;
            atualizarResumoMarcacao();
            renderResumoFinal();
            atualizarStepper();
        });
    });

    blocoHorarios.hidden = false;
    atualizarResumoMarcacao();
    renderResumoFinal();
    atualizarStepper();
});

function formatarDataPt(dataStr) {
    const [ano, mes, dia] = dataStr.split('-');
    return `${dia}/${mes}/${ano}`;
}

function atualizarResumoMarcacao() {
    if (!estado.dataEscolhida) {
        marcacaoResumo.textContent = '';
        return;
    }
    marcacaoResumo.textContent = estado.horaEscolhida
        ? `Aula marcada: ${formatarDataPt(estado.dataEscolhida)} às ${estado.horaEscolhida}`
        : `Escolhe um horário disponível para ${formatarDataPt(estado.dataEscolhida)}.`;
}

// ------------------------------------------------
// Resumo final (dentro do formulário de inscrição)
// ------------------------------------------------
const resumoServicos = document.getElementById('resumoServicos');
const resumoHorario = document.getElementById('resumoHorario');

function renderResumoFinal() {
    const selecionados = catalogoServicos.filter(s => estado.servicosSelecionados.has(s.id));

    if (selecionados.length === 0) {
        resumoServicos.textContent = 'Nenhum programa selecionado ainda.';
    } else {
        const totalMensal = totalPorTipo(selecionados, 'mensal');
        const totalUnico = totalPorTipo(selecionados, 'unico');
        const partes = [];
        if (totalMensal > 0) partes.push(`Mensalidade: ${totalMensal} €`);
        if (totalUnico > 0) partes.push(`Equipamento (único): ${totalUnico} €`);
        resumoServicos.textContent = `Programas: ${selecionados.map(s => s.nome).join(', ')} — ${partes.join(' · ')}`;
    }

    resumoHorario.textContent = (estado.dataEscolhida && estado.horaEscolhida)
        ? `Aula experimental: ${formatarDataPt(estado.dataEscolhida)} às ${estado.horaEscolhida}`
        : 'Nenhum horário selecionado ainda.';
}

// ------------------------------------------------
// Envio do formulário
// ------------------------------------------------
const formContacto = document.getElementById('formContacto');
const formNota = document.getElementById('formNota');
const btnEnviar = document.getElementById('btnEnviar');
const confirmacaoEnvio = document.getElementById('confirmacaoEnvio');
const linkAbrirEmail = document.getElementById('linkAbrirEmail');
const textoMensagem = document.getElementById('textoMensagem');
const btnCopiarMensagem = document.getElementById('btnCopiarMensagem');

btnCopiarMensagem.addEventListener('click', async () => {
    try {
        await navigator.clipboard.writeText(textoMensagem.value);
        btnCopiarMensagem.textContent = 'Copiado!';
        btnCopiarMensagem.classList.add('copiado');
    } catch (erro) {
        // sem permissão de clipboard — seleciona o texto para copiar manualmente (Ctrl+C)
        textoMensagem.select();
    }
    setTimeout(() => {
        btnCopiarMensagem.textContent = 'Copiar mensagem';
        btnCopiarMensagem.classList.remove('copiado');
    }, 2500);
});

formContacto.addEventListener('submit', async (e) => {
    e.preventDefault();

    const nome = document.getElementById('nome').value.trim();
    const email = document.getElementById('email').value.trim();
    const telefone = document.getElementById('telefone').value.trim();
    const mensagem = document.getElementById('mensagem').value.trim();

    const selecionados = catalogoServicos.filter(s => estado.servicosSelecionados.has(s.id));
    const totalMensal = totalPorTipo(selecionados, 'mensal');
    const totalUnico = totalPorTipo(selecionados, 'unico');
    const resumoServicosTexto = selecionados.length
        ? selecionados.map(s => `${s.nome} (${s.preco} € ${s.tipo === 'unico' ? 'único' : '/mês'})`).join(', ')
        : 'Nenhum';
    const resumoHorarioTexto = (estado.dataEscolhida && estado.horaEscolhida)
        ? `${formatarDataPt(estado.dataEscolhida)} às ${estado.horaEscolhida}`
        : 'Não selecionado';

    const corpoCompleto =
        `Nome do atleta: ${nome}\nEmail: ${email}\nTelefone: ${telefone || '-'}\n\n` +
        `Programas escolhidos: ${resumoServicosTexto}\n` +
        `Mensalidade estimada: ${totalMensal} €` + (totalUnico > 0 ? ` + ${totalUnico} € de equipamento (pagamento único)` : '') + `\n` +
        `Aula experimental: ${resumoHorarioTexto}\n\nMensagem:\n${mensagem || '-'}`;

    // Enquanto o Formspree não estiver configurado, mostra uma alternativa que
    // funciona sempre: um link mailto (clicado pela própria pessoa, mais fiável
    // do que redirecionar automaticamente) + a mensagem pronta a copiar.
    if (FORMSPREE_URL.includes('XXXXXXXX')) {
        const assunto = encodeURIComponent(`Nova inscrição de ${nome}`);
        linkAbrirEmail.href = `mailto:${EMAIL_NEGOCIO}?subject=${assunto}&body=${encodeURIComponent(corpoCompleto)}`;
        textoMensagem.value = `Para: ${EMAIL_NEGOCIO}\n\n${corpoCompleto}`;
        confirmacaoEnvio.hidden = false;
        formNota.textContent = '';
        estado.inscricaoEnviada = true;
        atualizarStepper();
        confirmacaoEnvio.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        return;
    }

    btnEnviar.disabled = true;
    formNota.textContent = 'A enviar...';
    formNota.className = 'form-nota';

    try {
        const dadosFormulario = new FormData();
        dadosFormulario.append('nome', nome);
        dadosFormulario.append('email', email);
        dadosFormulario.append('telefone', telefone);
        dadosFormulario.append('mensagem', corpoCompleto);

        const resposta = await fetch(FORMSPREE_URL, {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            body: dadosFormulario
        });

        if (resposta.ok) {
            formNota.textContent = 'Inscrição enviada com sucesso! Entraremos em contacto em breve.';
            formNota.className = 'form-nota sucesso';
            estado.inscricaoEnviada = true;
            formContacto.reset();
            estado.servicosSelecionados.clear();
            estado.dataEscolhida = null;
            estado.horaEscolhida = null;
            blocoHorarios.hidden = true;
            renderCatalogo();
            renderOrcamento();
            renderResumoFinal();
            renderResumoFlutuante();
            atualizarStepper();
        } else {
            throw new Error('Falha no envio');
        }
    } catch (erro) {
        formNota.textContent = 'Não foi possível enviar agora. Tenta novamente ou contacta-nos diretamente.';
        formNota.className = 'form-nota erro';
    } finally {
        btnEnviar.disabled = false;
    }
});

// ------------------------------------------------
// Arranque
// ------------------------------------------------
renderFiltros();
renderCatalogo();
renderOrcamento();
renderResumoFinal();
renderResumoFlutuante();
atualizarStepper();
ativarRevelacao(document.querySelectorAll('.orcamento-caixa, .marcacoes-caixa, .contacto-form'));


// ============================================
// NOVO: dados do clube (todos de exemplo, edita à vontade)
// ============================================
const CLUBE = {
    resultados: [
        { data: '28 Set', adv: 'Leões da Foz',     casa: true,  pts: [68, 61] },
        { data: '21 Set', adv: 'Tigres do Vale',   casa: false, pts: [57, 63] },
        { data: '14 Set', adv: 'Lobos de Gaia',    casa: true,  pts: [74, 52] },
        { data: '07 Set', adv: 'Falcões do Norte', casa: false, pts: [49, 71] }
    ],
    classificacao: [
        { eq: 'Aro Alto',         j: 8, v: 7, d: 1 },
        { eq: 'Falcões do Norte', j: 8, v: 6, d: 2 },
        { eq: 'Leões da Foz',     j: 8, v: 4, d: 4 },
        { eq: 'Tigres do Vale',   j: 8, v: 3, d: 5 },
        { eq: 'Lobos de Gaia',    j: 8, v: 2, d: 6 },
        { eq: 'Corvos do Cávado', j: 8, v: 1, d: 7 }
    ],
    equipa: [
        { nome: 'Marco Silva',  funcao: 'Treinador principal', num: 5,  cor: '#ff6b00' },
        { nome: 'Sofia Duarte', funcao: 'Formação jovem',      num: 11, cor: '#ffb100' },
        { nome: 'Rui Anjos',    funcao: 'Preparação física',   num: 23, cor: '#ff6b00' },
        { nome: 'Inês Costa',   funcao: 'Treino individual',   num: 7,  cor: '#ffb100' }
    ],
    noticias: [
        { tag: 'Clube',       data: '02 Out', titulo: 'Abertas as inscrições para a nova época',           texto: 'Há vagas em todos os escalões. A aula experimental continua grátis para novos atletas.' },
        { tag: 'Competição',  data: '28 Set', titulo: 'Sub-16 vencem em casa e lideram o campeonato',      texto: 'Uma segunda parte de grande intensidade garantiu a sétima vitória da época.' },
        { tag: 'Comunidade',  data: '19 Set', titulo: 'Torneio de mini-basket junta 12 equipas no pavilhão', texto: 'Um sábado de jogos, convívio e muitos sorrisos para os mais novos.' }
    ],
    parceiros: ['Pavilhão Norte', 'Sportiva', 'Clínica Movimento', 'Café Central', 'Gráfica Alvo'],
    faixa: ['BASQUETEBOL', 'FORMAÇÃO', 'COMPETIÇÃO', 'COMUNIDADE', 'AULA GRÁTIS', 'ESPÍRITO DE EQUIPA'],
    proximoJogo: { diaSemana: 6, hora: 18 }, // sábado às 18:00 (0 = domingo)
    // Vagas por escalão (o anúncio do topo usa estes números; "cat" tem de coincidir com a categoria do catálogo)
    vagas: [
        { nome: 'Mini-Basket (6-9 anos)', cat: 'Formação Jovem', total: 30, livres: 12 },
        { nome: 'Sub-12 (10-12 anos)',    cat: 'Formação Jovem', total: 30, livres: 7 },
        { nome: 'Sub-16 (13-16 anos)',    cat: 'Formação Jovem', total: 30, livres: 4 },
        { nome: 'Sénior / Adultos',       cat: 'Adultos',        total: 24, livres: 10 },
        { nome: 'Treino 1-a-1',           cat: 'Individual',     total: 10, livres: 3 }
    ]
};

function proximaData() {
    const agora = new Date();
    const d = new Date(agora);
    d.setHours(CLUBE.proximoJogo.hora, 0, 0, 0);
    let falta = (CLUBE.proximoJogo.diaSemana - d.getDay() + 7) % 7;
    if (falta === 0 && d <= agora) falta = 7;
    d.setDate(d.getDate() + falta);
    return d;
}

function renderClube() {
    // faixa que corre
    const palavras = CLUBE.faixa.map(p => `<span>${p}</span>`).join('');
    document.getElementById('marqueePista').innerHTML = palavras + palavras + palavras + palavras;

    // resultados
    document.getElementById('listaJogos').innerHTML = CLUBE.resultados.map(j => {
        const ganhou = j.pts[0] > j.pts[1];
        const [a, b] = j.casa ? [j.pts[0], j.pts[1]] : [j.pts[0], j.pts[1]];
        return `<li class="jogo ${ganhou ? 'vitoria' : 'derrota'}">
            <span class="jogo-data">${j.data}</span>
            <span class="jogo-eq"><strong>Aro Alto</strong> <em>${j.casa ? 'vs' : 'em'}</em> ${j.adv}</span>
            <span class="jogo-res">${a} - ${b}</span>
            <span class="jogo-tag">${ganhou ? 'V' : 'D'}</span></li>`;
    }).join('');

    // classificação
    const linhas = CLUBE.classificacao.map(e => ({ ...e, pts: e.v * 2 + e.d })).sort((x, y) => y.pts - x.pts);
    document.getElementById('tabelaClassificacao').innerHTML =
        `<thead><tr><th>#</th><th>Equipa</th><th>J</th><th>V</th><th>D</th><th>Pts</th></tr></thead><tbody>` +
        linhas.map((e, i) => `<tr class="${e.eq === 'Aro Alto' ? 'nos' : ''}"><td>${i + 1}</td><td>${e.eq}</td><td>${e.j}</td><td>${e.v}</td><td>${e.d}</td><td><strong>${e.pts}</strong></td></tr>`).join('') + `</tbody>`;

    // equipa técnica
    document.getElementById('equipaGrid').innerHTML = CLUBE.equipa.map(p => `
        <article class="treinador">
            <div class="treinador-foto" style="--c:${p.cor}">
                <span class="treinador-num">${p.num}</span>
                <svg viewBox="0 0 120 120" aria-hidden="true"><circle cx="60" cy="44" r="22" fill="#2a2a2a"/><path d="M14 120 C 14 84, 36 72, 60 72 C 84 72, 106 84, 106 120 Z" fill="#2a2a2a"/><path d="M40 78 L60 96 L80 78" fill="none" stroke="${p.cor}" stroke-width="5"/></svg>
            </div>
            <h3>${p.nome}</h3><p>${p.funcao}</p>
        </article>`).join('');

    // notícias
    document.getElementById('noticiasGrid').innerHTML = CLUBE.noticias.map((n, i) => `
        <article class="noticia">
            <div class="noticia-img n${i}"><svg viewBox="0 0 400 220" preserveAspectRatio="xMidYMid slice" aria-hidden="true"><circle cx="${120 + i * 70}" cy="110" r="${70 - i * 8}" fill="none" stroke="rgba(255,255,255,.55)" stroke-width="3"/><line x1="0" y1="110" x2="400" y2="110" stroke="rgba(255,255,255,.4)" stroke-width="3"/><circle cx="${300 - i * 40}" cy="${70 + i * 30}" r="26" fill="rgba(255,255,255,.9)"/><path d="M${274 - i * 40} ${70 + i * 30}h52M${300 - i * 40} ${44 + i * 30}v52" stroke="#ff6b00" stroke-width="3"/></svg><span class="noticia-tag">${n.tag}</span></div>
            <div class="noticia-corpo"><p class="noticia-data">${n.data}</p><h3>${n.titulo}</h3><p>${n.texto}</p></div>
        </article>`).join('');

    // parceiros
    document.getElementById('parceirosLista').innerHTML = CLUBE.parceiros.map(p => `<li>${p}</li>`).join('');
}

// Próximo jogo: contagem decrescente
function iniciarContagem() {
    const alvo = proximaData();
    const dias = ['domingo', 'segunda', 'terça', 'quarta', 'quinta', 'sexta', 'sábado'];
    document.getElementById('proximoData').textContent =
        `${dias[alvo.getDay()]}, ${String(alvo.getDate()).padStart(2, '0')}/${String(alvo.getMonth() + 1).padStart(2, '0')} · ${String(alvo.getHours()).padStart(2, '0')}:00 · Pavilhão Municipal`;
    const el = document.getElementById('contagemJogo');
    const p2 = n => String(n).padStart(2, '0');
    const tick = () => {
        let s = Math.max(0, Math.floor((alvo - Date.now()) / 1000));
        const d = Math.floor(s / 86400); s %= 86400; const h = Math.floor(s / 3600); s %= 3600; const m = Math.floor(s / 60); s %= 60;
        el.innerHTML = `<div><b>${p2(d)}</b><span>dias</span></div><div><b>${p2(h)}</b><span>horas</span></div><div><b>${p2(m)}</b><span>min</span></div><div><b>${p2(s)}</b><span>seg</span></div>`;
    };
    tick(); setInterval(tick, 1000);
}

// ------------------------------------------------
// Anúncio "Inscrições abertas": mensagens que rodam + painel de vagas
// ------------------------------------------------
(function iniciarAnuncio() {
    const totalLivres = CLUBE.vagas.reduce((n, v) => n + v.livres, 0);
    const maisCheio = [...CLUBE.vagas].sort((a, b) => a.livres - b.livres)[0];
    const msgs = [
        { html: `Nova época 2026/27 <strong>vagas limitadas</strong>`, alvo: '#painel' },
        { html: `Aula experimental <strong>grátis</strong> em qualquer escalão`, alvo: '#marcacoes' },
        { html: `Restam <strong>${totalLivres} vagas</strong> no total`, alvo: '#painel' },
        { html: `Últimas <strong>${maisCheio.livres} vagas</strong> no ${maisCheio.nome.split(' (')[0]}`, alvo: '#painel' }
    ];
    const caixa = document.getElementById('anuncioMsgs');
    const painel = document.getElementById('anuncioPainel');
    const btn = document.getElementById('anuncioBtn');
    const btnTxt = document.getElementById('anuncioBtnTxt');
    caixa.innerHTML = msgs.map((m, i) => `<a class="anuncio-msg ${i === 0 ? 'ativa' : ''}" href="${m.alvo === '#painel' ? '#' : m.alvo}" data-alvo="${m.alvo}">${m.html}</a>`).join('');
    const els = [...caixa.children];
    let atual = 0, pausado = false;
    setInterval(() => {
        if (pausado || document.hidden) return;
        els[atual].classList.remove('ativa'); els[atual].classList.add('saiu');
        const ant = els[atual];
        setTimeout(() => ant.classList.remove('saiu'), 520);
        atual = (atual + 1) % els.length;
        els[atual].classList.add('ativa');
    }, 3800);
    caixa.addEventListener('pointerenter', () => { pausado = true; });
    caixa.addEventListener('pointerleave', () => { pausado = false; });

    // painel de vagas
    document.getElementById('listaVagas').innerHTML = CLUBE.vagas.map((v, i) => {
        const ocupado = Math.round((v.total - v.livres) / v.total * 100);
        const poucas = v.livres <= 5;
        return `<li><button type="button" class="vaga ${poucas ? 'poucas' : ''}" data-i="${i}">
            <strong>${v.nome}</strong><em>${poucas ? 'Últimas ' : 'Restam '}${v.livres} vagas</em>
            <span class="vaga-barra"><i data-w="${ocupado}"></i></span></button></li>`;
    }).join('');
    function alternar(abrir) {
        const aberto = abrir ?? painel.hidden;
        painel.hidden = !aberto;
        btn.setAttribute('aria-expanded', aberto ? 'true' : 'false');
        btnTxt.textContent = aberto ? 'Fechar' : 'Ver vagas';
        if (aberto) requestAnimationFrame(() => requestAnimationFrame(() => painel.querySelectorAll('.vaga-barra i').forEach(b => { b.style.width = b.dataset.w + '%'; })));
        else painel.querySelectorAll('.vaga-barra i').forEach(b => { b.style.width = '0'; });
    }
    btn.addEventListener('click', () => alternar());
    caixa.addEventListener('click', e => {
        const a = e.target.closest('.anuncio-msg'); if (!a) return;
        if (a.dataset.alvo === '#painel') { e.preventDefault(); alternar(true); }
        else alternar(false);
    });
    document.getElementById('anuncioMarcar').addEventListener('click', () => alternar(false));
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && !painel.hidden) { alternar(false); btn.focus(); } });
    // clicar numa vaga: abre o catálogo já filtrado nesse escalão
    painel.addEventListener('click', e => {
        const b = e.target.closest('.vaga'); if (!b) return;
        estado.categoriaAtiva = CLUBE.vagas[Number(b.dataset.i)].cat;
        renderFiltros(); renderCatalogo();
        alternar(false);
        document.getElementById('catalogo').scrollIntoView({ behavior: 'smooth' });
    });
})();

// Cabeçalho muda ao fazer scroll
const cabecalho = document.getElementById('cabecalho');
function marcarScroll() { cabecalho.classList.toggle('solido', scrollY > 40); }
addEventListener('scroll', marcarScroll, { passive: true }); marcarScroll();

// Mascote do hero
const heroMascote = document.getElementById('heroMascote');
heroMascote.innerHTML = mascoteSVG({ classe: 'hero-m' });
ativarMascote(heroMascote.querySelector('svg'), { balao: document.getElementById('balaoMascote'), area: document });

renderClube();
iniciarContagem();
ativarRevelacao(document.querySelectorAll('.painel, .treinador, .noticia, .parceiros-lista li, .proximo-caixa'));

// O resumo flutuante esconde-se quando já estás a ver o placard ou o formulário (evita repetir a informação)
(function () {
    const visiveis = new Set();
    const obs = new IntersectionObserver(es => {
        es.forEach(e => e.isIntersecting ? visiveis.add(e.target.id) : visiveis.delete(e.target.id));
        document.body.classList.toggle('sem-flut', visiveis.size > 0);
    }, { threshold: 0.25 });
    ['orcamento', 'contacto'].forEach(id => obs.observe(document.getElementById(id)));
})();