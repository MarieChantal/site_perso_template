/*
 * L'atelier : comportement du site.
 * - bascule jour / nuit (mémorisée dans le navigateur du visiteur) ;
 * - placement de la déco du mur, accrochée aux étagères, sans chevauchement ;
 * - dessin du parquet en perspective et placement des objets au sol.
 * Tout est recalculé quand la largeur de l'écran change.
 */
(function () {
  'use strict';

  var doc = document.documentElement;

  /* ---------- jour / nuit ---------- */
  var bouton = document.querySelector('.bascule');
  function majBouton() {
    if (!bouton) return;
    var nuit = doc.getAttribute('data-mode') === 'nuit';
    bouton.setAttribute('aria-pressed', nuit ? 'true' : 'false');
    bouton.setAttribute('aria-label', nuit ? 'Passer en mode jour' : 'Passer en mode nuit');
    bouton.querySelector('.libelle').textContent = nuit ? 'Nuit' : 'Jour';
  }
  if (bouton) {
    bouton.addEventListener('click', function () {
      var mode = doc.getAttribute('data-mode') === 'nuit' ? 'jour' : 'nuit';
      doc.setAttribute('data-mode', mode);
      try { localStorage.setItem('mode', mode); } catch (e) {}
      majBouton();
    });
    majBouton();
  }

  /* ---------- réglages du placement ---------- */
  var LARGEUR_IMAGE = { petite: 80, moyenne: 120, grande: 170 };  // en px, pour un écran de 1000 px
  var TAILLE_TEXTE = { petite: 22, moyenne: 28, grande: 34 };
  var HAUTEUR_SOL = { petite: 44, moyenne: 66, grande: 92 };
  var ECART = 14;        // espace minimal entre deux éléments du mur
  var BANDE_LIBRE = 38;  // hauteur libre au-dessus des objets, où vont les écritures
  var COTES = ['gauche', 'droite', 'centre'];

  function echelle(W) { return Math.min(1.15, Math.max(0.62, W / 1000)); }

  /* positions horizontales à essayer, en partant du côté demandé */
  function positionsX(cote, w, W, marge) {
    var lo = marge, hi = W - w - marge, pas = Math.max(16, w * 0.25), xs = [], x, k;
    if (hi < lo) return [Math.max(0, (W - w) / 2)];
    if (cote === 'centre') {
      var c = (W - w) / 2;
      xs.push(c);
      for (k = pas; c - k >= lo || c + k <= hi; k += pas) {
        if (c - k >= lo) xs.push(c - k);
        if (c + k <= hi) xs.push(c + k);
      }
    } else {
      for (x = lo; x <= hi; x += pas) xs.push(x);
      if (xs[xs.length - 1] !== hi) xs.push(hi);
      if (cote === 'droite') xs.reverse();
    }
    return xs;
  }

  /* ---------- le mur ---------- */
  function dimensionner(el, type, taille, s) {
    if (type === 'texte') {
      el.style.width = '';
      el.style.fontSize = Math.max(14, Math.round(TAILLE_TEXTE[taille] * s)) + 'px';
    } else {
      el.style.width = Math.max(type === 'photo' ? 72 : 60, Math.round(LARGEUR_IMAGE[taille] * s)) + 'px';
    }
  }

  function placerMur() {
    var mur = document.querySelector('.mur');
    var deco = mur && mur.querySelector('.deco');
    var grille = mur && mur.querySelector('.etageres');
    if (!deco || !grille) return;

    var els = Array.prototype.slice.call(deco.children);
    var W = mur.clientWidth, H = mur.clientHeight;
    var styleGrille = getComputedStyle(grille);
    var RANG = parseFloat(styleGrille.gridAutoRows) || 220;
    var colonnes = styleGrille.gridTemplateColumns.split(' ').length;
    var rangees = Math.max(1, Math.ceil(grille.children.length / colonnes));
    var bandes = rangees + 1;                    // une bande par étagère + le bas du mur
    var haut = grille.offsetTop;
    var s = echelle(W);
    var marge = Math.max(8, W * 0.025);
    var pris = [];                               // cadres déjà posés, sur tout le mur
    var auto = els.filter(function (e) { return !e.getAttribute('data-etagere'); });
    var alterne = 0;

    function touche(r) {
      return pris.some(function (p) {
        return r.x < p.x + p.w + ECART && r.x + r.w + ECART > p.x && r.y < p.y + p.h + ECART && r.y + r.h + ECART > p.y;
      });
    }
    function hauteurs(type, b, h) {
      var t = haut + b * RANG, fin = b === bandes - 1;
      if (type === 'texte') return fin ? [t + 24, t + 64] : [t + Math.max(4, (BANDE_LIBRE - h) / 2)];
      if (type === 'image') return fin ? [t + 40, t + 80] : [t + 16, t + 56];
      return fin ? [t + 16] : [t + 10, t + 56];
    }

    els.forEach(function (el, i) {
      var d = el.dataset;
      var type = d.type, taille = d.taille || 'moyenne';
      var rot = parseFloat(d.rotation) || 0;
      el.hidden = false;
      el.style.zIndex = String(els.length - i);   // le premier de mur.txt passe devant
      if (d.telephone === 'non' && W < 600) { el.hidden = true; return; }

      var cote = COTES.indexOf(d.cote) >= 0 ? d.cote : (alterne++ % 2 ? 'droite' : 'gauche');
      var n = parseInt(d.etagere, 10);
      var pref = d.etagere === 'fin' ? bandes - 1
        : n > 0 ? Math.min(n - 1, Math.max(0, bandes - 2))
        : Math.min(bandes - 1, Math.floor((auto.indexOf(el) + 0.5) * bandes / auto.length));
      var ordre = [pref];
      for (var k = 1; k < bandes; k++) {
        if (pref + k < bandes) ordre.push(pref + k);
        if (pref - k >= 0) ordre.push(pref - k);
      }
      var echelles = [1, 0.85, 0.7];
      // épinglé : on réduit sur place avant de changer d'étagère ; sinon l'inverse
      var essais = [];
      if (d.etagere) {
        echelles.forEach(function (e) { essais.push([e, pref]); });
        echelles.forEach(function (e) { ordre.slice(1).forEach(function (b) { essais.push([e, b]); }); });
      } else {
        echelles.forEach(function (e) { ordre.forEach(function (b) { essais.push([e, b]); }); });
      }

      var place = null, derniere = null;
      for (var e = 0; e < essais.length && !place; e++) {
        var ech = essais[e][0], b = essais[e][1];
        if (ech !== derniere) { dimensionner(el, type, taille, s * ech); derniere = ech; }
        var w = el.offsetWidth, h = el.offsetHeight;
        var marge_rot = Math.ceil(Math.sin(Math.abs(rot) * Math.PI / 180) * Math.max(w, h) / 2) + 2
          + (el.classList.contains('scotch') ? 10 : 0);
        var ys = hauteurs(type, b, h), xs = positionsX(cote, w, W, marge);
        for (var iy = 0; iy < ys.length && !place; iy++) {
          for (var ix = 0; ix < xs.length; ix++) {
            var r = { x: xs[ix] - marge_rot, y: ys[iy] - marge_rot, w: w + 2 * marge_rot, h: h + 2 * marge_rot };
            if (r.x < 0 || r.x + r.w > W || r.y + r.h > H - 4 || touche(r)) continue;
            place = { x: xs[ix], y: ys[iy], r: r };
            break;
          }
        }
      }
      if (!place) { el.hidden = true; return; }  // pas de place sur cet écran : on n'affiche pas
      pris.push(place.r);
      el.style.transform = 'translate(' + Math.round(place.x) + 'px,' + Math.round(place.y) + 'px) rotate(' + rot + 'deg)';
    });
    deco.classList.add('prete');
  }

  /* ---------- le parquet ---------- */
  function dessinerParquet(plancher, W, H, s) {
    var svg = plancher.querySelector('.parquet');
    if (!svg) {
      svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
      svg.setAttribute('class', 'parquet');
      svg.setAttribute('aria-hidden', 'true');
      svg.setAttribute('preserveAspectRatio', 'none');
      plancher.insertBefore(svg, plancher.firstChild);
    }
    svg.setAttribute('viewBox', '0 0 ' + W + ' ' + H);
    var D = H * 2.4, cx = W / 2;                         // point de fuite au-dessus du sol
    function xA(x0, y) { return (cx + (x0 - cx) * (D + y) / (D + H)).toFixed(1); }
    var lame = Math.max(46, 92 * s);                     // largeur d'une lame au premier plan
    var n = Math.ceil((W / 2) / lame) + 2;
    var tons = ['var(--pq1)', 'var(--pq2)', 'var(--pq3)'];
    var ys = [0], h = H * 0.09;                          // rangées de plus en plus hautes vers l'avant
    while (ys[ys.length - 1] < H) { ys.push(Math.min(H, ys[ys.length - 1] + h)); h *= 1.32; }
    var out = '';
    for (var i = -n; i < n; i++) {
      var a = cx + i * lame, b = a + lame, r;
      for (r = 0; r < ys.length - 1; r++) {
        var y1 = ys[r], y2 = ys[r + 1];
        out += '<path d="M' + xA(a, y1) + ' ' + y1.toFixed(1) + 'L' + xA(b, y1) + ' ' + y1.toFixed(1)
          + 'L' + xA(b, y2) + ' ' + y2.toFixed(1) + 'L' + xA(a, y2) + ' ' + y2.toFixed(1) + 'Z" style="fill:'
          + tons[((i * 7 + r * 3) % 3 + 3) % 3] + '"/>';
      }
      out += '<path d="M' + xA(a, 0) + ' 0L' + a.toFixed(1) + ' ' + H + '" style="stroke:var(--pq-joint);stroke-width:1.2;fill:none"/>';
      for (r = 1; r < ys.length - 1; r++) {
        if ((r + ((i % 2) + 2) % 2) % 2) continue;       // un joint sur deux, en quinconce
        var y = ys[r].toFixed(1);
        out += '<path d="M' + xA(a, ys[r]) + ' ' + y + 'L' + xA(b, ys[r]) + ' ' + y + '" style="stroke:var(--pq-joint);stroke-width:1;fill:none;opacity:.8"/>';
      }
    }
    svg.innerHTML = out;
  }

  /* ---------- le sol ---------- */
  function placerSol() {
    var plancher = document.querySelector('.plancher');
    if (!plancher) return;
    var W = plancher.clientWidth, s = echelle(W);
    dessinerParquet(plancher, W, plancher.clientHeight, s);

    var els = Array.prototype.slice.call(plancher.querySelectorAll('.fi'));
    var marge = Math.max(8, W * 0.03), pris = [], alterne = 0, ECART_SOL = 18;
    els.forEach(function (el, i) {
      var d = el.dataset;
      el.hidden = false;
      el.style.zIndex = String(10 + els.length - i);
      el.style.height = Math.round(Math.max(32, HAUTEUR_SOL[d.taille || 'moyenne'] * s)) + 'px';
      var w = el.offsetWidth;
      var cote = COTES.indexOf(d.cote) >= 0 ? d.cote : (alterne++ % 2 ? 'droite' : 'gauche');
      var xs = positionsX(cote, w, W, marge);
      var x = null;
      for (var k = 0; k < xs.length; k++) {
        var libre = !pris.some(function (p) { return xs[k] < p[1] + ECART_SOL && xs[k] + w > p[0] - ECART_SOL; });
        if (libre) { x = xs[k]; break; }
      }
      if (x === null) { el.hidden = true; return; }
      pris.push([x, x + w]);
      el.style.transform = 'translateX(' + Math.round(x) + 'px)';
      el.classList.add('pose');
    });
  }

  /* ---------- quand tout recalculer ---------- */
  function toutPlacer() { placerMur(); placerSol(); }
  var derniereLargeur = 0, attente = null;
  function surRedimension() {
    var w = document.body.clientWidth;
    if (w === derniereLargeur) return;
    derniereLargeur = w;
    if (attente) cancelAnimationFrame(attente);
    attente = requestAnimationFrame(toutPlacer);
  }
  if (window.ResizeObserver) new ResizeObserver(surRedimension).observe(document.body);
  else window.addEventListener('resize', surRedimension);
  toutPlacer();
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(toutPlacer);
  window.addEventListener('load', toutPlacer);
})();
