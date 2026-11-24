<?php
/**
 * Champs personnalisés des annonces (natifs, sans ACF).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const NOVA_IMMO_META_FIELDS = array(
	'_nova_prix'        => 'integer',  // prix en DZD
	'_nova_surface'     => 'integer',  // m²
	'_nova_pieces'      => 'integer',
	'_nova_salles_bain' => 'integer',
	'_nova_etage'       => 'string',
	'_nova_reference'   => 'string',
	'_nova_adresse'     => 'string',
	'_nova_lat'         => 'number',
	'_nova_lng'         => 'number',
	'_nova_disponible'  => 'boolean',
	'_nova_featured'    => 'boolean',
	'_nova_galerie'     => 'string',   // IDs des images séparés par des virgules
);

add_action( 'init', function () {
	foreach ( NOVA_IMMO_META_FIELDS as $key => $type ) {
		register_post_meta( 'annonce', $key, array(
			'type'          => $type,
			'single'        => true,
			'show_in_rest'  => true,
			'auth_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
		) );
	}
} );

/**
 * Lit toutes les données d'une annonce sous forme de tableau prêt pour le template ou l'API.
 */
function nova_immo_get_listing( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post || 'annonce' !== $post->post_type ) {
		return null;
	}

	$gallery_ids = array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( $post_id, '_nova_galerie', true ) ) ) );
	$transaction = wp_get_post_terms( $post_id, 'transaction', array( 'fields' => 'slugs' ) );
	$type        = wp_get_post_terms( $post_id, 'type_bien', array( 'fields' => 'names' ) );
	$wilaya      = wp_get_post_terms( $post_id, 'wilaya', array( 'fields' => 'names' ) );

	$image_id = get_post_thumbnail_id( $post_id );

	return array(
		'id'          => $post_id,
		'title'       => get_the_title( $post_id ),
		'url'         => get_permalink( $post_id ),
		'reference'   => (string) get_post_meta( $post_id, '_nova_reference', true ),
		'transaction' => $transaction ? $transaction[0] : '',
		'type'        => $type ? $type[0] : '',
		'wilaya'      => $wilaya ? $wilaya[0] : '',
		'adresse'     => (string) get_post_meta( $post_id, '_nova_adresse', true ),
		'prix'        => (int) get_post_meta( $post_id, '_nova_prix', true ),
		'prix_label'  => nova_immo_format_price( get_post_meta( $post_id, '_nova_prix', true ) ),
		'surface'     => (int) get_post_meta( $post_id, '_nova_surface', true ),
		'pieces'      => (int) get_post_meta( $post_id, '_nova_pieces', true ),
		'salles_bain' => (int) get_post_meta( $post_id, '_nova_salles_bain', true ),
		'etage'       => (string) get_post_meta( $post_id, '_nova_etage', true ),
		'lat'         => (float) get_post_meta( $post_id, '_nova_lat', true ),
		'lng'         => (float) get_post_meta( $post_id, '_nova_lng', true ),
		'disponible'  => (bool) get_post_meta( $post_id, '_nova_disponible', true ),
		'featured'    => (bool) get_post_meta( $post_id, '_nova_featured', true ),
		'date'        => get_the_date( 'd/m/Y', $post_id ),
		'excerpt'     => wp_strip_all_tags( get_the_excerpt( $post_id ) ),
		'image'       => $image_id ? wp_get_attachment_image_url( $image_id, 'nova-card' ) : '',
		'gallery'     => array_values( array_filter( array_map( function ( $id ) {
			return wp_get_attachment_image_url( $id, 'nova-hero' );
		}, $gallery_ids ) ) ),
	);
}
