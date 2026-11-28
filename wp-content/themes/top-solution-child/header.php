<?php
/**
 * Document head, opening body and site header.
 *
 * Replaces the parent header.php. The header is always rendered by this theme
 * (Elementor Free only; no Elementor Pro Theme Builder locations are used).
 *
 * @package TopSolutionChild
 */

defined( 'ABSPATH' ) || exit;

$ts_viewport = apply_filters( 'hello_elementor_viewport_content', 'width=device-width, initial-scale=1' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Parent theme hook.
$ts_skip_url = apply_filters( 'hello_elementor_skip_link_url', '#content' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Parent theme hook.
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="<?php echo esc_attr( $ts_viewport ); ?>">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="<?php echo esc_url( $ts_skip_url ); ?>"><?php esc_html_e( 'انتقل إلى المحتوى', 'top-solution-child' ); ?></a>

<?php
// Hello Elementor's own (free) setting can hide the header and footer, e.g. for canvas pages.
if ( function_exists( 'hello_elementor_display_header_footer' ) ? hello_elementor_display_header_footer() : true ) {
	get_template_part( 'template-parts/site-header' );
}
