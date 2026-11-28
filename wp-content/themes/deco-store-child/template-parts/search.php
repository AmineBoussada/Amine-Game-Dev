<?php
/**
 * Search results: products render as WooCommerce cards, everything else as
 * generic cards.
 *
 * @package DecoStoreChild
 */

defined( 'ABSPATH' ) || exit;

$ds_is_products = ds_child_has_woocommerce() && 'product' === get_query_var( 'post_type' );
?>
<main id="content" class="ds-main ds-page ds-page--search">
	<header class="ds-page__header">
		<div class="ds-container">
			<?php ds_child_breadcrumbs(); ?>
			<h1 class="ds-page__title">
				<?php
				/* translators: %s: search query. */
				echo esc_html( sprintf( __( 'نتائج البحث عن: %s', 'deco-store-child' ), get_search_query() ) );
				?>
			</h1>
			<div class="ds-page__search"><?php get_search_form(); ?></div>
		</div>
	</header>

	<div class="ds-container ds-section ds-section--tight woocommerce">
		<?php if ( have_posts() ) : ?>
			<?php if ( $ds_is_products ) : ?>
				<ul class="products columns-4">
					<?php
					while ( have_posts() ) {
						the_post();
						wc_get_template_part( 'content', 'product' );
					}
					?>
				</ul>
			<?php else : ?>
				<div class="ds-post-grid">
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
			<div class="ds-empty">
				<p><?php esc_html_e( 'لم نجد نتائج مطابقة. جرّب كلمات أخرى أو تصفّح الأقسام.', 'deco-store-child' ); ?></p>
				<?php if ( ds_child_has_woocommerce() ) : ?>
					<a class="ds-btn" href="<?php echo esc_url( ds_child_categories_page_url() ); ?>"><?php esc_html_e( 'تصفّح الأقسام', 'deco-store-child' ); ?></a>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</main>
