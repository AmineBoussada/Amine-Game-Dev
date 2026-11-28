<?php
/**
 * Homepage hero.
 *
 * @package DecoStoreChild
 */

defined( 'ABSPATH' ) || exit;

$ds_title = ds_child_get( 'hero_title' );
$ds_title = '' !== $ds_title ? $ds_title : get_bloginfo( 'name' );
$ds_text  = ds_child_get( 'hero_text' );
$ds_text  = '' !== $ds_text ? $ds_text : get_bloginfo( 'description' );
$ds_label = ds_child_get( 'hero_cta_label' );
$ds_url   = ds_child_get( 'hero_cta_url' );
if ( '' === $ds_url && ds_child_has_woocommerce() ) {
	$ds_url = wc_get_page_permalink( 'shop' );
}
$ds_image = (int) ds_child_get( 'hero_image' );
?>
<section class="ds-hero<?php echo $ds_image ? ' ds-hero--has-image' : ''; ?>">
	<?php
	if ( $ds_image ) {
		echo wp_get_attachment_image(
			$ds_image,
			'full',
			false,
			array(
				'class'         => 'ds-hero__image',
				'alt'           => '',
				'loading'       => 'eager',
				'fetchpriority' => 'high',
				'sizes'         => '100vw',
			)
		);
	}
	?>
	<div class="ds-container ds-hero__inner">
		<div class="ds-hero__content">
			<h1 class="ds-hero__title"><?php echo esc_html( $ds_title ); ?></h1>
			<?php if ( '' !== $ds_text ) : ?>
				<p class="ds-hero__text"><?php echo esc_html( $ds_text ); ?></p>
			<?php endif; ?>
			<?php if ( '' !== $ds_label && '' !== $ds_url ) : ?>
				<a class="ds-btn ds-btn--light ds-btn--lg" href="<?php echo esc_url( $ds_url ); ?>">
					<span><?php echo esc_html( $ds_label ); ?></span>
					<?php ds_child_the_icon( 'arrow-end' ); ?>
				</a>
			<?php endif; ?>
		</div>
	</div>
</section>
