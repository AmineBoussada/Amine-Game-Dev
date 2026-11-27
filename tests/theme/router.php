<?php
$p = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
$f = __DIR__ . '/wordpress' . $p;
if ( '/' !== $p && file_exists( $f ) && ! is_dir( $f ) ) {
	return false;
}
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/wordpress/index.php';
