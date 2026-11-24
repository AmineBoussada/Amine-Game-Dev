<?php
/**
 * "Tous les biens" list: useful columns, sorting by price and quick filters.
 */

defined('ABSPATH') || exit;

add_filter('manage_property_posts_columns', function ($cols) {
    return [
        'cb' => $cols['cb'],
        'nova_photo' => 'Photo',
        'title' => 'Bien',
        'nova_ref' => 'Réf.',
        'nova_price' => 'Prix',
        'nova_type' => 'Type',
        'nova_place' => 'Localisation',
        'nova_status' => 'Statut',
        'nova_featured' => 'À la une',
        'date' => 'Date',
    ];
});

add_action('manage_property_posts_custom_column', function ($col, $post_id) {
    $p = nova_get_property($post_id);
    switch ($col) {
        case 'nova_photo':
            echo $p['images'][0]['id'] ? wp_get_attachment_image($p['images'][0]['id'], [64, 48]) : '<span class="nova-nophoto" title="Aucune photo">—</span>';
            break;
        case 'nova_ref':
            echo esc_html($p['reference']);
            break;
        case 'nova_price':
            echo '<strong>' . esc_html(nova_format_price($p['price'])) . '</strong>' . esc_html(nova_price_suffix($p));
            break;
        case 'nova_type':
            echo '<span class="nova-pill nova-pill--' . esc_attr($p['transaction']) . '">' . esc_html(nova_transaction_labels()[$p['transaction']]) . '</span> ' . esc_html(nova_type_label($p));
            break;
        case 'nova_place':
            echo esc_html(trim($p['commune'] . ', ' . $p['wilaya'], ', '));
            break;
        case 'nova_status':
            echo '<span class="nova-status nova-status--' . esc_attr($p['status']) . '">' . esc_html(nova_status_labels()[$p['status']] ?? '') . '</span>';
            break;
        case 'nova_featured':
            echo $p['featured'] ? '<span class="dashicons dashicons-star-filled" style="color:#A8875A" title="À la une"></span>' : '<span class="dashicons dashicons-star-empty" style="color:#ccc"></span>';
            break;
    }
}, 10, 2);

add_filter('manage_edit-property_sortable_columns', fn($cols) => $cols + ['nova_price' => 'nova_price']);

add_action('pre_get_posts', function (WP_Query $q) {
    if (!is_admin() || !$q->is_main_query() || $q->get('post_type') !== 'property') {
        return;
    }
    if ($q->get('orderby') === 'nova_price') {
        $q->set('meta_key', '_nova_price');
        $q->set('orderby', 'meta_value_num');
    }
    $meta = [];
    foreach (['nova_tx' => 'transaction', 'nova_type' => 'type', 'nova_status' => 'status'] as $param => $key) {
        if (!empty($_GET[$param])) {
            $meta[] = ['key' => "_nova_{$key}", 'value' => sanitize_text_field(wp_unslash($_GET[$param]))];
        }
    }
    if ($meta) {
        $q->set('meta_query', $meta);
    }
});

add_action('restrict_manage_posts', function ($post_type) {
    if ($post_type !== 'property') {
        return;
    }
    $filters = [
        'nova_tx' => ['Toutes transactions', nova_transaction_labels()],
        'nova_type' => ['Tous les types', nova_type_labels()],
        'nova_status' => ['Tous les statuts', nova_status_labels()],
    ];
    foreach ($filters as $param => [$all, $options]) {
        $current = sanitize_text_field(wp_unslash($_GET[$param] ?? ''));
        echo '<select name="' . esc_attr($param) . '"><option value="">' . esc_html($all) . '</option>';
        foreach ($options as $v => $label) {
            printf('<option value="%s" %s>%s</option>', esc_attr($v), selected($current, $v, false), esc_html($label));
        }
        echo '</select>';
    }
});

// Search also matches reference and commune.
add_filter('posts_search', function ($search, WP_Query $q) {
    global $wpdb;
    if (!is_admin() || !$q->is_main_query() || $q->get('post_type') !== 'property' || !$q->get('s')) {
        return $search;
    }
    $like = '%' . $wpdb->esc_like($q->get('s')) . '%';
    return $wpdb->prepare(
        " AND ({$wpdb->posts}.post_title LIKE %s OR {$wpdb->posts}.ID IN (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key IN ('_nova_reference','_nova_commune','_nova_wilaya') AND meta_value LIKE %s)) ",
        $like,
        $like
    );
}, 10, 2);

/* Dashboard widget: quick overview for the owner. */
add_action('wp_dashboard_setup', function () {
    wp_add_dashboard_widget('nova_overview', 'NOVA IMMOBILIER — aperçu', function () {
        $count = wp_count_posts('property');
        $leads = wp_count_posts('nova_lead');
        echo '<ul class="nova-overview">';
        echo '<li><strong>' . (int) $count->publish . '</strong> biens en ligne</li>';
        echo '<li><strong>' . (int) $count->draft . '</strong> brouillons</li>';
        echo '<li><strong>' . (int) ($leads->publish ?? 0) . '</strong> demandes reçues</li>';
        echo '</ul><p><a class="button button-primary" href="' . esc_url(admin_url('post-new.php?post_type=property')) . '">Ajouter un bien</a> ';
        echo '<a class="button" href="' . esc_url(admin_url('edit.php?post_type=nova_lead')) . '">Voir les demandes</a></p>';
    });
});
