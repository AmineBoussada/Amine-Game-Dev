<?php
use PHPUnit\Framework\TestCase;
use TopSolution\Forms\Visibility;

class VisibilityTest extends TestCase {

	private function field( $key, $rules = null ) {
		return array(
			'key'        => $key,
			'visibility' => $rules ? array( 'mode' => 'conditional', 'rules' => $rules ) : array( 'mode' => 'always', 'rules' => array() ),
		);
	}

	public function test_operators() {
		$this->assertTrue( Visibility::test( 'eq', 'a', 'a' ) );
		$this->assertFalse( Visibility::test( 'eq', 'a', 'b' ) );
		$this->assertTrue( Visibility::test( 'neq', 'a', 'b' ) );
		$this->assertTrue( Visibility::test( 'in', 'b', 'a, b ,c' ) );
		$this->assertFalse( Visibility::test( 'in', 'd', 'a,b,c' ) );
		$this->assertTrue( Visibility::test( 'empty', '', '' ) );
		$this->assertFalse( Visibility::test( 'empty', 'x', '' ) );
		$this->assertTrue( Visibility::test( 'not_empty', 'x', '' ) );
		$this->assertFalse( Visibility::test( 'bogus', 'x', 'x' ) );
		$this->assertTrue( Visibility::test( 'empty', array( 'x' ), '' ), 'Non-scalars count as empty.' );
	}

	public function test_a_field_without_rules_is_visible() {
		$f = array( 'a' => $this->field( 'a' ) );
		$this->assertTrue( Visibility::is_visible( $f['a'], $f, array() ) );
	}

	public function test_conditional_field_follows_its_rule() {
		$f = array(
			'method' => $this->field( 'method' ),
			'office' => $this->field( 'office', array( array( 'field' => 'method', 'op' => 'eq', 'value' => 'office' ) ) ),
		);
		$this->assertTrue( Visibility::is_visible( $f['office'], $f, array( 'method' => 'office' ) ) );
		$this->assertFalse( Visibility::is_visible( $f['office'], $f, array( 'method' => 'home' ) ) );
		$this->assertFalse( Visibility::is_visible( $f['office'], $f, array() ) );
	}

	public function test_rules_are_combined_with_and() {
		$f = array(
			'a' => $this->field( 'a' ),
			'b' => $this->field( 'b' ),
			'c' => $this->field( 'c', array( array( 'field' => 'a', 'op' => 'eq', 'value' => '1' ), array( 'field' => 'b', 'op' => 'eq', 'value' => '2' ) ) ),
		);
		$this->assertTrue( Visibility::is_visible( $f['c'], $f, array( 'a' => '1', 'b' => '2' ) ) );
		$this->assertFalse( Visibility::is_visible( $f['c'], $f, array( 'a' => '1', 'b' => '3' ) ) );
	}

	public function test_a_hidden_field_counts_as_empty_for_fields_that_depend_on_it() {
		$f = array(
			'a' => $this->field( 'a' ),
			'b' => $this->field( 'b', array( array( 'field' => 'a', 'op' => 'eq', 'value' => 'x' ) ) ),
			'c' => $this->field( 'c', array( array( 'field' => 'b', 'op' => 'not_empty', 'value' => '' ) ) ),
		);
		// b is hidden (a != x) although a value for b was submitted: c must not see it.
		$this->assertFalse( Visibility::is_visible( $f['c'], $f, array( 'a' => 'y', 'b' => 'sneaky' ) ) );
		$this->assertTrue( Visibility::is_visible( $f['c'], $f, array( 'a' => 'x', 'b' => 'real' ) ) );
	}

	public function test_a_rule_on_a_missing_field_hides_the_field() {
		$f = array( 'c' => $this->field( 'c', array( array( 'field' => 'ghost', 'op' => 'empty', 'value' => '' ) ) ) );
		$this->assertFalse( Visibility::is_visible( $f['c'], $f, array() ) );
	}

	public function test_cycles_are_found() {
		$rule = static function ( $ref ) {
			return array( array( 'field' => $ref, 'op' => 'not_empty', 'value' => '' ) );
		};
		$two  = array( 'a' => $this->field( 'a', $rule( 'b' ) ), 'b' => $this->field( 'b', $rule( 'a' ) ) );
		$self = array( 'a' => $this->field( 'a', $rule( 'a' ) ) );
		$long = array( 'a' => $this->field( 'a', $rule( 'b' ) ), 'b' => $this->field( 'b', $rule( 'c' ) ), 'c' => $this->field( 'c', $rule( 'a' ) ) );
		$ok   = array( 'a' => $this->field( 'a' ), 'b' => $this->field( 'b', $rule( 'a' ) ), 'c' => $this->field( 'c', $rule( 'b' ) ) );
		$this->assertNotNull( Visibility::find_cycle( $two ) );
		$this->assertNotNull( Visibility::find_cycle( $self ) );
		$this->assertCount( 3, Visibility::find_cycle( $long ) );
		$this->assertNull( Visibility::find_cycle( $ok ) );
	}

	public function test_dependencies() {
		$f = $this->field( 'c', array( array( 'field' => 'a', 'op' => 'empty', 'value' => '' ), array( 'field' => 'a', 'op' => 'eq', 'value' => '1' ), array( 'field' => 'b', 'op' => 'empty', 'value' => '' ) ) );
		$this->assertSame( array( 'a', 'b' ), Visibility::dependencies( $f ) );
		$this->assertSame( array(), Visibility::dependencies( $this->field( 'x' ) ) );
	}
}
