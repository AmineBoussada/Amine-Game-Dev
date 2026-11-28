<?php
/**
 * Built-in homepage: hero, trust bar, categories, featured products, steps.
 * Each section hides itself when it has nothing to show.
 *
 * @package DecoStoreChild
 */

defined( 'ABSPATH' ) || exit;
?>
<main id="content" class="ds-main ds-home">
	<?php
	get_template_part( 'template-parts/home/hero' );
	?>
	<section class="ds-section ds-section--tight" aria-label="<?php esc_attr_e( 'مزايا التسوق', 'deco-store-child' ); ?>">
		<div class="ds-container"><?php get_template_part( 'template-parts/trust-bar' ); ?></div>
	</section>
	<?php
	if ( ds_child_has_woocommerce() ) {
		if ( ds_child_get( 'show_categories' ) ) {
			get_template_part( 'template-parts/home/categories' );
		}
		if ( ds_child_get( 'show_featured' ) ) {
			get_template_part( 'template-parts/home/featured' );
		}
	}
	if ( ds_child_get( 'show_steps' ) ) {
		get_template_part( 'template-parts/home/steps' );
	}
	?>
</main>
