<?php
/**
 * Site search results: listings as cards, other content as short entries.
 *
 * @package Tassili_Immobilier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main id="main" class="site-main">
	<div class="page-head">
		<div class="container">
			<h1>
				<?php
				/* translators: %s: search query. */
				echo esc_html( sprintf( __( 'Search results for “%s”', 'tassili-immobilier' ), get_search_query( false ) ) );
				?>
			</h1>
			<p class="result-count" role="status"><?php echo esc_html( tassili_count_label( (int) $wp_query->found_posts ) ); ?></p>
		</div>
	</div>
	<div class="container page-wrap">
		<?php if ( have_posts() ) : ?>
			<div class="card-grid">
				<?php
				while ( have_posts() ) :
					the_post();
					if ( 'property' === get_post_type() ) {
						get_template_part( 'template-parts/card', 'property' );
					} else {
						get_template_part( 'template-parts/content', 'post' );
					}
				endwhile;
				?>
			</div>
			<?php tassili_the_pagination(); ?>
		<?php else : ?>
			<?php get_template_part( 'template-parts/content', 'none' ); ?>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
