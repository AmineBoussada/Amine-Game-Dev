<?php
/**
 * Fixtures for order-ui.js (FICTIONAL data). php order-setup.php setup|count <phone>|cleanup
 */
$_SERVER['HTTP_HOST']   = '127.0.0.1:8080';
$_SERVER['REQUEST_URI'] = '/';
require __DIR__ . '/wordpress/wp-load.php';

use DecoStore\Core\Settings;
use DecoStore\Delivery\DeliveryService;
use DecoStore\Delivery\DeliverySettings;
use DecoStore\Delivery\GeoRepository;
use DecoStore\Delivery\PickupRepository;
use DecoStore\Delivery\RateRepository;

$cmd = isset( $argv[1] ) ? $argv[1] : 'setup';
if ( 'count' === $cmd ) {
	echo count( wc_get_orders( array( 'billing_phone' => $argv[2], 'limit' => -1, 'return' => 'ids', 'status' => array_keys( wc_get_order_statuses() ) ) ) );
	exit;
}
if ( 'make-order' === $cmd ) {
	// One order through the real pipeline, flagged as a possible duplicate (second order, same phone).
	$state = get_option( 'ds_test_order_ui', array() );
	$w     = DecoStore\Delivery\GeoRepository::find_by_code( 'wilaya', '93' );
	$c     = DecoStore\Delivery\GeoRepository::find_by_code( 'commune', '9301' );
	$ids   = array();
	for ( $i = 0; $i < 2; $i++ ) {
		$r     = DecoStore\Orders\Checkout::submit(
			array(
				'token'           => DecoStore\Orders\Token::issue( $state['product'], DecoStore\Forms\FormRepository::default_id(), time() - 30, DecoStore\Orders\Checkout::secret(), 'x' . $i ),
				'idempotency_key' => 'admin-ui-test-key-' . $i . '-' . wp_rand( 100000, 999999 ) . 'abcd',
				'product_id'      => $state['product'],
				'qty'             => 1,
				'fields'          => array(
					'full_name'       => 'زبون إدارة تجريبي',
					'phone'           => '0777000099',
					'wilaya'          => (string) $w['id'],
					'commune'         => (string) $c['id'],
					'delivery_method' => 'home',
					'address'         => 'حي تجريبي',
				),
			),
			'192.0.2.' . wp_rand( 1, 254 ),
			time()
		);
		$ids[] = isset( $r['body']['order_id'] ) ? $r['body']['order_id'] : wp_json_encode( $r );
	}
	echo wp_json_encode( $ids );
	exit;
}
if ( 'cleanup' === $cmd ) {
	$state = get_option( 'ds_test_order_ui', array() );
	foreach ( wc_get_orders( array( 'limit' => -1, 'status' => array_keys( wc_get_order_statuses() ) ) ) as $o ) {
		if ( 0 === strpos( (string) $o->get_billing_phone(), '0777' ) ) {
			$o->delete( true );
		}
	}
	foreach ( array( 'product', 'page' ) as $k ) {
		if ( ! empty( $state[ $k ] ) ) {
			wp_delete_post( $state[ $k ], true );
		}
	}
	delete_option( 'ds_test_order_ui' );
	echo "cleaned\n";
	exit;
}
$d = DeliveryService::decimals();
$u = (int) pow( 10, $d );
$s = Settings::all();
$s['max_attempts_per_ip'] = 200;
update_option( Settings::OPTION, $s );
$ds = DeliverySettings::all();
$ds['methods']['home']['enabled']   = '1';
$ds['methods']['office']['enabled'] = '1';
$ds['free_threshold']               = '';
update_option( DeliverySettings::OPTION, $ds );
$w = GeoRepository::upsert_wilaya( '93', 'ولاية تجريبية للواجهة' );
$w = is_array( $w ) ? $w['id'] : $w;
GeoRepository::set_active( $w, true );
$c = GeoRepository::upsert_commune( $w, '9301', 'بلدية تجريبية للواجهة' );
$c = is_array( $c ) ? $c['id'] : $c;
GeoRepository::set_active( $c, true );
RateRepository::upsert( 'home', $w, 500 * $u, true, $d );
RateRepository::upsert( 'office', $w, 250 * $u, true, $d );
PickupRepository::upsert( array( 'geo_id' => $c, 'name' => 'مكتب الواجهة التجريبي', 'carrier' => 'شركة تجريبية', 'address' => 'عنوان', 'active' => 1 ) );
$p = new WC_Product_Simple();
$p->set_name( 'مزهرية تجريبية' );
$p->set_regular_price( '2500' );
$p->set_manage_stock( true );
$p->set_stock_quantity( 50 );
$pid  = $p->save();
$page = wp_insert_post(
	array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => 'طلب تجريبي',
		'post_content' => '[ds_order_form product="' . $pid . '"]',
	)
);
update_option( 'ds_test_order_ui', array( 'product' => $pid, 'page' => $page ) );
echo wp_json_encode(
	array(
		'product' => $pid,
		'url'     => get_permalink( $page ),
		'wilaya'  => $w,
		'commune' => $c,
	)
);
