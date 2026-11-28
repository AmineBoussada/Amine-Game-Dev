<?php
/**
 * Site footer.
 *
 * @package DecoStoreChild
 */

defined( 'ABSPATH' ) || exit;

$ds_about     = ds_child_get( 'footer_about' );
$ds_about     = '' !== $ds_about ? $ds_about : get_bloginfo( 'description' );
$ds_copyright = ds_child_get( 'footer_copyright' );
if ( '' === $ds_copyright ) {
	/* translators: 1: year, 2: site name. */
	$ds_copyright = sprintf( __( '© %1$s %2$s. جميع الحقوق محفوظة.', 'deco-store-child' ), wp_date( 'Y' ), get_bloginfo( 'name' ) );
}
$ds_phone_url   = ds_child_phone_url();
$ds_wa_url      = ds_child_whatsapp_url();
$ds_email       = ds_child_get( 'email' );
$ds_address     = ds_child_get( 'address' );
$ds_hours       = ds_child_get( 'hours' );
$ds_has_contact = '' !== $ds_phone_url || '' !== $ds_wa_url || '' !== $ds_email || '' !== $ds_address || '' !== $ds_hours;
?>
<footer class="ds-footer" id="site-footer">
	<div class="ds-container ds-footer__grid">
		<div class="ds-footer__col ds-footer__brand">
			<?php ds_child_the_logo( 'ds-logo--footer' ); ?>
			<?php if ( '' !== $ds_about ) : ?>
				<p class="ds-footer__about"><?php echo esc_html( $ds_about ); ?></p>
			<?php endif; ?>
		</div>

		<?php if ( has_nav_menu( 'menu-2' ) ) : ?>
			<nav class="ds-footer__col" aria-label="<?php esc_attr_e( 'روابط التذييل', 'deco-store-child' ); ?>">
				<h2 class="ds-footer__title"><?php esc_html_e( 'روابط', 'deco-store-child' ); ?></h2>
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'menu-2',
						'container'      => false,
						'menu_class'     => 'ds-footer__list',
						'depth'          => 1,
					)
				);
				?>
			</nav>
		<?php endif; ?>

		<?php if ( $ds_has_contact ) : ?>
			<div class="ds-footer__col">
				<h2 class="ds-footer__title"><?php esc_html_e( 'تواصل معنا', 'deco-store-child' ); ?></h2>
				<ul class="ds-footer__list ds-footer__contact">
					<?php if ( '' !== $ds_phone_url ) : ?>
						<li><?php ds_child_the_icon( 'phone' ); ?><a href="<?php echo esc_url( $ds_phone_url, array( 'tel' ) ); ?>" dir="ltr"><?php echo esc_html( ds_child_get( 'phone' ) ); ?></a></li>
					<?php endif; ?>
					<?php if ( '' !== $ds_wa_url ) : ?>
						<li><?php ds_child_the_icon( 'whatsapp' ); ?><a href="<?php echo esc_url( $ds_wa_url ); ?>" rel="noopener"><?php esc_html_e( 'واتساب', 'deco-store-child' ); ?></a></li>
					<?php endif; ?>
					<?php if ( '' !== $ds_email ) : ?>
						<li><?php ds_child_the_icon( 'mail' ); ?><a href="<?php echo esc_url( 'mailto:' . antispambot( $ds_email ), array( 'mailto' ) ); ?>" dir="ltr"><?php echo esc_html( $ds_email ); ?></a></li>
					<?php endif; ?>
					<?php if ( '' !== $ds_address ) : ?>
						<li><?php ds_child_the_icon( 'pin' ); ?><span><?php echo esc_html( $ds_address ); ?></span></li>
					<?php endif; ?>
					<?php if ( '' !== $ds_hours ) : ?>
						<li><?php ds_child_the_icon( 'clock' ); ?><span><?php echo esc_html( $ds_hours ); ?></span></li>
					<?php endif; ?>
				</ul>
			</div>
		<?php endif; ?>
	</div>

	<div class="ds-footer__bottom">
		<div class="ds-container ds-footer__bottom-inner">
			<p><?php echo esc_html( $ds_copyright ); ?></p>
			<p class="ds-footer__cod"><?php ds_child_the_icon( 'wallet' ); ?><?php esc_html_e( 'الدفع عند الاستلام', 'deco-store-child' ); ?></p>
		</div>
	</div>
</footer>
