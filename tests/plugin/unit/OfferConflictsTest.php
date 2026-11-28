<?php
use PHPUnit\Framework\TestCase;
use DecoStore\Offers\OfferConflicts;
use DecoStore\Offers\OfferSchema;

class OfferConflictsTest extends TestCase {

	private function offer( $id, array $over = array() ) {
		$in = array_merge(
			array(
				'label'  => 'o' . $id,
				'type'   => 'custom_rule',
				'status' => 'active',
				'action' => array( 'type' => 'percent_off', 'value' => 1000 ),
			),
			$over
		);
		$o       = OfferSchema::normalize( $in )['definition'];
		$o['id'] = $id;
		return $o;
	}

	public function test_same_priority_and_overlapping_scope_conflict() {
		$this->assertSame( array( array( 1, 2 ) ), OfferConflicts::find( array( $this->offer( 1 ), $this->offer( 2 ) ) ) );
	}

	public function test_different_priority_is_not_a_conflict() {
		$this->assertSame( array(), OfferConflicts::find( array( $this->offer( 1 ), $this->offer( 2, array( 'priority' => 1 ) ) ) ) );
	}

	public function test_disjoint_products_do_not_conflict() {
		$a = $this->offer( 1, array( 'scope' => array( 'mode' => 'products', 'product_ids' => array( 1 ) ) ) );
		$b = $this->offer( 2, array( 'scope' => array( 'mode' => 'products', 'product_ids' => array( 2 ) ) ) );
		$c = $this->offer( 3, array( 'scope' => array( 'mode' => 'products', 'product_ids' => array( 2, 3 ) ) ) );
		$this->assertSame( array( array( 2, 3 ) ), OfferConflicts::find( array( $a, $b, $c ) ) );
	}

	public function test_drafts_selectable_and_gifts_never_conflict() {
		$gift = array( 'type' => 'free_gift', 'gift' => array( 'product_id' => 5 ) );
		$this->assertSame( array(), OfferConflicts::find( array( $this->offer( 1, $gift ), $this->offer( 2, $gift ) ) ) );
		$this->assertSame( array(), OfferConflicts::find( array( $this->offer( 1 ), $this->offer( 2, array( 'status' => 'draft' ) ) ) ) );
		$this->assertSame( array(), OfferConflicts::find( array( $this->offer( 1 ), $this->offer( 2, array( 'selectable' => true ) ) ) ) );
	}

	public function test_different_groups_stack_so_they_do_not_conflict() {
		$delivery = $this->offer( 2, array( 'action' => array( 'type' => 'free_delivery' ) ) );
		$this->assertSame( array(), OfferConflicts::find( array( $this->offer( 1 ), $delivery ) ) );
	}

	public function test_non_overlapping_date_windows_do_not_conflict() {
		$a = $this->offer( 1, array( 'starts_at' => 100, 'ends_at' => 200 ) );
		$b = $this->offer( 2, array( 'starts_at' => 200, 'ends_at' => 300 ) );
		$c = $this->offer( 3, array( 'starts_at' => 150, 'ends_at' => 250 ) );
		$this->assertSame( array( array( 1, 3 ), array( 2, 3 ) ), OfferConflicts::find( array( $a, $b, $c ) ) );
	}

	public function test_bundle_uses_its_items_as_scope() {
		$bundle = $this->offer( 1, array( 'type' => 'bundle', 'items' => array( array( 'product_id' => 7 ), array( 'product_id' => 8 ) ), 'bundle_price' => array( 'kind' => 'price', 'value' => 100 ) ) );
		$other  = $this->offer( 2, array( 'scope' => array( 'mode' => 'products', 'product_ids' => array( 8 ) ) ) );
		$apart  = $this->offer( 3, array( 'scope' => array( 'mode' => 'products', 'product_ids' => array( 99 ) ) ) );
		$this->assertSame( array( array( 1, 2 ) ), OfferConflicts::find( array( $bundle, $other, $apart ) ) );
	}
}
