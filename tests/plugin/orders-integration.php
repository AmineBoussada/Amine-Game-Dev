<?php
/**
 * Orders (Phase 5) integration checks against real WordPress + WooCommerce.
 * FICTIONAL products, geography and customers only.
 * Run from the folder that contains wordpress/:  php orders-integration.php
 */
$_SERVER['HTTP_HOST']   = '127.0.0.1:8080';
$_SERVER['REQUEST_URI'] = '/';
require __DIR__ . '/wordpress/wp-load.php';

use DecoStore\Core\Settings;
use DecoStore\Delivery\DeliveryService;
use DecoStore\Delivery\DeliverySettings;
use DecoStore\Delivery\GeoRepository;
use DecoStore\Delivery\Money;
use DecoStore\Delivery\PickupRepository;
use DecoStore\Delivery\RateRepository;
use DecoStore\Forms\FormRepository;
use DecoStore\Offers\OfferRepository;
use DecoStore\Offers\OfferSchema;
use DecoStore\Orders\Checkout;
use DecoStore\Orders\Confirmation;
use DecoStore\Orders\Idempotency;
use DecoStore\Orders\Token;

$failed = 0;
function check( $ok, $label ) {
	global $failed;
	echo ( $ok ? 'PASS ' : 'FAIL ' ) . $label . "\n";
	if ( ! $ok ) {
		++$failed;
	}
}
$hpos = \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
echo 'Order storage: ' . ( $hpos ? 'HPOS tables' : 'legacy posts' ) . "\n";

$d = DeliveryService::decimals();
$u = (int) pow( 10, $d );

// Capture e-mails instead of sending them.
$mails = array();
add_filter(
	'pre_wp_mail',
	static function ( $null, $atts ) use ( &$mails ) {
		$mails[] = $atts;
		return true;
	},
	10,
	2
);

// ---- Fixtures (fictional).
$settings_backup = get_option( Settings::OPTION );
$delivery_backup = get_option( DeliverySettings::OPTION );
$s               = Settings::all();
$s['max_attempts_per_ip']    = 200;
$s['duplicate_window_hours'] = 24;
$s['initial_order_status']   = 'processing';
update_option( Settings::OPTION, $s );
$ds = DeliverySettings::all();
$ds['methods']['home']['enabled']   = '1';
$ds['methods']['office']['enabled'] = '1';
$ds['free_threshold']               = '';
update_option( DeliverySettings::OPTION, $ds );

$w = GeoRepository::upsert_wilaya( '92', 'ولاية تجريبية للطلبات' );
$w = is_array( $w ) ? $w['id'] : $w;
GeoRepository::set_active( $w, true );
$c = GeoRepository::upsert_commune( $w, '9201', 'بلدية تجريبية للطلبات' );
$c = is_array( $c ) ? $c['id'] : $c;
GeoRepository::set_active( $c, true );
RateRepository::upsert( 'home', $w, 600 * $u, true, $d );
RateRepository::upsert( 'office', $w, 300 * $u, true, $d );
$pk = PickupRepository::upsert(
	array(
		'geo_id'  => $c,
		'name'    => 'مكتب تجريبي للطلبات',
		'carrier' => 'شركة تجريبية',
		'address' => 'عنوان تجريبي',
		'active'  => 1,
	)
)['id'];

$p = new WC_Product_Simple();
$p->set_name( 'منتج طلبات تجريبي' );
$p->set_regular_price( '1000' );
$p->set_manage_stock( true );
$p->set_stock_quantity( 40 );
$pid = $p->save();

$var = new WC_Product_Variable();
$var->set_name( 'منتج متغير تجريبي' );
$attr = new WC_Product_Attribute();
$attr->set_name( 'اللون' );
$attr->set_options( array( 'أبيض', 'رمادي' ) );
$attr->set_visible( true );
$attr->set_variation( true );
$var->set_attributes( array( $attr ) );
$vid = $var->save();
$v1  = new WC_Product_Variation();
$v1->set_parent_id( $vid );
$v1->set_attributes( array( sanitize_title( 'اللون' ) => 'أبيض' ) );
$v1->set_regular_price( '2000' );
$v1id = $v1->save();
WC_Product_Variable::sync( $vid );

$gift = new WC_Product_Simple();
$gift->set_name( 'هدية طلبات تجريبية' );
$gift->set_regular_price( '300' );
$gift->set_manage_stock( true );
$gift->set_stock_quantity( 3 );
$gid = $gift->save();

$form    = FormRepository::get( FormRepository::default_id() );
$run_ip  = '203.0.113.' . wp_rand( 1, 254 ); // A fresh address per run, so repeated runs do not hit the rate limit.
$created = array();
$offers  = array();

function fields( array $over = array() ) {
	global $w, $c;
	return array_merge(
		array(
			'full_name'       => 'زبون تجريبي',
			'phone'           => '0555 12 34 56',
			'wilaya'          => (string) $w,
			'commune'         => (string) $c,
			'delivery_method' => 'home',
			'address'         => 'حي تجريبي، شارع 1',
			'note'            => 'ملاحظة تجريبية',
		),
		$over
	);
}
function token_for( $product, $age = 30 ) {
	return Token::issue( $product, FormRepository::default_id(), time() - $age, Checkout::secret(), bin2hex( random_bytes( 4 ) ) );
}
function new_key() {
	return rtrim( strtr( base64_encode( random_bytes( 18 ) ), '+/', '-_' ), '=' );
}
function body( array $over = array(), array $field_over = array() ) {
	global $pid;
	return array_merge(
		array(
			'token'           => token_for( $pid ),
			'idempotency_key' => new_key(),
			'product_id'      => $pid,
			'qty'             => 2,
			'fields'          => fields( $field_over ),
		),
		$over
	);
}
function submit( $body, $ip = null ) {
	global $created, $run_ip;
	$ip = null === $ip ? $run_ip : $ip;
	$r = Checkout::submit( $body, $ip, time() );
	if ( ! empty( $r['body']['order_id'] ) ) {
		$created[ $r['body']['order_id'] ] = true;
	}
	return $r;
}

// ---- 1. Happy path.
$mails = array();
$r     = submit( body() );
check( 201 === $r['status'] && ! empty( $r['body']['order_id'] ), 'order created (201)' );
$order = wc_get_order( $r['body']['order_id'] );
check( $order && 'processing' === $order->get_status(), 'status is the owner setting (processing)' );
check( Money::to_minor( (string) $order->get_total(), $d ) === ( 2000 + 600 ) * $u, 'total = 2 x 1000 + delivery 600, computed on the server' );
check( 'cod' === $order->get_payment_method(), 'payment method: cash on delivery' );
check( '0555123456' === $order->get_billing_phone(), 'phone stored normalised (spaces removed)' );
check( 'زبون تجريبي' === $order->get_billing_first_name() && 'DZ' === $order->get_billing_country(), 'customer name and country' );
check( 'بلدية تجريبية للطلبات' === $order->get_billing_city(), 'city = commune' );
check( 'ملاحظة تجريبية' === $order->get_customer_note(), 'note saved as customer note' );
$ship = current( $order->get_items( 'shipping' ) );
check( $ship && 'ds_delivery' === $ship->get_method_id() && Money::to_minor( (string) $ship->get_total(), $d ) === 600 * $u, 'shipping line from the delivery tables' );
$snap = json_decode( $order->get_meta( '_ds_pricing_snapshot' ), true );
check( is_array( $snap ) && 1 === $snap['engine_version'] && ( 2600 * $u ) === $snap['grand_total_minor'], 'pricing snapshot stored' );
$dsnap = json_decode( $order->get_meta( '_ds_delivery_snapshot' ), true );
check( 'home' === $dsnap['method'] && $dsnap['commune_id'] === $c, 'delivery snapshot stored' );
check( 'landing_page' === $order->get_meta( '_ds_source' ), 'source meta' );
check( 38 === wc_get_product( $pid )->get_stock_quantity(), 'stock reduced natively (40 → 38)' );
$client = json_decode( $order->get_meta( '_ds_client_meta' ), true );
check( isset( $client['ip_hash'] ) && false === strpos( wp_json_encode( $order->get_meta_data() ), $run_ip ), 'network address stored only as a hash' );
$admin_mail = array_filter(
	$mails,
	static function ( $m ) {
		return false !== strpos( (string) ( is_array( $m['to'] ) ? implode( ',', $m['to'] ) : $m['to'] ), get_option( 'admin_email' ) );
	}
);
check( count( $admin_mail ) >= 1, 'WooCommerce new-order e-mail sent to the shop' );
$all_mail = wp_json_encode( $mails );
check( false === strpos( $all_mail, '_ds_' ) && false === strpos( $all_mail, 'ip_hash' ) && false === strpos( $all_mail, 'idempotency' ), 'e-mails contain no internal data' );

// Confirmation page.
$_GET = array(
	'ds_order' => (string) $order->get_id(),
	'key'      => $order->get_order_key(),
);
$found = Confirmation::current_order();
check( $found && $found->get_id() === $order->get_id(), 'confirmation: valid key opens the order' );
$html = Confirmation::html( $order );
check( false !== strpos( $html, $order->get_order_number() ) && false === strpos( $html, '_ds_' ) && false === strpos( $html, 'ip_hash' ), 'confirmation shows the number and no internal data' );
$_GET['key'] = 'wc_order_wrong';
check( null === Confirmation::current_order(), 'confirmation: wrong key refused' );
$_GET['key'] = $order->get_order_key();
$_GET['ds_order'] = (string) ( $order->get_id() + 99999 );
check( null === Confirmation::current_order(), 'confirmation: another order id with this key refused' );
$_GET = array();

// ---- 2. Idempotency.
$b     = body();
$one   = submit( $b );
$two   = submit( $b );
check( 201 === $one['status'] && 200 === $two['status'] && $one['body']['order_id'] === $two['body']['order_id'] && ! empty( $two['body']['repeat'] ), 'same key twice: one order, same confirmation' );
$k = new_key();
check( 'claimed' === Idempotency::claim( $k, time(), '__return_zero' )['state'], 'claim: first request proceeds' );
check( 'in_progress' === Idempotency::claim( $k, time(), '__return_zero' )['state'], 'claim: a concurrent duplicate waits (409)' );
check( 'claimed' === Idempotency::claim( $k, time() + 300, '__return_zero' )['state'], 'claim: a dead request is taken over after the stale delay' );
Idempotency::release( $k );

// ---- 3. Tampering and bad input.
$before = count( wc_get_orders( array( 'limit' => -1, 'return' => 'ids' ) ) );
$r = submit( body( array( 'price' => 1, 'total' => 1, 'unit_price' => 1, 'fields' => fields( array( 'price' => '1' ) ) ) ) );
check( 201 === $r['status'] && Money::to_minor( (string) wc_get_order( $r['body']['order_id'] )->get_total(), $d ) === 2600 * $u, 'price fields sent by the browser are ignored' );
$r = submit( body( array( 'expected_total' => '100' ) ) );
check( 409 === $r['status'] && 'price_changed' === $r['body']['error'] && ! empty( $r['body']['quote']['total'] ), 'displayed total differs: refused with the new total' );
$r = submit( body( array( 'expected_total' => (string) ( 2600 * $u ) ) ) );
check( 201 === $r['status'], 'displayed total matches: accepted' );
$r = submit( body( array( 'product_id' => $gid ) ) );
check( 400 === $r['status'] && 'bad_token' === $r['body']['error'], 'token for another product refused' );
$r = submit( body( array( 'token' => token_for( $pid, 13 * HOUR_IN_SECONDS ) ) ) );
check( 400 === $r['status'] && 'expired' === $r['body']['error'], 'expired token refused' );
$r = submit( body( array( 'token' => token_for( $pid, 0 ) ) ) );
check( 400 === $r['status'] && 'too_fast' === $r['body']['error'], 'form sent within 2 seconds refused (the page retries by itself)' );
$r = submit( body( array( 'hp' => 'http://spam' ) ) );
check( 400 === $r['status'] && 'rejected' === $r['body']['error'], 'honeypot refused' );
$r = submit( body( array( 'idempotency_key' => 'short' ) ) );
check( 400 === $r['status'], 'malformed idempotency key refused' );
$r = submit( body( array(), array( 'phone' => '12' ) ) );
check( 422 === $r['status'] && isset( $r['body']['fields']['phone'] ), 'invalid phone: field error' );
$r = submit( body( array(), array( 'full_name' => '' ) ) );
check( 422 === $r['status'] && isset( $r['body']['fields']['full_name'] ), 'missing name: field error' );
$r = submit( body( array(), array( 'wilaya' => '999999' ) ) );
check( 422 === $r['status'] && isset( $r['body']['fields']['wilaya'] ), 'unknown wilaya refused' );
$r = submit( body( array( 'qty' => 0 ) ) );
check( 422 === $r['status'], 'quantity 0 refused' );
$r = submit( body( array( 'qty' => 5000 ) ) );
check( 422 === $r['status'], 'huge quantity refused' );
$r = submit( 'not an array' );
check( 400 === $r['status'], 'non-object body refused' );
$after_bad = count( wc_get_orders( array( 'limit' => -1, 'return' => 'ids' ) ) );
check( $after_bad === $before + 2, 'only the two valid submissions created orders' );

// ---- 4. Delivery.
$r = submit( body( array(), array( 'delivery_method' => 'office', 'pickup_point' => (string) $pk, 'address' => '' ) ) );
$o = wc_get_order( $r['body']['order_id'] );
check( 201 === $r['status'] && Money::to_minor( (string) $o->get_total(), $d ) === 2300 * $u, 'office delivery: office fee' );
check( false !== strpos( $o->get_billing_address_1(), 'مكتب تجريبي للطلبات' ), 'office order: address = pickup office' );
RateRepository::upsert( 'office', $w, 300 * $u, false, $d );
$r = submit( body( array(), array( 'delivery_method' => 'office', 'pickup_point' => (string) $pk, 'address' => '' ) ) );
check( 422 === $r['status'] && isset( $r['body']['fields']['delivery_method'] ), 'method not served in this area: field error, no order' );
RateRepository::upsert( 'office', $w, 300 * $u, true, $d );

// ---- 5. Stock and variations.
$r = submit( body( array( 'qty' => 60 ) ) );
check( 409 === $r['status'] && 'out_of_stock' === $r['body']['error'], 'more than the stock: refused' );
$sold_out = wc_get_product( $pid );
$sold_out->set_stock_quantity( 0 );
$sold_out->save();
$r = submit( body() );
check( 409 === $r['status'] && 'product_unavailable' === $r['body']['error'], 'sold-out product refused' );
$sold_out->set_stock_quantity( 30 );
$sold_out->save();
$r = submit( body( array( 'token' => token_for( $vid ), 'product_id' => $vid ) ) );
check( 422 === $r['status'] && 'choose_variation' === $r['body']['error'], 'variable product needs a variation' );
$r = submit( body( array( 'token' => token_for( $vid ), 'product_id' => $vid, 'variation_id' => $gid ) ) );
check( 422 === $r['status'], 'a variation of another product is refused' );
$r = submit( body( array( 'token' => token_for( $vid ), 'product_id' => $vid, 'variation_id' => $v1id, 'qty' => 1 ) ) );
$o = wc_get_order( $r['body']['order_id'] );
check( 201 === $r['status'] && Money::to_minor( (string) $o->get_total(), $d ) === 2600 * $u && (int) current( $o->get_items() )->get_variation_id() === $v1id, 'variation ordered at its own price' );

// ---- 6. Offers.
$mk = static function ( array $in ) use ( &$offers ) {
	$id       = OfferRepository::create( OfferSchema::normalize( $in )['definition'] );
	$offers[] = $id;
	return $id;
};
$q  = $mk( array( 'label' => 'خصم كمية تجريبي', 'type' => 'qty_discount', 'status' => 'active', 'scope' => array( 'mode' => 'products', 'product_ids' => array( $pid ) ), 'tiers' => array( array( 'min_qty' => 2, 'kind' => 'percent', 'value' => 1000 ) ) ) );
$g  = $mk( array( 'label' => 'هدية تجريبية', 'type' => 'free_gift', 'status' => 'active', 'scope' => array( 'mode' => 'products', 'product_ids' => array( $pid ) ), 'gift' => array( 'product_id' => $gid, 'qty' => 1 ) ) );
$fo = $mk( array( 'label' => 'خصم 100 على الطلب', 'type' => 'custom_rule', 'status' => 'active', 'scope' => array( 'mode' => 'products', 'product_ids' => array( $pid ) ), 'action' => array( 'type' => 'fixed_off_order', 'value' => 100 * $u ) ) );
$fd = $mk( array( 'label' => 'توصيل بنصف السعر', 'type' => 'custom_rule', 'status' => 'active', 'scope' => array( 'mode' => 'products', 'product_ids' => array( $pid ) ), 'action' => array( 'type' => 'delivery_percent_off', 'value' => 5000 ) ) );
$gift_stock = wc_get_product( $gid )->get_stock_quantity();
$r = submit( body() );
$o = wc_get_order( $r['body']['order_id'] );
// 2 x 900 = 1800, minus 100 order discount, plus 300 delivery = 2000.
check( 201 === $r['status'] && Money::to_minor( (string) $o->get_total(), $d ) === 2000 * $u, 'offers combined: WooCommerce total equals the engine total (2000)' );
$gift_items = array_filter(
	$o->get_items(),
	static function ( $i ) {
		return '1' === (string) $i->get_meta( '_ds_is_gift' );
	}
);
check( 1 === count( $gift_items ) && 0.0 === (float) current( $gift_items )->get_total(), 'gift line at zero' );
check( $gift_stock - 1 === wc_get_product( $gid )->get_stock_quantity(), 'gift stock reduced too' );
check( 1 === count( $o->get_fees() ) && -100.0 === (float) current( $o->get_fees() )->get_total(), 'order discount as a negative fee line' );
$line = current(
	array_filter(
		$o->get_items(),
		static function ( $i ) {
			return '1' !== (string) $i->get_meta( '_ds_is_gift' );
		}
	)
);
check( 2000.0 === (float) $line->get_subtotal() && 1800.0 === (float) $line->get_total() && (int) $line->get_meta( '_ds_offer_id' ) === $q, 'product line: base subtotal, discounted total, offer id' );
$card = $mk( array( 'label' => 'بطاقة تجريبية', 'type' => 'custom_rule', 'status' => 'active', 'selectable' => true, 'conditions' => array( 'min_qty' => 5 ), 'action' => array( 'type' => 'percent_off', 'value' => 2000 ) ) );
$r = submit( body( array( 'offer_id' => $card ) ) );
check( 409 === $r['status'] && 'offer_changed' === $r['body']['error'] && ! empty( $r['body']['quote']['total'] ), 'chosen offer no longer eligible: refused with a fresh total, no order' );
$r = submit( body( array( 'offer_id' => $q ) ) );
check( 409 === $r['status'], 'choosing a non-selectable offer id is refused' );
foreach ( $offers as $oid ) {
	OfferRepository::delete( $oid );
}

// ---- 7. Duplicate suspect flag.
$r1 = submit( body( array(), array( 'phone' => '0666000111' ) ) );
$r2 = submit( body( array(), array( 'phone' => '0666000111' ) ) );
$f1 = json_decode( wc_get_order( $r1['body']['order_id'] )->get_meta( '_ds_flags' ), true );
$f2 = json_decode( wc_get_order( $r2['body']['order_id'] )->get_meta( '_ds_flags' ), true );
check( empty( $f1['duplicate_suspect'] ) && ! empty( $f2['duplicate_suspect'] ), 'second order with the same phone and product is flagged (not refused)' );

// ---- 8. Rate limit.
$s2                        = Settings::all();
$s2['max_attempts_per_ip'] = 3;
update_option( Settings::OPTION, $s2 );
$ip     = '198.51.100.' . wp_rand( 1, 250 );
$status = array();
for ( $i = 0; $i < 4; $i++ ) {
	$status[] = submit( body( array(), array( 'phone' => '12' ) ), $ip )['status'];
}
check( array( 422, 422, 422, 429 ) === $status, 'rate limit: 4th attempt from one address in 10 minutes refused' );
update_option( Settings::OPTION, $s );

// ---- 9. REST layer.
$req = new WP_REST_Request( 'POST', '/ds-core/v1/orders' );
$req->set_header( 'Content-Type', 'text/plain' );
$req->set_body( wp_json_encode( body() ) );
check( 415 === rest_do_request( $req )->get_status(), 'REST: non-JSON content type refused' );
$req = new WP_REST_Request( 'POST', '/ds-core/v1/orders' );
$req->set_header( 'Content-Type', 'application/json' );
$req->set_body( wp_json_encode( array( 'pad' => str_repeat( 'x', 40000 ) ) ) );
check( 413 === rest_do_request( $req )->get_status(), 'REST: oversized body refused' );
$req = new WP_REST_Request( 'GET', '/ds-core/v1/token' );
$req->set_query_params( array( 'product' => $pid, 'form' => FormRepository::default_id() ) );
$res = rest_do_request( $req );
check( 200 === $res->get_status() && ! empty( $res->get_data()['token'] ) && false !== strpos( $res->get_headers()['Cache-Control'], 'no-store' ), 'REST: token endpoint, not cacheable' );
$req = new WP_REST_Request( 'POST', '/ds-core/v1/quote' );
$req->set_header( 'Content-Type', 'application/json' );
$req->set_body( wp_json_encode( array( 'product_id' => $pid, 'qty' => 2, 'wilaya_id' => $w, 'method' => 'home' ) ) );
$res = rest_do_request( $req );
check( 200 === $res->get_status() && (string) ( 2600 * $u ) === $res->get_data()['total_minor'] && ! isset( $res->get_data()['rejected'] ), 'REST: quote returns the total and no internal data' );
$draft = wp_insert_post( array( 'post_type' => 'product', 'post_status' => 'draft', 'post_title' => 'مسودة' ) );
$req   = new WP_REST_Request( 'GET', '/ds-core/v1/token' );
$req->set_query_params( array( 'product' => $draft, 'form' => FormRepository::default_id() ) );
check( 404 === rest_do_request( $req )->get_status(), 'REST: no token for an unpublished product' );
wp_delete_post( $draft, true );

// ---- 10. Order meta not exposed through the WooCommerce REST API.
wp_set_current_user( 1 );
$req = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order->get_id() );
$res = rest_do_request( $req );
$keys = wp_list_pluck( isset( $res->get_data()['meta_data'] ) ? $res->get_data()['meta_data'] : array(), 'key' );
$item_keys = array();
foreach ( $res->get_data()['line_items'] as $li ) {
	$item_keys = array_merge( $item_keys, wp_list_pluck( $li['meta_data'], 'key' ) );
}
$has_ds = static function ( $list ) {
	return (bool) array_filter(
		$list,
		static function ( $k ) {
			return 0 === strpos( (string) $k, '_ds_' );
		}
	);
};
check( 200 === $res->get_status() && ! $has_ds( $keys ) && ! $has_ds( $item_keys ), 'WooCommerce REST shows no _ds_ meta (order and line items)' );
check( '' !== (string) wc_get_order( $order->get_id() )->get_meta( '_ds_pricing_snapshot' ), 'the data itself is still stored on the order' );
wp_set_current_user( 0 );

// ---- Cleanup.
foreach ( array_keys( $created ) as $oid ) {
	$o = wc_get_order( $oid );
	if ( $o ) {
		$o->delete( true );
	}
}
foreach ( array( $pid, $gid, $v1id, $vid ) as $id ) {
	wp_delete_post( $id, true );
}
PickupRepository::delete( $pk );
update_option( Settings::OPTION, $settings_backup );
update_option( DeliverySettings::OPTION, $delivery_backup );
echo $failed ? "FAILED $failed\n" : "ALL OK\n";
exit( $failed ? 1 : 0 );
