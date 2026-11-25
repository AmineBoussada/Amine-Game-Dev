<?php
/**
 * "Riviera" layout: front-end in French or Arabic (right-to-left), light or dark display.
 * Loaded by functions.php. The back office stays the one of the base theme, plus optional
 * Arabic fields on each property and Arabic texts in the agency settings.
 */

defined('ABSPATH') || exit;

/* ---------------- Assets ---------------- */

add_filter('nova_fonts_url', fn() => 'https://fonts.googleapis.com/css2?family=Jost:wght@400;500;600;700&family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap');
add_filter('nova_styles', fn() => ['riviera']);

add_action('wp_enqueue_scripts', function () {
    $js = '/assets/js/riviera.js';
    wp_enqueue_script('nova-riviera', NOVA_URI . $js, ['nova-theme'], (string) @filemtime(NOVA_DIR . $js), ['strategy' => 'defer', 'in_footer' => true]);
});


add_filter('body_class', function ($classes) {
    $classes[] = 'layout-riviera';
    return $classes;
});

/* ---------------- Illustrations shipped with the layout ---------------- */

add_filter('nova_illustration_src', function ($src, $scene) {
    $map = [
        'villa-pool' => 'piscine', 'villa' => 'villa', 'living' => 'sejour', 'kitchen' => 'sejour',
        'bedroom' => 'chambre', 'bathroom' => 'chambre', 'building' => 'promenade', 'land' => 'terrain',
        'shop' => 'boutique', 'office' => 'boutique', 'promenade' => 'promenade',
    ];
    return NOVA_URI . '/assets/img/riviera-' . ($map[$scene] ?? 'sejour') . '.svg';
}, 10, 2);

function nova_rv_nav(): array
{
    $items = array_values(array_filter(nova_nav_items(), fn($i) => rtrim((string) wp_parse_url($i['url'], PHP_URL_PATH), '/') !== rtrim((string) wp_parse_url(home_url('/'), PHP_URL_PATH), '/')));
    foreach ($items as &$item) {
        $item['label'] = nova_t($item['label']);
    }
    return $items;
}

/** Communes with published properties, most listings first: [name, count, cover image]. */
function nova_top_communes(int $limit = 5): array
{
    global $wpdb;
    $rows = $wpdb->get_results(
        "SELECT pm.meta_value AS commune, COUNT(*) AS n, MIN(p.ID) AS first_id FROM {$wpdb->postmeta} pm
         INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
         WHERE pm.meta_key = '_nova_commune' AND p.post_type = 'property' AND p.post_status = 'publish' AND pm.meta_value <> ''
         GROUP BY pm.meta_value ORDER BY n DESC, commune ASC LIMIT " . (int) $limit
    );
    return array_map(fn($r) => ['name' => $r->commune, 'count' => (int) $r->n, 'image' => nova_get_property((int) $r->first_id)['images'][0]], $rows ?: []);
}

/** All communes with published properties (filters). */
function nova_used_communes(): array
{
    global $wpdb;
    $rows = $wpdb->get_col(
        "SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm
         INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
         WHERE pm.meta_key = '_nova_commune' AND p.post_type = 'property' AND p.post_status = 'publish' AND pm.meta_value <> ''"
    );
    sort($rows);
    return $rows;
}

/** Marinière stripes (decorative). */
function nova_stripes(string $class = ''): string
{
    return '<span class="rv-stripes ' . esc_attr($class) . '" aria-hidden="true"></span>';
}

function nova_rv_logo(bool $light = false): string
{
    return '<a href="' . esc_url(home_url('/')) . '" class="rv-logo' . ($light ? ' rv-logo--light' : '') . '" aria-label="' . esc_attr(nova_t('NOVA IMMOBILIER') . ' — ' . nova_t('Accueil')) . '">'
        . '<span class="rv-logo__name">__SHORT__</span>'
        . '<span class="rv-logo__sub">' . esc_html(nova_t('__LOGO_BOTTOM__') . ' · ' . nova_t('__CITY__')) . '</span></a>';
}
