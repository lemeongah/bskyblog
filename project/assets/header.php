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
/* document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.home-mosaic.wp-block-latest-posts.is-grid').forEach(ul => {
    const items = Array.from(ul.children);
    // ~30% des cartes deviennent "wide", sans casser l’ordre
    items.forEach((li, i) => {
      if (window.innerWidth > 1000 && Math.random() < 0.30) li.classList.add('wide');
      // si tu veux parfois des "tall"
      // if (Math.random() < 0.10) li.classList.add('tall');
    });
  });
}); */
document.addEventListener('DOMContentLoaded', function () {
  const ul = document.querySelector('.home-mosaic.wp-block-latest-posts.is-grid');
  if (!ul) return;
  [...ul.children].forEach(li => {
    if (window.innerWidth > 1000 && Math.random() < 0.30) li.classList.add('wide'); // ~30% en 2 colonnes
  });
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