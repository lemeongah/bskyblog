<?php
/**
 * The header for our theme.
 *
 * @package GeneratePress Child
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
    <script src="https://unpkg.com/css-doodle@0.15.3/css-doodle.min.js"></script>
</head>
<script type="text/javascript" src="https://s.skimresources.com/js/292380X1779517.skimlinks.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const ul = document.querySelector('.home-mosaic.wp-block-latest-posts.is-grid');
  if (!ul || window.innerWidth <= 1000) return;

  const items = Array.from(ul.children);
  if (items.length === 0) return;

  // Définir les patterns de lignes possibles
  const patterns = [
    [3],        // full-width (toute la largeur)
    [2, 1],     // wide + normal
    [1, 1, 1],  // 3 normales
    [1, 2],     // normal + wide
    [1, 1, 1],  // 3 normales (répété pour plus de variété)
  ];

  let currentIndex = 0;
  let previousPattern = -1;

  // TOUJOURS commencer par une carte full-width
  if (items.length > 0) {
    const firstItem = items[0];
    firstItem.classList.remove('wide', 'tall', 'single-normal');
    firstItem.classList.add('full-width');
    currentIndex = 1;
  }

  // Fonction pour choisir le prochain pattern (différent du précédent)
  function getNextPattern() {
    let nextPattern;
    do {
      // Exclure le pattern [3] après le premier élément pour éviter trop de full-width
      const availablePatterns = patterns.slice(1); // Enlever [3] du choix
      nextPattern = Math.floor(Math.random() * availablePatterns.length) + 1; // +1 car on a retiré le premier
    } while (nextPattern === previousPattern && patterns.length > 2);

    previousPattern = nextPattern;
    return patterns[nextPattern];
  }

  // Appliquer les patterns ligne par ligne pour le reste
  while (currentIndex < items.length) {
    const currentPattern = getNextPattern();
    let itemsInCurrentRow = 0;
    const currentRowItems = []; // Garder trace des items de la ligne courante

    // Appliquer le pattern à la ligne courante
    for (let i = 0; i < currentPattern.length && currentIndex < items.length; i++) {
      const item = items[currentIndex];
      const cellSize = currentPattern[i];

      // Nettoyer les classes existantes
      item.classList.remove('wide', 'tall', 'single-normal', 'full-width');

      // Appliquer la bonne classe selon la taille
      if (cellSize === 3) {
        item.classList.add('full-width');
        itemsInCurrentRow += 3;
      } else if (cellSize === 2) {
        item.classList.add('wide');
        itemsInCurrentRow += 2;
      } else {
        // cellSize === 1, carte normale
        itemsInCurrentRow += 1;
      }

      currentRowItems.push({ item, cellSize });
      currentIndex++;

      // Si on a rempli 3 colonnes, passer à la ligne suivante
      if (itemsInCurrentRow >= 3) break;
    }

    // Identifier les cartes normales seules (patterns 2/1 ou 1/2)
    const normalCards = currentRowItems.filter(({ cellSize }) => cellSize === 1);
    if (normalCards.length === 1) {
      // Il n'y a qu'une seule carte normale dans cette ligne
      normalCards[0].item.classList.add('single-normal');
    }
  }

  console.log('🎨 Mosaïque organisée avec patterns alternés, cartes full-width et gestion des extraits');
});
</script>


<body <?php body_class(); ?>>
    <?php
    /**
     * wp_body_open hook.
     *
     * @since 2.3
     */
    do_action('wp_body_open');
    ?>


    <div id="page" class="hfeed site">
        <?php
        /**
         * generate_before_header hook.
         *
         * @since 0.1
         */
        do_action('generate_before_header');

        /**
         * generate_header hook.
         *
         * @since 1.3.42
         *
         * @hooked generate_construct_header - 10
         */
        do_action('generate_header');

        /**
         * generate_after_header hook.
         *
         * @since 0.1
         */
        do_action('generate_after_header');
        ?>

        <div id="content" class="site-content">
            <?php
            /**
             * generate_inside_container hook.
             *
             * @since 0.1
             */
            do_action('generate_inside_container');
            ?>