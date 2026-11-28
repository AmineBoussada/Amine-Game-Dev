<?php
use PHPUnit\Framework\TestCase;
use TopSolution\Delivery\RateCalculator;

class RateCalculatorTest extends TestCase {

	private function req( array $over = array() ) {
		return array_merge(
			array(
				'method'         => 'home',
				'wilaya_id'      => 10,
				'commune_id'     => 0,
				'wilaya_active'  => true,
				'commune_active' => true,
				'subtotal_minor' => 5000,
			),
			$over
		);
	}

	private function settings( array $over = array() ) {
		return array_merge(
			array(
				'enabled_methods'      => array( 'home', 'office' ),
				'free_threshold_minor' => null,
			),
			$over
		);
	}

	private function rate( $id, $fee, $active = true ) {
		return array( 'id' => $id, 'fee_minor' => $fee, 'active' => $active );
	}

	public function test_wilaya_rate_is_used() {
		$q = RateCalculator::quote( $this->req(), array( 'home:10' => $this->rate( 1, 400 ) ), $this->settings() );
		$this->assertTrue( $q['available'] );
		$this->assertSame( 400, $q['fee_minor'] );
		$this->assertSame( 'wilaya', $q['source'] );
		$this->assertSame( 1, $q['rate_id'] );
	}

	public function test_commune_rate_wins_over_wilaya() {
		$rates = array( 'home:10' => $this->rate( 1, 400 ), 'home:101' => $this->rate( 2, 600 ) );
		$q     = RateCalculator::quote( $this->req( array( 'commune_id' => 101 ) ), $rates, $this->settings() );
		$this->assertSame( 600, $q['fee_minor'] );
		$this->assertSame( 'commune', $q['source'] );
	}

	public function test_falls_back_from_commune_to_wilaya_to_default() {
		$q = RateCalculator::quote( $this->req( array( 'commune_id' => 101 ) ), array( 'home:10' => $this->rate( 1, 400 ) ), $this->settings() );
		$this->assertSame( 'wilaya', $q['source'] );
		$q = RateCalculator::quote( $this->req( array( 'commune_id' => 101 ) ), array( 'home:0' => $this->rate( 9, 700 ) ), $this->settings() );
		$this->assertSame( 'default', $q['source'] );
		$this->assertSame( 700, $q['fee_minor'] );
	}

	public function test_no_rate_anywhere_means_not_available_never_a_guess() {
		$q = RateCalculator::quote( $this->req(), array( 'office:10' => $this->rate( 1, 300 ) ), $this->settings() );
		$this->assertFalse( $q['available'] );
		$this->assertSame( 'no_rate', $q['reason'] );
		$this->assertSame( 0, $q['fee_minor'] );
	}

	public function test_a_switched_off_commune_blocks_the_wilaya_price() {
		$rates = array( 'home:10' => $this->rate( 1, 400 ), 'home:101' => $this->rate( 2, 0, false ) );
		$q     = RateCalculator::quote( $this->req( array( 'commune_id' => 101 ) ), $rates, $this->settings() );
		$this->assertFalse( $q['available'] );
		$this->assertSame( 'not_served', $q['reason'] );
		$this->assertSame( 'commune', $q['source'] );
	}

	public function test_a_switched_off_wilaya_blocks_the_default_price() {
		$rates = array( 'home:0' => $this->rate( 9, 700 ), 'home:10' => $this->rate( 1, 0, false ) );
		$q     = RateCalculator::quote( $this->req(), $rates, $this->settings() );
		$this->assertFalse( $q['available'] );
		$this->assertSame( 'not_served', $q['reason'] );
	}

	public function test_disabled_method_is_not_available() {
		$q = RateCalculator::quote( $this->req(), array( 'home:10' => $this->rate( 1, 400 ) ), $this->settings( array( 'enabled_methods' => array( 'office' ) ) ) );
		$this->assertFalse( $q['available'] );
		$this->assertSame( 'method_disabled', $q['reason'] );
	}

	public function test_unknown_method_is_not_available() {
		$q = RateCalculator::quote( $this->req( array( 'method' => 'drone' ) ), array(), $this->settings() );
		$this->assertSame( 'method_disabled', $q['reason'] );
	}

	public function test_inactive_area_is_not_available() {
		$rates = array( 'home:10' => $this->rate( 1, 400 ) );
		$this->assertSame( 'area_inactive', RateCalculator::quote( $this->req( array( 'wilaya_active' => false ) ), $rates, $this->settings() )['reason'] );
		$this->assertSame( 'area_inactive', RateCalculator::quote( $this->req( array( 'commune_id' => 101, 'commune_active' => false ) ), $rates, $this->settings() )['reason'] );
	}

	public function test_missing_wilaya_is_not_available() {
		$q = RateCalculator::quote( $this->req( array( 'wilaya_id' => 0 ) ), array( 'home:0' => $this->rate( 9, 700 ) ), $this->settings() );
		$this->assertSame( 'no_area', $q['reason'] );
	}

	public function test_free_threshold() {
		$rates = array( 'home:10' => $this->rate( 1, 400 ) );
		$at    = RateCalculator::quote( $this->req( array( 'subtotal_minor' => 10000 ) ), $rates, $this->settings( array( 'free_threshold_minor' => 10000 ) ) );
		$below = RateCalculator::quote( $this->req( array( 'subtotal_minor' => 9999 ) ), $rates, $this->settings( array( 'free_threshold_minor' => 10000 ) ) );
		$this->assertSame( 0, $at['fee_minor'] );
		$this->assertTrue( $at['free'] );
		$this->assertSame( 'free_threshold', $at['reason'] );
		$this->assertSame( 400, $below['fee_minor'] );
		$this->assertFalse( $below['free'] );
	}

	public function test_free_threshold_never_overrides_a_blocked_area() {
		$rates = array( 'home:10' => $this->rate( 1, 400, false ) );
		$q     = RateCalculator::quote( $this->req( array( 'subtotal_minor' => 999999 ) ), $rates, $this->settings( array( 'free_threshold_minor' => 1 ) ) );
		$this->assertFalse( $q['available'] );
	}

	public function test_zero_fee_is_free_delivery() {
		$q = RateCalculator::quote( $this->req(), array( 'home:10' => $this->rate( 1, 0 ) ), $this->settings() );
		$this->assertTrue( $q['available'] );
		$this->assertTrue( $q['free'] );
		$this->assertSame( 0, $q['fee_minor'] );
	}

	public function test_negative_stored_fee_is_clamped_to_zero() {
		$q = RateCalculator::quote( $this->req(), array( 'home:10' => $this->rate( 1, -50 ) ), $this->settings() );
		$this->assertSame( 0, $q['fee_minor'] );
	}

	public function test_methods_are_independent() {
		$rates = array( 'home:10' => $this->rate( 1, 400 ), 'office:10' => $this->rate( 2, 250 ) );
		$this->assertSame( 400, RateCalculator::quote( $this->req(), $rates, $this->settings() )['fee_minor'] );
		$this->assertSame( 250, RateCalculator::quote( $this->req( array( 'method' => 'office' ) ), $rates, $this->settings() )['fee_minor'] );
	}
}
