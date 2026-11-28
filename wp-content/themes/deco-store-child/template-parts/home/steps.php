<?php
/**
 * Homepage "how it works" steps (text from the Customizer).
 *
 * @package DecoStoreChild
 */

defined( 'ABSPATH' ) || exit;

$ds_steps = array_values(
	array_filter(
		array( ds_child_get( 'step_1' ), ds_child_get( 'step_2' ), ds_child_get( 'step_3' ) ),
		static function ( $step ) {
			return '' !== $step;
		}
	)
);
if ( empty( $ds_steps ) ) {
	return;
}
?>
<section class="ds-section" aria-labelledby="ds-home-steps">
	<div class="ds-container">
		<h2 class="ds-section__title" id="ds-home-steps"><?php esc_html_e( 'كيف تطلب؟', 'deco-store-child' ); ?></h2>
		<ol class="ds-steps">
			<?php foreach ( $ds_steps as $ds_index => $ds_step ) : ?>
				<li class="ds-steps__item">
					<span class="ds-steps__num"><?php echo esc_html( number_format_i18n( $ds_index + 1 ) ); ?></span>
					<span class="ds-steps__text"><?php echo esc_html( $ds_step ); ?></span>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>
