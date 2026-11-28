<?php
/**
 * Creates the WooCommerce shipping zone "Algeria" with the Déco Store
 * delivery method (one click in Déco Store > Delivery).
 *
 * @package DecoStore
 */

namespace DecoStore\Delivery;

defined( 'ABSPATH' ) || exit;

/**
 * Shipping zone helper.
 */
class ZoneSetup {

	/**
	 * Id of a zone that already uses the method (0 = none).
	 *
	 * @return int
	 */
	public static function existing_zone_id() {
		if ( ! class_exists( '\WC_Shipping_Zones' ) ) {
			return 0;
		}
		foreach ( \WC_Shipping_Zones::get_zones() as $zone ) {
			foreach ( $zone['shipping_methods'] as $method ) {
				if ( ShippingMethod::ID === $method->id ) {
					return (int) $zone['id'];
				}
			}
		}
		return 0;
	}

	/**
	 * Create the zone if no zone uses the method yet.
	 *
	 * @return array{zone_id:int,created:bool}
	 */
	public static function ensure() {
		$existing = self::existing_zone_id();
		if ( $existing ) {
			return array(
				'zone_id' => $existing,
				'created' => false,
			);
		}
		$zone = new \WC_Shipping_Zone();
		$zone->set_zone_name( __( 'الجزائر — Déco Store', 'deco-store-core' ) );
		$zone->add_location( 'DZ', 'country' );
		$zone->save();
		$zone->add_shipping_method( ShippingMethod::ID );
		return array(
			'zone_id' => (int) $zone->get_id(),
			'created' => true,
		);
	}
}
