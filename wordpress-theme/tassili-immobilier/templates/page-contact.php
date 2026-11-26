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

$tassili_state    = tassili_form_state( 'contact' );
$tassili_values   = $tassili_state['values'];
$tassili_errors   = $tassili_state['errors'];
$tassili_phone    = (string) tassili_mod( 'phone' );
$tassili_email    = (string) tassili_mod( 'email' );
$tassili_subjects = tassili_contact_subjects();

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- prefill from the link, nothing is saved.
$tassili_subject = isset( $tassili_values['subject'] ) ? sanitize_key( $tassili_values['subject'] ) : ( isset( $_GET['subject'] ) ? sanitize_key( wp_unslash( $_GET['subject'] ) ) : 'buy' );
$tassili_ref     = isset( $tassili_values['ref'] ) ? sanitize_text_field( $tassili_values['ref'] ) : ( isset( $_GET['ref'] ) ? strtoupper( preg_replace( '/[^A-Za-z0-9\-]/', '', sanitize_text_field( wp_unslash( $_GET['ref'] ) ) ) ) : '' );
// phpcs:enable WordPress.Security.NonceVerification.Recommended
if ( ! isset( $tassili_subjects[ $tassili_subject ] ) ) {
	$tassili_subject = 'buy';
}

/**
 * Print the error line under a field.
 *
 * @param string $field Field name.
 * @param string $id    Error element ID.
 * @param array  $errors Errors by field.
 */
$tassili_err = static function ( $field, $id, $errors ) {
	$has = isset( $errors[ $field ] );
	printf( '<p class="f-error" id="%1$s"%2$s>%3$s</p>', esc_attr( $id ), $has ? '' : ' hidden', $has ? esc_html( $errors[ $field ] ) : '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $has switches a fixed attribute.
};
$tassili_val = static function ( $key ) use ( $tassili_values ) {
	return isset( $tassili_values[ $key ] ) ? (string) $tassili_values[ $key ] : '';
};
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
					<div class="form-card" id="contact-form">
						<div id="contact-form-wrap"<?php echo $tassili_state['sent'] ? ' hidden' : ''; ?>>
							<h2><?php esc_html_e( 'Send a message', 'tassili-immobilier' ); ?></h2>
							<p class="muted" style="margin:4px 0 var(--s-5)"><?php esc_html_e( 'We answer within 24 working hours.', 'tassili-immobilier' ); ?></p>
							<form id="contact-form-el" class="form-grid" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" novalidate data-ajax-action="tassili_contact">
								<?php tassili_form_fields( 'contact' ); ?>
								<div class="form-alert full" data-form-summary role="alert"<?php echo $tassili_errors ? '' : ' hidden'; ?>><?php echo esc_html( $tassili_state['message'] ? $tassili_state['message'] : __( 'Check the fields marked in red.', 'tassili-immobilier' ) ); ?></div>
								<div class="field">
									<label for="c-name"><?php esc_html_e( 'Full name', 'tassili-immobilier' ); ?></label>
									<input class="control" id="c-name" name="name" autocomplete="name" required data-rule="name" value="<?php echo esc_attr( $tassili_val( 'name' ) ); ?>"<?php echo isset( $tassili_errors['name'] ) ? ' aria-invalid="true" aria-describedby="c-name-err"' : ''; ?> />
									<?php $tassili_err( 'name', 'c-name-err', $tassili_errors ); ?>
								</div>
								<div class="field">
									<label for="c-phone"><?php esc_html_e( 'Phone', 'tassili-immobilier' ); ?></label>
									<input class="control ltr" id="c-phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" placeholder="0555 12 34 56" required data-rule="phone" value="<?php echo esc_attr( $tassili_val( 'phone' ) ); ?>"<?php echo isset( $tassili_errors['phone'] ) ? ' aria-invalid="true" aria-describedby="c-phone-err"' : ''; ?> />
									<?php $tassili_err( 'phone', 'c-phone-err', $tassili_errors ); ?>
								</div>
								<div class="field">
									<label for="c-email"><?php esc_html_e( 'E-mail (optional)', 'tassili-immobilier' ); ?></label>
									<input class="control ltr" id="c-email" name="email" type="email" autocomplete="email" placeholder="name@example.dz" data-rule="email" value="<?php echo esc_attr( $tassili_val( 'email' ) ); ?>"<?php echo isset( $tassili_errors['email'] ) ? ' aria-invalid="true" aria-describedby="c-email-err"' : ''; ?> />
									<?php $tassili_err( 'email', 'c-email-err', $tassili_errors ); ?>
								</div>
								<div class="field">
									<label for="c-subject"><?php esc_html_e( 'Subject', 'tassili-immobilier' ); ?></label>
									<select class="control" id="c-subject" name="subject">
										<?php foreach ( $tassili_subjects as $tassili_key => $tassili_label ) : ?>
											<option value="<?php echo esc_attr( $tassili_key ); ?>"<?php selected( $tassili_subject, $tassili_key ); ?>><?php echo esc_html( $tassili_label ); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
								<div class="field full">
									<label for="c-ref"><?php esc_html_e( 'Property reference (optional)', 'tassili-immobilier' ); ?></label>
									<input class="control ltr" id="c-ref" name="ref" autocomplete="off" placeholder="TS-1024" value="<?php echo esc_attr( $tassili_ref ); ?>" />
								</div>
								<div class="field full">
									<label for="c-message"><?php esc_html_e( 'Message', 'tassili-immobilier' ); ?></label>
									<textarea class="control" id="c-message" name="message" rows="5" required data-rule="message" placeholder="<?php esc_attr_e( 'Describe your project: wilaya, budget, timing…', 'tassili-immobilier' ); ?>"<?php echo isset( $tassili_errors['message'] ) ? ' aria-invalid="true" aria-describedby="c-message-err"' : ''; ?>><?php echo esc_textarea( $tassili_val( 'message' ) ); ?></textarea>
									<?php $tassili_err( 'message', 'c-message-err', $tassili_errors ); ?>
								</div>
								<div class="field full">
									<label class="check"><input type="checkbox" id="c-consent" name="consent" value="1" required data-rule="consent"<?php echo isset( $tassili_errors['consent'] ) ? ' aria-invalid="true" aria-describedby="c-consent-err"' : ''; ?> /><span><?php esc_html_e( 'I agree to be contacted about my request.', 'tassili-immobilier' ); ?></span></label>
									<?php $tassili_err( 'consent', 'c-consent-err', $tassili_errors ); ?>
								</div>
								<div class="full" style="display:grid;gap:8px;justify-items:start">
									<button class="btn btn-primary btn-lg" type="submit"><span><?php esc_html_e( 'Send the message', 'tassili-immobilier' ); ?></span></button>
								</div>
							</form>
						</div>

						<div class="success-panel" id="contact-success" role="status"<?php echo $tassili_state['sent'] ? '' : ' hidden'; ?>>
							<?php tassili_the_icon( 'check', 'ic-lg' ); ?>
							<h3 id="success-title" tabindex="-1"><?php echo esc_html( $tassili_state['sent'] ? $tassili_state['sent']['title'] : __( 'Message sent', 'tassili-immobilier' ) ); ?></h3>
							<p id="success-text"><?php echo esc_html( $tassili_state['sent'] ? $tassili_state['sent']['text'] : '' ); ?></p>
							<div class="cta-actions">
								<a class="btn btn-wa" href="<?php echo esc_url( tassili_whatsapp_url( __( 'Hello Tassili Immobilier, I would like some information.', 'tassili-immobilier' ) ) ); ?>" target="_blank" rel="noopener"><?php tassili_the_icon( 'wa' ); ?><span><?php esc_html_e( 'Write on WhatsApp', 'tassili-immobilier' ); ?></span></a>
								<a class="btn btn-ghost" id="success-again" href="<?php echo esc_url( remove_query_arg( array( 'tassili_sent', 'tassili_fid' ) ) ); ?>"><?php esc_html_e( 'Send another message', 'tassili-immobilier' ); ?></a>
							</div>
						</div>
					</div>

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
