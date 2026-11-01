<?php
/*
 * Plan du site pour les moteurs de recherche, servi à l'adresse /sitemap.xml grâce au .htaccess.
 * Il se met à jour tout seul : chaque projet hébergé sur le site y figure avec sa date "maj".
 * Les projets qui mènent à un autre site (lien:) n'y sont pas, puisqu'ils ne sont pas ici.
 */
require __DIR__ . '/inc/lecture.php';

$base = url_site();
$projets = liste_projets();
$pages = [];

$pages[] = ['loc' => $base, 'lastmod' => $projets ? $projets[0]['maj'] : null];
if (is_file(RACINE . '/a-propos.md')) {
    $pages[] = ['loc' => $base . 'apropos.php', 'lastmod' => date('Y-m-d', filemtime(RACINE . '/a-propos.md'))];
}
foreach ($projets as $p) {
    $dest = destination($p);
    if (!$dest['externe']) {
        $pages[] = ['loc' => $base . $dest['url'], 'lastmod' => $p['maj']];
    }
}

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($pages as $page): ?>
  <url>
    <loc><?= h($page['loc']) ?></loc>
<?php if ($page['lastmod']): ?>
    <lastmod><?= h(strlen($page['lastmod']) === 7 ? $page['lastmod'] . '-01' : $page['lastmod']) ?></lastmod>
<?php endif; ?>
  </url>
<?php endforeach; ?>
</urlset>
