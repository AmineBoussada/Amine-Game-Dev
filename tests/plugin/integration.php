<?php
/**
 * Integration checks for Déco Store Core against a real WordPress + WooCommerce.
 * Run from the folder that contains wordpress/:  php integration.php
 * The plugin must already be installed (e.g. from dist/deco-store-core.zip) and active.
 * Exit code 0 = all passed.
 */

$_SERVER['HTTP_HOST']   = '127.0.0.1:8080';
$_SERVER['REQUEST_URI'] = '/';
require __DIR__ . '/wordpress/wp-load.php';
wp_set_current_user( 1 );

use DecoStore\Core\Capabilities;
use DecoStore\Core\Logger;
use DecoStore\Core\Migrator;
use DecoStore\Core\Settings;
use DecoStore\Core\Activator;

$failed = 0;
function check( $ok, $label ) {
	global $failed;
	echo ( $ok ? 'PASS ' : 'FAIL ' ) . $label . "\n";
	if ( ! $ok ) {
		++$failed;
	}
}

$plugin = 'deco-store-core/deco-store-core.php';
check( is_plugin_active( $plugin ), 'plugin is active' );
check( defined( 'DS_CORE_VERSION' ), 'plugin booted (constants defined)' );
check( empty( \DecoStore\Core\Plugin::problems() ), 'no requirement problems reported' );

// ---- Capabilities
$admin = get_role( 'administrator' );
$mgr   = get_role( 'shop_manager' );
$missing = array_filter( Capabilities::all(), static function ( $c ) use ( $admin ) {
	return ! $admin->has_cap( $c );
} );
check( empty( $missing ), 'administrator has all 9 capabilities' );
foreach ( array( 'ds_view_costs', 'ds_manage_suppliers', 'ds_manage_settings' ) as $cap ) {
	check( ! $mgr->has_cap( $cap ), "shop_manager does NOT have $cap (costs/suppliers/settings stay private)" );
}
check( $mgr->has_cap( 'ds_manage_landing' ) && $mgr->has_cap( 'ds_view_reports' ), 'shop_manager has the day-to-day capabilities' );
foreach ( array( 'customer', 'subscriber', 'editor' ) as $r ) {
	$role = get_role( $r );
	$any  = $role ? array_filter( Capabilities::all(), array( $role, 'has_cap' ) ) : array();
	check( empty( $any ), "role $r has no plugin capability" );
}

// ---- Settings + migrations
$s = Settings::all();
check( 'processing' === $s['initial_order_status'], 'default initial order status is processing' );
check( (int) get_option( Migrator::OPTION ) === Migrator::CURRENT, 'database version = ' . Migrator::CURRENT );
check( \DecoStore\Core\Schema::tables_exist(), 'custom tables exist' );

update_option( Settings::OPTION, array_merge( Settings::all(), array( 'initial_order_status' => 'on-hold', 'debug_logging' => true ) ) );
Activator::activate();
$after = Settings::all();
check( 'on-hold' === $after['initial_order_status'] && true === $after['debug_logging'], 're-activation never overwrites existing settings' );
update_option( Migrator::OPTION, 0 );
Migrator::maybe_run();
check( (int) get_option( Migrator::OPTION ) === Migrator::CURRENT, 'migrations re-run from version 0 and finish at current' );
check( 'on-hold' === Settings::get( 'initial_order_status' ), 'migrations are idempotent (settings kept)' );
$sanitized = Settings::sanitize_callback( array( '__tab' => 'orders', 'initial_order_status' => 'completed' ) );
check( 'processing' === $sanitized['initial_order_status'], 'forged order status is rejected on the server' );
// Through the real Settings API path (sanitize runs inside update_option, as on a real save).
Settings::register();
update_option( Settings::OPTION, Settings::defaults() );
update_option( Settings::OPTION, array( '__tab' => 'orders', 'initial_order_status' => 'on-hold' ) );
check( 'on-hold' === Settings::get( 'initial_order_status' ), 'a real save through update_option keeps the new value (sanitize runs twice in WordPress)' );
update_option( Settings::OPTION, array( '__tab' => 'general', 'debug_logging' => '1' ) );
check( 'on-hold' === Settings::get( 'initial_order_status' ) && true === Settings::get( 'debug_logging' ), 'saving General keeps Orders and applies its own change' );
update_option( Settings::OPTION, Settings::defaults() );

// ---- HPOS
$util = '\Automattic\WooCommerce\Utilities\FeaturesUtil';
$plugins = method_exists( $util, 'get_compatible_plugins_for_feature' ) ? $util::get_compatible_plugins_for_feature( 'custom_order_tables' ) : array();
check( in_array( plugin_basename( DS_CORE_FILE ), $plugins['compatible'] ?? array(), true ), 'declared compatible with HPOS (custom_order_tables)' );
check( ! in_array( plugin_basename( DS_CORE_FILE ), $plugins['incompatible'] ?? array(), true ), 'not listed as incompatible with HPOS' );

// ---- Orders through WooCommerce CRUD with the plugin active (HPOS or legacy, whichever the store uses)
$hpos_on = \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
echo '  (store order storage: ' . ( $hpos_on ? 'HPOS custom tables' : 'legacy posts' ) . ")\n";
$order = wc_create_order();
$order->set_billing_first_name( 'Test' );
$order->set_payment_method( 'cod' );
$order->update_meta_data( '_ds_probe', 'ok' );
$order->save();
$read = wc_get_order( $order->get_id() );
check( $read && 'ok' === $read->get_meta( '_ds_probe' ), 'an order (with custom meta) is created and read back through WC CRUD' );
if ( $hpos_on ) {
	global $wpdb;
	$in_hpos = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wc_orders WHERE id = %d", $order->get_id() ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	check( 1 === $in_hpos, 'the order is stored in the HPOS table (wc_orders)' );
}
$order->delete( true );
check( false === wc_get_order( $order->get_id() ) || 0 === $order->get_id(), 'the probe order is removed again' );

// ---- Admin menu / status checks
check( class_exists( 'DecoStore\Admin\StatusChecks' ), 'status checks available' );
$rows = \DecoStore\Admin\StatusChecks::run();
$ids  = wp_list_pluck( $rows, 'id' );
check( array() === array_diff( array( 'versions', 'hpos', 'cod', 'currency', 'theme', 'rtl', 'permalinks', 'delivery', 'elementor' ), $ids ), 'status dashboard has all 9 checks' );
$hpos = array_values( array_filter( $rows, static function ( $r ) {
	return 'hpos' === $r['id'];
} ) );
check( ! empty( $hpos ) && 'ok' === $hpos[0]['status'], 'status check reports HPOS compatibility as OK' );
$elem = array_values( array_filter( $rows, static function ( $r ) {
	return 'elementor' === $r['id'];
} ) );
check( ! empty( $elem ) && 'ok' === $elem[0]['status'], 'Elementor check never fails (Free optional, Pro not needed)' );

// ---- Logger writes to WooCommerce logs without personal data
Logger::info( 'integration probe', array( 'order_id' => 99, 'customer_phone' => '0555123456', 'unit_cost' => 4321 ) );
$logs = glob( WP_CONTENT_DIR . '/uploads/wc-logs/deco-store-*.log' );
$text = '';
foreach ( (array) $logs as $f ) {
	$text .= file_get_contents( $f );
}
check( false !== strpos( $text, 'integration probe' ), 'log message reaches WooCommerce logs (source deco-store)' );
check( false === strpos( $text, '0555123456' ) && false === strpos( $text, '4321' ), 'phone number and cost are NOT in the log file' );
check( false !== strpos( $text, '[redacted]' ), 'sensitive context shows [redacted]' );

// ---- No Elementor Pro dependency in the plugin code
$bad = 0;
$it  = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( DS_CORE_DIR, FilesystemIterator::SKIP_DOTS ) );
foreach ( $it as $file ) {
	if ( 'php' !== $file->getExtension() ) {
		continue;
	}
	$code = php_strip_whitespace( $file->getPathname() ); // Comments removed.
	if ( preg_match( '/elementor_theme_do_location|ElementorPro\\\\|elementor-pro\/|elementor_pro_|register_locations|popup/i', $code ) ) {
		++$bad;
		echo '  Pro API reference in ' . $file->getPathname() . "\n";
	}
}
check( 0 === $bad, 'no Elementor Pro API/dependency in plugin code' );
check( false === strpos( (string) file_get_contents( DS_CORE_FILE ), 'Requires Plugins:  elementor' ), 'plugin header does not require Elementor' );

echo $failed ? "\n$failed FAILED\n" : "\nALL PASSED\n";
exit( $failed ? 1 : 0 );
