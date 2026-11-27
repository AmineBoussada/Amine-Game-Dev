<?php
/**
 * Search form (products only when WooCommerce is active).
 *
 * @package TopSolutionChild
 */

defined( 'ABSPATH' ) || exit;

$ts_uid = wp_unique_id( 'ts-search-' );
?>
<form role="search" method="get" class="ts-search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr( $ts_uid ); ?>"><?php esc_html_e( 'ابحث عن:', 'top-solution-child' ); ?></label>
	<input type="search" id="<?php echo esc_attr( $ts_uid ); ?>" class="ts-search__input" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'ابحث عن منتج…', 'top-solution-child' ); ?>" autocomplete="off">
	<?php if ( ts_child_has_woocommerce() ) : ?>
		<input type="hidden" name="post_type" value="product">
	<?php endif; ?>
	<button type="submit" class="ts-search__submit">
		<?php ts_child_the_icon( 'search' ); ?>
		<span class="screen-reader-text"><?php esc_html_e( 'بحث', 'top-solution-child' ); ?></span>
	</button>
</form>
