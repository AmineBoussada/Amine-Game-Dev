<?php
// TEST-ONLY seed: placeholder Arabic content for visual testing. Never shipped.
$_SERVER['HTTP_HOST']   = '127.0.0.1:8080';
$_SERVER['REQUEST_URI'] = '/';
require __DIR__ . '/wordpress/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

update_option( 'WPLANG', 'ar' );
update_option( 'timezone_string', 'Africa/Algiers' );
update_option( 'blogdescription', 'وصف تجريبي للموقع: ديكور وتحسينات منزلية' );
activate_plugin( 'woocommerce/woocommerce.php' );
echo "wc active\n";
switch_theme( 'top-solution-child' );
update_option( 'woocommerce_currency', 'DZD' );
update_option( 'woocommerce_price_decimals', 0 );
update_option( 'woocommerce_price_thousand_sep', ' ' );
update_option( 'woocommerce_default_country', 'DZ' );
update_option( 'woocommerce_currency_pos', 'right_space' );
update_option( 'woocommerce_shop_page_display', 'both' );
update_option( 'woocommerce_category_archive_display', 'both' );
update_option( 'woocommerce_cod_settings', array( 'enabled' => 'yes', 'title' => 'الدفع عند الاستلام', 'description' => 'ادفع نقداً عند استلام طلبك.' ) );
update_option( 'woocommerce_onboarding_opt_in', 'no' );
if ( class_exists( 'WC_Install' ) ) {
	WC_Install::create_pages();
}

function ts_test_image( $seed, $w = 900, $h = 900 ) {
	$img   = imagecreatetruecolor( $w, $h );
	$bases = array( array( 214, 190, 160 ), array( 190, 150, 110 ), array( 160, 120, 80 ), array( 230, 226, 220 ), array( 200, 200, 196 ), array( 138, 95, 55 ) );
	$b     = $bases[ $seed % count( $bases ) ];
	for ( $y = 0; $y < $h; $y++ ) {
		$k = $y / $h;
		$c = imagecolorallocate( $img, (int) ( $b[0] - 40 * $k ), (int) ( $b[1] - 40 * $k ), (int) ( $b[2] - 40 * $k ) );
		imageline( $img, 0, $y, $w, $y, $c );
	}
	$line = imagecolorallocatealpha( $img, 255, 255, 255, 100 );
	for ( $i = 0; $i < 12; $i++ ) {
		imageline( $img, 0, 60 + $i * 70 + ( $seed * 7 ) % 30, $w, 20 + $i * 70, $line );
	}
	$file = sys_get_temp_dir() . "/ts-test-$seed-$w.jpg";
	imagejpeg( $img, $file, 80 );
	return $file;
}
function ts_test_attach( $seed, $w = 900, $h = 900 ) {
	$tmp = ts_test_image( $seed, $w, $h );
	$id  = media_handle_sideload( array( 'name' => "test-$seed.jpg", 'tmp_name' => $tmp ), 0 );
	return is_wp_error( $id ) ? 0 : $id;
}

// Categories.
$cats = array(
	'المطابخ'        => array( 'خزائن المطابخ', 'أسطح العمل' ),
	'الحمامات'       => array(),
	'المكاتب'        => array(),
	'المحلات التجارية' => array(),
	'ديكور المنزل'   => array( 'إضاءة', 'أرضيات' ),
);
$cat_ids = array();
$seed    = 0;
foreach ( $cats as $name => $kids ) {
	$t = term_exists( $name, 'product_cat' ) ?: wp_insert_term( $name, 'product_cat' );
	$id = is_array( $t ) ? $t['term_id'] : $t;
	update_term_meta( $id, 'thumbnail_id', ts_test_attach( $seed++, 640, 480 ) );
	$cat_ids[ $name ] = $id;
	foreach ( $kids as $kid ) {
		$kt = term_exists( $kid, 'product_cat' ) ?: wp_insert_term( $kid, 'product_cat', array( 'parent' => $id ) );
		$cat_ids[ $kid ] = is_array( $kt ) ? $kt['term_id'] : $kt;
	}
}

// Products (placeholder names/prices, TEST ONLY).
$names = array( 'منتج تجريبي أ', 'منتج تجريبي ب ذو اسم طويل جداً لاختبار اقتطاع العنوان في بطاقة المنتج على الجوال', 'منتج تجريبي ج', 'منتج تجريبي د', 'منتج تجريبي هـ', 'منتج تجريبي و', 'منتج تجريبي ز', 'منتج تجريبي ح', 'منتج تجريبي ط', 'منتج تجريبي ي', 'منتج تجريبي ك', 'منتج تجريبي ل', 'منتج تجريبي م', 'منتج تجريبي ن');
$cat_names = array_keys( $cat_ids );
foreach ( $names as $i => $n ) {
	if ( 7 === $i ) {
		$p = new WC_Product_Variable();
	} else {
		$p = new WC_Product_Simple();
	}
	$p->set_name( $n );
	$p->set_status( 'publish' );
	$p->set_short_description( 'وصف قصير تجريبي للمنتج.' );
	$p->set_description( 'وصف مفصل تجريبي للمنتج. هذا النص للاختبار فقط.' );
	$p->set_image_id( ts_test_attach( $i + 10 ) );
	$p->set_category_ids( array( $cat_ids[ $cat_names[ $i % count( $cat_names ) ] ] ) );
	if ( 7 !== $i ) {
		$p->set_regular_price( (string) ( 4000 + $i * 1250 ) );
		if ( 1 === $i % 3 ) {
			$p->set_sale_price( (string) ( 3500 + $i * 1000 ) );
		}
	}
	$p->set_featured( $i < 8 && 0 === $i % 2 );
	$p->set_manage_stock( true );
	$p->set_stock_quantity( 20 );
	if ( 7 === $i ) {
		$attr = new WC_Product_Attribute();
		$attr->set_name( 'اللون' );
		$attr->set_options( array( 'بلوط', 'جوز' ) );
		$attr->set_visible( true );
		$attr->set_variation( true );
		$p->set_attributes( array( $attr ) );
	}
	$pid = $p->save();
	if ( 7 === $i ) {
		foreach ( array( 'بلوط' => 9000, 'جوز' => 9500 ) as $col => $price ) {
			$v = new WC_Product_Variation();
			$v->set_parent_id( $pid );
			$v->set_attributes( array( 'اللون' => $col ) );
			$v->set_regular_price( (string) $price );
			$v->save();
		}
		WC_Product_Variable::sync( $pid );
	}
}

// Pages.
$lorem = '<p>نص تجريبي للاختبار فقط. هذه فقرة طويلة نسبياً لاختبار عرض النص العربي من اليمين إلى اليسار وتباعد الأسطر وقراءة المحتوى على الجوال والحاسوب. هذه الفقرة لا تمثل محتوى المتجر الحقيقي.</p><h2>عنوان فرعي تجريبي</h2><p>فقرة أخرى تحتوي على <a href="#">رابط</a> وعلى <strong>نص عريض</strong> وأرقام مثل 12 000 د.ج للاختبار.</p><ul><li>عنصر قائمة أول</li><li>عنصر قائمة ثانٍ</li></ul><blockquote>اقتباس تجريبي لاختبار التنسيق.</blockquote><h3>عنوان ثالث</h3><p>نهاية النص التجريبي.</p>';
$pages  = array(
	'home'       => array( 'الرئيسية', '' ),
	'about'      => array( 'من نحن', $lorem ),
	'contact'    => array( 'اتصل بنا', '<p>يمكنكم التواصل معنا عبر الوسائل التالية.</p>[ts_contact_cards]' ),
	'delivery'   => array( 'سياسة التوصيل', $lorem ),
	'categories' => array( 'الأقسام', '<p>تصفّح جميع أقسام المتجر.</p>' ),
);
$pid = array();
foreach ( $pages as $slug => $d ) {
	$pid[ $slug ] = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $d[0], 'post_name' => $slug, 'post_content' => $d[1] ) );
}
update_post_meta( $pid['categories'], '_wp_page_template', 'templates/page-categories.php' );
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $pid['home'] );
update_option( 'permalink_structure', '/%postname%/' );

// Posts for blog archive.
for ( $i = 1; $i <= 4; $i++ ) {
	wp_insert_post( array( 'post_status' => 'publish', 'post_title' => "مقالة تجريبية $i", 'post_content' => $lorem, 'post_excerpt' => 'ملخص تجريبي للمقالة لاختبار بطاقة المقال.' ) );
}

// Menus.
$m = wp_create_nav_menu( 'الرئيسية' );
wp_update_nav_menu_item( $m, 0, array( 'menu-item-title' => 'الرئيسية', 'menu-item-url' => home_url( '/' ), 'menu-item-status' => 'publish', 'menu-item-type' => 'custom' ) );
wp_update_nav_menu_item( $m, 0, array( 'menu-item-title' => 'المتجر', 'menu-item-object' => 'page', 'menu-item-object-id' => wc_get_page_id( 'shop' ), 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish' ) );
$parent = wp_update_nav_menu_item( $m, 0, array( 'menu-item-title' => 'الأقسام', 'menu-item-object' => 'page', 'menu-item-object-id' => $pid['categories'], 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish' ) );
foreach ( array( 'المطابخ', 'الحمامات', 'المكاتب' ) as $c ) {
	wp_update_nav_menu_item( $m, 0, array( 'menu-item-title' => $c, 'menu-item-object' => 'product_cat', 'menu-item-object-id' => $cat_ids[ $c ], 'menu-item-type' => 'taxonomy', 'menu-item-parent-id' => $parent, 'menu-item-status' => 'publish' ) );
}
wp_update_nav_menu_item( $m, 0, array( 'menu-item-title' => 'من نحن', 'menu-item-object' => 'page', 'menu-item-object-id' => $pid['about'], 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish' ) );
wp_update_nav_menu_item( $m, 0, array( 'menu-item-title' => 'اتصل بنا', 'menu-item-object' => 'page', 'menu-item-object-id' => $pid['contact'], 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish' ) );
$f = wp_create_nav_menu( 'التذييل' );
foreach ( array( 'delivery', 'about', 'contact' ) as $s ) {
	wp_update_nav_menu_item( $f, 0, array( 'menu-item-title' => get_the_title( $pid[ $s ] ), 'menu-item-object' => 'page', 'menu-item-object-id' => $pid[ $s ], 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish' ) );
}
set_theme_mod( 'nav_menu_locations', array( 'menu-1' => $m, 'menu-2' => $f ) );

// Customizer test values (fake).
foreach ( array(
	'ts_announcement'   => 'نص تجريبي لشريط الإعلان',
	'ts_phone'          => '0555 00 00 00',
	'ts_whatsapp'       => '+213 555 00 00 00',
	'ts_email'          => 'test@example.com',
	'ts_address'        => 'عنوان تجريبي للاختبار',
	'ts_hours'          => 'ساعات العمل التجريبية',
	'ts_footer_about'   => 'نبذة تجريبية قصيرة عن المتجر لاختبار التذييل.',
) as $k => $v ) {
	set_theme_mod( $k, $v );
}
flush_rewrite_rules();
echo "seeded\n";
