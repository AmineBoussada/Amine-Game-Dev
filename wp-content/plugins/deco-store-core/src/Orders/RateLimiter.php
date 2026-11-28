<?php
/**
 * Simple fixed-window counters in transients (object cache when present).
 * The network address is stored only as a salted hash.
 *
 * @package DecoStore
 */

namespace DecoStore\Orders;

defined( 'ABSPATH' ) || exit;

/**
 * Attempt counters.
 */
class RateLimiter {

	/**
	 * Count one attempt; false when the limit is already reached.
	 *
	 * @param string $bucket Bucket name (already hashed).
	 * @param int    $limit  Allowed attempts per window.
	 * @param int    $window Window in seconds.
	 * @param int    $now    Unix time.
	 * @return bool
	 */
	public static function hit( $bucket, $limit, $window, $now ) {
		$name = 'ds_rl_' . substr( hash( 'sha256', $bucket ), 0, 32 );
		$data = get_transient( $name );
		if ( ! is_array( $data ) || ! isset( $data['start'], $data['count'] ) || $now - (int) $data['start'] >= $window ) {
			$data = array(
				'start' => (int) $now,
				'count' => 0,
			);
		}
		if ( $data['count'] >= $limit ) {
			return false;
		}
		++$data['count'];
		set_transient( $name, $data, $window );
		return true;
	}

	/**
	 * Privacy-preserving hash of a network address.
	 *
	 * @param string $ip Address.
	 * @return string
	 */
	public static function ip_hash( $ip ) {
		return substr( hash_hmac( 'sha256', (string) $ip, wp_salt( 'nonce' ) ), 0, 24 );
	}
}
