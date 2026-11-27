<?php
/**
 * Search results: products render as WooCommerce cards, everything else as
 * generic cards.
 *
 * @package TopSolutionChild
 */

defined( 'ABSPATH' ) || exit;

$ts_is_products = ts_child_has_woocommerce() && 'product' === get_query_var( 'post_type' );
?>
<main id="content" class="ts-main ts-page ts-page--search">
	<header class="ts-page__header">
		<div class="ts-container">
			<?php ts_child_breadcrumbs(); ?>
			<h1 class="ts-page__title">
				<?php
				/* translators: %s: search query. */
				echo esc_html( sprintf( __( 'نتائج البحث عن: %s', 'top-solution-child' ), get_search_query() ) );
				?>
			</h1>
			<div class="ts-page__search"><?php get_search_form(); ?></div>
		</div>
	</header>

	<div class="ts-container ts-section ts-section--tight woocommerce">
		<?php if ( have_posts() ) : ?>
			<?php if ( $ts_is_products ) : ?>
				<ul class="products columns-4">
					<?php
					while ( have_posts() ) {
						the_post();
						wc_get_template_part( 'content', 'product' );
					}
					?>
				</ul>
			<?php else : ?>
				<div class="ts-post-grid">
					<?php
					while ( have_posts() ) {
						the_post();
						get_template_part( 'template-parts/post-card' );
					}
					?>
				</div>
			<?php endif; ?>
			<?php the_posts_pagination( array( 'mid_size' => 1 ) ); ?>
		<?php else : ?>
			<div class="ts-empty">
				<p><?php esc_html_e( 'لم نجد نتائج مطابقة. جرّب كلمات أخرى أو تصفّح الأقسام.', 'top-solution-child' ); ?></p>
				<?php if ( ts_child_has_woocommerce() ) : ?>
					<a class="ts-btn" href="<?php echo esc_url( ts_child_categories_page_url() ); ?>"><?php esc_html_e( 'تصفّح الأقسام', 'top-solution-child' ); ?></a>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</main>
