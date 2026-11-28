<?php
$_SERVER['HTTP_HOST']='127.0.0.1:8080';$_SERVER['REQUEST_URI']='/';
require __DIR__.'/wordpress/wp-load.php';
wp_set_current_user(1);
echo 'first run changed: '; var_export(ts_child_elementor_sync_kit()); echo "\n";
echo 'second run changed: '; var_export(ts_child_elementor_sync_kit()); echo "\n";
$s=get_post_meta((int)get_option('elementor_active_kit'),'_elementor_page_settings',true);
foreach($s['system_colors'] as $c) echo $c['_id'],'=',$c['color'],' '; echo "\n";
foreach($s['system_typography'] as $t) echo $t['_id'],'=',$t['typography_font_family'],' '; echo "\n";
