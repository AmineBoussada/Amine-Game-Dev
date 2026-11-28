<?php
/**
 * Elementor Free compatibility.
 *
 * The theme targets Elementor FREE only. It uses no Elementor Pro widgets,
 * Theme Builder locations, WooCommerce Builder, Popup Builder or forms, and the
 * store works with Elementor deactivated (native templates are used).
 *
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

/**
 * Wrap content rendered by Elementor Free's "Full Width" and "Canvas" page
 * templates in a <main> landmark (skip-link target and accessibility).
 *
 * @return void
 */
function ts_child_elementor_main_open() {
	echo '<main id="content" class="ts-main ts-fullwidth">';
}

/**
 * Close the <main> landmark opened by ts_child_elementor_main_open().
 *
 * @return void
 */
function ts_child_elementor_main_close() {
	echo '</main>';
}
add_action( 'elementor/page_templates/header-footer/before_content', 'ts_child_elementor_main_open' );
add_action( 'elementor/page_templates/header-footer/after_content', 'ts_child_elementor_main_close' );
add_action( 'elementor/page_templates/canvas/before_content', 'ts_child_elementor_main_open' );
add_action( 'elementor/page_templates/canvas/after_content', 'ts_child_elementor_main_close' );

/**
 * Version of the brand defaults written to the Elementor kit. Bump to re-run.
 */
define( 'TS_CHILD_ELEMENTOR_KIT_VERSION', '1' );

/**
 * Elementor Free's factory-default system colours (used to detect untouched kits).
 *
 * @return string[]
 */
function ts_child_elementor_default_colors() {
	return array( '#6EC1E4', '#54595F', '#7A7A7A', '#61CE70' );
}

/**
 * Does a kit colour list still hold Elementor's factory defaults?
 *
 * @param mixed $colors The kit's `system_colors` setting.
 * @return bool
 */
function ts_child_elementor_colors_are_default( $colors ) {
	if ( empty( $colors ) || ! is_array( $colors ) ) {
		return true;
	}
	$values = array();
	foreach ( $colors as $color ) {
		$values[] = isset( $color['color'] ) ? strtoupper( $color['color'] ) : '';
	}
	return array_values( $values ) === ts_child_elementor_default_colors();
}

/**
 * Does a kit typography list still use Elementor's factory fonts (Roboto)?
 *
 * @param mixed $typography The kit's `system_typography` setting.
 * @return bool
 */
function ts_child_elementor_typography_is_default( $typography ) {
	if ( empty( $typography ) || ! is_array( $typography ) ) {
		return true;
	}
	foreach ( $typography as $item ) {
		$family = isset( $item['typography_font_family'] ) ? $item['typography_font_family'] : '';
		if ( '' !== $family && 0 !== strpos( $family, 'Roboto' ) ) {
			return false;
		}
	}
	return true;
}

/**
 * Write the brand palette, fonts and container width into the active
 * Elementor Free kit (Site Settings > Global Colors / Global Fonts / Layout).
 *
 * Runs once per TS_CHILD_ELEMENTOR_KIT_VERSION, only for administrators, and
 * only replaces values that are still Elementor's factory defaults, so choices
 * the owner already made in Site Settings are never overwritten.
 *
 * @return bool Whether the kit was changed.
 */
function ts_child_elementor_sync_kit() {
	if ( ! did_action( 'elementor/loaded' ) || ! current_user_can( 'manage_options' ) ) {
		return false;
	}
	if ( TS_CHILD_ELEMENTOR_KIT_VERSION === get_option( 'ts_child_elementor_kit_version' ) ) {
		return false;
	}
	$kit_id = (int) get_option( 'elementor_active_kit' );
	if ( ! $kit_id || ! get_post( $kit_id ) ) {
		return false;
	}

	$settings = get_post_meta( $kit_id, '_elementor_page_settings', true );
	$settings = is_array( $settings ) ? $settings : array();
	$changed  = false;

	if ( ts_child_elementor_colors_are_default( isset( $settings['system_colors'] ) ? $settings['system_colors'] : null ) ) {
		// Buttons use "accent" with white text, so accent is Moss (6.5:1), not Mustard.
		$settings['system_colors'] = array(
			array(
				'_id'   => 'primary',
				'title' => __( 'أساسي — طحلبي', 'top-solution-child' ),
				'color' => '#576238',
			),
			array(
				'_id'   => 'secondary',
				'title' => __( 'ثانوي — حبر', 'top-solution-child' ),
				'color' => '#1E2218',
			),
			array(
				'_id'   => 'text',
				'title' => __( 'النص', 'top-solution-child' ),
				'color' => '#565A48',
			),
			array(
				'_id'   => 'accent',
				'title' => __( 'الأزرار — طحلبي', 'top-solution-child' ),
				'color' => '#576238',
			),
		);
		$changed                   = true;
	}

	if ( empty( $settings['custom_colors'] ) ) {
		$settings['custom_colors'] = array(
			array(
				'_id'   => 'ts_mustard',
				'title' => __( 'خردلي (نص داكن فقط)', 'top-solution-child' ),
				'color' => '#FFD95D',
			),
			array(
				'_id'   => 'ts_eggshell',
				'title' => __( 'قشر البيض', 'top-solution-child' ),
				'color' => '#F0EADC',
			),
			array(
				'_id'   => 'ts_white',
				'title' => __( 'أبيض', 'top-solution-child' ),
				'color' => '#FFFFFF',
			),
		);
		$changed                   = true;
	}

	if ( ts_child_elementor_typography_is_default( isset( $settings['system_typography'] ) ? $settings['system_typography'] : null ) ) {
		$fonts                         = array(
			'primary'   => array( 'Noto Kufi Arabic', '700', __( 'العناوين', 'top-solution-child' ) ),
			'secondary' => array( 'Noto Kufi Arabic', '700', __( 'عناوين فرعية', 'top-solution-child' ) ),
			'text'      => array( 'IBM Plex Sans Arabic', '400', __( 'النص', 'top-solution-child' ) ),
			'accent'    => array( 'IBM Plex Sans Arabic', '700', __( 'الأزرار', 'top-solution-child' ) ),
		);
		$settings['system_typography'] = array();
		foreach ( $fonts as $id => $font ) {
			$settings['system_typography'][] = array(
				'_id'                    => $id,
				'title'                  => $font[2],
				'typography_typography'  => 'custom',
				'typography_font_family' => $font[0],
				'typography_font_weight' => $font[1],
			);
		}
		$changed = true;
	}

	if ( empty( $settings['container_width'] ) ) {
		$settings['container_width'] = array(
			'unit'  => 'px',
			'size'  => 1200,
			'sizes' => array(),
		);
		$changed                     = true;
	}

	if ( $changed ) {
		update_post_meta( $kit_id, '_elementor_page_settings', $settings );
		if ( isset( \Elementor\Plugin::$instance->files_manager ) ) {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
		}
	}
	update_option( 'ts_child_elementor_kit_version', TS_CHILD_ELEMENTOR_KIT_VERSION, false );
	return $changed;
}
add_action( 'admin_init', 'ts_child_elementor_sync_kit' );
