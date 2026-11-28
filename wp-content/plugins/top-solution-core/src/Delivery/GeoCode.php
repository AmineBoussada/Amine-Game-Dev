<?php
/**
 * Administrative codes (wilaya / commune) and their WooCommerce state codes.
 * Pure functions, unit-tested.
 *
 * @package TopSolution
 */

namespace TopSolution\Delivery;

defined( 'ABSPATH' ) || exit;

/**
 * Code normalisation.
 */
class GeoCode {

	/**
	 * Highest wilaya number WooCommerce has a state code for (DZ-01 … DZ-58).
	 */
	const WC_MAX_WILAYA = 58;

	/**
	 * Normalise a code: digits only, no leading zeros ("01" and "1" are the
	 * same wilaya).
	 *
	 * @param mixed $code Raw code.
	 * @return string|null Normalised code, or null when it is not a number.
	 */
	public static function normalize( $code ) {
		$code = trim( (string) $code );
		if ( '' === $code || ! ctype_digit( $code ) ) {
			return null;
		}
		$code = ltrim( $code, '0' );
		return '' === $code ? '0' : $code;
	}

	/**
	 * Display form of a wilaya code: two digits ("1" → "01").
	 *
	 * @param string $code Normalised code.
	 * @return string
	 */
	public static function display_wilaya( $code ) {
		return str_pad( (string) $code, 2, '0', STR_PAD_LEFT );
	}

	/**
	 * WooCommerce state code for a wilaya ("16" → "DZ-16").
	 *
	 * @param string $code Normalised wilaya code.
	 * @return string|null Null when WooCommerce has no state for this number.
	 */
	public static function to_wc_state( $code ) {
		$number = (int) $code;
		if ( $number < 1 || $number > self::WC_MAX_WILAYA ) {
			return null;
		}
		return 'DZ-' . self::display_wilaya( (string) $number );
	}

	/**
	 * Wilaya code from a WooCommerce state code ("DZ-16" → "16").
	 *
	 * @param string $state WooCommerce state code.
	 * @return string|null
	 */
	public static function from_wc_state( $state ) {
		if ( ! is_string( $state ) || ! preg_match( '/^DZ-(\d{1,3})$/', $state, $m ) ) {
			return null;
		}
		return self::normalize( $m[1] );
	}
}
