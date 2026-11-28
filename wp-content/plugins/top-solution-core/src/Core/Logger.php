<?php
/**
 * Logging through WooCommerce's logger (WooCommerce > Status > Logs, source
 * `top-solution`). Personal data and costs are never written.
 *
 * @package TopSolution
 */

namespace TopSolution\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Thin, privacy-safe logger.
 */
class Logger {

	const SOURCE = 'top-solution';

	/**
	 * Whole words (in snake_case, camelCase or kebab-case keys) that mark a
	 * value as personal or confidential.
	 *
	 * @return string[]
	 */
	public static function sensitive_words() {
		return array( 'ip', 'name', 'username', 'phone', 'mobile', 'tel', 'email', 'address', 'wilaya', 'commune', 'city', 'cost', 'costs', 'supplier', 'password', 'token', 'secret' );
	}

	/**
	 * Is this context key sensitive? Matches whole words, so `ip` catches
	 * `client_ip` but not `description` or `shipping_method`.
	 *
	 * @param string $key Context key.
	 * @return bool
	 */
	public static function is_sensitive_key( $key ) {
		$spaced = preg_replace( '/([a-z0-9])([A-Z])/', '$1_$2', (string) $key );
		$words  = preg_split( '/[^a-z0-9]+/', strtolower( (string) $spaced ), -1, PREG_SPLIT_NO_EMPTY );
		if ( array_intersect( $words, self::sensitive_words() ) ) {
			return true;
		}
		// Long, unambiguous fragments are matched anywhere (e.g. "customerphone").
		$lower = strtolower( (string) $key );
		foreach ( array( 'phone', 'email', 'address', 'supplier', 'password', 'token', 'secret' ) as $fragment ) {
			if ( false !== strpos( $lower, $fragment ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Replace values of sensitive keys with a marker. Pure function, unit-tested.
	 *
	 * @param array $context Log context.
	 * @return array
	 */
	public static function redact( array $context ) {
		foreach ( $context as $key => $value ) {
			if ( self::is_sensitive_key( $key ) ) {
				$context[ $key ] = '[redacted]';
			} elseif ( is_array( $value ) ) {
				$context[ $key ] = self::redact( $value );
			}
		}
		return $context;
	}

	/**
	 * Debug message: only written when debug logging is switched on.
	 *
	 * @param string $message Message.
	 * @param array  $context Context (redacted).
	 * @return void
	 */
	public static function debug( $message, array $context = array() ) {
		if ( Settings::get( 'debug_logging' ) ) {
			self::write( 'debug', $message, $context );
		}
	}

	/**
	 * Info message.
	 *
	 * @param string $message Message.
	 * @param array  $context Context (redacted).
	 * @return void
	 */
	public static function info( $message, array $context = array() ) {
		self::write( 'info', $message, $context );
	}

	/**
	 * Warning message.
	 *
	 * @param string $message Message.
	 * @param array  $context Context (redacted).
	 * @return void
	 */
	public static function warning( $message, array $context = array() ) {
		self::write( 'warning', $message, $context );
	}

	/**
	 * Error message.
	 *
	 * @param string $message Message.
	 * @param array  $context Context (redacted).
	 * @return void
	 */
	public static function error( $message, array $context = array() ) {
		self::write( 'error', $message, $context );
	}

	/**
	 * Write through WC_Logger when available.
	 *
	 * @param string $level   PSR-3 level.
	 * @param string $message Message.
	 * @param array  $context Context.
	 * @return void
	 */
	private static function write( $level, $message, array $context ) {
		if ( ! function_exists( 'wc_get_logger' ) ) {
			return;
		}
		$context           = self::redact( $context );
		$context['source'] = self::SOURCE;
		wc_get_logger()->log( $level, $message, $context );
	}
}
