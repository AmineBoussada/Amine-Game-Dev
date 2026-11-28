<?php
$_SERVER['HTTP_HOST']='127.0.0.1:8080';$_SERVER['REQUEST_URI']='/';
require __DIR__.'/wordpress/wp-load.php';
wp_set_current_user( 1 );
if ( 'on' === ( $argv[1] ?? '' ) ) {
	update_option( 'ds_shot_backup', array( get_option('blogname'), get_option('blogdescription'), get_theme_mod('ds_announcement'), get_theme_mod('ds_phone') ) );
	update_option( 'blogname', 'Déco Store' ); update_option( 'blogdescription', 'ديكور داخلي وتحسينات منزلية' );
	set_theme_mod( 'ds_announcement', '' ); set_theme_mod( 'ds_phone', '' );
} else {
	$b = get_option( 'ds_shot_backup' ); update_option('blogname',$b[0]); update_option('blogdescription',$b[1]); set_theme_mod('ds_announcement',$b[2]); set_theme_mod('ds_phone',$b[3]);
}
echo get_option('blogname'), "\n";
