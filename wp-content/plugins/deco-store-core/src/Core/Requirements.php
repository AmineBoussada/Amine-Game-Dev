<?php
/**
 * Environment requirements. The evaluation is a pure function of an
 * environment array so it can be unit-tested without WordPress.
 *
 * @package DecoStore
 */

namespace DecoStore\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Checks PHP, WordPress and WooCommerce versions.
 */
class Requirements {

	/**
	 * Read the real environment.
	 *
	 * @return array{php:string,wp:string,wc:string|null}
	 */
	public static function environment() {
		global $wp_version;
		return array(
			'php' => PHP_VERSION,
			'wp'  => (string) $wp_version,
			'wc'  => defined( 'WC_VERSION' ) ? WC_VERSION : null,
		);
	}

	/**
	 * Compare an environment with the minimum versions.
	 *
	 * @param array $env     Keys php, wp, wc (wc = null when WooCommerce is not active).
	 * @param array $minimum Keys php, wp, wc. Defaults to the plugin constants.
	 * @return string[] Problem codes: php, wp, wc_missing, wc_version. Empty when fine.
	 */
	public static function evaluate( array $env, array $minimum = array() ) {
		$minimum  = array_merge(
			array(
				'php' => defined( 'DS_CORE_MIN_PHP' ) ? DS_CORE_MIN_PHP : '7.4',
				'wp'  => defined( 'DS_CORE_MIN_WP' ) ? DS_CORE_MIN_WP : '6.4',
				'wc'  => defined( 'DS_CORE_MIN_WC' ) ? DS_CORE_MIN_WC : '8.0',
			),
			$minimum
		);
		$problems = array();

		if ( version_compare( $env['php'], $minimum['php'], '<' ) ) {
			$problems[] = 'php';
		}
		if ( version_compare( $env['wp'], $minimum['wp'], '<' ) ) {
			$problems[] = 'wp';
		}
		if ( empty( $env['wc'] ) ) {
			$problems[] = 'wc_missing';
		} elseif ( version_compare( $env['wc'], $minimum['wc'], '<' ) ) {
			$problems[] = 'wc_version';
		}
		return $problems;
	}

	/**
	 * Human-readable (Arabic) message for a problem code.
	 *
	 * @param string $code Problem code from evaluate().
	 * @return string
	 */
	public static function message( $code ) {
		switch ( $code ) {
			case 'php':
				/* translators: %s: minimum PHP version. */
				return sprintf( __( 'يتطلب Déco Store Core إصدار PHP %s أو أحدث.', 'deco-store-core' ), DS_CORE_MIN_PHP );
			case 'wp':
				/* translators: %s: minimum WordPress version. */
				return sprintf( __( 'يتطلب Déco Store Core ووردبريس %s أو أحدث.', 'deco-store-core' ), DS_CORE_MIN_WP );
			case 'wc_missing':
				return __( 'يتطلب Déco Store Core تفعيل إضافة ووكومرس.', 'deco-store-core' );
			case 'wc_version':
				/* translators: %s: minimum WooCommerce version. */
				return sprintf( __( 'يتطلب Déco Store Core ووكومرس %s أو أحدث.', 'deco-store-core' ), DS_CORE_MIN_WC );
			default:
				return '';
		}
	}
}
