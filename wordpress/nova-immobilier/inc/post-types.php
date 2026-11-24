<?php
/**
 * Custom post types: `property` (Biens) and `nova_lead` (Demandes de contact).
 */

defined('ABSPATH') || exit;

add_action('init', function () {
    register_post_type('property', [
        'labels' => [
            'name' => 'Biens',
            'singular_name' => 'Bien',
            'menu_name' => 'Biens immobiliers',
            'all_items' => 'Tous les biens',
            'add_new' => 'Ajouter un bien',
            'add_new_item' => 'Ajouter un bien',
            'edit_item' => 'Modifier le bien',
            'new_item' => 'Nouveau bien',
            'view_item' => 'Voir le bien',
            'view_items' => 'Voir les biens',
            'search_items' => 'Rechercher un bien',
            'not_found' => 'Aucun bien trouvé',
            'not_found_in_trash' => 'Aucun bien dans la corbeille',
            'featured_image' => 'Photo principale',
            'set_featured_image' => 'Choisir la photo principale',
            'remove_featured_image' => 'Retirer la photo principale',
            'use_featured_image' => 'Utiliser comme photo principale',
            'item_published' => 'Bien publié.',
            'item_updated' => 'Bien mis à jour.',
        ],
        'public' => true,
        'has_archive' => 'biens',
        'rewrite' => ['slug' => 'biens', 'with_front' => false],
        'menu_position' => 5,
        'menu_icon' => 'dashicons-building',
        'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'revisions'],
        'show_in_rest' => true,
        'rest_base' => 'biens',
    ]);

    register_post_type('nova_lead', [
        'labels' => [
            'name' => 'Demandes',
            'singular_name' => 'Demande',
            'menu_name' => 'Demandes clients',
            'all_items' => 'Toutes les demandes',
            'edit_item' => 'Demande',
            'search_items' => 'Rechercher une demande',
            'not_found' => 'Aucune demande pour le moment',
        ],
        'public' => false,
        'show_ui' => true,
        'menu_position' => 6,
        'menu_icon' => 'dashicons-email-alt',
        'supports' => ['title'],
        'capability_type' => 'post',
        'capabilities' => ['create_posts' => 'do_not_allow'],
        'map_meta_cap' => true,
    ]);
});

// Use the classic editor screen for properties: one simple form for the agency owner.
add_filter('use_block_editor_for_post_type', function ($use, $post_type) {
    return in_array($post_type, ['property', 'nova_lead'], true) ? false : $use;
}, 10, 2);

add_filter('enter_title_here', function ($text, $post) {
    return $post->post_type === 'property' ? 'Titre de l’annonce (ex. Appartement F3 lumineux avec vue dégagée)' : $text;
}, 10, 2);

// Flush rewrite rules once after the theme is activated so /biens/ works immediately.
add_action('after_switch_theme', function () {
    update_option('nova_flush_rewrite', 1);
});
add_action('init', function () {
    if (get_option('nova_flush_rewrite')) {
        flush_rewrite_rules();
        delete_option('nova_flush_rewrite');
    }
}, 99);
