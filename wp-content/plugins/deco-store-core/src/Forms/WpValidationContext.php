<?php
/**
 * ValidationContext backed by the real data (delivery tables and settings).
 *
 * @package DecoStore
 */

namespace DecoStore\Forms;

use DecoStore\Core\Settings;
use DecoStore\Delivery\DeliverySettings;
use DecoStore\Delivery\GeoRepository;
use DecoStore\Delivery\PickupRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Real lookups.
 */
class WpValidationContext implements ValidationContext {

	/**
	 * Existing, served wilaya?
	 *
	 * @param int $wilaya_id Wilaya id.
	 * @return bool
	 */
	public function wilaya_is_served( $wilaya_id ) {
		$geo = GeoRepository::get( (int) $wilaya_id );
		return $geo && 'wilaya' === $geo['level'] && $geo['active'];
	}

	/**
	 * Communes registered for a wilaya.
	 *
	 * @param int $wilaya_id Wilaya id.
	 * @return int
	 */
	public function commune_count( $wilaya_id ) {
		$counts = GeoRepository::commune_counts();
		return isset( $counts[ (int) $wilaya_id ] ) ? $counts[ (int) $wilaya_id ] : 0;
	}

	/**
	 * Served commune of that wilaya?
	 *
	 * @param int $commune_id Commune id.
	 * @param int $wilaya_id  Wilaya id.
	 * @return bool
	 */
	public function commune_belongs( $commune_id, $wilaya_id ) {
		$geo = GeoRepository::get( (int) $commune_id );
		return $geo && 'commune' === $geo['level'] && $geo['active'] && $geo['parent_id'] === (int) $wilaya_id;
	}

	/**
	 * Enabled delivery methods.
	 *
	 * @return string[]
	 */
	public function delivery_methods() {
		return DeliverySettings::enabled_methods();
	}

	/**
	 * Active pickup point serving the area?
	 *
	 * @param int $pickup_id  Pickup id.
	 * @param int $wilaya_id  Wilaya id.
	 * @param int $commune_id Commune id.
	 * @return bool
	 */
	public function pickup_serves( $pickup_id, $wilaya_id, $commune_id ) {
		foreach ( PickupRepository::for_area( (int) $wilaya_id, (int) $commune_id ) as $point ) {
			if ( (int) $pickup_id === $point['id'] ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Phone rule from settings.
	 *
	 * @return string
	 */
	public function phone_rule() {
		return (string) Settings::get( 'phone_rule' );
	}
}
