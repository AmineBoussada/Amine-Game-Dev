<?php
/**
 * Runs the plugin's uninstall.php in isolation. Usage: php uninstall-check.php off|on
 * off = "delete data" unticked (data must survive), on = ticked (data must go).
 * Re-activates the plugin afterwards so the test site is left as it was.
 */
$_SERVER['HTTP_HOST']   = '127.0.0.1:8080';
$_SERVER['REQUEST_URI'] = '/';
require __DIR__ . '/wordpress/wp-load.php';
wp_set_current_user( 1 );
$mode = $argv[1] ?? 'off';

$o = get_option( 'ts_core_settings', array() );
$o['delete_data_on_uninstall'] = ( 'on' === $mode );
update_option( 'ts_core_settings', $o );

define( 'WP_UNINSTALL_PLUGIN', 'top-solution-core/top-solution-core.php' );
require WP_PLUGIN_DIR . '/top-solution-core/uninstall.php';

wp_cache_flush();
$roles = wp_roles();
$roles->for_site();
$admin_has = get_role( 'administrator' )->has_cap( 'ts_manage_settings' );
$opt       = false !== get_option( 'ts_core_settings', false );
$orders    = wc_get_orders( array( 'limit' => 1, 'return' => 'ids' ) );
$ok        = ( 'on' === $mode ) ? ( ! $admin_has && ! $opt ) : ( $admin_has && $opt );
echo "uninstall ($mode): capability kept=" . var_export( $admin_has, true ) . ' option kept=' . var_export( $opt, true ) . ' orders untouched=' . var_export( ! empty( $orders ), true ) . "\n";
echo ( $ok && ! empty( $orders ) ? 'PASS' : 'FAIL' ) . " uninstall behaviour ($mode)\n";

// Restore the site.
\TopSolution\Core\Activator::activate();
exit( $ok && ! empty( $orders ) ? 0 : 1 );
