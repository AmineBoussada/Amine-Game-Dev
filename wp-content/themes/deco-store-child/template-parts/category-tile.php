<?php
/**
 * One category tile. Expects the `ds_term` query var (WP_Term).
 *
 * @package DecoStoreChild
 */

defined( 'ABSPATH' ) || exit;

$ds_term = get_query_var( 'ds_term' );
if ( ! $ds_term instanceof WP_Term ) {
	return;
}
$ds_thumb_id = (int) get_term_meta( $ds_term->term_id, 'thumbnail_id', true );
$ds_link     = get_term_link( $ds_term );
if ( is_wp_error( $ds_link ) ) {
	return;
}
?>
<li class="ds-tile">
	<a class="ds-tile__link" href="<?php echo esc_url( $ds_link ); ?>">
		<?php
		if ( $ds_thumb_id ) {
			echo wp_get_attachment_image(
				$ds_thumb_id,
				'ds-category-tile',
				false,
				array(
					'class'   => 'ds-tile__img',
					'alt'     => '',
					'loading' => 'lazy',
				)
			);
		}
		?>
		<span class="ds-tile__label">
			<span class="ds-tile__name"><?php echo esc_html( $ds_term->name ); ?></span>
			<span class="ds-tile__count">
				<?php
				/* translators: %s: number of products. */
				echo esc_html( sprintf( _n( '%s منتج', '%s منتجات', $ds_term->count, 'deco-store-child' ), number_format_i18n( $ds_term->count ) ) );
				?>
			</span>
		</span>
	</a>
</li>
