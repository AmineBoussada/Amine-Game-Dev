<?php
/**
 * Built-in homepage: hero, trust bar, categories, featured products, steps.
 * Each section hides itself when it has nothing to show.
 *
 * @package TopSolutionChild
 */

defined( 'ABSPATH' ) || exit;
?>
<main id="content" class="ts-main ts-home">
	<?php
	get_template_part( 'template-parts/home/hero' );
	?>
	<section class="ts-section ts-section--tight" aria-label="<?php esc_attr_e( 'مزايا التسوق', 'top-solution-child' ); ?>">
		<div class="ts-container"><?php get_template_part( 'template-parts/trust-bar' ); ?></div>
	</section>
	<?php
	if ( ts_child_has_woocommerce() ) {
		if ( ts_child_get( 'show_categories' ) ) {
			get_template_part( 'template-parts/home/categories' );
		}
		if ( ts_child_get( 'show_featured' ) ) {
			get_template_part( 'template-parts/home/featured' );
		}
	}
	if ( ts_child_get( 'show_steps' ) ) {
		get_template_part( 'template-parts/home/steps' );
	}
	?>
</main>
