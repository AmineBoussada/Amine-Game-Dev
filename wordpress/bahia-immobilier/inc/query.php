<?php
/**
 * Listing filters and sorting for /biens/ (same parameters as the React demo,
 * except `bien` for the property type, since `type` is reserved by WordPress).
 */

defined('ABSPATH') || exit;

function bahia_sort_options(): array
{
    return ['recent' => 'Plus récents', 'price-asc' => 'Prix croissant', 'price-desc' => 'Prix décroissant', 'surface-desc' => 'Surface décroissante'];
}

/** Current filters from the query string, validated. */
function bahia_current_filters(): array
{
    $g = fn($k) => isset($_GET[$k]) ? sanitize_text_field(wp_unslash($_GET[$k])) : '';
    $num = fn($k) => ($v = absint($g($k))) > 0 ? $v : 0;
    $tx = $g('transaction');
    $type = $g('bien');
    $sort = $g('tri');
    return [
        'transaction' => isset(bahia_transaction_labels()[$tx]) ? $tx : '',
        'bien' => isset(bahia_type_labels()[$type]) ? $type : '',
        'wilaya' => in_array($g('wilaya'), bahia_wilayas(), true) ? $g('wilaya') : '',
        'commune' => mb_substr($g('commune'), 0, 80),
        'prix_min' => $num('prix_min'),
        'prix_max' => $num('prix_max'),
        'surface' => $num('surface'),
        'pieces' => $num('pieces'),
        'tri' => isset(bahia_sort_options()[$sort]) ? $sort : 'recent',
    ];
}

function bahia_filters_meta_query(array $f): array
{
    $mq = ['relation' => 'AND'];
    if ($f['transaction']) $mq[] = ['key' => '_bahia_transaction', 'value' => $f['transaction']];
    if ($f['bien']) $mq[] = ['key' => '_bahia_type', 'value' => $f['bien']];
    if ($f['wilaya']) $mq[] = ['key' => '_bahia_wilaya', 'value' => $f['wilaya']];
    if ($f['commune']) $mq[] = ['key' => '_bahia_commune', 'value' => $f['commune']];
    if ($f['prix_min']) $mq[] = ['key' => '_bahia_price', 'value' => $f['prix_min'], 'compare' => '>=', 'type' => 'NUMERIC'];
    if ($f['prix_max']) $mq[] = ['key' => '_bahia_price', 'value' => $f['prix_max'], 'compare' => '<=', 'type' => 'NUMERIC'];
    if ($f['surface']) $mq[] = ['key' => '_bahia_surface', 'value' => $f['surface'], 'compare' => '>=', 'type' => 'NUMERIC'];
    if ($f['pieces']) $mq[] = ['key' => '_bahia_rooms', 'value' => $f['pieces'], 'compare' => '>=', 'type' => 'NUMERIC'];
    if (bahia_setting('hide_unavailable')) {
        $mq[] = ['key' => '_bahia_status', 'value' => ['vendu', 'loue'], 'compare' => 'NOT IN'];
    }
    return $mq;
}

add_action('pre_get_posts', function (WP_Query $q) {
    if (is_admin() || !$q->is_main_query() || !$q->is_post_type_archive('property')) {
        return;
    }
    $f = bahia_current_filters();
    $q->set('posts_per_page', 12);
    $q->set('meta_query', bahia_filters_meta_query($f));
    switch ($f['tri']) {
        case 'price-asc':
        case 'price-desc':
            $q->set('meta_key', '_bahia_price');
            $q->set('orderby', 'meta_value_num');
            $q->set('order', $f['tri'] === 'price-asc' ? 'ASC' : 'DESC');
            break;
        case 'surface-desc':
            $q->set('meta_key', '_bahia_surface');
            $q->set('orderby', 'meta_value_num');
            $q->set('order', 'DESC');
            break;
        default:
            $q->set('orderby', 'date');
            $q->set('order', 'DESC');
    }
});

/** URL of the listing with some filters changed (null removes a filter). */
function bahia_filters_url(array $changes = []): string
{
    $f = array_merge(bahia_current_filters(), $changes);
    if (($f['tri'] ?? '') === 'recent') {
        $f['tri'] = '';
    }
    return add_query_arg(array_filter($f), get_post_type_archive_link('property'));
}

function bahia_featured_properties(int $limit = 6): array
{
    $q = new WP_Query([
        'post_type' => 'property',
        'posts_per_page' => $limit,
        'meta_query' => [['key' => '_bahia_featured', 'value' => '1']],
        'no_found_rows' => true,
    ]);
    $posts = $q->posts;
    if (count($posts) < $limit) { // fill with latest properties if few are featured
        $more = get_posts(['post_type' => 'property', 'numberposts' => $limit - count($posts), 'post__not_in' => wp_list_pluck($posts, 'ID')]);
        $posts = array_merge($posts, $more);
    }
    return array_map('bahia_get_property', $posts);
}

function bahia_similar_properties(array $p, int $limit = 3): array
{
    $candidates = get_posts(['post_type' => 'property', 'numberposts' => 30, 'post__not_in' => [$p['id']]]);
    $scored = [];
    foreach ($candidates as $c) {
        $x = bahia_get_property($c);
        $score = ($x['type'] === $p['type'] ? 2 : 0) + ($x['transaction'] === $p['transaction'] ? 1 : 0) + ($x['wilaya'] === $p['wilaya'] ? 1 : 0);
        $scored[] = [$score, $x];
    }
    usort($scored, fn($a, $b) => $b[0] <=> $a[0]);
    return array_slice(array_column($scored, 1), 0, $limit);
}
