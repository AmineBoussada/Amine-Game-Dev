<?php
/**
 * Uninstall: removes plugin data only when "delete data on uninstall" was
 * switched on in Settings. Orders are WooCommerce data and are never touched.
 *
 * @package TopSolution
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$ts_core_settings = get_option( 'ts_core_settings', array() );

if ( is_array( $ts_core_settings ) && ! empty( $ts_core_settings['delete_data_on_uninstall'] ) ) {
	require_once __DIR__ . '/src/Core/Capabilities.php';
	require_once __DIR__ . '/src/Core/Schema.php';
	foreach ( get_posts(
		array(
			'post_type'      => array( 'ts_form', 'ts_offer' ),
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	) as $ts_core_form_id ) {
		wp_delete_post( (int) $ts_core_form_id, true );
	}
	delete_option( 'ts_default_form_id' );
	\TopSolution\Core\Capabilities::revoke_all();
	\TopSolution\Core\Schema::uninstall();
	delete_option( 'ts_core_settings' );
	delete_option( 'ts_core_db_version' );
	delete_option( 'ts_delivery_settings' );
}
