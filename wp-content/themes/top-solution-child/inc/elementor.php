<?php
/**
 * Elementor compatibility.
 *
 * - Elementor Pro theme-builder header/footer locations are honoured in
 *   header.php / footer.php.
 * - The design tokens and fonts are also loaded inside the editor and preview
 *   so what is edited matches the storefront.
 * - Google Fonts are not loaded for Elementor typography; the theme self-hosts
 *   its fonts (privacy + performance).
 *
 * @package TopSolutionChild
 */

defined( 'ABSPATH' ) || exit;

/**
 * Load the main stylesheet in the Elementor editor and preview.
 *
 * @return void
 */
function ts_child_elementor_editor_styles() {
	wp_enqueue_style(
		'ts-child-main',
		TS_CHILD_URI . '/assets/css/main.css',
		array(),
		ts_child_asset_version( 'assets/css/main.css' )
	);
}
add_action( 'elementor/editor/after_enqueue_styles', 'ts_child_elementor_editor_styles' );
add_action( 'elementor/preview/enqueue_styles', 'ts_child_elementor_editor_styles' );

/**
 * Do not print Google Fonts links for Elementor typography.
 *
 * @return bool
 */
function ts_child_elementor_no_google_fonts() {
	return false;
}
add_filter( 'elementor/frontend/print_google_fonts', 'ts_child_elementor_no_google_fonts' );
