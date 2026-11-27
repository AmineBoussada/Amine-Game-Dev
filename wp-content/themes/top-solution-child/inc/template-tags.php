<?php
/**
 * Reusable template helpers (no business logic).
 *
 * @package TopSolutionChild
 */

defined( 'ABSPATH' ) || exit;

/**
 * Is WooCommerce active?
 *
 * @return bool
 */
function ts_child_has_woocommerce() {
	return class_exists( 'WooCommerce' );
}

/**
 * Digits (and a leading plus) only, for tel: and wa.me links.
 *
 * @param string $number Raw phone number.
 * @return string
 */
function ts_child_phone_digits( $number ) {
	$number = preg_replace( '/[^0-9+]/', '', (string) $number );
	return (string) $number;
}

/**
 * Tel link for the configured phone number.
 *
 * @return string Empty string when no phone is configured.
 */
function ts_child_phone_url() {
	$digits = ts_child_phone_digits( ts_child_get( 'phone' ) );
	return '' === $digits ? '' : 'tel:' . $digits;
}

/**
 * WhatsApp link for the configured number.
 *
 * @return string Empty string when no number is configured.
 */
function ts_child_whatsapp_url() {
	$digits = ltrim( ts_child_phone_digits( ts_child_get( 'whatsapp' ) ), '+' );
	return '' === $digits ? '' : 'https://wa.me/' . $digits;
}

/**
 * Was this page built with Elementor?
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function ts_child_is_elementor_built( $post_id ) {
	if ( ! $post_id || ! did_action( 'elementor/loaded' ) ) {
		return false;
	}
	$document = \Elementor\Plugin::$instance->documents->get( $post_id );
	return $document && $document->is_built_with_elementor();
}

/**
 * Is the built-in (non-Elementor) homepage going to be rendered?
 *
 * @return bool
 */
function ts_child_is_home_builtin() {
	return is_front_page() && ! ts_child_is_elementor_built( get_queried_object_id() );
}

/**
 * Output the site logo (custom logo, or the site name as text).
 *
 * @param string $extra_class Extra CSS class for the wrapper.
 * @return void
 */
function ts_child_the_logo( $extra_class = '' ) {
	$classes = trim( 'ts-logo ' . $extra_class );
	echo '<a class="' . esc_attr( $classes ) . '" href="' . esc_url( home_url( '/' ) ) . '" rel="home">';
	if ( has_custom_logo() ) {
		$logo_id = (int) get_theme_mod( 'custom_logo' );
		echo wp_get_attachment_image(
			$logo_id,
			'full',
			false,
			array(
				'class'         => 'ts-logo__img',
				'alt'           => get_bloginfo( 'name' ),
				'loading'       => 'eager',
				'fetchpriority' => 'high',
			)
		);
	} else {
		echo '<span class="ts-logo__text">' . esc_html( get_bloginfo( 'name' ) ) . '</span>';
	}
	echo '</a>';
}

/**
 * Menu fallback when no menu is assigned: top-level pages plus the shop link.
 *
 * @param array $args wp_nav_menu() arguments.
 * @return void
 */
function ts_child_menu_fallback( $args = array() ) {
	$menu_class = isset( $args['menu_class'] ) ? $args['menu_class'] : 'ts-nav__list';
	$menu_id    = isset( $args['menu_id'] ) ? $args['menu_id'] : '';

	echo '<ul' . ( $menu_id ? ' id="' . esc_attr( $menu_id ) . '"' : '' ) . ' class="' . esc_attr( $menu_class ) . '">';
	echo '<li class="menu-item"><a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'الرئيسية', 'top-solution-child' ) . '</a></li>';
	if ( ts_child_has_woocommerce() ) {
		echo '<li class="menu-item"><a href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '">' . esc_html__( 'المتجر', 'top-solution-child' ) . '</a></li>';
	}
	wp_list_pages(
		array(
			'title_li' => '',
			'depth'    => 1,
			'exclude'  => implode( ',', ts_child_excluded_page_ids() ),
		)
	);
	echo '</ul>';
}

/**
 * Page IDs that should not show in the fallback menu (WooCommerce system pages).
 *
 * @return int[]
 */
function ts_child_excluded_page_ids() {
	$ids = array();
	if ( ts_child_has_woocommerce() ) {
		foreach ( array( 'shop', 'cart', 'checkout', 'myaccount', 'terms' ) as $page ) {
			$id = wc_get_page_id( $page );
			if ( $id > 0 ) {
				$ids[] = $id;
			}
		}
	}
	$front = (int) get_option( 'page_on_front' );
	if ( $front > 0 ) {
		$ids[] = $front;
	}
	return $ids;
}

/**
 * Simple breadcrumb for pages and posts (WooCommerce pages use its own).
 *
 * @return void
 */
function ts_child_breadcrumbs() {
	if ( is_front_page() ) {
		return;
	}
	$items   = array();
	$items[] = '<a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'الرئيسية', 'top-solution-child' ) . '</a>';

	if ( is_singular() ) {
		foreach ( array_reverse( get_post_ancestors( get_the_ID() ) ) as $ancestor_id ) {
			$items[] = '<a href="' . esc_url( get_permalink( $ancestor_id ) ) . '">' . esc_html( get_the_title( $ancestor_id ) ) . '</a>';
		}
		$items[] = '<span aria-current="page">' . esc_html( get_the_title() ) . '</span>';
	} elseif ( is_search() ) {
		$items[] = '<span aria-current="page">' . esc_html__( 'نتائج البحث', 'top-solution-child' ) . '</span>';
	} elseif ( is_archive() ) {
		$items[] = '<span aria-current="page">' . esc_html( wp_strip_all_tags( get_the_archive_title() ) ) . '</span>';
	} elseif ( is_404() ) {
		$items[] = '<span aria-current="page">404</span>';
	}

	echo '<nav class="ts-breadcrumbs" aria-label="' . esc_attr__( 'مسار التنقل', 'top-solution-child' ) . '"><ol>';
	foreach ( $items as $item ) {
		echo '<li>' . wp_kses_post( $item ) . '</li>';
	}
	echo '</ol></nav>';
}

/**
 * Current cart item count (0 when WooCommerce is not active).
 *
 * @return int
 */
function ts_child_cart_count() {
	if ( ! ts_child_has_woocommerce() || ! function_exists( 'WC' ) || ! WC()->cart ) {
		return 0;
	}
	return (int) WC()->cart->get_cart_contents_count();
}

/**
 * Cart link markup with a live count (updated by WooCommerce cart fragments).
 *
 * @return string
 */
function ts_child_cart_link_html() {
	$count = ts_child_cart_count();
	return sprintf(
		'<a class="ts-icon-btn ts-cart-link" href="%1$s"><span class="screen-reader-text">%2$s</span>%3$s<span class="ts-cart-link__count%4$s">%5$s</span></a>',
		esc_url( wc_get_cart_url() ),
		esc_html__( 'السلة', 'top-solution-child' ),
		ts_child_icon( 'cart' ),
		0 === $count ? ' is-empty' : '',
		esc_html( number_format_i18n( $count ) )
	);
}
