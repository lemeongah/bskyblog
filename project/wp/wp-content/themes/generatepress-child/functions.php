<?php
/**
 * Functions.php pour GeneratePress Child Theme
 * Family UGC
 * Ce fichier est intelligent et s'adapte automatiquement à l'environnement (local/production)
 */

// Détection de l'environnement
function is_production()
{
    // Vérifie si l'URL du site contient "bsky.com" (production) ou "localhost" (local)
    $site_url = site_url();
    return (strpos($site_url, 'blog.bskygrowth.com') !== false);
}

// Enregistrer le thème parent et les styles personnalisés
add_action('wp_enqueue_scripts', 'theme_enqueue_styles');
function theme_enqueue_styles()
{
    // Toujours charger le style parent
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');

    // Utiliser le bon chemin selon l'environnement
    if (is_production()) {
        // En production, utiliser des URLs absolues pour éviter les problèmes mixtes HTTP/HTTPS
        $site_url = site_url();
        wp_enqueue_style('custom-style', $site_url . '/wp-content/uploads/custom/css/styles.css', array(), '1.0.3');
    } else {
        // En local, utiliser des chemins relatifs standards
        wp_enqueue_style('custom-style', '/wp-content/uploads/custom/css/styles.css', array(), '1.0.3');
    }
}

// Ajouter le favicon avec la bonne URL selon l'environnement
function add_favicon()
{
    if (is_production()) {
        $favicon_url = site_url('/wp-content/uploads/custom/favicon.png');
        echo '<link rel="shortcut icon" href="' . esc_url($favicon_url) . '" />';
    } else {
        echo '<link rel="shortcut icon" href="/wp-content/uploads/custom/favicon.png" />';
    }
}
add_action('wp_head', 'add_favicon');

// Insérer le logo dans le header avec JavaScript et utiliser la bonne URL selon l'environnement
function add_custom_logo_script()
{
    // Préparer les URLs selon l'environnement
    if (is_production()) {
        $logo_url = site_url('/wp-content/uploads/custom/logo.png');
        $home_url = site_url('/');
    } else {
        $logo_url = '/wp-content/uploads/custom/logo.png';
        $home_url = '/';
    }
    ?>

    <?php
}
add_action('wp_footer', 'add_custom_logo_script');

// Style supplémentaire pour placer le menu à droite du logo
function add_custom_header_css()
{
    ?>
    <style type="text/css">
        .inside-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .site-logo {
            margin-right: auto;
        }

        .main-navigation {
            margin-left: auto;
        }

        @media (max-width: 768px) {
            .inside-header {
                flex-direction: column;
            }
        }
    </style>
    <?php
}
add_action('wp_head', 'add_custom_header_css');

// Si en production, ajouter des filtres pour résoudre les problèmes de contenu mixte HTTP/HTTPS
if (is_production()) {
    function fix_mixed_content($content)
    {
        $domain = parse_url(site_url(), PHP_URL_HOST);
        // Convertir les URLs HTTP en HTTPS
        $content = str_replace('http://' . $domain, 'https://' . $domain, $content);
        return $content;
    }

    // Appliquer aux différents types de contenu
    add_filter('the_content', 'fix_mixed_content', 99);
    add_filter('widget_text_content', 'fix_mixed_content', 99);
    add_filter('wp_get_attachment_url', 'fix_mixed_content', 99);
    add_filter('generate_sidebar_layout', function ($layout) {
        if (is_page() || is_single()) {
            return 'no-sidebar';
        }
        return $layout;
    });

    // Forcer HTTPS dans les URLs d'images
    function force_https_for_images($image, $attachment_id, $size, $icon)
    {
        if (is_array($image) && isset($image[0])) {
            $image[0] = str_replace('http://', 'https://', $image[0]);
        }
        return $image;
    }
    add_filter('wp_get_attachment_image_src', 'force_https_for_images', 10, 4);
}




add_filter('generate_sidebar_layout', function ($layout) {
    if (is_page() || is_single()) {
        return 'no-sidebar';
    }
    return $layout;
});


add_filter('generate_copyright','custom_footer_copyright');
function custom_footer_copyright() {
    return '© ' . date('Y') . ' BskyGrowth. Tous droits réservés.';
}
// Fonction pour afficher notre footer personnalisé

// Ajoute une fonction pour créer un fichier footer-template.php si nécessaire
// add_action('after_switch_theme', callback: 'create_footer_template');
// add_action('generate_after_header', function () {
//     if (function_exists('pll_the_languages')) {
//         $languages = pll_the_languages([
//             'raw' => true,
//             'hide_if_empty' => 0,
//         ]);

//         if (!empty($languages)) {
//             echo '<form class="lang-switcher-form" method="get">';
//             echo '<select onchange="if(this.value) window.location.href=this.value;">';

//             foreach ($languages as $lang) {
//                 $selected = $lang['current_lang'] ? 'selected' : '';
//                 $name = strtoupper($lang['slug']);
//                 $url = $lang['url'];

//                 echo '<option value="' . esc_url($url) . '" ' . $selected . '>' . esc_html($name) . '</option>';
//             }

//             echo '</select>';
//             echo '</form>';
//         }
//     }
// });
// add_filter('wp_nav_menu_items', 'add_language_switcher_to_menu', 10, 2);


// add_filter('wp_nav_menu_items', 'add_language_switcher_to_menu', 10, 2);
// error_log('Theme location: ' . $args->theme_location);

// add_filter('wp_nav_menu_items', 'add_language_switcher_to_menu', 10, 2);
// function add_language_switcher_to_menu($items, $args)
// {
//     error_log('Theme location: ' . $args->theme_location);

//     if ($args->theme_location === 'primary' && function_exists('pll_the_languages')) {
//         $langs = pll_the_languages([
//             'raw' => 1,
//             'hide_if_empty' => 0,
//         ]);

//         $current_lang = pll_current_language();
//         $switcher = '<select class="lang-switcher-select" onchange="if(this.value) window.location.href=this.value">';
//         foreach ($langs as $lang) {
//             $selected = $lang['slug'] === $current_lang ? ' selected' : '';
//             $switcher .= '<option value="' . esc_url($lang['url']) . '"' . $selected . '>' . esc_html(strtoupper($lang['slug'])) . '</option>';
//         }
//         $switcher .= '</select>';

//         $items .= '<li class="menu-item lang-switcher-item">' . $switcher . '</li>';
//     }

//     return $items;
// }
add_action('generate_menu_bar_items', function() {
    if (function_exists('pll_the_languages')) {
        $langs = pll_the_languages([
            'raw' => 1,
            'hide_if_empty' => 0,
        ]);
        if ($langs) {
            echo '<li class="menu-item lang-switcher-item">';
            echo '<select class="lang-switcher-select" onchange="if(this.value) window.location.href=this.value">';
            foreach ($langs as $lang) {
                $selected = $lang['current_lang'] ? ' selected' : '';
                echo '<option value="' . esc_url($lang['url']) . '"' . $selected . '>' . esc_html(strtoupper($lang['slug'])) . '</option>';
            }
            echo '</select>';
            echo '</li>';
        }
    }
});

