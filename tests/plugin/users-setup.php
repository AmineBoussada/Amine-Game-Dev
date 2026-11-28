<?php
// Test users for ui.js: manager (shop_manager) and sub (subscriber).
$_SERVER['HTTP_HOST'] = '127.0.0.1:8080'; $_SERVER['REQUEST_URI'] = '/';
require __DIR__ . '/wordpress/wp-load.php';
foreach ( array( array( 'manager', 'manager-pass', 'shop_manager' ), array( 'sub', 'sub-pass', 'subscriber' ) ) as $u ) {
	$id = username_exists( $u[0] ) ?: wp_create_user( $u[0], $u[1], $u[0] . '@example.com' );
	( new WP_User( $id ) )->set_role( $u[2] );
	echo $u[0], ' => ', $u[2], "\n";
}
