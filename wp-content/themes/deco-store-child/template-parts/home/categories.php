<?php
/**
 * Homepage categories: top-level product categories that have products.
 *
 * @package DecoStoreChild
 */

defined( 'ABSPATH' ) || exit;

$ds_terms = ds_child_get_top_categories( 8 );
if ( empty( $ds_terms ) ) {
	return;
}
?>
<section class="ds-section" aria-labelledby="ds-home-categories">
	<div class="ds-container">
		<div class="ds-section__head">
			<h2 class="ds-section__title" id="ds-home-categories"><?php esc_html_e( 'الأقسام', 'deco-store-child' ); ?></h2>
			<a class="ds-link-more" href="<?php echo esc_url( ds_child_categories_page_url() ); ?>">
				<span><?php esc_html_e( 'كل الأقسام', 'deco-store-child' ); ?></span>
				<?php ds_child_the_icon( 'arrow-end' ); ?>
			</a>
		</div>
		<ul class="ds-tiles">
			<?php
			foreach ( $ds_terms as $ds_term ) {
				set_query_var( 'ds_term', $ds_term );
				get_template_part( 'template-parts/category-tile' );
			}
			?>
		</ul>
	</div>
</section>
