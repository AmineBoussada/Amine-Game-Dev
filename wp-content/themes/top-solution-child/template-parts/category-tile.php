<?php
/**
 * One category tile. Expects the `ts_term` query var (WP_Term).
 *
 * @package TopSolutionChild
 */

defined( 'ABSPATH' ) || exit;

$ts_term = get_query_var( 'ts_term' );
if ( ! $ts_term instanceof WP_Term ) {
	return;
}
$ts_thumb_id = (int) get_term_meta( $ts_term->term_id, 'thumbnail_id', true );
$ts_link     = get_term_link( $ts_term );
if ( is_wp_error( $ts_link ) ) {
	return;
}
?>
<li class="ts-tile">
	<a class="ts-tile__link" href="<?php echo esc_url( $ts_link ); ?>">
		<?php
		if ( $ts_thumb_id ) {
			echo wp_get_attachment_image(
				$ts_thumb_id,
				'ts-category-tile',
				false,
				array(
					'class'   => 'ts-tile__img',
					'alt'     => '',
					'loading' => 'lazy',
				)
			);
		}
		?>
		<span class="ts-tile__label">
			<span class="ts-tile__name"><?php echo esc_html( $ts_term->name ); ?></span>
			<span class="ts-tile__count">
				<?php
				/* translators: %s: number of products. */
				echo esc_html( sprintf( _n( '%s منتج', '%s منتجات', $ts_term->count, 'top-solution-child' ), number_format_i18n( $ts_term->count ) ) );
				?>
			</span>
		</span>
	</a>
</li>
