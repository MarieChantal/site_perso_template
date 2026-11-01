<?php
/*
 * Page d'un projet, générée à partir de son dossier :
 * - page.md s'il existe (Markdown) ;
 * - sinon le résumé de la fiche et les images du dossier, en galerie.
 * Les projets avec un lien ou un index.html n'ont pas besoin de cette page.
 */
require __DIR__ . '/inc/gabarit.php';

$p = projet($_GET['p'] ?? '');

if ($p === null) {
    http_response_code(404);
    entete('Introuvable', 'page');
    ?>
<main class="mur mur-page">
  <article class="feuille">
    <a class="retour" href="./">← Retour à l'établi</a>
    <h1>Rien sur cette étagère</h1>
    <p>Ce projet n'existe pas, ou il a été rangé ailleurs.</p>
  </article>
</main>
<?php
    pied();
    exit;
}

$dest = destination($p);
if (strpos($dest['url'], 'projet.php') !== 0) {
    header('Location: ' . $dest['url'], true, 302);
    exit;
}

$web = 'projets/' . rawurlencode($p['slug']) . '/';
$md = $p['dossier'] . '/page.md';

entete($p['titre'], 'page');
?>
<main class="mur mur-page">
  <article class="feuille">
    <a class="retour" href="./">← Retour à l'établi</a>
    <header class="feuille-titre">
      <span class="feuille-objet"><?= objet_html($p) ?></span>
      <div>
        <h1><?= h($p['titre']) ?></h1>
        <p class="feuille-date">Mis à jour en <?= h(date_fr($p['maj'])) ?></p>
      </div>
    </header>
    <div class="contenu">
<?php if (is_file($md)): ?>
      <?= markdown(file_get_contents($md), $web) ?>
<?php else: ?>
      <?php if (!empty($p['resume'])): ?><p><?= h($p['resume']) ?></p><?php endif; ?>
      <?php
        $images = [];
        foreach (array_merge(glob($p['dossier'] . '/*') ?: [], glob($p['dossier'] . '/photos/*') ?: []) as $f) {
            if (preg_match('/\.(jpe?g|png|webp|gif)$/i', $f) && !preg_match('/^objet\./i', basename($f))) {
                $images[] = substr($f, strlen($p['dossier']) + 1);
            }
        }
        if ($images): ?>
      <div class="galerie">
        <?php foreach ($images as $img): ?><a href="<?= h($web . $img) ?>"><img src="<?= h($web . $img) ?>" alt="" loading="lazy"></a><?php endforeach; ?>
      </div>
      <?php endif; ?>
<?php endif; ?>
    </div>
  </article>
</main>
<?php pied();
