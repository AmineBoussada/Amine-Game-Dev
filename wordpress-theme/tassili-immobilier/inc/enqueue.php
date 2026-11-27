<?php
/**
 * Styles and scripts.
 *
 * @package Tassili_Immobilier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the current request shows the property search results.
 *
 * @return bool
 */
function tassili_is_property_archive() {
	return is_post_type_archive( 'property' ) || is_tax( array( 'wilaya', 'transaction', 'property_type' ) );
}

/**
 * Enqueue front-end assets.
 */
function tassili_enqueue_assets() {
	$ver = TASSILI_VERSION;

	wp_enqueue_style( 'tassili-fonts', TASSILI_URI . 'assets/css/fonts.css', array(), $ver );
	wp_enqueue_style( 'tassili-tokens', TASSILI_URI . 'assets/css/tokens.css', array( 'tassili-fonts' ), $ver );
	wp_enqueue_style( 'tassili-main', TASSILI_URI . 'assets/css/styles.css', array( 'tassili-tokens' ), $ver );
	wp_enqueue_style( 'tassili-style', get_stylesheet_uri(), array( 'tassili-main' ), $ver );

	$defer = array(
		'strategy'  => 'defer',
		'in_footer' => true,
	);

	wp_enqueue_script( 'tassili-app', TASSILI_URI . 'assets/js/app.js', array(), $ver, $defer );
	wp_localize_script( 'tassili-app', 'TassiliData', tassili_script_data() );

	if ( is_front_page() || tassili_is_elementor_post( get_queried_object_id() ) || tassili_is_property_archive() || is_page_template( 'templates/page-favorites.php' ) ) {
		wp_enqueue_script( 'tassili-search', TASSILI_URI . 'assets/js/search.js', array( 'tassili-app' ), $ver, $defer );
	}
	if ( is_singular( 'property' ) ) {
		wp_enqueue_script( 'tassili-property', TASSILI_URI . 'assets/js/property.js', array( 'tassili-app', 'tassili-forms' ), $ver, $defer );
	}
	if ( is_singular( 'property' ) || is_page_template( 'templates/page-contact.php' ) || tassili_is_elementor_post( get_queried_object_id() ) ) {
		wp_enqueue_script( 'tassili-forms', TASSILI_URI . 'assets/js/forms.js', array( 'tassili-app' ), $ver, $defer );
	}

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'tassili_enqueue_assets' );

/**
 * Preload the two fonts every page needs, in the visitor's language.
 */
function tassili_preload_fonts() {
	$files = tassili_is_ar()
		? array( 'ibm-plex-sans-arabic-arabic-400-normal.woff2', 'reem-kufi-arabic-600-normal.woff2' )
		: array( 'ibm-plex-sans-latin-400-normal.woff2', 'marcellus-latin-400-normal.woff2' );
	foreach ( $files as $file ) {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin />' . "\n",
			esc_url( TASSILI_URI . 'assets/fonts/' . $file )
		);
	}
}
add_action( 'wp_head', 'tassili_preload_fonts', 1 );

/**
 * Data and translated strings handed to the scripts.
 *
 * @return array<string,mixed>
 */
function tassili_script_data() {
	$communes = array();
	$wilayas  = get_terms(
		array(
			'taxonomy'   => 'wilaya',
			'hide_empty' => false,
			'parent'     => 0,
		)
	);
	if ( is_array( $wilayas ) ) {
		foreach ( $wilayas as $wilaya ) {
			$children = get_terms(
				array(
					'taxonomy'   => 'wilaya',
					'hide_empty' => false,
					'parent'     => $wilaya->term_id,
				)
			);
			if ( is_array( $children ) && $children ) {
				$communes[ $wilaya->slug ] = array_map(
					static function ( $c ) {
						return array(
							'slug' => $c->slug,
							'name' => tassili_term_name( $c ),
						);
					},
					$children
				);
			}
		}
	}

	return array(
		'lang'         => tassili_lang(),
		'rtl'          => is_rtl(),
		'restUrl'      => esc_url_raw( rest_url( 'tassili/v1/' ) ),
		'ajaxUrl'      => esc_url_raw( admin_url( 'admin-ajax.php' ) ),
		'archiveUrl'   => esc_url_raw( get_post_type_archive_link( 'property' ) ),
		'favoritesUrl' => esc_url_raw( tassili_favorites_url() ),
		'closedDay'    => (int) tassili_mod( 'closed_weekday' ),
		'communes'     => $communes,
		'i18n'         => array(
			'menuOpen'     => __( 'Open the menu', 'tassili-immobilier' ),
			'menuClose'    => __( 'Close the menu', 'tassili-immobilier' ),
			'themeDark'    => __( 'Switch to dark mode', 'tassili-immobilier' ),
			'themeLight'   => __( 'Switch to light mode', 'tassili-immobilier' ),
			'favAdd'       => __( 'Add to favourites', 'tassili-immobilier' ),
			'favRemove'    => __( 'Remove from favourites', 'tassili-immobilier' ),
			'favAdded'     => __( 'Added to your favourites', 'tassili-immobilier' ),
			'favRemoved'   => __( 'Removed from your favourites', 'tassili-immobilier' ),
			'favorites'    => __( 'My favourites', 'tassili-immobilier' ),
			/* translators: %d: number of favourites. */
			'favoritesN'   => __( 'My favourites (%d)', 'tassili-immobilier' ),
			'allCommunes'  => __( 'All communes', 'tassili-immobilier' ),
			'seeNone'      => __( 'No properties', 'tassili-immobilier' ),
			'seeOne'       => __( 'View 1 property', 'tassili-immobilier' ),
			/* translators: %s: number of properties. */
			'seeMany'      => __( 'View %s properties', 'tassili-immobilier' ),
			'filtersOpen'  => __( 'Filters', 'tassili-immobilier' ),
			/* translators: %d: number of active filters. */
			'filtersOpenN' => __( 'Filters (%d)', 'tassili-immobilier' ),
			'loading'      => __( 'Loading…', 'tassili-immobilier' ),
			'loadError'    => __( 'Something went wrong. Please try again.', 'tassili-immobilier' ),
			'readMore'     => __( 'Read more', 'tassili-immobilier' ),
			'readLess'     => __( 'Show less', 'tassili-immobilier' ),
			'moreCriteria' => __( '+ More criteria', 'tassili-immobilier' ),
			'lessCriteria' => __( '− Fewer criteria', 'tassili-immobilier' ),
			'shareCopied'  => __( 'Link copied', 'tassili-immobilier' ),
			'shareFail'    => __( 'Copy the link from the address bar', 'tassili-immobilier' ),
			'sending'      => __( 'Sending…', 'tassili-immobilier' ),
			'errRequired'  => __( 'This field is required.', 'tassili-immobilier' ),
			'errName'      => __( 'Enter at least 2 letters.', 'tassili-immobilier' ),
			'errPhone'     => __( 'Enter an Algerian number, for example 0555 12 34 56.', 'tassili-immobilier' ),
			'errEmail'     => __( 'Enter a valid e-mail address, for example name@example.dz.', 'tassili-immobilier' ),
			'errMessage'   => __( 'Write at least 10 characters.', 'tassili-immobilier' ),
			'errConsent'   => __( 'Tick this box so that we can answer you.', 'tassili-immobilier' ),
			'errDate'      => __( 'Choose a date from tomorrow.', 'tassili-immobilier' ),
			'errClosed'    => __( 'The agency is closed that day. Choose another day.', 'tassili-immobilier' ),
			'errSummary'   => __( 'Check the fields marked in red.', 'tassili-immobilier' ),
			'errSend'      => __( 'We could not send your request. Please try again or contact us on WhatsApp.', 'tassili-immobilier' ),
			'close'        => __( 'Close', 'tassili-immobilier' ),
		),
	);
}
