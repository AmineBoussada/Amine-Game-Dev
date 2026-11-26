<?php
/**
 * Site footer, floating WhatsApp button and message region.
 *
 * @package Tassili_Immobilier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tassili_phone = (string) tassili_mod( 'phone' );
$tassili_email = (string) tassili_mod( 'email' );
$tassili_fb    = (string) tassili_mod( 'facebook' );
$tassili_ig    = (string) tassili_mod( 'instagram' );
$tassili_wa    = tassili_whatsapp_url( __( 'Hello Tassili Immobilier, I would like some information.', 'tassili-immobilier' ) );
$tassili_priv  = get_privacy_policy_url();
?>
<footer id="site-footer" class="site-footer">
	<div class="container">
		<div class="footer-grid">
			<div class="footer-brand">
				<?php tassili_the_logo( 'logo--footer' ); ?>
				<p>
					<?php
					printf(
						/* translators: 1: year founded, 2: number of wilayas. */
						esc_html__( 'Real estate agency since %1$d. Sales, rentals and new developments in %2$d wilayas, with documents checked before every listing.', 'tassili-immobilier' ),
						(int) tassili_mod( 'founded' ),
						(int) tassili_mod( 'stat_wilayas' )
					);
					?>
				</p>
				<div class="social">
					<?php if ( '' !== $tassili_fb ) : ?>
						<a href="<?php echo esc_url( $tassili_fb ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'Facebook', 'tassili-immobilier' ); ?>"><?php tassili_the_icon( 'facebook' ); ?></a>
					<?php endif; ?>
					<?php if ( '' !== $tassili_ig ) : ?>
						<a href="<?php echo esc_url( $tassili_ig ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'Instagram', 'tassili-immobilier' ); ?>"><?php tassili_the_icon( 'instagram' ); ?></a>
					<?php endif; ?>
					<a href="<?php echo esc_url( $tassili_wa ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'Write on WhatsApp', 'tassili-immobilier' ); ?>"><?php tassili_the_icon( 'wa' ); ?></a>
				</div>
			</div>

			<details class="f-acc" open>
				<summary><h3><?php esc_html_e( 'Useful links', 'tassili-immobilier' ); ?></h3></summary>
				<?php tassili_the_footer_nav(); ?>
			</details>

			<details class="f-acc" open>
				<summary><h3><?php esc_html_e( 'Our wilayas', 'tassili-immobilier' ); ?></h3></summary>
				<ul>
					<?php foreach ( tassili_top_wilayas( 6 ) as $tassili_w ) : ?>
						<li><a href="<?php echo esc_url( get_term_link( $tassili_w ) ); ?>"><?php echo esc_html( tassili_term_name( $tassili_w ) ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</details>

			<details class="f-acc" open>
				<summary><h3><?php esc_html_e( 'Contact us', 'tassili-immobilier' ); ?></h3></summary>
				<ul class="contact-list">
					<li><?php tassili_the_icon( 'pin', 'ic-sm' ); ?><span><?php echo esc_html( tassili_mod_l( 'address' ) ); ?></span></li>
					<?php if ( '' !== $tassili_phone ) : ?>
						<li><?php tassili_the_icon( 'phone', 'ic-sm' ); ?><a class="ltr" href="<?php echo esc_url( 'tel:' . tassili_tel_href( $tassili_phone ) ); ?>"><?php echo esc_html( $tassili_phone ); ?></a></li>
					<?php endif; ?>
					<li><?php tassili_the_icon( 'wa', 'ic-sm' ); ?><a href="<?php echo esc_url( $tassili_wa ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Write on WhatsApp', 'tassili-immobilier' ); ?></a></li>
					<?php if ( '' !== $tassili_email ) : ?>
						<li><?php tassili_the_icon( 'mail', 'ic-sm' ); ?><a href="<?php echo esc_url( 'mailto:' . $tassili_email ); ?>"><?php echo esc_html( $tassili_email ); ?></a></li>
					<?php endif; ?>
					<li><?php tassili_the_icon( 'clock', 'ic-sm' ); ?><span><?php echo esc_html( tassili_mod_l( 'hours' ) ); ?><br /><?php echo esc_html( tassili_mod_l( 'saturday' ) ); ?></span></li>
				</ul>
			</details>
		</div>

		<?php if ( is_active_sidebar( 'footer-widgets' ) ) : ?>
			<div class="footer-widgets">
				<?php dynamic_sidebar( 'footer-widgets' ); ?>
			</div>
		<?php endif; ?>

		<div class="footer-bottom">
			<span>
				<?php
				printf(
					/* translators: 1: year, 2: site name. */
					esc_html__( '© %1$s %2$s', 'tassili-immobilier' ),
					esc_html( wp_date( 'Y' ) ),
					esc_html( get_bloginfo( 'name' ) )
				);
				?>
			</span>
			<nav aria-label="<?php esc_attr_e( 'Legal', 'tassili-immobilier' ); ?>">
				<?php
				$tassili_about = tassili_get_page_by_template( 'templates/page-about.php' );
				if ( $tassili_about ) :
					?>
					<a href="<?php echo esc_url( get_permalink( $tassili_about ) . '#mentions' ); ?>"><?php esc_html_e( 'Legal notice', 'tassili-immobilier' ); ?></a>
				<?php endif; ?>
				<?php if ( $tassili_priv ) : ?>
					<a href="<?php echo esc_url( $tassili_priv ); ?>"><?php esc_html_e( 'Privacy', 'tassili-immobilier' ); ?></a>
				<?php endif; ?>
			</nav>
		</div>
	</div>
</footer>

<a class="wa-fab" id="wa-fab" href="<?php echo esc_url( $tassili_wa ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'Contact us on WhatsApp', 'tassili-immobilier' ); ?>">
	<?php tassili_the_icon( 'wa' ); ?>
</a>
<div class="toast-region" id="toast-region" role="status" aria-live="polite"></div>

<?php wp_footer(); ?>
</body>
</html>
