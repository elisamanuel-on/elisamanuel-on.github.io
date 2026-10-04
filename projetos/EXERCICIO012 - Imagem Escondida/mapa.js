/* Mapa do jogo (Leaflet). Os países são desenhados a partir das fronteiras locais (dados/mundo.js),
   sem nomes, para as perguntas de clicar não terem a resposta escrita no mapa.
   Depois de cada resposta pode mostrar-se o mapa do OpenStreetMap por baixo, para dar contexto. */
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

        mundo();
        return mapa;
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
            tiles = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 8, attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>'
            });
            tiles.addTo(mapa);
        } else if (!ligado && tiles) {
            mapa.removeLayer(tiles);
            tiles = null;
        }
        comContexto = !!ligado && !!tiles;
        aplicarEstilos();
    }

    window.Mapa = {
        iniciar: iniciar, modoClique: modoClique, repor: repor, destacar: destacar,
        ajustar: ajustar, contexto: contexto, mundo: mundo, redimensionar: redimensionar,
        clicavel: function (cc) { return !!camadas[cc]; },
        // usado nos testes automáticos: dispara o clique de um país
        simular: function (cc) { if (camadas[cc] && camadas[cc][0]) { camadas[cc][0].fire('click'); } },
        pontoEcra: function (ll) { var p = mapa.latLngToContainerPoint(ll); var r = mapa.getContainer().getBoundingClientRect(); return [r.left + p.x, r.top + p.y]; }
    };
})();
