<?php
/**
 * WooCommerce visual integration. Presentation only: wrappers, loop layout,
 * breadcrumbs, cart fragment. No pricing, stock or order logic lives here.
 *
 * @package TopSolutionChild
 */

defined( 'ABSPATH' ) || exit;

if ( ! ts_child_has_woocommerce() ) {
	return;
}

/**
 * Replace WooCommerce's default content wrappers with this theme's markup.
 *
 * @return void
 */
function ts_child_wc_setup() {
	remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
	remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
	remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );

	add_action( 'woocommerce_before_main_content', 'ts_child_wc_wrapper_start', 10 );
	add_action( 'woocommerce_after_main_content', 'ts_child_wc_wrapper_end', 10 );

	/**
	 * Filter: show WooCommerce's "add to cart" button inside product cards.
	 * Off by default because orders are placed from the product page.
	 */
	if ( ! apply_filters( 'ts_child_show_loop_add_to_cart', false ) ) {
		remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
	}
}
add_action( 'wp', 'ts_child_wc_setup' );

/**
 * Open the shop wrapper.
 *
 * @return void
 */
function ts_child_wc_wrapper_start() {
	echo '<main id="content" class="ts-main ts-shop"><div class="ts-container">';
}

/**
 * Close the shop wrapper.
 *
 * @return void
 */
function ts_child_wc_wrapper_end() {
	echo '</div></main>';
}

/**
 * Drop WooCommerce's float-based layout stylesheets; layout is provided by
 * assets/css/woocommerce.css (CSS grid, logical properties). The "general"
 * stylesheet (forms, notices, tables) is kept and restyled.
 *
 * @param array $styles Registered WooCommerce styles.
 * @return array
 */
function ts_child_wc_styles( $styles ) {
	unset( $styles['woocommerce-layout'], $styles['woocommerce-smallscreen'] );
	return $styles;
}
add_filter( 'woocommerce_enqueue_styles', 'ts_child_wc_styles' );

/**
 * Products per row (CSS grid adapts to the viewport; this is the maximum).
 *
 * @return int
 */
function ts_child_wc_columns() {
	return 4;
}
add_filter( 'loop_shop_columns', 'ts_child_wc_columns' );

/**
 * Products per page: divisible by 2, 3 and 4 so grids never end on a short row.
 *
 * @return int
 */
function ts_child_wc_per_page() {
	return 12;
}
add_filter( 'loop_shop_per_page', 'ts_child_wc_per_page' );

/**
 * Breadcrumb markup: shares styles with the theme breadcrumb.
 *
 * @param array $defaults Breadcrumb arguments.
 * @return array
 */
function ts_child_wc_breadcrumb( $defaults ) {
	$defaults['delimiter']   = '<span class="ts-breadcrumbs__sep" aria-hidden="true">›</span>';
	$defaults['wrap_before'] = '<nav class="woocommerce-breadcrumb ts-breadcrumbs" aria-label="' . esc_attr__( 'مسار التنقل', 'top-solution-child' ) . '">';
	$defaults['home']        = __( 'الرئيسية', 'top-solution-child' );
	return $defaults;
}
add_filter( 'woocommerce_breadcrumb_defaults', 'ts_child_wc_breadcrumb' );

/**
 * Pagination labels.
 *
 * @param array $args Pagination arguments.
 * @return array
 */
function ts_child_wc_pagination( $args ) {
	$args['prev_text'] = esc_html__( 'السابق', 'top-solution-child' );
	$args['next_text'] = esc_html__( 'التالي', 'top-solution-child' );
	return $args;
}
add_filter( 'woocommerce_pagination_args', 'ts_child_wc_pagination' );

/**
 * Arabic label for the sale badge (the store is Arabic only in release 1).
 *
 * @return string
 */
function ts_child_wc_sale_flash() {
	return '<span class="onsale">' . esc_html__( 'تخفيض', 'top-solution-child' ) . '</span>';
}
add_filter( 'woocommerce_sale_flash', 'ts_child_wc_sale_flash' );

/**
 * Keep the header cart count in sync via WooCommerce cart fragments.
 *
 * @param array $fragments Fragments keyed by CSS selector.
 * @return array
 */
function ts_child_wc_cart_fragment( $fragments ) {
	$fragments['a.ts-cart-link'] = ts_child_cart_link_html();
	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'ts_child_wc_cart_fragment' );

/**
 * Add the WooCommerce page type as a body class for page-specific wide layouts.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function ts_child_wc_body_class( $classes ) {
	if ( is_cart() || is_checkout() || is_account_page() ) {
		$classes[] = 'ts-wide-content';
	}
	return $classes;
}
add_filter( 'body_class', 'ts_child_wc_body_class' );

/**
 * Top-level product categories (excluding the default "Uncategorized"),
 * ordered by the WooCommerce category order.
 *
 * @param int  $limit      Maximum number of categories (0 = all).
 * @param bool $hide_empty Hide categories without products.
 * @return WP_Term[]
 */
function ts_child_get_top_categories( $limit = 0, $hide_empty = true ) {
	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'parent'     => 0,
			'hide_empty' => $hide_empty,
			'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
			'orderby'    => 'menu_order',
			'number'     => $limit,
		)
	);
	return is_wp_error( $terms ) ? array() : $terms;
}

/**
 * URL of the page using the "Categories" page template, or the shop page.
 *
 * @return string
 */
function ts_child_categories_page_url() {
	$pages = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Single indexed lookup of one page.
			'meta_value'     => 'templates/page-categories.php', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);
	return $pages ? get_permalink( $pages[0] ) : wc_get_page_permalink( 'shop' );
}
