<?php
use PHPUnit\Framework\TestCase;
use DecoStore\Delivery\Money;

class MoneyTest extends TestCase {

	/**
	 * @dataProvider valid_amounts
	 */
	public function test_valid_amounts( $input, $decimals, $expected ) {
		$this->assertSame( $expected, Money::to_minor( $input, $decimals ) );
	}

	public function valid_amounts() {
		return array(
			'whole dinars'              => array( '400', 0, 400 ),
			'thousands with space'      => array( '1 200', 0, 1200 ),
			'non-breaking space'        => array( "1\u{00A0}200", 0, 1200 ),
			'two decimals, dot'         => array( '1200.5', 2, 120050 ),
			'two decimals, comma'       => array( '1200,50', 2, 120050 ),
			'trailing zero decimals ok' => array( '400.00', 0, 400 ),
			'arabic-indic digits'       => array( '٣٠٠', 0, 300 ),
			'zero'                      => array( '0', 0, 0 ),
			'leading zeros'             => array( '0050', 0, 50 ),
			'integer input'             => array( 750, 0, 750 ),
			'padded fraction'           => array( '12.3', 2, 1230 ),
		);
	}

	/**
	 * @dataProvider invalid_amounts
	 */
	public function test_invalid_amounts_are_rejected( $input, $decimals ) {
		$this->assertNull( Money::to_minor( $input, $decimals ) );
	}

	public function invalid_amounts() {
		return array(
			'negative'                 => array( '-5', 0 ),
			'letters'                  => array( '12a', 0 ),
			'empty'                    => array( '', 0 ),
			'two separators'           => array( '1.200.50', 2 ),
			'too precise'              => array( '10.5', 0 ),
			'too precise for 2'        => array( '10.505', 2 ),
			'float type'               => array( 10.5, 2 ),
			'null'                     => array( null, 0 ),
			'array'                    => array( array( '1' ), 0 ),
			'absurdly large'           => array( '9999999999999999', 0 ),
			'currency symbol'          => array( '400 د.ج', 0 ),
		);
	}

	public function test_to_decimal() {
		$this->assertSame( '1200', Money::to_decimal( 1200, 0 ) );
		$this->assertSame( '1200.50', Money::to_decimal( 120050, 2 ) );
		$this->assertSame( '0.05', Money::to_decimal( 5, 2 ) );
		$this->assertSame( '0.00', Money::to_decimal( 0, 2 ) );
		$this->assertSame( '-3.10', Money::to_decimal( -310, 2 ) );
	}

	public function test_round_trip() {
		foreach ( array( 0, 2, 3 ) as $d ) {
			foreach ( array( 0, 1, 99, 120050, 7000000 ) as $minor ) {
				$this->assertSame( $minor, Money::to_minor( Money::to_decimal( $minor, $d ), $d ), "$minor @ $d" );
			}
		}
	}
}
