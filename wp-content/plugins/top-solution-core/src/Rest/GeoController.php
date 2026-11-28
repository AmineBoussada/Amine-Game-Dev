<?php
/**
 * Read-only public endpoints that feed the dependent selects of a form
 * (communes of a wilaya, pickup offices of an area). They return names and
 * ids of data the customer is meant to see: no prices, no costs, no suppliers.
 *
 * @package TopSolution
 */

namespace TopSolution\Rest;

use TopSolution\Delivery\GeoRepository;
use TopSolution\Delivery\PickupRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Routes under /wp-json/ts-core/v1/.
 */
class GeoController {

	const NAMESPACE_ID = 'ts-core/v1';

	/**
	 * Hook.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public static function register_routes() {
		$int = array(
			'type'              => 'integer',
			'required'          => true,
			'sanitize_callback' => 'absint',
			'validate_callback' => static function ( $value ) {
				return is_numeric( $value ) && (int) $value > 0;
			},
		);
		register_rest_route(
			self::NAMESPACE_ID,
			'/communes',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'communes' ),
				'permission_callback' => '__return_true',
				'args'                => array( 'wilaya' => $int ),
			)
		);
		register_rest_route(
			self::NAMESPACE_ID,
			'/pickups',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'pickups' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'wilaya'  => $int,
					'commune' => array(
						'type'              => 'integer',
						'required'          => false,
						'default'           => 0,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/**
	 * Add short public caching (the data changes rarely).
	 *
	 * @param array $data Response data.
	 * @return \WP_REST_Response
	 */
	private static function response( array $data ) {
		$response = new \WP_REST_Response( $data );
		$response->header( 'Cache-Control', 'public, max-age=300' );
		return $response;
	}

	/**
	 * Active communes of a served wilaya.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public static function communes( $request ) {
		$wilaya = GeoRepository::get( (int) $request['wilaya'] );
		if ( ! $wilaya || 'wilaya' !== $wilaya['level'] || ! $wilaya['active'] ) {
			return self::response( array() );
		}
		$out = array();
		foreach ( GeoRepository::communes( $wilaya['id'], true ) as $commune ) {
			$out[] = array(
				'id'   => $commune['id'],
				'name' => $commune['name_ar'],
			);
		}
		return self::response( $out );
	}

	/**
	 * Active pickup offices of an area.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public static function pickups( $request ) {
		$wilaya = GeoRepository::get( (int) $request['wilaya'] );
		if ( ! $wilaya || 'wilaya' !== $wilaya['level'] || ! $wilaya['active'] ) {
			return self::response( array() );
		}
		$commune_id = (int) $request['commune'];
		$commune    = $commune_id ? GeoRepository::get( $commune_id ) : null;
		if ( $commune_id && ( ! $commune || $commune['parent_id'] !== $wilaya['id'] ) ) {
			$commune_id = 0;
		}
		$out = array();
		foreach ( PickupRepository::for_area( $wilaya['id'], $commune_id ) as $point ) {
			$out[] = array(
				'id'      => $point['id'],
				'name'    => $point['name'],
				'carrier' => $point['carrier'],
				'address' => $point['address'],
			);
		}
		return self::response( $out );
	}
}
