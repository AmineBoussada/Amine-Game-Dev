<?php
use PHPUnit\Framework\TestCase;
use DecoStore\Forms\DefaultForm;
use DecoStore\Forms\FormSchema;
use DecoStore\Forms\PhoneRule;
use DecoStore\Forms\SubmissionValidator;
use DecoStore\Forms\ValidationContext;

/**
 * Fake world: wilaya 1 has communes 11, 12; wilaya 2 has none; wilaya 3 is
 * not served. Pickup 100 serves wilaya 1 / commune 11; pickup 101 serves the
 * whole wilaya 1.
 */
class FakeContext implements ValidationContext {
	public $rule    = PhoneRule::GENERIC;
	public $methods = array( 'home', 'office' );
	public function wilaya_is_served( $id ) {
		return in_array( $id, array( 1, 2 ), true );
	}
	public function commune_count( $id ) {
		return 1 === $id ? 2 : 0;
	}
	public function commune_belongs( $commune, $wilaya ) {
		return 1 === $wilaya && in_array( $commune, array( 11, 12 ), true );
	}
	public function delivery_methods() {
		return $this->methods;
	}
	public function pickup_serves( $pickup, $wilaya, $commune ) {
		return 1 === $wilaya && ( 101 === $pickup || ( 100 === $pickup && 11 === $commune ) );
	}
	public function phone_rule() {
		return $this->rule;
	}
}

class SubmissionValidatorTest extends TestCase {

	private $form;
	private $ctx;

	protected function setUp(): void {
		$this->form = FormSchema::normalize( DefaultForm::definition() )['definition'];
		$this->ctx  = new FakeContext();
	}

	private function good( array $over = array() ) {
		return array_merge(
			array(
				'full_name'       => 'محمد أمين',
				'phone'           => '0555 12 34 56',
				'wilaya'          => '1',
				'commune'         => '11',
				'delivery_method' => 'home',
				'address'         => "حي الأمل\nرقم 5",
				'note'            => '',
			),
			$over
		);
	}

	private function run_validation( array $input ) {
		return SubmissionValidator::validate( $this->form, $input, $this->ctx );
	}

	public function test_a_good_home_delivery_order() {
		$r = $this->run_validation( $this->good() );
		$this->assertSame( array(), $r['errors'] );
		$this->assertSame( '0555123456', $r['values']['phone'] );
		$this->assertSame( 1, $r['values']['wilaya'] );
		$this->assertSame( 11, $r['values']['commune'] );
		$this->assertSame( "حي الأمل\nرقم 5", $r['values']['address'], 'New lines are kept in textareas.' );
		$this->assertArrayNotHasKey( 'pickup_point', $r['values'], 'Hidden by the delivery method rule.' );
		$this->assertArrayNotHasKey( 'note', $r['values'], 'An empty optional field is omitted.' );
	}

	public function test_a_good_office_pickup_order() {
		$r = $this->run_validation( $this->good( array( 'delivery_method' => 'office', 'pickup_point' => '100', 'address' => '' ) ) );
		$this->assertSame( array(), $r['errors'] );
		$this->assertSame( 100, $r['values']['pickup_point'] );
		$this->assertArrayNotHasKey( 'address', $r['values'], 'Address is hidden and not required for pickup.' );
	}

	public function test_required_fields() {
		$r = $this->run_validation( array() );
		foreach ( array( 'full_name', 'phone', 'wilaya', 'delivery_method' ) as $key ) {
			$this->assertSame( 'required', $r['errors'][ $key ], $key );
		}
		$this->assertArrayNotHasKey( 'address', $r['errors'], 'Hidden until a delivery method is chosen.' );
		$this->assertArrayNotHasKey( 'commune', $r['errors'], 'No wilaya yet: the wilaya error is shown, not the commune one.' );
	}

	public function test_a_hidden_field_is_discarded_even_if_submitted() {
		$r = $this->run_validation( $this->good( array( 'pickup_point' => '100' ) ) );
		$this->assertArrayNotHasKey( 'pickup_point', $r['values'] );
		$this->assertContains( 'pickup_point', $r['ignored'] );
		$this->assertArrayNotHasKey( 'pickup_point', $r['errors'] );
	}

	public function test_a_hidden_required_field_is_not_required() {
		$r = $this->run_validation( $this->good( array( 'delivery_method' => 'office', 'address' => '', 'pickup_point' => '101' ) ) );
		$this->assertSame( array(), $r['errors'] );
	}

	public function test_visible_required_field_missing() {
		$r = $this->run_validation( $this->good( array( 'address' => '' ) ) );
		$this->assertSame( 'required', $r['errors']['address'] );
		$r = $this->run_validation( $this->good( array( 'delivery_method' => 'office', 'pickup_point' => '' ) ) );
		$this->assertSame( 'required', $r['errors']['pickup_point'] );
	}

	public function test_unknown_keys_are_ignored() {
		$r = $this->run_validation( $this->good( array( 'price' => '1', 'total' => '0', 'is_admin' => 'yes' ) ) );
		$this->assertSame( array(), $r['errors'] );
		$this->assertEqualsCanonicalizing( array( 'price', 'total', 'is_admin' ), $r['ignored'] );
		$this->assertArrayNotHasKey( 'price', $r['values'] );
	}

	public function test_name_length() {
		$this->assertSame( 'too_short', $this->run_validation( $this->good( array( 'full_name' => 'اب' ) ) )['errors']['full_name'] );
		$this->assertSame( 'too_long', $this->run_validation( $this->good( array( 'full_name' => str_repeat( 'ا', 81 ) ) ) )['errors']['full_name'] );
	}

	public function test_html_in_text_is_stripped() {
		$r = $this->run_validation( $this->good( array( 'full_name' => '<b>محمد</b> <script>alert(1)</script>أمين' ) ) );
		$this->assertSame( 'محمد alert(1)أمين', $r['values']['full_name'] );
	}

	public function test_phone_follows_the_chosen_rule() {
		$this->assertSame( 'invalid', $this->run_validation( $this->good( array( 'phone' => '123' ) ) )['errors']['phone'] );
		$this->ctx->rule = PhoneRule::DZ_MOBILE;
		$this->assertSame( 'invalid', $this->run_validation( $this->good( array( 'phone' => '0412345678' ) ) )['errors']['phone'] );
		$this->assertSame( '0555123456', $this->run_validation( $this->good( array( 'phone' => '+213 555 12 34 56' ) ) )['values']['phone'] );
	}

	public function test_wilaya_must_exist_and_be_served() {
		$this->assertSame( 'not_allowed', $this->run_validation( $this->good( array( 'wilaya' => '3' ) ) )['errors']['wilaya'] );
		$this->assertSame( 'not_allowed', $this->run_validation( $this->good( array( 'wilaya' => '999' ) ) )['errors']['wilaya'] );
		$this->assertSame( 'not_allowed', $this->run_validation( $this->good( array( 'wilaya' => 'abc' ) ) )['errors']['wilaya'] );
		$this->assertSame( 'not_allowed', $this->run_validation( $this->good( array( 'wilaya' => '1 OR 1=1' ) ) )['errors']['wilaya'] );
	}

	public function test_commune_must_belong_to_the_wilaya() {
		$r = $this->run_validation( $this->good( array( 'commune' => '99' ) ) );
		$this->assertSame( 'not_allowed', $r['errors']['commune'] );
		$r = $this->run_validation( $this->good( array( 'wilaya' => '2', 'commune' => '11' ) ) );
		$this->assertArrayNotHasKey( 'commune', $r['errors'], 'A wilaya with no communes loaded asks for none.' );
		$this->assertArrayNotHasKey( 'commune', $r['values'] );
		$r = $this->run_validation( $this->good( array( 'commune' => '' ) ) );
		$this->assertSame( 'required', $r['errors']['commune'] );
	}

	public function test_delivery_method_must_be_enabled() {
		$this->ctx->methods = array( 'home' );
		$this->assertSame( 'not_allowed', $this->run_validation( $this->good( array( 'delivery_method' => 'office', 'pickup_point' => '100' ) ) )['errors']['delivery_method'] );
		$this->assertSame( 'not_allowed', $this->run_validation( $this->good( array( 'delivery_method' => 'drone' ) ) )['errors']['delivery_method'] );
	}

	public function test_pickup_must_serve_the_area() {
		$base = array( 'delivery_method' => 'office', 'address' => '' );
		$this->assertSame( 'not_allowed', $this->run_validation( $this->good( $base + array( 'pickup_point' => '100', 'commune' => '12' ) ) )['errors']['pickup_point'] );
		$this->assertSame( array(), $this->run_validation( $this->good( $base + array( 'pickup_point' => '101', 'commune' => '12' ) ) )['errors'] );
		$this->assertSame( 'not_allowed', $this->run_validation( $this->good( $base + array( 'pickup_point' => '555' ) ) )['errors']['pickup_point'] );
	}

	public function test_array_values_are_treated_as_empty_not_crashes() {
		$r = $this->run_validation( $this->good( array( 'full_name' => array( 'x' ), 'phone' => array( '1' ), 'wilaya' => array( 1 ) ) ) );
		$this->assertSame( 'required', $r['errors']['full_name'] );
		$this->assertSame( 'required', $r['errors']['phone'] );
		$this->assertSame( 'required', $r['errors']['wilaya'] );
	}

	public function test_extra_field_types() {
		$d             = DefaultForm::definition();
		$d['fields'][] = array( 'key' => 'qty', 'type' => 'quantity', 'label' => 'الكمية', 'min' => 1, 'max' => 5 );
		$d['fields'][] = array( 'key' => 'color', 'type' => 'select', 'label' => 'اللون', 'options' => "red|أحمر\nblue|أزرق", 'required' => true );
		$d['fields'][] = array( 'key' => 'gift', 'type' => 'checkbox', 'label' => 'هدية' );
		$d['fields'][] = array( 'key' => 'agree', 'type' => 'terms', 'label' => 'أوافق' );
		$d['fields'][] = array( 'key' => 'age', 'type' => 'number', 'label' => 'العمر', 'min' => 18, 'max' => 99 );
		$form          = FormSchema::normalize( $d )['definition'];
		$run           = function ( array $over ) use ( $form ) {
			return SubmissionValidator::validate( $form, array_merge( $this->good(), array( 'qty' => '2', 'color' => 'red', 'agree' => '1' ), $over ), $this->ctx );
		};
		$r = $run( array( 'gift' => '1', 'age' => '٣٠' ) );
		$this->assertSame( array(), $r['errors'] );
		$this->assertSame( 2, $r['values']['qty'] );
		$this->assertTrue( $r['values']['gift'] );
		$this->assertSame( 30, $r['values']['age'], 'Arabic-Indic digits are accepted.' );
		$this->assertSame( 'too_large', $run( array( 'qty' => '6' ) )['errors']['qty'] );
		$this->assertSame( 'required', $run( array( 'qty' => '' ) )['errors']['qty'] );
		$this->assertSame( 'invalid', $run( array( 'qty' => '0' ) )['errors']['qty'] );
		$this->assertSame( 'invalid', $run( array( 'qty' => '1.5' ) )['errors']['qty'] );
		$this->assertSame( 'not_allowed', $run( array( 'color' => 'green' ) )['errors']['color'] );
		$this->assertSame( 'required', $run( array( 'agree' => '' ) )['errors']['agree'] );
		$this->assertSame( 'too_small', $run( array( 'age' => '10' ) )['errors']['age'] );
		$this->assertFalse( $run( array( 'gift' => '0' ) )['values']['gift'] );
	}

	public function test_values_keep_the_form_field_order() {
		$r = $this->run_validation( $this->good() );
		$this->assertSame( array( 'full_name', 'phone', 'wilaya', 'commune', 'delivery_method', 'address' ), array_keys( $r['values'] ) );
	}

	public function test_a_conditional_custom_field_follows_a_choice() {
		$d             = DefaultForm::definition();
		$d['fields'][] = array( 'key' => 'floor', 'type' => 'text', 'label' => 'الطابق', 'required' => true, 'visibility' => array( 'mode' => 'conditional', 'rules' => array( array( 'field' => 'delivery_method', 'op' => 'eq', 'value' => 'home' ) ) ) );
		$form          = FormSchema::normalize( $d )['definition'];
		$home          = SubmissionValidator::validate( $form, $this->good(), $this->ctx );
		$office        = SubmissionValidator::validate( $form, $this->good( array( 'delivery_method' => 'office', 'pickup_point' => '101' ) ), $this->ctx );
		$this->assertSame( 'required', $home['errors']['floor'] );
		$this->assertArrayNotHasKey( 'floor', $office['errors'] );
	}
}
