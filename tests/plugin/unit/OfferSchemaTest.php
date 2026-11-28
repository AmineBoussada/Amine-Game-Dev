<?php
use PHPUnit\Framework\TestCase;
use DecoStore\Offers\OfferSchema;

class OfferSchemaTest extends TestCase {

	private function codes( array $input ) {
		return array_column( OfferSchema::normalize( $input )['errors'], 'code' );
	}

	private function qty() {
		return array(
			'label' => 'x',
			'type'  => 'qty_discount',
			'tiers' => array( array( 'min_qty' => 2, 'kind' => 'percent', 'value' => 1000 ) ),
		);
	}

	public function test_valid_definitions_of_each_type() {
		$valid = array(
			$this->qty(),
			array( 'label' => 'b', 'type' => 'bundle', 'items' => array( array( 'product_id' => 1 ), array( 'product_id' => 2, 'qty' => 2 ) ), 'bundle_price' => array( 'kind' => 'price', 'value' => 5000 ) ),
			array( 'label' => 'g', 'type' => 'free_gift', 'gift' => array( 'product_id' => 3 ) ),
			array( 'label' => 'c', 'type' => 'custom_rule', 'action' => array( 'type' => 'free_delivery' ) ),
		);
		foreach ( $valid as $input ) {
			$this->assertSame( array(), $this->codes( $input ), $input['type'] );
		}
	}

	public function test_normalising_twice_is_stable() {
		$once = OfferSchema::normalize( $this->qty() );
		$twice = OfferSchema::normalize( $once['definition'] );
		$this->assertSame( array(), $twice['errors'] );
		$this->assertSame( $once['definition'], $twice['definition'] );
	}

	public function test_required_fields_and_bad_values() {
		$this->assertContains( 'label_required', $this->codes( array_merge( $this->qty(), array( 'label' => '' ) ) ) );
		$this->assertContains( 'label_required', $this->codes( array_merge( $this->qty(), array( 'label' => '  ' ) ) ) );
		$this->assertContains( 'bad_type', $this->codes( array_merge( $this->qty(), array( 'type' => 'eval' ) ) ) );
		$this->assertContains( 'bad_priority', $this->codes( array_merge( $this->qty(), array( 'priority' => 5000 ) ) ) );
		$this->assertContains( 'bad_priority', $this->codes( array_merge( $this->qty(), array( 'priority' => -1 ) ) ) );
		$this->assertContains( 'end_before_start', $this->codes( array_merge( $this->qty(), array( 'starts_at' => 200, 'ends_at' => 100 ) ) ) );
		$this->assertContains( 'bad_date', $this->codes( array_merge( $this->qty(), array( 'starts_at' => 'tomorrow' ) ) ) );
		$this->assertContains( 'scope_empty', $this->codes( array_merge( $this->qty(), array( 'scope' => array( 'mode' => 'products', 'product_ids' => array() ) ) ) ) );
		$this->assertContains( 'bad_condition', $this->codes( array_merge( $this->qty(), array( 'conditions' => array( 'min_qty' => 0 ) ) ) ) );
		$this->assertContains( 'bad_condition', $this->codes( array_merge( $this->qty(), array( 'conditions' => array( 'min_subtotal' => -5 ) ) ) ) );
	}

	public function test_tiers() {
		$bad = array(
			array( array( array( 'min_qty' => 0, 'kind' => 'percent', 'value' => 100 ) ), 'bad_tier' ),
			array( array( array( 'min_qty' => 2, 'kind' => 'percent', 'value' => 0 ) ), 'bad_tier' ),
			array( array( array( 'min_qty' => 2, 'kind' => 'percent', 'value' => 10001 ) ), 'bad_tier' ),
			array( array( array( 'min_qty' => 2, 'kind' => 'magic', 'value' => 5 ) ), 'bad_tier' ),
			array( array( array( 'min_qty' => 2, 'kind' => 'fixed_off', 'value' => 0 ) ), 'bad_tier' ),
			array( array( array( 'min_qty' => 2, 'kind' => 'percent', 'value' => 100 ), array( 'min_qty' => 2, 'kind' => 'percent', 'value' => 200 ) ), 'duplicate_tier' ),
			array( array(), 'tiers_required' ),
		);
		foreach ( $bad as $case ) {
			$this->assertContains( $case[1], $this->codes( array_merge( $this->qty(), array( 'tiers' => $case[0] ) ) ), $case[1] );
		}
		$sorted = OfferSchema::normalize( array_merge( $this->qty(), array( 'tiers' => array( array( 'min_qty' => 5, 'kind' => 'percent', 'value' => 100 ), array( 'min_qty' => 2, 'kind' => 'unit_price', 'value' => 0 ) ) ) ) );
		$this->assertSame( array( 2, 5 ), array_column( $sorted['definition']['tiers'], 'min_qty' ) );
		$this->assertSame( array(), $sorted['errors'] ); // A unit price of 0 (free) is allowed.
	}

	public function test_bundle_rules() {
		$base = array( 'label' => 'b', 'type' => 'bundle', 'items' => array( array( 'product_id' => 1 ), array( 'product_id' => 2 ) ), 'bundle_price' => array( 'kind' => 'price', 'value' => 100 ) );
		$this->assertContains( 'bundle_needs_two_items', $this->codes( array_merge( $base, array( 'items' => array( array( 'product_id' => 1 ) ) ) ) ) );
		$this->assertContains( 'duplicate_item', $this->codes( array_merge( $base, array( 'items' => array( array( 'product_id' => 1 ), array( 'product_id' => 1 ), array( 'product_id' => 2 ) ) ) ) ) );
		$this->assertContains( 'bad_item', $this->codes( array_merge( $base, array( 'items' => array( array( 'product_id' => 0 ), array( 'product_id' => 2 ), array( 'product_id' => 3 ) ) ) ) ) );
		$this->assertContains( 'bad_bundle_price', $this->codes( array_merge( $base, array( 'bundle_price' => array( 'kind' => 'percent', 'value' => 20000 ) ) ) ) );
		$this->assertContains( 'bad_bundle_price', $this->codes( array_merge( $base, array( 'bundle_price' => null ) ) ) );
	}

	public function test_gift_and_actions() {
		$this->assertContains( 'bad_gift', $this->codes( array( 'label' => 'g', 'type' => 'free_gift', 'gift' => array( 'product_id' => 'abc' ) ) ) );
		$this->assertContains( 'bad_action', $this->codes( array( 'label' => 'c', 'type' => 'custom_rule', 'action' => array( 'type' => 'run_php' ) ) ) );
		$this->assertContains( 'bad_action_value', $this->codes( array( 'label' => 'c', 'type' => 'custom_rule', 'action' => array( 'type' => 'percent_off', 'value' => 0 ) ) ) );
		$this->assertContains( 'bad_gift', $this->codes( array( 'label' => 'c', 'type' => 'custom_rule', 'action' => array( 'type' => 'add_gift' ) ) ) );
		$this->assertSame( array(), $this->codes( array( 'label' => 'c', 'type' => 'custom_rule', 'action' => array( 'type' => 'add_gift', 'gift' => array( 'product_id' => 4, 'qty' => 2 ) ) ) ) );
	}

	public function test_groups() {
		$g = function ( array $input ) {
			return OfferSchema::group( OfferSchema::normalize( $input )['definition'] );
		};
		$this->assertSame( 'price', $g( $this->qty() ) );
		$this->assertSame( 'gift', $g( array( 'label' => 'g', 'type' => 'free_gift', 'gift' => array( 'product_id' => 1 ) ) ) );
		$this->assertSame( 'delivery', $g( array( 'label' => 'c', 'type' => 'custom_rule', 'action' => array( 'type' => 'free_delivery' ) ) ) );
		$this->assertSame( 'order', $g( array( 'label' => 'c', 'type' => 'custom_rule', 'action' => array( 'type' => 'fixed_off_order', 'value' => 5 ) ) ) );
	}

	public function test_oversized_lists_are_trimmed_not_trusted() {
		$ids = range( 1, 500 );
		$r   = OfferSchema::normalize( array_merge( $this->qty(), array( 'scope' => array( 'mode' => 'products', 'product_ids' => $ids ) ) ) );
		$this->assertCount( OfferSchema::MAX_IDS, $r['definition']['scope']['product_ids'] );
	}
}
