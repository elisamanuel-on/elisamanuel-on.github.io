/* Quiz Dev · motor do jogo. Todo o conteúdo vem de dados.js. */
(function () {
'use strict';
const D = window.QUIZ_DADOS || [];
const NIV = ['Reconhecer', 'Compreender', 'Aplicar', 'Escrever'];
const NIV_DESC = ['o que é e para que serve', 'ler código e dizer o que faz', 'completar e encontrar o erro', 'escrever de memória'];
const XP_NIVEL = 25, VIDAS = 2, KEY = 'quizdev.v1';
const $ = s => document.querySelector(s);

/* ---------- estado guardado no navegador ---------- */
const VAZIO = () => ({ xp: 0, done: {}, dias: {}, commits: [], erradas: {}, tags: {} });
function carregar() {
  try { const r = localStorage.getItem(KEY); if (r) return Object.assign(VAZIO(), JSON.parse(r)); } catch (e) {}
  return VAZIO();
}
let ST = carregar();
function gravar() { try { localStorage.setItem(KEY, JSON.stringify(ST)); } catch (e) {} }

/* ---------- utilitários ---------- */
const esc = s => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
const hoje = () => chave(new Date());
function chave(d) { const p = n => String(n).padStart(2, '0'); return d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate()); }
function hash(s) { let h = 2166136261; for (let i = 0; i < s.length; i++) { h ^= s.charCodeAt(i); h = Math.imul(h, 16777619); } return (h >>> 0).toString(16).padStart(8, '0').slice(0, 7); }
function baralhar(a) { a = a.slice(); for (let i = a.length - 1; i > 0; i--) { const j = Math.floor(Math.random() * (i + 1)); [a[i], a[j]] = [a[j], a[i]]; } return a; }
function normalizar(s) { return String(s).replace(/\s+/g, '').replace(/"/g, "'").replace(/;$/, ''); }
const isTel = () => matchMedia('(max-width: 700px)').matches || (matchMedia('(pointer: coarse)').matches && innerWidth < 900);

const TEMA = id => D.find(t => t.id === id);
const TOP = (tid, pid) => (TEMA(tid) || { topicos: [] }).topicos.find(p => p.id === pid);
const tk = (t, p) => t + '/' + p;
const feitos = k => ST.done[k] || [false, false, false, false];
const nFeitos = k => feitos(k).filter(Boolean).length;
const totalNiveis = () => D.reduce((n, t) => n + t.topicos.length * 4, 0);
const totalFeitos = () => D.reduce((n, t) => n + t.topicos.reduce((m, p) => m + nFeitos(tk(t.id, p.id)), 0), 0);
function qid(t, p, n, i) { return [t, p, n, i].join('/'); }
const INDICE = {};
D.forEach(t => t.topicos.forEach(p => p.niveis.forEach((nv, n) => nv.perguntas.forEach((q, i) => { INDICE[qid(t.id, p.id, n, i)] = q; }))));
function streak() {
  let n = 0; const d = new Date();
  if (!ST.dias[chave(d)]) d.setDate(d.getDate() - 1);
  while (ST.dias[chave(d)]) { n++; d.setDate(d.getDate() - 1); }
  return n;
}

/* ---------- realce de sintaxe leve ---------- */
const KW = 'const|let|var|function|return|if|else|elif|for|while|in|def|class|import|from|as|new|try|catch|async|await|break|continue|true|false|null|None|True|False|and|or|not|self|super|print|range|git|SELECT|FROM|WHERE|then|this';
const RX = new RegExp('(\\/\\/.*|#.*)|(\'(?:\\\\.|[^\'\\\\])*\'|"(?:\\\\.|[^"\\\\])*"|`(?:\\\\.|[^`\\\\])*`)|(\\b\\d+(?:\\.\\d+)?\\b)|(\\b(?:' + KW + ')\\b)', 'g');
function realcar(linha) {
  let out = '', i = 0, m; RX.lastIndex = 0;
  while ((m = RX.exec(linha))) {
    out += esc(linha.slice(i, m.index));
    const cls = m[1] ? 'c' : m[2] ? 's' : m[3] ? 'nn' : 'k';
    out += '<span class="' + cls + '">' + esc(m[0]) + '</span>';
    i = m.index + m[0].length;
  }
  return out + esc(linha.slice(i));
}

/* ---------- estado da interface ---------- */
const V = { abas: ['bem'], at: 'bem', exp: {}, painel: 'term', min: false, sessao: null, log: [{ c: 'g', t: '$ quiz-dev --start' }, { c: 'g', t: 'Escolhe um tema no explorador. Lê a ficha, joga os 4 níveis e ganha XP.' }] };
const rotulo = id => {
  if (id === 'bem') return 'bem-vindo.md';
  if (id === 'rev') return 'revisao.quiz';
  const [tipo, resto] = id.split('|'); const [t, p, n] = resto.split('/'); const top = TOP(t, p);
  if (tipo === 'mapa') return p + '/mapa.quest';
  if (tipo === 'ficha') return top.ficheiro;
  return p + '/nivel-' + (+n + 1) + '.quiz';
};
function corDe(id) {
  if (id === 'bem' || id === 'rev') return '#22d3ee';
  const t = TEMA(id.split('|')[1].split('/')[0]); return t ? t.cor : '#22d3ee';
}
function abrir(id) {
  if (!V.abas.includes(id)) { V.abas.push(id); if (V.abas.length > 7) V.abas.splice(V.abas.findIndex(a => a !== id && a !== 'bem'), 1); }
  V.at = id;
  if (id.startsWith('nivel|')) { const [t, p, n] = id.split('|')[1].split('/'); iniciar(t, p, +n); } else if (id === 'rev') iniciarRev(); else V.sessao = null;
  const m = id.split('|')[1]; if (m) V.exp[m.split('/')[0]] = true;
  fechaMenu(); render();
}
function fechar(id) {
  const i = V.abas.indexOf(id); if (i < 0 || V.abas.length === 1) return;
  V.abas.splice(i, 1); if (V.at === id) { V.at = V.abas[Math.max(0, i - 1)]; if (V.at.startsWith('nivel|') || V.at === 'rev') V.at = V.abas.find(a => a.startsWith('mapa|')) || 'bem'; V.sessao = null; }
  render();
}
function fechaMenu() { $('#aside').classList.remove('ab'); $('#fundoAside').classList.remove('ab'); }

/* ---------- sessão de um nível ---------- */
function montarPergunta(id, q) {
  const o = q.o ? baralhar(q.o.map((_, i) => i)) : null;
  return { id, q, ordem: o };
}
function iniciar(t, p, n) {
  const k = tk(t, p), f = feitos(k);
  if (n > 0 && !f[n - 1]) { V.sessao = null; return; }
  const top = TOP(t, p);
  V.sessao = { tipo: 'nivel', t, p, n, i: 0, vidas: VIDAS, perguntas: top.niveis[n].perguntas.map((q, i) => montarPergunta(qid(t, p, n, i), q)), sel: null, res: null, fim: null, seq: [], usados: [], txt: '', erros: 0 };
}
function iniciarRev() {
  const ids = baralhar(Object.keys(ST.erradas).filter(i => INDICE[i])).slice(0, 8);
  V.sessao = { tipo: 'rev', i: 0, vidas: 99, perguntas: ids.map(i => montarPergunta(i, INDICE[i])), sel: null, res: null, fim: ids.length ? null : 'vazio', seq: [], usados: [], txt: '', erros: 0 };
}
function resetPergunta(S) { S.sel = null; S.res = null; S.seq = []; S.usados = []; S.txt = ''; }
function verificar() {
  const S = V.sessao; if (!S || S.fim || S.res) return;
  const P = S.perguntas[S.i], q = P.q; let ok;
  if (q.t === 'esc') {
    const v = isTel() ? S.seq.join(' ') : S.txt;
    if (!v.trim()) return;
    ok = q.ok.some(a => normalizar(a) === normalizar(v));
  } else { if (S.sel === null) return; ok = P.ordem[S.sel] === q.c; }
  S.res = ok ? 'ok' : 'no'; V.painel = 'term';
  const nome = S.tipo === 'rev' ? 'revisao.quiz' : 'nivel-' + (S.n + 1) + '.quiz';
  V.log.push({ c: 'g', t: '$ run ' + nome + ' (pergunta ' + (S.i + 1) + '/' + S.perguntas.length + ')' });
  if (ok) {
    delete ST.erradas[P.id]; gravar();
    V.log.push({ c: 'v', t: 'BUILD SUCCESSFUL' });
  } else {
    ST.erradas[P.id] = 1; S.erros++; gravar();
    V.log.push({ c: 'e', t: 'BUILD FAILED' }, { c: 'e', t: 'error: resposta incorreta' + (S.tipo === 'nivel' ? ' (−1 ❤)' : '') });
    if (S.tipo === 'nivel') { S.vidas--; pop('#hp'); if (S.vidas <= 0) S.fim = 'falhou'; }
  }
  V.log.push({ c: 'g', t: '// ' + q.e });
  render();
}
function seguinte() {
  const S = V.sessao; if (!S || !S.res) return;
  if (S.fim === 'falhou') { iniciar(S.t, S.p, S.n); render(); return; }
  if (S.i + 1 < S.perguntas.length) { S.i++; resetPergunta(S); render(); return; }
  if (S.tipo === 'rev') { S.fim = 'rev'; render(); return; }
  concluir(S); render();
}
function concluir(S) {
  const k = tk(S.t, S.p), f = feitos(k).slice(); const primeira = !f[S.n];
  f[S.n] = true; ST.done[k] = f;
  const dia = hoje(); ST.dias[dia] = (ST.dias[dia] || 0) + 1;
  S.fim = 'ok'; S.ganhou = 0; S.tag = null;
  const top = TOP(S.t, S.p);
  if (primeira) {
    ST.xp += XP_NIVEL; S.ganhou = XP_NIVEL; ganho();
    ST.commits.unshift({ h: hash(k + S.n), m: 'feat: nível ' + (S.n + 1) + ' concluído (' + S.t + '/' + S.p + ')', d: dia });
    V.log.push({ c: 'v', t: '+' + XP_NIVEL + ' XP · nivel-' + (S.n + 1) + ' passou' });
  } else V.log.push({ c: 'v', t: 'nivel-' + (S.n + 1) + ' passou outra vez (sem XP novo)' });
  if (f.every(Boolean) && !ST.tags[k]) {
    ST.tags[k] = dia; S.tag = 'v1.0-' + S.p;
    ST.commits.unshift({ h: hash('tag' + k), m: 'tag v1.0-' + S.p + ' · ' + top.nome + ' concluído', d: dia, tag: true });
    V.log.push({ c: 'v', t: 'Tópico concluído! Medalha: ' + top.nome });
  }
  gravar();
}
function pop(sel) { const e = $(sel); if (e) { e.classList.remove('pop'); void e.offsetWidth; e.classList.add('pop'); } }
function ganho() { const e = document.createElement('div'); e.className = 'xpf'; e.textContent = '+' + XP_NIVEL + ' XP'; document.body.appendChild(e); setTimeout(() => e.remove(), 1500); pop('.ch.x'); }

/* ---------- vistas ---------- */
const lin = rows => rows.map((r, i) => '<div class="ln"><span class="n">' + (i + 1) + '</span><span class="t">' + r + '</span></div>').join('');
const quebra = (txt, max) => { const out = []; let l = ''; txt.split(' ').forEach(w => { if ((l + ' ' + w).trim().length > max) { out.push(l); l = w; } else l = (l + ' ' + w).trim(); }); if (l) out.push(l); return out; };

function vistaBem() {
  const cards = D.map(t => {
    const tot = t.topicos.length * 4, f = t.topicos.reduce((n, p) => n + nFeitos(tk(t.id, p.id)), 0), pc = Math.round(f / tot * 100);
    return '<button class="card" data-t="' + t.id + '" style="--cor:' + t.cor + '"><h3>' + esc(t.nome) + '</h3><p>' + esc(t.descricao) + '</p><div class="bar"><i style="width:' + pc + '%"></i></div><div class="pc">' + f + '/' + tot + ' níveis · ' + t.topicos.length + ' tópicos</div></button>';
  }).join('');
  const nErr = Object.keys(ST.erradas).filter(i => INDICE[i]).length;
  return '<div class="bem"><h1><span>Quiz Dev</span></h1><div class="c">// Estuda programação por temas. Lê a ficha, joga os 4 níveis de cada tópico,<br>// ganha XP e medalhas. O progresso fica guardado neste navegador.</div><div class="cards">' + cards + '</div>' +
    '<button class="acao" id="irRev" ' + (nErr ? '' : 'disabled style="opacity:.4;cursor:default"') + '>↻ Rever erradas (' + nErr + ')</button>' +
    '<div class="c" style="margin:6px 0 0">// ' + totalFeitos() + ' de ' + totalNiveis() + ' níveis concluídos · ' + Object.keys(INDICE).length + ' perguntas</div></div>';
}
function vistaMapa(t, p) {
  const top = TOP(t, p), tema = TEMA(t), k = tk(t, p), f = feitos(k), at = f.findIndex(x => !x), tudo = f.every(Boolean);
  const nodes = NIV.map((n, i) => {
    const st = f[i] ? 'feito' : i === at ? 'atual' : 'bloq';
    return '<button class="gn ' + st + '" data-n="' + i + '" ' + (st === 'bloq' ? 'disabled' : '') + '><span class="rail"><span class="cir">' + (f[i] ? '✓' : i + 1) + '</span><span class="l ' + (f[i] ? 'v' : '') + '"></span></span><span class="gi"><span class="h1"><span class="hash">' + hash(k + i) + '</span><span class="m">nível ' + (i + 1) + ' · ' + n.toLowerCase() + '</span></span><div class="s2">' + (st === 'feito' ? 'concluído · ' + NIV_DESC[i] : st === 'atual' ? 'clica para jogar · ' + NIV_DESC[i] : 'bloqueado · passa o nível anterior') + '</div></span></button>';
  }).join('');
  return '<div class="mapa"><div class="tt">' + esc(top.nome) + '</div><div class="c">// ' + esc(tema.nome) + ' · 4 níveis, 4 perguntas cada. Podes falhar 1 pergunta por nível.</div>' +
    '<button class="acao" id="irFicha">≡ Ler a ficha (' + esc(top.ficheiro) + ')</button>' + nodes +
    '<div class="gn ' + (tudo ? 'feito' : 'bloq') + ' sem"><span class="rail"><span class="cir">' + (tudo ? '🏅' : '⌀') + '</span></span><span class="gi"><span class="h1"><span class="tag ' + (tudo ? '' : 'off') + '">tag: v1.0-' + esc(p) + '</span></span><div class="s2">' + (tudo ? 'medalha desbloqueada!' : 'medalha por concluir os 4 níveis') + '</div></span></div></div>';
}
function vistaFicha(t, p) {
  const fi = TOP(t, p).ficha, rows = [];
  rows.push('<span class="h"># ' + esc(TOP(t, p).nome) + '</span>', '');
  quebra(fi.oque, 78).forEach(l => rows.push('<span class="c">' + esc(l) + '</span>'));
  rows.push('', '<span class="h">## O essencial</span>', '');
  fi.pontos.forEach(pt => { rows.push('<span class="k">' + esc(pt[0]) + '</span>'); quebra(pt[1], 78).forEach(l => rows.push('<span class="c">   ' + esc(l) + '</span>')); });
  rows.push('', '<span class="h">## Exemplo</span>', '');
  fi.exemplo.split('\n').forEach(l => rows.push(realcar(l)));
  if (fi.dica) { rows.push('', '<span class="h">## Atenção</span>', ''); quebra(fi.dica, 78).forEach(l => rows.push('<span class="c">' + esc(l) + '</span>')); }
  rows.push('', '<span class="c">Fonte: ' + esc(fi.fonte) + '</span>');
  return lin(rows) + '<div style="padding:14px 18px 0 46px"><button class="bt v" id="irMapa">Praticar este tópico →</button></div>';
}
function vistaPergunta() {
  const S = V.sessao;
  if (!S) return '<div class="mapa"><div class="c">// este nível ainda está bloqueado. Passa o nível anterior no mapa.</div></div>';
  if (S.fim === 'vazio') return '<div class="res"><div class="big">✓</div><h2>Sem perguntas por rever</h2><p>As perguntas em que falhares aparecem aqui para treinares outra vez.</p><button class="bt" id="irBem">Voltar ao início</button></div>';
  if (S.fim === 'rev') return '<div class="res"><div class="big">↻</div><h2>Revisão feita</h2><p>Acertaste ' + (S.perguntas.length - S.erros) + ' de ' + S.perguntas.length + '. As que falhaste continuam na lista.</p><button class="bt v" id="irRev2">Rever mais</button> <button class="bt" id="irBem">Voltar ao início</button></div>';
  if (S.fim === 'ok') {
    const k = tk(S.t, S.p), proximo = S.n < 3;
    return '<div class="res"><div class="big">' + (S.tag ? '🏅' : '⭐') + '</div><h2>' + (S.tag ? 'Tópico concluído!' : 'Nível ' + (S.n + 1) + ' concluído!') + '</h2>' + (S.ganhou ? '<div class="xp">+' + S.ganhou + ' XP</div>' : '') + '<p>' + (S.tag ? 'Ganhaste a medalha de ' + esc(TOP(S.t, S.p).nome) + '. Está no painel de baixo.' : 'Falhaste ' + S.erros + ' de ' + S.perguntas.length + ' perguntas. ' + (proximo ? 'O próximo nível já está aberto.' : '')) + '</p>' + (proximo ? '<button class="bt v" id="irProx">Nível ' + (S.n + 2) + ' →</button> ' : '') + '<button class="bt" id="irMapa2">Voltar ao mapa</button></div>';
  }
  const P = S.perguntas[S.i], q = P.q, rows = [];
  const cab = S.tipo === 'rev' ? 'revisão' : 'nível ' + (S.n + 1) + ' · ' + NIV[S.n].toLowerCase();
  rows.push('<span class="c">// ' + cab + ' · pergunta ' + (S.i + 1) + ' de ' + S.perguntas.length + '</span>');
  quebra(q.q, 80).forEach(l => rows.push('<span class="c">// ' + esc(l) + '</span>'));
  rows.push('');
  if (q.cod) {
    const partes = q.cod.split('\n');
    partes.forEach(l => {
      if (q.t === 'lac' && l.includes('___')) { const [a, b] = l.split('___'); rows.push(realcar(a) + '<span class="slot">' + (S.sel !== null ? esc(q.o[P.ordem[S.sel]]) : '&nbsp;&nbsp;&nbsp;&nbsp;') + '</span>' + realcar(b)); } else rows.push(realcar(l));
    });
    rows.push('');
  }
  if (q.t === 'esc') {
    if (isTel()) {
      rows.push('<span class="k">&gt;</span> <span class="seq">' + S.seq.map(w => '<span class="pal">' + esc(w) + '</span>').join('') + '</span>', '');
      rows.push('<span>' + q.tok.map((w, i) => S.usados.includes(i) ? '' : '<button class="pal" data-i="' + i + '">' + esc(w) + '</button>').join('') + '</span>', '<button class="bt" id="lim">limpar</button>');
    } else rows.push('<span class="k">&gt;</span> <input class="inp" id="in" autocomplete="off" autocapitalize="off" spellcheck="false" placeholder="escreve de memória e carrega em Enter" value="' + esc(S.txt) + '">');
  } else {
    P.ordem.forEach((oi, i) => {
      const certa = S.res && oi === q.c, errada = S.res && S.sel === i && oi !== q.c;
      rows.push('<span class="opc ' + (S.sel === i && !S.res ? 'on' : '') + (certa ? ' certa' : '') + (errada ? ' errada' : '') + '" data-i="' + i + '">[' + (S.sel === i ? 'x' : ' ') + '] ' + String.fromCharCode(65 + i) + '  ' + esc(q.o[oi]) + '</span>');
    });
  }
  let h = lin(rows);
  if (S.res) {
    const certaTxt = q.t === 'esc' ? '<br><span class="g">Resposta aceite: <b style="display:inline;color:#fff">' + esc(q.ok[0]) + '</b></span>' : '';
    const ultimo = S.i + 1 >= S.perguntas.length;
    h += '<div class="fb ' + S.res + '"><b>' + (S.res === 'ok' ? 'Certo!' : (S.fim === 'falhou' ? 'Sem vidas.' : 'Ainda não.')) + '</b>' + esc(q.e) + certaTxt + '<div class="acoes"><button class="bt ' + (S.res === 'ok' ? 'v' : 'r') + '" id="seg">' + (S.fim === 'falhou' ? 'Recomeçar o nível' : ultimo ? 'Ver resultado →' : 'Seguinte →') + '</button></div></div>';
  }
  return h;
}

/* ---------- barra lateral, abas, painel ---------- */
function lateral() {
  let h = '<h6>EXPLORADOR</h6><button class="fich raiz ' + (V.at === 'bem' ? 'at' : '') + '" data-a="bem"><span class="e">≡</span>bem-vindo.md</button>';
  D.forEach(t => {
    const aberto = V.exp[t.id];
    h += '<button class="pasta" data-t="' + t.id + '"><span class="sv">' + (aberto ? '▾' : '▸') + '</span><span class="d" style="background:' + t.cor + '"></span><b>' + esc(t.id === 'js' ? 'javascript' : t.id === 'meu' ? 'o-meu-codigo' : t.id) + '</b></button>';
    if (aberto) t.topicos.forEach(p => {
      const k = tk(t.id, p.id), n = nFeitos(k), id = 'mapa|' + k, ativo = V.at.endsWith('|' + k) || V.at.includes('|' + k + '/');
      h += '<button class="fich ' + (ativo ? 'at' : '') + (n === 4 ? ' ok' : '') + '" data-a="' + id + '"><span class="e">' + (n === 4 ? '✓' : '≡') + '</span>' + esc(p.ficheiro) + '<small>' + '▰'.repeat(n) + '▱'.repeat(4 - n) + '</small></button>';
    });
  });
  const nErr = Object.keys(ST.erradas).filter(i => INDICE[i]).length;
  h += '<h6 style="margin-top:8px">TREINO</h6><button class="fich raiz ' + (V.at === 'rev' ? 'at' : '') + '" data-a="rev"><span class="e">↻</span>revisao.quiz<small style="letter-spacing:0">' + nErr + '</small></button>';
  $('#aside').innerHTML = h;
  $('#aside').querySelectorAll('[data-a]').forEach(b => b.onclick = () => abrir(b.dataset.a));
  $('#aside').querySelectorAll('.pasta').forEach(b => b.onclick = () => { V.exp[b.dataset.t] = !V.exp[b.dataset.t]; lateral(); });
}
function abas() {
  const S = V.sessao, podeRun = S && !S.fim && !S.res && (V.at.startsWith('nivel|') || V.at === 'rev');
  $('#abas').innerHTML = V.abas.map(id => '<span class="aba ' + (V.at === id ? 'at' : '') + '" data-a="' + id + '" style="' + (V.at === id ? '--cor:' + corDe(id) : '') + '"><span>' + esc(rotulo(id)) + '</span>' + (id !== 'bem' ? '<button class="x" data-x="' + id + '" aria-label="Fechar">×</button>' : '') + '</span>').join('') + '<span class="run"><button id="run" ' + (podeRun ? '' : 'disabled') + '>▶ Verificar</button></span>';
  $('#abas').querySelectorAll('.aba').forEach(b => b.onclick = e => { if (e.target.dataset.x) return; abrir(b.dataset.a); });
  $('#abas').querySelectorAll('[data-x]').forEach(b => b.onclick = e => { e.stopPropagation(); fechar(b.dataset.x); });
  $('#run').onclick = verificar;
}
function editor() {
  const ed = $('#ed'), at = V.at; let h = '';
  const topo = ed.scrollTop;
  if (at === 'bem') h = vistaBem();
  else if (at === 'rev' || at.startsWith('nivel|')) h = vistaPergunta();
  else { const [tipo, resto] = at.split('|'), [t, p] = resto.split('/'); h = tipo === 'mapa' ? vistaMapa(t, p) : vistaFicha(t, p); }
  ed.innerHTML = h; ed.scrollTop = at === V.ultimo ? topo : 0; V.ultimo = at;
  ed.querySelectorAll('.card').forEach(b => b.onclick = () => {
    const t = TEMA(b.dataset.t), p = t.topicos.find(x => nFeitos(tk(t.id, x.id)) < 4) || t.topicos[0]; abrir('mapa|' + tk(t.id, p.id));
  });
  const q = s => ed.querySelector(s);
  if (q('#irRev')) q('#irRev').onclick = () => abrir('rev');
  if (q('#irBem')) q('#irBem').onclick = () => abrir('bem');
  if (q('#irRev2')) q('#irRev2').onclick = () => { iniciarRev(); render(); };
  if (at.startsWith('mapa|')) {
    const k = at.split('|')[1];
    ed.querySelectorAll('.gn[data-n]:not(:disabled)').forEach(b => b.onclick = () => abrir('nivel|' + k + '/' + b.dataset.n));
    q('#irFicha').onclick = () => abrir('ficha|' + k);
  }
  if (at.startsWith('ficha|') && q('#irMapa')) q('#irMapa').onclick = () => abrir('mapa|' + at.split('|')[1]);
  const S = V.sessao;
  if (S && S.fim === 'ok') {
    const k = tk(S.t, S.p);
    if (q('#irProx')) q('#irProx').onclick = () => { fechaAba('nivel|' + k + '/' + S.n); abrir('nivel|' + k + '/' + (S.n + 1)); };
    if (q('#irMapa2')) q('#irMapa2').onclick = () => { fechaAba('nivel|' + k + '/' + S.n); abrir('mapa|' + k); };
  }
  if (S && !S.fim && S.perguntas) {
    const P = S.perguntas[S.i];
    ed.querySelectorAll('.opc').forEach(b => b.onclick = () => { if (S.res) return; S.sel = +b.dataset.i; editor(); abas(); });
    ed.querySelectorAll('.pal[data-i]').forEach(b => b.onclick = () => { if (S.res) return; S.seq.push(P.q.tok[+b.dataset.i]); S.usados.push(+b.dataset.i); editor(); });
    if (q('#lim')) q('#lim').onclick = () => { S.seq = []; S.usados = []; editor(); };
    const inp = q('#in'); if (inp) { inp.oninput = () => { S.txt = inp.value; }; inp.onkeydown = e => { if (e.key === 'Enter') { e.preventDefault(); S.res ? seguinte() : verificar(); } }; if (!S.res) inp.focus({ preventScroll: true }); else inp.disabled = true; }
  }
  if (S && S.res && q('#seg')) q('#seg').onclick = seguinte;
}
function fechaAba(id) { const i = V.abas.indexOf(id); if (i > -1 && V.abas.length > 1) V.abas.splice(i, 1); }
function painel() {
  const tabs = [['term', 'TERMINAL'], ['prog', 'PROGRESSO'], ['com', 'COMMITS E TAGS'], ['med', 'MEDALHAS']];
  $('#pt').innerHTML = tabs.map(([k, n]) => '<button class="' + (V.painel === k ? 'at' : '') + '" data-k="' + k + '">' + n + '</button>').join('') + '<button class="tg" id="tg" aria-label="Mostrar ou esconder painel">' + (V.min ? '⌃' : '⌄') + '</button>';
  $('#painel').classList.toggle('min', V.min);
  $('#pt').querySelectorAll('[data-k]').forEach(b => b.onclick = () => { V.painel = b.dataset.k; V.min = false; painel(); });
  $('#tg').onclick = () => { V.min = !V.min; painel(); };
  const pb = $('#pb');
  if (V.painel === 'term') { pb.innerHTML = V.log.slice(-60).map(l => '<div class="' + l.c + '">' + esc(l.t) + '</div>').join(''); pb.scrollTop = pb.scrollHeight; }
  else if (V.painel === 'prog') {
    const fim = new Date(), cels = []; fim.setDate(fim.getDate() + (6 - fim.getDay()));
    for (let i = 20 * 7 - 1; i >= 0; i--) { const d = new Date(fim); d.setDate(fim.getDate() - i); const n = ST.dias[chave(d)] || 0, fut = d > new Date(); cels.push('<i class="' + (n >= 4 ? 'l3' : n >= 2 ? 'l2' : n >= 1 ? 'l1' : '') + (chave(d) === hoje() ? ' hoje' : '') + '" title="' + chave(d) + ': ' + n + ' nível(is)"' + (fut ? ' style="opacity:.25"' : '') + '></i>'); }
    pb.innerHTML = '<div class="g">// dias em que estudaste (níveis concluídos por dia)</div><div class="heat">' + cels.join('') + '</div><div><span class="y">XP</span> ' + ST.xp + ' · <span class="y">sequência</span> ' + streak() + ' dia(s) · <span class="y">níveis</span> ' + totalFeitos() + '/' + totalNiveis() + '</div>';
  } else if (V.painel === 'com') pb.innerHTML = ST.commits.length ? ST.commits.slice(0, 40).map(c => '<div><span class="y">' + c.h + '</span> ' + (c.tag ? '<span class="v">' : '<span>') + esc(c.m) + '</span> <span class="g">(' + c.d + ')</span></div>').join('') : '<div class="g">// ainda sem commits. Passa um nível e aparece aqui.</div>';
  else {
    pb.innerHTML = '<div class="med">' + D.map(t => t.topicos.map(p => { const on = !!ST.tags[tk(t.id, p.id)]; return '<div class="mc ' + (on ? 'on' : '') + '" style="--cor:' + t.cor + '"><div class="i">🏅</div>' + esc(p.nome) + '</div>'; }).join('')).join('') + '</div>';
  }
}
function estado() {
  const S = V.sessao; let nb = '';
  if (V.at.includes('|')) { const [t, p] = V.at.split('|')[1].split('/'); const n = nFeitos(tk(t, p)); nb = '<span class="x">' + '▰'.repeat(n) + '▱'.repeat(4 - n) + '</span><span class="x">' + esc(TEMA(t).nome) + ' › ' + esc(TOP(t, p).nome) + '</span>'; }
  $('#estado').innerHTML = '<span>⎇ main</span><span>✓ ' + totalFeitos() + '/' + totalNiveis() + ' níveis</span>' + nb + '<span>Português · UTF-8</span>';
  $('#xp').textContent = ST.xp; $('#fg').textContent = streak();
  const v = S && S.tipo === 'nivel' ? S.vidas : VIDAS; $('#hp').textContent = '❤ '.repeat(Math.max(0, v)).trim() + (v < VIDAS ? ' ' + '♡ '.repeat(VIDAS - Math.max(0, v)).trim() : '');
  $('#nome').textContent = 'quiz-dev' + (V.at.includes('|') ? ' · ' + rotulo(V.at) : '');
  document.documentElement.style.setProperty('--cor', corDe(V.at));
}
function render() { lateral(); abas(); editor(); painel(); estado(); }

/* ---------- eventos globais ---------- */
$('#burger').onclick = () => { $('#aside').classList.toggle('ab'); $('#fundoAside').classList.toggle('ab'); };
$('#fundoAside').onclick = fechaMenu;
document.addEventListener('keydown', e => {
  if (e.target.tagName === 'INPUT' || e.ctrlKey || e.metaKey || e.altKey) return;
  const S = V.sessao; if (!S || !S.perguntas) return;
  if (e.key === 'Enter') { e.preventDefault(); if (S.res) seguinte(); else if (!S.fim) verificar(); return; }
  if (S.fim || S.res) return;
  const q = S.perguntas[S.i].q; if (q.t === 'esc') return;
  const i = 'abcd'.indexOf(e.key.toLowerCase()); const j = '1234'.indexOf(e.key);
  const k = i > -1 ? i : j; if (k > -1) { S.sel = k; editor(); abas(); }
});
if (!D.length) { $('#ed').innerHTML = '<div class="res"><h2>Sem conteúdo</h2></div>'; return; }
D[0] && (V.exp[D[0].id] = true);
render();
})();
