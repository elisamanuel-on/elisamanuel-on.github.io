// Tabuada em Jogos · parte comum: estado, estrelas, perguntas, sons, confetes, ecrã inicial e navegação.
(function () {
    'use strict';

    var T = window.Tabuada = window.Tabuada || {};

    T.VERSAO = '2.0.1';
    T.semMovimento = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);

    // ---------- utilitários ----------
    T.aleatorio = function (min, max) {
        return min + Math.floor(Math.random() * (max - min + 1));
    };

    T.baralhar = function (lista) {
        var a = lista.slice();
        for (var i = a.length - 1; i > 0; i--) {
            var j = Math.floor(Math.random() * (i + 1));
            var tmp = a[i]; a[i] = a[j]; a[j] = tmp;
        }
        return a;
    };

    T.escolher = function (lista) {
        return lista[Math.floor(Math.random() * lista.length)];
    };

    T.esperar = function (ms, fn) {
        return window.setTimeout(fn, ms);
    };

    // ---------- estado guardado só neste navegador ----------
    var CHAVE = 'tabuada_estado_v2';
    var estado = { tabuadas: [2, 3, 4, 5], nivel: 'facil', som: true, estrelas: 0, recordes: {} };

    try {
        var lido = JSON.parse(window.localStorage.getItem(CHAVE) || 'null');
        if (lido && typeof lido === 'object') {
            if (Array.isArray(lido.tabuadas)) {
                var validas = lido.tabuadas.filter(function (n) { return n >= 1 && n <= 10; });
                if (validas.length) estado.tabuadas = validas;
            }
            if (['facil', 'medio', 'dificil'].indexOf(lido.nivel) !== -1) estado.nivel = lido.nivel;
            if (typeof lido.som === 'boolean') estado.som = lido.som;
            if (typeof lido.estrelas === 'number' && lido.estrelas >= 0) estado.estrelas = Math.floor(lido.estrelas);
            if (lido.recordes && typeof lido.recordes === 'object') estado.recordes = lido.recordes;
        }
    } catch (erro) { /* primeira visita ou armazenamento bloqueado */ }

    T.estado = estado;

    T.guardar = function () {
        try { window.localStorage.setItem(CHAVE, JSON.stringify(estado)); } catch (erro) { /* só dura esta visita */ }
    };

    // ---------- desenhos (SVG próprios) ----------
    var svg = function (d, extra) {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"' + (extra || '') + '>' + d + '</svg>';
    };

    T.icones = {
        aprender: svg('<path d="M4 5.5A2 2 0 0 1 6 4h13v15H6a2 2 0 0 0-2 2V5.5z"/><path d="M9 8h6M9 12h4"/>'),
        labirinto: svg('<path d="M4 4h16v16H4z"/><path d="M4 9h8M12 9v6M8 15h4M16 4v8M16 16v4"/>'),
        quiz: svg('<circle cx="12" cy="13" r="8"/><path d="M12 9v4l3 2M9 3h6"/>'),
        caca: svg('<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.5"/>'),
        memoria: svg('<rect x="3" y="6" width="11" height="14" rx="2"/><path d="M10 4h9a2 2 0 0 1 2 2v10"/>'),
        voltar: svg('<path d="M15 5l-7 7 7 7"/>'),
        som: svg('<path d="M4 9v6h4l5 4V5L8 9H4z"/><path d="M16.5 8.5a5 5 0 0 1 0 7M19 6a8.5 8.5 0 0 1 0 12"/>'),
        mudo: svg('<path d="M4 9v6h4l5 4V5L8 9H4z"/><path d="M17 9l5 6M22 9l-5 6"/>'),
        estrela: '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2.5l2.9 6.1 6.6.8-4.9 4.6 1.3 6.6L12 17.3 6.1 20.6l1.3-6.6L2.5 9.4l6.6-.8L12 2.5z"/></svg>',
        coracao: '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 21s-8-5.2-8-11a4.5 4.5 0 0 1 8-2.8A4.5 4.5 0 0 1 20 10c0 5.800-8 11-8 11z"/></svg>',
        tesouro: '<svg viewBox="0 0 32 32" aria-hidden="true"><rect x="4" y="13" width="24" height="14" rx="3" fill="#ff8a3d"/><path d="M4 16a12 8 0 0 1 24 0v-1a6 6 0 0 0-6-6H10a6 6 0 0 0-6 6z" fill="#ffc83d"/><rect x="13" y="15" width="6" height="7" rx="1.5" fill="#2b2350"/><path d="M4 18h24" stroke="#2b2350" stroke-width="2"/></svg>',
        porta: '<svg viewBox="0 0 32 32" aria-hidden="true"><rect x="6" y="4" width="20" height="26" rx="3" fill="#ff8a3d"/><rect x="9" y="7" width="14" height="20" rx="2" fill="#ffb066"/><circle cx="19.500" cy="17" r="1.800" fill="#2b2350"/><path d="M12 14l4-4 4 4" stroke="#fff" stroke-width="0" fill="none"/></svg>',
        cadeado: '<svg viewBox="0 0 32 32" aria-hidden="true"><rect x="7" y="14" width="18" height="14" rx="3" fill="#7c4dff"/><path d="M11 14v-3a5 5 0 0 1 10 0v3" fill="none" stroke="#7c4dff" stroke-width="3"/><circle cx="16" cy="21" r="2.200" fill="#fff"/></svg>'
    };

    /** O Tabi: personagem original. humor: feliz | triste | pensar */
    T.mascote = function (humor) {
        var boca = {
            feliz: '<path d="M38 62q12 16 24 0z" fill="#2b2350"/><path d="M44 68q6 5 12 0" fill="none" stroke="#ff5c93" stroke-width="3" stroke-linecap="round"/>',
            triste: '<path d="M39 70q11-12 22 0" fill="none" stroke="#2b2350" stroke-width="4" stroke-linecap="round"/>',
            pensar: '<path d="M40 66h20" fill="none" stroke="#2b2350" stroke-width="4" stroke-linecap="round"/>'
        }[humor || 'feliz'];
        return '<svg class="mascote mascote-' + (humor || 'feliz') + '" viewBox="0 0 100 100" aria-hidden="true">' +
            '<ellipse cx="50" cy="94" rx="26" ry="4" fill="#2b2350" opacity=".12"/>' +
            '<circle cx="30" cy="12" r="6" fill="#ffc83d"/><circle cx="70" cy="12" r="6" fill="#ffc83d"/>' +
            '<path d="M32 15l6 10M68 15l-6 10" stroke="#ffc83d" stroke-width="4" stroke-linecap="round"/>' +
            '<path d="M50 20c24 0 38 17 38 38 0 20-14 34-38 34S12 78 12 58c0-21 14-38 38-38z" fill="#8f5cff"/>' +
            '<ellipse cx="50" cy="72" rx="24" ry="17" fill="#b79bff"/>' +
            '<ellipse cx="37" cy="48" rx="10" ry="11" fill="#fff"/><ellipse cx="63" cy="48" rx="10" ry="11" fill="#fff"/>' +
            '<circle cx="39" cy="50" r="5.500" fill="#2b2350"/><circle cx="61" cy="50" r="5.500" fill="#2b2350"/>' +
            '<circle cx="41" cy="48" r="1.800" fill="#fff"/><circle cx="63" cy="48" r="1.800" fill="#fff"/>' +
            '<circle cx="24" cy="63" r="5" fill="#ff5c93" opacity=".55"/><circle cx="76" cy="63" r="5" fill="#ff5c93" opacity=".55"/>' +
            boca + '</svg>';
    };

    // ---------- sons (Web Audio, só depois de um toque) ----------
    var contextoAudio = null;

    function nota(freq, inicio, duracao, tipo, volume) {
        try {
            var AC = window.AudioContext || window.webkitAudioContext;
            if (!AC) return;
            if (!contextoAudio) contextoAudio = new AC();
            if (contextoAudio.state === 'suspended') contextoAudio.resume();
            var osc = contextoAudio.createOscillator();
            var ganho = contextoAudio.createGain();
            var t0 = contextoAudio.currentTime + inicio;
            osc.type = tipo || 'sine';
            osc.frequency.setValueAtTime(freq, t0);
            ganho.gain.setValueAtTime(0.0001, t0);
            ganho.gain.exponentialRampToValueAtTime(volume || 0.12, t0 + 0.02);
            ganho.gain.exponentialRampToValueAtTime(0.0001, t0 + duracao);
            osc.connect(ganho);
            ganho.connect(contextoAudio.destination);
            osc.start(t0);
            osc.stop(t0 + duracao + 0.05);
        } catch (erro) { /* sem áudio: o jogo continua em silêncio */ }
    }

    T.som = function (tipo) {
        if (!estado.som) return;
        if (tipo === 'certo') { nota(660, 0, 0.12, 'triangle'); nota(880, 0.1, 0.18, 'triangle'); }
        else if (tipo === 'errado') { nota(220, 0, 0.16, 'square', 0.06); nota(160, 0.14, 0.22, 'square', 0.06); }
        else if (tipo === 'estrela') { nota(988, 0, 0.1, 'sine'); nota(1319, 0.08, 0.16, 'sine'); }
        else if (tipo === 'passo') { nota(330, 0, 0.05, 'sine', 0.05); }
        else if (tipo === 'virar') { nota(520, 0, 0.06, 'triangle', 0.07); }
        else if (tipo === 'vitoria') { [523, 659, 784, 1047].forEach(function (f, i) { nota(f, i * 0.12, 0.22, 'triangle'); }); }
        else if (tipo === 'fim') { [392, 330, 262].forEach(function (f, i) { nota(f, i * 0.14, 0.24, 'sine'); }); }
    };

    // ---------- confetes ----------
    var canvas = null;
    var particulas = [];
    var animando = false;
    var CORES = ['#ff5c93', '#ffc83d', '#36a9ff', '#2ed573', '#8f5cff', '#ff8a3d'];

    function desenharConfetes() {
        var ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        particulas = particulas.filter(function (p) { return p.vida > 0 && p.y < canvas.height + 30; });
        particulas.forEach(function (p) {
            p.vx *= 0.99; p.vy += 0.18; p.x += p.vx; p.y += p.vy; p.rot += p.vr; p.vida -= 1;
            ctx.save();
            ctx.translate(p.x, p.y);
            ctx.rotate(p.rot);
            ctx.globalAlpha = Math.min(1, p.vida / 30);
            ctx.fillStyle = p.cor;
            if (p.estrela) {
                ctx.beginPath();
                for (var i = 0; i < 10; i++) {
                    var r = i % 2 ? p.t * 0.45 : p.t;
                    var a = (Math.PI * 2 * i) / 10 - Math.PI / 2;
                    ctx.lineTo(Math.cos(a) * r, Math.sin(a) * r);
                }
                ctx.closePath();
                ctx.fill();
            } else {
                ctx.fillRect(-p.t / 2, -p.t / 3, p.t, p.t * 0.6);
            }
            ctx.restore();
        });
        if (particulas.length) {
            window.requestAnimationFrame(desenharConfetes);
        } else {
            animando = false;
            canvas.style.display = 'none';
        }
    }

    T.confetes = function (quantidade, origem) {
        if (T.semMovimento) return;
        canvas = canvas || document.getElementById('confete');
        if (!canvas) return;
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
        canvas.style.display = 'block';
        var ox = origem ? origem.x : canvas.width / 2;
        var oy = origem ? origem.y : canvas.height * 0.35;
        for (var i = 0; i < (quantidade || 80); i++) {
            var ang = Math.random() * Math.PI * 2;
            var vel = 3 + Math.random() * 7;
            particulas.push({
                x: ox, y: oy,
                vx: Math.cos(ang) * vel, vy: Math.sin(ang) * vel - 5,
                rot: Math.random() * 6, vr: (Math.random() - 0.5) * 0.4,
                t: 7 + Math.random() * 8, cor: T.escolher(CORES),
                vida: 80 + Math.random() * 60, estrela: Math.random() < 0.35
            });
        }
        if (!animando) {
            animando = true;
            window.requestAnimationFrame(desenharConfetes);
        }
    };

    // ---------- avisos curtos ----------
    var avisoTimer = null;
    T.aviso = function (texto, tipo) {
        var el = document.getElementById('aviso');
        if (!el) return;
        el.textContent = texto;
        el.className = 'aviso mostrar ' + (tipo || '');
        window.clearTimeout(avisoTimer);
        avisoTimer = window.setTimeout(function () { el.className = 'aviso'; }, 1500);
    };

    // ---------- estrelas e títulos ----------
    var RANKS = [0, 15, 40, 100];

    T.rank = function () {
        var indice = 0;
        RANKS.forEach(function (limite, i) { if (estado.estrelas >= limite) indice = i; });
        return indice;
    };

    function atualizarEstrelas(saltar) {
        var el = document.getElementById('estrelasTotal');
        if (!el) return;
        el.querySelector('b').textContent = estado.estrelas;
        if (saltar && !T.semMovimento) {
            el.classList.remove('salto');
            void el.offsetWidth;
            el.classList.add('salto');
        }
        var r = document.querySelector('[data-rank]');
        if (r) r.innerHTML = rankHTML();
    }

    T.ganharEstrelas = function (n) {
        if (!n) return;
        var antes = T.rank();
        estado.estrelas += n;
        T.guardar();
        atualizarEstrelas(true);
        if (T.rank() > antes) {
            T.aviso(T.t('rank_' + T.rank()), 'rank');
            T.confetes(60);
            T.som('vitoria');
        }
    };

    function rankHTML() {
        var r = T.rank();
        var proximo = r < RANKS.length - 1 ? RANKS[r + 1] - estado.estrelas : 0;
        return '<span class="rank-nome">' + T.t('rank_' + r) + '</span>' +
            '<span class="rank-falta">' + (proximo > 0 ? T.t('faltam_rank', proximo, T.t('rank_' + (r + 1))) : T.t('rank_max')) + '</span>';
    }

    /** Guarda um recorde. maior=true: vence o valor mais alto. Devolve true se foi batido. */
    T.recorde = function (chave, valor, maior) {
        var atual = estado.recordes[chave];
        var bate = atual === undefined || (maior ? valor > atual : valor < atual);
        if (bate) {
            estado.recordes[chave] = valor;
            T.guardar();
        }
        return bate;
    };

    T.formatarTabuadas = function () {
        return estado.tabuadas.length >= 10 ? T.t('todas') : estado.tabuadas.slice().sort(function (a, b) { return a - b; }).join(', ');
    };

    // ---------- perguntas ----------
    /** Cria uma pergunta de tabuada conforme as tabuadas e o nível escolhidos. */
    T.gerarPergunta = function (nivel, tabuadasEscolhidas) {
        nivel = nivel || estado.nivel;
        var lista = tabuadasEscolhidas || estado.tabuadas;
        var a = T.escolher(lista);
        var b = T.aleatorio(1, 10);
        var c = a * b;
        var tipos = nivel === 'facil' ? ['mult'] : (nivel === 'medio' ? ['mult', 'mult', 'falta'] : ['mult', 'falta', 'div']);
        var tipo = T.escolher(tipos);
        var texto, resposta, vizinhos;

        if (tipo === 'mult') {
            texto = a + ' × ' + b + ' = ?';
            resposta = c;
            vizinhos = [a * (b + 1), a * (b - 1), (a + 1) * b, (a - 1) * b, c + 1, c - 1, c + 2, c - 2, c + 10, c - 10];
        } else if (tipo === 'falta') {
            if (Math.random() < 0.5) {
                texto = a + ' × ? = ' + c;
                resposta = b;
            } else {
                texto = '? × ' + b + ' = ' + c;
                resposta = a;
            }
            vizinhos = [resposta + 1, resposta - 1, resposta + 2, resposta - 2, resposta + 3, resposta - 3];
        } else {
            texto = c + ' ÷ ' + a + ' = ?';
            resposta = b;
            vizinhos = [b + 1, b - 1, b + 2, b - 2, b + 3, b - 3];
        }

        var vistos = {};
        vistos[resposta] = true;
        var opcoes = [resposta];
        T.baralhar(vizinhos).forEach(function (v) {
            if (opcoes.length < 4 && v >= 0 && !vistos[v]) { vistos[v] = true; opcoes.push(v); }
        });
        var extra = 1;
        while (opcoes.length < 4) {
            var v = resposta + extra++;
            if (!vistos[v]) { vistos[v] = true; opcoes.push(v); }
        }

        return { texto: texto, resposta: resposta, opcoes: T.baralhar(opcoes), digitar: nivel === 'dificil', a: a, b: b, tipo: tipo };
    };

    var limparTeclas = null;

    /**
     * Mostra uma pergunta (opções grandes, ou caixa de texto no nível difícil).
     * opcoes.aoResponder(certo, dada); opcoes.repetir = true deixa tentar outra vez depois de errar.
     */
    T.perguntaUI = function (cont, p, opcoes) {
        opcoes = opcoes || {};
        if (limparTeclas) limparTeclas();

        var html = '<div class="pergunta-card"><div class="pergunta-texto">' + p.texto + '</div><div class="resposta-area">';
        if (p.digitar) {
            html += '<form class="resposta-form" autocomplete="off"><input type="text" inputmode="numeric" pattern="[0-9]*" class="resposta-input" placeholder="' + T.t('resposta_ph') + '" aria-label="' + T.t('resposta_ph') + '">' +
                '<button type="submit" class="btn btn-verde">' + T.t('responder') + '</button></form>';
        } else {
            html += '<div class="opcoes">' + p.opcoes.map(function (o, i) {
                return '<button type="button" class="opcao" data-valor="' + o + '"><span class="opcao-tecla">' + (i + 1) + '</span>' + o + '</button>';
            }).join('') + '</div>';
        }
        html += '</div><p class="pergunta-fb" role="status"></p></div>';
        cont.innerHTML = html;

        var fb = cont.querySelector('.pergunta-fb');
        var respondida = false;
        var erros = 0;

        function responder(dada, botao) {
            if (respondida) return;
            var certo = dada === p.resposta;
            if (certo) {
                respondida = true;
                T.som('certo');
                fb.textContent = T.tr('certo');
                fb.className = 'pergunta-fb certo';
                if (botao) botao.classList.add('certa');
                cont.querySelectorAll('button, input').forEach(function (el) { el.disabled = true; });
            } else {
                erros++;
                T.som('errado');
                if (opcoes.repetir) {
                    fb.textContent = T.tr('errado');
                    fb.className = 'pergunta-fb errado';
                    if (botao) { botao.classList.add('errada'); botao.disabled = true; }
                    var entrada = cont.querySelector('.resposta-input');
                    if (entrada) { entrada.value = ''; entrada.focus(); }
                } else {
                    respondida = true;
                    fb.textContent = p.texto.replace('?', p.resposta);
                    fb.className = 'pergunta-fb errado';
                    if (botao) botao.classList.add('errada');
                    cont.querySelectorAll('.opcao').forEach(function (el) {
                        el.disabled = true;
                        if (Number(el.getAttribute('data-valor')) === p.resposta) el.classList.add('certa');
                    });
                    var campo = cont.querySelector('.resposta-input');
                    if (campo) campo.disabled = true;
                }
            }
            if (opcoes.aoResponder) opcoes.aoResponder(certo, dada, erros);
        }

        cont.querySelectorAll('.opcao').forEach(function (botao) {
            botao.addEventListener('click', function () {
                responder(Number(botao.getAttribute('data-valor')), botao);
            });
        });

        var formulario = cont.querySelector('.resposta-form');
        if (formulario) {
            formulario.addEventListener('submit', function (ev) {
                ev.preventDefault();
                var campo = formulario.querySelector('input');
                var texto = campo.value.trim().replace(',', '.');
                if (texto === '' || isNaN(Number(texto))) { campo.focus(); return; }
                responder(Number(texto), null);
            });
            window.setTimeout(function () { var c = formulario.querySelector('input'); if (c) c.focus(); }, 30);
        }

        function teclas(ev) {
            if (p.digitar || ev.ctrlKey || ev.metaKey || ev.altKey) return;
            var n = Number(ev.key);
            if (n >= 1 && n <= 4) {
                var botao = cont.querySelectorAll('.opcao')[n - 1];
                if (botao && !botao.disabled) botao.click();
            }
        }
        document.addEventListener('keydown', teclas);
        limparTeclas = function () { document.removeEventListener('keydown', teclas); limparTeclas = null; };

        return { destruir: function () { if (limparTeclas) limparTeclas(); } };
    };

    // ---------- janela de fim de jogo ----------
    var janelaFim = null;

    T.fecharFim = function () {
        if (janelaFim) { janelaFim.remove(); janelaFim = null; }
    };

    /** opcoes: humor, titulo, linhas[], estrelas, aoRepetir */
    T.fim = function (opcoes) {
        T.fecharFim();
        var el = document.createElement('div');
        el.className = 'janela-fundo';
        var ganhou = opcoes.estrelas > 0
            ? '<p class="fim-estrelas">' + T.icones.estrela + ' ' + (opcoes.estrelas === 1 ? T.t('ganhaste_1') : T.t('ganhaste', opcoes.estrelas)) + '</p>' : '';
        el.innerHTML = '<div class="janela" role="dialog" aria-modal="true" aria-labelledby="fimTitulo">' +
            '<div class="janela-mascote">' + T.mascote(opcoes.humor || 'feliz') + '</div>' +
            '<h2 id="fimTitulo">' + opcoes.titulo + '</h2>' +
            (opcoes.linhas || []).map(function (l) { return '<p class="fim-linha">' + l + '</p>'; }).join('') +
            ganhou +
            '<div class="janela-botoes"><button type="button" class="btn btn-verde" data-fim="repetir">' + (opcoes.rotuloRepetir || T.t('jogar_outra')) + '</button>' +
            '<button type="button" class="btn btn-claro" data-fim="inicio">' + T.t('inicio') + '</button></div></div>';
        document.body.appendChild(el);
        janelaFim = el;
        el.querySelector('[data-fim="repetir"]').addEventListener('click', function () {
            T.fecharFim();
            if (opcoes.aoRepetir) opcoes.aoRepetir();
        });
        el.querySelector('[data-fim="inicio"]').addEventListener('click', function () {
            T.fecharFim();
            T.ir('inicio');
        });
        el.querySelector('[data-fim="repetir"]').focus();
        if (opcoes.humor !== 'triste') T.confetes(opcoes.estrelas > 1 ? 120 : 70);
        T.som(opcoes.humor === 'triste' ? 'fim' : 'vitoria');
    };

    // ---------- módulos dos jogos e navegação ----------
    var modulos = {};
    var atual = null;
    var ORDEM = ['aprender', 'labirinto', 'quiz', 'caca', 'memoria'];
    var COR = { aprender: 'azul', labirinto: 'laranja', quiz: 'rosa', caca: 'verde', memoria: 'roxo' };

    T.registar = function (id, modulo) {
        modulos[id] = modulo;
    };

    T.ir = function (id) {
        var novo = id === 'inicio' ? '' : '#' + id;
        if ((window.location.hash || '') === novo) {
            renderizar();
        } else if (novo === '') {
            window.history.pushState(null, '', window.location.pathname + window.location.search);
            renderizar();
        } else {
            window.location.hash = novo;
        }
    };

    function rotaAtual() {
        var id = (window.location.hash || '').replace('#', '');
        return modulos[id] ? id : 'inicio';
    }

    function textoRecorde(id) {
        var r = estado.recordes;
        if (id === 'labirinto' && r.labirinto) return T.t('lab_total', r.labirinto);
        if (id === 'quiz' && r['quiz_' + estado.nivel] !== undefined) return T.t('recorde', r['quiz_' + estado.nivel] + ' ' + T.t('qz_pontos').toLowerCase());
        if (id === 'caca' && r['caca_' + estado.nivel] !== undefined) return T.t('recorde', r['caca_' + estado.nivel] + '/5');
        if (id === 'memoria' && r['memoria_' + estado.nivel] !== undefined) return T.t('recorde', r['memoria_' + estado.nivel] + ' ' + T.t('me_jogadas').toLowerCase());
        return '';
    }

    function renderInicio(ecra) {
        var chips = '';
        for (var n = 1; n <= 10; n++) {
            chips += '<button type="button" class="chip' + (estado.tabuadas.indexOf(n) !== -1 ? ' ativo' : '') + '" data-tab="' + n + '" aria-pressed="' + (estado.tabuadas.indexOf(n) !== -1) + '">' + n + '</button>';
        }
        chips += '<button type="button" class="chip chip-todas' + (estado.tabuadas.length === 10 ? ' ativo' : '') + '" data-tab="todas" aria-pressed="' + (estado.tabuadas.length === 10) + '">' + T.t('todas') + '</button>';

        var niveis = ['facil', 'medio', 'dificil'].map(function (n, i) {
            return '<button type="button" class="nivel nivel-' + n + (estado.nivel === n ? ' ativo' : '') + '" data-nivel="' + n + '" aria-pressed="' + (estado.nivel === n) + '"><span class="nivel-estrelas">' +
                new Array(i + 2).join(T.icones.estrela) + '</span>' + T.t(n) + '</button>';
        }).join('');

        var jogos = ORDEM.map(function (id) {
            var rec = textoRecorde(id);
            return '<a class="jogo-card cor-' + COR[id] + '" href="#' + id + '">' +
                '<span class="jogo-icone">' + T.icones[id] + '</span>' +
                '<span class="jogo-nome">' + T.t('jogo_' + id) + '</span>' +
                '<span class="jogo-desc">' + T.t('jogo_' + id + '_d') + '</span>' +
                '<span class="jogo-rec">' + (rec || T.t('sem_recorde')) + '</span>' +
                '<span class="jogo-jogar">' + T.t('jogar') + '</span></a>';
        }).join('');

        ecra.innerHTML =
            '<section class="inicio">' +
            '<div class="hero"><div class="hero-mascote">' + T.mascote('feliz') + '</div>' +
            '<div class="hero-texto"><p class="balao">' + T.t('ola') + '</p>' +
            '<p class="rank"><span class="rank-etiqueta">' + T.t('titulo_rank') + '</span><span data-rank>' + rankHTML() + '</span></p></div></div>' +
            '<div class="painel definicoes"><h2>' + T.t('escolhe_tabuadas') + '</h2><div class="chips" id="chips">' + chips + '</div>' +
            '<h2>' + T.t('nivel') + '</h2><div class="niveis" id="niveis">' + niveis + '</div></div>' +
            '<h2 class="titulo-jogos">' + T.t('escolhe_jogo') + '</h2><div class="jogos">' + jogos + '</div></section>';

        ecra.querySelector('#chips').addEventListener('click', function (ev) {
            var botao = ev.target.closest('.chip');
            if (!botao) return;
            var valor = botao.getAttribute('data-tab');
            if (valor === 'todas') {
                estado.tabuadas = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
            } else {
                var n = Number(valor);
                var i = estado.tabuadas.indexOf(n);
                if (i === -1) estado.tabuadas.push(n);
                else if (estado.tabuadas.length > 1) estado.tabuadas.splice(i, 1);
            }
            T.guardar();
            T.som('passo');
            ecra.querySelectorAll('.chip').forEach(function (c) {
                var v = c.getAttribute('data-tab');
                var ativo = v === 'todas' ? estado.tabuadas.length === 10 : estado.tabuadas.indexOf(Number(v)) !== -1;
                c.classList.toggle('ativo', ativo);
                c.setAttribute('aria-pressed', String(ativo));
            });
        });

        ecra.querySelector('#niveis').addEventListener('click', function (ev) {
            var botao = ev.target.closest('.nivel');
            if (!botao) return;
            estado.nivel = botao.getAttribute('data-nivel');
            T.guardar();
            T.som('passo');
            renderInicio(ecra); // atualiza também os recordes do nível escolhido
        });
    }

    function renderJogo(ecra, id) {
        ecra.innerHTML =
            '<section class="jogo-ecra cor-' + COR[id] + '">' +
            '<div class="jogo-topo"><a class="btn-voltar" href="#inicio">' + T.icones.voltar + '<span>' + T.t('voltar') + '</span></a>' +
            '<h2 class="jogo-titulo"><span class="jogo-icone">' + T.icones[id] + '</span>' + T.t('jogo_' + id) + '</h2>' +
            (id === 'aprender' ? '<span></span>' :
                '<a class="resumo-chip" href="#inicio" title="' + T.t('mudar') + '">' + T.t('resumo_tabuadas', T.formatarTabuadas()) + ' · ' + T.t(estado.nivel) + '</a>') +
            '</div><div class="jogo-corpo" id="jogoCorpo"></div></section>';
        modulos[id].abrir(document.getElementById('jogoCorpo'));
    }

    function renderizar() {
        if (atual && modulos[atual] && modulos[atual].fechar) modulos[atual].fechar();
        T.fecharFim();
        if (limparTeclas) limparTeclas();
        var id = rotaAtual();
        var ecra = document.getElementById('ecra');
        atual = id === 'inicio' ? null : id;
        if (id === 'inicio') renderInicio(ecra);
        else renderJogo(ecra, id);
        window.scrollTo(0, 0);
    }

    // ---------- cabeçalho e rodapé ----------
    function textosFixos() {
        document.documentElement.setAttribute('lang', T.t('html_lang'));
        document.title = T.t('titulo') + ' · Elisama Manuel';
        document.querySelectorAll('[data-i18n]').forEach(function (el) { el.textContent = T.t(el.getAttribute('data-i18n')); });
        document.querySelectorAll('[data-i18n-aria]').forEach(function (el) { el.setAttribute('aria-label', T.t(el.getAttribute('data-i18n-aria'))); });
        var som = document.getElementById('somBtn');
        som.innerHTML = estado.som ? T.icones.som : T.icones.mudo;
        som.setAttribute('aria-pressed', String(estado.som));
        var versao = document.getElementById('versao');
        if (versao) versao.textContent = T.t('versao') + ' ' + T.VERSAO;
        var logo = document.getElementById('logoMascote');
        if (logo) logo.innerHTML = T.mascote('feliz');
        var estrela = document.querySelector('#estrelasTotal .estrela-icone');
        if (estrela) estrela.innerHTML = T.icones.estrela;
    }

    function iniciar() {
        var seletor = document.getElementById('idioma');
        seletor.innerHTML = T.SUPORTADOS.map(function (c) {
            return '<option value="' + c + '" lang="' + c + '">' + T.NOMES[c] + '</option>';
        }).join('');
        seletor.value = T.idioma;
        seletor.addEventListener('change', function () {
            T.mudarIdioma(seletor.value);
            textosFixos();
            atualizarEstrelas(false);
            renderizar();
        });

        document.getElementById('somBtn').addEventListener('click', function () {
            estado.som = !estado.som;
            T.guardar();
            textosFixos();
            if (estado.som) T.som('estrela');
        });

        document.getElementById('marcaBtn').addEventListener('click', function () { T.ir('inicio'); });
        window.addEventListener('hashchange', renderizar);

        textosFixos();
        atualizarEstrelas(false);
        renderizar();
    }

    T.iniciar = iniciar;
})();
