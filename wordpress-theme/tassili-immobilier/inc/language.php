<?php
/**
 * French / Arabic language switching.
 *
 * The visitor's language comes from ?lang=fr|ar, then the tassili_lang cookie, then the
 * default set in the Customizer. WordPress is told to use the matching locale for the
 * public site, so gettext, <html lang> and <html dir> all follow. The admin keeps the
 * user's own language.
 *
 * Page-caching plugins must vary the cache by the "lang" query argument and the
 * tassili_lang cookie.
 *
 * @package Tassili_Immobilier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Languages the theme supports.
 *
 * @return array<string,array{locale:string,dir:string,label:string}>
 */
function tassili_supported_languages() {
	return array(
		'fr' => array(
			'locale' => 'fr_FR',
			'dir'    => 'ltr',
			'label'  => 'Français',
		),
		'ar' => array(
			'locale' => 'ar',
			'dir'    => 'rtl',
			'label'  => 'العربية',
		),
	);
}

/**
 * Default language chosen in the Customizer.
 *
 * @return string fr|ar
 */
function tassili_default_language() {
	$lang = get_theme_mod( 'tassili_default_language', 'fr' );
	return array_key_exists( $lang, tassili_supported_languages() ) ? $lang : 'fr';
}

/**
 * Whether this is a REST API request for something other than the theme's own routes.
 *
 * Works before WordPress has parsed the request, which is when the locale is chosen.
 *
 * @return bool
 */
function tassili_is_foreign_rest_request() {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- only inspected, never output or stored.
	$uri   = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$route = isset( $_GET['rest_route'] ) ? wp_unslash( $_GET['rest_route'] ) : '';
	// phpcs:enable WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$prefix  = '/' . rest_get_url_prefix() . '/';
	$is_rest = false !== strpos( (string) $uri, $prefix ) || '' !== (string) $route;
	if ( ! $is_rest ) {
		return false;
	}
	return false === strpos( (string) $uri, $prefix . 'tassili/v1/' ) && 0 !== strpos( (string) $route, '/tassili/v1/' );
}

/**
 * True for requests that serve the public site, including our own public form endpoints.
 *
 * @return bool
 */
function tassili_is_public_request() {
	if ( ! is_admin() ) {
		// The block editor talks to WordPress through the REST API. Only our own endpoints
		// follow the visitor's language; everything else keeps the signed-in user's language.
		return ! tassili_is_foreign_rest_request();
	}
	global $pagenow;
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- only reads the action name to pick a language.
	$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';
	return in_array( $pagenow, array( 'admin-post.php', 'admin-ajax.php' ), true )
		&& in_array( $action, array( 'tassili_contact', 'tassili_visit' ), true );
}

/**
 * The visitor's language code for this request.
 *
 * @return string fr|ar
 */
function tassili_lang() {
	static $lang = null;
	if ( null !== $lang ) {
		return $lang;
	}
	$supported = tassili_supported_languages();

	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- display preference only, no state change.
	if ( isset( $_GET['lang'] ) ) {
		$requested = sanitize_key( wp_unslash( $_GET['lang'] ) );
		if ( isset( $supported[ $requested ] ) ) {
			$lang = $requested;
			return $lang;
		}
	}
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	if ( isset( $_COOKIE['tassili_lang'] ) ) {
		$cookie = sanitize_key( wp_unslash( $_COOKIE['tassili_lang'] ) );
		if ( isset( $supported[ $cookie ] ) ) {
			$lang = $cookie;
			return $lang;
		}
	}

	$lang = tassili_default_language();
	return $lang;
}

/**
 * Whether the page is shown in Arabic.
 *
 * @return bool
 */
function tassili_is_ar() {
	return 'ar' === tassili_lang();
}

/**
 * Make the public site use the visitor's locale.
 *
 * @param string $locale Locale WordPress would use.
 * @return string
 */
function tassili_filter_locale( $locale ) {
	if ( ! tassili_is_public_request() ) {
		return $locale;
	}
	$supported = tassili_supported_languages();
	return $supported[ tassili_lang() ]['locale'];
}
add_filter( 'locale', 'tassili_filter_locale' );

/**
 * WordPress takes text direction from its own core translation, which sites rarely install
 * for Arabic. Set it from the chosen language instead, so is_rtl() and dir="rtl" are right.
 */
function tassili_set_text_direction() {
	global $wp_locale;
	if ( ! tassili_is_public_request() || ! $wp_locale instanceof WP_Locale ) {
		return;
	}
	$supported                 = tassili_supported_languages();
	$wp_locale->text_direction = $supported[ tassili_lang() ]['dir'];
}
add_action( 'after_setup_theme', 'tassili_set_text_direction', 1 );

/**
 * Remember the language when it is chosen with ?lang=.
 */
function tassili_remember_language() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display preference only.
	if ( ! isset( $_GET['lang'] ) || headers_sent() || is_admin() ) {
		return;
	}
	$lang = tassili_lang();
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_COOKIE['tassili_lang'] ) && sanitize_key( wp_unslash( $_COOKIE['tassili_lang'] ) ) === $lang ) {
		return;
	}
	setcookie(
		'tassili_lang',
		$lang,
		array(
			'expires'  => time() + YEAR_IN_SECONDS,
			'path'     => COOKIEPATH ? COOKIEPATH : '/',
			'domain'   => COOKIE_DOMAIN,
			'secure'   => is_ssl(),
			'httponly' => true,
			'samesite' => 'Lax',
		)
	);
}
add_action( 'init', 'tassili_remember_language', 1 );

/**
 * URL of the current page in the other language.
 *
 * @param string|null $lang Target language, or null for "the other one".
 * @return string
 */
function tassili_language_url( $lang = null ) {
	if ( null === $lang ) {
		$lang = tassili_is_ar() ? 'fr' : 'ar';
	}
	$current = ( is_ssl() ? 'https://' : 'http://' ) . ( isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '' ) . ( isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/' );
	return add_query_arg( 'lang', $lang, remove_query_arg( 'lang', $current ) );
}

/**
 * Text of a post in the visitor's language: Arabic when available, otherwise the main text.
 *
 * @param int    $post_id Post ID.
 * @param string $field   title|desc|role|langs|place|quote|delivery.
 * @param string $main    The main-language value used as fallback.
 * @return string
 */
function tassili_localized_post_text( $post_id, $field, $main ) {
	if ( ! tassili_is_ar() ) {
		return $main;
	}
	$ar = get_post_meta( $post_id, '_tassili_' . $field . '_ar', true );
	return is_string( $ar ) && '' !== trim( $ar ) ? $ar : $main;
}

/**
 * Arabic titles for listings, agents and testimonials.
 *
 * @param string $title   Title.
 * @param int    $post_id Post ID.
 * @return string
 */
function tassili_filter_title( $title, $post_id = 0 ) {
	if ( ! tassili_is_ar() || ! $post_id || is_admin() ) {
		return $title;
	}
	if ( ! in_array( get_post_type( $post_id ), array( 'property', 'agent', 'testimonial', 'page', 'post' ), true ) ) {
		return $title;
	}
	return tassili_localized_post_text( $post_id, 'title', $title );
}
add_filter( 'the_title', 'tassili_filter_title', 10, 2 );

/**
 * Same for the document <title> of a single listing.
 *
 * @param string  $title Title.
 * @param WP_Post $post  Post.
 * @return string
 */
function tassili_filter_single_post_title( $title, $post ) {
	return $post instanceof WP_Post ? tassili_filter_title( $title, $post->ID ) : $title;
}
add_filter( 'single_post_title', 'tassili_filter_single_post_title', 10, 2 );

/**
 * Name of a term in the visitor's language.
 *
 * @param WP_Term|int|null $term Term object or ID.
 * @return string
 */
function tassili_term_name( $term ) {
	if ( is_numeric( $term ) ) {
		$term = get_term( (int) $term );
	}
	if ( ! $term instanceof WP_Term ) {
		return '';
	}
	if ( tassili_is_ar() ) {
		$ar = get_term_meta( $term->term_id, 'tassili_name_ar', true );
		if ( is_string( $ar ) && '' !== trim( $ar ) ) {
			return $ar;
		}
	}
	return $term->name;
}

/**
 * Alternate-language links (hreflang) for search engines.
 */
function tassili_print_hreflang() {
	if ( is_admin() ) {
		return;
	}
	foreach ( array_keys( tassili_supported_languages() ) as $code ) {
		printf(
			'<link rel="alternate" hreflang="%1$s" href="%2$s" />' . "\n",
			esc_attr( $code ),
			esc_url( tassili_language_url( $code ) )
		);
	}
}
add_action( 'wp_head', 'tassili_print_hreflang', 3 );

/**
 * Show the Arabic content of a page or post to Arabic visitors when one was written.
 *
 * Runs before WordPress formats the content, so blocks, shortcodes and paragraphs still work.
 *
 * @param string $content Post content.
 * @return string
 */
function tassili_filter_content( $content ) {
	if ( ! tassili_is_ar() || is_admin() || ! in_the_loop() || ! is_main_query() || ! is_singular( array( 'page', 'post' ) ) ) {
		return $content;
	}
	$ar = get_post_meta( get_the_ID(), '_tassili_content_ar', true );
	return is_string( $ar ) && '' !== trim( $ar ) ? $ar : $content;
}
add_filter( 'the_content', 'tassili_filter_content', 1 );
