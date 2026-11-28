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
	\TopSolution\Core\Capabilities::revoke_all();
	delete_option( 'ts_core_settings' );
	delete_option( 'ts_core_db_version' );
}
