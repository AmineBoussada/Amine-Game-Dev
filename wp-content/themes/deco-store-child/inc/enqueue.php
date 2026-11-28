<?php
/**
 * Styles, scripts, font preloads.
 *
 * @package DecoStoreChild
 */

defined( 'ABSPATH' ) || exit;

/**
 * Cache-busting version for an asset path relative to the theme directory.
 *
 * @param string $relative Path such as `assets/css/main.css`.
 * @return string
 */
function ds_child_asset_version( $relative ) {
	$file = DS_CHILD_DIR . '/' . ltrim( $relative, '/' );
	return file_exists( $file ) ? (string) filemtime( $file ) : DS_CHILD_VERSION;
}

/**
 * Enqueue front-end assets.
 *
 * The parent styles `hello-elementor` (reset) and `hello-elementor-theme-style`
 * stay enqueued so Elementor and parent markup keep working; the parent header
 * and footer stylesheet is dropped because this theme renders its own header
 * and footer.
 *
 * @return void
 */
function ds_child_enqueue_assets() {
	wp_dequeue_style( 'hello-elementor-header-footer' );

	wp_enqueue_style(
		'ds-child-main',
		DS_CHILD_URI . '/assets/css/main.css',
		array( 'hello-elementor-theme-style' ),
		ds_child_asset_version( 'assets/css/main.css' )
	);

	if ( ds_child_is_home_builtin() ) {
		wp_enqueue_style(
			'ds-child-home',
			DS_CHILD_URI . '/assets/css/home.css',
			array( 'ds-child-main' ),
			ds_child_asset_version( 'assets/css/home.css' )
		);
	}

	if ( class_exists( 'WooCommerce' ) ) {
		wp_enqueue_style(
			'ds-child-woocommerce',
			DS_CHILD_URI . '/assets/css/woocommerce.css',
			array( 'ds-child-main' ),
			ds_child_asset_version( 'assets/css/woocommerce.css' )
		);
	}

	wp_enqueue_script(
		'ds-child-theme',
		DS_CHILD_URI . '/assets/js/theme.js',
		array(),
		ds_child_asset_version( 'assets/js/theme.js' ),
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
}
add_action( 'wp_enqueue_scripts', 'ds_child_enqueue_assets', 20 );

/**
 * Preload the two most used font files (body regular and bold, Arabic subset).
 *
 * @return void
 */
function ds_child_preload_fonts() {
	foreach ( array( '400', '700' ) as $weight ) {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( DS_CHILD_URI . '/assets/fonts/ibm-plex-sans-arabic-arabic-' . $weight . '-normal.woff2' )
		);
	}
}
add_action( 'wp_head', 'ds_child_preload_fonts', 2 );
