<?php
/**
 * NOVA IMMOBILIER theme bootstrap.
 */

defined('ABSPATH') || exit;

define('NOVA_VERSION', '1.0.0');
define('NOVA_DIR', get_template_directory());
define('NOVA_URI', get_template_directory_uri());

foreach (['fields', 'helpers', 'post-types', 'meta-boxes', 'admin-columns', 'settings', 'query', 'leads', 'seo', 'seed'] as $file) {
    require_once NOVA_DIR . "/inc/{$file}.php";
}
// Alternative layouts (see the real-estate-wp-theme skill) add their front-end here.
if (is_readable(NOVA_DIR . '/inc/layout.php')) {
    require_once NOVA_DIR . '/inc/layout.php';
}

add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'gallery', 'caption', 'style', 'script']);
    register_nav_menus(['primary' => 'Menu principal']);

    // Image sizes used by cards, gallery and thumbnails (4:3 / 3:2 crops).
    add_image_size('nova-card', 900, 675, true);
    add_image_size('nova-large', 1600, 1067, true);
    add_image_size('nova-thumb', 300, 225, true);
});

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'nova-fonts',
        apply_filters('nova_fonts_url', 'https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,500;0,600;1,500&display=swap'),
        [],
        null
    );
    $css = apply_filters('nova_styles', ['tokens', 'base', 'components', 'pages', 'theme']);
    $deps = ['nova-fonts'];
    foreach ($css as $name) {
        $path = "/assets/css/{$name}.css";
        wp_enqueue_style("nova-{$name}", NOVA_URI . $path, $deps, (string) @filemtime(NOVA_DIR . $path));
        $deps = ["nova-{$name}"];
    }
    $js = '/assets/js/theme.js';
    wp_enqueue_script('nova-theme', NOVA_URI . $js, [], (string) @filemtime(NOVA_DIR . $js), ['strategy' => 'defer', 'in_footer' => true]);
});

// Mark JS availability early so reveal animations never hide content without JS.
add_action('wp_head', function () {
    echo "<script>document.documentElement.classList.add('js')</script>\n";
}, 1);

add_filter('body_class', function ($classes) {
    if (is_front_page()) {
        $classes[] = 'has-hero';
    }
    return $classes;
});

// Keep the admin bar from covering the fixed header.
add_action('wp_head', function () {
    if (is_admin_bar_showing()) {
        echo '<style>.site-header{top:var(--wp-admin--admin-bar--height,32px)}</style>';
    }
});
