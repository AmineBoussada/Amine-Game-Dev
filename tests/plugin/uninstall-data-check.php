<?php
/**
 * Uninstall with "delete data" ticked removes Déco Store data (forms, offers,
 * idempotency rows, schedule, settings) and never touches WooCommerce orders.
 * Restores the plugin's data afterwards. php uninstall-data-check.php
 */
$_SERVER['HTTP_HOST']   = '127.0.0.1:8080';
$_SERVER['REQUEST_URI'] = '/';
require __DIR__ . '/wordpress/wp-load.php';
wp_set_current_user( 1 );

$failed = 0;
function check( $ok, $label ) {
	global $failed;
	echo ( $ok ? 'PASS ' : 'FAIL ' ) . $label . "\n";
	if ( ! $ok ) {
		++$failed;
	}
}
$offer = DecoStore\Offers\OfferRepository::create( DecoStore\Offers\OfferSchema::normalize( array( 'label' => 'x', 'type' => 'custom_rule', 'action' => array( 'type' => 'free_delivery' ) ) )['definition'] );
add_option( 'ds_idem_uninstall_test', 'processing:1', '', 'no' );
DecoStore\Orders\Module::schedule();
$order = wc_create_order();
$order->update_meta_data( '_ds_source', 'landing_page' );
$order->save();
check( $offer && wp_next_scheduled( 'ds_core_daily' ), 'fixtures ready' );

$o                             = get_option( 'ds_core_settings', array() );
$o['delete_data_on_uninstall'] = true;
update_option( 'ds_core_settings', $o );
define( 'WP_UNINSTALL_PLUGIN', 'deco-store-core/deco-store-core.php' );
require WP_PLUGIN_DIR . '/deco-store-core/uninstall.php';
wp_cache_flush();

check( ! get_post( $offer ), 'offers deleted' );
check( 0 === count( get_posts( array( 'post_type' => 'ds_form', 'post_status' => 'any', 'numberposts' => 1 ) ) ), 'forms deleted' );
check( false === get_option( 'ds_idem_uninstall_test', false ), 'idempotency rows deleted' );
check( ! wp_next_scheduled( 'ds_core_daily' ), 'daily schedule removed' );
check( false === get_option( 'ds_core_settings', false ), 'settings deleted' );
check( (bool) wc_get_order( $order->get_id() ), 'WooCommerce orders are never deleted' );

// Restore the test site.
$order->delete( true );
DecoStore\Core\Activator::activate();
DecoStore\Forms\FormRepository::ensure_default();
echo $failed ? "FAILED $failed\n" : "ALL OK\n";
exit( $failed ? 1 : 0 );
