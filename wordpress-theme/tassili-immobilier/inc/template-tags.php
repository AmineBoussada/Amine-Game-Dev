<?php
/**
 * Template tags used by the theme templates.
 *
 * @package Tassili_Immobilier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WhatsApp (wa.me) link with a ready-to-send message.
 *
 * @param string $message Message text.
 * @param string $number  Number in international format, digits only. Empty = agency number.
 * @return string URL (already escaped for use in href).
 */
function tassili_whatsapp_url( $message, $number = '' ) {
	$number = preg_replace( '/\D+/', '', '' !== $number ? $number : (string) tassili_mod( 'whatsapp' ) );
	return esc_url( 'https://wa.me/' . $number . '?text=' . rawurlencode( $message ) );
}

/**
 * Message sent from a listing's WhatsApp button.
 *
 * @param int $post_id Listing ID.
 * @return string
 */
function tassili_property_whatsapp_message( $post_id ) {
	$p = tassili_get_property( $post_id );
	if ( ! $p ) {
		return __( 'Hello Tassili Immobilier, I would like some information.', 'tassili-immobilier' );
	}
	return sprintf(
		/* translators: 1: listing reference, 2: listing title, 3: place. */
		__( 'Hello Tassili Immobilier, I am interested in the property Ref. %1$s (%2$s, %3$s). Is it still available?', 'tassili-immobilier' ),
		$p['ref'],
		get_the_title( $post_id ),
		tassili_place( $post_id )
	);
}

/**
 * First published page that uses a given page template.
 *
 * @param string $template Template file, such as templates/page-contact.php.
 * @return WP_Post|null
 */
function tassili_get_page_by_template( $template ) {
	static $cache = array();
	if ( array_key_exists( $template, $cache ) ) {
		return $cache[ $template ];
	}
	$pages              = get_pages(
		array(
			'meta_key'    => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- cached, runs once per request.
			'meta_value'  => $template, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'number'      => 1,
			'post_status' => 'publish',
		)
	);
	$cache[ $template ] = $pages ? $pages[0] : null;
	return $cache[ $template ];
}

/**
 * URL of the page that uses a template, or a fallback path under the home URL.
 *
 * @param string $template Template file.
 * @param string $fallback Fallback path.
 * @return string
 */
function tassili_page_url( $template, $fallback ) {
	$page = tassili_get_page_by_template( $template );
	return $page ? get_permalink( $page ) : home_url( $fallback );
}

/**
 * Favourites page URL.
 *
 * @return string
 */
function tassili_favorites_url() {
	return tassili_page_url( 'templates/page-favorites.php', '/favoris/' );
}

/**
 * Contact page URL, optionally with a subject.
 *
 * @param string $subject Subject key (sell|estimate|buy|rent) or empty.
 * @return string
 */
function tassili_contact_url( $subject = '' ) {
	$url = tassili_page_url( 'templates/page-contact.php', '/contact/' );
	return $subject ? add_query_arg( 'subject', $subject, $url ) : $url;
}

/**
 * URL for the header "List my property" button.
 *
 * @return string
 */
function tassili_cta_url() {
	$custom = (string) tassili_mod( 'cta_url' );
	return '' !== $custom ? $custom : tassili_contact_url( 'sell' );
}

/**
 * Print the logo: the custom logo when set, otherwise the mark and name.
 *
 * @param string $extra_class Extra CSS classes.
 */
function tassili_the_logo( $extra_class = '' ) {
	$classes = trim( 'logo ' . $extra_class );
	if ( has_custom_logo() && '' === $extra_class ) {
		the_custom_logo();
		return;
	}
	?>
	<a class="<?php echo esc_attr( $classes ); ?>" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
		<?php tassili_the_logo_mark(); ?>
		<span class="logo-text">
			<span class="logo-a"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
			<span class="logo-b"><?php echo esc_html( get_bloginfo( 'description' ) ? get_bloginfo( 'description' ) : __( 'Real estate', 'tassili-immobilier' ) ); ?></span>
		</span>
	</a>
	<?php
}

/**
 * The agency's headline figures.
 *
 * @return array<int,array{value:string,label:string}>
 */
function tassili_stats() {
	$years = max( 1, (int) wp_date( 'Y' ) - (int) tassili_mod( 'founded' ) );
	return array(
		array(
			'value' => tassili_number( (int) tassili_mod( 'stat_sold' ) ),
			'label' => __( 'properties sold', 'tassili-immobilier' ),
		),
		array(
			'value' => (string) $years,
			'label' => __( 'years of experience', 'tassili-immobilier' ),
		),
		array(
			'value' => (string) (int) tassili_mod( 'stat_wilayas' ),
			'label' => __( 'wilayas covered', 'tassili-immobilier' ),
		),
		array(
			'value' => (int) tassili_mod( 'stat_satisfaction' ) . "\u{202F}%",
			'label' => __( 'satisfied clients', 'tassili-immobilier' ),
		),
	);
}

/**
 * URL of the hero background.
 *
 * @return string
 */
function tassili_hero_image_url() {
	$id = (int) get_theme_mod( 'tassili_hero_image', 0 );
	if ( $id ) {
		$url = wp_get_attachment_image_url( $id, 'full' );
		if ( $url ) {
			return $url;
		}
	}
	return tassili_placeholder_url( 'hero' );
}

/**
 * Floor as a label.
 *
 * @param int|null $floor Floor number, 0 = ground floor.
 * @return string
 */
function tassili_floor_label( $floor ) {
	if ( null === $floor ) {
		return '';
	}
	if ( 0 === (int) $floor ) {
		return __( 'Ground floor', 'tassili-immobilier' );
	}
	if ( 1 === (int) $floor ) {
		return __( '1st floor', 'tassili-immobilier' );
	}
	/* translators: %d: floor number. */
	return sprintf( __( 'Floor %d', 'tassili-immobilier' ), (int) $floor );
}

/**
 * Print a breadcrumb.
 *
 * @param array<int,array{label:string,url?:string}> $items Trail; the last item is the current page.
 */
function tassili_the_breadcrumb( $items ) {
	if ( ! $items ) {
		return;
	}
	$last = count( $items ) - 1;
	?>
	<nav class="breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'tassili-immobilier' ); ?>">
		<ol>
			<?php foreach ( $items as $i => $item ) : ?>
				<?php if ( $i === $last ) : ?>
					<li aria-current="page"><?php echo esc_html( $item['label'] ); ?></li>
				<?php else : ?>
					<li><a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a><?php tassili_the_icon( 'chev', 'flip' ); ?></li>
				<?php endif; ?>
			<?php endforeach; ?>
		</ol>
	</nav>
	<?php
}

/**
 * Print numbered pagination for the main query.
 */
function tassili_the_pagination() {
	global $wp_query;
	if ( $wp_query->max_num_pages < 2 ) {
		return;
	}
	$links = paginate_links(
		array(
			'current'   => max( 1, (int) get_query_var( 'paged' ) ),
			'total'     => (int) $wp_query->max_num_pages,
			'mid_size'  => 1,
			'prev_text' => tassili_icon( 'chev', 'rev' ) . '<span class="screen-reader-text">' . esc_html__( 'Previous page', 'tassili-immobilier' ) . '</span>',
			'next_text' => tassili_icon( 'chev', 'flip' ) . '<span class="screen-reader-text">' . esc_html__( 'Next page', 'tassili-immobilier' ) . '</span>',
		)
	);
	if ( $links ) {
		echo '<nav class="pagination" aria-label="' . esc_attr__( 'Pagination', 'tassili-immobilier' ) . '">' . wp_kses( $links, tassili_pagination_kses() ) . '</nav>';
	}
}

/**
 * Tags allowed inside pagination links.
 *
 * @return array<string,array<string,bool>>
 */
function tassili_pagination_kses() {
	return array_merge(
		tassili_svg_allowed_html(),
		array(
			'a'    => array(
				'class'        => true,
				'href'         => true,
				'aria-current' => true,
			),
			'span' => array(
				'class'        => true,
				'aria-current' => true,
			),
		)
	);
}

/**
 * Title for the results page.
 *
 * @return string
 */
function tassili_archive_title() {
	$f = tassili_current_filters();
	if ( $f['neuf'] ) {
		$title = __( 'New developments', 'tassili-immobilier' );
	} elseif ( 'vente' === $f['tx'] ) {
		$title = __( 'Properties for sale', 'tassili-immobilier' );
	} elseif ( 'location' === $f['tx'] ) {
		$title = __( 'Properties for rent', 'tassili-immobilier' );
	} else {
		$title = __( 'All our properties', 'tassili-immobilier' );
	}

	$term = is_tax() ? get_queried_object() : null;
	if ( ! $term instanceof WP_Term && $f['wilaya'] ) {
		$term = get_term_by( 'slug', $f['wilaya'], 'wilaya' );
	}
	if ( $term instanceof WP_Term && 'wilaya' === $term->taxonomy ) {
		/* translators: 1: title such as "Properties for sale", 2: wilaya name. */
		return sprintf( __( '%1$s in %2$s', 'tassili-immobilier' ), $title, tassili_term_name( $term ) );
	}
	if ( $term instanceof WP_Term && 'transaction' === $term->taxonomy ) {
		return 'location' === $term->slug ? __( 'Properties for rent', 'tassili-immobilier' ) : __( 'Properties for sale', 'tassili-immobilier' );
	}
	if ( $term instanceof WP_Term && 'property_type' === $term->taxonomy ) {
		return tassili_term_name( $term );
	}
	return $title;
}

/**
 * "12 properties" style count line.
 *
 * @param int $n Count.
 * @return string
 */
function tassili_count_label( $n ) {
	if ( 0 === (int) $n ) {
		return __( 'No properties found', 'tassili-immobilier' );
	}
	/* translators: %s: number of properties. */
	return sprintf( _n( '%s property', '%s properties', (int) $n, 'tassili-immobilier' ), number_format_i18n( (int) $n ) );
}

/**
 * Wilayas that have listings, most listings first.
 *
 * @param int $limit Maximum number.
 * @return WP_Term[]
 */
function tassili_top_wilayas( $limit = 5 ) {
	$terms = get_terms(
		array(
			'taxonomy'   => 'wilaya',
			'parent'     => 0,
			'hide_empty' => true,
			'orderby'    => 'count',
			'order'      => 'DESC',
			'number'     => $limit,
		)
	);
	return is_array( $terms ) ? $terms : array();
}

/**
 * Number of published listings in a term, children included.
 *
 * @param WP_Term $term Term.
 * @return int
 */
function tassili_term_listing_count( $term ) {
	return (int) $term->count;
}

/**
 * Listings similar to a given one: same type first, then same wilaya, then the most recent.
 *
 * @param int $post_id Listing ID.
 * @param int $limit   How many to return.
 * @return int[] Listing IDs.
 */
function tassili_similar_properties( $post_id, $limit = 3 ) {
	$ids  = array();
	$base = array(
		'post_type'      => 'property',
		'post_status'    => 'publish',
		'post__not_in'   => array( $post_id ),
		'posts_per_page' => $limit,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	);
	$p    = tassili_get_property( $post_id );

	$passes = array();
	if ( $p && $p['type'] ) {
		$passes[] = array(
			array(
				'taxonomy' => 'property_type',
				'field'    => 'term_id',
				'terms'    => array( $p['type']->term_id ),
			),
		);
	}
	if ( $p && $p['wilaya'] ) {
		$passes[] = array(
			array(
				'taxonomy' => 'wilaya',
				'field'    => 'term_id',
				'terms'    => array( $p['wilaya']->term_id ),
			),
		);
	}
	$passes[] = array();

	foreach ( $passes as $tax_query ) {
		if ( count( $ids ) >= $limit ) {
			break;
		}
		$args                   = $base;
		$args['post__not_in']   = array_merge( $args['post__not_in'], $ids );
		$args['posts_per_page'] = $limit - count( $ids );
		if ( $tax_query ) {
			$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- three rows.
		}
		$ids = array_merge( $ids, get_posts( $args ) );
	}
	return array_slice( $ids, 0, $limit );
}

/**
 * Structured data for a listing (schema.org), for search engines.
 */
function tassili_print_listing_jsonld() {
	if ( ! is_singular( 'property' ) ) {
		return;
	}
	$id = get_queried_object_id();
	$p  = tassili_get_property( $id );
	if ( ! $p ) {
		return;
	}
	$photos = tassili_property_photos( $id );
	$data   = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'RealEstateListing',
		'name'        => get_the_title( $id ),
		'url'         => get_permalink( $id ),
		'description' => wp_trim_words( tassili_property_description( $id ), 40, '…' ),
		'image'       => $photos ? ( $photos[0]['is_placeholder'] ? $photos[0]['url'] : $photos[0]['full'] ) : '',
		'datePosted'  => get_the_date( 'c', $id ),
		'offers'      => array(
			'@type'         => 'Offer',
			'price'         => $p['price'],
			'priceCurrency' => 'DZD',
		),
		'about'       => array(
			'@type'         => 'Residence',
			'address'       => array(
				'@type'           => 'PostalAddress',
				'addressLocality' => $p['commune'] ? $p['commune']->name : '',
				'addressRegion'   => $p['wilaya'] ? $p['wilaya']->name : '',
				'addressCountry'  => 'DZ',
			),
			'floorSize'     => array(
				'@type'    => 'QuantitativeValue',
				'value'    => $p['surface'],
				'unitCode' => 'MTK',
			),
			'numberOfRooms' => $p['rooms'],
		),
	);
	wp_print_inline_script_tag( (string) wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ), array( 'type' => 'application/ld+json' ) );
}
add_action( 'wp_head', 'tassili_print_listing_jsonld', 20 );
