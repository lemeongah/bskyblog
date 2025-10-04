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
        // Charger les styles de mosaïque séparément
        wp_enqueue_style('mosaic-style', $site_url . '/wp-content/uploads/custom/mosaic-styles.css', array('custom-style'), '1.0.1');
    } else {
        // En local, utiliser des chemins relatifs standards
        wp_enqueue_style('custom-style', '/wp-content/uploads/custom/css/styles.css', array(), '1.0.3');
        // Charger les styles de mosaïque séparément
        wp_enqueue_style('mosaic-style', '/wp-content/uploads/custom/mosaic-styles.css', array('custom-style'), '1.0.1');
    }
}

// JavaScript pour gérer les clics sur les cartes wide et full-width
add_action('wp_footer', 'add_mosaic_click_handler');
function add_mosaic_click_handler() {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Vérifier si on est sur la page d'accueil
        const isHomePage = document.body.classList.contains('home') ||
                          document.body.classList.contains('blog') ||
                          window.location.pathname === '/' ||
                          window.location.pathname === '/index.php';

        // Gérer les clics sur les cartes wide
        const wideCards = document.querySelectorAll('.home-mosaic li.wide');

        wideCards.forEach(function(card) {
            const titleLink = card.querySelector('.wp-block-latest-posts__post-title');

            if (titleLink && titleLink.href) {
                // Ajouter un gestionnaire de clic sur toute la carte
                card.addEventListener('click', function(e) {
                    // Ne pas déclencher si on clique directement sur le titre
                    if (e.target === titleLink || titleLink.contains(e.target)) {
                        return;
                    }

                    // Sinon, rediriger vers l'URL du titre
                    window.location.href = titleLink.href;
                });

                // Ajouter un curseur pointer sur la carte
                card.style.cursor = 'pointer';
            }
        });

        // Fonction pour récupérer la catégorie d'un article via AJAX
        function getPostCategory(postUrl, callback) {
            // Extraire l'ID du post depuis l'URL si possible
            const postId = postUrl.match(/\?p=(\d+)/);
            if (postId) {
                // Utiliser l'API REST WordPress pour récupérer les données du post
                fetch('/wp-json/wp/v2/posts/' + postId[1])
                    .then(response => response.json())
                    .then(data => {
                        if (data.categories && data.categories.length > 0) {
                            // Récupérer le nom de la première catégorie
                            fetch('/wp-json/wp/v2/categories/' + data.categories[0])
                                .then(response => response.json())
                                .then(categoryData => {
                                    callback(categoryData.name);
                                })
                                .catch(() => callback('Article'));
                        } else {
                            callback('Article');
                        }
                    })
                    .catch(() => callback('À la une')); // Fallback si l'API échoue
            } else {
                // Essayer d'extraire depuis l'URL pretty permalinks
                const slug = postUrl.split('/').filter(part => part.length > 0).pop();
                fetch('/wp-json/wp/v2/posts?slug=' + slug)
                    .then(response => response.json())
                    .then(data => {
                        if (data.length > 0 && data[0].categories && data[0].categories.length > 0) {
                            fetch('/wp-json/wp/v2/categories/' + data[0].categories[0])
                                .then(response => response.json())
                                .then(categoryData => {
                                    callback(categoryData.name);
                                })
                                .catch(() => callback('Article'));
                        } else {
                            callback('À la une');
                        }
                    })
                    .catch(() => callback('À la une'));
            }
        }

        // Gérer les cartes full-width - restructurer complètement le HTML
        const fullWidthCards = document.querySelectorAll('.home-mosaic li.full-width');

        fullWidthCards.forEach(function(card) {
            const titleLink = card.querySelector('.wp-block-latest-posts__post-title');
            const featuredImage = card.querySelector('.wp-block-latest-posts__featured-image');
            const excerpt = card.querySelector('.wp-block-latest-posts__post-excerpt');

            if (titleLink && featuredImage) {
                // Sauvegarder les données avant restructuration
                const titleText = titleLink.textContent;
                const titleHref = titleLink.href;
                const excerptText = excerpt ? excerpt.textContent : '';

                // Si on est sur la page d'accueil, récupérer et afficher les catégories
                if (isHomePage) {
                    // Récupérer la vraie catégorie de l'article
                    getPostCategory(titleHref, function(categoryName) {
                        // Vider complètement la carte
                        card.innerHTML = '';

                        // Recréer la structure HTML avec l'image d'abord
                        const imageContainer = featuredImage.cloneNode(true);
                        card.appendChild(imageContainer);

                        // Créer le conteneur de contenu
                        const contentDiv = document.createElement('div');
                        contentDiv.className = 'full-width-content';

                        // Créer l'élément catégorie avec la vraie catégorie (utiliser innerHTML pour supporter les entités HTML)
                        const categorySpan = document.createElement('span');
                        categorySpan.className = 'post-category';
                        // Utiliser innerHTML au lieu de textContent pour gérer les entités HTML comme &
                        categorySpan.innerHTML = categoryName;
                        contentDiv.appendChild(categorySpan);

                        // Recréer le titre
                        const newTitle = document.createElement('a');
                        newTitle.className = 'wp-block-latest-posts__post-title';
                        newTitle.href = titleHref;
                        newTitle.textContent = titleText;
                        contentDiv.appendChild(newTitle);

                        // Recréer l'extrait si il existe
                        if (excerptText.trim()) {
                            const newExcerpt = document.createElement('div');
                            newExcerpt.className = 'wp-block-latest-posts__post-excerpt';
                            newExcerpt.textContent = excerptText;
                            contentDiv.appendChild(newExcerpt);
                        }

                        // Ajouter le conteneur de contenu à la carte
                        card.appendChild(contentDiv);

                        // Forcer les styles CSS si nécessaire
                        card.style.display = 'flex';
                        card.style.flexDirection = 'row';
                        imageContainer.style.flex = '0 0 50%';
                        imageContainer.style.width = '50%';
                        contentDiv.style.flex = '0 0 50%';
                        contentDiv.style.width = '50%';

                        // Gestionnaire de clic pour toute la carte
                        card.addEventListener('click', function(e) {
                            // Ne pas déclencher si on clique sur des liens spécifiques
                            if (e.target.tagName === 'A' || e.target.closest('a')) {
                                return;
                            }

                            window.location.href = titleHref;
                        });

                        card.style.cursor = 'pointer';

                        console.log('✅ Carte full-width restructurée avec catégorie:', titleText, 'Catégorie:', categoryName);
                    });
                } else {
                    // Si on n'est pas sur la page d'accueil, restructurer sans catégorie
                    // Vider complètement la carte
                    card.innerHTML = '';

                    // Recréer la structure HTML avec l'image d'abord
                    const imageContainer = featuredImage.cloneNode(true);
                    card.appendChild(imageContainer);

                    // Créer le conteneur de contenu
                    const contentDiv = document.createElement('div');
                    contentDiv.className = 'full-width-content';

                    // Recréer le titre SANS catégorie
                    const newTitle = document.createElement('a');
                    newTitle.className = 'wp-block-latest-posts__post-title';
                    newTitle.href = titleHref;
                    newTitle.textContent = titleText;
                    contentDiv.appendChild(newTitle);

                    // Recréer l'extrait si il existe
                    if (excerptText.trim()) {
                        const newExcerpt = document.createElement('div');
                        newExcerpt.className = 'wp-block-latest-posts__post-excerpt';
                        newExcerpt.textContent = excerptText;
                        contentDiv.appendChild(newExcerpt);
                    }

                    // Ajouter le conteneur de contenu à la carte
                    card.appendChild(contentDiv);

                    // Forcer les styles CSS si nécessaire
                    card.style.display = 'flex';
                    card.style.flexDirection = 'row';
                    imageContainer.style.flex = '0 0 50%';
                    imageContainer.style.width = '50%';
                    contentDiv.style.flex = '0 0 50%';
                    contentDiv.style.width = '50%';

                    // Gestionnaire de clic pour toute la carte
                    card.addEventListener('click', function(e) {
                        // Ne pas déclencher si on clique sur des liens spécifiques
                        if (e.target.tagName === 'A' || e.target.closest('a')) {
                            return;
                        }

                        window.location.href = titleHref;
                    });

                    card.style.cursor = 'pointer';

                    console.log('✅ Carte full-width restructurée sans catégorie:', titleText);
                }
            }
        });

        console.log('🎨 Restructuration des cartes full-width terminée - Page d\'accueil:', isHomePage);
    });
    </script>
    <?php
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

// Forcer l'utilisation d'images haute résolution
add_filter('wp_get_attachment_image_attributes', 'force_high_quality_images', 10, 3);
function force_high_quality_images($attr, $attachment, $size) {
    // Pour les images dans la mosaïque, forcer une taille plus grande
    if (is_front_page() || is_home()) {
        // Utiliser 'large' au lieu de 'medium' pour une meilleure qualité
        if ($size === 'medium' || $size === 'thumbnail') {
            $image_src = wp_get_attachment_image_src($attachment->ID, 'large');
            if ($image_src) {
                $attr['src'] = $image_src[0];
                $attr['srcset'] = wp_get_attachment_image_srcset($attachment->ID, 'large');
            }
        }
    }
    return $attr;
}
