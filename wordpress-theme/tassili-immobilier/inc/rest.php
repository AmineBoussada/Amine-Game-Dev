<?php
/**
 * Read-only REST endpoints that make the search feel instant.
 *
 *  GET /wp-json/tassili/v1/count?tx=vente&wilaya=oran   -> { "count": 3 }
 *  GET /wp-json/tassili/v1/cards?ids=12,15,18           -> { "html": "...", "count": 3 }
 *
 * Both are public on purpose: they expose only what the public site already shows.
 *
 * @package Tassili_Immobilier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the routes.
 */
function tassili_register_rest_routes() {
	register_rest_route(
		'tassili/v1',
		'/count',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'tassili_rest_count',
			'permission_callback' => '__return_true',
		)
	);
	register_rest_route(
		'tassili/v1',
		'/cards',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'tassili_rest_cards',
			'permission_callback' => '__return_true',
			'args'                => array(
				'ids' => array(
					'required'          => true,
					'description'       => 'Comma-separated listing IDs (24 at most).',
					'sanitize_callback' => static function ( $value ) {
						$ids = array_values( array_unique( array_filter( array_map( 'absint', explode( ',', (string) $value ) ) ) ) );
						return array_slice( $ids, 0, 24 );
					},
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'tassili_register_rest_routes' );

/**
 * Number of listings matching a filter set.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function tassili_rest_count( $request ) {
	$count    = tassili_count_listings( tassili_normalize_filters( $request->get_query_params() ) );
	$response = rest_ensure_response( array( 'count' => $count ) );
	$response->header( 'Cache-Control', 'no-store' );
	return $response;
}

/**
 * Server-rendered cards for a list of listing IDs.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function tassili_rest_cards( $request ) {
	$ids  = (array) $request['ids'];
	$html = '';
	$n    = 0;
	if ( $ids ) {
		$query = new WP_Query(
			array(
				'post_type'      => 'property',
				'post_status'    => 'publish',
				'post__in'       => $ids,
				'orderby'        => 'post__in',
				'posts_per_page' => count( $ids ),
				'no_found_rows'  => true,
			)
		);
		ob_start();
		while ( $query->have_posts() ) {
			$query->the_post();
			get_template_part( 'template-parts/card', 'property' );
			++$n;
		}
		wp_reset_postdata();
		$html = ob_get_clean();
	}
	$response = rest_ensure_response(
		array(
			'html'  => $html,
			'count' => $n,
		)
	);
	$response->header( 'Cache-Control', 'no-store' );
	return $response;
}
