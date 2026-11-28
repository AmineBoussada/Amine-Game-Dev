<?php
/**
 * Phone number rules. The owner chooses one in Settings > Forms; nothing is
 * assumed about the market by default (generic rule).
 *
 * @package TopSolution
 */

namespace TopSolution\Forms;

use TopSolution\Core\Text;

defined( 'ABSPATH' ) || exit;

/**
 * Normalises and validates phone numbers. Pure, unit-tested.
 */
class PhoneRule {

	const GENERIC   = 'generic';
	const DZ_MOBILE = 'dz_mobile';

	/**
	 * Available rules.
	 *
	 * @return string[]
	 */
	public static function ids() {
		return array( self::GENERIC, self::DZ_MOBILE );
	}

	/**
	 * Remove separators and convert digits.
	 *
	 * @param mixed $raw Raw input.
	 * @return string
	 */
	public static function strip( $raw ) {
		if ( ! is_scalar( $raw ) ) {
			return '';
		}
		$value = Text::western_digits( Text::line( $raw, 40 ) );
		return (string) preg_replace( '/[\s\-.()\/\x{200E}\x{200F}\x{202A}-\x{202E}]+/u', '', $value );
	}

	/**
	 * Canonical number for a rule, or null when invalid.
	 *
	 * - generic: optional leading "+", then 8 to 15 digits.
	 * - dz_mobile: 05, 06 or 07 followed by 8 digits; "+213", "00213" and
	 *   "213" prefixes are accepted and normalised to the local "0…" form.
	 *
	 * @param mixed  $raw  Raw input.
	 * @param string $rule Rule id.
	 * @return string|null
	 */
	public static function normalize( $raw, $rule ) {
		$value = self::strip( $raw );
		if ( '' === $value ) {
			return null;
		}
		if ( self::DZ_MOBILE === $rule ) {
			$value = (string) preg_replace( '/^(\+213|00213|213)/', '0', $value );
			return preg_match( '/^0[567]\d{8}$/', $value ) ? $value : null;
		}
		return preg_match( '/^\+?\d{8,15}$/', $value ) ? $value : null;
	}

	/**
	 * Regular expression the browser may use for the same rule (kept in sync
	 * with normalize(); the server stays authoritative).
	 *
	 * @param string $rule Rule id.
	 * @return string JavaScript-compatible pattern without delimiters.
	 */
	public static function js_pattern( $rule ) {
		return self::DZ_MOBILE === $rule ? '^0[567][0-9]{8}$' : '^\\+?[0-9]{8,15}$';
	}

	/**
	 * Strip prefixes the browser should also apply before testing the pattern.
	 *
	 * @param string $rule Rule id.
	 * @return string JavaScript regular expression source, or empty.
	 */
	public static function js_prefix( $rule ) {
		return self::DZ_MOBILE === $rule ? '^(\\+213|00213|213)' : '';
	}
}
