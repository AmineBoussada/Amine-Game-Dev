<?php
/**
 * Site footer.
 *
 * @package TopSolutionChild
 */

defined( 'ABSPATH' ) || exit;

$ts_about     = ts_child_get( 'footer_about' );
$ts_about     = '' !== $ts_about ? $ts_about : get_bloginfo( 'description' );
$ts_copyright = ts_child_get( 'footer_copyright' );
if ( '' === $ts_copyright ) {
	/* translators: 1: year, 2: site name. */
	$ts_copyright = sprintf( __( '© %1$s %2$s. جميع الحقوق محفوظة.', 'top-solution-child' ), wp_date( 'Y' ), get_bloginfo( 'name' ) );
}
$ts_phone_url   = ts_child_phone_url();
$ts_wa_url      = ts_child_whatsapp_url();
$ts_email       = ts_child_get( 'email' );
$ts_address     = ts_child_get( 'address' );
$ts_hours       = ts_child_get( 'hours' );
$ts_has_contact = '' !== $ts_phone_url || '' !== $ts_wa_url || '' !== $ts_email || '' !== $ts_address || '' !== $ts_hours;
?>
<footer class="ts-footer" id="site-footer">
	<div class="ts-container ts-footer__grid">
		<div class="ts-footer__col ts-footer__brand">
			<?php ts_child_the_logo( 'ts-logo--footer' ); ?>
			<?php if ( '' !== $ts_about ) : ?>
				<p class="ts-footer__about"><?php echo esc_html( $ts_about ); ?></p>
			<?php endif; ?>
		</div>

		<?php if ( has_nav_menu( 'menu-2' ) ) : ?>
			<nav class="ts-footer__col" aria-label="<?php esc_attr_e( 'روابط التذييل', 'top-solution-child' ); ?>">
				<h2 class="ts-footer__title"><?php esc_html_e( 'روابط', 'top-solution-child' ); ?></h2>
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'menu-2',
						'container'      => false,
						'menu_class'     => 'ts-footer__list',
						'depth'          => 1,
					)
				);
				?>
			</nav>
		<?php endif; ?>

		<?php if ( $ts_has_contact ) : ?>
			<div class="ts-footer__col">
				<h2 class="ts-footer__title"><?php esc_html_e( 'تواصل معنا', 'top-solution-child' ); ?></h2>
				<ul class="ts-footer__list ts-footer__contact">
					<?php if ( '' !== $ts_phone_url ) : ?>
						<li><?php ts_child_the_icon( 'phone' ); ?><a href="<?php echo esc_url( $ts_phone_url, array( 'tel' ) ); ?>" dir="ltr"><?php echo esc_html( ts_child_get( 'phone' ) ); ?></a></li>
					<?php endif; ?>
					<?php if ( '' !== $ts_wa_url ) : ?>
						<li><?php ts_child_the_icon( 'whatsapp' ); ?><a href="<?php echo esc_url( $ts_wa_url ); ?>" rel="noopener"><?php esc_html_e( 'واتساب', 'top-solution-child' ); ?></a></li>
					<?php endif; ?>
					<?php if ( '' !== $ts_email ) : ?>
						<li><?php ts_child_the_icon( 'mail' ); ?><a href="<?php echo esc_url( 'mailto:' . antispambot( $ts_email ), array( 'mailto' ) ); ?>" dir="ltr"><?php echo esc_html( $ts_email ); ?></a></li>
					<?php endif; ?>
					<?php if ( '' !== $ts_address ) : ?>
						<li><?php ts_child_the_icon( 'pin' ); ?><span><?php echo esc_html( $ts_address ); ?></span></li>
					<?php endif; ?>
					<?php if ( '' !== $ts_hours ) : ?>
						<li><?php ts_child_the_icon( 'clock' ); ?><span><?php echo esc_html( $ts_hours ); ?></span></li>
					<?php endif; ?>
				</ul>
			</div>
		<?php endif; ?>
	</div>

	<div class="ts-footer__bottom">
		<div class="ts-container ts-footer__bottom-inner">
			<p><?php echo esc_html( $ts_copyright ); ?></p>
			<p class="ts-footer__cod"><?php ts_child_the_icon( 'wallet' ); ?><?php esc_html_e( 'الدفع عند الاستلام', 'top-solution-child' ); ?></p>
		</div>
	</div>
</footer>
