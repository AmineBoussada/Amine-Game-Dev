<?php
/**
 * Delivery fee resolution. Pure function of its inputs, unit-tested; the
 * DeliveryService loads the data and calls it.
 *
 * Resolution order for a method: commune rate → wilaya rate → default rate
 * for the method. The first rate found decides: a rate switched off at a
 * level means "not delivered there", even if a broader level has a price.
 * No rate at any level means "not available"; a price is never guessed.
 *
 * @package DecoStore
 */

namespace DecoStore\Delivery;

defined( 'ABSPATH' ) || exit;

/**
 * Computes a quote.
 */
class RateCalculator {

	/**
	 * Quote one delivery method.
	 *
	 * @param array $request {
	 *     The order context.
	 *
	 *     @type string $method         Method id (home | office).
	 *     @type int    $wilaya_id      Wilaya geo id (0 = unknown).
	 *     @type int    $commune_id     Commune geo id (0 = not chosen).
	 *     @type bool   $wilaya_active  Wilaya is served.
	 *     @type bool   $commune_active Commune is served (true when none chosen).
	 *     @type int    $subtotal_minor Order subtotal in minor units.
	 * }
	 * @param array $rates    Map "method:geo_id" => array{id:int,fee_minor:int,active:bool}. geo_id 0 = method default.
	 * @param array $settings {
	 *     Delivery settings.
	 *
	 *     @type array    $enabled_methods      Method ids that are switched on.
	 *     @type int|null $free_threshold_minor Subtotal from which delivery is free (null = off).
	 * }
	 * @return array{available:bool,fee_minor:int,source:string,rate_id:int,free:bool,reason:string}
	 */
	public static function quote( array $request, array $rates, array $settings ) {
		$method = isset( $request['method'] ) ? (string) $request['method'] : '';
		$result = array(
			'available' => false,
			'fee_minor' => 0,
			'source'    => '',
			'rate_id'   => 0,
			'free'      => false,
			'reason'    => '',
		);

		$enabled = isset( $settings['enabled_methods'] ) ? (array) $settings['enabled_methods'] : array();
		if ( ! in_array( $method, $enabled, true ) ) {
			$result['reason'] = 'method_disabled';
			return $result;
		}
		$wilaya_id  = isset( $request['wilaya_id'] ) ? (int) $request['wilaya_id'] : 0;
		$commune_id = isset( $request['commune_id'] ) ? (int) $request['commune_id'] : 0;
		if ( $wilaya_id <= 0 ) {
			$result['reason'] = 'no_area';
			return $result;
		}
		if ( empty( $request['wilaya_active'] ) || ( $commune_id > 0 && empty( $request['commune_active'] ) ) ) {
			$result['reason'] = 'area_inactive';
			return $result;
		}

		$levels = array();
		if ( $commune_id > 0 ) {
			$levels['commune'] = $commune_id;
		}
		$levels['wilaya']  = $wilaya_id;
		$levels['default'] = 0;

		foreach ( $levels as $source => $geo_id ) {
			$key = $method . ':' . $geo_id;
			if ( ! isset( $rates[ $key ] ) ) {
				continue;
			}
			$rate = $rates[ $key ];
			if ( empty( $rate['active'] ) ) {
				$result['reason']  = 'not_served';
				$result['source']  = $source;
				$result['rate_id'] = (int) $rate['id'];
				return $result;
			}
			$fee = max( 0, (int) $rate['fee_minor'] );

			$threshold = isset( $settings['free_threshold_minor'] ) ? $settings['free_threshold_minor'] : null;
			$subtotal  = isset( $request['subtotal_minor'] ) ? (int) $request['subtotal_minor'] : 0;
			$free      = null !== $threshold && $subtotal >= (int) $threshold;

			$result['available'] = true;
			$result['fee_minor'] = $free ? 0 : $fee;
			$result['source']    = $source;
			$result['rate_id']   = (int) $rate['id'];
			$result['free']      = $free || 0 === $fee;
			$result['reason']    = $free ? 'free_threshold' : '';
			return $result;
		}

		$result['reason'] = 'no_rate';
		return $result;
	}
}
