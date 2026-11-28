<?php
use PHPUnit\Framework\TestCase;
use DecoStore\Orders\Token;

class TokenTest extends TestCase {

	const SECRET = 'test-secret';

	public function test_round_trip() {
		$t = Token::issue( 12, 3, 1000, self::SECRET, 'abc' );
		$v = Token::verify( $t, self::SECRET, 1100, 3600 );
		$this->assertTrue( $v['ok'] );
		$this->assertSame( 12, $v['product_id'] );
		$this->assertSame( 3, $v['form_id'] );
		$this->assertSame( 1000, $v['issued_at'] );
	}

	public function test_expiry_boundary() {
		$t = Token::issue( 1, 1, 1000, self::SECRET, 'n' );
		$this->assertTrue( Token::verify( $t, self::SECRET, 1000 + 3600, 3600 )['ok'] );
		$this->assertSame( 'expired', Token::verify( $t, self::SECRET, 1000 + 3601, 3600 )['error'] );
	}

	public function test_wrong_secret_or_tampering_is_refused() {
		$t = Token::issue( 1, 1, 1000, self::SECRET, 'n' );
		$this->assertSame( 'bad_token', Token::verify( $t, 'other', 1000, 3600 )['error'] );
		list( $payload, $sig ) = explode( '.', $t );
		// Change the product id inside the payload but keep the signature.
		$forged = rtrim( strtr( base64_encode( json_encode( array( 'p' => 2, 'f' => 1, 't' => 1000, 'n' => 'n' ) ) ), '+/', '-_' ), '=' );
		$this->assertSame( 'bad_token', Token::verify( $forged . '.' . $sig, self::SECRET, 1000, 3600 )['error'] );
		$this->assertSame( 'bad_token', Token::verify( $payload . '.' . strrev( $sig ), self::SECRET, 1000, 3600 )['error'] );
	}

	public function test_garbage_is_refused() {
		foreach ( array( '', 'abc', 'a.b.c', null, array(), 123, str_repeat( 'a', 600 ) . '.x', '!!!.???' ) as $bad ) {
			$this->assertFalse( Token::verify( $bad, self::SECRET, 1000, 3600 )['ok'], var_export( $bad, true ) );
		}
	}

	public function test_signed_but_malformed_payload_is_refused() {
		$payload = rtrim( strtr( base64_encode( json_encode( array( 'p' => '1', 'f' => 1, 't' => 1000 ) ) ), '+/', '-_' ), '=' );
		$sig     = rtrim( strtr( base64_encode( hash_hmac( 'sha256', $payload, self::SECRET, true ) ), '+/', '-_' ), '=' );
		$this->assertSame( 'bad_token', Token::verify( $payload . '.' . $sig, self::SECRET, 1000, 3600 )['error'] );
	}

	public function test_token_from_the_future_is_refused() {
		$t = Token::issue( 1, 1, 5000, self::SECRET, 'n' );
		$this->assertSame( 'bad_token', Token::verify( $t, self::SECRET, 1000, 3600 )['error'] );
		$this->assertTrue( Token::verify( Token::issue( 1, 1, 1030, self::SECRET, 'n' ), self::SECRET, 1000, 3600 )['ok'] ); // Small clock skew tolerated.
	}

	public function test_tokens_differ_by_nonce() {
		$this->assertNotSame( Token::issue( 1, 1, 1000, self::SECRET, 'a' ), Token::issue( 1, 1, 1000, self::SECRET, 'b' ) );
	}
}
