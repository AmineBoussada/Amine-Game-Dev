<?php
/**
 * Template Name: Favourites
 * Template Post Type: page
 *
 * Favourites live in the visitor's browser. The script fetches the cards from the REST API.
 *
 * @package Tassili_Immobilier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

if ( tassili_render_builder_page() ) {
	get_footer();
	return;
}
?>
<main id="main" class="site-main">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<div class="page-head">
			<div class="container">
				<?php
				tassili_the_breadcrumb(
					array(
						array(
							'label' => __( 'Home', 'tassili-immobilier' ),
							'url'   => home_url( '/' ),
						),
						array( 'label' => get_the_title() ),
					)
				);
				?>
				<h1><?php the_title(); ?></h1>
				<p class="result-count" id="fav-count" role="status" aria-live="polite"></p>
			</div>
		</div>
		<section class="section" style="padding-top:var(--s-6)">
			<div class="container" id="favorites" data-favorites>
				<div class="card-grid" id="favorites-grid" hidden></div>
				<div class="empty" id="favorites-empty">
					<?php tassili_the_icon( 'heart' ); ?>
					<h2><?php esc_html_e( 'No favourites yet', 'tassili-immobilier' ); ?></h2>
					<p><?php esc_html_e( 'Tap the heart on a property to find it here, on this device.', 'tassili-immobilier' ); ?></p>
					<a class="btn btn-primary" href="<?php echo esc_url( get_post_type_archive_link( 'property' ) ); ?>"><?php esc_html_e( 'All properties', 'tassili-immobilier' ); ?></a>
				</div>
				<noscript><p><?php esc_html_e( 'Favourites need JavaScript to be displayed.', 'tassili-immobilier' ); ?></p></noscript>
			</div>
		</section>
	<?php endwhile; ?>
</main>
<?php
get_footer();
