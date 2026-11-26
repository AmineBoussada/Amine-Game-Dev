<?php
/**
 * Not found.
 *
 * @package Tassili_Immobilier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main id="main" class="site-main">
	<div class="container notfound">
		<?php tassili_the_icon( 'search', 'ic-lg' ); ?>
		<h1><?php esc_html_e( 'This page cannot be found', 'tassili-immobilier' ); ?></h1>
		<p class="muted"><?php esc_html_e( 'The link may be broken, or the property may have been sold or let.', 'tassili-immobilier' ); ?></p>
		<?php get_search_form(); ?>
		<div class="cta-actions">
			<a class="btn btn-primary" href="<?php echo esc_url( get_post_type_archive_link( 'property' ) ); ?>"><?php esc_html_e( 'All properties', 'tassili-immobilier' ); ?></a>
			<a class="btn btn-ghost" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Back to the home page', 'tassili-immobilier' ); ?></a>
		</div>
	</div>
</main>
<?php
get_footer();
