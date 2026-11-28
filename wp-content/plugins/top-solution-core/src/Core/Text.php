<?php
/**
 * Text cleaning shared by imports and forms. Pure functions, unit-tested.
 *
 * @package TopSolution
 */

namespace TopSolution\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Single-line and multi-line text cleaning.
 */
class Text {

	/**
	 * Single-line text: no tags, no control characters, collapsed spaces.
	 *
	 * @param mixed $value Raw value.
	 * @param int   $max   Maximum length in characters.
	 * @return string
	 */
	public static function line( $value, $max = 191 ) {
		if ( ! is_scalar( $value ) ) {
			return '';
		}
		$value = preg_replace( '/<[^>]*>/', '', (string) $value );
		$value = preg_replace( '/[\x00-\x1F\x7F]+/u', ' ', (string) $value );
		$value = trim( (string) preg_replace( '/\s+/u', ' ', (string) $value ) );
		return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $max ) : substr( $value, 0, $max );
	}

	/**
	 * Multi-line text: no tags, no control characters except new lines.
	 *
	 * @param mixed $value Raw value.
	 * @param int   $max   Maximum length in characters.
	 * @return string
	 */
	public static function block( $value, $max = 1000 ) {
		if ( ! is_scalar( $value ) ) {
			return '';
		}
		$value = preg_replace( '/<[^>]*>/', '', (string) $value );
		$value = str_replace( array( "\r\n", "\r" ), "\n", (string) $value );
		$value = preg_replace( '/[\x00-\x09\x0B-\x1F\x7F]+/u', ' ', (string) $value );
		$value = preg_replace( '/[ \t]+/u', ' ', (string) $value );
		$value = trim( (string) preg_replace( "/\n{3,}/", "\n\n", (string) $value ) );
		return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $max ) : substr( $value, 0, $max );
	}

	/**
	 * Length in characters.
	 *
	 * @param string $value Text.
	 * @return int
	 */
	public static function length( $value ) {
		return function_exists( 'mb_strlen' ) ? mb_strlen( (string) $value ) : strlen( (string) $value );
	}

	/**
	 * Arabic-Indic and Persian digits to Western digits.
	 *
	 * @param string $value Text.
	 * @return string
	 */
	public static function western_digits( $value ) {
		$from = array( '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩', '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' );
		$to   = array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' );
		return str_replace( $from, $to, (string) $value );
	}
}
