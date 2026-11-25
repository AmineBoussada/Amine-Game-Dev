<?php
/**
 * Elementor (free version) compatibility.
 *
 * - Home, "À propos", "Contact" and ordinary pages: when the owner builds the page with Elementor,
 *   the Elementor version replaces the theme's version (header, footer and language buttons are kept).
 * - Five widgets in the "NOVA IMMOBILIER" category: featured properties, property search,
 *   contact form, key figures and contact banner. They show the real listings and follow the
 *   French/Arabic and light/dark choices.
 */

defined('ABSPATH') || exit;

/** True when this page was built with Elementor (and Elementor is active). */
function nova_is_elementor_page(int $post_id = 0): bool
{
    $post_id = $post_id ?: (int) get_queried_object_id();
    return defined('ELEMENTOR_VERSION') && $post_id && get_post_meta($post_id, '_elementor_edit_mode', true) === 'builder';
}

/** Renders the current page with its Elementor content inside the theme header and footer. Returns false if not an Elementor page. */
function nova_render_elementor_page(): bool
{
    if (!is_page() || !nova_is_elementor_page()) {
        return false;
    }
    get_template_part('template-parts/elementor-page');
    return true;
}

add_filter('body_class', function ($classes) {
    if (is_singular() && nova_is_elementor_page()) {
        $classes[] = 'nova-elementor';
        $classes = array_values(array_diff($classes, ['has-hero']));
    }
    return $classes;
}, 20);

/**
 * Let Elementor headings, texts and buttons use the theme's colours and fonts by default
 * (Elementor's own default palette is light blue). The owner can still pick colours in Elementor.
 */
function nova_elementor_defaults(): void
{
    update_option('elementor_disable_color_schemes', 'yes');
    update_option('elementor_disable_typography_schemes', 'yes');
}
add_action('after_switch_theme', 'nova_elementor_defaults');
add_action('activated_plugin', function ($plugin) {
    if ($plugin === 'elementor/elementor.php') {
        nova_elementor_defaults();
    }
});

add_action('elementor/elements/categories_registered', function ($manager) {
    $manager->add_category('nova', ['title' => 'NOVA IMMOBILIER', 'icon' => 'eicon-home']);
});

add_action('elementor/widgets/register', function ($widgets) {
    require_once __DIR__ . '/elementor-widgets.php';
    foreach (['NovaTheme_Featured_Widget', 'NovaTheme_Search_Widget', 'NovaTheme_Contact_Widget', 'NovaTheme_Stats_Widget', 'NovaTheme_Cta_Widget'] as $class) {
        $widgets->register(new $class());
    }
});

/** In the Elementor editor, widgets are re-rendered without a page load: draw their illustrations again. */
add_action('elementor/frontend/after_enqueue_scripts', function () {
    wp_add_inline_script('elementor-frontend', "jQuery(window).on('elementor/frontend/init',function(){elementorFrontend.hooks.addAction('frontend/element_ready/global',function(){if(window.NOVA_REFRESH){window.NOVA_REFRESH();}});});");
});
