<?php
/**
 * Data helpers: settings, numbers and prices, listing data, galleries and placeholders.
 *
 * @package Tassili_Immobilier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default settings. They describe the fictional demo agency; the owner overrides them in
 * Appearance > Customize.
 *
 * @return array<string,mixed>
 */
function tassili_defaults() {
	return array(
		'phone'             => '0555 00 00 00',
		'whatsapp'          => '213555000000',
		'email'             => 'contact@tassili-immobilier.dz',
		'notify_email'      => '',
		'address_fr'        => 'Lot 12, Cité des Pins, El Biar, Alger',
		'address_ar'        => 'التجزئة 12، حي الصنوبر، الأبيار، الجزائر',
		'hours_fr'          => 'Dim – Jeu · 8h30 – 17h30',
		'hours_ar'          => 'الأحد – الخميس · 8:30 – 17:30',
		'saturday_fr'       => 'Samedi · 9h – 13h (sur rendez-vous)',
		'saturday_ar'       => 'السبت · 9:00 – 13:00 (بموعد)',
		'friday_fr'         => 'Vendredi · fermé',
		'friday_ar'         => 'الجمعة · مغلق',
		'rc'                => 'RC 16/00-0000000 B 09',
		'nif'               => 'NIF 0000 1600 0000 000',
		'founded'           => 2009,
		'map_query'         => 'El Biar, Alger, Algérie',
		'facebook'          => '',
		'instagram'         => '',
		'ref_prefix'        => 'TS',
		'per_page'          => 12,
		'stat_sold'         => 1240,
		'stat_wilayas'      => 7,
		'stat_satisfaction' => 98,
		'cta_url'           => '',
		'show_closed'       => 0,
		'hero_lede_fr'      => '',
		'hero_lede_ar'      => '',
		'closed_weekday'    => 5,
	);
}

/**
 * A theme setting.
 *
 * @param string $key Setting key without the tassili_ prefix.
 * @return mixed
 */
function tassili_mod( $key ) {
	$defaults = tassili_defaults();
	$default  = isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
	return get_theme_mod( 'tassili_' . $key, $default );
}

/**
 * A bilingual setting (key_fr / key_ar) in the visitor's language, falling back to French.
 *
 * @param string $key Setting key without suffix.
 * @return string
 */
function tassili_mod_l( $key ) {
	$value = tassili_is_ar() ? (string) tassili_mod( $key . '_ar' ) : '';
	if ( '' === trim( $value ) ) {
		$value = (string) tassili_mod( $key . '_fr' );
	}
	return $value;
}

/**
 * Digits-only phone for a tel: link, in international format.
 *
 * @param string $phone Phone as typed.
 * @return string
 */
function tassili_tel_href( $phone ) {
	$digits = preg_replace( '/\D+/', '', (string) $phone );
	if ( '' === $digits ) {
		return '';
	}
	if ( 0 === strpos( $digits, '213' ) ) {
		return '+' . $digits;
	}
	if ( 0 === strpos( $digits, '0' ) ) {
		return '+213' . substr( $digits, 1 );
	}
	return '+' . $digits;
}

/**
 * Group the digits of a whole number: narrow no-break space in French, dots in Arabic.
 *
 * @param int|float $n Number.
 * @return string
 */
function tassili_number( $n ) {
	$sep = tassili_is_ar() ? '.' : "\u{202F}";
	return preg_replace( '/\B(?=(\d{3})+(?!\d))/', $sep, (string) (int) round( (float) $n ) );
}

/**
 * One decimal, comma as separator.
 *
 * @param float $x Number.
 * @return string
 */
function tassili_decimal( $x ) {
	$r = round( (float) $x, 1 );
	return str_replace( '.', ',', ( floor( $r ) === $r ) ? (string) (int) $r : number_format( $r, 1, '.', '' ) );
}

/**
 * Price pieces for display: the amount in dinars and a note (per month, or the spoken
 * "billion centimes" form Algerians use for sale prices).
 *
 * @param int    $price Price in DA.
 * @param string $tx    vente|location.
 * @return array{amount:string,note:string}
 */
function tassili_price_parts( $price, $tx ) {
	$price  = (int) $price;
	$amount = tassili_number( $price );

	if ( 'location' === $tx ) {
		$note = __( '/ month', 'tassili-immobilier' );
	} else {
		$billions = $price / 10000000;
		if ( $billions >= 1 ) {
			/* translators: %s: amount in billions of centimes, e.g. 4,8 (1 billion centimes = 10,000,000 DA). */
			$note = sprintf( _n( '%s billion centimes', '%s billion centimes', (int) floor( $billions ), 'tassili-immobilier' ), tassili_decimal( $billions ) );
		} else {
			$millions = $price / 10000;
			/* translators: %s: amount in millions of centimes, e.g. 950 (1 million centimes = 10,000 DA). */
			$note = sprintf( _n( '%s million centimes', '%s million centimes', (int) max( 1, floor( $millions ) ), 'tassili-immobilier' ), tassili_number( $millions ) );
		}
	}

	return array(
		'amount' => $amount,
		'note'   => $note,
	);
}

/**
 * Print a price: <span class="ltr">amount</span> DA plus the note.
 *
 * @param int    $price Price in DA.
 * @param string $tx    vente|location.
 * @param string $note_tag Wrapper tag for the note.
 */
function tassili_the_price( $price, $tx, $note_tag = 'small' ) {
	$parts = tassili_price_parts( $price, $tx );
	$tag   = in_array( $note_tag, array( 'small', 'span' ), true ) ? $note_tag : 'small';
	printf(
		'<span class="ltr">%1$s</span> %2$s <%3$s>%4$s</%3$s>',
		esc_html( $parts['amount'] ),
		esc_html__( 'DA', 'tassili-immobilier' ),
		esc_attr( $tag ),
		esc_html( $parts['note'] )
	);
}

/**
 * The paper (document) options of a listing.
 *
 * @return array<string,string> id => label.
 */
function tassili_paper_options() {
	return array(
		'acte'       => __( 'Notarial deed', 'tassili-immobilier' ),
		'livret'     => __( 'Land registry booklet', 'tassili-immobilier' ),
		'permis'     => __( 'Building permit', 'tassili-immobilier' ),
		'conformite' => __( 'Certificate of conformity', 'tassili-immobilier' ),
	);
}

/**
 * Typology options (Algerian "F" system).
 *
 * @return string[]
 */
function tassili_typology_options() {
	return array( 'F1', 'F2', 'F3', 'F4', 'F5', 'F6', 'F7', 'F8' );
}

/**
 * Typologies offered as search chips.
 *
 * @return string[]
 */
function tassili_typology_chips() {
	return array( 'F1', 'F2', 'F3', 'F4', 'F5+' );
}

/**
 * Terms a listing belongs to, split into wilaya and commune.
 *
 * @param int $post_id Post ID.
 * @return array{wilaya:?WP_Term,commune:?WP_Term}
 */
function tassili_listing_location( $post_id ) {
	$terms  = get_the_terms( $post_id, 'wilaya' );
	$result = array(
		'wilaya'  => null,
		'commune' => null,
	);
	if ( ! is_array( $terms ) ) {
		return $result;
	}
	foreach ( $terms as $term ) {
		if ( $term->parent ) {
			$result['commune'] = $term;
			$parent            = get_term( $term->parent, 'wilaya' );
			if ( $parent instanceof WP_Term ) {
				$result['wilaya'] = $parent;
			}
		} elseif ( null === $result['wilaya'] ) {
			$result['wilaya'] = $term;
		}
	}
	return $result;
}

/**
 * "Commune, Wilaya" in the visitor's language.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function tassili_place( $post_id ) {
	$loc   = tassili_listing_location( $post_id );
	$parts = array();
	if ( $loc['commune'] ) {
		$parts[] = tassili_term_name( $loc['commune'] );
	}
	if ( $loc['wilaya'] ) {
		$parts[] = tassili_term_name( $loc['wilaya'] );
	}
	return implode( _x( ', ', 'separator between commune and wilaya', 'tassili-immobilier' ), $parts );
}

/**
 * All the data of one listing in one array.
 *
 * @param int|WP_Post|null $post Post.
 * @return array<string,mixed>|null Null when it is not a listing.
 */
function tassili_get_property( $post = null ) {
	static $cache = array();

	$post = get_post( $post );
	if ( ! $post || 'property' !== $post->post_type ) {
		return null;
	}
	$id = (int) $post->ID;
	if ( isset( $cache[ $id ] ) ) {
		return $cache[ $id ];
	}

	$meta = function ( $key ) use ( $id ) {
		return get_post_meta( $id, '_tassili_' . $key, true );
	};

	$tx_terms = get_the_terms( $id, 'transaction' );
	$tx       = ( is_array( $tx_terms ) && $tx_terms ) ? $tx_terms[0]->slug : 'vente';
	$type_t   = get_the_terms( $id, 'property_type' );
	$type     = ( is_array( $type_t ) && $type_t ) ? $type_t[0] : null;
	$loc      = tassili_listing_location( $id );
	$papers   = $meta( 'papers' );
	$floor    = $meta( 'floor' );

	$cache[ $id ] = array(
		'id'        => $id,
		'ref'       => (string) $meta( 'ref' ),
		'title'     => get_the_title( $post ),
		'tx'        => 'location' === $tx ? 'location' : 'vente',
		'type'      => $type,
		'wilaya'    => $loc['wilaya'],
		'commune'   => $loc['commune'],
		'price'     => (int) $meta( 'price' ),
		'surface'   => (int) $meta( 'surface' ),
		'land'      => (int) $meta( 'land' ),
		'rooms'     => (int) $meta( 'rooms' ),
		'beds'      => (int) $meta( 'beds' ),
		'baths'     => (int) $meta( 'baths' ),
		'floor'     => '' === $floor ? null : (int) $floor,
		'floors'    => (int) $meta( 'floors' ),
		'year'      => (int) $meta( 'year' ),
		'typo'      => (string) $meta( 'typo' ),
		'is_new'    => (bool) $meta( 'is_new' ),
		'delivery'  => tassili_localized_post_text( $id, 'delivery', (string) $meta( 'delivery' ) ),
		'featured'  => (bool) $meta( 'featured' ),
		'exclusive' => (bool) $meta( 'exclusive' ),
		'status'    => tassili_normalize_status( (string) $meta( 'status' ) ),
		'papers'    => is_array( $papers ) ? $papers : array(),
		'agent'     => (int) $meta( 'agent' ),
	);
	return $cache[ $id ];
}

/**
 * Description of a listing (Arabic version when available).
 *
 * @param int $post_id Post ID.
 * @return string Plain text.
 */
function tassili_property_description( $post_id ) {
	$main = wp_strip_all_tags( (string) get_post_field( 'post_content', $post_id ) );
	return tassili_localized_post_text( $post_id, 'desc', $main );
}

/**
 * Names of the features (amenities) of a listing.
 *
 * @param int $post_id Post ID.
 * @return string[]
 */
function tassili_property_features( $post_id ) {
	$terms = get_the_terms( $post_id, 'feature' );
	if ( ! is_array( $terms ) ) {
		return array();
	}
	return array_map( 'tassili_term_name', $terms );
}

/**
 * Whether a listing has a feature (by term slug).
 *
 * @param int    $post_id Post ID.
 * @param string $slug    Term slug.
 * @return bool
 */
function tassili_property_has_feature( $post_id, $slug ) {
	return has_term( $slug, 'feature', $post_id );
}

/*
---------------------------------------------------------------------------------------
 * Images: real photos from the media library, or bundled illustrations when there are none.
 * ------------------------------------------------------------------------------------
 */

/**
 * URL of a bundled placeholder illustration.
 *
 * @param string $name File name without extension.
 * @return string
 */
function tassili_placeholder_url( $name ) {
	return TASSILI_URI . 'assets/img/placeholders/' . sanitize_file_name( $name ) . '.svg';
}

/**
 * Which illustrations stand in for photos, per property type slug.
 *
 * @param string $type_slug Type slug.
 * @param int    $variant   1-3, picks the time of day.
 * @return string[] Placeholder names, in gallery order.
 */
function tassili_placeholder_set( $type_slug, $variant ) {
	$v = max( 1, min( 3, (int) $variant ) );
	switch ( $type_slug ) {
		case 'villa':
			return array( 'ext-villa-' . $v, 'int-salon', 'int-cuisine', 'int-chambre', 'int-sdb', 'out-jardin', 'out-terrasse' );
		case 'duplex':
			return array( 'ext-duplex-' . $v, 'int-salon', 'out-terrasse', 'int-cuisine', 'int-chambre', 'int-sdb' );
		case 'terrain':
			return array( 'ext-terrain-' . $v, 'ext-terrain-' . ( $v % 3 + 1 ), 'out-vue' );
		case 'local':
		case 'local-commercial':
			return array( 'ext-local-' . $v, 'int-interieur', 'ext-local-' . ( $v % 3 + 1 ) );
		default:
			return array( 'ext-residence-' . $v, 'int-salon', 'int-cuisine', 'int-chambre', 'int-sdb', 'out-vue' );
	}
}

/**
 * Photos of a listing, ready for display.
 *
 * Each item: url (card size), large (gallery size), full (original), alt, caption, is_placeholder.
 *
 * @param int $post_id Post ID.
 * @return array<int,array<string,mixed>>
 */
function tassili_property_photos( $post_id ) {
	$ids   = array();
	$thumb = (int) get_post_thumbnail_id( $post_id );
	if ( $thumb ) {
		$ids[] = $thumb;
	}
	$gallery = get_post_meta( $post_id, '_tassili_gallery', true );
	if ( is_string( $gallery ) && '' !== $gallery ) {
		foreach ( array_filter( array_map( 'absint', explode( ',', $gallery ) ) ) as $att ) {
			if ( ! in_array( $att, $ids, true ) && wp_attachment_is_image( $att ) ) {
				$ids[] = $att;
			}
		}
	}

	$photos = array();
	$title  = get_the_title( $post_id );
	foreach ( $ids as $att ) {
		$alt      = trim( (string) get_post_meta( $att, '_wp_attachment_image_alt', true ) );
		$cap      = wp_get_attachment_caption( $att );
		$photos[] = array(
			'attachment'     => $att,
			'url'            => (string) wp_get_attachment_image_url( $att, 'tassili-card' ),
			'large'          => (string) wp_get_attachment_image_url( $att, 'tassili-gallery' ),
			'full'           => (string) wp_get_attachment_image_url( $att, 'full' ),
			'alt'            => '' !== $alt ? $alt : $title,
			'caption'        => $cap ? $cap : '',
			'is_placeholder' => false,
		);
	}
	if ( $photos ) {
		return $photos;
	}

	$type   = tassili_get_property( $post_id );
	$slug   = ( $type && $type['type'] ) ? $type['type']->slug : 'appartement';
	$labels = tassili_placeholder_labels();
	foreach ( tassili_placeholder_set( $slug, ( $post_id % 3 ) + 1 ) as $name ) {
		$key      = preg_replace( '/^(ext|int|out)-|-\d$/', '', $name );
		$url      = tassili_placeholder_url( $name );
		$photos[] = array(
			'attachment'     => 0,
			'url'            => $url,
			'large'          => $url,
			'full'           => $url,
			'alt'            => $title,
			'caption'        => isset( $labels[ $key ] ) ? $labels[ $key ] : '',
			'is_placeholder' => true,
		);
	}
	return $photos;
}

/**
 * Captions for the bundled illustrations.
 *
 * @return array<string,string>
 */
function tassili_placeholder_labels() {
	return array(
		'villa'     => __( 'Front', 'tassili-immobilier' ),
		'duplex'    => __( 'Front', 'tassili-immobilier' ),
		'residence' => __( 'Residence', 'tassili-immobilier' ),
		'terrain'   => __( 'Land', 'tassili-immobilier' ),
		'local'     => __( 'Shopfront', 'tassili-immobilier' ),
		'salon'     => __( 'Living room', 'tassili-immobilier' ),
		'cuisine'   => __( 'Kitchen', 'tassili-immobilier' ),
		'chambre'   => __( 'Bedroom', 'tassili-immobilier' ),
		'sdb'       => __( 'Bathroom', 'tassili-immobilier' ),
		'jardin'    => __( 'Garden', 'tassili-immobilier' ),
		'terrasse'  => __( 'Terrace', 'tassili-immobilier' ),
		'vue'       => __( 'View', 'tassili-immobilier' ),
		'interieur' => __( 'Interior', 'tassili-immobilier' ),
	);
}

/**
 * Print the card image of a listing.
 *
 * @param int    $post_id Post ID.
 * @param string $loading lazy|eager.
 */
function tassili_the_card_image( $post_id, $loading = 'lazy' ) {
	$photos = tassili_property_photos( $post_id );
	$first  = $photos[0];
	if ( $first['attachment'] ) {
		echo wp_get_attachment_image(
			$first['attachment'],
			'tassili-card',
			false,
			array(
				'alt'     => $first['alt'],
				'loading' => $loading,
				'sizes'   => '(max-width: 639px) 100vw, (max-width: 1023px) 50vw, 400px',
			)
		);
		return;
	}
	printf(
		'<img src="%1$s" alt="%2$s" width="800" height="600" loading="%3$s" decoding="async" />',
		esc_url( $first['url'] ),
		esc_attr( $first['alt'] ),
		esc_attr( $loading )
	);
}

/**
 * Initials for an avatar.
 *
 * @param string $name Full name.
 * @return string
 */
function tassili_initials( $name ) {
	$words = preg_split( '/\s+/u', trim( $name ) );
	$out   = '';
	foreach ( array_slice( (array) $words, 0, 2 ) as $word ) {
		$out .= mb_strtoupper( mb_substr( $word, 0, 1 ) );
	}
	return $out;
}

/**
 * Agent data.
 *
 * @param int $agent_id Agent post ID (0 for the first agent).
 * @return array<string,mixed>|null
 */
function tassili_get_agent( $agent_id = 0 ) {
	$post = $agent_id ? get_post( $agent_id ) : null;
	if ( ! $post || 'agent' !== $post->post_type || 'publish' !== $post->post_status ) {
		$first = get_posts(
			array(
				'post_type'        => 'agent',
				'posts_per_page'   => 1,
				'orderby'          => array(
					'menu_order' => 'ASC',
					'ID'         => 'ASC',
				),
				'suppress_filters' => true,
			)
		);
		$post  = $first ? $first[0] : null;
	}
	if ( ! $post ) {
		return null;
	}
	$id       = (int) $post->ID;
	$whatsapp = preg_replace( '/\D+/', '', (string) get_post_meta( $id, '_tassili_whatsapp', true ) );
	$phone    = (string) get_post_meta( $id, '_tassili_phone', true );
	return array(
		'id'       => $id,
		'name'     => get_the_title( $post ),
		'role'     => tassili_localized_post_text( $id, 'role', (string) get_post_meta( $id, '_tassili_role', true ) ),
		'langs'    => tassili_localized_post_text( $id, 'langs', (string) get_post_meta( $id, '_tassili_langs', true ) ),
		'phone'    => $phone,
		'tel'      => tassili_tel_href( '' !== $phone ? $phone : $whatsapp ),
		'whatsapp' => '' !== $whatsapp ? $whatsapp : preg_replace( '/\D+/', '', (string) tassili_mod( 'whatsapp' ) ),
		'photo'    => (int) get_post_thumbnail_id( $id ),
	);
}

/*
---------------------------------------------------------------------------------------
 * Availability status of a listing
 * ------------------------------------------------------------------------------------
 */

/**
 * The statuses a listing can have.
 *
 * @return array<string,string> key => label.
 */
function tassili_status_options() {
	return array(
		'available' => __( 'Available', 'tassili-immobilier' ),
		'reserved'  => __( 'Reserved', 'tassili-immobilier' ),
		'closed'    => __( 'Sold or rented', 'tassili-immobilier' ),
	);
}

/**
 * A valid status key, "available" when empty or unknown.
 *
 * @param string $status Raw value.
 * @return string
 */
function tassili_normalize_status( $status ) {
	return array_key_exists( $status, tassili_status_options() ) ? $status : 'available';
}

/**
 * Public label of a status: "Sold" for a sale, "Rented" for a rental.
 *
 * @param string $status Status key.
 * @param string $tx     vente|location.
 * @return string Empty for an available listing.
 */
function tassili_status_label( $status, $tx ) {
	if ( 'reserved' === $status ) {
		return __( 'Reserved', 'tassili-immobilier' );
	}
	if ( 'closed' === $status ) {
		return 'location' === $tx ? __( 'Rented', 'tassili-immobilier' ) : __( 'Sold', 'tassili-immobilier' );
	}
	return '';
}

/**
 * Meta query clause that leaves sold and rented listings out, unless the owner chose to show them.
 *
 * @return array Empty when nothing must be excluded.
 */
function tassili_open_listing_clause() {
	if ( tassili_mod( 'show_closed' ) ) {
		return array();
	}
	return array(
		'relation' => 'OR',
		array(
			'key'     => '_tassili_status',
			'compare' => 'NOT EXISTS',
		),
		array(
			'key'     => '_tassili_status',
			'value'   => 'closed',
			'compare' => '!=',
		),
	);
}
