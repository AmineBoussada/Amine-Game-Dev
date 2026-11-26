<?php
/**
 * Navigation helpers: fallback menus and the current-page marker.
 *
 * @package Tassili_Immobilier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default links, used until the site owner builds a menu in Appearance > Menus.
 *
 * @return array<int,array{label:string,url:string}>
 */
function tassili_default_menu_items() {
	return array(
		array(
			'label' => __( 'Home', 'tassili-immobilier' ),
			'url'   => home_url( '/' ),
		),
		array(
			'label' => __( 'Buy', 'tassili-immobilier' ),
			'url'   => tassili_archive_url( array( 'tx' => 'vente' ) ),
		),
		array(
			'label' => __( 'Rent', 'tassili-immobilier' ),
			'url'   => tassili_archive_url( array( 'tx' => 'location' ) ),
		),
		array(
			'label' => __( 'New', 'tassili-immobilier' ),
			'url'   => tassili_archive_url( array( 'neuf' => '1' ) ),
		),
		array(
			'label' => __( 'The agency', 'tassili-immobilier' ),
			'url'   => tassili_page_url( 'templates/page-about.php', '/a-propos/' ),
		),
		array(
			'label' => __( 'Contact', 'tassili-immobilier' ),
			'url'   => tassili_contact_url(),
		),
	);
}

/**
 * Whether a menu link points at what is being shown now.
 *
 * Compares the path, and for the property archive also the filter arguments, so "Buy"
 * (?tx=vente) and "Rent" (?tx=location) are marked separately.
 *
 * @param string $url Link URL.
 * @return bool
 */
function tassili_url_is_current( $url ) {
	$link = wp_parse_url( $url );
	if ( empty( $link['path'] ) ) {
		return false;
	}
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- only parsed and compared, never output.
	$request = wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/' );
	$path    = isset( $request['path'] ) ? $request['path'] : '/';
	if ( untrailingslashit( $link['path'] ) !== untrailingslashit( $path ) ) {
		return false;
	}

	$link_args = array();
	if ( ! empty( $link['query'] ) ) {
		wp_parse_str( $link['query'], $link_args );
		unset( $link_args['lang'] );
	}
	$filters = tassili_current_filters();
	if ( is_post_type_archive( 'property' ) ) {
		$wanted = array(
			'tx'   => isset( $link_args['tx'] ) ? sanitize_key( $link_args['tx'] ) : '',
			'neuf' => isset( $link_args['neuf'] ) && '1' === (string) $link_args['neuf'],
		);
		return $wanted['tx'] === $filters['tx'] && $wanted['neuf'] === $filters['neuf'];
	}
	return empty( $link_args );
}

/**
 * Mark the current menu link for assistive technology and styling.
 *
 * @param array $atts Link attributes.
 * @return array
 */
function tassili_nav_link_attributes( $atts ) {
	if ( ! empty( $atts['href'] ) && tassili_url_is_current( $atts['href'] ) ) {
		$atts['aria-current'] = 'page';
	}
	return $atts;
}
add_filter( 'nav_menu_link_attributes', 'tassili_nav_link_attributes' );

/**
 * Print the fallback menu (an unordered list of links).
 *
 * @param string $list_class Class for the list.
 * @param bool   $arrow Add a chevron after each label (mobile drawer).
 */
function tassili_fallback_menu( $list_class = '', $arrow = false ) {
	echo '<ul' . ( '' !== $list_class ? ' class="' . esc_attr( $list_class ) . '"' : '' ) . '>';
	foreach ( tassili_default_menu_items() as $item ) {
		$current = tassili_url_is_current( $item['url'] ) ? ' aria-current="page"' : '';
		printf(
			'<li><a href="%1$s"%2$s>%3$s%4$s</a></li>',
			esc_url( $item['url'] ),
			$current, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed attribute string.
			esc_html( $item['label'] ),
			$arrow ? wp_kses( tassili_icon( 'chev', 'flip ic-sm' ), tassili_svg_allowed_html() ) : ''
		);
	}
	echo '</ul>';
}

/**
 * Print the main navigation (desktop bar) or the drawer list.
 *
 * @param string $variant bar|drawer.
 */
function tassili_the_primary_nav( $variant = 'bar' ) {
	$class = 'drawer' === $variant ? 'drawer-nav' : 'nav-list';
	$arrow = 'drawer' === $variant;
	if ( has_nav_menu( 'primary' ) ) {
		wp_nav_menu(
			array(
				'theme_location' => 'primary',
				'container'      => false,
				'menu_class'     => $class,
				'items_wrap'     => '<ul class="%2$s">%3$s</ul>',
				'link_after'     => $arrow ? tassili_icon( 'chev', 'flip ic-sm' ) : '',
				'depth'          => 1,
				'fallback_cb'    => false,
			)
		);
		return;
	}
	tassili_fallback_menu( $class, $arrow );
}

/**
 * Print the footer links.
 */
function tassili_the_footer_nav() {
	if ( has_nav_menu( 'footer' ) ) {
		wp_nav_menu(
			array(
				'theme_location' => 'footer',
				'container'      => false,
				'items_wrap'     => '<ul>%3$s</ul>',
				'depth'          => 1,
				'fallback_cb'    => false,
			)
		);
		return;
	}
	$items   = tassili_default_menu_items();
	$items[] = array(
		'label' => __( 'List my property', 'tassili-immobilier' ),
		'url'   => tassili_cta_url(),
	);
	echo '<ul>';
	foreach ( array_slice( $items, 1 ) as $item ) {
		printf( '<li><a href="%1$s">%2$s</a></li>', esc_url( $item['url'] ), esc_html( $item['label'] ) );
	}
	echo '</ul>';
}

/*
---------------------------------------------------------------------------------------
 * Arabic label for custom menu items
 * ------------------------------------------------------------------------------------
 */

/**
 * Add an "Arabic label" field to every menu item in Appearance > Menus.
 *
 * @param int $item_id Menu item ID.
 */
function tassili_menu_item_field( $item_id ) {
	$value = get_post_meta( $item_id, '_menu_item_tassili_title_ar', true );
	?>
	<p class="field-tassili-title-ar description description-wide">
		<label for="edit-menu-item-tassili-title-ar-<?php echo esc_attr( (string) $item_id ); ?>">
			<?php esc_html_e( 'Label in Arabic', 'tassili-immobilier' ); ?><br />
			<input type="text" id="edit-menu-item-tassili-title-ar-<?php echo esc_attr( (string) $item_id ); ?>" class="widefat" dir="rtl" name="menu-item-tassili-title-ar[<?php echo esc_attr( (string) $item_id ); ?>]" value="<?php echo esc_attr( (string) $value ); ?>" />
		</label>
	</p>
	<?php
}
add_action( 'wp_nav_menu_item_custom_fields', 'tassili_menu_item_field', 10, 1 );

/**
 * Save the Arabic label.
 *
 * @param int $menu_id         Menu ID.
 * @param int $menu_item_db_id Menu item ID.
 */
function tassili_save_menu_item_field( $menu_id, $menu_item_db_id ) {
	if ( ! isset( $_POST['update-nav-menu-nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['update-nav-menu-nonce'] ) ), 'update-nav_menu' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	if ( isset( $_POST['menu-item-tassili-title-ar'][ $menu_item_db_id ] ) ) {
		$value = sanitize_text_field( wp_unslash( $_POST['menu-item-tassili-title-ar'][ $menu_item_db_id ] ) );
		if ( '' === $value ) {
			delete_post_meta( $menu_item_db_id, '_menu_item_tassili_title_ar' );
		} else {
			update_post_meta( $menu_item_db_id, '_menu_item_tassili_title_ar', $value );
		}
	}
}
add_action( 'wp_update_nav_menu_item', 'tassili_save_menu_item_field', 10, 2 );

/**
 * Show the Arabic label to Arabic visitors.
 *
 * @param string  $title Item title.
 * @param WP_Post $item  Menu item.
 * @return string
 */
function tassili_menu_item_title( $title, $item ) {
	if ( is_admin() || ! tassili_is_ar() || empty( $item->ID ) ) {
		return $title;
	}
	$ar = get_post_meta( $item->ID, '_menu_item_tassili_title_ar', true );
	return is_string( $ar ) && '' !== $ar ? $ar : $title;
}
add_filter( 'nav_menu_item_title', 'tassili_menu_item_title', 10, 2 );
