<?php
/**
 * Endpoint REST : /wp-json/nova/v1/annonces
 * Paramètres : transaction, type, wilaya, budget_min, budget_max, sort (recent|prix_asc|prix_desc), per_page, page
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'rest_api_init', function () {
	register_rest_route( 'nova/v1', '/annonces', array(
		'methods'             => 'GET',
		'permission_callback' => '__return_true',
		'args'                => array(
			'transaction' => array( 'sanitize_callback' => 'sanitize_title' ),
			'type'        => array( 'sanitize_callback' => 'sanitize_title' ),
			'wilaya'      => array( 'sanitize_callback' => 'sanitize_title' ),
			'budget_min'  => array( 'sanitize_callback' => 'absint' ),
			'budget_max'  => array( 'sanitize_callback' => 'absint' ),
			'sort'        => array(
				'default'           => 'recent',
				'sanitize_callback' => 'sanitize_key',
				'validate_callback' => function ( $value ) {
					return in_array( $value, array( 'recent', 'prix_asc', 'prix_desc' ), true );
				},
			),
			'per_page'    => array( 'default' => 12, 'sanitize_callback' => 'absint' ),
			'page'        => array( 'default' => 1, 'sanitize_callback' => 'absint' ),
		),
		'callback'            => 'nova_immo_rest_listings',
	) );
} );

function nova_immo_rest_listings( WP_REST_Request $request ) {
	$tax_query  = array( 'relation' => 'AND' );
	$meta_query = array( 'relation' => 'AND' );

	$transaction = $request->get_param( 'transaction' );
	$type        = $request->get_param( 'type' );
	$wilaya      = $request->get_param( 'wilaya' );

	if ( $transaction ) {
		$tax_query[] = array( 'taxonomy' => 'transaction', 'field' => 'slug', 'terms' => $transaction );
	}
	if ( $type ) {
		$tax_query[] = array( 'taxonomy' => 'type_bien', 'field' => 'slug', 'terms' => $type );
	}
	if ( $wilaya ) {
		$tax_query[] = array( 'taxonomy' => 'wilaya', 'field' => 'slug', 'terms' => $wilaya );
	}

	$budget_min = $request->get_param( 'budget_min' );
	$budget_max = $request->get_param( 'budget_max' );
	if ( $budget_min || $budget_max ) {
		$range = array( 'key' => '_nova_prix', 'type' => 'NUMERIC', 'compare' => 'BETWEEN' );
		$range['value'] = array( (int) $budget_min, $budget_max ? (int) $budget_max : PHP_INT_MAX );
		$meta_query[]   = $range;
	}

	$sort_map = array(
		'recent'     => array( 'orderby' => 'date', 'order' => 'DESC' ),
		'prix_asc'   => array( 'orderby' => 'meta_value_num', 'meta_key' => '_nova_prix', 'order' => 'ASC' ),
		'prix_desc'  => array( 'orderby' => 'meta_value_num', 'meta_key' => '_nova_prix', 'order' => 'DESC' ),
	);
	$sort = $sort_map[ $request->get_param( 'sort' ) ] ?? $sort_map['recent'];

	$per_page = min( max( 1, (int) $request->get_param( 'per_page' ) ), 50 );

	$query = new WP_Query( array_merge( $sort, array(
		'post_type'      => 'annonce',
		'post_status'    => 'publish',
		'posts_per_page' => $per_page,
		'paged'          => max( 1, (int) $request->get_param( 'page' ) ),
		'tax_query'      => count( $tax_query ) > 1 ? $tax_query : array(),
		'meta_query'     => count( $meta_query ) > 1 ? $meta_query : array(),
		'no_found_rows'  => false,
	) ) );

	$items = array_map( 'nova_immo_get_listing', $query->posts );

	$response = new WP_REST_Response( array_values( array_filter( $items ) ) );
	$response->header( 'X-WP-Total', (int) $query->found_posts );
	$response->header( 'X-WP-TotalPages', (int) $query->max_num_pages );
	return $response;
}
