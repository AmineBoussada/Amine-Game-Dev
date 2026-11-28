<?php
/**
 * Switch WooCommerce order storage for the test matrix: php hpos-toggle.php on|off
 * Synchronises existing orders first (WooCommerce refuses to switch while orders are out of sync).
 */
$_SERVER['HTTP_HOST']   = '127.0.0.1:8080';
$_SERVER['REQUEST_URI'] = '/';
require __DIR__ . '/wordpress/wp-load.php';

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;
use Automattic\WooCommerce\Utilities\OrderUtil;

$want = isset( $argv[1] ) && 'off' === $argv[1] ? 'no' : 'yes';
$sync = wc_get_container()->get( DataSynchronizer::class );
update_option( DataSynchronizer::ORDERS_DATA_SYNC_ENABLED_OPTION, 'yes' );
$rounds = 0;
while ( $rounds++ < 200 ) {
	$batch = $sync->get_next_batch_to_process( 200 );
	if ( empty( $batch ) ) {
		break;
	}
	$sync->process_batch( $batch );
}
echo 'pending after sync: ' . $sync->get_current_orders_pending_sync_count() . "\n";
update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, $want );
wp_cache_flush();
echo 'HPOS authoritative: ' . ( OrderUtil::custom_orders_table_usage_is_enabled() ? 'yes' : 'no' ) . "\n";
