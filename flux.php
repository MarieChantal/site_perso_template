<?php
/*
 * Flux RSS des projets, pour que les lecteurs s'abonnent.
 * Un élément par projet, du plus récemment mis à jour au plus ancien.
 * Changer la date "maj" d'un projet le fait réapparaître comme nouveauté.
 */
require __DIR__ . '/inc/gabarit.php';

$site = site();
$base = url_site();
$projets = array_slice(liste_projets(), 0, 30);

function date_rss($maj)
{
    $t = strtotime(strlen($maj) === 7 ? $maj . '-01' : $maj);
    return date(DATE_RSS, $t ?: time());
}

function cdata($html)
{
    return '<![CDATA[' . str_replace(']]>', ']]]]><![CDATA[>', $html) . ']]>';
}

header('Content-Type: application/rss+xml; charset=utf-8');
header('Cache-Control: max-age=3600');

echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/">
<channel>
  <title><?= h($site['nom']) ?></title>
  <link><?= h($base) ?></link>
  <description><?= h($site['description']) ?></description>
  <language>fr</language>
  <atom:link href="<?= h($base . 'flux.php') ?>" rel="self" type="application/rss+xml"/>
<?php if ($projets): ?>
  <lastBuildDate><?= date_rss($projets[0]['maj']) ?></lastBuildDate>
<?php endif; ?>
<?php foreach ($projets as $p):
    $dest = destination($p);
    $lien = $dest['externe'] ? $dest['url'] : $base . $dest['url'];
    $md = $p['dossier'] . '/page.md';
    ?>
  <item>
    <title><?= h($p['titre']) ?></title>
    <link><?= h($lien) ?></link>
    <guid isPermaLink="false"><?= h($p['slug'] . '@' . $p['maj']) ?></guid>
    <pubDate><?= date_rss($p['maj']) ?></pubDate>
    <description><?= h($p['resume'] ?? '') ?></description>
<?php if (!$dest['externe'] && is_file($md)): ?>
    <content:encoded><?= cdata(markdown(file_get_contents($md), $base . 'projets/' . rawurlencode($p['slug']) . '/')) ?></content:encoded>
<?php endif; ?>
  </item>
<?php endforeach; ?>
</channel>
</rss>
