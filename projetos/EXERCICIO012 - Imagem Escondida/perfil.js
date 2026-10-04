/* Jogadores: nome, avatar, recordes por nível e ranking local.
   Tudo fica no localStorage deste navegador; se o armazenamento não existir, o jogo continua em memória. */
(function () {
    'use strict';

    var CHAVE = 'imagem_escondida_v2';
    var MAX_JOGADORES = 8;
    var NIVEIS = 3;

    // cada avatar é um ícone do sprite com uma cor
    var AVATARES = [
        { icone: 'i-globo', cor: '#667eea' },
        { icone: 'i-bussola', cor: '#48bb78' },
        { icone: 'i-aviao', cor: '#f093fb' },
        { icone: 'i-ancora', cor: '#4299e1' },
        { icone: 'i-estrela', cor: '#ecc94b' },
        { icone: 'i-montanha', cor: '#ed8936' },
        { icone: 'i-pin', cor: '#fc8181' },
        { icone: 'i-trofeu', cor: '#a78bfa' }
    ];

    var dados = { jogadores: [], atual: null };

    function nivelVazio() { return { melhor: 0, estrelas: 0, tentativas: 0, concluido: false }; }
    function estatVazia() { return { jogos: 0, perguntas: 0, certas: 0, tempo: 0 }; }

    function inteiroSeguro(v, max) {
        var n = Math.floor(Number(v));
        if (!isFinite(n) || n < 0) { return 0; }
        return Math.min(n, max || 1e9);
    }

    function limparNome(texto) {
        // só letras, números, espaços, hífen e apóstrofo; máximo 16 caracteres
        var s = String(texto == null ? '' : texto).replace(/[^\p{L}\p{N} '\-]/gu, '').replace(/\s+/g, ' ').trim();
        return s.slice(0, 16);
    }

    function validarJogador(j) {
        if (!j || typeof j !== 'object') { return null; }
        var nome = limparNome(j.nome);
        if (!nome || typeof j.id !== 'string' || !/^[a-z0-9]{4,24}$/.test(j.id)) { return null; }
        var niveis = [];
        for (var i = 0; i < NIVEIS; i++) {
            var n = (j.n && j.n[i]) || {};
            niveis.push({
                melhor: inteiroSeguro(n.melhor, 100000),
                estrelas: inteiroSeguro(n.estrelas, 3),
                tentativas: inteiroSeguro(n.tentativas, 100000),
                concluido: n.concluido === true
            });
        }
        var e = j.est || {};
        return {
            id: j.id,
            nome: nome,
            av: inteiroSeguro(j.av, AVATARES.length - 1),
            n: niveis,
            est: {
                jogos: inteiroSeguro(e.jogos), perguntas: inteiroSeguro(e.perguntas),
                certas: inteiroSeguro(e.certas), tempo: inteiroSeguro(e.tempo)
            }
        };
    }

    function carregar() {
        var bruto = null;
        try { bruto = JSON.parse(window.localStorage.getItem(CHAVE) || 'null'); } catch (e) { bruto = null; }
        dados = { jogadores: [], atual: null };
        if (bruto && Array.isArray(bruto.jogadores)) {
            for (var i = 0; i < bruto.jogadores.length && dados.jogadores.length < MAX_JOGADORES; i++) {
                var v = validarJogador(bruto.jogadores[i]);
                if (v) { dados.jogadores.push(v); }
            }
            if (typeof bruto.atual === 'string' && dados.jogadores.some(function (j) { return j.id === bruto.atual; })) {
                dados.atual = bruto.atual;
            }
        }
        if (!dados.atual && dados.jogadores.length) { dados.atual = dados.jogadores[0].id; }
    }

    function guardar() {
        try { window.localStorage.setItem(CHAVE, JSON.stringify(dados)); } catch (e) { /* memória apenas */ }
    }

    function novoId() {
        return (Date.now().toString(36) + Math.random().toString(36).slice(2, 8)).replace(/[^a-z0-9]/g, '').slice(0, 24).padEnd(4, '0');
    }

    function jogadorAtual() {
        for (var i = 0; i < dados.jogadores.length; i++) {
            if (dados.jogadores[i].id === dados.atual) { return dados.jogadores[i]; }
        }
        return null;
    }

    function criar(nome, avatar) {
        var limpo = limparNome(nome);
        if (!limpo) { return { erro: 'nome_curto' }; }
        if (dados.jogadores.length >= MAX_JOGADORES) { return { erro: 'perfil_cheio' }; }
        var j = {
            id: novoId(), nome: limpo, av: inteiroSeguro(avatar, AVATARES.length - 1),
            n: [nivelVazio(), nivelVazio(), nivelVazio()], est: estatVazia()
        };
        dados.jogadores.push(j);
        dados.atual = j.id;
        guardar();
        return { jogador: j };
    }

    function escolher(id) {
        if (dados.jogadores.some(function (j) { return j.id === id; })) {
            dados.atual = id;
            guardar();
        }
    }

    function apagar(id) {
        dados.jogadores = dados.jogadores.filter(function (j) { return j.id !== id; });
        if (dados.atual === id) { dados.atual = dados.jogadores.length ? dados.jogadores[0].id : null; }
        guardar();
    }

    function total(j) {
        return j.n.reduce(function (soma, n) { return soma + n.melhor; }, 0);
    }

    function desbloqueado(nivel) {
        var j = jogadorAtual();
        if (!j) { return nivel === 1; }
        return nivel === 1 || j.n[nivel - 2].concluido;
    }

    /** Regista o fim de um nível. resultado: { pontos, estrelas, concluido, perguntas, certas, tempo } */
    function registar(nivel, r) {
        var j = jogadorAtual();
        if (!j || nivel < 1 || nivel > NIVEIS) { return { recorde: false }; }
        var n = j.n[nivel - 1];
        n.tentativas += 1;
        var recorde = false;
        if (r.concluido) {
            n.concluido = true;
            if (r.pontos > n.melhor) { n.melhor = inteiroSeguro(r.pontos, 100000); recorde = true; }
            if (r.estrelas > n.estrelas) { n.estrelas = inteiroSeguro(r.estrelas, 3); }
        }
        j.est.jogos += 1;
        j.est.perguntas += inteiroSeguro(r.perguntas, 1000);
        j.est.certas += inteiroSeguro(r.certas, 1000);
        j.est.tempo += inteiroSeguro(r.tempo, 1e7);
        guardar();
        return { recorde: recorde };
    }

    function ranking() {
        return dados.jogadores.slice().sort(function (a, b) { return total(b) - total(a); });
    }

    carregar();

    window.Perfil = {
        AVATARES: AVATARES,
        MAX: MAX_JOGADORES,
        jogadores: function () { return dados.jogadores.slice(); },
        atual: jogadorAtual,
        criar: criar,
        escolher: escolher,
        apagar: apagar,
        total: total,
        desbloqueado: desbloqueado,
        registar: registar,
        ranking: ranking,
        limparNome: limparNome
    };
})();
