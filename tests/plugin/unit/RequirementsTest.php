<?php
use PHPUnit\Framework\TestCase;
use TopSolution\Core\Requirements;

class RequirementsTest extends TestCase {

	private function env( $php = '8.2.0', $wp = '6.8', $wc = '9.0.0' ) {
		return array( 'php' => $php, 'wp' => $wp, 'wc' => $wc );
	}

	public function test_a_good_environment_has_no_problems() {
		$this->assertSame( array(), Requirements::evaluate( $this->env() ) );
	}

	public function test_exact_minimums_pass() {
		$this->assertSame( array(), Requirements::evaluate( $this->env( '7.4.0', '6.4', '8.0' ) ) );
	}

	public function test_old_php_is_reported() {
		$this->assertSame( array( 'php' ), Requirements::evaluate( $this->env( '7.3.33' ) ) );
	}

	public function test_old_wordpress_is_reported() {
		$this->assertSame( array( 'wp' ), Requirements::evaluate( $this->env( '8.2.0', '6.3.9' ) ) );
	}

	public function test_missing_woocommerce_is_reported() {
		$this->assertSame( array( 'wc_missing' ), Requirements::evaluate( $this->env( '8.2.0', '6.8', null ) ) );
	}

	public function test_old_woocommerce_is_reported() {
		$this->assertSame( array( 'wc_version' ), Requirements::evaluate( $this->env( '8.2.0', '6.8', '7.9.0' ) ) );
	}

	public function test_several_problems_are_all_reported() {
		$this->assertSame( array( 'php', 'wp', 'wc_missing' ), Requirements::evaluate( $this->env( '7.0', '5.0', null ) ) );
	}

	public function test_custom_minimums_are_honoured() {
		$this->assertSame( array( 'wc_version' ), Requirements::evaluate( $this->env(), array( 'wc' => '10.0' ) ) );
	}

	public function test_every_problem_code_has_a_message() {
		foreach ( array( 'php', 'wp', 'wc_missing', 'wc_version' ) as $code ) {
			$this->assertNotSame( '', Requirements::message( $code ), $code );
		}
		$this->assertSame( '', Requirements::message( 'unknown' ) );
	}
}
