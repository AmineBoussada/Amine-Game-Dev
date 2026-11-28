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
 * 1 = first Moss/Mustard palette, 2 = Everglade/Whirlpool/Aqua Cove/Cornfield/Sisal Beige.
 */
define( 'TS_CHILD_ELEMENTOR_KIT_VERSION', '2' );

/**
 * Colour lists that count as "not chosen by the owner": Elementor Free's
 * factory defaults and the theme's own earlier palettes. A kit that still
 * holds one of these is safe to update; anything else is left untouched.
 *
 * @return array<string,string[]> Map of label => upper-case hex list (system colours, in order).
 */
function ts_child_elementor_replaceable_color_sets() {
	return array(
		'elementor-default' => array( '#6EC1E4', '#54595F', '#7A7A7A', '#61CE70' ),
		'theme-v1'          => array( '#576238', '#1E2218', '#565A48', '#576238' ),
	);
}

/**
 * Custom colours written by earlier theme versions (replaceable).
 *
 * @return string[][]
 */
function ts_child_elementor_replaceable_custom_sets() {
	return array(
		array( '#FFD95D', '#F0EADC', '#FFFFFF' ),
	);
}

/**
 * Extract an ordered, upper-case list of hex colours from a kit colour repeater.
 *
 * @param mixed $items Kit repeater value.
 * @return string[]
 */
function ts_child_elementor_color_list( $items ) {
	$values = array();
	if ( is_array( $items ) ) {
		foreach ( $items as $item ) {
			$values[] = isset( $item['color'] ) ? strtoupper( $item['color'] ) : '';
		}
	}
	return $values;
}

/**
 * Does a kit's system colour list still hold a replaceable set?
 *
 * @param mixed $colors The kit's `system_colors` setting.
 * @return bool
 */
function ts_child_elementor_colors_are_default( $colors ) {
	if ( empty( $colors ) || ! is_array( $colors ) ) {
		return true;
	}
	$list = ts_child_elementor_color_list( $colors );
	foreach ( ts_child_elementor_replaceable_color_sets() as $set ) {
		if ( $list === $set ) {
			return true;
		}
	}
	return false;
}

/**
 * Do the kit's custom colours still hold a replaceable (or no) set?
 *
 * @param mixed $colors The kit's `custom_colors` setting.
 * @return bool
 */
function ts_child_elementor_custom_colors_are_default( $colors ) {
	if ( empty( $colors ) || ! is_array( $colors ) ) {
		return true;
	}
	$list = ts_child_elementor_color_list( $colors );
	foreach ( ts_child_elementor_replaceable_custom_sets() as $set ) {
		if ( $list === $set ) {
			return true;
		}
	}
	return false;
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
 * only replaces values that are still Elementor's factory defaults or this
 * theme's own earlier palette, so choices the owner made in Site Settings are
 * never overwritten.
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
		// Elementor buttons use "accent" with white text, so accent is Everglade (10.5:1), not Cornfield.
		$settings['system_colors'] = array(
			array(
				'_id'   => 'primary',
				'title' => __( 'أساسي — إيفرغليد', 'top-solution-child' ),
				'color' => '#014749',
			),
			array(
				'_id'   => 'secondary',
				'title' => __( 'ثانوي — ويربول', 'top-solution-child' ),
				'color' => '#395C7A',
			),
			array(
				'_id'   => 'text',
				'title' => __( 'النص', 'top-solution-child' ),
				'color' => '#42585A',
			),
			array(
				'_id'   => 'accent',
				'title' => __( 'الأزرار — إيفرغليد', 'top-solution-child' ),
				'color' => '#014749',
			),
		);
		$changed                   = true;
	}

	if ( ts_child_elementor_custom_colors_are_default( isset( $settings['custom_colors'] ) ? $settings['custom_colors'] : null ) ) {
		$settings['custom_colors'] = array(
			array(
				'_id'   => 'ts_cornfield',
				'title' => __( 'كورنفيلد (نص داكن فقط)', 'top-solution-child' ),
				'color' => '#C88928',
			),
			array(
				'_id'   => 'ts_aqua',
				'title' => __( 'أكوا كوف (للزخرفة فقط)', 'top-solution-child' ),
				'color' => '#53978A',
			),
			array(
				'_id'   => 'ts_sisal',
				'title' => __( 'سيزال بيج', 'top-solution-child' ),
				'color' => '#C0976B',
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
