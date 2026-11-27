<?php
/**
 * Elementor support: pages built with Elementor render full width, and the theme's dynamic
 * blocks (search panel, listings, wilaya tiles) are available as shortcodes.
 *
 * @package Tassili_Immobilier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether a post was built with the Elementor editor.
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function tassili_is_elementor_post( $post_id ) {
	$post_id = (int) $post_id;
	return $post_id > 0 && 'builder' === get_post_meta( $post_id, '_elementor_edit_mode', true );
}

/**
 * Body class so the stylesheet can drop the page width limit.
 *
 * @param string[] $classes Classes.
 * @return string[]
 */
function tassili_elementor_body_class( $classes ) {
	if ( is_singular() && tassili_is_elementor_post( get_queried_object_id() ) ) {
		$classes[] = 'is-elementor-page';
	}
	return $classes;
}
add_filter( 'body_class', 'tassili_elementor_body_class' );

/**
 * Shortcode [tassili_search]: the property search panel.
 *
 * @return string
 */
function tassili_shortcode_search() {
	ob_start();
	echo '<div class="container tassili-sc-search">';
	get_template_part( 'template-parts/home-search' );
	echo '</div>';
	return (string) ob_get_clean();
}
add_shortcode( 'tassili_search', 'tassili_shortcode_search' );

/**
 * Shortcode [tassili_listings count="6" featured="1" tx="vente" type="" wilaya=""]: property cards.
 *
 * @param array $atts Attributes.
 * @return string
 */
function tassili_shortcode_listings( $atts ) {
	$atts = shortcode_atts(
		array(
			'count'    => 6,
			'featured' => '',
			'tx'       => '',
			'type'     => '',
			'wilaya'   => '',
		),
		$atts,
		'tassili_listings'
	);

	$meta_query = array( 'relation' => 'AND' );
	if ( '' !== $atts['featured'] && '0' !== (string) $atts['featured'] ) {
		$meta_query[] = array(
			'key'   => '_tassili_featured',
			'value' => '1',
		);
	}
	$open = tassili_open_listing_clause();
	if ( $open ) {
		$meta_query[] = $open;
	}

	$tax_query = array();
	foreach ( array(
		'tx'     => 'transaction',
		'type'   => 'property_type',
		'wilaya' => 'wilaya',
	) as $key => $taxonomy ) {
		if ( '' !== $atts[ $key ] ) {
			$tax_query[] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => array_map( 'sanitize_title', explode( ',', $atts[ $key ] ) ),
			);
		}
	}

	$args = array(
		'post_type'      => 'property',
		'posts_per_page' => max( 1, min( 24, (int) $atts['count'] ) ),
		'no_found_rows'  => true,
	);
	if ( count( $meta_query ) > 1 ) {
		$args['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- small, capped query.
	}
	if ( $tax_query ) {
		$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- small, capped query.
	}

	$query = new WP_Query( $args );
	if ( ! $query->have_posts() ) {
		return '';
	}
	ob_start();
	echo '<div class="container"><div class="card-grid">';
	while ( $query->have_posts() ) {
		$query->the_post();
		get_template_part( 'template-parts/card', 'property' );
	}
	echo '</div></div>';
	wp_reset_postdata();
	return (string) ob_get_clean();
}
add_shortcode( 'tassili_listings', 'tassili_shortcode_listings' );

/**
 * Shortcode [tassili_wilayas count="5"]: wilaya tiles with listing counts.
 *
 * @param array $atts Attributes.
 * @return string
 */
function tassili_shortcode_wilayas( $atts ) {
	$atts  = shortcode_atts( array( 'count' => 5 ), $atts, 'tassili_wilayas' );
	$top   = tassili_top_wilayas( max( 1, min( 12, (int) $atts['count'] ) ) );
	$tiles = array( 'ext-villa-2', 'ext-residence-1', 'ext-residence-3', 'ext-duplex-2', 'ext-residence-2' );
	if ( ! $top ) {
		return '';
	}
	ob_start();
	echo '<div class="container"><div class="wilaya-grid">';
	foreach ( $top as $i => $w ) {
		printf(
			'<a class="wilaya-tile" href="%1$s"><img src="%2$s" alt="" width="800" height="600" loading="lazy" /><span class="wt-in"><span class="wt-name">%3$s</span><span class="wt-count">%4$s</span></span></a>',
			esc_url( get_term_link( $w ) ),
			esc_url( tassili_placeholder_url( $tiles[ $i % count( $tiles ) ] ) ),
			esc_html( tassili_term_name( $w ) ),
			esc_html( tassili_count_label( tassili_term_listing_count( $w ) ) )
		);
	}
	echo '</div></div>';
	return (string) ob_get_clean();
}
add_shortcode( 'tassili_wilayas', 'tassili_shortcode_wilayas' );

/**
 * Print the current page's Elementor content full width, if it was built with Elementor.
 *
 * @return bool True when the page was printed.
 */
function tassili_render_builder_page() {
	if ( ! is_singular() || ! tassili_is_elementor_post( get_queried_object_id() ) ) {
		return false;
	}
	echo '<main id="main" class="site-main site-main--builder">';
	while ( have_posts() ) {
		the_post();
		the_content();
	}
	echo '</main>';
	return true;
}

/**
 * Let Elementor Pro's Theme Builder replace the header or footer, when it has a template for them.
 *
 * @param string $location Location name.
 * @return bool True when Elementor printed it.
 */
function tassili_elementor_location( $location ) {
	return function_exists( 'elementor_theme_do_location' ) && elementor_theme_do_location( $location );
}

/**
 * Register the Theme Builder locations (Elementor Pro).
 *
 * @param object $manager Locations manager.
 */
function tassili_register_elementor_locations( $manager ) {
	$manager->register_all_core_location();
}
add_action( 'elementor/theme/register_locations', 'tassili_register_elementor_locations' );

/**
 * Shortcode [tassili_contact_form]: the contact form.
 *
 * @return string
 */
function tassili_shortcode_contact_form() {
	ob_start();
	echo '<div class="container tassili-sc-form">';
	get_template_part( 'template-parts/contact-form' );
	echo '</div>';
	return (string) ob_get_clean();
}
add_shortcode( 'tassili_contact_form', 'tassili_shortcode_contact_form' );
