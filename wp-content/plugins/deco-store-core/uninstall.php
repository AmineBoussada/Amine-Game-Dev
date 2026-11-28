<?php
/**
 * Uninstall: removes plugin data only when "delete data on uninstall" was
 * switched on in Settings. Orders are WooCommerce data and are never touched.
 *
 * @package DecoStore
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$ds_core_settings = get_option( 'ds_core_settings', array() );

if ( is_array( $ds_core_settings ) && ! empty( $ds_core_settings['delete_data_on_uninstall'] ) ) {
	require_once __DIR__ . '/src/Core/Capabilities.php';
	require_once __DIR__ . '/src/Core/Schema.php';
	foreach ( get_posts(
		array(
			'post_type'      => array( 'ds_form', 'ds_offer' ),
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	) as $ds_core_form_id ) {
		wp_delete_post( (int) $ds_core_form_id, true );
	}
	delete_option( 'ds_default_form_id' );
	wp_clear_scheduled_hook( 'ds_core_daily' );
	global $wpdb;
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( 'ds_idem_' ) . '%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Removing our own rows on uninstall.
	\DecoStore\Core\Capabilities::revoke_all();
	\DecoStore\Core\Schema::uninstall();
	delete_option( 'ds_core_settings' );
	delete_option( 'ds_core_db_version' );
	delete_option( 'ds_delivery_settings' );
}
