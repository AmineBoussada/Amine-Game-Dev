<?php
/**
 * NOVA Immo Theme — bootstrap.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NOVA_IMMO_VERSION', '0.1.0' );

require_once get_template_directory() . '/inc/post-types.php';
require_once get_template_directory() . '/inc/meta.php';
require_once get_template_directory() . '/inc/rest-api.php';
require_once get_template_directory() . '/inc/shortcodes.php';

add_action( 'after_setup_theme', function () {
	load_theme_textdomain( 'nova-immo', get_template_directory() . '/languages' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption' ) );
	add_image_size( 'nova-card', 720, 960, true );   // ratio 3:4 pour les cartes
	add_image_size( 'nova-hero', 1600, 900, true );
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'nova-immo-theme', get_theme_file_uri( 'assets/css/theme.css' ), array(), NOVA_IMMO_VERSION );
	wp_enqueue_script( 'nova-immo-theme-theme-toggle', get_theme_file_uri( 'assets/js/theme-toggle.js' ), array(), NOVA_IMMO_VERSION, true );

	if ( is_post_type_archive( 'annonce' ) || is_singular( 'annonce' ) || is_front_page() ) {
		wp_enqueue_style( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4' );
		wp_enqueue_script( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true );
		wp_enqueue_script( 'nova-immo-map', get_theme_file_uri( 'assets/js/map-listings.js' ), array( 'leaflet' ), NOVA_IMMO_VERSION, true );
		wp_localize_script( 'nova-immo-map', 'novaImmo', array(
			'restUrl'  => esc_url_raw( rest_url( 'nova/v1/annonces' ) ),
			'whatsapp' => nova_immo_whatsapp_number(),
			'i18n'     => array(
				'noResults' => __( 'Aucune annonce ne correspond à votre recherche.', 'nova-immo' ),
				'loading'   => __( 'Chargement…', 'nova-immo' ),
			),
		) );
	}
} );

/**
 * Numéro WhatsApp de l'agence (démo). Modifiable dans Réglages > Général via l'option nova_immo_whatsapp.
 */
function nova_immo_whatsapp_number() {
	return preg_replace( '/\D/', '', (string) get_option( 'nova_immo_whatsapp', '213550000000' ) );
}

/**
 * Prix formaté en dinars algériens (DZD).
 */
function nova_immo_format_price( $amount ) {
	return number_format_i18n( (float) $amount, 0 ) . ' DA';
}
