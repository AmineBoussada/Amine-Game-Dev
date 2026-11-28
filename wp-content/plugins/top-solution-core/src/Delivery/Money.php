<?php
/**
 * Money in integer minor units (e.g. centimes when the store uses 2
 * decimals, whole dinars when it uses 0). Pure functions, unit-tested.
 * Floats are never used for amounts that are stored or compared.
 *
 * @package TopSolution
 */

namespace TopSolution\Delivery;

defined( 'ABSPATH' ) || exit;

/**
 * Parsing and formatting of amounts.
 */
class Money {

	/**
	 * Parse an amount typed by a person or read from a CSV file.
	 *
	 * Accepts digits with spaces as thousands separators and one decimal
	 * separator ("." or ","), e.g. "1 200", "1200.5", "1200,50", "٣٠٠"
	 * (Arabic-Indic digits are converted). Rejects negatives and anything else.
	 *
	 * @param mixed $amount   Raw value.
	 * @param int   $decimals Store decimals (wc_get_price_decimals()).
	 * @return int|null Minor units, or null when the value is not a valid amount.
	 */
	public static function to_minor( $amount, $decimals ) {
		if ( is_int( $amount ) ) {
			$amount = (string) $amount;
		}
		if ( ! is_string( $amount ) ) {
			return null;
		}
		$decimals = max( 0, (int) $decimals );
		$amount   = strtr( $amount, array_combine( array( '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩' ), range( '0', '9' ) ) );
		$amount   = str_replace( array( ' ', "\u{00A0}", "\u{202F}", '٬' ), '', trim( $amount ) );
		$amount   = str_replace( array( ',', '٫' ), '.', $amount );
		if ( '' === $amount || ! preg_match( '/^\d+(\.\d+)?$/', $amount ) ) {
			return null;
		}
		$parts    = explode( '.', $amount, 2 );
		$whole    = ltrim( $parts[0], '0' );
		$fraction = isset( $parts[1] ) ? $parts[1] : '';
		if ( strlen( $fraction ) > $decimals ) {
			// More precision than the store uses: only zeros may be dropped.
			if ( '' !== trim( substr( $fraction, $decimals ), '0' ) ) {
				return null;
			}
			$fraction = substr( $fraction, 0, $decimals );
		}
		$fraction = str_pad( $fraction, $decimals, '0' );
		$digits   = ltrim( ( '' === $whole ? '0' : $whole ) . $fraction, '0' );
		if ( strlen( $digits ) > 15 ) {
			return null; // Beyond any realistic price; keeps integers exact.
		}
		return (int) ( '' === $digits ? '0' : $digits );
	}

	/**
	 * Minor units to a plain decimal string ("1200.50"), as stored in the
	 * database and passed to WooCommerce.
	 *
	 * @param int $minor    Minor units.
	 * @param int $decimals Store decimals.
	 * @return string
	 */
	public static function to_decimal( $minor, $decimals ) {
		$decimals = max( 0, (int) $decimals );
		$minor    = (int) $minor;
		$sign     = $minor < 0 ? '-' : '';
		$digits   = str_pad( (string) abs( $minor ), $decimals + 1, '0', STR_PAD_LEFT );
		if ( 0 === $decimals ) {
			return $sign . $digits;
		}
		return $sign . substr( $digits, 0, -$decimals ) . '.' . substr( $digits, -$decimals );
	}
}
