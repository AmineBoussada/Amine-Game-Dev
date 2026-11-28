<?php
define( 'WP_INSTALLING', true );
$_SERVER['HTTP_HOST']   = '127.0.0.1:8080';
$_SERVER['REQUEST_URI'] = '/';
require __DIR__ . '/wordpress/wp-load.php';
require ABSPATH . 'wp-admin/includes/upgrade.php';
if ( ! is_blog_installed() ) {
	wp_install( 'متجر Déco Store (اختبار)', 'admin', 'admin@example.com', true, '', 'admin-pass', 'ar' );
	echo "installed\n";
} else {
	echo "already installed\n";
}
