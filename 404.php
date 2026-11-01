<?php
/* Page affichée quand une adresse n'existe pas (voir ErrorDocument dans .htaccess). */
require __DIR__ . '/inc/gabarit.php';

http_response_code(404);
entete('Page introuvable', 'page', true);
?>
<main class="mur mur-page">
  <article class="feuille">
    <a class="retour" href="./">← Retour à l'établi</a>
    <h1>Rien sur cette étagère</h1>
    <p>Cette page n'existe pas, ou elle a été rangée ailleurs.</p>
  </article>
</main>
<?php pied();
