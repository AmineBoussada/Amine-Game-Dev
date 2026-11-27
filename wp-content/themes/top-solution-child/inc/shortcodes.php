<?php
/**
 * Presentation-only shortcodes for Elementor/editor pages. They only print
 * values the owner entered in the Customizer.
 *
 * @package TopSolutionChild
 */

defined( 'ABSPATH' ) || exit;

/**
 * [ts_contact_cards] — tap-to-call / WhatsApp / email / address / hours cards.
 *
 * @return string
 */
function ts_child_shortcode_contact_cards() {
	$cards = array();

	$phone_url = ts_child_phone_url();
	if ( '' !== $phone_url ) {
		$cards[] = array( 'phone', __( 'اتصل بنا', 'top-solution-child' ), ts_child_get( 'phone' ), $phone_url, true );
	}
	$wa_url = ts_child_whatsapp_url();
	if ( '' !== $wa_url ) {
		$cards[] = array( 'whatsapp', __( 'واتساب', 'top-solution-child' ), ts_child_get( 'whatsapp' ), $wa_url, true );
	}
	if ( '' !== ts_child_get( 'email' ) ) {
		$cards[] = array( 'mail', __( 'البريد الإلكتروني', 'top-solution-child' ), ts_child_get( 'email' ), 'mailto:' . antispambot( ts_child_get( 'email' ) ), true );
	}
	if ( '' !== ts_child_get( 'address' ) ) {
		$cards[] = array( 'pin', __( 'العنوان', 'top-solution-child' ), ts_child_get( 'address' ), '', false );
	}
	if ( '' !== ts_child_get( 'hours' ) ) {
		$cards[] = array( 'clock', __( 'ساعات العمل', 'top-solution-child' ), ts_child_get( 'hours' ), '', false );
	}

	if ( empty( $cards ) ) {
		return current_user_can( 'edit_theme_options' )
			? '<p class="ts-note">' . esc_html__( 'أضف بيانات التواصل من المظهر ← تخصيص ← Top Solution ← بيانات التواصل.', 'top-solution-child' ) . '</p>'
			: '';
	}

	ob_start();
	echo '<ul class="ts-contact-cards">';
	foreach ( $cards as $card ) {
		list( $icon, $label, $value, $url, $ltr ) = $card;
		echo '<li class="ts-contact-card">';
		if ( '' !== $url ) {
			echo '<a class="ts-contact-card__link" href="' . esc_url( $url, array( 'http', 'https', 'tel', 'mailto' ) ) . '">';
		} else {
			echo '<div class="ts-contact-card__link">';
		}
		echo '<span class="ts-contact-card__icon">';
		ts_child_the_icon( $icon );
		echo '</span><span class="ts-contact-card__body"><span class="ts-contact-card__label">' . esc_html( $label ) . '</span>';
		echo '<span class="ts-contact-card__value"' . ( $ltr ? ' dir="ltr"' : '' ) . '>' . esc_html( $value ) . '</span></span>';
		echo '' !== $url ? '</a>' : '</div>';
		echo '</li>';
	}
	echo '</ul>';
	return ob_get_clean();
}
add_shortcode( 'ts_contact_cards', 'ts_child_shortcode_contact_cards' );

/**
 * [ts_trust_bar] — the three trust items configured in the Customizer.
 *
 * @return string
 */
function ts_child_shortcode_trust_bar() {
	ob_start();
	get_template_part( 'template-parts/trust-bar' );
	return ob_get_clean();
}
add_shortcode( 'ts_trust_bar', 'ts_child_shortcode_trust_bar' );
