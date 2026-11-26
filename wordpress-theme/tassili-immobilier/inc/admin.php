<?php
/**
 * Back office: one "Properties" menu, an overview page, a richer listing screen (status, home
 * page star, filters, quick actions, bulk actions, duplicate) and a dashboard widget.
 *
 * @package Tassili_Immobilier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
---------------------------------------------------------------------------------------
 * Menu
 * ------------------------------------------------------------------------------------
 */

/**
 * Add the top-level menu that holds every screen of the theme.
 */
function tassili_admin_menu() {
	add_menu_page(
		__( 'Properties', 'tassili-immobilier' ),
		__( 'Properties', 'tassili-immobilier' ),
		'edit_posts',
		'tassili',
		'tassili_render_overview',
		'dashicons-admin-home',
		5
	);
	add_submenu_page( 'tassili', __( 'Overview', 'tassili-immobilier' ), __( 'Overview', 'tassili-immobilier' ), 'edit_posts', 'tassili', 'tassili_render_overview' );
	// WordPress only adds these itself when a post type owns its own top-level menu.
	add_submenu_page( 'tassili', __( 'Add new property', 'tassili-immobilier' ), __( 'Add new', 'tassili-immobilier' ), 'edit_posts', 'post-new.php?post_type=property' );
	foreach ( array( 'transaction', 'wilaya', 'property_type', 'feature' ) as $taxonomy ) {
		$tax_object = get_taxonomy( $taxonomy );
		if ( $tax_object ) {
			add_submenu_page( 'tassili', $tax_object->labels->menu_name, $tax_object->labels->menu_name, $tax_object->cap->manage_terms, 'edit-tags.php?taxonomy=' . $taxonomy . '&post_type=property' );
		}
	}
	add_submenu_page( 'tassili', __( 'Site settings', 'tassili-immobilier' ), __( 'Site settings', 'tassili-immobilier' ), 'edit_theme_options', 'customize.php?autofocus[panel]=tassili' );
}
add_action( 'admin_menu', 'tassili_admin_menu' );

/**
 * Put "Overview" first in the submenu.
 */
function tassili_reorder_submenu() {
	global $submenu;
	if ( empty( $submenu['tassili'] ) ) {
		return;
	}
	$order = array(
		'tassili',
		'edit.php?post_type=property',
		'post-new.php?post_type=property',
		'edit-tags.php?taxonomy=transaction&post_type=property',
		'edit-tags.php?taxonomy=wilaya&post_type=property',
		'edit-tags.php?taxonomy=property_type&post_type=property',
		'edit-tags.php?taxonomy=feature&post_type=property',
		'edit.php?post_type=agent',
		'edit.php?post_type=testimonial',
		'edit.php?post_type=lead',
		'customize.php?autofocus[panel]=tassili',
	);
	$items = $submenu['tassili'];
	usort(
		$items,
		static function ( $a, $b ) use ( $order ) {
			$pa = array_search( $a[2], $order, true );
			$pb = array_search( $b[2], $order, true );
			$pa = false === $pa ? 99 : $pa;
			$pb = false === $pb ? 99 : $pb;
			return $pa <=> $pb;
		}
	);
	$submenu['tassili'] = array_values( $items ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- reordering our own menu.
}
add_action( 'admin_menu', 'tassili_reorder_submenu', 999 );

/**
 * Keep the "Properties" menu open on every screen that belongs to it.
 *
 * @param string $parent_file Current parent menu.
 * @return string
 */
function tassili_menu_parent_file( $parent_file ) {
	$screen = get_current_screen();
	if ( $screen && in_array( $screen->post_type, array( 'property', 'agent', 'testimonial', 'lead' ), true ) && in_array( $screen->base, array( 'post', 'edit', 'edit-tags', 'term' ), true ) ) {
		return 'tassili';
	}
	return $parent_file;
}
add_filter( 'parent_file', 'tassili_menu_parent_file' );

/**
 * Highlight the right submenu entry on term screens.
 *
 * @param string|null $submenu_file Current submenu file.
 * @return string|null
 */
function tassili_menu_submenu_file( $submenu_file ) {
	$screen = get_current_screen();
	if ( $screen && in_array( $screen->base, array( 'edit-tags', 'term' ), true ) && 'property' === $screen->post_type ) {
		return 'edit-tags.php?taxonomy=' . $screen->taxonomy . '&post_type=property';
	}
	return $submenu_file;
}
add_filter( 'submenu_file', 'tassili_menu_submenu_file' );

/*
---------------------------------------------------------------------------------------
 * Overview page
 * ------------------------------------------------------------------------------------
 */

/**
 * Numbers shown on the overview.
 *
 * @return array<string,int>
 */
function tassili_overview_stats() {
	$counts    = wp_count_posts( 'property' );
	$by        = static function ( $status ) {
		$query = new WP_Query(
			array(
				'post_type'      => 'property',
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'posts_per_page' => 1,
				'meta_key'       => '_tassili_status', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- counts on one admin screen.
				'meta_value'     => $status, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
		return (int) $query->found_posts;
	};
	$published = (int) $counts->publish;
	$reserved  = $by( 'reserved' );
	$closed    = $by( 'closed' );

	$new_leads = 0;
	if ( current_user_can( 'manage_options' ) ) {
		$leads     = new WP_Query(
			array(
				'post_type'      => 'lead',
				'post_status'    => 'private',
				'fields'         => 'ids',
				'posts_per_page' => 1,
				'date_query'     => array( array( 'after' => '30 days ago' ) ),
			)
		);
		$new_leads = (int) $leads->found_posts;
	}

	return array(
		'published' => $published,
		'available' => max( 0, $published - $reserved - $closed ),
		'reserved'  => $reserved,
		'closed'    => $closed,
		'drafts'    => (int) $counts->draft + (int) $counts->pending,
		'leads'     => $new_leads,
	);
}

/**
 * Print the overview page.
 */
function tassili_render_overview() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}
	$stats    = tassili_overview_stats();
	$can_set  = current_user_can( 'edit_theme_options' );
	$is_admin = current_user_can( 'manage_options' );

	$cards = array(
		array( $stats['published'], __( 'Published properties', 'tassili-immobilier' ), admin_url( 'edit.php?post_type=property&post_status=publish' ) ),
		array( $stats['available'], __( 'Available', 'tassili-immobilier' ), admin_url( 'edit.php?post_type=property&tassili_status=available' ) ),
		array( $stats['reserved'], __( 'Reserved', 'tassili-immobilier' ), admin_url( 'edit.php?post_type=property&tassili_status=reserved' ) ),
		array( $stats['closed'], __( 'Sold or rented', 'tassili-immobilier' ), admin_url( 'edit.php?post_type=property&tassili_status=closed' ) ),
		array( $stats['drafts'], __( 'Drafts', 'tassili-immobilier' ), admin_url( 'edit.php?post_type=property&post_status=draft' ) ),
	);
	if ( $is_admin ) {
		$cards[] = array( $stats['leads'], __( 'Requests in the last 30 days', 'tassili-immobilier' ), admin_url( 'edit.php?post_type=lead' ) );
	}

	$actions = array(
		array( __( 'Add a property', 'tassili-immobilier' ), admin_url( 'post-new.php?post_type=property' ), true ),
		array( __( 'All properties', 'tassili-immobilier' ), admin_url( 'edit.php?post_type=property' ), false ),
		array( __( 'Agents', 'tassili-immobilier' ), admin_url( 'edit.php?post_type=agent' ), false ),
		array( __( 'Wilayas and communes', 'tassili-immobilier' ), admin_url( 'edit-tags.php?taxonomy=wilaya&post_type=property' ), false ),
		array( __( 'Pages', 'tassili-immobilier' ), admin_url( 'edit.php?post_type=page' ), false ),
	);
	if ( $is_admin ) {
		$actions[] = array( __( 'Requests', 'tassili-immobilier' ), admin_url( 'edit.php?post_type=lead' ), false );
	}
	if ( $can_set ) {
		$actions[] = array( __( 'Site settings', 'tassili-immobilier' ), admin_url( 'customize.php?autofocus[panel]=tassili' ), false );
		$actions[] = array( __( 'Menus', 'tassili-immobilier' ), admin_url( 'nav-menus.php' ), false );
	}

	$guide = array(
		array( __( 'Add a property', 'tassili-immobilier' ), __( 'Go to Properties > Add new. Enter the title and the description, then fill in the “Property details” box: price, surface, typology and so on. In the boxes on the right, tick the transaction (sale or rent), the wilaya and the commune, and the type of property. Add the main photo with “Set featured image”, then click Publish.', 'tassili-immobilier' ) ),
		array( __( 'Edit or delete a property', 'tassili-immobilier' ), __( 'Open Properties > All properties and move the mouse over a row: Edit opens the full form, Quick Edit changes the title and the date, Trash removes it. To delete for good, open the Trash view and click Delete permanently.', 'tassili-immobilier' ) ),
		array( __( 'Photos', 'tassili-immobilier' ), __( 'The featured image is the first photo. Add the others in the “Photo gallery” box and drag them to change the order. Without photos, the site shows a neutral illustration.', 'tassili-immobilier' ) ),
		array( __( 'Sold, rented or reserved', 'tassili-immobilier' ), __( 'Change the Availability in the box on the right of the property, or use the quick links under its title in the list. You can also select several properties and use Bulk actions. A sold or rented property gets a label and leaves the searches and the home page, unless you tick the option in Site settings.', 'tassili-immobilier' ) ),
		array( __( 'Properties on the home page', 'tassili-immobilier' ), __( 'Click the star in the list, or tick “Show on the home page” in the property. The home page shows the six latest starred properties.', 'tassili-immobilier' ) ),
		array( __( 'Arabic version', 'tassili-immobilier' ), __( 'Each property, agent, testimonial and page has an “Arabic version” box. Leave it empty to show the French text to everyone. Wilayas, types and features have an Arabic name field too.', 'tassili-immobilier' ) ),
		array( __( 'Requests from visitors', 'tassili-immobilier' ), __( 'Contact and visit requests arrive by e-mail and are kept in Properties > Requests, which only administrators can see.', 'tassili-immobilier' ) ),
		array( __( 'Change texts, phone and logo', 'tassili-immobilier' ), __( 'Site settings holds the phone, WhatsApp number, address, opening hours, social links, hero text and figures. Pages such as About and Contact are edited under Pages. The menu is edited under Appearance > Menus.', 'tassili-immobilier' ) ),
	);

	$recent = $is_admin ? get_posts(
		array(
			'post_type'      => 'lead',
			'post_status'    => 'private',
			'posts_per_page' => 5,
		)
	) : array();
	?>
	<div class="wrap tassili-overview">
		<h1><?php esc_html_e( 'Properties: overview', 'tassili-immobilier' ); ?></h1>

		<div class="tassili-cards">
			<?php foreach ( $cards as $card ) : ?>
				<a class="tassili-card" href="<?php echo esc_url( $card[2] ); ?>">
					<strong><?php echo esc_html( (string) $card[0] ); ?></strong>
					<span><?php echo esc_html( $card[1] ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>

		<p class="tassili-actions">
			<?php foreach ( $actions as $action ) : ?>
				<a class="button <?php echo $action[2] ? 'button-primary' : ''; ?>" href="<?php echo esc_url( $action[1] ); ?>"><?php echo esc_html( $action[0] ); ?></a>
			<?php endforeach; ?>
		</p>

		<div class="tassili-columns">
			<div class="tassili-box">
				<h2><?php esc_html_e( 'How to manage your properties', 'tassili-immobilier' ); ?></h2>
				<?php foreach ( $guide as $i => $item ) : ?>
					<details<?php echo 0 === $i ? ' open' : ''; ?>>
						<summary><?php echo esc_html( $item[0] ); ?></summary>
						<p><?php echo esc_html( $item[1] ); ?></p>
					</details>
				<?php endforeach; ?>
			</div>

			<?php if ( $is_admin ) : ?>
				<div class="tassili-box">
					<h2><?php esc_html_e( 'Latest requests', 'tassili-immobilier' ); ?></h2>
					<?php tassili_print_recent_leads( $recent ); ?>
					<p><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=lead' ) ); ?>"><?php esc_html_e( 'See all requests', 'tassili-immobilier' ); ?></a></p>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<?php
}

/**
 * Print a short list of requests.
 *
 * @param WP_Post[] $leads Leads.
 */
function tassili_print_recent_leads( $leads ) {
	if ( ! $leads ) {
		echo '<p>' . esc_html__( 'No requests yet.', 'tassili-immobilier' ) . '</p>';
		return;
	}
	echo '<ul class="tassili-leads">';
	foreach ( $leads as $lead ) {
		$kind = 'visit' === get_post_meta( $lead->ID, '_tassili_kind', true ) ? __( 'Visit request', 'tassili-immobilier' ) : __( 'Message', 'tassili-immobilier' );
		$ref  = (string) get_post_meta( $lead->ID, '_tassili_ref', true );
		printf(
			'<li><a href="%1$s"><strong>%2$s</strong></a> <span class="description">%3$s%4$s &middot; %5$s</span><br><span dir="ltr">%6$s</span></li>',
			esc_url( get_edit_post_link( $lead->ID ) ),
			esc_html( $lead->post_title ),
			esc_html( $kind ),
			'' !== $ref ? ' &middot; ' . esc_html( $ref ) : '', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $ref is escaped just above.
			esc_html( get_the_date( '', $lead ) ),
			esc_html( (string) get_post_meta( $lead->ID, '_tassili_phone', true ) )
		);
	}
	echo '</ul>';
}

/**
 * Dashboard widget with the latest requests.
 */
function tassili_register_dashboard_widget() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	wp_add_dashboard_widget( 'tassili_requests', __( 'Latest requests', 'tassili-immobilier' ), 'tassili_dashboard_widget' );
}
add_action( 'wp_dashboard_setup', 'tassili_register_dashboard_widget' );

/**
 * Dashboard widget content.
 */
function tassili_dashboard_widget() {
	tassili_print_recent_leads(
		get_posts(
			array(
				'post_type'      => 'lead',
				'post_status'    => 'private',
				'posts_per_page' => 5,
			)
		)
	);
	echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=tassili' ) ) . '">' . esc_html__( 'Open the overview', 'tassili-immobilier' ) . '</a></p>';
}

/*
---------------------------------------------------------------------------------------
 * Listing screen: columns
 * ------------------------------------------------------------------------------------
 */

/**
 * Add the status and home page star after the price.
 *
 * @param string[] $columns Columns.
 * @return string[]
 */
function tassili_property_extra_columns( $columns ) {
	$new = array();
	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;
		if ( 'tassili_price' === $key ) {
			$new['tassili_status']   = __( 'Availability', 'tassili-immobilier' );
			$new['tassili_featured'] = __( 'Home', 'tassili-immobilier' );
		}
	}
	return $new;
}
add_filter( 'manage_property_posts_columns', 'tassili_property_extra_columns', 20 );

/**
 * URL of a one-click admin action (protected by a nonce).
 *
 * @param string $action  status|featured|duplicate.
 * @param int    $post_id Listing ID.
 * @param array  $extra   Extra query arguments.
 * @return string
 */
function tassili_action_url( $action, $post_id, $extra = array() ) {
	return wp_nonce_url(
		add_query_arg(
			array_merge(
				array(
					'action' => 'tassili_' . $action,
					'post'   => $post_id,
				),
				$extra
			),
			admin_url( 'admin-post.php' )
		),
		'tassili_' . $action . '_' . $post_id
	);
}

/**
 * Cells of the status and star columns.
 *
 * @param string $column  Column key.
 * @param int    $post_id Post ID.
 */
function tassili_property_extra_column_content( $column, $post_id ) {
	if ( 'tassili_status' === $column ) {
		$status = tassili_normalize_status( (string) get_post_meta( $post_id, '_tassili_status', true ) );
		$tx     = has_term( 'location', 'transaction', $post_id ) ? 'location' : 'vente';
		$label  = 'available' === $status ? __( 'Available', 'tassili-immobilier' ) : tassili_status_label( $status, $tx );
		printf( '<span class="tassili-pill tassili-pill--%1$s">%2$s</span>', esc_attr( $status ), esc_html( $label ) );
	} elseif ( 'tassili_featured' === $column ) {
		$on = (bool) get_post_meta( $post_id, '_tassili_featured', true );
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			echo $on ? '&#9733;' : '&mdash;'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed symbols.
			return;
		}
		printf(
			'<a class="tassili-star%1$s" href="%2$s" title="%3$s"><span aria-hidden="true">&#9733;</span><span class="screen-reader-text">%3$s</span></a>',
			$on ? ' is-on' : '', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed class.
			esc_url( tassili_action_url( 'featured', $post_id ) ),
			esc_attr( $on ? __( 'Remove from the home page', 'tassili-immobilier' ) : __( 'Show on the home page', 'tassili-immobilier' ) )
		);
	}
}
add_action( 'manage_property_posts_custom_column', 'tassili_property_extra_column_content', 10, 2 );

/**
 * Quick links under a listing's title.
 *
 * @param array   $actions Row actions.
 * @param WP_Post $post    Post.
 * @return array
 */
function tassili_property_row_actions( $actions, $post ) {
	if ( 'property' !== $post->post_type || ! current_user_can( 'edit_post', $post->ID ) ) {
		return $actions;
	}
	$status = tassili_normalize_status( (string) get_post_meta( $post->ID, '_tassili_status', true ) );
	if ( 'reserved' !== $status ) {
		$actions['tassili_reserve'] = '<a href="' . esc_url( tassili_action_url( 'status', $post->ID, array( 'to' => 'reserved' ) ) ) . '">' . esc_html__( 'Mark as reserved', 'tassili-immobilier' ) . '</a>';
	}
	if ( 'closed' !== $status ) {
		$actions['tassili_close'] = '<a href="' . esc_url( tassili_action_url( 'status', $post->ID, array( 'to' => 'closed' ) ) ) . '">' . esc_html__( 'Mark as sold or rented', 'tassili-immobilier' ) . '</a>';
	}
	if ( 'available' !== $status ) {
		$actions['tassili_open'] = '<a href="' . esc_url( tassili_action_url( 'status', $post->ID, array( 'to' => 'available' ) ) ) . '">' . esc_html__( 'Make available again', 'tassili-immobilier' ) . '</a>';
	}
	$actions['tassili_duplicate'] = '<a href="' . esc_url( tassili_action_url( 'duplicate', $post->ID ) ) . '">' . esc_html__( 'Duplicate', 'tassili-immobilier' ) . '</a>';
	return $actions;
}
add_filter( 'post_row_actions', 'tassili_property_row_actions', 10, 2 );

/*
---------------------------------------------------------------------------------------
 * One-click actions
 * ------------------------------------------------------------------------------------
 */

/**
 * Check the nonce and permission for a one-click action and return the listing ID.
 *
 * @param string $action status|featured|duplicate.
 * @return int
 */
function tassili_action_post_id( $action ) {
	$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- verified on the next line.
	check_admin_referer( 'tassili_' . $action . '_' . $post_id );
	if ( ! $post_id || 'property' !== get_post_type( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_die( esc_html__( 'You do not have permission to do this.', 'tassili-immobilier' ), 403 );
	}
	return $post_id;
}

/**
 * Back to where the click came from, with a notice.
 *
 * @param string $notice Notice key.
 * @param string $url    Optional target.
 */
function tassili_action_redirect( $notice, $url = '' ) {
	$url = '' !== $url ? $url : wp_get_referer();
	$url = $url ? $url : admin_url( 'edit.php?post_type=property' );
	wp_safe_redirect( add_query_arg( 'tassili_notice', $notice, remove_query_arg( array( 'tassili_notice', 'tassili_count' ), $url ) ) );
	exit;
}

/**
 * Change the availability of one listing.
 */
function tassili_action_set_status() {
	$post_id = tassili_action_post_id( 'status' );
	$to      = isset( $_GET['to'] ) ? tassili_normalize_status( sanitize_key( wp_unslash( $_GET['to'] ) ) ) : 'available'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- verified in tassili_action_post_id().
	update_post_meta( $post_id, '_tassili_status', $to );
	tassili_action_redirect( 'status' );
}
add_action( 'admin_post_tassili_status', 'tassili_action_set_status' );

/**
 * Add or remove a listing from the home page.
 */
function tassili_action_toggle_featured() {
	$post_id = tassili_action_post_id( 'featured' );
	if ( get_post_meta( $post_id, '_tassili_featured', true ) ) {
		delete_post_meta( $post_id, '_tassili_featured' );
	} else {
		update_post_meta( $post_id, '_tassili_featured', 1 );
	}
	tassili_action_redirect( 'featured' );
}
add_action( 'admin_post_tassili_featured', 'tassili_action_toggle_featured' );

/**
 * Copy a listing into a new draft: same fields, terms, featured image and gallery.
 *
 * @param int $post_id Listing to copy.
 * @return int New listing ID, 0 on failure.
 */
function tassili_duplicate_property( $post_id ) {
	$source = get_post( $post_id );
	if ( ! $source || 'property' !== $source->post_type ) {
		return 0;
	}
	$new_id = wp_insert_post(
		array(
			'post_type'    => 'property',
			'post_status'  => 'draft',
			/* translators: %s: title of the listing that was copied. */
			'post_title'   => sprintf( __( '%s (copy)', 'tassili-immobilier' ), $source->post_title ),
			'post_content' => $source->post_content,
			'post_excerpt' => $source->post_excerpt,
			'post_author'  => get_current_user_id(),
		),
		true
	);
	if ( is_wp_error( $new_id ) ) {
		return 0;
	}
	foreach ( get_post_meta( $post_id ) as $key => $values ) {
		if ( 0 === strpos( $key, '_tassili_' ) && ! in_array( $key, array( '_tassili_ref', '_tassili_demo' ), true ) ) {
			update_post_meta( $new_id, $key, maybe_unserialize( $values[0] ) );
		}
	}
	foreach ( get_object_taxonomies( 'property' ) as $taxonomy ) {
		wp_set_object_terms( $new_id, wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'ids' ) ), $taxonomy );
	}
	$thumb = get_post_thumbnail_id( $post_id );
	if ( $thumb ) {
		set_post_thumbnail( $new_id, $thumb );
	}
	update_post_meta( $new_id, '_tassili_ref', tassili_generate_ref( $new_id ) );
	return (int) $new_id;
}

/**
 * Duplicate button.
 */
function tassili_action_duplicate() {
	$post_id = tassili_action_post_id( 'duplicate' );
	$new_id  = tassili_duplicate_property( $post_id );
	if ( ! $new_id ) {
		tassili_action_redirect( 'error' );
	}
	tassili_action_redirect( 'duplicated', admin_url( 'post.php?post=' . $new_id . '&action=edit' ) );
}
add_action( 'admin_post_tassili_duplicate', 'tassili_action_duplicate' );

/*
---------------------------------------------------------------------------------------
 * Bulk actions
 * ------------------------------------------------------------------------------------
 */

/**
 * Add the bulk actions.
 *
 * @param string[] $actions Bulk actions.
 * @return string[]
 */
function tassili_property_bulk_actions( $actions ) {
	$actions['tassili_available'] = __( 'Mark as available', 'tassili-immobilier' );
	$actions['tassili_reserved']  = __( 'Mark as reserved', 'tassili-immobilier' );
	$actions['tassili_closed']    = __( 'Mark as sold or rented', 'tassili-immobilier' );
	$actions['tassili_feature']   = __( 'Show on the home page', 'tassili-immobilier' );
	$actions['tassili_unfeature'] = __( 'Remove from the home page', 'tassili-immobilier' );
	return $actions;
}
add_filter( 'bulk_actions-edit-property', 'tassili_property_bulk_actions' );

/**
 * Run a bulk action. WordPress has already checked the nonce.
 *
 * @param string $redirect Redirect URL.
 * @param string $action   Action.
 * @param int[]  $ids      Selected listing IDs.
 * @return string
 */
function tassili_handle_bulk_actions( $redirect, $action, $ids ) {
	if ( 0 !== strpos( $action, 'tassili_' ) ) {
		return $redirect;
	}
	$done = 0;
	foreach ( $ids as $id ) {
		if ( ! current_user_can( 'edit_post', $id ) ) {
			continue;
		}
		if ( in_array( $action, array( 'tassili_available', 'tassili_reserved', 'tassili_closed' ), true ) ) {
			update_post_meta( $id, '_tassili_status', substr( $action, 8 ) );
		} elseif ( 'tassili_feature' === $action ) {
			update_post_meta( $id, '_tassili_featured', 1 );
		} elseif ( 'tassili_unfeature' === $action ) {
			delete_post_meta( $id, '_tassili_featured' );
		} else {
			continue;
		}
		++$done;
	}
	return add_query_arg(
		array(
			'tassili_notice' => 'bulk',
			'tassili_count'  => $done,
		),
		$redirect
	);
}
add_filter( 'handle_bulk_actions-edit-property', 'tassili_handle_bulk_actions', 10, 3 );

/*
---------------------------------------------------------------------------------------
 * Filters above the list
 * ------------------------------------------------------------------------------------
 */

/**
 * Drop-downs above the listing table.
 *
 * @param string $post_type Post type being listed.
 */
function tassili_property_list_filters( $post_type ) {
	if ( 'property' !== $post_type ) {
		return;
	}
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- filtering a list, nothing is saved.
	$tx     = isset( $_GET['tassili_tx'] ) ? sanitize_title( wp_unslash( $_GET['tassili_tx'] ) ) : '';
	$wilaya = isset( $_GET['tassili_wilaya'] ) ? sanitize_title( wp_unslash( $_GET['tassili_wilaya'] ) ) : '';
	$type   = isset( $_GET['tassili_type'] ) ? sanitize_title( wp_unslash( $_GET['tassili_type'] ) ) : '';
	$status = isset( $_GET['tassili_status'] ) ? sanitize_key( wp_unslash( $_GET['tassili_status'] ) ) : '';
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	wp_dropdown_categories(
		array(
			'taxonomy'        => 'transaction',
			'name'            => 'tassili_tx',
			'show_option_all' => __( 'All transactions', 'tassili-immobilier' ),
			'value_field'     => 'slug',
			'selected'        => $tx,
			'hide_empty'      => false,
		)
	);
	wp_dropdown_categories(
		array(
			'taxonomy'        => 'wilaya',
			'name'            => 'tassili_wilaya',
			'show_option_all' => __( 'All wilayas', 'tassili-immobilier' ),
			'value_field'     => 'slug',
			'selected'        => $wilaya,
			'hide_empty'      => true,
			'depth'           => 1,
		)
	);
	wp_dropdown_categories(
		array(
			'taxonomy'        => 'property_type',
			'name'            => 'tassili_type',
			'show_option_all' => __( 'All property types', 'tassili-immobilier' ),
			'value_field'     => 'slug',
			'selected'        => $type,
			'hide_empty'      => false,
		)
	);
	echo '<select name="tassili_status"><option value="">' . esc_html__( 'Any availability', 'tassili-immobilier' ) . '</option>';
	foreach ( tassili_status_options() as $key => $label ) {
		printf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $key ), selected( $status, $key, false ), esc_html( $label ) );
	}
	echo '</select>';
}
add_action( 'restrict_manage_posts', 'tassili_property_list_filters' );

/**
 * Apply the drop-downs to the admin listing query.
 *
 * @param WP_Query $query Query.
 */
function tassili_property_list_query( $query ) {
	global $pagenow;
	if ( ! is_admin() || 'edit.php' !== $pagenow || ! $query->is_main_query() || 'property' !== $query->get( 'post_type' ) ) {
		return;
	}
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- filtering a list, nothing is saved.
	$tax_query = array();
	foreach ( array(
		'tassili_tx'     => 'transaction',
		'tassili_wilaya' => 'wilaya',
		'tassili_type'   => 'property_type',
	) as $param => $taxonomy ) {
		$slug = isset( $_GET[ $param ] ) ? sanitize_title( wp_unslash( $_GET[ $param ] ) ) : '';
		if ( '' !== $slug && '0' !== $slug ) {
			$tax_query[] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => array( $slug ),
			);
		}
	}
	if ( $tax_query ) {
		$query->set( 'tax_query', $tax_query );
	}

	$status = isset( $_GET['tassili_status'] ) ? sanitize_key( wp_unslash( $_GET['tassili_status'] ) ) : '';
	// phpcs:enable WordPress.Security.NonceVerification.Recommended
	if ( 'reserved' === $status || 'closed' === $status ) {
		$query->set(
			'meta_query',
			array(
				array(
					'key'   => '_tassili_status',
					'value' => $status,
				),
			)
		);
	} elseif ( 'available' === $status ) {
		$query->set(
			'meta_query',
			array(
				'relation' => 'OR',
				array(
					'key'     => '_tassili_status',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'   => '_tassili_status',
					'value' => 'available',
				),
			)
		);
	}
}
add_action( 'pre_get_posts', 'tassili_property_list_query' );

/*
---------------------------------------------------------------------------------------
 * Notices
 * ------------------------------------------------------------------------------------
 */

/**
 * Confirmation messages after an action.
 */
function tassili_admin_notices() {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- only picks which message to show.
	if ( ! isset( $_GET['tassili_notice'] ) ) {
		return;
	}
	$notice = sanitize_key( wp_unslash( $_GET['tassili_notice'] ) );
	$count  = isset( $_GET['tassili_count'] ) ? absint( $_GET['tassili_count'] ) : 0;
	// phpcs:enable WordPress.Security.NonceVerification.Recommended
	$class = 'notice-success';
	switch ( $notice ) {
		case 'status':
			$text = __( 'Availability updated.', 'tassili-immobilier' );
			break;
		case 'featured':
			$text = __( 'Home page setting updated.', 'tassili-immobilier' );
			break;
		case 'duplicated':
			$text = __( 'The property was duplicated. You are editing the copy, which is a draft.', 'tassili-immobilier' );
			break;
		case 'bulk':
			/* translators: %s: number of properties. */
			$text = sprintf( _n( '%s property updated.', '%s properties updated.', $count, 'tassili-immobilier' ), number_format_i18n( $count ) );
			break;
		default:
			$text  = __( 'Something went wrong. Please try again.', 'tassili-immobilier' );
			$class = 'notice-error';
	}
	printf( '<div class="notice %1$s is-dismissible"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $text ) );
}
add_action( 'admin_notices', 'tassili_admin_notices' );
