<?php
/**
 * Property search: reads the filters from the URL, validates them, and turns them into a
 * WP_Query. The same code serves the main archive query and the REST count endpoint, so the
 * "View N properties" button always matches the page that opens.
 *
 * Query arguments (all optional): tx, neuf, wilaya, commune, type, typo, pmin, pmax, smin,
 * papers, amen, sort, view, paged.
 *
 * @package Tassili_Immobilier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sort modes.
 *
 * @return string[]
 */
function tassili_sort_modes() {
	return array( 'recent', 'price_asc', 'price_desc', 'surface' );
}

/**
 * Split and sanitize a comma-separated list of slugs.
 *
 * @param mixed $value Raw value.
 * @return string[]
 */
function tassili_slug_list( $value ) {
	if ( is_array( $value ) ) {
		$value = implode( ',', $value );
	}
	$parts = array_map( 'sanitize_title', explode( ',', (string) $value ) );
	return array_values( array_unique( array_filter( $parts ) ) );
}

/**
 * Normalise raw request values into a clean filter set.
 *
 * @param array $raw Unsanitised values, for example $_GET.
 * @return array<string,mixed>
 */
function tassili_normalize_filters( $raw ) {
	$raw = wp_unslash( $raw );
	$get = static function ( $key ) use ( $raw ) {
		if ( ! isset( $raw[ $key ] ) ) {
			return '';
		}
		// List fields (typo, papers, amen) may arrive as typo[]=F3&typo[]=F4 from a plain form.
		$value = is_array( $raw[ $key ] ) ? implode( ',', array_map( 'sanitize_text_field', array_filter( $raw[ $key ], 'is_scalar' ) ) ) : $raw[ $key ];
		return sanitize_text_field( (string) $value );
	};

	$tx = $get( 'tx' );
	$f  = array(
		'tx'      => in_array( $tx, array( 'vente', 'location' ), true ) ? $tx : '',
		'neuf'    => '1' === $get( 'neuf' ) || 'neuf' === $tx,
		'wilaya'  => sanitize_title( $get( 'wilaya' ) ),
		'commune' => sanitize_title( $get( 'commune' ) ),
		'type'    => sanitize_title( $get( 'type' ) ),
		'typo'    => array_values( array_intersect( tassili_slug_list_upper( $get( 'typo' ) ), tassili_typology_chips() ) ),
		'pmin'    => absint( preg_replace( '/\D+/', '', $get( 'pmin' ) ) ),
		'pmax'    => absint( preg_replace( '/\D+/', '', $get( 'pmax' ) ) ),
		'smin'    => absint( preg_replace( '/\D+/', '', $get( 'smin' ) ) ),
		'papers'  => array_values( array_intersect( tassili_slug_list( $get( 'papers' ) ), array_keys( tassili_paper_options() ) ) ),
		'amen'    => tassili_slug_list( $get( 'amen' ) ),
		'sort'    => in_array( $get( 'sort' ), tassili_sort_modes(), true ) ? $get( 'sort' ) : 'recent',
		'view'    => 'list' === $get( 'view' ) ? 'list' : 'grid',
	);
	if ( $f['neuf'] ) {
		$f['tx'] = '';
	}
	return $f;
}

/**
 * Typology list (uppercase letters and the plus sign are kept).
 *
 * @param string $value Comma-separated, such as "F3,F5+".
 * @return string[]
 */
function tassili_slug_list_upper( $value ) {
	$out = array();
	foreach ( explode( ',', (string) $value ) as $part ) {
		$part = strtoupper( preg_replace( '/[^A-Za-z0-9+]/', '', $part ) );
		if ( '' !== $part ) {
			$out[] = $part;
		}
	}
	return array_values( array_unique( $out ) );
}

/**
 * The filters in the current request.
 *
 * @return array<string,mixed>
 */
function tassili_current_filters() {
	static $filters = null;
	if ( null === $filters ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only search filters.
		$filters = tassili_normalize_filters( $_GET );
	}
	return $filters;
}

/**
 * Convert a filter set to WP_Query arguments (tax_query, meta_query, ordering).
 *
 * @param array $f Normalised filters.
 * @return array<string,mixed>
 */
function tassili_filters_to_query_args( $f ) {
	$tax_query  = array();
	$meta_query = array();

	if ( $f['tx'] ) {
		$tax_query[] = array(
			'taxonomy' => 'transaction',
			'field'    => 'slug',
			'terms'    => array( $f['tx'] ),
		);
	}
	if ( $f['commune'] ) {
		$tax_query[] = array(
			'taxonomy' => 'wilaya',
			'field'    => 'slug',
			'terms'    => array( $f['commune'] ),
		);
	} elseif ( $f['wilaya'] ) {
		$tax_query[] = array(
			'taxonomy'         => 'wilaya',
			'field'            => 'slug',
			'terms'            => array( $f['wilaya'] ),
			'include_children' => true,
		);
	}
	if ( $f['type'] ) {
		$tax_query[] = array(
			'taxonomy' => 'property_type',
			'field'    => 'slug',
			'terms'    => array( $f['type'] ),
		);
	}
	foreach ( $f['amen'] as $slug ) {
		$tax_query[] = array(
			'taxonomy' => 'feature',
			'field'    => 'slug',
			'terms'    => array( $slug ),
		);
	}
	if ( count( $tax_query ) > 1 ) {
		$tax_query['relation'] = 'AND';
	}

	if ( $f['neuf'] ) {
		$meta_query[] = array(
			'key'     => '_tassili_is_new',
			'value'   => '1',
			'compare' => '=',
		);
	}
	if ( $f['pmin'] ) {
		$meta_query[] = array(
			'key'     => '_tassili_price',
			'value'   => $f['pmin'],
			'type'    => 'NUMERIC',
			'compare' => '>=',
		);
	}
	if ( $f['pmax'] ) {
		$meta_query[] = array(
			'key'     => '_tassili_price',
			'value'   => $f['pmax'],
			'type'    => 'NUMERIC',
			'compare' => '<=',
		);
	}
	if ( $f['smin'] ) {
		$meta_query[] = array(
			'key'     => '_tassili_surface',
			'value'   => $f['smin'],
			'type'    => 'NUMERIC',
			'compare' => '>=',
		);
	}
	if ( $f['typo'] ) {
		$values = array();
		foreach ( $f['typo'] as $typo ) {
			if ( 'F5+' === $typo ) {
				$values = array_merge( $values, array( 'F5', 'F6', 'F7', 'F8' ) );
			} else {
				$values[] = $typo;
			}
		}
		$meta_query[] = array(
			'key'     => '_tassili_typo',
			'value'   => array_values( array_unique( $values ) ),
			'compare' => 'IN',
		);
	}
	foreach ( $f['papers'] as $paper ) {
		$meta_query[] = array(
			'key'     => '_tassili_papers',
			'value'   => '"' . $paper . '"',
			'compare' => 'LIKE',
		);
	}
	$open = tassili_open_listing_clause();
	if ( $open ) {
		$meta_query[] = $open;
	}
	if ( count( $meta_query ) > 1 ) {
		$meta_query['relation'] = 'AND';
	}

	$args = array(
		'post_type'   => 'property',
		'post_status' => 'publish',
	);
	if ( $tax_query ) {
		$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- the filters are the feature.
	}
	if ( $meta_query ) {
		$args['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- the filters are the feature.
	}

	switch ( $f['sort'] ) {
		case 'price_asc':
			$args['meta_key'] = '_tassili_price'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$args['orderby']  = array(
				'meta_value_num' => 'ASC',
				'ID'             => 'ASC',
			);
			break;
		case 'price_desc':
			$args['meta_key'] = '_tassili_price'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$args['orderby']  = array(
				'meta_value_num' => 'DESC',
				'ID'             => 'DESC',
			);
			break;
		case 'surface':
			$args['meta_key'] = '_tassili_surface'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$args['orderby']  = array(
				'meta_value_num' => 'DESC',
				'ID'             => 'DESC',
			);
			break;
		default:
			$args['orderby'] = array(
				'date' => 'DESC',
				'ID'   => 'DESC',
			);
	}
	return $args;
}

/**
 * Number of listings per results page.
 *
 * @return int
 */
function tassili_per_page() {
	return max( 3, min( 60, (int) tassili_mod( 'per_page' ) ) );
}

/**
 * Apply the filters to the main query of the property archive and its taxonomy archives.
 *
 * @param WP_Query $query Query.
 */
function tassili_pre_get_posts( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	$is_results = $query->is_post_type_archive( 'property' ) || $query->is_tax( array( 'wilaya', 'transaction', 'property_type' ) );
	if ( ! $is_results ) {
		return;
	}

	$args = tassili_filters_to_query_args( tassili_current_filters() );
	foreach ( array( 'meta_key', 'orderby', 'meta_query' ) as $key ) {
		if ( isset( $args[ $key ] ) ) {
			$query->set( $key, $args[ $key ] );
		}
	}
	if ( isset( $args['tax_query'] ) ) {
		$existing = $query->get( 'tax_query' );
		$query->set(
			'tax_query',
			is_array( $existing ) && $existing ? array_merge(
				array(
					'relation' => 'AND',
					$existing,
				),
				array( $args['tax_query'] )
			) : $args['tax_query']
		);
	}
	$query->set( 'post_type', 'property' );
	$query->set( 'posts_per_page', tassili_per_page() );
}
add_action( 'pre_get_posts', 'tassili_pre_get_posts' );

/**
 * Count listings for a filter set.
 *
 * @param array $f Normalised filters.
 * @return int
 */
function tassili_count_listings( $f ) {
	$args                   = tassili_filters_to_query_args( $f );
	$args['fields']         = 'ids';
	$args['posts_per_page'] = 1;
	$args['no_found_rows']  = false;
	$query                  = new WP_Query( $args );
	return (int) $query->found_posts;
}

/**
 * Filters that change the result set (for the "Filters (n)" badge).
 *
 * @param array $f Normalised filters.
 * @return int
 */
function tassili_active_filter_count( $f ) {
	return ( ( $f['tx'] || $f['neuf'] ) ? 1 : 0 ) + ( $f['wilaya'] ? 1 : 0 ) + ( $f['commune'] ? 1 : 0 ) + ( $f['type'] ? 1 : 0 )
		+ count( $f['typo'] ) + ( $f['pmin'] ? 1 : 0 ) + ( $f['pmax'] ? 1 : 0 ) + ( $f['smin'] ? 1 : 0 )
		+ count( $f['papers'] ) + count( $f['amen'] );
}

/**
 * URL of the property archive with filters.
 *
 * @param array $args Query arguments (empty values are dropped).
 * @return string
 */
function tassili_archive_url( $args = array() ) {
	$base = get_post_type_archive_link( 'property' );
	$base = $base ? $base : home_url( '/' );
	$args = array_filter(
		$args,
		static function ( $v ) {
			return '' !== $v && null !== $v && false !== $v && array() !== $v;
		}
	);
	return $args ? add_query_arg( array_map( 'rawurlencode', array_map( 'strval', $args ) ), $base ) : $base;
}

/**
 * Filters including the term of a taxonomy archive, so the form shows what the page shows.
 *
 * @return array<string,mixed>
 */
function tassili_effective_filters() {
	$f    = tassili_current_filters();
	$term = is_tax() ? get_queried_object() : null;
	if ( ! $term instanceof WP_Term ) {
		return $f;
	}
	if ( 'wilaya' === $term->taxonomy ) {
		if ( $term->parent ) {
			$parent       = get_term( $term->parent, 'wilaya' );
			$f['wilaya']  = $parent instanceof WP_Term ? $parent->slug : '';
			$f['commune'] = $term->slug;
		} else {
			$f['wilaya'] = $term->slug;
		}
	} elseif ( 'transaction' === $term->taxonomy && ! $f['neuf'] ) {
		$f['tx'] = $term->slug;
	} elseif ( 'property_type' === $term->taxonomy ) {
		$f['type'] = $term->slug;
	}
	return $f;
}

/**
 * Query-string parameters for a filter set (empty and default values left out).
 *
 * @param array $f Normalised filters.
 * @return array<string,string>
 */
function tassili_filters_to_params( $f ) {
	$params = array();
	if ( $f['neuf'] ) {
		$params['neuf'] = '1';
	} elseif ( $f['tx'] ) {
		$params['tx'] = $f['tx'];
	}
	foreach ( array( 'wilaya', 'commune', 'type' ) as $key ) {
		if ( $f[ $key ] ) {
			$params[ $key ] = $f[ $key ];
		}
	}
	foreach ( array( 'pmin', 'pmax', 'smin' ) as $key ) {
		if ( $f[ $key ] ) {
			$params[ $key ] = (string) $f[ $key ];
		}
	}
	foreach ( array( 'typo', 'papers', 'amen' ) as $key ) {
		if ( $f[ $key ] ) {
			$params[ $key ] = implode( ',', $f[ $key ] );
		}
	}
	if ( 'recent' !== $f['sort'] ) {
		$params['sort'] = $f['sort'];
	}
	if ( 'list' === $f['view'] ) {
		$params['view'] = 'list';
	}
	return $params;
}

/**
 * Chips for the filters in use, each with a link that removes that one filter.
 *
 * @param array $f Normalised (effective) filters.
 * @return array<int,array{label:string,url:string}>
 */
function tassili_active_chips( $f ) {
	$chips = array();
	$add   = static function ( $label, $without ) use ( &$chips, $f ) {
		$next = $f;
		foreach ( $without as $key => $value ) {
			$next[ $key ] = $value;
		}
		$chips[] = array(
			'label' => $label,
			'url'   => tassili_archive_url( tassili_filters_to_params( $next ) ),
		);
	};

	if ( $f['neuf'] ) {
		$add( __( 'New', 'tassili-immobilier' ), array( 'neuf' => false ) );
	}
	if ( $f['tx'] ) {
		$add( 'vente' === $f['tx'] ? __( 'For sale', 'tassili-immobilier' ) : __( 'For rent', 'tassili-immobilier' ), array( 'tx' => '' ) );
	}
	if ( $f['wilaya'] ) {
		$term = get_term_by( 'slug', $f['wilaya'], 'wilaya' );
		if ( $term instanceof WP_Term ) {
			$add(
				tassili_term_name( $term ),
				array(
					'wilaya'  => '',
					'commune' => '',
				)
			);
		}
	}
	if ( $f['commune'] ) {
		$term = get_term_by( 'slug', $f['commune'], 'wilaya' );
		if ( $term instanceof WP_Term ) {
			$add( tassili_term_name( $term ), array( 'commune' => '' ) );
		}
	}
	if ( $f['type'] ) {
		$term = get_term_by( 'slug', $f['type'], 'property_type' );
		if ( $term instanceof WP_Term ) {
			$add( tassili_term_name( $term ), array( 'type' => '' ) );
		}
	}
	foreach ( $f['typo'] as $typo ) {
		$add( $typo, array( 'typo' => array_values( array_diff( $f['typo'], array( $typo ) ) ) ) );
	}
	if ( $f['pmin'] ) {
		/* translators: %s: amount in DA. */
		$add( sprintf( __( 'From %s DA', 'tassili-immobilier' ), tassili_number( $f['pmin'] ) ), array( 'pmin' => 0 ) );
	}
	if ( $f['pmax'] ) {
		/* translators: %s: amount in DA. */
		$add( sprintf( __( 'Up to %s DA', 'tassili-immobilier' ), tassili_number( $f['pmax'] ) ), array( 'pmax' => 0 ) );
	}
	if ( $f['smin'] ) {
		/* translators: %s: surface in square metres. */
		$add( sprintf( __( '%s m² and more', 'tassili-immobilier' ), tassili_number( $f['smin'] ) ), array( 'smin' => 0 ) );
	}
	$papers = tassili_paper_options();
	foreach ( $f['papers'] as $paper ) {
		$add( $papers[ $paper ], array( 'papers' => array_values( array_diff( $f['papers'], array( $paper ) ) ) ) );
	}
	foreach ( $f['amen'] as $slug ) {
		$term = get_term_by( 'slug', $slug, 'feature' );
		if ( $term instanceof WP_Term ) {
			$add( tassili_term_name( $term ), array( 'amen' => array_values( array_diff( $f['amen'], array( $slug ) ) ) ) );
		}
	}
	return $chips;
}
