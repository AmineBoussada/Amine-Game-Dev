<?php
use PHPUnit\Framework\TestCase;
use DecoStore\Offers\OfferForm;
use DecoStore\Offers\OfferSchema;

class OfferFormTest extends TestCase {

	private function tz() {
		return new DateTimeZone( 'Africa/Algiers' );
	}

	/** @dataProvider percents */
	public function test_percent_to_basis_points( $text, $bp ) {
		$this->assertSame( $bp, OfferForm::percent_to_bp( $text ) );
	}

	public function percents() {
		return array(
			array( '10', 1000 ),
			array( '12.5', 1250 ),
			array( '12,5', 1250 ),
			array( '٢٠', 2000 ),
			array( '10%', 1000 ),
			array( '100', 10000 ),
			array( '0.01', 1 ),
			array( '1.234', null ),
			array( 'abc', null ),
			array( '', null ),
			array( '-5', null ),
			array( '1000', null ),
		);
	}

	public function test_basis_points_round_trip() {
		foreach ( array( 1, 100, 1250, 3333, 10000 ) as $bp ) {
			$this->assertSame( $bp, OfferForm::percent_to_bp( OfferForm::bp_to_percent( $bp ) ) );
		}
		$this->assertSame( '12.5', OfferForm::bp_to_percent( 1250 ) );
		$this->assertSame( '10', OfferForm::bp_to_percent( 1000 ) );
	}

	public function test_money_uses_store_decimals_and_rejects_garbage() {
		$this->assertSame( 1200, OfferForm::money( '1200', 0 ) );
		$this->assertSame( 120050, OfferForm::money( '1200.50', 2 ) );
		$this->assertNull( OfferForm::money( '12.5', 0 ) );
		$this->assertNull( OfferForm::money( 'abc', 0 ) );
		$this->assertNull( OfferForm::money( '', 0 ) );
	}

	public function test_dates_use_the_store_time_zone_and_round_trip() {
		$ts = OfferForm::date( '2026-12-01T09:30', $this->tz() );
		$this->assertSame( ( new DateTimeImmutable( '2026-12-01 09:30', $this->tz() ) )->getTimestamp(), $ts );
		$this->assertSame( '2026-12-01T09:30', OfferForm::date_text( $ts, $this->tz() ) );
		$this->assertNull( OfferForm::date( '', $this->tz() ) );
		$this->assertSame( 'tomorrow', OfferForm::date( 'tomorrow', $this->tz() ) ); // Left for the schema to refuse.
		$this->assertSame( '2026-02-30T10:00', OfferForm::date( '2026-02-30T10:00', $this->tz() ) );
	}

	public function test_tier_lines() {
		$tiers = OfferForm::tiers( "2|percent|10\n3 | fixed | 200\n5|price|900\n\nbad line", 0 );
		$this->assertCount( 4, $tiers );
		$this->assertSame( array( 'min_qty' => 2, 'kind' => 'percent', 'value' => 1000 ), $tiers[0] );
		$this->assertSame( array( 'min_qty' => 3, 'kind' => 'fixed_off', 'value' => 200 ), $tiers[1] );
		$this->assertSame( array( 'min_qty' => 5, 'kind' => 'unit_price', 'value' => 900 ), $tiers[2] );
		$this->assertNull( $tiers[3]['min_qty'] ); // The bad line is kept so the schema reports it.
		$norm = OfferSchema::normalize( array( 'label' => 'x', 'type' => 'qty_discount', 'tiers' => $tiers ) );
		$this->assertContains( 'bad_tier', array_column( $norm['errors'], 'code' ) );
		$clean = OfferForm::tiers( "2|percent|10\n3|fixed|200", 0 );
		$this->assertSame( "2|percent|10\n3|fixed|200", OfferForm::tiers_text( OfferSchema::normalize( array( 'label' => 'x', 'type' => 'qty_discount', 'tiers' => $clean ) )['definition']['tiers'], 0 ) );
	}

	public function test_items_and_gift() {
		$items = OfferForm::items( "10|2\n11\n12|1|77\nx" );
		$this->assertSame( array( 'product_id' => 10, 'qty' => 2, 'variation_id' => 0 ), $items[0] );
		$this->assertSame( array( 'product_id' => 11, 'qty' => 1, 'variation_id' => 0 ), $items[1] );
		$this->assertSame( 77, $items[2]['variation_id'] );
		$this->assertNull( $items[3]['product_id'] );
		$this->assertSame( "10|2\n11|1\n12|1|77", OfferForm::items_text( array_slice( $items, 0, 3 ) ) );
		$this->assertSame( array( 'product_id' => 5, 'variation_id' => 0, 'qty' => 1 ), OfferForm::gift( '5', '', '' ) );
		$this->assertNull( OfferForm::gift( 'x', '', '2' )['product_id'] );
		$this->assertSame( 3, OfferForm::gift( '٥', '', '٣' )['qty'] );
	}

	public function test_id_list() {
		$this->assertSame( array( 1, 22, 3 ), OfferForm::id_list( '1, 22،3 ;3 0 abc -4' ) );
		$this->assertSame( array(), OfferForm::id_list( null ) );
	}

	public function test_a_full_post_becomes_a_valid_offer() {
		$post = array(
			'label'            => 'عرض الكمية',
			'type'             => 'qty_discount',
			'status'           => 'active',
			'priority'         => '3',
			'selectable'       => '1',
			'starts_at'        => '2026-12-01T00:00',
			'ends_at'          => '2026-12-31T00:00',
			'scope_mode'       => 'products',
			'scope_products'   => '10, 11',
			'min_subtotal'     => '1000',
			'wilaya_ids'       => array( '5', '9' ),
			'delivery_methods' => array( 'home' ),
			'weekdays'         => array( '5', '6' ),
			'tiers'            => "2|percent|10\n3|percent|15",
		);
		$r = OfferSchema::normalize( OfferForm::canonical( $post, 0, $this->tz() ) );
		$this->assertSame( array(), $r['errors'] );
		$o = $r['definition'];
		$this->assertTrue( $o['selectable'] );
		$this->assertSame( array( 10, 11 ), $o['scope']['product_ids'] );
		$this->assertSame( 1000, $o['conditions']['min_subtotal'] );
		$this->assertSame( array( 5, 9 ), $o['conditions']['wilaya_ids'] );
		$this->assertSame( array( 5, 6 ), $o['conditions']['weekdays'] );
		$this->assertSame( 1500, $o['tiers'][1]['value'] );
		// Fields of other types in the POST are ignored.
		$post['type']  = 'free_gift';
		$post['gift_product'] = '50';
		$r = OfferSchema::normalize( OfferForm::canonical( $post, 0, $this->tz() ) );
		$this->assertSame( array(), $r['errors'] );
		$this->assertSame( array(), $r['definition']['tiers'] );
		$this->assertSame( 50, $r['definition']['gift']['product_id'] );
	}

	public function test_invalid_typed_values_are_reported_not_guessed() {
		$post = array( 'label' => 'x', 'type' => 'custom_rule', 'action_type' => 'percent_off', 'action_value' => 'abc', 'min_subtotal' => 'oops', 'starts_at' => 'soon', 'priority' => 'high' );
		$codes = array_column( OfferSchema::normalize( OfferForm::canonical( $post, 0, $this->tz() ) )['errors'], 'code' );
		foreach ( array( 'bad_action_value', 'bad_condition', 'bad_date', 'bad_priority' ) as $c ) {
			$this->assertContains( $c, $codes );
		}
	}

	public function test_gift_action_of_a_custom_rule() {
		$post = array( 'label' => 'g', 'type' => 'custom_rule', 'action_type' => 'add_gift', 'gift_product' => '9', 'gift_qty' => '2' );
		$r    = OfferSchema::normalize( OfferForm::canonical( $post, 0, $this->tz() ) );
		$this->assertSame( array(), $r['errors'] );
		$this->assertSame( 'gift', OfferSchema::group( $r['definition'] ) );
	}
}
