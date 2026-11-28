<?php
/**
 * Theme setup: supports, menus, image sizes, small hygiene filters.
 *
 * @package DecoStoreChild
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register theme supports, menus and image sizes.
 *
 * The parent theme already registers the `menu-1` (header) and `menu-2`
 * (footer) locations; they are re-registered here only to give them Arabic
 * labels.
 *
 * @return void
 */
function ds_child_setup() {
	load_child_theme_textdomain( 'deco-store-child', DS_CHILD_DIR . '/languages' );

	add_theme_support(
		'custom-logo',
		array(
			'height'      => 80,
			'width'       => 240,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );

	register_nav_menus(
		array(
			'menu-1' => __( 'القائمة الرئيسية (الرأس والجوال)', 'deco-store-child' ),
			'menu-2' => __( 'قائمة التذييل', 'deco-store-child' ),
		)
	);

	add_image_size( 'ds-category-tile', 640, 480, true );

	// Block editor canvas matches the storefront typography and colours.
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/css/editor.css' );
}
add_action( 'after_setup_theme', 'ds_child_setup', 20 );

/**
 * Add body classes used by the stylesheet.
 *
 * @param string[] $classes Existing body classes.
 * @return string[]
 */
function ds_child_body_class( $classes ) {
	$classes[] = 'ds-theme';
	if ( '' !== ds_child_get( 'announcement' ) ) {
		$classes[] = 'ds-has-announcement';
	}
	return $classes;
}
add_filter( 'body_class', 'ds_child_body_class' );

/**
 * Remove the emoji detection script (not needed, saves a request).
 *
 * @return void
 */
function ds_child_remove_emoji() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
}
add_action( 'init', 'ds_child_remove_emoji' );

/**
 * Shorter, RTL-friendly excerpts.
 *
 * @return int
 */
function ds_child_excerpt_length() {
	return 28;
}
add_filter( 'excerpt_length', 'ds_child_excerpt_length' );

/**
 * Excerpt ellipsis.
 *
 * @return string
 */
function ds_child_excerpt_more() {
	return '…';
}
add_filter( 'excerpt_more', 'ds_child_excerpt_more' );

/**
 * Warn administrators if the site is not using an RTL locale.
 *
 * The first release is Arabic only; an LTR locale would show Arabic text
 * with a left-to-right layout.
 *
 * @return void
 */
function ds_child_rtl_notice() {
	if ( is_rtl() || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	echo '<div class="notice notice-warning"><p>' . esc_html__( 'قالب Déco Store مصمم للغة العربية (RTL). اضبط لغة الموقع على العربية من الإعدادات ← عام.', 'deco-store-child' ) . '</p></div>';
}
add_action( 'admin_notices', 'ds_child_rtl_notice' );
