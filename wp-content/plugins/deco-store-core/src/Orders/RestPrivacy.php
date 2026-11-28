<?php
/**
 * WooCommerce's REST API returns every meta entry of an order, its line
 * items and products, underscore keys included, to anyone allowed to read
 * them (staff accounts and API keys). Déco Store keeps internal data there
 * (price snapshots, flags, hashed network address; purchase costs from
 * Phase 7), so every `_ds_` entry is removed from those responses.
 * The data stays visible in the order screen to the people allowed to see it.
 *
 * @package DecoStore
 */

namespace DecoStore\Orders;

defined( 'ABSPATH' ) || exit;

/**
 * Strips internal meta from WooCommerce REST responses.
 */
class RestPrivacy {

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'woocommerce_rest_prepare_shop_order_object', array( __CLASS__, 'filter' ), 99 );
		add_filter( 'woocommerce_rest_prepare_product_object', array( __CLASS__, 'filter' ), 99 );
		add_filter( 'woocommerce_rest_prepare_product_variation_object', array( __CLASS__, 'filter' ), 99 );
	}

	/**
	 * Remove `_ds_` entries from a meta_data list.
	 *
	 * @param mixed $meta List of meta (arrays or WC_Meta_Data objects).
	 * @return array
	 */
	public static function clean( $meta ) {
		if ( ! is_array( $meta ) ) {
			return array();
		}
		return array_values(
			array_filter(
				$meta,
				static function ( $entry ) {
					$key = is_array( $entry ) ? ( isset( $entry['key'] ) ? $entry['key'] : '' ) : ( is_object( $entry ) && isset( $entry->key ) ? $entry->key : '' );
					return 0 !== strpos( (string) $key, '_ds_' );
				}
			)
		);
	}

	/**
	 * Filter a REST response.
	 *
	 * @param \WP_REST_Response $response Response.
	 * @return \WP_REST_Response
	 */
	public static function filter( $response ) {
		if ( ! $response instanceof \WP_REST_Response ) {
			return $response;
		}
		$data = $response->get_data();
		if ( isset( $data['meta_data'] ) ) {
			$data['meta_data'] = self::clean( $data['meta_data'] );
		}
		foreach ( array( 'line_items', 'shipping_lines', 'fee_lines' ) as $list ) {
			if ( isset( $data[ $list ] ) && is_array( $data[ $list ] ) ) {
				foreach ( $data[ $list ] as $i => $item ) {
					if ( isset( $item['meta_data'] ) ) {
						$data[ $list ][ $i ]['meta_data'] = self::clean( $item['meta_data'] );
					}
				}
			}
		}
		$response->set_data( $data );
		return $response;
	}
}
