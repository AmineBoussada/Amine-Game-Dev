<?php
$_SERVER['HTTP_HOST']='127.0.0.1:8080';$_SERVER['REQUEST_URI']='/';
require __DIR__.'/wordpress/wp-load.php';
require_once ABSPATH.'wp-admin/includes/plugin.php';
$mode = $argv[1] ?? 'on';
if ( 'on' === $mode ) {
	$r = activate_plugin('elementor/elementor.php'); echo is_wp_error($r)?$r->get_error_message():"elementor activated\n";
	$id = (int) get_option('page_on_front');
	$data = array(array('id'=>'a1b2c3d','elType'=>'container','settings'=>array('content_width'=>'boxed'),'elements'=>array(array('id'=>'e5f6a7b','elType'=>'widget','widgetType'=>'heading','settings'=>array('title'=>'عنوان تجريبي من Elementor','header_size'=>'h1'),'elements'=>array())),'isInner'=>false));
	update_post_meta($id,'_elementor_edit_mode','builder');
	update_post_meta($id,'_elementor_template_type','wp-page');
	update_post_meta($id,'_elementor_version',ELEMENTOR_VERSION);
	update_post_meta($id,'_elementor_data',wp_slash(wp_json_encode($data)));
	echo "home built with elementor\n";
	// full-width template page
	$pid = wp_insert_post(array('post_type'=>'page','post_status'=>'publish','post_title'=>'صفحة عرض كامل','post_name'=>'full','post_content'=>'<h2>محتوى عرض كامل تجريبي</h2><p>نص</p>'));
	update_post_meta($pid,'_wp_page_template','templates/page-full-width.php');
} else {
	$id = (int) get_option('page_on_front');
	delete_post_meta($id,'_elementor_edit_mode');
	echo "elementor mode off\n";
}
