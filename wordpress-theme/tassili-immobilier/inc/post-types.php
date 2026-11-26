<?php
/**
 * Custom post types: property, agent, testimonial and lead.
 *
 * Note: post types registered by a theme are available only while the theme is active.
 * Content is kept in the database when the theme is switched, and shows up again on reactivation.
 *
 * @package Tassili_Immobilier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register post types.
 */
function tassili_register_post_types() {
	/**
	 * Filters the URL slug of the property archive.
	 *
	 * @param string $slug Slug, "biens" by default.
	 */
	$slug = sanitize_title( apply_filters( 'tassili_property_slug', 'biens' ) );

	register_post_type(
		'property',
		array(
			'labels'              => array(
				'name'               => __( 'Properties', 'tassili-immobilier' ),
				'singular_name'      => __( 'Property', 'tassili-immobilier' ),
				'add_new'            => __( 'Add a property', 'tassili-immobilier' ),
				'add_new_item'       => __( 'Add new property', 'tassili-immobilier' ),
				'edit_item'          => __( 'Edit property', 'tassili-immobilier' ),
				'new_item'           => __( 'New property', 'tassili-immobilier' ),
				'view_item'          => __( 'View property', 'tassili-immobilier' ),
				'search_items'       => __( 'Search properties', 'tassili-immobilier' ),
				'not_found'          => __( 'No properties found.', 'tassili-immobilier' ),
				'not_found_in_trash' => __( 'No properties found in the Trash.', 'tassili-immobilier' ),
				'all_items'          => __( 'All properties', 'tassili-immobilier' ),
				'menu_name'          => _x( 'Properties', 'admin menu', 'tassili-immobilier' ),
			),
			'public'              => true,
			'has_archive'         => $slug,
			'rewrite'             => array(
				'slug'       => $slug,
				'with_front' => false,
			),
			'show_in_menu'        => true,
			'menu_position'       => 3,
			'menu_icon'           => 'dashicons-building',
			'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
			'show_in_rest'        => false,
			'capability_type'     => 'post',
			'exclude_from_search' => false,
		)
	);

	register_post_type(
		'agent',
		array(
			'labels'          => array(
				'name'               => __( 'Agents', 'tassili-immobilier' ),
				'singular_name'      => __( 'Agent', 'tassili-immobilier' ),
				'add_new_item'       => __( 'Add new agent', 'tassili-immobilier' ),
				'edit_item'          => __( 'Edit agent', 'tassili-immobilier' ),
				'all_items'          => __( 'All agents', 'tassili-immobilier' ),
				'menu_name'          => __( 'Agents', 'tassili-immobilier' ),
				'featured_image'     => __( 'Photo', 'tassili-immobilier' ),
				'set_featured_image' => __( 'Set photo', 'tassili-immobilier' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => 'tassili',
			'supports'        => array( 'title', 'thumbnail', 'page-attributes' ),
			'menu_icon'       => 'dashicons-businessperson',
			'capability_type' => 'post',
		)
	);

	register_post_type(
		'testimonial',
		array(
			'labels'          => array(
				'name'          => __( 'Testimonials', 'tassili-immobilier' ),
				'singular_name' => __( 'Testimonial', 'tassili-immobilier' ),
				'add_new_item'  => __( 'Add new testimonial', 'tassili-immobilier' ),
				'edit_item'     => __( 'Edit testimonial', 'tassili-immobilier' ),
				'all_items'     => __( 'All testimonials', 'tassili-immobilier' ),
				'menu_name'     => __( 'Testimonials', 'tassili-immobilier' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => 'tassili',
			'supports'        => array( 'title', 'editor', 'page-attributes' ),
			'capability_type' => 'post',
		)
	);

	// Contact and visit requests. Personal data, so only administrators can see them.
	register_post_type(
		'lead',
		array(
			'labels'              => array(
				'name'          => __( 'Leads', 'tassili-immobilier' ),
				'singular_name' => __( 'Lead', 'tassili-immobilier' ),
				'edit_item'     => __( 'View lead', 'tassili-immobilier' ),
				'all_items'     => __( 'Leads', 'tassili-immobilier' ),
				'menu_name'     => __( 'Leads', 'tassili-immobilier' ),
				'not_found'     => __( 'No leads yet.', 'tassili-immobilier' ),
				'search_items'  => __( 'Search leads', 'tassili-immobilier' ),
			),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_icon'           => 'dashicons-email-alt',
			'show_in_rest'        => false,
			'exclude_from_search' => true,
			'supports'            => array( 'title' ),
			'map_meta_cap'        => true,
			// Only administrators may see leads (they hold personal data). Map the primitive
			// capabilities only. Never put the meta capabilities edit_post, read_post or
			// delete_post here: WordPress would then treat "manage_options" itself as that
			// meta capability for the whole site.
			'capabilities'        => array(
				'edit_posts'             => 'manage_options',
				'edit_others_posts'      => 'manage_options',
				'edit_published_posts'   => 'manage_options',
				'edit_private_posts'     => 'manage_options',
				'publish_posts'          => 'manage_options',
				'read_private_posts'     => 'manage_options',
				'delete_posts'           => 'manage_options',
				'delete_others_posts'    => 'manage_options',
				'delete_published_posts' => 'manage_options',
				'delete_private_posts'   => 'manage_options',
				'create_posts'           => 'do_not_allow',
			),
		)
	);
}
add_action( 'init', 'tassili_register_post_types' );

/*
---------------------------------------------------------------------------------------
 * Admin list columns
 * ------------------------------------------------------------------------------------
 */

/**
 * Columns of the property list.
 *
 * @param string[] $columns Columns.
 * @return string[]
 */
function tassili_property_columns( $columns ) {
	$new = array();
	foreach ( $columns as $key => $label ) {
		if ( 'title' === $key ) {
			$new['tassili_thumb'] = __( 'Photo', 'tassili-immobilier' );
		}
		$new[ $key ] = $label;
		if ( 'title' === $key ) {
			$new['tassili_ref']   = __( 'Ref.', 'tassili-immobilier' );
			$new['tassili_price'] = __( 'Price (DA)', 'tassili-immobilier' );
		}
	}
	return $new;
}
add_filter( 'manage_property_posts_columns', 'tassili_property_columns' );

/**
 * Cells of the property list.
 *
 * @param string $column  Column key.
 * @param int    $post_id Post ID.
 */
function tassili_property_column_content( $column, $post_id ) {
	if ( 'tassili_thumb' === $column ) {
		$thumb = get_the_post_thumbnail( $post_id, array( 60, 45 ) );
		echo $thumb ? wp_kses_post( $thumb ) : '&mdash;'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static dash.
	} elseif ( 'tassili_ref' === $column ) {
		echo esc_html( (string) get_post_meta( $post_id, '_tassili_ref', true ) );
	} elseif ( 'tassili_price' === $column ) {
		$price = (int) get_post_meta( $post_id, '_tassili_price', true );
		echo $price ? esc_html( number_format_i18n( $price ) ) : '&mdash;'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static dash.
	}
}
add_action( 'manage_property_posts_custom_column', 'tassili_property_column_content', 10, 2 );

/**
 * Make price sortable.
 *
 * @param string[] $columns Sortable columns.
 * @return string[]
 */
function tassili_property_sortable_columns( $columns ) {
	$columns['tassili_price'] = 'tassili_price';
	$columns['tassili_ref']   = 'tassili_ref';
	return $columns;
}
add_filter( 'manage_edit-property_sortable_columns', 'tassili_property_sortable_columns' );

/**
 * Apply the admin sort.
 *
 * @param WP_Query $query Query.
 */
function tassili_property_admin_sort( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() || 'property' !== $query->get( 'post_type' ) ) {
		return;
	}
	$orderby = $query->get( 'orderby' );
	if ( 'tassili_price' === $orderby ) {
		$query->set( 'meta_key', '_tassili_price' );
		$query->set( 'orderby', 'meta_value_num' );
	} elseif ( 'tassili_ref' === $orderby ) {
		$query->set( 'meta_key', '_tassili_ref' );
		$query->set( 'orderby', 'meta_value' );
	}
}
add_action( 'pre_get_posts', 'tassili_property_admin_sort' );

/**
 * Columns of the lead list.
 *
 * @param string[] $columns Columns.
 * @return string[]
 */
function tassili_lead_columns( $columns ) {
	return array(
		'cb'              => isset( $columns['cb'] ) ? $columns['cb'] : '',
		'title'           => __( 'Name', 'tassili-immobilier' ),
		'tassili_kind'    => __( 'Type', 'tassili-immobilier' ),
		'tassili_phone'   => __( 'Phone', 'tassili-immobilier' ),
		'tassili_subject' => __( 'Subject / property', 'tassili-immobilier' ),
		'date'            => __( 'Date', 'tassili-immobilier' ),
	);
}
add_filter( 'manage_lead_posts_columns', 'tassili_lead_columns' );

/**
 * Cells of the lead list.
 *
 * @param string $column  Column key.
 * @param int    $post_id Post ID.
 */
function tassili_lead_column_content( $column, $post_id ) {
	if ( 'tassili_kind' === $column ) {
		echo esc_html( 'visit' === get_post_meta( $post_id, '_tassili_kind', true ) ? __( 'Visit request', 'tassili-immobilier' ) : __( 'Message', 'tassili-immobilier' ) );
	} elseif ( 'tassili_phone' === $column ) {
		echo esc_html( (string) get_post_meta( $post_id, '_tassili_phone', true ) );
	} elseif ( 'tassili_subject' === $column ) {
		$ref = (string) get_post_meta( $post_id, '_tassili_ref', true );
		$sub = (string) get_post_meta( $post_id, '_tassili_subject', true );
		echo esc_html( trim( $sub . ' ' . $ref ) );
	}
}
add_action( 'manage_lead_posts_custom_column', 'tassili_lead_column_content', 10, 2 );

/**
 * Edit listings with the classic editor: every field of a listing is visible straight away,
 * which suits agency staff better than the block editor's hidden panels.
 *
 * @param bool   $use_block_editor Whether the block editor is used.
 * @param string $post_type Post type.
 * @return bool
 */
function tassili_classic_editor_for_listings( $use_block_editor, $post_type ) {
	return 'property' === $post_type ? false : $use_block_editor;
}
add_filter( 'use_block_editor_for_post_type', 'tassili_classic_editor_for_listings', 10, 2 );
