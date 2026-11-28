<?php
use PHPUnit\Framework\TestCase;
use TopSolution\Core\Logger;

class LoggerTest extends TestCase {

	public function test_personal_data_and_costs_are_redacted() {
		$out = Logger::redact(
			array(
				'order_id'       => 123,
				'customer_phone' => '0555000000',
				'billing_name'   => 'مثال',
				'address_1'      => 'x',
				'email'          => 'a@b.c',
				'unit_cost'      => 100,
				'supplier_id'    => 4,
				'client_ip'      => '1.2.3.4',
				'form_token'     => 'abc',
			)
		);
		$this->assertSame( 123, $out['order_id'], 'Non-sensitive values pass through.' );
		foreach ( array( 'customer_phone', 'billing_name', 'address_1', 'email', 'unit_cost', 'supplier_id', 'client_ip', 'form_token' ) as $key ) {
			$this->assertSame( '[redacted]', $out[ $key ], $key );
		}
	}

	public function test_nested_arrays_are_redacted() {
		$out = Logger::redact( array( 'order' => array( 'id' => 5, 'phone' => '0555' ) ) );
		$this->assertSame( 5, $out['order']['id'] );
		$this->assertSame( '[redacted]', $out['order']['phone'] );
	}

	public function test_key_matching_is_case_insensitive() {
		$out = Logger::redact( array( 'CustomerEmail' => 'x', 'UNIT_COST' => 1 ) );
		$this->assertSame( '[redacted]', $out['CustomerEmail'] );
		$this->assertSame( '[redacted]', $out['UNIT_COST'] );
	}

	public function test_harmless_keys_that_merely_contain_ip_or_name_are_kept() {
		$out = Logger::redact(
			array(
				'description'     => 'ok',
				'shipping_method' => 'home',
				'filename'        => 'a.csv',
				'recipe'          => 'x',
				'order_id'        => 7,
			)
		);
		$this->assertSame( 'ok', $out['description'] );
		$this->assertSame( 'home', $out['shipping_method'] );
		$this->assertSame( 'a.csv', $out['filename'] );
		$this->assertSame( 'x', $out['recipe'] );
		$this->assertSame( 7, $out['order_id'] );
	}

	public function test_camel_case_and_kebab_case_keys_are_understood() {
		$this->assertTrue( Logger::is_sensitive_key( 'clientIp' ) );
		$this->assertTrue( Logger::is_sensitive_key( 'client-ip' ) );
		$this->assertTrue( Logger::is_sensitive_key( 'customerName' ) );
		$this->assertTrue( Logger::is_sensitive_key( 'customerphone' ) );
		$this->assertTrue( Logger::is_sensitive_key( 'wilaya' ) );
		$this->assertFalse( Logger::is_sensitive_key( 'order_status' ) );
	}

	public function test_empty_context_is_fine() {
		$this->assertSame( array(), Logger::redact( array() ) );
	}
}
