<?php
/**
 * Bahia Immobilier theme bootstrap.
 */

defined('ABSPATH') || exit;

define('BAHIA_VERSION', '1.0.0');
define('BAHIA_DIR', get_template_directory());
define('BAHIA_URI', get_template_directory_uri());

foreach (['fields', 'helpers', 'post-types', 'meta-boxes', 'admin-columns', 'settings', 'query', 'leads', 'seo', 'seed', 'i18n'] as $file) {
    require_once BAHIA_DIR . "/inc/{$file}.php";
}
// Alternative layouts (see the real-estate-wp-theme skill) add their front-end here.
if (is_readable(BAHIA_DIR . '/inc/layout.php')) {
    require_once BAHIA_DIR . '/inc/layout.php';
}

add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'gallery', 'caption', 'style', 'script']);
    register_nav_menus(['primary' => 'Menu principal']);

    // Image sizes used by cards, gallery and thumbnails (4:3 / 3:2 crops).
    add_image_size('bahia-card', 900, 675, true);
    add_image_size('bahia-large', 1600, 1067, true);
    add_image_size('bahia-thumb', 300, 225, true);
});

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'bahia-fonts',
        apply_filters('bahia_fonts_url', 'https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,500;0,600;1,500&display=swap'),
        [],
        null
    );
    $css = apply_filters('bahia_styles', ['tokens', 'base', 'components', 'pages', 'theme', 'modes']);
    $deps = ['bahia-fonts'];
    foreach ($css as $name) {
        $path = "/assets/css/{$name}.css";
        wp_enqueue_style("bahia-{$name}", BAHIA_URI . $path, $deps, (string) @filemtime(BAHIA_DIR . $path));
        $deps = ["bahia-{$name}"];
    }
    $js = '/assets/js/theme.js';
    wp_enqueue_script('bahia-theme', BAHIA_URI . $js, [], (string) @filemtime(BAHIA_DIR . $js), ['strategy' => 'defer', 'in_footer' => true]);
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
