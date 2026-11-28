<?php
use PHPUnit\Framework\TestCase;
use DecoStore\Delivery\GeoCode;

class GeoCodeTest extends TestCase {

	public function test_normalize() {
		$this->assertSame( '1', GeoCode::normalize( '01' ) );
		$this->assertSame( '16', GeoCode::normalize( ' 16 ' ) );
		$this->assertSame( '1601', GeoCode::normalize( '1601' ) );
		$this->assertSame( '0', GeoCode::normalize( '000' ) );
		$this->assertNull( GeoCode::normalize( '' ) );
		$this->assertNull( GeoCode::normalize( '1a' ) );
		$this->assertNull( GeoCode::normalize( '-1' ) );
	}

	public function test_display_wilaya() {
		$this->assertSame( '01', GeoCode::display_wilaya( '1' ) );
		$this->assertSame( '16', GeoCode::display_wilaya( '16' ) );
	}

	public function test_woocommerce_state_mapping() {
		$this->assertSame( 'DZ-01', GeoCode::to_wc_state( '1' ) );
		$this->assertSame( 'DZ-58', GeoCode::to_wc_state( '58' ) );
		$this->assertNull( GeoCode::to_wc_state( '59' ), 'WooCommerce has no state code above 58.' );
		$this->assertNull( GeoCode::to_wc_state( '0' ) );
		$this->assertSame( '16', GeoCode::from_wc_state( 'DZ-16' ) );
		$this->assertSame( '1', GeoCode::from_wc_state( 'DZ-01' ) );
		$this->assertNull( GeoCode::from_wc_state( 'FR-75' ) );
		$this->assertNull( GeoCode::from_wc_state( null ) );
	}
}
