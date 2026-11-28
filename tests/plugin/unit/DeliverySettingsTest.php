<?php
use PHPUnit\Framework\TestCase;
use TopSolution\Delivery\DeliverySettings;

class DeliverySettingsTest extends TestCase {

	public function test_defaults_have_no_price_and_no_free_threshold() {
		$d = DeliverySettings::defaults();
		$this->assertSame( '', $d['free_threshold'] );
		$this->assertSame( array( 'home', 'office' ), array_keys( $d['methods'] ) );
	}

	public function test_methods_and_labels() {
		$out = DeliverySettings::sanitize(
			array(
				'methods' => array(
					'home'   => array( 'enabled' => '1', 'label' => '<i>للمنزل</i>' ),
					'office' => array( 'label' => '' ),
					'drone'  => array( 'enabled' => '1' ),
				),
			),
			0
		);
		$this->assertTrue( $out['settings']['methods']['home']['enabled'] );
		$this->assertSame( 'للمنزل', $out['settings']['methods']['home']['label'] );
		$this->assertFalse( $out['settings']['methods']['office']['enabled'], 'Unchecked = off.' );
		$this->assertNotSame( '', $out['settings']['methods']['office']['label'], 'Empty label keeps the default.' );
		$this->assertArrayNotHasKey( 'drone', $out['settings']['methods'] );
	}

	public function test_free_threshold() {
		$this->assertSame( '15000', DeliverySettings::sanitize( array( 'free_threshold' => '15 000' ), 0 )['settings']['free_threshold'] );
		$this->assertSame( '15000.00', DeliverySettings::sanitize( array( 'free_threshold' => '15000' ), 2 )['settings']['free_threshold'] );
		$bad = DeliverySettings::sanitize( array( 'free_threshold' => 'abc' ), 0 );
		$this->assertSame( '', $bad['settings']['free_threshold'] );
		$this->assertSame( array( 'bad_free_threshold' ), $bad['errors'] );
		$this->assertSame( array( 'bad_free_threshold' ), DeliverySettings::sanitize( array( 'free_threshold' => '0' ), 0 )['errors'], 'Zero would make all delivery free: rejected.' );
	}

	public function test_garbage_input_gives_defaults() {
		$this->assertEquals( DeliverySettings::defaults()['free_threshold'], DeliverySettings::sanitize( 'x', 0 )['settings']['free_threshold'] );
	}
}
