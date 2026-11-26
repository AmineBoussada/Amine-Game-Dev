<?php
/**
 * Theme setup: supports, menus, image sizes, head tweaks.
 *
 * @package Tassili_Immobilier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register theme features.
 */
function tassili_setup() {
	load_theme_textdomain( 'tassili-immobilier', TASSILI_DIR . 'languages' );

	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' )
	);
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 68,
			'width'       => 220,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	add_editor_style( 'assets/css/editor.css' );

	register_nav_menus(
		array(
			'primary' => __( 'Primary menu (header and mobile drawer)', 'tassili-immobilier' ),
			'footer'  => __( 'Footer links', 'tassili-immobilier' ),
		)
	);

	add_image_size( 'tassili-card', 800, 600, true );
	add_image_size( 'tassili-gallery', 1400, 933, true );
	add_image_size( 'tassili-thumb', 280, 210, true );
	add_image_size( 'tassili-avatar', 160, 160, true );
}
add_action( 'after_setup_theme', 'tassili_setup' );

/**
 * Content width for embeds.
 */
function tassili_content_width() {
	$GLOBALS['content_width'] = 1120;
}
add_action( 'after_setup_theme', 'tassili_content_width', 0 );

/**
 * Body classes: language and direction.
 *
 * @param string[] $classes Classes.
 * @return string[]
 */
function tassili_body_classes( $classes ) {
	$classes[] = 'tassili-lang-' . tassili_lang();
	if ( is_singular( 'property' ) ) {
		$classes[] = 'is-property-page';
	}
	return $classes;
}
add_filter( 'body_class', 'tassili_body_classes' );

/**
 * Head: viewport colour and the early theme script.
 *
 * The inline script applies the saved light/dark choice before first paint, so the page
 * never flashes the wrong theme.
 */
function tassili_head_extras() {
	echo '<meta name="theme-color" content="#023047" />' . "\n";
	echo "<script>document.documentElement.classList.add('js');try{var t=localStorage.getItem('tassili-theme');if(t==='dark'||t==='light'){document.documentElement.setAttribute('data-theme',t);}}catch(e){}</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static, no user data.
}
add_action( 'wp_head', 'tassili_head_extras', 2 );

/**
 * Skip link and site-wide anchors rendered right after <body>.
 */
function tassili_skip_link() {
	printf(
		'<a class="skip-link" href="#main">%s</a>' . "\n",
		esc_html__( 'Skip to content', 'tassili-immobilier' )
	);
}
add_action( 'wp_body_open', 'tassili_skip_link', 5 );

/**
 * Suggested privacy-policy text, shown under Settings > Privacy.
 */
function tassili_privacy_policy_content() {
	if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
		return;
	}
	$content = '<p class="privacy-policy-tutorial">' . esc_html__( 'Suggested text for sites using the Tassili Immobilier theme.', 'tassili-immobilier' ) . '</p>'
		. '<h2>' . esc_html__( 'Contact and visit forms', 'tassili-immobilier' ) . '</h2>'
		. '<p>' . esc_html__( 'When you send a message or request a visit, we store your name, phone number, optional e-mail address and message in our back office so that an adviser can answer you. We keep them only as long as needed to handle your request.', 'tassili-immobilier' ) . '</p>'
		. '<h2>' . esc_html__( 'Preferences stored on your device', 'tassili-immobilier' ) . '</h2>'
		. '<p>' . esc_html__( 'The site remembers your language in a cookie and your favourite properties and light or dark theme in your browser storage. This information never leaves your device.', 'tassili-immobilier' ) . '</p>';
	wp_add_privacy_policy_content( 'Tassili Immobilier', wp_kses_post( wpautop( $content, false ) ) );
}
add_action( 'admin_init', 'tassili_privacy_policy_content' );

/**
 * Flush rewrite rules and seed the default terms when the theme is activated.
 */
function tassili_after_switch_theme() {
	tassili_seed_terms();
	tassili_schedule_rewrite_flush();
}
add_action( 'after_switch_theme', 'tassili_after_switch_theme' );

/**
 * Ask for the rewrite rules to be rebuilt on the next request.
 *
 * Doing it at once can miss rules: WordPress only adds a post type's URLs at registration
 * time, and only when permalinks are already enabled.
 */
function tassili_schedule_rewrite_flush() {
	update_option( 'tassili_flush_rewrite', 1, false );
}

/**
 * Rebuild the rewrite rules when asked to, after every post type has registered.
 */
function tassili_maybe_flush_rewrite() {
	if ( get_option( 'tassili_flush_rewrite' ) ) {
		delete_option( 'tassili_flush_rewrite' );
		flush_rewrite_rules( false );
	}
}
add_action( 'init', 'tassili_maybe_flush_rewrite', 99 );

/**
 * Clean up rewrite rules when the theme is deactivated.
 */
function tassili_switch_theme_cleanup() {
	flush_rewrite_rules();
}
add_action( 'switch_theme', 'tassili_switch_theme_cleanup' );

/**
 * Widget area shown at the bottom of the footer.
 */
function tassili_register_sidebars() {
	register_sidebar(
		array(
			'name'          => __( 'Footer widgets', 'tassili-immobilier' ),
			'id'            => 'footer-widgets',
			'description'   => __( 'Shown at the bottom of every page, above the copyright line.', 'tassili-immobilier' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h3 class="widget-title">',
			'after_title'   => '</h3>',
		)
	);
}
add_action( 'widgets_init', 'tassili_register_sidebars' );
