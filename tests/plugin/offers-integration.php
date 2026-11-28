<?php
/**
 * Offers (Phase 4) integration checks against real WordPress + WooCommerce.
 * FICTIONAL products and geography only. Run from the folder that contains wordpress/:  php offers-integration.php
 */
$_SERVER['HTTP_HOST']   = '127.0.0.1:8080';
$_SERVER['REQUEST_URI'] = '/';
require __DIR__ . '/wordpress/wp-load.php';
wp_set_current_user( 1 );

use TopSolution\Delivery\DeliveryService;
use TopSolution\Delivery\DeliverySettings;
use TopSolution\Delivery\GeoRepository;
use TopSolution\Delivery\RateRepository;
use TopSolution\Offers\EngineInputs;
use TopSolution\Offers\OfferRepository;
use TopSolution\Offers\OfferSchema;

$failed = 0;
function check( $ok, $label ) {
	global $failed;
	echo ( $ok ? 'PASS ' : 'FAIL ' ) . $label . "\n";
	if ( ! $ok ) {
		++$failed;
	}
}
function mk_offer( array $in ) {
	$r = OfferSchema::normalize( $in );
	if ( $r['errors'] ) {
		echo 'INVALID TEST OFFER: ' . json_encode( $r['errors'] ) . "\n";
	}
	return OfferRepository::create( $r['definition'] );
}
$d = DeliveryService::decimals();
$u = (int) pow( 10, $d );

check( post_type_exists( 'ts_offer' ), 'ts_offer registered' );
$pt = get_post_type_object( 'ts_offer' );
check( ! $pt->public && ! $pt->show_in_rest, 'ts_offer is private and not in REST' );

// Fixtures.
$cat  = wp_insert_term( 'تصنيف تجريبي ' . wp_rand(), 'product_cat' );
$prod = new WC_Product_Simple();
$prod->set_name( 'منتج تجريبي للعروض' );
$prod->set_regular_price( '1000' );
$prod->set_category_ids( array( $cat['term_id'] ) );
$pid = $prod->save();
$gift = new WC_Product_Simple();
$gift->set_name( 'هدية تجريبية' );
$gift->set_regular_price( '300' );
$gift->set_manage_stock( true );
$gift->set_stock_quantity( 5 );
$gid = $gift->save();
$wil = GeoRepository::upsert_wilaya( '91', 'ولاية تجريبية ث' );
$wid = is_array( $wil ) ? (int) $wil['id'] : (int) $wil;
RateRepository::upsert( 'home', $wid, 600 * $u, true, $d );
$delivery_backup = get_option( 'ts_delivery_settings' );
$ds              = DeliverySettings::all();
$ds['methods']['home']['enabled'] = '1';
update_option( 'ts_delivery_settings', $ds );
$created = array();

$base = EngineInputs::price_single( array( 'product_id' => $pid, 'qty' => 1, 'wilaya_id' => $wid, 'method' => 'home' ) );
check( $base['ok'] && 1000 * $u === $base['subtotal_minor'], 'price comes from WooCommerce, never from the browser' );
check( 600 * $u === $base['delivery']['final_fee_minor'], 'delivery fee comes from the delivery tables' );
check( ( 1000 + 600 ) * $u === $base['grand_total_minor'], 'no offers: product + delivery' );

// Quantity discount.
$q = mk_offer( array( 'label' => 'خصم الكمية', 'type' => 'qty_discount', 'status' => 'active', 'tiers' => array( array( 'min_qty' => 2, 'kind' => 'percent', 'value' => 1000 ) ) ) );
$created[] = $q;
$r = EngineInputs::price_single( array( 'product_id' => $pid, 'qty' => 2, 'wilaya_id' => $wid, 'method' => 'home' ) );
check( 1800 * $u === $r['lines_total_minor'] && $r['applied'][0]['offer_id'] === $q && 1 === $r['applied'][0]['version'], 'qty discount applies with its version' );
$r = EngineInputs::price_single( array( 'product_id' => $pid, 'qty' => 1, 'wilaya_id' => $wid, 'method' => 'home' ) );
check( 1000 * $u === $r['lines_total_minor'], 'below the tier: base price' );

// Editing bumps the version; drafts never apply.
$def = OfferRepository::get( $q );
unset( $def['id'], $def['version'] );
$def['tiers'][0]['value'] = 2000;
OfferRepository::update( $q, OfferSchema::normalize( $def )['definition'] );
check( 2 === OfferRepository::get( $q )['version'], 'update bumps the version' );
$r = EngineInputs::price_single( array( 'product_id' => $pid, 'qty' => 2, 'wilaya_id' => $wid, 'method' => 'home' ) );
check( 1600 * $u === $r['lines_total_minor'] && 2 === $r['applied'][0]['version'], 'new version is used' );
check( OfferRepository::set_status( $q, 'draft' ), 'set draft' );
$r = EngineInputs::price_single( array( 'product_id' => $pid, 'qty' => 2, 'wilaya_id' => $wid, 'method' => 'home' ) );
check( 2000 * $u === $r['lines_total_minor'], 'draft offer is not applied' );
OfferRepository::set_status( $q, 'active' );

// Free gift with stock, then out of stock.
$g = mk_offer( array( 'label' => 'هدية', 'type' => 'free_gift', 'status' => 'active', 'gift' => array( 'product_id' => $gid, 'qty' => 2 ) ) );
$created[] = $g;
$r = EngineInputs::price_single( array( 'product_id' => $pid, 'qty' => 1, 'wilaya_id' => $wid, 'method' => 'home' ) );
check( 1 === count( $r['gifts'] ) && $r['gifts'][0]['product_id'] === $gid, 'gift offered when in stock' );
check( ( 1000 + 600 ) * $u === $r['grand_total_minor'], 'gift is free' );
$gift->set_stock_quantity( 1 );
$gift->save();
$r = EngineInputs::price_single( array( 'product_id' => $pid, 'qty' => 1, 'wilaya_id' => $wid, 'method' => 'home' ) );
check( 0 === count( $r['gifts'] ), 'gift withdrawn when stock is below its quantity' );
$gift->set_stock_quantity( 5 );
$gift->save();

// Delivery offer + order discount.
$fd = mk_offer( array( 'label' => 'توصيل مجاني', 'type' => 'custom_rule', 'status' => 'active', 'conditions' => array( 'min_qty' => 2 ), 'action' => array( 'type' => 'free_delivery' ) ) );
$created[] = $fd;
$r = EngineInputs::price_single( array( 'product_id' => $pid, 'qty' => 2, 'wilaya_id' => $wid, 'method' => 'home' ) );
check( 0 === $r['delivery']['final_fee_minor'] && 600 * $u === $r['delivery']['discount_minor'], 'free-delivery offer removes the fee' );
$r = EngineInputs::price_single( array( 'product_id' => $pid, 'qty' => 2 ) );
check( ! $r['delivery']['selected'], 'no area chosen: delivery not priced, offer not applied' );

// Selectable offer: tampered id refused.
$card = mk_offer( array( 'label' => 'بطاقة', 'type' => 'custom_rule', 'status' => 'active', 'selectable' => true, 'action' => array( 'type' => 'percent_off', 'value' => 5000 ) ) );
$created[] = $card;
$r = EngineInputs::price_single( array( 'product_id' => $pid, 'qty' => 1, 'wilaya_id' => $wid, 'method' => 'home', 'chosen_offer_id' => $card ) );
check( 500 * $u === $r['lines_total_minor'], 'chosen selectable offer applies' );
$r = EngineInputs::price_single( array( 'product_id' => $pid, 'qty' => 1, 'wilaya_id' => $wid, 'method' => 'home', 'chosen_offer_id' => $q ) );
check( ! $r['ok'] && 'chosen_offer_invalid' === $r['error'], 'tampered/ineligible chosen id is refused' );

// Bad products and quantities.
check( 'product_unavailable' === EngineInputs::price_single( array( 'product_id' => 999999, 'qty' => 1 ) )['error'], 'unknown product refused' );
check( 'bad_quantity' === EngineInputs::price_single( array( 'product_id' => $pid, 'qty' => 0 ) )['error'], 'quantity 0 refused' );
check( 'bad_quantity' === EngineInputs::price_single( array( 'product_id' => $pid, 'qty' => 100000 ) )['error'], 'huge quantity refused' );

// Native sale price is the base.
$prod->set_sale_price( '800' );
$prod->save();
$r = EngineInputs::price_single( array( 'product_id' => $pid, 'qty' => 1 ) );
check( 800 * $u === $r['lines_total_minor'], 'WooCommerce sale price is the base price' );
$prod->set_sale_price( '' );
$prod->save();

// Duplicate, archive, corrupt, delete.
$copy = OfferRepository::duplicate( $q );
$created[] = $copy;
check( $copy && 'draft' === OfferRepository::get( $copy )['status'] && 1 === OfferRepository::get( $copy )['version'], 'duplicate is a fresh draft' );
update_post_meta( $copy, OfferRepository::META, wp_slash( '{"label":"x","type":"qty_discount","status":"active","tiers":[]}' ) );
$bad = OfferRepository::get( $copy );
check( 'draft' === $bad['status'] && ! empty( $bad['invalid'] ), 'a stored offer that no longer validates is forced to draft' );
check( ! OfferRepository::set_status( $copy, 'active' ), 'an invalid offer cannot be activated' );
check( OfferRepository::delete( $copy ) && ! OfferRepository::get( $copy ), 'delete' );
check( ! in_array( 'ts_offer', get_post_types( array( 'public' => true ) ), true ), 'not a public post type' );

// Cleanup.
foreach ( $created as $id ) {
	OfferRepository::delete( $id );
}
wp_delete_post( $pid, true );
wp_delete_post( $gid, true );
update_option( 'ts_delivery_settings', $delivery_backup );
echo $failed ? "FAILED $failed\n" : "ALL OK\n";
exit( $failed ? 1 : 0 );
