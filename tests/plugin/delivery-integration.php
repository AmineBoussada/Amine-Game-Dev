<?php
/**
 * Delivery (Phase 2) integration checks against real WordPress + WooCommerce.
 * Uses FICTIONAL test geography only ("ولاية تجريبية"), never real data.
 * Run from the folder that contains wordpress/:  php delivery-integration.php
 */

$_SERVER['HTTP_HOST']   = '127.0.0.1:8080';
$_SERVER['REQUEST_URI'] = '/';
require __DIR__ . '/wordpress/wp-load.php';
wp_set_current_user( 1 );

use TopSolution\Core\Migrator;
use TopSolution\Core\Schema;
use TopSolution\Delivery\CsvImporter;
use TopSolution\Delivery\DeliveryService;
use TopSolution\Delivery\DeliverySettings;
use TopSolution\Delivery\GeoRepository;
use TopSolution\Delivery\Importer;
use TopSolution\Delivery\PickupRepository;
use TopSolution\Delivery\RateRepository;
use TopSolution\Delivery\ZoneSetup;

$failed = 0;
function check( $ok, $label ) {
	global $failed;
	echo ( $ok ? 'PASS ' : 'FAIL ' ) . $label . "\n";
	if ( ! $ok ) {
		++$failed;
	}
}
$d = DeliveryService::decimals();

check( (int) get_option( Migrator::OPTION ) === 2, 'database upgraded to version 2' );
check( Schema::tables_exist(), 'delivery tables exist (geo_units, delivery_rates, pickup_points)' );

// ---- Geography import: dry run writes nothing, commit writes, re-import updates.
$geo_csv = "wilaya_code;wilaya_name_ar;commune_code;commune_name_ar\n57;ولاية تجريبية أ;5701;بلدية تجريبية 1\n57;ولاية تجريبية أ;5702;بلدية تجريبية 2\n58;ولاية تجريبية ب;5801;بلدية تجريبية 3\n90;ولاية تجريبية خارج ووكومرس;;\nxx;خطأ;;\n";
$parsed  = CsvImporter::parse( 'geography', $geo_csv, $d );
check( 4 === count( $parsed['rows'] ) && isset( $parsed['errors'][6] ), 'CSV: 4 valid rows, the bad line is reported with its number' );
$before  = GeoRepository::count( 'wilaya' ) + GeoRepository::count( 'commune' );
$dry     = Importer::run( 'geography', $parsed['rows'], true, $d, 'TEST' );
check( GeoRepository::count( 'wilaya' ) + GeoRepository::count( 'commune' ) === $before, 'dry run writes nothing' );
$real = Importer::run( 'geography', $parsed['rows'], false, $d, 'TEST' );
check( $dry['created'] + $dry['updated'] === $real['created'] + $real['updated'], 'dry-run counts match the real import' );
$again = Importer::run( 'geography', $parsed['rows'], false, $d, 'TEST' );
check( 0 === $again['created'], 're-importing the same file only updates (no duplicates)' );
$w57 = GeoRepository::find_by_code( 'wilaya', '057' );
$w58 = GeoRepository::find_by_code( 'wilaya', '58' );
$c1  = GeoRepository::find_by_code( 'commune', '5701' );
$c2  = GeoRepository::find_by_code( 'commune', '5702' );
$c3  = GeoRepository::find_by_code( 'commune', '5801' );
check( $w57 && $c1 && $c1['parent_id'] === $w57['id'], 'codes are normalised ("057" = "57") and communes belong to their wilaya' );
$clash = Importer::run( 'geography', CsvImporter::parse( 'geography', "wilaya_code,wilaya_name_ar,commune_code,commune_name_ar\n58,ولاية تجريبية ب,5701,بلدية مسروقة\n" )['rows'], false, $d );
check( 1 === count( $clash['skipped'] ) && GeoRepository::find_by_code( 'commune', '5701' )['parent_id'] === $w57['id'], 'a commune code cannot be moved to another wilaya by import' );

// ---- Settings and rates.
update_option( DeliverySettings::OPTION, DeliverySettings::defaults() );
foreach ( array( 'home', 'office' ) as $m ) {
	RateRepository::delete( $m, 0 );
	foreach ( array( $w57, $w58, $c1, $c2, $c3 ) as $g ) {
		RateRepository::delete( $m, $g['id'] );
	}
}
$rates_csv = "method,wilaya_code,commune_code,fee,active\nhome,57,,400,1\nhome,57,5702,650,1\nhome,58,5801,0,0\noffice,57,,250,1\nhome,99,,100,1\n";
$rep       = Importer::run( 'rates', CsvImporter::parse( 'rates', $rates_csv, $d )['rows'], false, $d );
check( 4 === $rep['created'] && 'unknown_wilaya' === reset( $rep['skipped'] ), 'rates import: 4 created, unknown wilaya rejected' );

$q = DeliveryService::quote( 'home', $w57['id'], $c1['id'], 0 );
check( $q['available'] && 'wilaya' === $q['source'] && 400 === $q['fee_minor'], 'commune without its own price uses the wilaya price (400)' );
$q = DeliveryService::quote( 'home', $w57['id'], $c2['id'], 0 );
check( 650 === $q['fee_minor'] && 'commune' === $q['source'], 'commune price overrides the wilaya (650)' );
$q = DeliveryService::quote( 'home', $w58['id'], $c3['id'], 0 );
check( ! $q['available'] && 'not_served' === $q['reason'], 'commune marked "not served" is not available' );
$q = DeliveryService::quote( 'home', $w58['id'], 0, 0 );
check( ! $q['available'] && 'no_rate' === $q['reason'], 'no price anywhere = not available (no guessed price)' );
RateRepository::upsert( 'home', 0, 900, true, $d );
$q = DeliveryService::quote( 'home', $w58['id'], 0, 0 );
check( $q['available'] && 'default' === $q['source'] && 900 === $q['fee_minor'], 'default price applies to wilayas without their own' );
$q = DeliveryService::quote( 'home', $w58['id'], $c3['id'], 0 );
check( ! $q['available'], '"not served" commune still blocks the default price' );
$q = DeliveryService::quote( 'home', $w58['id'], $c1['id'], 0 );
check( ! $q['available'] && 'invalid_area' === $q['reason'], 'a commune of another wilaya is refused (tampered request)' );
GeoRepository::set_active( $w57['id'], false );
check( 'area_inactive' === DeliveryService::quote( 'home', $w57['id'], 0, 0 )['reason'], 'a switched-off wilaya is not served' );
GeoRepository::set_active( $w57['id'], true );

update_option( DeliverySettings::OPTION, DeliverySettings::sanitize( array( 'methods' => array( 'home' => array( 'enabled' => 1 ), 'office' => array( 'enabled' => 0 ) ), 'free_threshold' => '10000' ), $d )['settings'] );
check( 'method_disabled' === DeliveryService::quote( 'office', $w57['id'], 0, 0 )['reason'], 'a disabled method is not offered' );
$q = DeliveryService::quote( 'home', $w57['id'], $c1['id'], (int) \TopSolution\Delivery\Money::to_minor( '10000', $d ) );
check( $q['available'] && 0 === $q['fee_minor'] && 'free_threshold' === $q['reason'], 'free delivery from the threshold' );
update_option( DeliverySettings::OPTION, DeliverySettings::defaults() );

// ---- Pickup points.
$pk = Importer::run( 'pickups', CsvImporter::parse( 'pickups', "wilaya_code,commune_code,carrier,name,address,hours\n57,5701,شركة تجريبية,مكتب تجريبي 1,عنوان تجريبي,9-17\n57,,شركة تجريبية,مكتب الولاية التجريبي,عنوان,\n" )['rows'], false, $d );
check( $pk['created'] + $pk['updated'] === 2, 'pickup points imported' );
$names = wp_list_pluck( PickupRepository::for_area( $w57['id'], $c1['id'] ), 'name' );
check( in_array( 'مكتب تجريبي 1', $names, true ) && in_array( 'مكتب الولاية التجريبي', $names, true ), 'an area lists its commune offices and the wilaya-wide offices' );
check( ! in_array( 'مكتب تجريبي 1', wp_list_pluck( PickupRepository::for_area( $w57['id'], $c2['id'] ), 'name' ), true ), "another commune does not see that commune's office" );

// ---- WooCommerce shipping method and zone.
$zone = ZoneSetup::ensure();
check( $zone['zone_id'] > 0, 'WooCommerce shipping zone exists with the method' );
check( ZoneSetup::ensure()['created'] === false, 'zone setup is idempotent (no duplicate zone)' );
WC()->shipping()->load_shipping_methods();
check( isset( WC()->shipping()->get_shipping_method_class_names()['ts_delivery'] ), 'shipping method registered in WooCommerce' );

function ts_test_rates( $state, $city, $cost ) {
	$package = array(
		'contents'        => array(),
		'contents_cost'   => $cost,
		'applied_coupons' => array(),
		'user'            => array( 'ID' => 0 ),
		'destination'     => array( 'country' => 'DZ', 'state' => $state, 'postcode' => '', 'city' => $city, 'address' => '', 'address_2' => '' ),
	);
	WC()->shipping()->reset_shipping();
	$result = WC()->shipping()->calculate_shipping_for_package( $package, 'ts-test-' . md5( $state . $city . $cost . microtime() ) );
	$out    = array();
	foreach ( $result['rates'] as $rate ) {
		$out[ $rate->get_meta_data()['ts_method'] ?? $rate->get_id() ] = (float) $rate->get_cost();
	}
	return $out;
}
$r = ts_test_rates( 'DZ-57', 'بلدية تجريبية 2', 3000 );
check( isset( $r['home'] ) && 650.0 === $r['home'] && isset( $r['office'] ) && 250.0 === $r['office'], 'WooCommerce rates for DZ-57 / commune 2: home 650, office 250' );
$r = ts_test_rates( 'DZ-57', 'مدينة غير معروفة', 3000 );
check( isset( $r['home'] ) && 400.0 === $r['home'], 'unknown city falls back to the wilaya price' );
$r = ts_test_rates( 'DZ-58', 'بلدية تجريبية 3', 3000 );
check( ! isset( $r['home'] ), 'not-served commune gets no home-delivery rate in WooCommerce' );
check( null === \TopSolution\Delivery\GeoCode::to_wc_state( '90' ), 'wilaya 90 has no WooCommerce state code (shown as a warning in the admin)' );

echo $failed ? "\n$failed FAILED\n" : "\nALL PASSED\n";
exit( $failed ? 1 : 0 );
