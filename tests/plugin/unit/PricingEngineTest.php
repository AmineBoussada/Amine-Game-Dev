<?php
use PHPUnit\Framework\TestCase;
use TopSolution\Offers\OfferSchema;
use TopSolution\Offers\PricingEngine;

class PricingEngineTest extends TestCase {

	private $next_id = 1;

	/** Build a normalised offer; throws if the schema refuses it. */
	private function offer( array $input ) {
		$input += array(
			'label'  => 'Offer ' . $this->next_id,
			'status' => 'active',
		);
		$r = OfferSchema::normalize( $input );
		$this->assertSame( array(), $r['errors'], 'test offer must be valid' );
		$o               = $r['definition'];
		$o['id']         = isset( $input['id'] ) ? $input['id'] : $this->next_id++;
		$o['version']    = 1;
		return $o;
	}

	private function request( array $lines = null, array $extra = array() ) {
		$lines = null === $lines ? array( array( 'id' => 'a', 'product_id' => 10, 'qty' => 1, 'unit_minor' => 1000 ) ) : $lines;
		return array_merge(
			array(
				'lines'           => $lines,
				'chosen_offer_id' => 0,
				'delivery'        => array( 'method' => 'home', 'wilaya_id' => 5, 'fee_minor' => 600 ),
				'now'             => 1000,
				'weekday'         => 2,
			),
			$extra
		);
	}

	private function settings( array $over = array() ) {
		return array_merge( array( 'max_gifts_per_order' => 1, 'min_price_percent' => 0 ), $over );
	}

	private function tiers() {
		return array(
			array( 'min_qty' => 2, 'kind' => 'percent', 'value' => 1000 ),
			array( 'min_qty' => 3, 'kind' => 'percent', 'value' => 2000 ),
			array( 'min_qty' => 5, 'kind' => 'unit_price', 'value' => 500 ),
		);
	}

	private function reasons( array $result ) {
		return array_column( $result['rejected'], 'reason', 'offer_id' );
	}

	private function assertInvariant( array $r ) {
		$sum = 0;
		foreach ( $r['lines'] as $line ) {
			$sum += $line['final_total_minor'];
			$this->assertGreaterThanOrEqual( 0, $line['final_total_minor'] );
			$this->assertLessThanOrEqual( $line['base_total_minor'], $line['final_total_minor'] );
		}
		$this->assertSame( $sum, $r['lines_total_minor'] );
		$this->assertSame( $r['lines_total_minor'] - $r['order_discount_minor'] + $r['delivery']['final_fee_minor'], $r['grand_total_minor'] );
		$this->assertLessThanOrEqual( $r['subtotal_minor'], $r['discount_total_minor'] );
	}

	public function test_no_offers_means_base_price_plus_delivery() {
		$r = PricingEngine::price( $this->request(), array(), $this->settings() );
		$this->assertTrue( $r['ok'] );
		$this->assertSame( 1000, $r['subtotal_minor'] );
		$this->assertSame( 1600, $r['grand_total_minor'] );
		$this->assertInvariant( $r );
	}

	/** @dataProvider tier_edges */
	public function test_quantity_tier_boundaries( $qty, $expected_total ) {
		$o = $this->offer( array( 'type' => 'qty_discount', 'tiers' => $this->tiers() ) );
		$r = PricingEngine::price( $this->request( array( array( 'id' => 'a', 'product_id' => 10, 'qty' => $qty, 'unit_minor' => 1000 ) ) ), array( $o ), $this->settings() );
		$this->assertSame( $expected_total, $r['lines_total_minor'], "qty $qty" );
		$this->assertInvariant( $r );
	}

	public function tier_edges() {
		return array(
			'below first tier' => array( 1, 1000 ),
			'first tier'       => array( 2, 1800 ),   // 10 % off each.
			'between'          => array( 2, 1800 ),
			'second tier'      => array( 3, 2400 ),   // 20 % off.
			'just below fifth' => array( 4, 3200 ),
			'unit price tier'  => array( 5, 2500 ),   // 500 each.
			'above'            => array( 9, 4500 ),
		);
	}

	public function test_percent_rounds_half_up_per_unit() {
		// 10.5 % of 99 = 10.395 -> 10 ; 12.5 % of 12 = 1.5 -> 2 (half up).
		$o = $this->offer( array( 'type' => 'custom_rule', 'action' => array( 'type' => 'percent_off', 'value' => 1250 ) ) );
		$r = PricingEngine::price( $this->request( array( array( 'id' => 'a', 'product_id' => 1, 'qty' => 3, 'unit_minor' => 12 ) ) ), array( $o ), $this->settings() );
		$this->assertSame( 10 * 3, $r['lines_total_minor'] ); // 12 - 2 = 10 per unit.
		$this->assertSame( 10, $r['lines'][0]['final_unit_minor'] );
	}

	public function test_fixed_off_never_goes_below_zero_and_set_price_never_raises() {
		$o  = $this->offer( array( 'type' => 'custom_rule', 'action' => array( 'type' => 'fixed_off_line', 'value' => 5000 ) ) );
		$r  = PricingEngine::price( $this->request(), array( $o ), $this->settings() );
		$this->assertSame( 0, $r['lines_total_minor'] );
		$up = $this->offer( array( 'type' => 'custom_rule', 'action' => array( 'type' => 'set_unit_price', 'value' => 9000 ) ) );
		$r  = PricingEngine::price( $this->request(), array( $up ), $this->settings() );
		$this->assertSame( 1000, $r['lines_total_minor'] );
		$this->assertSame( 'not_a_discount', $this->reasons( $r )[ $up['id'] ] );
	}

	public function test_price_floor_protects_against_misconfiguration() {
		$o = $this->offer( array( 'type' => 'custom_rule', 'action' => array( 'type' => 'percent_off', 'value' => 9000 ) ) );
		$r = PricingEngine::price( $this->request(), array( $o ), $this->settings( array( 'min_price_percent' => 50 ) ) );
		$this->assertSame( 500, $r['lines_total_minor'] );
		$this->assertTrue( $r['lines'][0]['floor_applied'] );
		$this->assertInvariant( $r );
	}

	public function test_dates_are_half_open() {
		$o = $this->offer( array( 'type' => 'custom_rule', 'starts_at' => 1000, 'ends_at' => 2000, 'action' => array( 'type' => 'percent_off', 'value' => 1000 ) ) );
		$at = function ( $now ) use ( $o ) {
			return PricingEngine::price( $this->request( null, array( 'now' => $now ) ), array( $o ), $this->settings() );
		};
		$this->assertSame( 'not_started', $this->reasons( $at( 999 ) )[ $o['id'] ] );
		$this->assertSame( 900, $at( 1000 )['lines_total_minor'] );
		$this->assertSame( 900, $at( 1999 )['lines_total_minor'] );
		$this->assertSame( 'ended', $this->reasons( $at( 2000 ) )[ $o['id'] ] );
	}

	public function test_drafts_and_archived_offers_never_apply() {
		$d = $this->offer( array( 'type' => 'custom_rule', 'status' => 'draft', 'action' => array( 'type' => 'percent_off', 'value' => 5000 ) ) );
		$a = $this->offer( array( 'type' => 'custom_rule', 'status' => 'archived', 'action' => array( 'type' => 'percent_off', 'value' => 5000 ) ) );
		$r = PricingEngine::price( $this->request(), array( $d, $a ), $this->settings() );
		$this->assertSame( 1000, $r['lines_total_minor'] );
		$this->assertSame( array(), $r['rejected'] );
	}

	public function test_conditions() {
		$base = array( 'type' => 'custom_rule', 'action' => array( 'type' => 'percent_off', 'value' => 1000 ) );
		$cases = array(
			array( array( 'min_qty' => 2 ), 'below_min_qty' ),
			array( array( 'min_subtotal' => 1001 ), 'below_min_subtotal' ),
			array( array( 'wilaya_ids' => array( 9 ) ), 'wilaya_not_included' ),
			array( array( 'delivery_methods' => array( 'office' ) ), 'delivery_method_not_included' ),
			array( array( 'weekdays' => array( 5 ) ), 'weekday_not_included' ),
		);
		foreach ( $cases as $case ) {
			$o = $this->offer( $base + array( 'conditions' => $case[0] ) );
			$r = PricingEngine::price( $this->request(), array( $o ), $this->settings() );
			$this->assertSame( $case[1], $this->reasons( $r )[ $o['id'] ], $case[1] );
			$this->assertSame( 1000, $r['lines_total_minor'] );
		}
		$ok = $this->offer( $base + array( 'conditions' => array( 'min_qty' => 1, 'min_subtotal' => 1000, 'wilaya_ids' => array( 5 ), 'delivery_methods' => array( 'home' ), 'weekdays' => array( 2 ) ) ) );
		$this->assertSame( 900, PricingEngine::price( $this->request(), array( $ok ), $this->settings() )['lines_total_minor'] );
		$wilaya = $this->offer( $base + array( 'conditions' => array( 'wilaya_ids' => array( 5 ) ) ) );
		$r      = PricingEngine::price( $this->request( null, array( 'delivery' => array( 'method' => '', 'wilaya_id' => 0, 'fee_minor' => null ) ) ), array( $wilaya ), $this->settings() );
		$this->assertSame( 'needs_wilaya', $this->reasons( $r )[ $wilaya['id'] ] );
	}

	public function test_scope_by_product_and_category() {
		$lines = array(
			array( 'id' => 'a', 'product_id' => 10, 'category_ids' => array( 3 ), 'qty' => 1, 'unit_minor' => 1000 ),
			array( 'id' => 'b', 'product_id' => 11, 'category_ids' => array( 4 ), 'qty' => 1, 'unit_minor' => 2000 ),
		);
		$by_product  = $this->offer( array( 'type' => 'custom_rule', 'scope' => array( 'mode' => 'products', 'product_ids' => array( 11 ) ), 'action' => array( 'type' => 'percent_off', 'value' => 5000 ) ) );
		$r           = PricingEngine::price( $this->request( $lines ), array( $by_product ), $this->settings() );
		$this->assertSame( 1000 + 1000, $r['lines_total_minor'] );
		$by_cat      = $this->offer( array( 'type' => 'custom_rule', 'scope' => array( 'mode' => 'categories', 'category_ids' => array( 3 ) ), 'action' => array( 'type' => 'percent_off', 'value' => 5000 ) ) );
		$r           = PricingEngine::price( $this->request( $lines ), array( $by_cat ), $this->settings() );
		$this->assertSame( 500 + 2000, $r['lines_total_minor'] );
		$none        = $this->offer( array( 'type' => 'custom_rule', 'scope' => array( 'mode' => 'products', 'product_ids' => array( 99 ) ), 'action' => array( 'type' => 'percent_off', 'value' => 5000 ) ) );
		$this->assertSame( 'scope_mismatch', $this->reasons( PricingEngine::price( $this->request( $lines ), array( $none ), $this->settings() ) )[ $none['id'] ] );
	}

	public function test_only_one_price_offer_per_line_highest_priority_wins() {
		$low  = $this->offer( array( 'type' => 'custom_rule', 'priority' => 1, 'action' => array( 'type' => 'percent_off', 'value' => 5000 ) ) );
		$high = $this->offer( array( 'type' => 'custom_rule', 'priority' => 5, 'action' => array( 'type' => 'percent_off', 'value' => 1000 ) ) );
		$r    = PricingEngine::price( $this->request(), array( $low, $high ), $this->settings() );
		$this->assertSame( 900, $r['lines_total_minor'] );
		$this->assertSame( $high['id'], $r['lines'][0]['offer_id'] );
		$this->assertSame( 'outranked', $this->reasons( $r )[ $low['id'] ] );
	}

	public function test_equal_priority_prefers_the_bigger_discount_then_lowest_id() {
		$a = $this->offer( array( 'type' => 'custom_rule', 'action' => array( 'type' => 'percent_off', 'value' => 1000 ) ) );
		$b = $this->offer( array( 'type' => 'custom_rule', 'action' => array( 'type' => 'percent_off', 'value' => 2000 ) ) );
		$this->assertSame( $b['id'], PricingEngine::price( $this->request(), array( $a, $b ), $this->settings() )['lines'][0]['offer_id'] );
		$c = $this->offer( array( 'type' => 'custom_rule', 'action' => array( 'type' => 'percent_off', 'value' => 2000 ) ) );
		$this->assertSame( $b['id'], PricingEngine::price( $this->request(), array( $c, $b ), $this->settings() )['lines'][0]['offer_id'] );
	}

	public function test_result_does_not_depend_on_offer_order() {
		$offers = array();
		foreach ( array( 1000, 3000, 2000 ) as $v ) {
			$offers[] = $this->offer( array( 'type' => 'custom_rule', 'action' => array( 'type' => 'percent_off', 'value' => $v ) ) );
		}
		$one = PricingEngine::price( $this->request(), $offers, $this->settings() );
		$two = PricingEngine::price( $this->request(), array_reverse( $offers ), $this->settings() );
		$this->assertSame( $one, $two );
	}

	public function test_exclusive_offer_wins_alone() {
		$ex   = $this->offer( array( 'type' => 'custom_rule', 'exclusive' => true, 'priority' => 1, 'action' => array( 'type' => 'percent_off', 'value' => 1000 ) ) );
		$big  = $this->offer( array( 'type' => 'custom_rule', 'priority' => 9, 'action' => array( 'type' => 'percent_off', 'value' => 5000 ) ) );
		$free = $this->offer( array( 'type' => 'custom_rule', 'action' => array( 'type' => 'free_delivery' ) ) );
		$r    = PricingEngine::price( $this->request(), array( $ex, $big, $free ), $this->settings() );
		$this->assertSame( 900, $r['lines_total_minor'] );
		$this->assertSame( 600, $r['delivery']['final_fee_minor'] );
		$this->assertSame( 'exclusive_won', $this->reasons( $r )[ $big['id'] ] );
		$this->assertSame( 'exclusive_won', $this->reasons( $r )[ $free['id'] ] );
	}

	public function test_selectable_offers_need_a_valid_choice() {
		$card = $this->offer( array( 'type' => 'custom_rule', 'selectable' => true, 'action' => array( 'type' => 'percent_off', 'value' => 2000 ) ) );
		$auto = $this->offer( array( 'type' => 'custom_rule', 'action' => array( 'type' => 'percent_off', 'value' => 1000 ) ) );
		$none = PricingEngine::price( $this->request(), array( $card, $auto ), $this->settings() );
		$this->assertSame( 900, $none['lines_total_minor'] );
		$this->assertSame( 'not_chosen', $this->reasons( $none )[ $card['id'] ] );
		$pick = PricingEngine::price( $this->request( null, array( 'chosen_offer_id' => $card['id'] ) ), array( $card, $auto ), $this->settings() );
		$this->assertSame( 800, $pick['lines_total_minor'] ); // The choice beats the auto offer even though its priority is equal.
		// Tampered ids are rejected, never swapped.
		foreach ( array( 999, $auto['id'] ) as $bad ) {
			$r = PricingEngine::price( $this->request( null, array( 'chosen_offer_id' => $bad ) ), array( $card, $auto ), $this->settings() );
			$this->assertFalse( $r['ok'] );
			$this->assertSame( 'chosen_offer_invalid', $r['error'] );
		}
		// A chosen offer that is no longer eligible is refused too.
		$limited = $this->offer( array( 'type' => 'custom_rule', 'selectable' => true, 'conditions' => array( 'min_qty' => 5 ), 'action' => array( 'type' => 'percent_off', 'value' => 2000 ) ) );
		$r       = PricingEngine::price( $this->request( null, array( 'chosen_offer_id' => $limited['id'] ) ), array( $limited ), $this->settings() );
		$this->assertSame( 'chosen_offer_invalid', $r['error'] );
	}

	public function test_gifts_up_to_the_limit_and_stock_check() {
		$g1 = $this->offer( array( 'type' => 'free_gift', 'priority' => 2, 'gift' => array( 'product_id' => 50, 'qty' => 1 ) ) );
		$g2 = $this->offer( array( 'type' => 'free_gift', 'priority' => 1, 'gift' => array( 'product_id' => 51, 'qty' => 2 ) ) );
		$r  = PricingEngine::price( $this->request(), array( $g2, $g1 ), $this->settings() );
		$this->assertCount( 1, $r['gifts'] );
		$this->assertSame( 50, $r['gifts'][0]['product_id'] );
		$this->assertSame( 'gift_limit', $this->reasons( $r )[ $g2['id'] ] );
		$r = PricingEngine::price( $this->request(), array( $g1, $g2 ), $this->settings( array( 'max_gifts_per_order' => 2 ) ) );
		$this->assertCount( 2, $r['gifts'] );
		$this->assertSame( 1000 + 600, $r['grand_total_minor'] ); // Gifts are free.
		$this->assertCount( 0, PricingEngine::price( $this->request(), array( $g1 ), $this->settings( array( 'max_gifts_per_order' => 0 ) ) )['gifts'] );
		$g1['gift_available'] = false;
		$r = PricingEngine::price( $this->request(), array( $g1 ), $this->settings() );
		$this->assertSame( array(), $r['gifts'] );
		$this->assertSame( 'gift_out_of_stock', $this->reasons( $r )[ $g1['id'] ] );
	}

	public function test_gift_stacks_with_a_price_offer_unless_exclusive() {
		$p = $this->offer( array( 'type' => 'custom_rule', 'action' => array( 'type' => 'percent_off', 'value' => 1000 ) ) );
		$g = $this->offer( array( 'type' => 'free_gift', 'gift' => array( 'product_id' => 50, 'qty' => 1 ) ) );
		$r = PricingEngine::price( $this->request(), array( $p, $g ), $this->settings() );
		$this->assertSame( 900, $r['lines_total_minor'] );
		$this->assertCount( 1, $r['gifts'] );
		$this->assertCount( 2, $r['applied'] );
	}

	public function test_delivery_offers_best_reduction_wins_and_needs_a_fee() {
		$pct  = $this->offer( array( 'type' => 'custom_rule', 'action' => array( 'type' => 'delivery_percent_off', 'value' => 5000 ) ) ); // 300.
		$fix  = $this->offer( array( 'type' => 'custom_rule', 'action' => array( 'type' => 'delivery_fixed_off', 'value' => 200 ) ) );
		$r    = PricingEngine::price( $this->request(), array( $pct, $fix ), $this->settings() );
		$this->assertSame( 300, $r['delivery']['final_fee_minor'] );
		$this->assertSame( 300, $r['delivery']['discount_minor'] );
		$free = $this->offer( array( 'type' => 'custom_rule', 'action' => array( 'type' => 'free_delivery' ) ) );
		$this->assertSame( 0, PricingEngine::price( $this->request(), array( $pct, $free ), $this->settings() )['delivery']['final_fee_minor'] );
		$none = PricingEngine::price( $this->request( null, array( 'delivery' => array( 'method' => '', 'wilaya_id' => 0, 'fee_minor' => null ) ) ), array( $free ), $this->settings() );
		$this->assertFalse( $none['delivery']['selected'] );
		$this->assertSame( 1000, $none['grand_total_minor'] );
		$this->assertSame( 'no_delivery_fee', $this->reasons( $none )[ $free['id'] ] );
		$big = $this->offer( array( 'type' => 'custom_rule', 'action' => array( 'type' => 'delivery_fixed_off', 'value' => 99999 ) ) );
		$this->assertSame( 0, PricingEngine::price( $this->request(), array( $big ), $this->settings() )['delivery']['final_fee_minor'] );
	}

	public function test_order_discount_is_capped_at_the_lines_total() {
		$o = $this->offer( array( 'type' => 'custom_rule', 'action' => array( 'type' => 'fixed_off_order', 'value' => 5000 ) ) );
		$r = PricingEngine::price( $this->request(), array( $o ), $this->settings() );
		$this->assertSame( 1000, $r['order_discount_minor'] );
		$this->assertSame( 600, $r['grand_total_minor'] );
		$this->assertInvariant( $r );
		$r = PricingEngine::price( $this->request(), array( $o ), $this->settings( array( 'min_price_percent' => 40 ) ) );
		$this->assertSame( 600, $r['order_discount_minor'] ); // The floor keeps 40 %.
	}

	public function test_bundle_atomicity_and_allocation() {
		$bundle = $this->offer( array(
			'type'         => 'bundle',
			'items'        => array( array( 'product_id' => 10, 'qty' => 1 ), array( 'product_id' => 11, 'qty' => 1 ) ),
			'bundle_price' => array( 'kind' => 'price', 'value' => 2500 ),
		) );
		$lines  = array(
			array( 'id' => 'a', 'product_id' => 10, 'qty' => 1, 'unit_minor' => 1000 ),
			array( 'id' => 'b', 'product_id' => 11, 'qty' => 1, 'unit_minor' => 2000 ),
		);
		$r = PricingEngine::price( $this->request( $lines ), array( $bundle ), $this->settings() );
		$this->assertSame( 2500, $r['lines_total_minor'] );
		$this->assertSame( 500, $r['discount_total_minor'] );
		$this->assertInvariant( $r );
		// Partial presence: nothing is discounted.
		$r = PricingEngine::price( $this->request( array( $lines[0] ) ), array( $bundle ), $this->settings() );
		$this->assertSame( 1000, $r['lines_total_minor'] );
		$this->assertSame( 'bundle_incomplete', $this->reasons( $r )[ $bundle['id'] ] );
		// Two full sets plus one extra unit of the first product.
		$lines[0]['qty'] = 3;
		$lines[1]['qty'] = 2;
		$r = PricingEngine::price( $this->request( $lines ), array( $bundle ), $this->settings() );
		$this->assertSame( 5000 + 1000, $r['lines_total_minor'] );
		$this->assertInvariant( $r );
	}

	public function test_bundle_allocation_sums_exactly_with_awkward_numbers() {
		$bundle = $this->offer( array(
			'type'         => 'bundle',
			'items'        => array( array( 'product_id' => 1, 'qty' => 1 ), array( 'product_id' => 2, 'qty' => 1 ), array( 'product_id' => 3, 'qty' => 1 ) ),
			'bundle_price' => array( 'kind' => 'price', 'value' => 1000 ),
		) );
		$lines  = array(
			array( 'id' => 'a', 'product_id' => 1, 'qty' => 1, 'unit_minor' => 333 ),
			array( 'id' => 'b', 'product_id' => 2, 'qty' => 1, 'unit_minor' => 333 ),
			array( 'id' => 'c', 'product_id' => 3, 'qty' => 1, 'unit_minor' => 700 ),
		);
		$r = PricingEngine::price( $this->request( $lines ), array( $bundle ), $this->settings() );
		$this->assertSame( 1000, $r['lines_total_minor'] );
		$this->assertInvariant( $r );
		$pct = $bundle;
		$pct['bundle_price'] = array( 'kind' => 'percent', 'value' => 1000 );
		$r = PricingEngine::price( $this->request( $lines ), array( $pct ), $this->settings() );
		$this->assertSame( 1366 - 137, $r['lines_total_minor'] ); // 10 % of 1366 = 136.6 -> 137.
	}

	public function test_bundle_that_is_not_cheaper_is_rejected() {
		$bundle = $this->offer( array( 'type' => 'bundle', 'items' => array( array( 'product_id' => 1, 'qty' => 1 ), array( 'product_id' => 2, 'qty' => 1 ) ), 'bundle_price' => array( 'kind' => 'price', 'value' => 5000 ) ) );
		$lines  = array( array( 'id' => 'a', 'product_id' => 1, 'qty' => 1, 'unit_minor' => 1000 ), array( 'id' => 'b', 'product_id' => 2, 'qty' => 1, 'unit_minor' => 1000 ) );
		$r      = PricingEngine::price( $this->request( $lines ), array( $bundle ), $this->settings() );
		$this->assertSame( 2000, $r['lines_total_minor'] );
		$this->assertSame( 'not_a_discount', $this->reasons( $r )[ $bundle['id'] ] );
	}

	public function test_bundle_does_not_share_a_line_with_a_higher_ranked_offer() {
		$bundle = $this->offer( array( 'type' => 'bundle', 'priority' => 1, 'items' => array( array( 'product_id' => 1, 'qty' => 1 ), array( 'product_id' => 2, 'qty' => 1 ) ), 'bundle_price' => array( 'kind' => 'price', 'value' => 1000 ) ) );
		$other  = $this->offer( array( 'type' => 'custom_rule', 'priority' => 9, 'scope' => array( 'mode' => 'products', 'product_ids' => array( 1 ) ), 'action' => array( 'type' => 'percent_off', 'value' => 1000 ) ) );
		$lines  = array( array( 'id' => 'a', 'product_id' => 1, 'qty' => 1, 'unit_minor' => 1000 ), array( 'id' => 'b', 'product_id' => 2, 'qty' => 1, 'unit_minor' => 1000 ) );
		$r      = PricingEngine::price( $this->request( $lines ), array( $bundle, $other ), $this->settings() );
		$this->assertSame( 900 + 1000, $r['lines_total_minor'] );
		$this->assertSame( 'outranked', $this->reasons( $r )[ $bundle['id'] ] );
		$this->assertInvariant( $r );
	}

	public function test_variation_specific_bundle_item() {
		$bundle = $this->offer( array( 'type' => 'bundle', 'items' => array( array( 'product_id' => 1, 'variation_id' => 7, 'qty' => 1 ), array( 'product_id' => 2, 'qty' => 1 ) ), 'bundle_price' => array( 'kind' => 'price', 'value' => 1000 ) ) );
		$lines  = array( array( 'id' => 'a', 'product_id' => 1, 'variation_id' => 8, 'qty' => 1, 'unit_minor' => 1000 ), array( 'id' => 'b', 'product_id' => 2, 'qty' => 1, 'unit_minor' => 1000 ) );
		$this->assertSame( 2000, PricingEngine::price( $this->request( $lines ), array( $bundle ), $this->settings() )['lines_total_minor'] );
		$lines[0]['variation_id'] = 7;
		$this->assertSame( 1000, PricingEngine::price( $this->request( $lines ), array( $bundle ), $this->settings() )['lines_total_minor'] );
	}

	public function test_bad_requests_are_refused() {
		foreach ( array(
			array( array(), 'bad_lines' ),
			array( array( array( 'id' => 'a', 'product_id' => 1, 'qty' => 0, 'unit_minor' => 1 ) ), 'bad_quantity' ),
			array( array( array( 'id' => 'a', 'product_id' => 1, 'qty' => 1001, 'unit_minor' => 1 ) ), 'bad_quantity' ),
			array( array( array( 'id' => 'a', 'product_id' => 1, 'qty' => '2', 'unit_minor' => 1 ) ), 'bad_quantity' ),
			array( array( array( 'id' => 'a', 'product_id' => 1, 'qty' => 1, 'unit_minor' => -5 ) ), 'bad_price' ),
			array( array( array( 'id' => 'a', 'product_id' => 1, 'qty' => 1, 'unit_minor' => '100' ) ), 'bad_price' ),
			array( array( array( 'id' => 'a', 'product_id' => 1, 'qty' => 1, 'unit_minor' => 1 ), array( 'id' => 'a', 'product_id' => 2, 'qty' => 1, 'unit_minor' => 1 ) ), 'bad_lines' ),
		) as $case ) {
			$r = PricingEngine::price( array( 'lines' => $case[0] ), array(), $this->settings() );
			$this->assertFalse( $r['ok'] );
			$this->assertSame( $case[1], $r['error'] );
		}
	}

	public function test_huge_quantity_and_price_stay_exact() {
		$o = $this->offer( array( 'type' => 'qty_discount', 'tiers' => array( array( 'min_qty' => 1, 'kind' => 'percent', 'value' => 3333 ) ) ) );
		$r = PricingEngine::price( $this->request( array( array( 'id' => 'a', 'product_id' => 1, 'qty' => 1000, 'unit_minor' => 999999999999 ) ) ), array( $o ), $this->settings() );
		$this->assertTrue( $r['ok'] );
		$this->assertInvariant( $r );
	}

	public function test_applied_offers_carry_the_version_for_snapshots() {
		$o            = $this->offer( array( 'type' => 'custom_rule', 'action' => array( 'type' => 'percent_off', 'value' => 1000 ) ) );
		$o['version'] = 7;
		$r            = PricingEngine::price( $this->request(), array( $o ), $this->settings() );
		$this->assertSame( 7, $r['applied'][0]['version'] );
		$this->assertSame( PricingEngine::VERSION, $r['engine_version'] );
	}

	public function test_same_input_same_output_property() {
		mt_srand( 42 );
		for ( $i = 0; $i < 200; $i++ ) {
			$this->next_id = 1;
			$offers        = array(
				$this->offer( array( 'type' => 'qty_discount', 'priority' => mt_rand( 0, 3 ), 'tiers' => array( array( 'min_qty' => mt_rand( 1, 4 ), 'kind' => 'percent', 'value' => mt_rand( 1, 10000 ) ) ) ) ),
				$this->offer( array( 'type' => 'custom_rule', 'priority' => mt_rand( 0, 3 ), 'action' => array( 'type' => 'fixed_off_order', 'value' => mt_rand( 1, 5000 ) ) ) ),
				$this->offer( array( 'type' => 'custom_rule', 'priority' => mt_rand( 0, 3 ), 'action' => array( 'type' => 'delivery_percent_off', 'value' => mt_rand( 1, 10000 ) ) ) ),
				$this->offer( array( 'type' => 'custom_rule', 'priority' => mt_rand( 0, 3 ), 'action' => array( 'type' => 'set_unit_price', 'value' => mt_rand( 0, 2000 ) ) ) ),
			);
			$lines   = array();
			for ( $n = 0, $c = mt_rand( 1, 3 ); $n < $c; $n++ ) {
				$lines[] = array( 'id' => (string) $n, 'product_id' => $n + 1, 'qty' => mt_rand( 1, 6 ), 'unit_minor' => mt_rand( 0, 3000 ) );
			}
			$settings = $this->settings( array( 'min_price_percent' => mt_rand( 0, 1 ) * 25 ) );
			$one      = PricingEngine::price( $this->request( $lines ), $offers, $settings );
			shuffle( $offers );
			$two      = PricingEngine::price( $this->request( $lines ), $offers, $settings );
			$this->assertSame( $one, $two );
			$this->assertInvariant( $one );
		}
	}
}
