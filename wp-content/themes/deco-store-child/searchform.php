<?php
/**
 * Search form (products only when WooCommerce is active).
 *
 * @package DecoStoreChild
 */

defined( 'ABSPATH' ) || exit;

$ds_uid = wp_unique_id( 'ds-search-' );
?>
<form role="search" method="get" class="ds-search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr( $ds_uid ); ?>"><?php esc_html_e( 'ابحث عن:', 'deco-store-child' ); ?></label>
	<input type="search" id="<?php echo esc_attr( $ds_uid ); ?>" class="ds-search__input" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'ابحث عن منتج…', 'deco-store-child' ); ?>" autocomplete="off">
	<?php if ( ds_child_has_woocommerce() ) : ?>
		<input type="hidden" name="post_type" value="product">
	<?php endif; ?>
	<button type="submit" class="ds-search__submit">
		<?php ds_child_the_icon( 'search' ); ?>
		<span class="screen-reader-text"><?php esc_html_e( 'بحث', 'deco-store-child' ); ?></span>
	</button>
</form>
