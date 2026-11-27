<?php
/**
 * Homepage featured products: featured first, newest as fallback.
 *
 * @package TopSolutionChild
 */

defined( 'ABSPATH' ) || exit;

$ts_count = max( 2, (int) ts_child_get( 'featured_count' ) );
$ts_args  = array(
	'status'  => 'publish',
	'limit'   => $ts_count,
	'return'  => 'ids',
	'orderby' => 'date',
	'order'   => 'DESC',
);
$ts_ids   = wc_get_products( $ts_args + array( 'featured' => true ) );
$ts_atts  = 'visibility="featured"';
if ( empty( $ts_ids ) ) {
	$ts_ids  = wc_get_products( $ts_args );
	$ts_atts = 'orderby="date" order="DESC"';
}
if ( empty( $ts_ids ) ) {
	return;
}
$ts_title = ts_child_get( 'featured_title' );
?>
<section class="ts-section ts-section--surface" aria-labelledby="ts-home-featured">
	<div class="ts-container">
		<div class="ts-section__head">
			<h2 class="ts-section__title" id="ts-home-featured"><?php echo esc_html( '' !== $ts_title ? $ts_title : __( 'منتجات مختارة', 'top-solution-child' ) ); ?></h2>
			<a class="ts-link-more" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">
				<span><?php esc_html_e( 'عرض الكل', 'top-solution-child' ); ?></span>
				<?php ts_child_the_icon( 'arrow-end' ); ?>
			</a>
		</div>
		<?php
		echo do_shortcode( '[products limit="' . (int) $ts_count . '" columns="4" ' . $ts_atts . ']' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce shortcode output.
		?>
	</div>
</section>
