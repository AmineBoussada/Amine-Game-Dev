<?php
/**
 * The one entry point for delivery prices. Used by the WooCommerce shipping
 * method now and by the order system (Phase 5) later, so both always agree.
 *
 * @package DecoStore
 */

namespace DecoStore\Delivery;

defined( 'ABSPATH' ) || exit;

/**
 * Loads data and asks RateCalculator.
 */
class DeliveryService {

	/**
	 * Store decimals.
	 *
	 * @return int
	 */
	public static function decimals() {
		return function_exists( 'wc_get_price_decimals' ) ? (int) wc_get_price_decimals() : 0;
	}

	/**
	 * Free-delivery threshold in minor units (null = off).
	 *
	 * @return int|null
	 */
	public static function free_threshold_minor() {
		$value = DeliverySettings::all()['free_threshold'];
		if ( '' === $value ) {
			return null;
		}
		$minor = Money::to_minor( $value, self::decimals() );
		return $minor ? $minor : null;
	}

	/**
	 * Quote one method for an area.
	 *
	 * @param string $method         Method id.
	 * @param int    $wilaya_id      Wilaya id.
	 * @param int    $commune_id     Commune id (0 = not chosen).
	 * @param int    $subtotal_minor Order subtotal, minor units.
	 * @return array Calculator result plus `fee` (decimal string), `label` and `method`.
	 */
	public static function quote( $method, $wilaya_id, $commune_id, $subtotal_minor ) {
		$decimals = self::decimals();
		$wilaya   = $wilaya_id ? GeoRepository::get( $wilaya_id ) : null;
		$commune  = $commune_id ? GeoRepository::get( $commune_id ) : null;

		$invalid = ( $wilaya_id && ( ! $wilaya || 'wilaya' !== $wilaya['level'] ) )
			|| ( $commune_id && ( ! $commune || 'commune' !== $commune['level'] || ! $wilaya || $commune['parent_id'] !== $wilaya['id'] ) );
		if ( $invalid ) {
			$result = array(
				'available' => false,
				'fee_minor' => 0,
				'source'    => '',
				'rate_id'   => 0,
				'free'      => false,
				'reason'    => 'invalid_area',
			);
		} else {
			$rates  = RateRepository::for_lookup( $method, array( (int) $commune_id, (int) $wilaya_id, 0 ), $decimals );
			$result = RateCalculator::quote(
				array(
					'method'         => $method,
					'wilaya_id'      => (int) $wilaya_id,
					'commune_id'     => (int) $commune_id,
					'wilaya_active'  => $wilaya ? $wilaya['active'] : false,
					'commune_active' => $commune ? $commune['active'] : true,
					'subtotal_minor' => (int) $subtotal_minor,
				),
				$rates,
				array(
					'enabled_methods'      => DeliverySettings::enabled_methods(),
					'free_threshold_minor' => self::free_threshold_minor(),
				)
			);
		}
		$result['method'] = $method;
		$result['label']  = DeliverySettings::label( $method );
		$result['fee']    = Money::to_decimal( $result['fee_minor'], $decimals );
		return $result;
	}

	/**
	 * Quote every method.
	 *
	 * @param int $wilaya_id      Wilaya id.
	 * @param int $commune_id     Commune id.
	 * @param int $subtotal_minor Subtotal, minor units.
	 * @return array<string,array>
	 */
	public static function quote_all( $wilaya_id, $commune_id, $subtotal_minor ) {
		$out = array();
		foreach ( DeliverySettings::method_ids() as $method ) {
			$out[ $method ] = self::quote( $method, $wilaya_id, $commune_id, $subtotal_minor );
		}
		return $out;
	}

	/**
	 * Arabic explanation of a quote (admin preview, logs).
	 *
	 * @param array $quote Quote.
	 * @return string
	 */
	public static function explain( array $quote ) {
		if ( $quote['available'] ) {
			$sources = array(
				'commune' => __( 'سعر خاص بالبلدية', 'deco-store-core' ),
				'wilaya'  => __( 'سعر الولاية', 'deco-store-core' ),
				'default' => __( 'السعر الافتراضي لهذه الطريقة', 'deco-store-core' ),
			);
			$text    = isset( $sources[ $quote['source'] ] ) ? $sources[ $quote['source'] ] : '';
			if ( 'free_threshold' === $quote['reason'] ) {
				$text .= ' · ' . __( 'مجاني لأن المجموع بلغ حد التوصيل المجاني', 'deco-store-core' );
			}
			return $text;
		}
		$reasons = array(
			'method_disabled' => __( 'طريقة التوصيل هذه معطّلة.', 'deco-store-core' ),
			'no_area'         => __( 'لم تُحدَّد الولاية.', 'deco-store-core' ),
			'area_inactive'   => __( 'هذه المنطقة غير مفعّلة.', 'deco-store-core' ),
			'not_served'      => __( 'التوصيل غير متاح لهذه المنطقة (معطّل صراحةً).', 'deco-store-core' ),
			'no_rate'         => __( 'لا يوجد سعر لهذه المنطقة ولا سعر افتراضي.', 'deco-store-core' ),
			'invalid_area'    => __( 'البلدية لا تتبع هذه الولاية أو المنطقة غير موجودة.', 'deco-store-core' ),
		);
		return isset( $reasons[ $quote['reason'] ] ) ? $reasons[ $quote['reason'] ] : $quote['reason'];
	}
}
