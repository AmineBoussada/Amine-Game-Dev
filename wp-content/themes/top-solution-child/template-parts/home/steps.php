<?php
/**
 * Homepage "how it works" steps (text from the Customizer).
 *
 * @package TopSolutionChild
 */

defined( 'ABSPATH' ) || exit;

$ts_steps = array_values(
	array_filter(
		array( ts_child_get( 'step_1' ), ts_child_get( 'step_2' ), ts_child_get( 'step_3' ) ),
		static function ( $step ) {
			return '' !== $step;
		}
	)
);
if ( empty( $ts_steps ) ) {
	return;
}
?>
<section class="ts-section" aria-labelledby="ts-home-steps">
	<div class="ts-container">
		<h2 class="ts-section__title" id="ts-home-steps"><?php esc_html_e( 'كيف تطلب؟', 'top-solution-child' ); ?></h2>
		<ol class="ts-steps">
			<?php foreach ( $ts_steps as $ts_index => $ts_step ) : ?>
				<li class="ts-steps__item">
					<span class="ts-steps__num"><?php echo esc_html( number_format_i18n( $ts_index + 1 ) ); ?></span>
					<span class="ts-steps__text"><?php echo esc_html( $ts_step ); ?></span>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>
