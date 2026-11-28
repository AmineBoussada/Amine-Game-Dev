<?php
use PHPUnit\Framework\TestCase;
use DecoStore\Forms\PhoneRule;

class PhoneRuleTest extends TestCase {

	/**
	 * @dataProvider generic_valid
	 */
	public function test_generic_accepts( $input, $expected ) {
		$this->assertSame( $expected, PhoneRule::normalize( $input, PhoneRule::GENERIC ) );
	}

	public function generic_valid() {
		return array(
			'plain'            => array( '0555123456', '0555123456' ),
			'spaces dashes'    => array( '05 55-12.34 56', '0555123456' ),
			'international'    => array( '+213555123456', '+213555123456' ),
			'arabic digits'    => array( '٠٥٥٥١٢٣٤٥٦', '0555123456' ),
			'parentheses'      => array( '(021) 12 34 56', '021123456' ),
			'eight digits'     => array( '12345678', '12345678' ),
		);
	}

	/**
	 * @dataProvider generic_invalid
	 */
	public function test_generic_rejects( $input ) {
		$this->assertNull( PhoneRule::normalize( $input, PhoneRule::GENERIC ) );
	}

	public function generic_invalid() {
		return array(
			'empty'      => array( '' ),
			'too short'  => array( '1234567' ),
			'too long'   => array( '1234567890123456' ),
			'letters'    => array( '05551a3456' ),
			'plus inside' => array( '05+55123456' ),
			'array'      => array( array( '0555123456' ) ),
			'null'       => array( null ),
		);
	}

	/**
	 * @dataProvider dz_valid
	 */
	public function test_dz_mobile_accepts_and_normalises( $input ) {
		$this->assertSame( '0555123456', PhoneRule::normalize( $input, PhoneRule::DZ_MOBILE ) );
	}

	public function dz_valid() {
		return array(
			'local'           => array( '0555123456' ),
			'spaced'          => array( '05 55 12 34 56' ),
			'plus 213'        => array( '+213555123456' ),
			'plus 213 spaced' => array( '+213 555 12 34 56' ),
			'00213'           => array( '00213555123456' ),
			'213'             => array( '213555123456' ),
			'arabic digits'   => array( '٠٥٥٥١٢٣٤٥٦' ),
		);
	}

	public function test_dz_mobile_accepts_05_06_07_only() {
		$this->assertSame( '0612345678', PhoneRule::normalize( '0612345678', PhoneRule::DZ_MOBILE ) );
		$this->assertSame( '0712345678', PhoneRule::normalize( '0712345678', PhoneRule::DZ_MOBILE ) );
		foreach ( array( '0412345678', '0812345678', '0212345678', '055512345', '05551234567', '+33612345678', 'abc' ) as $bad ) {
			$this->assertNull( PhoneRule::normalize( $bad, PhoneRule::DZ_MOBILE ), $bad );
		}
	}

	public function test_unknown_rule_behaves_like_generic() {
		$this->assertSame( '0555123456', PhoneRule::normalize( '0555123456', 'nope' ) );
	}

	public function test_browser_patterns_agree_with_the_server() {
		foreach ( array( PhoneRule::GENERIC, PhoneRule::DZ_MOBILE ) as $rule ) {
			$pattern = '/' . str_replace( '/', '\/', PhoneRule::js_pattern( $rule ) ) . '/';
			foreach ( array( '0555123456', '+213555123456', '12345678', '0412345678', '1234567', 'abc' ) as $sample ) {
				$prefix = PhoneRule::js_prefix( $rule );
				$test   = '' !== $prefix ? preg_replace( '/' . str_replace( '/', '\/', $prefix ) . '/', '0', $sample ) : $sample;
				$this->assertSame( null !== PhoneRule::normalize( $sample, $rule ), 1 === preg_match( $pattern, $test ), "$rule / $sample" );
			}
		}
	}
}
