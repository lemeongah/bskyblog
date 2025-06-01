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

<body <?php body_class(); ?>>
    <?php
    /**
     * wp_body_open hook.
     *
     * @since 2.3
     */
    do_action('wp_body_open');
    ?>
    <css-doodle>
        :doodle {
        @grid: 1x1;
        position: fixed;
        top: 0;
        left: 0;
        z-index: -1;
        width: 100vw;
        height: 100vh;
        opacity: 0.4;
        pointer-events: none;
        background: #fff;
        }

        background: radial-gradient(
        circle at @rand(80%) @rand(100%),
        rgba(18, 89, 77, @rand(.90, .15)),
        transparent 70%
        );

        animation: move 10s ease-in-out infinite alternate;

        @keyframes move {
        0% { transform: scale(1) translate(1, 0); }
        100% { transform: scale(1.2) translate(10px, -10px); }
        }
    </css-doodle>
    <div class="logo-background-spot"></div>

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