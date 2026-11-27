<?php
$_SERVER['HTTP_HOST']='127.0.0.1:8080';$_SERVER['REQUEST_URI']='/';
require __DIR__.'/wordpress/wp-load.php';
update_option('woocommerce_coming_soon','no'); update_option('woocommerce_price_num_decimals',0); update_option('woocommerce_store_pages_only','no');
echo "ok\n";
