<?php
/*
 * Lecture des fichiers texte du site : fiche du site, fiches de projet, mur et sol.
 * Aucune base de données : tout vient des dossiers projets/, mur/, sol/ et dessins/.
 */

define('RACINE', dirname(__DIR__));

function h($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

/* Retire les commentaires : ligne qui commence par #, ou " # ..." en fin de ligne. */
function nettoyer_ligne($l)
{
    $l = str_replace("\r", '', $l);
    if (preg_match('/^\s*#/', $l)) {
        return '';
    }
    return rtrim(preg_replace('/\s+#\s.*$/u', '', $l));
}

function lignes($fichier)
{
    if (!is_file($fichier)) {
        return [];
    }
    $texte = preg_replace('/^\xEF\xBB\xBF/', '', file_get_contents($fichier));
    return explode("\n", $texte);
}

/*
 * Fiche "cle: valeur".
 * Une ligne indentée continue la valeur précédente (pour les résumés longs).
 */
function lire_fiche($fichier)
{
    $fiche = [];
    $derniere = null;
    foreach (lignes($fichier) as $l) {
        $l = nettoyer_ligne($l);
        if (trim($l) === '') {
            continue;
        }
        if (!preg_match('/^\s/', $l) && preg_match('/^([^:\s]+):\s*(.*)$/u', $l, $m)) {
            $derniere = strtolower($m[1]);
            $fiche[$derniere] = trim($m[2]);
        } elseif ($derniere !== null) {
            $fiche[$derniere] = trim($fiche[$derniere] . ' ' . trim($l));
        }
    }
    return $fiche;
}

/* "taille: moyenne  cote: gauche" : paires séparées par deux espaces ou plus. */
function lire_options($texte, array &$bloc)
{
    foreach (preg_split('/\s{2,}|\t+/', trim($texte)) as $morceau) {
        if (preg_match('/^([^:\s]+):\s*(.*)$/u', $morceau, $m)) {
            $bloc[strtolower($m[1])] = trim($m[2]);
        }
    }
}

/*
 * Liste de blocs, pour mur.txt et sol.txt :
 *   type: valeur
 *     option: x   option: y
 */
function lire_blocs($fichier)
{
    $blocs = [];
    foreach (lignes($fichier) as $l) {
        $l = nettoyer_ligne($l);
        if (trim($l) === '') {
            continue;
        }
        if (!preg_match('/^\s/', $l) && preg_match('/^([^:\s]+):\s*(.*)$/u', $l, $m)) {
            $morceaux = preg_split('/\s{2,}|\t+/', trim($m[2]), 2);
            $bloc = ['type' => strtolower($m[1]), 'valeur' => trim($morceaux[0])];
            if (isset($morceaux[1])) {
                lire_options($morceaux[1], $bloc);
            }
            $blocs[] = $bloc;
        } elseif ($blocs) {
            lire_options($l, $blocs[count($blocs) - 1]);
        }
    }
    return $blocs;
}

/* Réglages généraux, dans site.txt. */
function site()
{
    static $site = null;
    if ($site === null) {
        $site = lire_fiche(RACINE . '/site.txt') + [
            'nom' => 'Mon atelier',
            'auteur' => '',
            'email' => '',
            'description' => 'Les projets que je fabrique.',
            'debut' => date('Y'),
        ];
    }
    return $site;
}

/*
 * Adresse complète du site, terminée par / (utile pour le flux RSS).
 * Prise dans site.txt (adresse: https://...), sinon devinée à partir de la requête.
 */
function url_site()
{
    $adresse = trim(site()['adresse'] ?? '');
    if ($adresse !== '') {
        return rtrim($adresse, '/') . '/';
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $hote = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dossier = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    return ($https ? 'https' : 'http') . '://' . $hote . $dossier . '/';
}

function oui($valeur)
{
    return in_array(strtolower(trim((string) $valeur)), ['oui', 'yes', 'vrai', '1'], true);
}

function couleur($c, $defaut = '#9aa3a8')
{
    return preg_match('/^#[0-9a-f]{3,8}$/i', trim((string) $c)) ? trim($c) : $defaut;
}

function taille($bloc)
{
    $t = strtolower($bloc['taille'] ?? '');
    return in_array($t, ['petite', 'moyenne', 'grande'], true) ? $t : 'moyenne';
}

function slug_valide($slug)
{
    return is_string($slug) && preg_match('/^[a-z0-9][a-z0-9_-]*$/i', $slug);
}

function date_fr($date)
{
    $mois = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
    $x = explode('-', $date);
    return $mois[max(0, min(11, (int) $x[1] - 1))] . ' ' . $x[0];
}

/*
 * Tous les projets visibles, du plus récemment mis à jour au plus ancien.
 * Un dossier qui commence par _ est ignoré (brouillon, modèle).
 */
function liste_projets()
{
    static $projets = null;
    if ($projets !== null) {
        return $projets;
    }
    $projets = [];
    foreach (glob(RACINE . '/projets/*/projet.txt') ?: [] as $fichier) {
        $dossier = dirname($fichier);
        $slug = basename($dossier);
        if (!slug_valide($slug) || $slug[0] === '_') {
            continue;
        }
        $p = lire_fiche($fichier);
        if (oui($p['cache'] ?? '')) {
            continue;
        }
        $p['slug'] = $slug;
        $p['dossier'] = $dossier;
        if (empty($p['titre'])) {
            $p['titre'] = ucfirst(str_replace(['-', '_'], ' ', $slug));
        }
        if (empty($p['maj']) || !preg_match('/^\d{4}-\d{2}(-\d{2})?$/', $p['maj'])) {
            $p['maj'] = date('Y-m-d', filemtime($fichier));
        }
        $projets[] = $p;
    }
    usort($projets, function ($a, $b) {
        return strcmp($b['maj'], $a['maj']) ?: strcmp($a['titre'], $b['titre']);
    });
    return $projets;
}

function projet($slug)
{
    if (!slug_valide($slug)) {
        return null;
    }
    foreach (liste_projets() as $p) {
        if ($p['slug'] === $slug) {
            return $p;
        }
    }
    return null;
}

/* Où mène l'objet : lien externe, page HTML du dossier, ou page générée. */
function destination($p)
{
    if (!empty($p['lien'])) {
        return ['url' => $p['lien'], 'externe' => true];
    }
    if (is_file($p['dossier'] . '/index.html')) {
        return ['url' => 'projets/' . rawurlencode($p['slug']) . '/', 'externe' => false];
    }
    return ['url' => 'projet.php?p=' . rawurlencode($p['slug']), 'externe' => false];
}

/* Insère un fichier SVG dans la page pour qu'il prenne les couleurs de l'atelier. */
function svg_inline($fichier, $style = '')
{
    $svg = @file_get_contents($fichier);
    if ($svg === false) {
        return '';
    }
    $svg = preg_replace('/<\?xml.*?\?>|<!DOCTYPE.*?>|<!--.*?-->/s', '', $svg);
    $svg = preg_replace('/<script\b.*?<\/script>/is', '', $svg);
    $attrs = '<svg class="o" aria-hidden="true" focusable="false"' . ($style !== '' ? ' style="' . h($style) . '"' : '');
    return preg_replace('/<svg\b/', $attrs, trim($svg), 1);
}

/* Rapport largeur / hauteur d'une image, pour réserver sa place avant chargement. */
function ratio_image($fichier)
{
    if (preg_match('/\.svg$/i', $fichier)) {
        $svg = @file_get_contents($fichier);
        if ($svg && preg_match('/viewBox="\s*[-\d.]+[\s,]+[-\d.]+[\s,]+([\d.]+)[\s,]+([\d.]+)/', $svg, $m) && $m[2] > 0) {
            return round($m[1] / $m[2], 4);
        }
        return 1;
    }
    $info = @getimagesize($fichier);
    return ($info && $info[1]) ? round($info[0] / $info[1], 4) : 1;
}

/*
 * L'objet d'un projet, par ordre de priorité :
 * 1. objet.svg / objet.png / ... dans le dossier du projet
 * 2. dessins/<objet>.svg (la bibliothèque, que tu peux compléter)
 * 3. une place vide en pointillés
 */
function objet_html($p)
{
    $style = '--of:' . couleur($p['couleur'] ?? '');
    foreach (['svg', 'png', 'webp', 'jpg', 'jpeg', 'gif'] as $ext) {
        $f = $p['dossier'] . '/objet.' . $ext;
        if (is_file($f)) {
            return $ext === 'svg'
                ? svg_inline($f, $style)
                : '<img src="' . h('projets/' . $p['slug'] . '/objet.' . $ext) . '" alt="">';
        }
    }
    $nom = preg_replace('/[^a-z0-9_-]/i', '', $p['objet'] ?? '');
    $f = RACINE . '/dessins/' . $nom . '.svg';
    if ($nom === '' || !is_file($f)) {
        $f = RACINE . '/dessins/vide.svg';
    }
    return svg_inline($f, $style);
}
