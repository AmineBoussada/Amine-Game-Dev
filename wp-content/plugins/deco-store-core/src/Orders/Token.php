<?php
/**
 * Form token: an HMAC-signed, time-limited ticket that binds an order
 * submission to the product and form the page showed. Pure functions
 * (the secret and the clock are passed in), unit-tested.
 *
 * Format: base64url(JSON payload) "." base64url(HMAC-SHA256).
 * Payload: p (product id), f (form id), t (issued at, Unix), n (random).
 *
 * @package DecoStore
 */

namespace DecoStore\Orders;

defined( 'ABSPATH' ) || exit;

/**
 * Issue and verify tokens.
 */
class Token {

	const MAX_LENGTH = 512;

	/**
	 * URL-safe base64 without padding.
	 *
	 * @param string $bytes Raw bytes.
	 * @return string
	 */
	private static function b64( $bytes ) {
		return rtrim( strtr( base64_encode( $bytes ), '+/', '-_' ), '=' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Token encoding, not obfuscation.
	}

	/**
	 * Decode URL-safe base64.
	 *
	 * @param string $text Text.
	 * @return string|false
	 */
	private static function unb64( $text ) {
		if ( ! preg_match( '/^[A-Za-z0-9_-]+$/', $text ) ) {
			return false;
		}
		return base64_decode( strtr( $text, '-_', '+/' ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Token decoding, not obfuscation.
	}

	/**
	 * Issue a token.
	 *
	 * @param int    $product_id Product id.
	 * @param int    $form_id    Form id.
	 * @param int    $now        Unix time.
	 * @param string $secret     Signing secret.
	 * @param string $nonce      Random string (hex).
	 * @return string
	 */
	public static function issue( $product_id, $form_id, $now, $secret, $nonce ) {
		$payload = self::b64(
			(string) wp_json_encode(
				array(
					'p' => (int) $product_id,
					'f' => (int) $form_id,
					't' => (int) $now,
					'n' => (string) $nonce,
				)
			)
		);
		return $payload . '.' . self::b64( hash_hmac( 'sha256', $payload, $secret, true ) );
	}

	/**
	 * Verify a token.
	 *
	 * @param mixed  $token   Token text.
	 * @param string $secret  Signing secret.
	 * @param int    $now     Unix time.
	 * @param int    $ttl     Lifetime in seconds.
	 * @return array{ok:bool,error:string,product_id:int,form_id:int,issued_at:int}
	 */
	public static function verify( $token, $secret, $now, $ttl ) {
		$fail = static function ( $error ) {
			return array(
				'ok'         => false,
				'error'      => $error,
				'product_id' => 0,
				'form_id'    => 0,
				'issued_at'  => 0,
			);
		};
		if ( ! is_string( $token ) || strlen( $token ) > self::MAX_LENGTH || 1 !== substr_count( $token, '.' ) ) {
			return $fail( 'bad_token' );
		}
		list( $payload, $sig ) = explode( '.', $token );
		$expected              = self::b64( hash_hmac( 'sha256', $payload, $secret, true ) );
		if ( ! hash_equals( $expected, $sig ) ) {
			return $fail( 'bad_token' );
		}
		$json = self::unb64( $payload );
		$data = false === $json ? null : json_decode( $json, true );
		if ( ! is_array( $data ) || ! isset( $data['p'], $data['f'], $data['t'] ) || ! is_int( $data['p'] ) || ! is_int( $data['f'] ) || ! is_int( $data['t'] ) ) {
			return $fail( 'bad_token' );
		}
		if ( $data['t'] > $now + 60 ) {
			return $fail( 'bad_token' ); // Issued in the future: clock tampering.
		}
		if ( $now - $data['t'] > $ttl ) {
			return $fail( 'expired' );
		}
		return array(
			'ok'         => true,
			'error'      => '',
			'product_id' => $data['p'],
			'form_id'    => $data['f'],
			'issued_at'  => $data['t'],
		);
	}
}
