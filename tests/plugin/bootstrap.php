<?php
/**
 * Unit-test bootstrap: pure classes only, no WordPress.
 * (Integration checks that need WordPress live in integration.php.)
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'TS_CORE_MIN_PHP', '7.4' );
define( 'TS_CORE_MIN_WP', '6.4' );
define( 'TS_CORE_MIN_WC', '8.0' );

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

require_once dirname( __DIR__, 2 ) . '/wp-content/plugins/top-solution-core/src/Core/Autoloader.php';
\TopSolution\Core\Autoloader::register( 'TopSolution\\', dirname( __DIR__, 2 ) . '/wp-content/plugins/top-solution-core/src/' );
