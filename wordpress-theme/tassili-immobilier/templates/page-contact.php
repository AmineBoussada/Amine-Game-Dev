<?php
/**
 * Template Name: Contact
 * Template Post Type: page
 *
 * @package Tassili_Immobilier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

if ( tassili_render_builder_page() ) {
	get_footer();
	return;
}

$tassili_phone    = (string) tassili_mod( 'phone' );
$tassili_email    = (string) tassili_mod( 'email' );
?>
<main id="main" class="site-main">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<div class="page-head">
			<div class="container">
				<p class="eyebrow"><?php esc_html_e( 'Contact', 'tassili-immobilier' ); ?></p>
				<h1><?php the_title(); ?></h1>
				<p class="muted" style="margin-top:6px;max-width:60ch"><?php esc_html_e( 'The fastest way is WhatsApp. You can also call us or fill in the form.', 'tassili-immobilier' ); ?></p>
			</div>
		</div>

		<section class="section" style="padding-top:var(--s-6)">
			<div class="container">
				<div class="channels">
					<a class="channel wa" href="<?php echo esc_url( tassili_whatsapp_url( __( 'Hello Tassili Immobilier, I would like some information.', 'tassili-immobilier' ) ) ); ?>" target="_blank" rel="noopener">
						<span class="ch-ic"><?php tassili_the_icon( 'wa', 'ic-lg' ); ?></span><b><?php esc_html_e( 'WhatsApp', 'tassili-immobilier' ); ?></b><span><?php esc_html_e( 'Reply within 15 minutes during opening hours', 'tassili-immobilier' ); ?></span>
					</a>
					<?php if ( '' !== $tassili_phone ) : ?>
						<a class="channel" href="<?php echo esc_url( 'tel:' . tassili_tel_href( $tassili_phone ) ); ?>">
							<span class="ch-ic"><?php tassili_the_icon( 'phone', 'ic-lg' ); ?></span><b><?php esc_html_e( 'Phone', 'tassili-immobilier' ); ?></b><span class="ltr"><?php echo esc_html( $tassili_phone ); ?></span>
						</a>
					<?php endif; ?>
					<?php if ( '' !== $tassili_email ) : ?>
						<a class="channel" href="<?php echo esc_url( 'mailto:' . $tassili_email ); ?>">
							<span class="ch-ic"><?php tassili_the_icon( 'mail', 'ic-lg' ); ?></span><b><?php esc_html_e( 'E-mail', 'tassili-immobilier' ); ?></b><span><?php echo esc_html( $tassili_email ); ?></span>
						</a>
					<?php endif; ?>
				</div>

				<div class="contact-layout">
					<?php get_template_part( 'template-parts/contact-form' ); ?>

					<div class="office">
						<div class="office-card">
							<img src="<?php echo esc_url( tassili_placeholder_url( 'office' ) ); ?>" alt="" width="800" height="600" loading="lazy" />
							<div class="in">
								<h2 style="font-size:1.4rem"><?php esc_html_e( 'Our agency', 'tassili-immobilier' ); ?></h2>
								<dl>
									<div><dt><?php esc_html_e( 'Address', 'tassili-immobilier' ); ?></dt><dd><?php echo esc_html( tassili_mod_l( 'address' ) ); ?></dd></div>
									<div><dt><?php esc_html_e( 'Opening hours', 'tassili-immobilier' ); ?></dt><dd><?php echo esc_html( tassili_mod_l( 'hours' ) ); ?><br /><?php echo esc_html( tassili_mod_l( 'saturday' ) ); ?><br /><?php echo esc_html( tassili_mod_l( 'friday' ) ); ?></dd></div>
								</dl>
								<a class="link-arrow" href="
								<?php
								echo esc_url(
									add_query_arg(
										array(
											'api'   => '1',
											'query' => rawurlencode( (string) tassili_mod( 'map_query' ) ),
										),
										'https://www.google.com/maps/search/'
									)
								);
								?>
															" target="_blank" rel="noopener"><?php esc_html_e( 'Open in Google Maps', 'tassili-immobilier' ); ?><?php tassili_the_icon( 'external', 'ic-sm' ); ?></a>
							</div>
						</div>

						<div>
							<h2 style="font-size:1.4rem;margin-bottom:var(--s-3)"><?php esc_html_e( 'Frequently asked questions', 'tassili-immobilier' ); ?></h2>
							<div class="faq">
								<details><summary><?php esc_html_e( 'What are your agency fees?', 'tassili-immobilier' ); ?></summary><p><?php esc_html_e( 'For a sale, 1% of the price paid by each party. For a rental, the equivalent of one month’s rent.', 'tassili-immobilier' ); ?></p></details>
								<details><summary><?php esc_html_e( 'Are the documents checked?', 'tassili-immobilier' ); ?></summary><p><?php esc_html_e( 'Yes. We ask for the notarial deed and, when there is one, the land registry booklet before publishing a property.', 'tassili-immobilier' ); ?></p></details>
								<details><summary><?php esc_html_e( 'Can I visit at the weekend?', 'tassili-immobilier' ); ?></summary><p><?php esc_html_e( 'Visits take place from Sunday to Thursday and on Saturday morning. The agency is closed on Fridays.', 'tassili-immobilier' ); ?></p></details>
								<details><summary><?php esc_html_e( 'I live abroad, what can I do?', 'tassili-immobilier' ); ?></summary><p><?php esc_html_e( 'We organise video visits and prepare a power of attorney with the notary if needed.', 'tassili-immobilier' ); ?></p></details>
							</div>
						</div>
					</div>
				</div>

				<?php if ( '' !== trim( wp_strip_all_tags( get_the_content() ) ) ) : ?>
					<div class="entry-content" style="margin-top:var(--s-6)"><?php the_content(); ?></div>
				<?php endif; ?>
			</div>
		</section>
	<?php endwhile; ?>
</main>
<?php
get_footer();
