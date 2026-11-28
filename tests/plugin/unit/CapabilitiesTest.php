<?php
use PHPUnit\Framework\TestCase;
use TopSolution\Core\Capabilities;

class CapabilitiesTest extends TestCase {

	public function test_there_are_nine_unique_prefixed_capabilities() {
		$all = Capabilities::all();
		$this->assertCount( 9, $all );
		$this->assertSame( $all, array_unique( $all ) );
		foreach ( $all as $cap ) {
			$this->assertStringStartsWith( 'ts_', $cap );
		}
	}

	public function test_administrator_gets_everything() {
		$map = Capabilities::default_role_map();
		$this->assertEqualsCanonicalizing( Capabilities::all(), $map['administrator'] );
	}

	public function test_costs_suppliers_and_settings_are_administrator_only() {
		$map = Capabilities::default_role_map();
		foreach ( array( Capabilities::VIEW_COSTS, Capabilities::MANAGE_SUPPLIERS, Capabilities::MANAGE_SETTINGS ) as $cap ) {
			$this->assertContains( $cap, $map['administrator'] );
			foreach ( $map as $role => $caps ) {
				if ( 'administrator' !== $role ) {
					$this->assertNotContains( $cap, $caps, "$role must not have $cap by default." );
				}
			}
		}
	}

	public function test_shop_manager_only_gets_known_capabilities() {
		$map = Capabilities::default_role_map();
		$this->assertSame( array(), array_diff( $map['shop_manager'], Capabilities::all() ) );
		$this->assertNotEmpty( $map['shop_manager'] );
	}
}
