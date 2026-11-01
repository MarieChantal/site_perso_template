<?php
/* robots.txt, servi à l'adresse /robots.txt grâce au .htaccess. */
require __DIR__ . '/inc/lecture.php';

header('Content-Type: text/plain; charset=utf-8');
$chemin = chemin_site();
?>
User-agent: *
Disallow: <?= $chemin ?>inc/
Disallow: <?= $chemin ?>projets/_modele/

Sitemap: <?= url_site() ?>sitemap.xml
