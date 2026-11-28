<?php
// Run the official Theme Check plugin against the installed theme from CLI.
$_SERVER['HTTP_HOST']='127.0.0.1:8080';$_SERVER['REQUEST_URI']='/';
require __DIR__.'/wordpress/wp-load.php';
wp_set_current_user(1);
require_once WP_PLUGIN_DIR . '/theme-check/checkbase.php';
$theme = wp_get_theme( 'deco-store-child' );
$ok = run_themechecks_against_theme( $theme, 'deco-store-child' );
global $themechecks;
$counts = array();
foreach ( $themechecks as $check ) {
	foreach ( (array) $check->getError() as $e ) {
		$t = wp_strip_all_tags( $e );
		preg_match( '/^(REQUIRED|WARNING|RECOMMENDED|INFO)/', $t, $m );
		$lvl = $m[1] ?? 'OTHER';
		$counts[ $lvl ] = ( $counts[ $lvl ] ?? 0 ) + 1;
		echo $lvl, ': ', mb_substr( preg_replace( '/\s+/', ' ', $t ), 0, 330 ), "\n";
	}
}
echo "\nRESULT: ", $ok ? 'PASS (no required issues)' : 'FAIL', "\n"; print_r( $counts );
