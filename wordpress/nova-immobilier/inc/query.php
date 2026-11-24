<?php
/**
 * Listing filters and sorting for /biens/ (same parameters as the React demo,
 * except `bien` for the property type, since `type` is reserved by WordPress).
 */

defined('ABSPATH') || exit;

function nova_sort_options(): array
{
    return ['recent' => 'Plus récents', 'price-asc' => 'Prix croissant', 'price-desc' => 'Prix décroissant', 'surface-desc' => 'Surface décroissante'];
}

/** Current filters from the query string, validated. */
function nova_current_filters(): array
{
    $g = fn($k) => isset($_GET[$k]) ? sanitize_text_field(wp_unslash($_GET[$k])) : '';
    $num = fn($k) => ($v = absint($g($k))) > 0 ? $v : 0;
    $tx = $g('transaction');
    $type = $g('bien');
    $sort = $g('tri');
    return [
        'transaction' => isset(nova_transaction_labels()[$tx]) ? $tx : '',
        'bien' => isset(nova_type_labels()[$type]) ? $type : '',
        'wilaya' => in_array($g('wilaya'), nova_wilayas(), true) ? $g('wilaya') : '',
        'prix_min' => $num('prix_min'),
        'prix_max' => $num('prix_max'),
        'surface' => $num('surface'),
        'pieces' => $num('pieces'),
        'tri' => isset(nova_sort_options()[$sort]) ? $sort : 'recent',
    ];
}

function nova_filters_meta_query(array $f): array
{
    $mq = ['relation' => 'AND'];
    if ($f['transaction']) $mq[] = ['key' => '_nova_transaction', 'value' => $f['transaction']];
    if ($f['bien']) $mq[] = ['key' => '_nova_type', 'value' => $f['bien']];
    if ($f['wilaya']) $mq[] = ['key' => '_nova_wilaya', 'value' => $f['wilaya']];
    if ($f['prix_min']) $mq[] = ['key' => '_nova_price', 'value' => $f['prix_min'], 'compare' => '>=', 'type' => 'NUMERIC'];
    if ($f['prix_max']) $mq[] = ['key' => '_nova_price', 'value' => $f['prix_max'], 'compare' => '<=', 'type' => 'NUMERIC'];
    if ($f['surface']) $mq[] = ['key' => '_nova_surface', 'value' => $f['surface'], 'compare' => '>=', 'type' => 'NUMERIC'];
    if ($f['pieces']) $mq[] = ['key' => '_nova_rooms', 'value' => $f['pieces'], 'compare' => '>=', 'type' => 'NUMERIC'];
    if (nova_setting('hide_unavailable')) {
        $mq[] = ['key' => '_nova_status', 'value' => ['vendu', 'loue'], 'compare' => 'NOT IN'];
    }
    return $mq;
}

add_action('pre_get_posts', function (WP_Query $q) {
    if (is_admin() || !$q->is_main_query() || !$q->is_post_type_archive('property')) {
        return;
    }
    $f = nova_current_filters();
    $q->set('posts_per_page', 12);
    $q->set('meta_query', nova_filters_meta_query($f));
    switch ($f['tri']) {
        case 'price-asc':
        case 'price-desc':
            $q->set('meta_key', '_nova_price');
            $q->set('orderby', 'meta_value_num');
            $q->set('order', $f['tri'] === 'price-asc' ? 'ASC' : 'DESC');
            break;
        case 'surface-desc':
            $q->set('meta_key', '_nova_surface');
            $q->set('orderby', 'meta_value_num');
            $q->set('order', 'DESC');
            break;
        default:
            $q->set('orderby', 'date');
            $q->set('order', 'DESC');
    }
});

/** URL of the listing with some filters changed (null removes a filter). */
function nova_filters_url(array $changes = []): string
{
    $f = array_merge(nova_current_filters(), $changes);
    if (($f['tri'] ?? '') === 'recent') {
        $f['tri'] = '';
    }
    return add_query_arg(array_filter($f), get_post_type_archive_link('property'));
}

function nova_featured_properties(int $limit = 6): array
{
    $q = new WP_Query([
        'post_type' => 'property',
        'posts_per_page' => $limit,
        'meta_query' => [['key' => '_nova_featured', 'value' => '1']],
        'no_found_rows' => true,
    ]);
    $posts = $q->posts;
    if (count($posts) < $limit) { // fill with latest properties if few are featured
        $more = get_posts(['post_type' => 'property', 'numberposts' => $limit - count($posts), 'post__not_in' => wp_list_pluck($posts, 'ID')]);
        $posts = array_merge($posts, $more);
    }
    return array_map('nova_get_property', $posts);
}

function nova_similar_properties(array $p, int $limit = 3): array
{
    $candidates = get_posts(['post_type' => 'property', 'numberposts' => 30, 'post__not_in' => [$p['id']]]);
    $scored = [];
    foreach ($candidates as $c) {
        $x = nova_get_property($c);
        $score = ($x['type'] === $p['type'] ? 2 : 0) + ($x['transaction'] === $p['transaction'] ? 1 : 0) + ($x['wilaya'] === $p['wilaya'] ? 1 : 0);
        $scored[] = [$score, $x];
    }
    usort($scored, fn($a, $b) => $b[0] <=> $a[0]);
    return array_slice(array_column($scored, 1), 0, $limit);
}
