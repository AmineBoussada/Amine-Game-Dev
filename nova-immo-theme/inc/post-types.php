<?php
/**
 * Type de contenu « annonce » et taxonomies.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', function () {
	register_post_type( 'annonce', array(
		'labels'       => array(
			'name'          => __( 'Annonces', 'nova-immo' ),
			'singular_name' => __( 'Annonce', 'nova-immo' ),
			'add_new_item'  => __( 'Ajouter une annonce', 'nova-immo' ),
			'edit_item'     => __( 'Modifier l’annonce', 'nova-immo' ),
		),
		'public'       => true,
		'has_archive'  => 'annonces',
		'rewrite'      => array( 'slug' => 'annonce' ),
		'menu_icon'    => 'dashicons-admin-home',
		'show_in_rest' => true,
		'supports'     => array( 'title', 'editor', 'thumbnail' ),
	) );

	register_taxonomy( 'transaction', 'annonce', array(
		'label'        => __( 'Transaction', 'nova-immo' ),
		'hierarchical' => false,
		'show_in_rest' => true,
		'rewrite'      => array( 'slug' => 'transaction' ),
	) );

	register_taxonomy( 'type_bien', 'annonce', array(
		'label'        => __( 'Type de bien', 'nova-immo' ),
		'hierarchical' => false,
		'show_in_rest' => true,
		'rewrite'      => array( 'slug' => 'type-bien' ),
	) );

	register_taxonomy( 'wilaya', 'annonce', array(
		'label'        => __( 'Wilaya', 'nova-immo' ),
		'hierarchical' => false,
		'show_in_rest' => true,
		'rewrite'      => array( 'slug' => 'wilaya' ),
	) );
} );

/**
 * Termes créés à l'activation du thème (vente/location, types de bien).
 */
add_action( 'after_switch_theme', function () {
	$terms = array(
		'transaction' => array( 'vente' => 'Vente', 'location' => 'Location' ),
		'type_bien'   => array( 'appartement' => 'Appartement', 'villa' => 'Villa', 'terrain' => 'Terrain', 'local-commercial' => 'Local commercial' ),
	);
	foreach ( $terms as $taxonomy => $items ) {
		foreach ( $items as $slug => $name ) {
			if ( ! term_exists( $slug, $taxonomy ) ) {
				wp_insert_term( $name, $taxonomy, array( 'slug' => $slug ) );
			}
		}
	}
} );
