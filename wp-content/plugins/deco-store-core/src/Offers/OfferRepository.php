<?php
/**
 * Offers as a private custom post type (native storage): title = label,
 * `_ds_offer` = definition JSON (underscore meta: not exposed by REST).
 * Every save bumps the version; editing an offer never changes past orders
 * because orders store a snapshot (Phase 5).
 *
 * @package DecoStore
 */

namespace DecoStore\Offers;

defined( 'ABSPATH' ) || exit;

/**
 * CRUD for ds_offer posts.
 */
class OfferRepository {

	const POST_TYPE    = 'ds_offer';
	const META         = '_ds_offer';
	const META_VERSION = '_ds_offer_version';

	/**
	 * Register the post type (private, no UI, no REST).
	 *
	 * @return void
	 */
	public static function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array( 'name' => __( 'العروض', 'deco-store-core' ) ),
				'public'              => false,
				'show_ui'             => false,
				'show_in_rest'        => false,
				'show_in_menu'        => false,
				'exclude_from_search' => true,
				'publicly_queryable'  => false,
				'rewrite'             => false,
				'query_var'           => false,
				'supports'            => array( 'title' ),
				'can_export'          => true,
			)
		);
	}

	/**
	 * Create an offer from a normalised definition.
	 *
	 * @param array $definition Normalised definition.
	 * @return int Post id, 0 on failure.
	 */
	public static function create( array $definition ) {
		$id = wp_insert_post(
			array(
				'post_type'   => self::POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => $definition['label'],
			),
			true
		);
		if ( is_wp_error( $id ) || ! $id ) {
			return 0;
		}
		self::store( (int) $id, $definition, 1 );
		return (int) $id;
	}

	/**
	 * Replace an offer's definition and bump its version.
	 *
	 * @param int   $id         Offer id.
	 * @param array $definition Normalised definition.
	 * @return bool
	 */
	public static function update( $id, array $definition ) {
		$current = self::get( $id );
		if ( ! $current ) {
			return false;
		}
		wp_update_post(
			array(
				'ID'         => (int) $id,
				'post_title' => $definition['label'],
			)
		);
		self::store( (int) $id, $definition, $current['version'] + 1 );
		return true;
	}

	/**
	 * Write meta (wp_slash because update_post_meta unslashes).
	 *
	 * @param int   $id         Id.
	 * @param array $definition Definition.
	 * @param int   $version    Version.
	 * @return void
	 */
	private static function store( $id, array $definition, $version ) {
		update_post_meta( $id, self::META, wp_slash( wp_json_encode( $definition ) ) );
		update_post_meta( $id, self::META_VERSION, (int) $version );
	}

	/**
	 * One offer: normalised definition + id + version. A stored definition
	 * that no longer validates is forced to draft so it can never apply.
	 *
	 * @param int $id Offer id.
	 * @return array|null
	 */
	public static function get( $id ) {
		$post = get_post( (int) $id );
		if ( ! $post || self::POST_TYPE !== $post->post_type || 'publish' !== $post->post_status ) {
			return null;
		}
		$raw    = json_decode( (string) get_post_meta( $post->ID, self::META, true ), true );
		$result = OfferSchema::normalize( is_array( $raw ) ? $raw : array() );
		$offer  = $result['definition'];
		if ( $result['errors'] ) {
			$offer['status']  = 'draft';
			$offer['invalid'] = true;
		}
		$offer['id']      = (int) $post->ID;
		$offer['version'] = max( 1, (int) get_post_meta( $post->ID, self::META_VERSION, true ) );
		return $offer;
	}

	/**
	 * All offers, newest first.
	 *
	 * @return array[]
	 */
	public static function all() {
		$ids = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 200, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- Offers are few; bounded list.
				'orderby'        => 'ID',
				'order'          => 'DESC',
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		return array_values( array_filter( array_map( array( __CLASS__, 'get' ), $ids ) ) );
	}

	/**
	 * Copy an offer as a draft.
	 *
	 * @param int $id Offer id.
	 * @return int New id, 0 on failure.
	 */
	public static function duplicate( $id ) {
		$offer = self::get( $id );
		if ( ! $offer ) {
			return 0;
		}
		$offer['label']  = mb_substr( $offer['label'], 0, 90 ) . ' ' . __( '(نسخة)', 'deco-store-core' );
		$offer['status'] = 'draft';
		unset( $offer['id'], $offer['version'], $offer['invalid'] );
		return self::create( OfferSchema::normalize( $offer )['definition'] );
	}

	/**
	 * Change only the status.
	 *
	 * @param int    $id     Offer id.
	 * @param string $status draft | active | archived.
	 * @return bool
	 */
	public static function set_status( $id, $status ) {
		$offer = self::get( $id );
		if ( ! $offer || ! in_array( $status, OfferSchema::STATUSES, true ) ) {
			return false;
		}
		unset( $offer['id'], $offer['version'], $offer['invalid'] );
		$offer['status'] = $status;
		$result          = OfferSchema::normalize( $offer );
		return $result['errors'] ? false : self::update( $id, $result['definition'] );
	}

	/**
	 * Permanently delete an offer.
	 *
	 * @param int $id Offer id.
	 * @return bool
	 */
	public static function delete( $id ) {
		return self::get( $id ) && (bool) wp_delete_post( (int) $id, true );
	}
}
