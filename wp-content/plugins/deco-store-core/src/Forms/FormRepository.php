<?php
/**
 * Forms are stored as a private custom post type (native storage): title =
 * name, post meta = fields and settings as JSON. Never exposed through REST
 * or the front end by themselves.
 *
 * @package DecoStore
 */

namespace DecoStore\Forms;

defined( 'ABSPATH' ) || exit;

/**
 * CRUD for ds_form posts.
 */
class FormRepository {

	const POST_TYPE      = 'ds_form';
	const OPTION_DEFAULT = 'ds_default_form_id';

	/**
	 * Register the post type (private, no UI, no REST).
	 *
	 * @return void
	 */
	public static function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array( 'name' => __( 'النماذج', 'deco-store-core' ) ),
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
	 * Create a form from a definition (already normalised).
	 *
	 * @param array $definition Normalised definition.
	 * @return int Post id, or 0 on failure.
	 */
	public static function create( array $definition ) {
		$id = wp_insert_post(
			array(
				'post_type'   => self::POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => $definition['name'],
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			return 0;
		}
		self::write_meta( (int) $id, $definition, 1 );
		return (int) $id;
	}

	/**
	 * Update a form from a normalised definition; bumps its version.
	 *
	 * @param int   $id         Form id.
	 * @param array $definition Normalised definition.
	 * @return bool
	 */
	public static function update( $id, array $definition ) {
		if ( ! self::get( $id ) ) {
			return false;
		}
		wp_update_post(
			array(
				'ID'         => (int) $id,
				'post_title' => $definition['name'],
			)
		);
		self::write_meta( (int) $id, $definition, self::version( $id ) + 1 );
		return true;
	}

	/**
	 * Write meta.
	 *
	 * @param int   $id         Form id.
	 * @param array $definition Definition.
	 * @param int   $version    Version.
	 * @return void
	 */
	private static function write_meta( $id, array $definition, $version ) {
		update_post_meta( $id, '_ds_fields', wp_slash( wp_json_encode( $definition['fields'] ) ) );
		update_post_meta( $id, '_ds_settings', wp_slash( wp_json_encode( $definition['settings'] ) ) );
		update_post_meta( $id, '_ds_version', (int) $version );
	}

	/**
	 * Current version number (cache key for rendered forms).
	 *
	 * @param int $id Form id.
	 * @return int
	 */
	public static function version( $id ) {
		return (int) get_post_meta( (int) $id, '_ds_version', true );
	}

	/**
	 * Load a form definition. Stored data is re-normalised on load so an edited
	 * or corrupted record can never reach the renderer or validator.
	 *
	 * @param int $id Form id.
	 * @return array|null Definition plus `id` and `version`; null if missing.
	 */
	public static function get( $id ) {
		$post = get_post( (int) $id );
		if ( ! $post || self::POST_TYPE !== $post->post_type || 'publish' !== $post->post_status ) {
			return null;
		}
		$fields                = json_decode( (string) get_post_meta( $post->ID, '_ds_fields', true ), true );
		$settings              = json_decode( (string) get_post_meta( $post->ID, '_ds_settings', true ), true );
		$result                = FormSchema::normalize(
			array(
				'name'     => $post->post_title,
				'settings' => is_array( $settings ) ? $settings : array(),
				'fields'   => is_array( $fields ) ? $fields : array(),
			)
		);
		$definition            = $result['definition'];
		$definition['id']      = (int) $post->ID;
		$definition['version'] = self::version( $post->ID );
		$definition['valid']   = empty( $result['errors'] );
		return $definition;
	}

	/**
	 * All forms (id, name, field count, version), newest first.
	 *
	 * @return array[]
	 */
	public static function all() {
		$posts = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 100, // Forms are few; a hard cap keeps the list bounded.
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			)
		);
		$out   = array();
		foreach ( $posts as $post ) {
			$fields = json_decode( (string) get_post_meta( $post->ID, '_ds_fields', true ), true );
			$out[]  = array(
				'id'      => (int) $post->ID,
				'name'    => $post->post_title,
				'fields'  => is_array( $fields ) ? count( $fields ) : 0,
				'version' => self::version( $post->ID ),
			);
		}
		return $out;
	}

	/**
	 * Duplicate a form.
	 *
	 * @param int $id Source form id.
	 * @return int New id, or 0.
	 */
	public static function duplicate( $id ) {
		$form = self::get( $id );
		if ( ! $form ) {
			return 0;
		}
		/* translators: %s: form name. */
		$form['name'] = sprintf( __( '%s (نسخة)', 'deco-store-core' ), $form['name'] );
		$result       = FormSchema::normalize( $form );
		return self::create( $result['definition'] );
	}

	/**
	 * Delete a form permanently. The default form cannot be deleted.
	 *
	 * @param int $id Form id.
	 * @return bool
	 */
	public static function delete( $id ) {
		if ( self::default_id() === (int) $id || ! self::get( $id ) ) {
			return false;
		}
		return (bool) wp_delete_post( (int) $id, true );
	}

	/**
	 * Id of the default form (0 = none yet).
	 *
	 * @return int
	 */
	public static function default_id() {
		$id = (int) get_option( self::OPTION_DEFAULT, 0 );
		return $id && self::get( $id ) ? $id : 0;
	}

	/**
	 * Make a form the default.
	 *
	 * @param int $id Form id.
	 * @return bool
	 */
	public static function set_default( $id ) {
		if ( ! self::get( $id ) ) {
			return false;
		}
		update_option( self::OPTION_DEFAULT, (int) $id, false );
		return true;
	}

	/**
	 * Create the default form if the store has none.
	 *
	 * @return int Default form id.
	 */
	public static function ensure_default() {
		$id = self::default_id();
		if ( $id ) {
			return $id;
		}
		$existing = self::all();
		if ( $existing ) {
			self::set_default( $existing[0]['id'] );
			return $existing[0]['id'];
		}
		$result = FormSchema::normalize( DefaultForm::definition() );
		$id     = self::create( $result['definition'] );
		if ( $id ) {
			self::set_default( $id );
		}
		return $id;
	}
}
