<?php
/**
 * Homepage featured products: featured first, newest as fallback.
 *
 * @package DecoStoreChild
 */

defined( 'ABSPATH' ) || exit;

$ds_count = max( 2, (int) ds_child_get( 'featured_count' ) );
$ds_args  = array(
	'status'  => 'publish',
	'limit'   => $ds_count,
	'return'  => 'ids',
	'orderby' => 'date',
	'order'   => 'DESC',
);
$ds_ids   = wc_get_products( $ds_args + array( 'featured' => true ) );
$ds_atts  = 'visibility="featured"';
if ( empty( $ds_ids ) ) {
	$ds_ids  = wc_get_products( $ds_args );
	$ds_atts = 'orderby="date" order="DESC"';
}
if ( empty( $ds_ids ) ) {
	return;
}
$ds_title = ds_child_get( 'featured_title' );
?>
<section class="ds-section ds-section--surface" aria-labelledby="ds-home-featured">
	<div class="ds-container">
		<div class="ds-section__head">
			<h2 class="ds-section__title" id="ds-home-featured"><?php echo esc_html( '' !== $ds_title ? $ds_title : __( 'منتجات مختارة', 'deco-store-child' ) ); ?></h2>
			<a class="ds-link-more" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">
				<span><?php esc_html_e( 'عرض الكل', 'deco-store-child' ); ?></span>
				<?php ds_child_the_icon( 'arrow-end' ); ?>
			</a>
		</div>
		<?php
		echo do_shortcode( '[products limit="' . (int) $ds_count . '" columns="4" ' . $ds_atts . ']' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce shortcode output.
		?>
	</div>
</section>
