<?php
use PHPUnit\Framework\TestCase;
use DecoStore\Core\Settings;

class SettingsTest extends TestCase {

	public function test_defaults_match_the_owner_decisions() {
		$d = Settings::defaults();
		$this->assertSame( 'processing', $d['initial_order_status'], 'Owner decision 2026-11-28: new COD orders are processing.' );
		$this->assertFalse( $d['delete_data_on_uninstall'], 'Nothing is deleted unless the owner opts in.' );
		$this->assertFalse( $d['debug_logging'] );
	}

	public function test_every_tab_field_has_a_default() {
		foreach ( Settings::tabs() as $tab => $fields ) {
			foreach ( array_keys( $fields ) as $key ) {
				$this->assertArrayHasKey( $key, Settings::defaults(), "$tab/$key" );
			}
		}
	}

	public function test_a_valid_order_status_is_accepted() {
		$out = Settings::sanitize( array( '__tab' => 'orders', 'initial_order_status' => 'on-hold' ), Settings::defaults() );
		$this->assertSame( 'on-hold', $out['initial_order_status'] );
	}

	/**
	 * @dataProvider invalid_statuses
	 */
	public function test_an_invalid_order_status_falls_back_to_the_default( $bad ) {
		$out = Settings::sanitize( array( '__tab' => 'orders', 'initial_order_status' => $bad ), array( 'initial_order_status' => 'on-hold' ) );
		$this->assertSame( 'processing', $out['initial_order_status'] );
	}

	public function invalid_statuses() {
		return array( array( 'completed' ), array( 'wc-processing' ), array( '' ), array( array( 'processing' ) ), array( null ), array( 1 ), array( 'PROCESSING' ) );
	}

	public function test_a_missing_order_status_falls_back_to_the_default() {
		$out = Settings::sanitize( array( '__tab' => 'orders' ), array() );
		$this->assertSame( 'processing', $out['initial_order_status'] );
	}

	public function test_checkboxes_follow_submission() {
		$on  = Settings::sanitize( array( '__tab' => 'general', 'debug_logging' => '1', 'delete_data_on_uninstall' => '1' ), array() );
		$off = Settings::sanitize( array( '__tab' => 'general' ), array( 'debug_logging' => true, 'delete_data_on_uninstall' => true ) );
		$this->assertTrue( $on['debug_logging'] );
		$this->assertTrue( $on['delete_data_on_uninstall'] );
		$this->assertFalse( $off['debug_logging'], 'An unchecked box is not submitted, so it means off.' );
		$this->assertFalse( $off['delete_data_on_uninstall'] );
	}

	public function test_a_zero_string_is_off() {
		$out = Settings::sanitize( array( '__tab' => 'general', 'debug_logging' => '0' ), array( 'debug_logging' => true ) );
		$this->assertFalse( $out['debug_logging'] );
	}

	public function test_saving_one_tab_never_resets_another() {
		$current = array( 'debug_logging' => true, 'delete_data_on_uninstall' => true, 'initial_order_status' => 'on-hold' );
		$out     = Settings::sanitize( array( '__tab' => 'orders', 'initial_order_status' => 'processing' ), $current );
		$this->assertTrue( $out['debug_logging'] );
		$this->assertTrue( $out['delete_data_on_uninstall'] );
		$this->assertSame( 'processing', $out['initial_order_status'] );
	}

	public function test_garbage_input_changes_nothing() {
		$current = array( 'debug_logging' => true, 'delete_data_on_uninstall' => false, 'initial_order_status' => 'on-hold', 'phone_rule' => 'generic', 'max_gifts_per_order' => 1, 'min_price_percent' => 0, 'confirmation_message' => '', 'token_ttl_hours' => 12, 'max_attempts_per_ip' => 20, 'duplicate_window_hours' => 24 );
		foreach ( array( array(), array( '__tab' => 'nope' ), 'text', null, 5 ) as $input ) {
			$this->assertSame( $current, Settings::sanitize( $input, $current ), var_export( $input, true ) );
		}
	}

	public function test_an_unknown_tab_does_not_turn_absent_checkboxes_off() {
		$current = array( 'debug_logging' => true, 'delete_data_on_uninstall' => true, 'initial_order_status' => 'on-hold' );
		$out     = Settings::sanitize( array( '__tab' => 'nope', 'initial_order_status' => 'processing' ), $current );
		$this->assertTrue( $out['debug_logging'] );
		$this->assertTrue( $out['delete_data_on_uninstall'] );
		$this->assertSame( 'processing', $out['initial_order_status'], 'A submitted, valid key is always applied.' );
	}

	/**
	 * Regression: WordPress runs the sanitize callback twice on a save. The
	 * second pass receives the already-sanitised array (no __tab) and must not
	 * discard the new values.
	 */
	public function test_sanitising_twice_gives_the_same_result() {
		$current = Settings::defaults();
		$once    = Settings::sanitize( array( '__tab' => 'orders', 'initial_order_status' => 'on-hold' ), $current );
		$twice   = Settings::sanitize( $once, $current );
		$this->assertSame( 'on-hold', $once['initial_order_status'] );
		$this->assertSame( $once, $twice );

		$once  = Settings::sanitize( array( '__tab' => 'general', 'debug_logging' => '1' ), $current );
		$twice = Settings::sanitize( $once, $current );
		$this->assertSame( $once, $twice );
		$this->assertTrue( $twice['debug_logging'] );
	}

	public function test_a_tampered_value_in_a_second_pass_is_still_validated() {
		$twice = Settings::sanitize( array( 'initial_order_status' => 'completed', 'debug_logging' => 'yes' ), Settings::defaults() );
		$this->assertSame( 'processing', $twice['initial_order_status'] );
		$this->assertTrue( $twice['debug_logging'] );
	}

	public function test_unknown_submitted_keys_are_ignored() {
		$out = Settings::sanitize( array( '__tab' => 'general', 'evil' => 'x', 'debug_logging' => '1' ), array() );
		$this->assertArrayNotHasKey( 'evil', $out );
	}

	public function test_stray_keys_in_stored_data_are_dropped() {
		$out = Settings::sanitize( array( '__tab' => 'general' ), array( '__tab' => 'orders', 'junk' => 1, 'debug_logging' => true ) );
		$this->assertArrayNotHasKey( '__tab', $out );
		$this->assertArrayNotHasKey( 'junk', $out );
		$this->assertSame( array_keys( Settings::defaults() ), array_keys( $out ) );
	}

	public function test_result_is_always_a_complete_array() {
		$out = Settings::sanitize( null, array() );
		$this->assertSame( array_keys( Settings::defaults() ), array_keys( $out ) );
	}

	public function test_integer_settings_are_range_checked_and_idempotent() {
		$cur = Settings::defaults();
		$ok  = Settings::sanitize( array( '__tab' => 'offers', 'max_gifts_per_order' => '3', 'min_price_percent' => '40' ), $cur );
		$this->assertSame( 3, $ok['max_gifts_per_order'] );
		$this->assertSame( 40, $ok['min_price_percent'] );
		$this->assertSame( $ok, Settings::sanitize( $ok + array( '__tab' => 'offers' ), $ok ) );
		foreach ( array( '-1', '11', 'abc', '', '1.5', '99999', array( 5 ) ) as $bad ) {
			$out = Settings::sanitize( array( '__tab' => 'offers', 'max_gifts_per_order' => $bad ), $ok );
			$this->assertSame( 1, $out['max_gifts_per_order'], var_export( $bad, true ) );
		}
		$out = Settings::sanitize( array( '__tab' => 'offers', 'min_price_percent' => '101' ), $ok );
		$this->assertSame( 0, $out['min_price_percent'] );
		// Saving another tab never touches them.
		$other = Settings::sanitize( array( '__tab' => 'orders', 'initial_order_status' => 'on-hold' ), $ok );
		$this->assertSame( 3, $other['max_gifts_per_order'] );
		$this->assertSame( 40, $other['min_price_percent'] );
	}

	public function test_text_settings_are_cleaned_and_idempotent() {
		$cur = Settings::defaults();
		$out = Settings::sanitize( array( '__tab' => 'orders', 'confirmation_message' => "  <b>سنتصل بك</b>\n\n\n\nقريباً  " . str_repeat( 'x', 600 ) ), $cur );
		$this->assertStringNotContainsString( '<b>', $out['confirmation_message'] );
		$this->assertLessThanOrEqual( 500, mb_strlen( $out['confirmation_message'] ) );
		$this->assertSame( $out, Settings::sanitize( $out + array( '__tab' => 'orders' ), $out ) );
		$this->assertSame( '', Settings::sanitize( array( '__tab' => 'orders', 'confirmation_message' => array( 'x' ) ), $cur )['confirmation_message'] );
		// The forms tab does not reset the orders text.
		$other = Settings::sanitize( array( '__tab' => 'forms', 'phone_rule' => 'generic' ), $out );
		$this->assertSame( $out['confirmation_message'], $other['confirmation_message'] );
	}
}
