<?php
/**
 * Native meta boxes (no plugin needed) for listings, agents, testimonials, and the lead viewer.
 *
 * Fields are declared once in tassili_meta_fields() and drive both the form and the saving,
 * so every value is sanitized by its declared type.
 *
 * @package Tassili_Immobilier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Field definitions per post type.
 *
 * Types: text, textarea, int, select, checkbox, checkboxes, agent, phone, digits.
 *
 * @param string $post_type Post type.
 * @return array<string,array<string,mixed>>
 */
function tassili_meta_fields( $post_type ) {
	switch ( $post_type ) {
		case 'property':
			return array(
				'ref'         => array(
					'box'   => 'details',
					'label' => __( 'Reference', 'tassili-immobilier' ),
					'type'  => 'text',
					'help'  => __( 'Leave empty to generate one automatically.', 'tassili-immobilier' ),
				),
				'price'       => array(
					'box'   => 'details',
					'label' => __( 'Price (DA)', 'tassili-immobilier' ),
					'type'  => 'int',
					'help'  => __( 'Sale price, or monthly rent for rentals.', 'tassili-immobilier' ),
				),
				'surface'     => array(
					'box'   => 'details',
					'label' => __( 'Surface (m²)', 'tassili-immobilier' ),
					'type'  => 'int',
				),
				'land'        => array(
					'box'   => 'details',
					'label' => __( 'Land area (m²)', 'tassili-immobilier' ),
					'type'  => 'int',
				),
				'typo'        => array(
					'box'     => 'details',
					'label'   => __( 'Typology', 'tassili-immobilier' ),
					'type'    => 'select',
					'options' => tassili_typology_options(),
				),
				'rooms'       => array(
					'box'   => 'details',
					'label' => __( 'Rooms', 'tassili-immobilier' ),
					'type'  => 'int',
				),
				'beds'        => array(
					'box'   => 'details',
					'label' => __( 'Bedrooms', 'tassili-immobilier' ),
					'type'  => 'int',
				),
				'baths'       => array(
					'box'   => 'details',
					'label' => __( 'Bathrooms', 'tassili-immobilier' ),
					'type'  => 'int',
				),
				'floor'       => array(
					'box'   => 'details',
					'label' => __( 'Floor', 'tassili-immobilier' ),
					'type'  => 'int',
					'empty' => true,
					'help'  => __( '0 = ground floor. Leave empty if not applicable.', 'tassili-immobilier' ),
				),
				'floors'      => array(
					'box'   => 'details',
					'label' => __( 'Number of levels', 'tassili-immobilier' ),
					'type'  => 'int',
				),
				'year'        => array(
					'box'   => 'details',
					'label' => __( 'Year built', 'tassili-immobilier' ),
					'type'  => 'int',
				),
				'delivery'    => array(
					'box'   => 'details',
					'label' => __( 'Delivery date (new developments)', 'tassili-immobilier' ),
					'type'  => 'text',
				),
				'is_new'      => array(
					'box'   => 'flags',
					'label' => __( 'New development', 'tassili-immobilier' ),
					'type'  => 'checkbox',
				),
				'featured'    => array(
					'box'   => 'flags',
					'label' => __( 'Show on the home page', 'tassili-immobilier' ),
					'type'  => 'checkbox',
				),
				'exclusive'   => array(
					'box'   => 'flags',
					'label' => __( 'Exclusive listing', 'tassili-immobilier' ),
					'type'  => 'checkbox',
				),
				'status'      => array(
					'box'   => 'flags',
					'label' => __( 'Availability', 'tassili-immobilier' ),
					'type'  => 'status',
				),
				'agent'       => array(
					'box'   => 'flags',
					'label' => __( 'Agent in charge', 'tassili-immobilier' ),
					'type'  => 'agent',
				),
				'papers'      => array(
					'box'     => 'papers',
					'label'   => __( 'Documents available', 'tassili-immobilier' ),
					'type'    => 'checkboxes',
					'options' => tassili_paper_options(),
				),
				'title_ar'    => array(
					'box'   => 'arabic',
					'label' => __( 'Title in Arabic', 'tassili-immobilier' ),
					'type'  => 'text',
					'rtl'   => true,
				),
				'desc_ar'     => array(
					'box'   => 'arabic',
					'label' => __( 'Description in Arabic', 'tassili-immobilier' ),
					'type'  => 'textarea',
					'rtl'   => true,
				),
				'delivery_ar' => array(
					'box'   => 'arabic',
					'label' => __( 'Delivery date in Arabic', 'tassili-immobilier' ),
					'type'  => 'text',
					'rtl'   => true,
				),
			);
		case 'page':
		case 'post':
			return array(
				'title_ar' => array(
					'box'   => 'arabic_page',
					'label' => __( 'Title in Arabic', 'tassili-immobilier' ),
					'type'  => 'text',
					'rtl'   => true,
				),
			);
		case 'agent':
			return array(
				'role'     => array(
					'box'   => 'agent',
					'label' => __( 'Role', 'tassili-immobilier' ),
					'type'  => 'text',
				),
				'role_ar'  => array(
					'box'   => 'agent',
					'label' => __( 'Role in Arabic', 'tassili-immobilier' ),
					'type'  => 'text',
					'rtl'   => true,
				),
				'phone'    => array(
					'box'   => 'agent',
					'label' => __( 'Phone', 'tassili-immobilier' ),
					'type'  => 'phone',
				),
				'whatsapp' => array(
					'box'   => 'agent',
					'label' => __( 'WhatsApp number', 'tassili-immobilier' ),
					'type'  => 'digits',
					'help'  => __( 'International format, digits only, for example 213555123456.', 'tassili-immobilier' ),
				),
				'langs'    => array(
					'box'   => 'agent',
					'label' => __( 'Languages spoken', 'tassili-immobilier' ),
					'type'  => 'text',
				),
				'langs_ar' => array(
					'box'   => 'agent',
					'label' => __( 'Languages spoken, in Arabic', 'tassili-immobilier' ),
					'type'  => 'text',
					'rtl'   => true,
				),
			);
		case 'testimonial':
			return array(
				'place'    => array(
					'box'   => 'testimonial',
					'label' => __( 'Role and place', 'tassili-immobilier' ),
					'type'  => 'text',
					'help'  => __( 'For example "Buyers, Alger".', 'tassili-immobilier' ),
				),
				'place_ar' => array(
					'box'   => 'testimonial',
					'label' => __( 'Role and place in Arabic', 'tassili-immobilier' ),
					'type'  => 'text',
					'rtl'   => true,
				),
				'title_ar' => array(
					'box'   => 'testimonial',
					'label' => __( 'Name in Arabic', 'tassili-immobilier' ),
					'type'  => 'text',
					'rtl'   => true,
				),
				'quote_ar' => array(
					'box'   => 'testimonial',
					'label' => __( 'Testimonial in Arabic', 'tassili-immobilier' ),
					'type'  => 'textarea',
					'rtl'   => true,
				),
			);
	}
	return array();
}

/**
 * Register meta so WordPress knows the types and sanitizers.
 */
function tassili_register_meta() {
	foreach ( array( 'property', 'agent', 'testimonial', 'page', 'post' ) as $post_type ) {
		foreach ( tassili_meta_fields( $post_type ) as $key => $field ) {
			register_post_meta(
				$post_type,
				'_tassili_' . $key,
				array(
					'single'            => true,
					'type'              => in_array( $field['type'], array( 'int', 'agent', 'checkbox' ), true ) ? 'integer' : ( 'checkboxes' === $field['type'] ? 'array' : 'string' ),
					'sanitize_callback' => static function ( $value ) use ( $field ) {
						return tassili_sanitize_field( $value, $field );
					},
					'auth_callback'     => static function () {
						return current_user_can( 'edit_posts' );
					},
					'show_in_rest'      => false,
				)
			);
		}
	}
	register_post_meta(
		'property',
		'_tassili_gallery',
		array(
			'single'            => true,
			'type'              => 'string',
			'sanitize_callback' => 'tassili_sanitize_id_list',
			'auth_callback'     => static function () {
				return current_user_can( 'edit_posts' );
			},
			'show_in_rest'      => false,
		)
	);
}
add_action( 'init', 'tassili_register_meta' );

/**
 * Clean a comma-separated list of attachment IDs.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function tassili_sanitize_id_list( $value ) {
	$ids = array_filter( array_map( 'absint', explode( ',', (string) $value ) ) );
	return implode( ',', array_unique( $ids ) );
}

/**
 * Sanitize one value according to its field definition.
 *
 * @param mixed $value Raw value.
 * @param array $field Field definition.
 * @return mixed
 */
function tassili_sanitize_field( $value, $field ) {
	switch ( $field['type'] ) {
		case 'int':
			if ( '' === $value || null === $value ) {
				return '';
			}
			return (int) $value;
		case 'status':
			return tassili_normalize_status( (string) $value );
		case 'agent':
			return absint( $value );
		case 'checkbox':
			return $value ? 1 : 0;
		case 'select':
			return in_array( $value, $field['options'], true ) ? $value : '';
		case 'checkboxes':
			$allowed = array_keys( $field['options'] );
			return array_values( array_intersect( $allowed, array_map( 'sanitize_key', (array) $value ) ) );
		case 'textarea':
			return sanitize_textarea_field( (string) $value );
		case 'phone':
			return trim( preg_replace( '/[^0-9+\s().\-]/', '', (string) $value ) );
		case 'digits':
			return preg_replace( '/\D+/', '', (string) $value );
		default:
			return sanitize_text_field( (string) $value );
	}
}

/**
 * Generate a unique listing reference such as TS-1042.
 *
 * @param int $post_id Listing being saved.
 * @return string
 */
function tassili_generate_ref( $post_id ) {
	$prefix = strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', (string) tassili_mod( 'ref_prefix' ) ) );
	$prefix = '' !== $prefix ? $prefix : 'TS';
	$n      = max( 1000, (int) get_option( 'tassili_ref_counter', 1000 ) );
	do {
		++$n;
		$ref   = $prefix . '-' . $n;
		$taken = get_posts(
			array(
				'post_type'      => 'property',
				'post_status'    => 'any',
				'meta_key'       => '_tassili_ref', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- tiny lookup, runs once per new listing.
				'meta_value'     => $ref, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'fields'         => 'ids',
				'posts_per_page' => 1,
				'post__not_in'   => array( $post_id ),
			)
		);
	} while ( $taken );
	update_option( 'tassili_ref_counter', $n, false );
	return $ref;
}

/*
---------------------------------------------------------------------------------------
 * Rendering
 * ------------------------------------------------------------------------------------
 */

/**
 * Print one field.
 *
 * @param int    $post_id Post ID.
 * @param string $key     Field key.
 * @param array  $field   Definition.
 */
function tassili_render_field( $post_id, $key, $field ) {
	$name  = 'tassili_meta[' . $key . ']';
	$id    = 'tassili-meta-' . $key;
	$value = get_post_meta( $post_id, '_tassili_' . $key, true );
	$dir   = ! empty( $field['rtl'] ) ? ' dir="rtl"' : '';

	echo '<div class="tassili-field tassili-field--' . esc_attr( $field['type'] ) . '">';

	if ( 'checkbox' === $field['type'] ) {
		printf(
			'<label for="%1$s"><input type="checkbox" id="%1$s" name="%2$s" value="1"%3$s /> %4$s</label>',
			esc_attr( $id ),
			esc_attr( $name ),
			checked( ! empty( $value ), true, false ),
			esc_html( $field['label'] )
		);
	} elseif ( 'checkboxes' === $field['type'] ) {
		echo '<span class="tassili-field__label">' . esc_html( $field['label'] ) . '</span><span class="tassili-checks">';
		$value = is_array( $value ) ? $value : array();
		foreach ( $field['options'] as $option => $label ) {
			printf(
				'<label><input type="checkbox" name="%1$s[]" value="%2$s"%3$s /> %4$s</label>',
				esc_attr( $name ),
				esc_attr( $option ),
				checked( in_array( $option, $value, true ), true, false ),
				esc_html( $label )
			);
		}
		echo '</span>';
	} else {
		printf( '<label for="%1$s" class="tassili-field__label">%2$s</label>', esc_attr( $id ), esc_html( $field['label'] ) );
		if ( 'textarea' === $field['type'] ) {
			printf( '<textarea id="%1$s" name="%2$s" rows="5" class="widefat"%4$s>%3$s</textarea>', esc_attr( $id ), esc_attr( $name ), esc_textarea( (string) $value ), $dir ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $dir is a fixed attribute string.
		} elseif ( 'select' === $field['type'] ) {
			printf( '<select id="%1$s" name="%2$s"><option value="">—</option>', esc_attr( $id ), esc_attr( $name ) );
			foreach ( $field['options'] as $option ) {
				printf( '<option value="%1$s"%2$s>%1$s</option>', esc_attr( $option ), selected( $value, $option, false ) );
			}
			echo '</select>';
		} elseif ( 'status' === $field['type'] ) {
			printf( '<select id="%1$s" name="%2$s">', esc_attr( $id ), esc_attr( $name ) );
			foreach ( tassili_status_options() as $status_key => $status_label ) {
				printf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $status_key ), selected( tassili_normalize_status( (string) $value ), $status_key, false ), esc_html( $status_label ) );
			}
			echo '</select>';
		} elseif ( 'agent' === $field['type'] ) {
			printf( '<select id="%1$s" name="%2$s"><option value="0">—</option>', esc_attr( $id ), esc_attr( $name ) );
			$agents = get_posts(
				array(
					'post_type'      => 'agent',
					'posts_per_page' => 100,
					'orderby'        => 'title',
					'order'          => 'ASC',
					'post_status'    => 'publish',
				)
			);
			foreach ( $agents as $agent ) {
				printf( '<option value="%1$d"%2$s>%3$s</option>', (int) $agent->ID, selected( (int) $value, (int) $agent->ID, false ), esc_html( $agent->post_title ) );
			}
			echo '</select>';
		} else {
			$type = 'int' === $field['type'] ? 'number' : 'text';
			$step = 'int' === $field['type'] ? ' step="1"' : '';
			printf( '<input type="%1$s" id="%2$s" name="%3$s" value="%4$s" class="widefat"%5$s%6$s />', esc_attr( $type ), esc_attr( $id ), esc_attr( $name ), esc_attr( (string) $value ), $step, $dir ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $step and $dir are fixed attribute strings.
		}
		if ( ! empty( $field['help'] ) ) {
			echo '<span class="description">' . esc_html( $field['help'] ) . '</span>';
		}
	}
	echo '</div>';
}

/**
 * Print every field of a box.
 *
 * @param WP_Post $post Post.
 * @param array   $args Box arguments (args => array( post_type, box )).
 */
function tassili_render_box( $post, $args ) {
	$post_type = $args['args']['post_type'];
	$box       = $args['args']['box'];
	wp_nonce_field( 'tassili_save_meta', 'tassili_meta_nonce' );
	echo '<div class="tassili-fields tassili-fields--' . esc_attr( $box ) . '">';
	foreach ( tassili_meta_fields( $post_type ) as $key => $field ) {
		if ( $box === $field['box'] ) {
			tassili_render_field( $post->ID, $key, $field );
		}
	}
	echo '</div>';
}

/**
 * Print the gallery box.
 *
 * @param WP_Post $post Post.
 */
function tassili_render_gallery_box( $post ) {
	$value = (string) get_post_meta( $post->ID, '_tassili_gallery', true );
	$ids   = array_filter( array_map( 'absint', explode( ',', $value ) ) );
	echo '<p class="description">' . esc_html__( 'The featured image is the first photo. Add more here and drag to reorder.', 'tassili-immobilier' ) . '</p>';
	echo '<ul class="tassili-gallery" id="tassili-gallery-list">';
	foreach ( $ids as $id ) {
		$img = wp_get_attachment_image( $id, 'thumbnail' );
		if ( $img ) {
			printf(
				'<li data-id="%1$d">%2$s<button type="button" class="button-link tassili-gallery__remove" aria-label="%3$s">&times;</button></li>',
				(int) $id,
				wp_kses_post( $img ),
				esc_attr__( 'Remove photo', 'tassili-immobilier' )
			);
		}
	}
	echo '</ul>';
	printf( '<input type="hidden" id="tassili-gallery-ids" name="tassili_gallery" value="%s" />', esc_attr( implode( ',', $ids ) ) );
	printf( '<button type="button" class="button" id="tassili-gallery-add" data-title="%1$s" data-button="%2$s">%3$s</button>', esc_attr__( 'Choose photos', 'tassili-immobilier' ), esc_attr__( 'Add to gallery', 'tassili-immobilier' ), esc_html__( 'Add photos', 'tassili-immobilier' ) );
}

/**
 * Print the Arabic version box of a page or post: title and content.
 *
 * @param WP_Post $post Post.
 */
function tassili_render_arabic_page_box( $post ) {
	wp_nonce_field( 'tassili_save_meta', 'tassili_meta_nonce' );
	echo '<div class="tassili-fields tassili-fields--arabic_page">';
	tassili_render_field( $post->ID, 'title_ar', tassili_meta_fields( 'page' )['title_ar'] );
	echo '<p class="description">' . esc_html__( 'Shown instead of the main text when the visitor reads the site in Arabic. Leave empty to show the main text.', 'tassili-immobilier' ) . '</p>';
	echo '<span class="tassili-field__label">' . esc_html__( 'Content in Arabic', 'tassili-immobilier' ) . '</span>';
	wp_editor(
		(string) get_post_meta( $post->ID, '_tassili_content_ar', true ),
		'tassili_content_ar',
		array(
			'textarea_name' => 'tassili_content_ar',
			'textarea_rows' => 10,
			'media_buttons' => true,
			'tinymce'       => array( 'directionality' => 'rtl' ),
		)
	);
	echo '</div>';
}

/**
 * Print the lead viewer (read-only).
 *
 * @param WP_Post $post Post.
 */
function tassili_render_lead_box( $post ) {
	$rows = array(
		'_tassili_kind'    => __( 'Type', 'tassili-immobilier' ),
		'_tassili_phone'   => __( 'Phone', 'tassili-immobilier' ),
		'_tassili_email'   => __( 'E-mail', 'tassili-immobilier' ),
		'_tassili_subject' => __( 'Subject', 'tassili-immobilier' ),
		'_tassili_ref'     => __( 'Property reference', 'tassili-immobilier' ),
		'_tassili_date'    => __( 'Visit date', 'tassili-immobilier' ),
		'_tassili_slot'    => __( 'Time slot', 'tassili-immobilier' ),
		'_tassili_lang'    => __( 'Language', 'tassili-immobilier' ),
	);
	echo '<table class="widefat striped"><tbody>';
	foreach ( $rows as $meta_key => $label ) {
		$value = (string) get_post_meta( $post->ID, $meta_key, true );
		if ( '' === $value ) {
			continue;
		}
		echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td>' . esc_html( $value ) . '</td></tr>';
	}
	echo '</tbody></table>';
	echo '<h3>' . esc_html__( 'Message', 'tassili-immobilier' ) . '</h3>';
	echo '<p>' . nl2br( esc_html( (string) get_post_meta( $post->ID, '_tassili_message', true ) ) ) . '</p>';
}

/**
 * Add the meta boxes.
 */
function tassili_add_meta_boxes() {
	$boxes = array(
		'property'    => array(
			'details' => array( __( 'Property details', 'tassili-immobilier' ), 'normal', 'high' ),
			'papers'  => array( __( 'Documents', 'tassili-immobilier' ), 'normal', 'default' ),
			'arabic'  => array( __( 'Arabic version', 'tassili-immobilier' ), 'normal', 'default' ),
			'flags'   => array( __( 'Publishing', 'tassili-immobilier' ), 'side', 'default' ),
		),
		'agent'       => array(
			'agent' => array( __( 'Agent details', 'tassili-immobilier' ), 'normal', 'high' ),
		),
		'testimonial' => array(
			'testimonial' => array( __( 'Testimonial details', 'tassili-immobilier' ), 'normal', 'high' ),
		),
	);
	foreach ( $boxes as $post_type => $list ) {
		foreach ( $list as $box => $def ) {
			add_meta_box(
				'tassili-' . $box,
				$def[0],
				'tassili_render_box',
				$post_type,
				$def[1],
				$def[2],
				array(
					'post_type' => $post_type,
					'box'       => $box,
				)
			);
		}
	}
	foreach ( array( 'page', 'post' ) as $page_type ) {
		add_meta_box( 'tassili-arabic-page', __( 'Arabic version', 'tassili-immobilier' ), 'tassili_render_arabic_page_box', $page_type, 'normal', 'default' );
	}
	add_meta_box( 'tassili-gallery', __( 'Photo gallery', 'tassili-immobilier' ), 'tassili_render_gallery_box', 'property', 'normal', 'default' );
	add_meta_box( 'tassili-lead', __( 'Request details', 'tassili-immobilier' ), 'tassili_render_lead_box', 'lead', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'tassili_add_meta_boxes' );

/*
---------------------------------------------------------------------------------------
 * Saving
 * ------------------------------------------------------------------------------------
 */

/**
 * Save the fields of a listing, agent or testimonial.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post.
 */
function tassili_save_meta( $post_id, $post ) {
	if ( ! isset( $_POST['tassili_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tassili_meta_nonce'] ) ), 'tassili_save_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- each value is sanitized by tassili_sanitize_field() below.
	$posted = isset( $_POST['tassili_meta'] ) && is_array( $_POST['tassili_meta'] ) ? wp_unslash( $_POST['tassili_meta'] ) : array();

	foreach ( tassili_meta_fields( $post->post_type ) as $key => $field ) {
		$raw   = isset( $posted[ $key ] ) ? $posted[ $key ] : ( in_array( $field['type'], array( 'checkbox', 'checkboxes' ), true ) ? array() : '' );
		$value = tassili_sanitize_field( $raw, $field );

		if ( 'ref' === $key ) {
			if ( '' === $value ) {
				$value = tassili_generate_ref( $post_id );
			} else {
				$value = strtoupper( $value );
			}
		}
		if ( 'int' === $field['type'] && '' === $value ) {
			// Price and surface must exist so listings sort and filter reliably.
			if ( in_array( $key, array( 'price', 'surface' ), true ) ) {
				$value = 0;
			} else {
				delete_post_meta( $post_id, '_tassili_' . $key );
				continue;
			}
		}
		if ( '' === $value && 'text' === $field['type'] ) {
			delete_post_meta( $post_id, '_tassili_' . $key );
			continue;
		}
		update_post_meta( $post_id, '_tassili_' . $key, $value );
	}

	if ( in_array( $post->post_type, array( 'page', 'post' ), true ) && isset( $_POST['tassili_content_ar'] ) ) {
		$content_ar = wp_kses_post( wp_unslash( $_POST['tassili_content_ar'] ) );
		if ( '' === trim( $content_ar ) ) {
			delete_post_meta( $post_id, '_tassili_content_ar' );
		} else {
			update_post_meta( $post_id, '_tassili_content_ar', $content_ar );
		}
	}

	if ( 'property' === $post->post_type && isset( $_POST['tassili_gallery'] ) ) {
		update_post_meta( $post_id, '_tassili_gallery', tassili_sanitize_id_list( sanitize_text_field( wp_unslash( $_POST['tassili_gallery'] ) ) ) );
	}
}
add_action( 'save_post_property', 'tassili_save_meta', 10, 2 );
add_action( 'save_post_agent', 'tassili_save_meta', 10, 2 );
add_action( 'save_post_testimonial', 'tassili_save_meta', 10, 2 );
add_action( 'save_post_page', 'tassili_save_meta', 10, 2 );
add_action( 'save_post_post', 'tassili_save_meta', 10, 2 );

/*
---------------------------------------------------------------------------------------
 * Admin assets
 * ------------------------------------------------------------------------------------
 */

/**
 * Load the gallery picker and field styles on our edit screens.
 *
 * @param string $hook Admin page.
 */
function tassili_admin_assets( $hook ) {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php', 'edit-tags.php', 'term.php', 'edit.php', 'index.php', 'toplevel_page_tassili' ), true ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen ) {
		return;
	}
	$ours = in_array( $screen->post_type, array( 'property', 'agent', 'testimonial', 'lead', 'page', 'post' ), true ) || in_array( $hook, array( 'toplevel_page_tassili', 'index.php' ), true );
	if ( ! $ours ) {
		return;
	}
	wp_enqueue_style( 'tassili-admin', TASSILI_URI . 'assets/css/admin.css', array(), TASSILI_VERSION );
	if ( 'property' === $screen->post_type && in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		wp_enqueue_media();
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'tassili-admin-gallery', TASSILI_URI . 'assets/js/admin-gallery.js', array( 'jquery', 'jquery-ui-sortable' ), TASSILI_VERSION, true );
	}
}
add_action( 'admin_enqueue_scripts', 'tassili_admin_assets' );
