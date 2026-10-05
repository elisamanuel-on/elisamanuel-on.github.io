/* Mapa do jogo (Leaflet). Os países são desenhados a partir das fronteiras locais (dados/mundo.js),
   sem nomes, para as perguntas de clicar não terem a resposta escrita no mapa.
   Depois de cada resposta mostra-se por baixo um mapa de ruas SEM nomes (OpenStreetMap, estilo CARTO) e os nomes dos países
   e dos mares são escritos por cima, na língua do jogo (as ruas do OpenStreetMap trazem os nomes na língua local). */
(function () {
    'use strict';

    var ESTILOS = {
        base: { fillColor: '#2a2d4a', color: '#5a5f94', weight: 0.7, fillOpacity: 0.95 },
        neutro: { fillColor: '#1d1f35', color: '#3a3d5e', weight: 0.5, fillOpacity: 0.95 },
        hover: { fillColor: '#3d4275', color: '#8f95d6', weight: 1.2, fillOpacity: 0.95 },
        certo: { fillColor: '#48bb78', color: '#c6f6d5', weight: 1.4, fillOpacity: 0.9 },
        errado: { fillColor: '#fc8181', color: '#fed7d7', weight: 1.4, fillOpacity: 0.9 },
        alvo: { fillColor: '#ecc94b', color: '#fefcbf', weight: 1.4, fillOpacity: 0.9 },
        continente: { fillColor: '#667eea', color: '#c3dafe', weight: 1, fillOpacity: 0.85 }
    };

    var mapa = null;
    var camadas = {};      // cc -> [camadas Leaflet]
    var neutras = [];
    var limites = {};      // cc -> [[sul, oeste], [norte, este]] do maior pedaço
    var estado = {};       // cc -> nome do estilo
    var cliqueCb = null;
    var tiles = null;
    var comContexto = false;
    var estaSobre = null;
    var rotulos = null;    // camada com os nomes por cima do mapa de contexto
    var candidatos = null; // países com centro e área, para decidir que nomes cabem


    // Oceanos e mares: [nome em pt, en, es, fr], [latitude, longitude], zoom mínimo
    var MARES = [
        [['Oceano Atlântico', 'Atlantic Ocean', 'Océano Atlántico', 'océan Atlantique'], [28, -42], 1],
        [['Oceano Atlântico', 'Atlantic Ocean', 'Océano Atlántico', 'océan Atlantique'], [-22, -14], 1],
        [['Oceano Pacífico', 'Pacific Ocean', 'Océano Pacífico', 'océan Pacifique'], [22, -150], 1],
        [['Oceano Pacífico', 'Pacific Ocean', 'Océano Pacífico', 'océan Pacifique'], [-24, -128], 1],
        [['Oceano Índico', 'Indian Ocean', 'Océano Índico', 'océan Indien'], [-16, 80], 1],
        [['Oceano Antártico', 'Southern Ocean', 'Océano Austral', 'océan Austral'], [-56, 40], 1],
        [['Oceano Ártico', 'Arctic Ocean', 'Océano Ártico', 'océan Arctique'], [79, -30], 1],
        [['Mar Mediterrâneo', 'Mediterranean Sea', 'Mar Mediterráneo', 'mer Méditerranée'], [34.6, 18], 3],
        [['Mar Negro', 'Black Sea', 'Mar Negro', 'mer Noire'], [43.3, 34.5], 3.5],
        [['Mar Vermelho', 'Red Sea', 'Mar Rojo', 'mer Rouge'], [20.5, 38.3], 3.5],
        [['Mar do Norte', 'North Sea', 'Mar del Norte', 'mer du Nord'], [56, 3], 3.5],
        [['Mar Báltico', 'Baltic Sea', 'Mar Báltico', 'mer Baltique'], [58.3, 20], 4],
        [['Golfo Pérsico', 'Persian Gulf', 'Golfo Pérsico', 'golfe Persique'], [27.2, 51.5], 4],
        [['Mar Cáspio', 'Caspian Sea', 'Mar Caspio', 'mer Caspienne'], [41.6, 50.6], 3.5],
        [['Mar das Caraíbas', 'Caribbean Sea', 'Mar Caribe', 'mer des Caraïbes'], [15, -74], 3],
        [['Golfo do México', 'Gulf of Mexico', 'Golfo de México', 'golfe du Mexique'], [25, -90], 3],
        [['Mar da China Meridional', 'South China Sea', 'Mar de la China Meridional', 'mer de Chine méridionale'], [13, 114], 3],
        [['Mar do Japão', 'Sea of Japan', 'Mar del Japón', 'mer du Japon'], [40, 134], 3.5],
        [['Mar Arábico', 'Arabian Sea', 'Mar Arábigo', "mer d'Arabie"], [15, 65], 3],
        [['Baía de Bengala', 'Bay of Bengal', 'Bahía de Bengala', 'golfe du Bengale'], [14, 89], 3],
        [['Mar da Tasmânia', 'Tasman Sea', 'Mar de Tasmania', 'mer de Tasman'], [-39, 160], 3.5]
    ];

    function estiloDe(cc) {
        var nome = cc ? (estado[cc] || 'base') : 'neutro';
        var base = ESTILOS[nome];
        var s = {};
        for (var k in base) { s[k] = base[k]; }
        if (comContexto) {
            // com o mapa de ruas por baixo, só os países marcados ficam preenchidos
            if (nome === 'base' || nome === 'neutro' || nome === 'hover') { s.fillOpacity = 0; s.weight = 0.5; s.color = '#667eea'; }
            else { s.fillOpacity = 0.45; }
        }
        return s;
    }

    function aplicarEstilos() {
        var cc;
        for (cc in camadas) {
            if (Object.prototype.hasOwnProperty.call(camadas, cc)) {
                var e = estiloDe(cc);
                camadas[cc].forEach(function (c) { c.setStyle(e); });
            }
        }
        var en = estiloDe(null);
        neutras.forEach(function (c) { c.setStyle(en); });
    }

    /* As fronteiras vêm cortadas no meridiano 180: um anel que "salta" de 179° para -179° desenharia uma linha
       a atravessar o mapa. Aqui os anéis são continuados (longitudes acima de 180) e copiados 360° para o outro lado. */
    function corrigirAnel(anel) {
        var saltos = false, i;
        for (i = 1; i < anel.length; i++) {
            if (Math.abs(anel[i][0] - anel[i - 1][0]) > 180) { saltos = true; break; }
        }
        if (!saltos) { return null; }
        var novo = [[anel[0][0], anel[0][1]]];
        for (i = 1; i < anel.length; i++) {
            var x = anel[i][0], ant = novo[i - 1][0];
            while (x - ant > 180) { x -= 360; }
            while (ant - x > 180) { x += 360; }
            novo.push([x, anel[i][1]]);
        }
        return novo;
    }

    function deslocar(poligono, dx) {
        return poligono.map(function (anel) { return anel.map(function (pt) { return [pt[0] + dx, pt[1]]; }); });
    }

    function corrigirGeometria(geom) {
        var polys = geom.type === 'Polygon' ? [geom.coordinates] : geom.coordinates;
        var saida = [];
        polys.forEach(function (poly) {
            var fix = corrigirAnel(poly[0]);
            if (!fix) { saida.push(poly); return; }
            var novo = [fix];
            for (var h = 1; h < poly.length; h++) { novo.push(corrigirAnel(poly[h]) || poly[h]); }
            var minx = 1e9, maxx = -1e9;
            fix.forEach(function (pt) { if (pt[0] < minx) { minx = pt[0]; } if (pt[0] > maxx) { maxx = pt[0]; } });
            saida.push(novo);
            if (maxx > 180) { saida.push(deslocar(novo, -360)); }
            if (minx < -180) { saida.push(deslocar(novo, 360)); }
        });
        return { type: 'MultiPolygon', coordinates: saida };
    }

    function maiorPedaco(geom) {
        var polys = geom.type === 'Polygon' ? [geom.coordinates] : geom.coordinates;
        var melhor = null, area = -1;
        polys.forEach(function (p) {
            var x0 = 181, x1 = -181, y0 = 91, y1 = -91;
            p[0].forEach(function (pt) {
                if (pt[0] < x0) { x0 = pt[0]; } if (pt[0] > x1) { x1 = pt[0]; }
                if (pt[1] < y0) { y0 = pt[1]; } if (pt[1] > y1) { y1 = pt[1]; }
            });
            var a = (x1 - x0) * (y1 - y0);
            if (a > area) { area = a; melhor = [[y0, x0], [y1, x1]]; }
        });
        return { caixa: melhor, area: area };
    }

    function iniciar(elemento) {
        if (mapa) { return mapa; }
        mapa = L.map(elemento, {
            zoomSnap: 0.25, zoomDelta: 0.5, minZoom: 1, maxZoom: 8,
            preferCanvas: true, worldCopyJump: false, attributionControl: true,
            maxBounds: [[-85, -250], [85, 250]], maxBoundsViscosity: 0.9
        });
        mapa.attributionControl.setPrefix('');

        var geo = topojson.feature(window.MUNDO, window.MUNDO.objects.countries);
        geo.features.forEach(function (f) { f.geometry = corrigirGeometria(f.geometry); });
        var melhores = {};
        geo.features.forEach(function (f) {
            var cc = f.properties && f.properties.c;
            if (!cc) { return; }
            var m = maiorPedaco(f.geometry);
            if (!melhores[cc] || m.area > melhores[cc]) { melhores[cc] = m.area; limites[cc] = m.caixa; }
        });

        L.geoJSON(geo, {
            style: function (f) { return estiloDe(f.properties && f.properties.c); },
            onEachFeature: function (f, camada) {
                var cc = f.properties && f.properties.c;
                if (cc) { (camadas[cc] = camadas[cc] || []).push(camada); } else { neutras.push(camada); }
                camada.on('click', function () { if (cliqueCb) { cliqueCb(cc || null); } });
                camada.on('mouseover', function () {
                    if (!cliqueCb) { return; }
                    estaSobre = cc || null;
                    if (cc && !estado[cc]) { camadaSet(cc, 'hover'); }
                });
                camada.on('mouseout', function () {
                    if (cc && !estado[cc]) { camadaSet(cc, 'base'); }
                    estaSobre = null;
                });
            }
        }).addTo(mapa);

        mapa.on('zoomend moveend', function () { if (comContexto) { desenharNomes(); } });

        mundo();
        return mapa;
    }


    // ---------- nomes por cima do mapa de contexto (língua do jogo) ----------
    function esc(t) {
        return String(t).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
    }

    function indiceIdioma() {
        return (window.Jogo && typeof window.Jogo.indice === 'function') ? window.Jogo.indice() : 0;
    }

    function prepararCandidatos() {
        if (candidatos || !window.PAISES) { return; }
        candidatos = [];
        window.PAISES.forEach(function (p) {
            var cx = limites[p.c], centro = null;
            if (cx) { centro = [(cx[0][0] + cx[1][0]) / 2, (cx[0][1] + cx[1][1]) / 2]; }
            else if (p.ll) { centro = p.ll; }
            if (centro) { candidatos.push({ cc: p.c, nomes: p.n, ar: p.ar || 0, ll: centro }); }
        });
        candidatos.sort(function (a, b) { return b.ar - a.ar; });
    }

    function desenharNomes() {
        if (!mapa) { return; }
        if (!rotulos) { rotulos = L.layerGroup(); }
        rotulos.clearLayers();
        if (!comContexto) { if (mapa.hasLayer(rotulos)) { mapa.removeLayer(rotulos); } return; }
        if (!mapa.hasLayer(rotulos)) { rotulos.addTo(mapa); }
        prepararCandidatos();

        var z = mapa.getZoom(), idx = indiceIdioma();
        var tam = mapa.getSize();
        var minimo = 2.2e6 * Math.pow(0.25, z - 2);   // área mínima (km²) para o nome caber neste zoom
        var ocupadas = [];

        function cabe(x, y, w, h) {
            if (x + w / 2 < 0 || x - w / 2 > tam.x || y + h / 2 < 0 || y - h / 2 > tam.y) { return false; }
            for (var i = 0; i < ocupadas.length; i++) {
                var o = ocupadas[i];
                if (Math.abs(x - o[0]) < (w + o[2]) / 2 + 3 && Math.abs(y - o[1]) < (h + o[3]) / 2 + 1) { return false; }
            }
            return true;
        }
        function colocar(ll, texto, classe, larguraLetra) {
            var pt = mapa.latLngToContainerPoint(ll);
            var w = texto.length * larguraLetra + 8, h = classe === 'nm-alvo' ? 20 : 15;
            if (!cabe(pt.x, pt.y, w, h)) { return; }
            ocupadas.push([pt.x, pt.y, w, h]);
            L.marker(ll, {
                interactive: false, keyboard: false, zIndexOffset: classe === 'nm-alvo' ? 500 : 0,
                icon: L.divIcon({ className: 'nm ' + classe, html: '<span>' + esc(texto) + '</span>', iconSize: [0, 0] })
            }).addTo(rotulos);
        }

        var prioritarios = [], outros = [];
        candidatos.forEach(function (c) {
            var e = estado[c.cc];
            if (e === 'certo' || e === 'errado' || e === 'alvo') { prioritarios.push(c); }
            else if (c.ar >= minimo) { outros.push(c); }
        });
        prioritarios.forEach(function (c) { colocar(c.ll, c.nomes[idx], 'nm-alvo', 7.6); });
        outros.forEach(function (c) { colocar(c.ll, c.nomes[idx], 'nm-pais', 6.1); });
        MARES.forEach(function (m) { if (z >= m[2]) { colocar(m[1], m[0][idx], 'nm-mar', 6.4); } });
    }

    function camadaSet(cc, nomeEstilo) {
        var base = ESTILOS[nomeEstilo];
        var s = {};
        for (var k in base) { s[k] = base[k]; }
        if (comContexto) { s.fillOpacity = nomeEstilo === 'hover' ? 0.2 : 0; }
        (camadas[cc] || []).forEach(function (c) { c.setStyle(s); });
    }

    function mundo() {
        if (!mapa) { return; }
        mapa.invalidateSize(false);
        mapa.fitBounds([[-58, -170], [80, 180]], { animate: false, padding: [4, 4] });
    }

    function redimensionar() {
        if (mapa) { mapa.invalidateSize(false); }
    }

    function modoClique(cb) {
        cliqueCb = cb || null;
        if (mapa) { mapa.getContainer().classList.toggle('mapa-clicavel', !!cb); }
    }

    function repor() {
        estado = {};
        contexto(false);
        aplicarEstilos();
        mundo();
    }

    function destacar(lista, nomeEstilo) {
        (Array.isArray(lista) ? lista : [lista]).forEach(function (cc) { if (cc) { estado[cc] = nomeEstilo; } });
        aplicarEstilos();
    }

    function ajustar(cc, ll) {
        if (!mapa) { return; }
        mapa.invalidateSize(false);
        if (limites[cc]) {
            mapa.fitBounds(limites[cc], { animate: false, maxZoom: 5.5, padding: [24, 24] });
        } else if (ll) {
            mapa.setView(ll, 5, { animate: false });
        }
    }

    function contexto(ligado) {
        if (!mapa) { return; }
        if (ligado && !tiles) {
            // mapa de ruas sem nomes: os nomes são escritos por nós, na língua do jogo
            tiles = L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager_nolabels/{z}/{x}/{y}{r}.png', {
                maxZoom: 8, subdomains: 'abcd',
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> &copy; <a href="https://carto.com/attributions" target="_blank" rel="noopener">CARTO</a>'
            });
            tiles.addTo(mapa);
        } else if (!ligado && tiles) {
            mapa.removeLayer(tiles);
            tiles = null;
        }
        comContexto = !!ligado && !!tiles;
        aplicarEstilos();
        desenharNomes();
    }

    window.Mapa = {
        iniciar: iniciar, modoClique: modoClique, repor: repor, destacar: destacar,
        ajustar: ajustar, contexto: contexto, atualizarNomes: desenharNomes, mundo: mundo, redimensionar: redimensionar,
        clicavel: function (cc) { return !!camadas[cc]; },
        // usado nos testes automáticos: dispara o clique de um país
        simular: function (cc) { if (camadas[cc] && camadas[cc][0]) { camadas[cc][0].fire('click'); } },
        pontoEcra: function (ll) { var p = mapa.latLngToContainerPoint(ll); var r = mapa.getContainer().getBoundingClientRect(); return [r.left + p.x, r.top + p.y]; }
    };
})();
