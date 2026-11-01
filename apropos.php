<?php
/* Page « À propos », écrite dans a-propos.md à la racine du site. */
require __DIR__ . '/inc/gabarit.php';

$fichier = RACINE . '/a-propos.md';
entete('À propos', 'page');
?>
<main class="mur mur-page">
  <article class="feuille">
    <a class="retour" href="./">← Retour à l'établi</a>
    <div class="contenu">
      <?= is_file($fichier) ? markdown(file_get_contents($fichier)) : '<h1>À propos</h1><p>Rien ici pour l\'instant.</p>' ?>
    </div>
  </article>
</main>
<?php pied();
