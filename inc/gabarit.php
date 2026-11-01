<?php
/* Morceaux communs à toutes les pages : en-tête, déco du mur, sol et pied de page. */

require_once __DIR__ . '/lecture.php';

function entete($titre = '', $classe = '')
{
    $site = site();
    $titre_complet = $titre !== '' ? $titre . ' · ' . $site['nom'] : $site['nom'];
    $apropos = is_file(RACINE . '/a-propos.md');
    ?><!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= h($titre_complet) ?></title>
<meta name="description" content="<?= h($site['description']) ?>">
<script>
  /* jour ou nuit : le choix du visiteur, sinon le réglage de son appareil */
  (function () {
    var m = null;
    try { m = localStorage.getItem('mode'); } catch (e) {}
    if (m !== 'jour' && m !== 'nuit') m = window.matchMedia && matchMedia('(prefers-color-scheme: dark)').matches ? 'nuit' : 'jour';
    document.documentElement.setAttribute('data-mode', m);
  })();
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,600;12..96,800&family=Caveat:wght@600;700&family=IBM+Plex+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="assets/atelier.css">
<link rel="alternate" type="application/rss+xml" title="<?= h($site['nom']) ?>" href="flux.php">
</head>
<body class="<?= h($classe) ?>">
<div class="lumiere" aria-hidden="true"></div>
<header class="entete">
  <a class="nom" href="./"><?= h($site['nom']) ?></a>
  <nav>
    <a class="lien-nav" href="./">Projets</a>
    <?php if ($apropos): ?><a class="lien-nav" href="apropos.php">À propos</a><?php endif; ?>
    <button class="bascule" type="button" aria-pressed="false">
      <svg class="ic-lune" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 14.5A8 8 0 1 1 9.5 4a6.5 6.5 0 0 0 10.5 10.5z"/></svg>
      <svg class="ic-soleil" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
      <span class="libelle">Jour</span>
    </button>
  </nav>
</header>
<?php
}

/* Les éléments accrochés au mur, lus dans mur/mur.txt. Le placement est fait par assets/atelier.js. */
function deco_mur()
{
    $html = '';
    foreach (lire_blocs(RACINE . '/mur/mur.txt') as $d) {
        $type = $d['type'];
        $attrs = ' data-type="' . h($type) . '"'
            . ' data-taille="' . h(taille($d)) . '"'
            . ' data-cote="' . h(strtolower($d['cote'] ?? '')) . '"'
            . ' data-etagere="' . h(strtolower($d['etagere'] ?? '')) . '"'
            . ' data-rotation="' . h((float) ($d['rotation'] ?? 0)) . '"'
            . ' data-telephone="' . h(strtolower($d['telephone'] ?? 'oui')) . '"';

        if ($type === 'texte') {
            $html .= '<div class="d-texte"' . $attrs . '>' . h($d['valeur']) . '</div>';
            continue;
        }
        if (!in_array($type, ['affiche', 'photo', 'image'], true)) {
            continue;
        }
        $nom = basename($d['valeur']);
        $fichier = RACINE . '/mur/' . $nom;
        if ($nom === '' || !is_file($fichier)) {
            continue;
        }
        $src = h('mur/' . $nom);
        $ratio = ratio_image($fichier);
        if ($type === 'affiche') {
            $html .= '<div class="d-affiche scotch"' . $attrs . ' style="aspect-ratio:' . $ratio . '"><img src="' . $src . '" alt=""></div>';
        } elseif ($type === 'photo') {
            $html .= '<figure class="d-photo scotch"' . $attrs . '><img src="' . $src . '" alt="" style="aspect-ratio:' . $ratio . '">'
                . (!empty($d['legende']) ? '<figcaption>' . h($d['legende']) . '</figcaption>' : '') . '</figure>';
        } else {
            $html .= '<div class="d-image"' . $attrs . ' style="aspect-ratio:' . $ratio . '"><img src="' . $src . '" alt=""></div>';
        }
    }
    return $html;
}

/* Ce qui traîne par terre, lu dans sol/sol.txt. */
function objets_sol()
{
    $html = '';
    foreach (lire_blocs(RACINE . '/sol/sol.txt') as $d) {
        $attrs = ' data-taille="' . h(taille($d)) . '" data-cote="' . h(strtolower($d['cote'] ?? '')) . '"';
        if ($d['type'] === 'objet') {
            $nom = preg_replace('/[^a-z0-9_-]/i', '', $d['valeur']);
            $f = RACINE . '/dessins/sol/' . $nom . '.svg';
            if ($nom === '' || !is_file($f)) {
                continue;
            }
            $style = '--of:' . couleur($d['couleur'] ?? '', 'none');
            if (!empty($d['trait'])) {
                $style .= ';stroke:' . couleur($d['trait']);
            }
            $html .= '<div class="fi"' . $attrs . '>' . svg_inline($f, $style) . '</div>';
        } elseif ($d['type'] === 'image') {
            $nom = basename($d['valeur']);
            if ($nom === '' || !is_file(RACINE . '/sol/' . $nom)) {
                continue;
            }
            $html .= '<div class="fi"' . $attrs . '><img src="' . h('sol/' . $nom) . '" alt=""></div>';
        }
    }
    return $html;
}

function pied()
{
    $site = site();
    $annees = ($site['debut'] && $site['debut'] != date('Y')) ? $site['debut'] . '–' . date('Y') : date('Y');
    ?>
<footer class="sol">
  <div class="plinthe"></div>
  <div class="plancher">
    <?= objets_sol() ?>
    <div class="credits">
      <span>© <?= h($annees) ?><?= $site['auteur'] !== '' ? ' ' . h($site['auteur']) : '' ?></span>
      <?php if ($site['email'] !== ''): ?><a href="mailto:<?= h($site['email']) ?>"><?= h($site['email']) ?></a><?php endif; ?>
      <a href="flux.php">s'abonner (RSS)</a>
      <span>fait main, hébergé chez OVH</span>
    </div>
  </div>
</footer>
<script src="assets/atelier.js"></script>
</body>
</html>
<?php
}

/*
 * Markdown → HTML. Les liens et images relatifs (photo.jpg) pointent vers le dossier
 * du projet, puisque la page est servie depuis la racine du site.
 */
function markdown($texte, $dossier_web = '')
{
    require_once __DIR__ . '/Parsedown.php';
    $html = (new Parsedown())->text($texte);
    if ($dossier_web === '') {
        return $html;
    }
    return preg_replace_callback('/\b(src|href)="([^"]*)"/', function ($m) use ($dossier_web) {
        $url = $m[2];
        if ($url === '' || preg_match('#^([a-z][a-z0-9+.-]*:|/|\#|\?)#i', $url)) {
            return $m[0];
        }
        return $m[1] . '="' . $dossier_web . $url . '"';
    }, $html);
}
