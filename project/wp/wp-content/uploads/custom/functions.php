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
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function () {
            // Créer un élément pour le logo
            var logoHtml = '<div class="site-logo"><a href="<?php echo $home_url; ?>" title="Blog Bsky"><img src="<?php echo $logo_url; ?>" alt="Blog bskygrowth"></a></div>';

            // Ajouter le logo au début de l'en-tête et masquer le titre
            var header = document.querySelector('.inside-header');
            if (header) {
                header.insertAdjacentHTML('afterbegin', logoHtml);

                // Masquer le titre du site et la description
                var siteTitle = header.querySelector('.site-branding');
                if (siteTitle) {
                    siteTitle.style.display = 'none';
                }
            }
        });
    </script>
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
    add_filter( 'generate_sidebar_layout', function( $layout ) {
        if ( is_page() || is_single() ) {
            return 'no-sidebar';
        }
        return $layout;
    } );

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

// Désactiver le footer original de GeneratePress et ajouter le nôtre
add_action('init', 'setup_custom_footer');

add_action( 'wp_footer', function () {
    if ( is_front_page() || is_page() ) { // ou ajuste selon besoin
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const items = document.querySelectorAll('.wp-block-latest-posts__list.is-grid li');
            items.forEach(function (li) {
                const link = li.querySelector('.wp-block-latest-posts__post-title');
                if (!link) return;

                const href = link.getAttribute('href');
                const wrapper = document.createElement('a');
                wrapper.href = href;
                wrapper.className = 'full-post-link';
                wrapper.style.display = 'block';
                wrapper.style.textDecoration = 'none';
                wrapper.style.color = 'inherit';

                // Copie tout le contenu de li dans le <a>
                while (li.firstChild) {
                    wrapper.appendChild(li.firstChild);
                }
                li.appendChild(wrapper);
            });
        });
        </script>
        <?php
    }
} );

function setup_custom_footer()
{
    // Supprimer les hooks du footer original
    remove_action('generate_footer', 'generate_construct_footer');
    remove_action('generate_before_footer_content', 'generate_footer_widgets', 5);
    remove_action('generate_before_copyright', 'generate_footer_bar', 15);
    remove_action('generate_credits', 'generate_add_footer_info');
    remove_action('generate_footer', 'generate_construct_footer_widgets', 5);

    // Ajouter notre propre footer
    add_action('generate_footer', 'action_custom_footer');
}
add_filter( 'generate_sidebar_layout', function( $layout ) {
    if ( is_page() || is_single() ) {
        return 'no-sidebar';
    }
    return $layout;
} );

// Fonction pour afficher notre footer personnalisé
function action_custom_footer()
{
    // Essayer d'inclure le footer depuis le thème
    $footer_template = get_stylesheet_directory() . '/footer-template.php';
    if (file_exists($footer_template)) {
        include $footer_template;
    } else {
        // Sinon, charger le footer depuis les uploads
        $footer_html = WP_CONTENT_DIR . '/uploads/custom/footer.html';
        if (file_exists($footer_html)) {
            include $footer_html;
        } else {
            // Footer de secours si rien d'autre n'est trouvé
            echo '<footer class="site-footer"><div class="inside-footer-widgets">';
            echo '<div class="footer-widgets-container"><div class="inside-footer-widgets">';
            echo '<div class="footer-widget-1">';
            echo '<p>&copy; ' . date('Y') . ' Family UGC - Tous droits réservés</p>';
            echo '</div></div></div></footer>';
        }
    }
}

// Ajoute une fonction pour créer un fichier footer-template.php si nécessaire
function create_footer_template()
{
    $footer_template = get_stylesheet_directory() . '/footer-template.php';
    if (!file_exists($footer_template)) {
        $footer_html = WP_CONTENT_DIR . '/uploads/custom/footer.html';
        if (file_exists($footer_html)) {
            $footer_content = file_get_contents($footer_html);
            $php_content = '<?php
/**
 * Footer template pour GeneratePress Child Theme
 * Family UGC
 */
?>' . PHP_EOL . $footer_content;
            file_put_contents($footer_template, $php_content);
        }
    }
}
add_action('after_switch_theme', 'create_footer_template');
add_action('generate_after_header', function () {
    if (function_exists('pll_the_languages')) {
        echo '<div class="lang-switcher">';
        pll_the_languages(['show_flags' => 1, 'show_names' => 0]);
        echo '</div>';
    }
});