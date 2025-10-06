<?php
/**
 * The template for displaying the footer.
 *
 * @package GeneratePress Child
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
?>
</div><!-- #content -->
</div><!-- #page -->

<?php
/**
 * generate_before_footer hook.
 *
 * @since 0.1
 */
do_action('generate_before_footer');

/**
 * generate_footer hook.
 *
 * @since 0.1
 *
 * @hooked generate_construct_footer_widgets - 5
 * @hooked generate_construct_footer - 10
 */
do_action('generate_footer');

/**
 * generate_after_footer hook.
 *
 * @since 0.1
 */
do_action('generate_after_footer');
?>

<?php wp_footer(); ?>
<?php
// Utilitaire: fetch + cache d'un snippet CDN
function lemeon_fetch_snippet_cdn($name, $ttl = 600) { // TTL 10 min
    $key  = 'lemeon_snip_' . sanitize_key($name);
    $html = get_transient($key);

    if ($html === false) {
        $url = 'https://static.le-meon.com/snippets/' . rawurlencode($name) . '.html';
        $res = wp_remote_get($url, ['timeout' => 1.0, 'redirection' => 0]);

        if (!is_wp_error($res) && wp_remote_retrieve_response_code($res) === 200) {
            $html = wp_remote_retrieve_body($res);
        } else {
            $html = ''; // fallback silencieux
        }
        set_transient($key, $html, $ttl);
    }
    return $html;
}

// Hook GeneratePress: place le bloc juste après le footer
add_action('generate_after_footer', function () {
    $snippet = lemeon_fetch_snippet_cdn('footer-links', 600);
    if ($snippet) {
        echo '<div class="footer-links-shared" role="complementary">' . $snippet . '</div>';
    } else {
        // Fallback local minimal si besoin :
        // echo '<div class="footer-links-shared"><a href="https://le-meon.com">Le Meon</a></div>';
    }
}, 20);

// Petite route de purge pour admins: ?flush_shared_footer=1
add_action('init', function () {
    if (is_user_logged_in() && current_user_can('manage_options') && isset($_GET['flush_shared_footer'])) {
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_lemeon_snip_%' OR option_name LIKE '_transient_timeout_lemeon_snip_%'");
    }
});

</body>

</html>