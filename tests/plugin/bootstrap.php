<?php
/**
 * Unit-test bootstrap: pure classes only, no WordPress.
 * (Integration checks that need WordPress live in integration.php.)
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'DS_CORE_MIN_PHP', '7.4' );
define( 'DS_CORE_MIN_WP', '6.4' );
define( 'DS_CORE_MIN_WC', '8.0' );

// Minimal stubs of the few WordPress functions the pure classes may touch.
if ( ! function_exists( '__' ) ) {
	/**
	 * Translation stub.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	function __( $text ) {
		return $text;
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	/**
	 * JSON stub.
	 *
	 * @param mixed $data Data.
	 * @return string|false
	 */
	function wp_json_encode( $data ) {
		return json_encode( $data );
	}
}

require_once dirname( __DIR__, 2 ) . '/wp-content/plugins/deco-store-core/src/Core/Autoloader.php';
\DecoStore\Core\Autoloader::register( 'DecoStore\\', dirname( __DIR__, 2 ) . '/wp-content/plugins/deco-store-core/src/' );
