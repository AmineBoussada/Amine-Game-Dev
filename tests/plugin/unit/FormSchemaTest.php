<?php
use PHPUnit\Framework\TestCase;
use DecoStore\Forms\DefaultForm;
use DecoStore\Forms\FieldTypes;
use DecoStore\Forms\FormSchema;

class FormSchemaTest extends TestCase {

	private function codes( array $result ) {
		return array_column( $result['errors'], 'code' );
	}

	private function base() {
		return DefaultForm::definition();
	}

	public function test_the_default_form_is_valid_and_complete() {
		$r = FormSchema::normalize( $this->base() );
		$this->assertSame( array(), $r['errors'] );
		$this->assertSame( 8, count( $r['definition']['fields'] ) );
		$maps = array_column( $r['definition']['fields'], 'maps_to' );
		foreach ( FieldTypes::required_mappings() as $m ) {
			$this->assertContains( $m, $maps );
		}
	}

	public function test_normalising_twice_gives_the_same_result() {
		$once  = FormSchema::normalize( $this->base() );
		$twice = FormSchema::normalize( $once['definition'] );
		$this->assertSame( $once['definition'], $twice['definition'] );
		$this->assertSame( array(), $twice['errors'] );
	}

	public function test_name_is_required() {
		$d         = $this->base();
		$d['name'] = '  ';
		$this->assertContains( 'name_required', $this->codes( FormSchema::normalize( $d ) ) );
	}

	public function test_core_fields_cannot_be_removed() {
		foreach ( FieldTypes::required_mappings() as $mapping ) {
			$d          = $this->base();
			$d['fields'] = array_values( array_filter( $d['fields'], static function ( $f ) use ( $mapping ) {
				return ( isset( $f['maps_to'] ) ? $f['maps_to'] : $f['type'] ) !== $mapping;
			} ) );
			$this->assertContains( 'missing_mapping:' . $mapping, $this->codes( FormSchema::normalize( $d ) ), $mapping );
		}
	}

	public function test_core_fields_are_forced_required() {
		$d = $this->base();
		foreach ( $d['fields'] as &$f ) {
			$f['required'] = false;
		}
		unset( $f );
		$r = FormSchema::normalize( $d );
		foreach ( $r['definition']['fields'] as $f ) {
			if ( in_array( $f['maps_to'], FieldTypes::required_mappings(), true ) ) {
				$this->assertTrue( $f['required'], $f['key'] );
			}
		}
	}

	public function test_core_fields_cannot_be_conditional() {
		$d = $this->base();
		$d['fields'][1]['visibility'] = array( 'mode' => 'conditional', 'rules' => array( array( 'field' => 'full_name', 'op' => 'not_empty' ) ) );
		$this->assertContains( 'core_field_conditional', $this->codes( FormSchema::normalize( $d ) ) );
	}

	public function test_duplicate_keys_and_mappings() {
		$d              = $this->base();
		$d['fields'][]  = array( 'key' => 'full_name', 'type' => 'text', 'label' => 'x' );
		$this->assertContains( 'duplicate_key', $this->codes( FormSchema::normalize( $d ) ) );
		$d              = $this->base();
		$d['fields'][]  = array( 'key' => 'second_phone', 'type' => 'phone', 'label' => 'x', 'maps_to' => 'phone' );
		$this->assertContains( 'duplicate_mapping:phone', $this->codes( FormSchema::normalize( $d ) ) );
	}

	public function test_bad_keys_types_and_mappings() {
		$d             = $this->base();
		$d['fields'][] = array( 'key' => 'Bad Key!', 'type' => 'text', 'label' => 'x' );
		$d['fields'][] = array( 'key' => '1abc', 'type' => 'text', 'label' => 'x' );
		$d['fields'][] = array( 'key' => 'ok_key', 'type' => 'banana', 'label' => 'x' );
		$d['fields'][] = array( 'key' => 'other', 'type' => 'select', 'label' => 'x', 'options' => 'a', 'maps_to' => 'phone' );
		$c             = $this->codes( FormSchema::normalize( $d ) );
		$this->assertSame( 2, count( array_keys( $c, 'bad_key', true ) ) );
		$this->assertContains( 'bad_type', $c );
		$this->assertContains( 'bad_mapping', $c );
	}

	public function test_label_is_required_except_for_info() {
		$d             = $this->base();
		$d['fields'][] = array( 'key' => 'nolabel', 'type' => 'text', 'label' => '' );
		$d['fields'][] = array( 'key' => 'inf', 'type' => 'info', 'text' => 'مرحباً' );
		$d['fields'][] = array( 'key' => 'inf2', 'type' => 'info', 'text' => '' );
		$c             = $this->codes( FormSchema::normalize( $d ) );
		$this->assertContains( 'label_required', $c );
		$this->assertContains( 'text_required', $c );
		$this->assertSame( 1, count( array_keys( $c, 'label_required', true ) ), 'An info field needs no label.' );
	}

	public function test_options_are_parsed_from_lines_and_validated() {
		$d             = $this->base();
		$d['fields'][] = array( 'key' => 'color', 'type' => 'select', 'label' => 'اللون', 'options' => "red|أحمر\nأخضر\n\nblue|أزرق" );
		$r             = FormSchema::normalize( $d );
		$this->assertSame( array(), $r['errors'] );
		$color = null;
		foreach ( $r['definition']['fields'] as $f ) {
			if ( 'color' === $f['key'] ) {
				$color = $f;
			}
		}
		$this->assertSame( array( 'red', 'o2', 'blue' ), array_column( $color['options'], 'value' ) );
		$d['fields'][ count( $d['fields'] ) - 1 ]['options'] = 'a|x\na|y';
		$d['fields'][ count( $d['fields'] ) - 1 ]['options'] = "a|x\na|y";
		$this->assertContains( 'duplicate_option_value', $this->codes( FormSchema::normalize( $d ) ) );
		$d['fields'][ count( $d['fields'] ) - 1 ]['options'] = '';
		$this->assertContains( 'options_required', $this->codes( FormSchema::normalize( $d ) ) );
	}

	public function test_visibility_rules_are_validated() {
		$mk = function ( array $vis ) {
			$d             = $this->base();
			$d['fields'][] = array( 'key' => 'extra', 'type' => 'text', 'label' => 'x', 'visibility' => $vis );
			return $this->codes( FormSchema::normalize( $d ) );
		};
		$this->assertSame( array(), $mk( array( 'mode' => 'conditional', 'rules' => array( array( 'field' => 'phone', 'op' => 'not_empty' ) ) ) ) );
		$this->assertContains( 'conditional_without_rules', $mk( array( 'mode' => 'conditional', 'rules' => array() ) ) );
		$this->assertContains( 'rule_unknown_field', $mk( array( 'mode' => 'conditional', 'rules' => array( array( 'field' => 'ghost', 'op' => 'not_empty' ) ) ) ) );
		$this->assertContains( 'rule_on_itself', $mk( array( 'mode' => 'conditional', 'rules' => array( array( 'field' => 'extra', 'op' => 'not_empty' ) ) ) ) );
		$this->assertContains( 'bad_rule', $mk( array( 'mode' => 'conditional', 'rules' => array( array( 'field' => 'phone', 'op' => 'hack' ) ) ) ) );
		$this->assertContains( 'rule_value_required', $mk( array( 'mode' => 'conditional', 'rules' => array( array( 'field' => 'phone', 'op' => 'eq', 'value' => '' ) ) ) ) );
	}

	public function test_a_cycle_is_rejected() {
		$d             = $this->base();
		$d['fields'][] = array( 'key' => 'aa', 'type' => 'text', 'label' => 'x', 'visibility' => array( 'mode' => 'conditional', 'rules' => array( array( 'field' => 'bb', 'op' => 'not_empty' ) ) ) );
		$d['fields'][] = array( 'key' => 'bb', 'type' => 'text', 'label' => 'x', 'visibility' => array( 'mode' => 'conditional', 'rules' => array( array( 'field' => 'aa', 'op' => 'not_empty' ) ) ) );
		$this->assertContains( 'rule_cycle', $this->codes( FormSchema::normalize( $d ) ) );
	}

	public function test_a_rule_on_an_info_field_is_rejected() {
		$d             = $this->base();
		$d['fields'][] = array( 'key' => 'inf', 'type' => 'info', 'text' => 'نص' );
		$d['fields'][] = array( 'key' => 'extra', 'type' => 'text', 'label' => 'x', 'visibility' => array( 'mode' => 'conditional', 'rules' => array( array( 'field' => 'inf', 'op' => 'not_empty' ) ) ) );
		$this->assertContains( 'rule_on_info_field', $this->codes( FormSchema::normalize( $d ) ) );
	}

	public function test_min_max_and_quantity_defaults() {
		$d             = $this->base();
		$d['fields'][] = array( 'key' => 'qty', 'type' => 'quantity', 'label' => 'الكمية' );
		$d['fields'][] = array( 'key' => 'num', 'type' => 'number', 'label' => 'x', 'min' => 10, 'max' => 5 );
		$r             = FormSchema::normalize( $d );
		$this->assertContains( 'min_greater_than_max', $this->codes( $r ) );
		$by = array_column( $r['definition']['fields'], null, 'key' );
		$this->assertSame( 1, $by['qty']['min'] );
		$this->assertSame( 99, $by['qty']['max'] );
	}

	public function test_texts_are_cleaned_and_unknown_keys_dropped() {
		$d                         = $this->base();
		$d['fields'][0]['label']   = '<b>الاسم</b><script>x</script>';
		$d['fields'][0]['evil']    = 'x';
		$d['evil']                 = 'y';
		$r                         = FormSchema::normalize( $d );
		$this->assertSame( 'الاسمx', $r['definition']['fields'][0]['label'] );
		$this->assertArrayNotHasKey( 'evil', $r['definition']['fields'][0] );
		$this->assertArrayNotHasKey( 'evil', $r['definition'] );
	}

	public function test_terms_are_always_required_and_limits_enforced() {
		$d             = $this->base();
		$d['fields'][] = array( 'key' => 'agree', 'type' => 'terms', 'label' => 'أوافق', 'required' => false );
		$by            = array_column( FormSchema::normalize( $d )['definition']['fields'], null, 'key' );
		$this->assertTrue( $by['agree']['required'] );
		$d = $this->base();
		for ( $i = 0; $i < 45; $i++ ) {
			$d['fields'][] = array( 'key' => 'f' . $i, 'type' => 'text', 'label' => 'x' );
		}
		$this->assertContains( 'too_many_fields', $this->codes( FormSchema::normalize( $d ) ) );
	}

	public function test_garbage_input() {
		foreach ( array( null, 'x', 5, array() ) as $input ) {
			$r = FormSchema::normalize( $input );
			$this->assertNotEmpty( $r['errors'] );
			$this->assertIsArray( $r['definition']['fields'] );
		}
	}
}
