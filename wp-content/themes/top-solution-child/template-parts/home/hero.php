<?php
/**
 * Homepage hero.
 *
 * @package TopSolutionChild
 */

defined( 'ABSPATH' ) || exit;

$ts_title = ts_child_get( 'hero_title' );
$ts_title = '' !== $ts_title ? $ts_title : get_bloginfo( 'name' );
$ts_text  = ts_child_get( 'hero_text' );
$ts_text  = '' !== $ts_text ? $ts_text : get_bloginfo( 'description' );
$ts_label = ts_child_get( 'hero_cta_label' );
$ts_url   = ts_child_get( 'hero_cta_url' );
if ( '' === $ts_url && ts_child_has_woocommerce() ) {
	$ts_url = wc_get_page_permalink( 'shop' );
}
$ts_image = (int) ts_child_get( 'hero_image' );
?>
<section class="ts-hero<?php echo $ts_image ? ' ts-hero--has-image' : ''; ?>">
	<?php
	if ( $ts_image ) {
		echo wp_get_attachment_image(
			$ts_image,
			'full',
			false,
			array(
				'class'         => 'ts-hero__image',
				'alt'           => '',
				'loading'       => 'eager',
				'fetchpriority' => 'high',
				'sizes'         => '100vw',
			)
		);
	}
	?>
	<div class="ts-container ts-hero__inner">
		<div class="ts-hero__content">
			<h1 class="ts-hero__title"><?php echo esc_html( $ts_title ); ?></h1>
			<?php if ( '' !== $ts_text ) : ?>
				<p class="ts-hero__text"><?php echo esc_html( $ts_text ); ?></p>
			<?php endif; ?>
			<?php if ( '' !== $ts_label && '' !== $ts_url ) : ?>
				<a class="ts-btn ts-btn--light ts-btn--lg" href="<?php echo esc_url( $ts_url ); ?>">
					<span><?php echo esc_html( $ts_label ); ?></span>
					<?php ts_child_the_icon( 'arrow-end' ); ?>
				</a>
			<?php endif; ?>
		</div>
	</div>
</section>
