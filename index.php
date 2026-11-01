<?php
/* Page d'accueil : l'établi, avec un objet par projet. */
require __DIR__ . '/inc/gabarit.php';

$projets = liste_projets();
entete('', 'accueil');
?>
<main class="mur" id="mur">
  <div class="deco" aria-hidden="true"><?= deco_mur() ?></div>
  <h1 class="discret">Projets</h1>
  <div class="etageres">
    <?php foreach ($projets as $p):
        $dest = destination($p); ?>
    <a class="projet" href="<?= h($dest['url']) ?>"<?= $dest['externe'] ? ' target="_blank" rel="noopener"' : '' ?><?= !empty($p['resume']) ? ' title="' . h($p['resume']) . '"' : '' ?>>
      <span class="art"><?= objet_html($p) ?></span>
      <span class="etiquette"><b><?= h($p['titre']) ?></b><small><?= h(date_fr($p['maj'])) ?></small></span>
    </a>
    <?php endforeach; ?>
    <div class="projet vide" aria-hidden="true">
      <span class="art"><?= svg_inline(RACINE . '/dessins/vide.svg') ?></span>
      <span class="etiquette"><b>la suite…</b></span>
    </div>
  </div>
</main>
<?php pied();
