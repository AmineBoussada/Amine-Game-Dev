<?php
/**
 * Front-end helpers: property data, prices, links, icons and images.
 */

defined('ABSPATH') || exit;

/* ---------------- Formatting ---------------- */

function nova_format_price($value): string
{
    return number_format((float) $value, 0, ',', ' ') . ' DA';
}

function nova_format_price_short($value): string
{
    $value = (float) $value;
    return $value >= 1000000 ? number_format($value / 1000000, 0, ',', ' ') . ' M DA' : nova_format_price($value);
}

/** Local habit: prices spoken in centimes (1 DA = 100 centimes). */
function nova_format_centimes($value): string
{
    $centimes = (float) $value * 100;
    if ($centimes >= 1e9) {
        $v = $centimes / 1e9;
        $txt = rtrim(rtrim(number_format($v, 2, ',', ' '), '0'), ',');
        return '≈ ' . $txt . ' milliard' . ($v >= 2 ? 's' : '') . ' de centimes';
    }
    return '≈ ' . number_format($centimes / 1e6, 0, ',', ' ') . ' millions de centimes';
}

function nova_price_suffix(array $p): string
{
    return $p['transaction'] === 'location' ? ' / mois' : '';
}

function nova_whatsapp_link(string $message): string
{
    return 'https://wa.me/' . preg_replace('/\D+/', '', (string) nova_setting('whatsapp')) . '?text=' . rawurlencode($message);
}

function nova_property_whatsapp_message(array $p): string
{
    return sprintf(
        'Bonjour NOVA IMMOBILIER, je suis intéressé(e) par le bien « %s » (réf. %s) à %s. Pouvez-vous me recontacter ?',
        $p['title'],
        $p['reference'],
        $p['commune']
    );
}

function nova_maps_link(string $query): string
{
    return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($query);
}

function nova_tel_href(string $phone): string
{
    return 'tel:' . preg_replace('/[^\d+]/', '', $phone);
}

function nova_plural(int $n, string $word, string $plural = ''): string
{
    return $n . ' ' . ($n > 1 ? ($plural ?: $word . 's') : $word);
}

/* ---------------- Property data ---------------- */

/** Illustration scenes used when a property has no photos (see theme-src/illustrations). */
function nova_default_scenes(string $type): array
{
    return match ($type) {
        'villa' => ['villa-pool', 'living', 'kitchen', 'bedroom', 'villa'],
        'terrain' => ['land', 'land'],
        'local' => ['shop', 'office'],
        default => ['living', 'kitchen', 'bedroom', 'bathroom', 'building'],
    };
}

/**
 * Returns one property as an array with the same shape as the React `Property` type.
 */
function nova_get_property($post = null): array
{
    $post = get_post($post);
    $id = $post->ID;
    $m = fn($k) => nova_meta($id, $k);

    $ids = array_filter(array_map('absint', explode(',', (string) $m('gallery'))));
    $thumb = get_post_thumbnail_id($id);
    if ($thumb && !in_array($thumb, $ids, true)) {
        array_unshift($ids, $thumb);
    }

    $images = [];
    foreach ($ids as $att) {
        if (!wp_attachment_is_image($att)) {
            continue;
        }
        $alt = get_post_meta($att, '_wp_attachment_image_alt', true) ?: get_the_title($att);
        $images[] = ['id' => $att, 'alt' => $alt ?: $post->post_title, 'scene' => null];
    }
    if (!$images) {
        $scenes = nova_default_scenes((string) $m('type'));
        $shift = $id % count($scenes); // vary the cover between properties
        $scenes = array_merge(array_slice($scenes, $shift), array_slice($scenes, 0, $shift));
        foreach ($scenes as $i => $scene) {
            $images[] = ['id' => 0, 'alt' => $post->post_title . ' — visuel d’illustration ' . ($i + 1), 'scene' => $scene, 'seed' => $id . '-' . $i];
        }
    }

    $features = $m('features');
    return apply_filters('nova_property', [
        'id' => $id,
        'url' => get_permalink($id),
        'reference' => $m('reference') ?: 'NOVA-' . $id,
        'title' => get_the_title($id),
        'price' => (float) $m('price'),
        'transaction' => $m('transaction') ?: 'vente',
        'type' => $m('type') ?: 'appartement',
        'typology' => (string) $m('typology'),
        'wilaya' => (string) $m('wilaya'),
        'commune' => (string) $m('commune'),
        'quartier' => (string) $m('quartier'),
        'surface' => (float) $m('surface'),
        'land_surface' => (float) $m('land_surface'),
        'rooms' => (int) $m('rooms'),
        'bedrooms' => (int) $m('bedrooms'),
        'bathrooms' => (int) $m('bathrooms'),
        'floor' => (string) $m('floor'),
        'year_built' => (int) $m('year_built'),
        'excerpt' => has_excerpt($id) ? get_the_excerpt($id) : wp_trim_words(wp_strip_all_tags($post->post_content), 26),
        'features' => is_array($features) ? $features : [],
        'images' => $images,
        'featured' => (bool) $m('featured'),
        'status' => $m('status') ?: 'disponible',
        'date' => get_the_date('Y-m-d', $id),
    ], $post);
}

function nova_type_label(array $p): string
{
    return $p['type'] === 'appartement'
        ? trim('Appartement ' . $p['typology'])
        : ($p['typology'] ?: nova_type_labels()[$p['type']] ?? '');
}

/* ---------------- Images ---------------- */

const NOVA_BLANK = 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==';

/**
 * Image inside a ratio frame, with lazy loading. Images without an attachment
 * render a generated illustration (filled by theme.js from data-scene).
 */
function nova_image(array $img, string $size = 'nova-card', string $frame = 'ratio-4-3', bool $priority = false, ?string $alt = null): string
{
    $alt = $alt ?? $img['alt'];
    if (!empty($img['id'])) {
        $html = wp_get_attachment_image($img['id'], $size, false, [
            'alt' => $alt,
            'loading' => $priority ? 'eager' : 'lazy',
            'decoding' => 'async',
            'fetchpriority' => $priority ? 'high' : 'auto',
            'sizes' => $size === 'nova-thumb' ? '120px' : '(max-width: 640px) 100vw, (max-width: 1200px) 60vw, 900px',
        ]);
        return '<div class="img-frame is-loaded ' . esc_attr($frame) . '">' . $html . '</div>';
    }
    // A layout can ship its own illustration files instead of the generated ones.
    $src = (string) apply_filters('nova_illustration_src', '', $img['scene'] ?? 'living', $img['seed'] ?? '');
    if ($src !== '') {
        return sprintf(
            '<div class="img-frame is-loaded is-illustration %s"><img src="%s" alt="%s" loading="%s" decoding="async"></div>',
            esc_attr($frame), esc_url($src), esc_attr($alt), $priority ? 'eager' : 'lazy'
        );
    }
    return sprintf(
        '<div class="img-frame is-loaded is-illustration %s"><img src="%s" data-scene="%s" data-seed="%s" alt="%s" decoding="async"></div>',
        esc_attr($frame),
        NOVA_BLANK,
        esc_attr($img['scene'] ?? 'living'),
        esc_attr($img['seed'] ?? ''),
        esc_attr($alt)
    );
}

/** Image chosen in the settings page, or an illustration. */
function nova_setting_image(string $key, string $scene, string $alt, string $frame = '', bool $priority = false): string
{
    $id = (int) nova_setting($key);
    return nova_image(['id' => $id, 'alt' => $alt, 'scene' => $scene, 'seed' => $key], 'nova-large', $frame, $priority);
}

/* ---------------- Navigation ---------------- */

function nova_default_nav(): array
{
    $biens = get_post_type_archive_link('property') ?: home_url('/biens/');
    return [
        ['label' => 'Accueil', 'url' => home_url('/')],
        ['label' => 'Acheter', 'url' => add_query_arg('transaction', 'vente', $biens)],
        ['label' => 'Louer', 'url' => add_query_arg('transaction', 'location', $biens)],
        ['label' => 'Nos biens', 'url' => $biens],
        ['label' => 'À propos', 'url' => home_url('/a-propos/')],
        ['label' => 'Contact', 'url' => home_url('/contact/')],
    ];
}

/** Menu items from Apparence › Menus (location "primary"), or the default list. */
function nova_nav_items(): array
{
    $locations = get_nav_menu_locations();
    $items = [];
    if (!empty($locations['primary'])) {
        foreach ((array) wp_get_nav_menu_items($locations['primary']) as $item) {
            if (!$item->menu_item_parent) {
                $items[] = ['label' => $item->title, 'url' => $item->url];
            }
        }
    }
    $items = $items ?: nova_default_nav();

    // Active state that understands ?transaction= (Acheter / Louer / Nos biens).
    $req = wp_parse_url(home_url(add_query_arg([])));
    $current_path = untrailingslashit($req['path'] ?? '/') ?: '/';
    $current_tx = $_GET['transaction'] ?? '';
    foreach ($items as &$item) {
        $u = wp_parse_url($item['url']);
        parse_str($u['query'] ?? '', $q);
        $path = untrailingslashit($u['path'] ?? '/') ?: '/';
        $is_biens = is_post_type_archive('property');
        $item['active'] = $path === $current_path && (!$is_biens || ($q['transaction'] ?? '') === $current_tx);
    }
    return $items;
}

function nova_contact_url(string $subject = '', string $hash = ''): string
{
    $url = home_url('/contact/');
    if ($subject) {
        $url = add_query_arg('sujet', $subject, $url);
    }
    return $url . ($hash ? "#{$hash}" : '');
}

function nova_hours(): array
{
    $rows = [];
    foreach (preg_split('/\r?\n/', (string) nova_setting('hours')) as $line) {
        $parts = array_map('trim', explode('|', $line, 2));
        if ($parts[0] !== '') {
            $rows[] = ['days' => $parts[0], 'time' => $parts[1] ?? ''];
        }
    }
    return $rows;
}

function nova_socials(): array
{
    $out = [];
    foreach (['facebook' => 'Facebook', 'instagram' => 'Instagram', 'linkedin' => 'LinkedIn', 'youtube' => 'YouTube'] as $key => $name) {
        if ($url = nova_setting($key)) {
            $out[] = ['icon' => $key, 'name' => $name, 'url' => $url];
        }
    }
    return $out;
}

/* ---------------- Icons (same set as the React demo) ---------------- */

function nova_icon(string $name, int $size = 20, string $class = ''): string
{
    static $paths = null;
    if ($paths === null) {
        $paths = [
            'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
            'heart' => '<path d="M12 20.5s-7.5-4.6-9.3-9.4C1.5 7.7 3.7 4.5 7 4.5c2 0 3.4 1.1 5 3 1.6-1.9 3-3 5-3 3.3 0 5.5 3.2 4.3 6.6-1.8 4.8-9.3 9.4-9.3 9.4Z"/>',
            'pin' => '<path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.5"/>',
            'area' => '<path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/>',
            'bed' => '<path d="M3 18v-8a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v8M3 14h18M3 18v2M21 18v2"/><path d="M7 8V6h4v2"/>',
            'bath' => '<path d="M4 12h16v3a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5v-3ZM6 12V6a2 2 0 0 1 3.5-1.3"/><path d="M7 20l-1 1.5M17 20l1 1.5"/>',
            'rooms' => '<rect x="3.5" y="3.5" width="17" height="17" rx="1.5"/><path d="M3.5 12h9M12.5 3.5v6M12.5 14.5v6"/>',
            'floor' => '<path d="M4 20h4v-4h4v-4h4V8h4"/>',
            'calendar' => '<rect x="3.5" y="5" width="17" height="15" rx="1.5"/><path d="M3.5 10h17M8 3v4M16 3v4"/>',
            'phone' => '<path d="M5 4h3l1.5 4.5-2 1.3a12 12 0 0 0 6.7 6.7l1.3-2L20 16v3a1.5 1.5 0 0 1-1.6 1.5A16.5 16.5 0 0 1 3.5 5.6 1.5 1.5 0 0 1 5 4Z"/>',
            'mail' => '<rect x="3" y="5" width="18" height="14" rx="1.5"/><path d="m3.5 6 8.5 7 8.5-7"/>',
            'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
            'chevronLeft' => '<path d="m15 6-6 6 6 6"/>',
            'chevronRight' => '<path d="m9 6 6 6-6 6"/>',
            'close' => '<path d="M6 6l12 12M18 6 6 18"/>',
            'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
            'filter' => '<path d="M4 6h16M7 12h10M10 18h4"/>',
            'check' => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
            'expand' => '<path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/>',
            'share' => '<circle cx="6" cy="12" r="2.5"/><circle cx="18" cy="6" r="2.5"/><circle cx="18" cy="18" r="2.5"/><path d="m8.2 10.8 7.6-3.6M8.2 13.2l7.6 3.6"/>',
            'home' => '<path d="M3.5 11 12 4l8.5 7"/><path d="M5.5 9.5V20h13V9.5"/><path d="M10 20v-5h4v5"/>',
            'selection' => '<path d="M4 20h16"/><path d="M6 20V9l6-4 6 4v11"/><path d="m9.5 13 2 2 3.5-3.5"/>',
            'support' => '<circle cx="12" cy="8" r="3.5"/><path d="M5 20c.8-3.6 3.6-5.5 7-5.5s6.2 1.9 7 5.5"/>',
            'map' => '<path d="m9 4-5.5 2v14L9 18l6 2 5.5-2V4L15 6 9 4Z"/><path d="M9 4v14M15 6v14"/>',
            'shield' => '<path d="M12 3.5 5 6v5.5c0 4.3 3 7.9 7 9 4-1.1 7-4.7 7-9V6l-7-2.5Z"/><path d="m9 12 2 2 4-4"/>',
            'layers' => '<path d="m12 4 8.5 4.5L12 13 3.5 8.5 12 4Z"/><path d="m3.5 12.5 8.5 4.5 8.5-4.5M3.5 16.5 12 21l8.5-4.5"/>',
            'image' => '<rect x="3.5" y="4.5" width="17" height="15" rx="1.5"/><circle cx="9" cy="10" r="1.8"/><path d="m4 18 5-5 4 4 2.5-2.5L20 19"/>',
            'facebook' => '<path d="M14 8.5h2.5V5H14a3.5 3.5 0 0 0-3.5 3.5V11H8v3.5h2.5V21H14v-6.5h2.5L17 11h-3V9a.5.5 0 0 1 .5-.5Z"/>',
            'instagram' => '<rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.2" cy="6.8" r="0.6" fill="currentColor"/>',
            'linkedin' => '<rect x="3.5" y="3.5" width="17" height="17" rx="2"/><path d="M8 10.5V16.5M8 7.6v.1M11.5 16.5v-6M11.5 13c0-1.6 1-2.6 2.5-2.6s2 1 2 2.6v3.5"/>',
            'youtube' => '<rect x="2.5" y="5.5" width="19" height="13" rx="3.5"/><path d="m10 9.2 5 2.8-5 2.8V9.2Z"/>',
            'whatsapp' => '<path fill="currentColor" stroke="none" d="M12 2.2a9.7 9.7 0 0 0-8.3 14.8L2.3 21.8l4.9-1.3A9.7 9.7 0 1 0 12 2.2Zm0 17.7a8 8 0 0 1-4.1-1.1l-.3-.2-2.9.8.8-2.8-.2-.3A8 8 0 1 1 12 19.9Zm4.4-6c-.2-.1-1.4-.7-1.7-.8-.2-.1-.4-.1-.5.1l-.8 1c-.1.2-.3.2-.5.1a6.6 6.6 0 0 1-3.3-2.9c-.2-.4.2-.4.7-1.3.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.5-.4h-.5a.9.9 0 0 0-.7.3 2.8 2.8 0 0 0-.9 2.1 4.9 4.9 0 0 0 1 2.6 11.2 11.2 0 0 0 4.3 3.8c1.6.7 2.2.7 3 .6a2.5 2.5 0 0 0 1.7-1.2 2 2 0 0 0 .1-1.2c0-.1-.2-.2-.5-.3Z"/>',
        ];
    }
    return sprintf(
        '<svg class="%s" width="%d" height="%d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%s</svg>',
        esc_attr($class),
        $size,
        $size,
        $paths[$name] ?? ''
    );
}

function nova_logo(bool $light = false): string
{
    return '<a href="' . esc_url(home_url('/')) . '" class="logo' . ($light ? ' logo--light' : '') . '" aria-label="NOVA IMMOBILIER — Accueil">'
        . '<svg class="logo__mark" viewBox="0 0 40 40" aria-hidden="true"><rect x="0.75" y="0.75" width="38.5" height="38.5" rx="2" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M12 29V11l16 18V11" fill="none" stroke="var(--logo-accent)" stroke-width="2.2" stroke-linecap="square"/></svg>'
        . '<span class="logo__text"><span class="logo__name">NOVA</span><span class="logo__sub">IMMOBILIER</span></span></a>';
}

/** Distinct wilayas that currently have published properties (for filters). */
function nova_used_wilayas(): array
{
    global $wpdb;
    $rows = $wpdb->get_col(
        "SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm
         INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
         WHERE pm.meta_key = '_nova_wilaya' AND p.post_type = 'property' AND p.post_status = 'publish' AND pm.meta_value <> ''"
    );
    sort($rows);
    return $rows;
}

function nova_budget_options(string $transaction = ''): array
{
    $vente = [5000000, 10000000, 15000000, 20000000, 30000000, 50000000, 80000000, 120000000, 200000000];
    $location = [30000, 50000, 80000, 100000, 150000, 250000, 400000];
    return match ($transaction) {
        'vente' => $vente,
        'location' => $location,
        default => array_merge($location, $vente),
    };
}

/** Count published properties of a type. */
function nova_count_type(string $type): int
{
    $q = new WP_Query([
        'post_type' => 'property',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_query' => [['key' => '_nova_type', 'value' => $type]],
        'no_found_rows' => false,
    ]);
    return (int) $q->found_posts;
}
