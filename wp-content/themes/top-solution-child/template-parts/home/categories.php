<?php
/**
 * Homepage categories: top-level product categories that have products.
 *
 * @package TopSolutionChild
 */

defined( 'ABSPATH' ) || exit;

$ts_terms = ts_child_get_top_categories( 8 );
if ( empty( $ts_terms ) ) {
	return;
}
?>
<section class="ts-section" aria-labelledby="ts-home-categories">
	<div class="ts-container">
		<div class="ts-section__head">
			<h2 class="ts-section__title" id="ts-home-categories"><?php esc_html_e( 'الأقسام', 'top-solution-child' ); ?></h2>
			<a class="ts-link-more" href="<?php echo esc_url( ts_child_categories_page_url() ); ?>">
				<span><?php esc_html_e( 'كل الأقسام', 'top-solution-child' ); ?></span>
				<?php ts_child_the_icon( 'arrow-end' ); ?>
			</a>
		</div>
		<ul class="ts-tiles">
			<?php
			foreach ( $ts_terms as $ts_term ) {
				set_query_var( 'ts_term', $ts_term );
				get_template_part( 'template-parts/category-tile' );
			}
			?>
		</ul>
	</div>
</section>
