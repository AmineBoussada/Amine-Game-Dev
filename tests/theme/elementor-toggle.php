<?php
$_SERVER['HTTP_HOST']='127.0.0.1:8080';$_SERVER['REQUEST_URI']='/';
require __DIR__.'/wordpress/wp-load.php';
require_once ABSPATH.'wp-admin/includes/plugin.php';
if ( 'off' === ( $argv[1] ?? '' ) ) { deactivate_plugins( 'elementor/elementor.php' ); } else { activate_plugin( 'elementor/elementor.php' ); }
echo 'elementor active: ', is_plugin_active( 'elementor/elementor.php' ) ? 'yes' : 'no', "\n";
