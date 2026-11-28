<?php
/**
 * Conditional visibility of fields. Pure, unit-tested. The same rules are
 * mirrored in assets/forms.js for the browser; PHP is authoritative.
 *
 * @package DecoStore
 */

namespace DecoStore\Forms;

defined( 'ABSPATH' ) || exit;

/**
 * Rule evaluation and dependency checks.
 */
class Visibility {

	/**
	 * Operators.
	 *
	 * @return string[]
	 */
	public static function operators() {
		return array( 'eq', 'neq', 'in', 'empty', 'not_empty' );
	}

	/**
	 * Evaluate one rule against a value.
	 *
	 * @param string $op    Operator.
	 * @param mixed  $actual Current value of the referenced field ('' when hidden or empty).
	 * @param string $expected Rule value (for "in": comma-separated list).
	 * @return bool
	 */
	public static function test( $op, $actual, $expected ) {
		$actual = is_scalar( $actual ) ? (string) $actual : '';
		switch ( $op ) {
			case 'eq':
				return $actual === (string) $expected;
			case 'neq':
				return $actual !== (string) $expected;
			case 'in':
				$list = array_map( 'trim', explode( ',', (string) $expected ) );
				return in_array( $actual, $list, true );
			case 'empty':
				return '' === $actual;
			case 'not_empty':
				return '' !== $actual;
		}
		return false;
	}

	/**
	 * Is a field visible, given the values of the other fields?
	 * Rules are combined with AND. A referenced field that is itself hidden
	 * counts as empty.
	 *
	 * @param array $field  Field definition.
	 * @param array $fields All fields keyed by field key.
	 * @param array $values Raw values keyed by field key.
	 * @param array $memo   Visibility cache (by reference).
	 * @return bool
	 */
	public static function is_visible( array $field, array $fields, array $values, array &$memo = array() ) {
		$key = $field['key'];
		if ( isset( $memo[ $key ] ) ) {
			return $memo[ $key ];
		}
		$memo[ $key ] = true; // Guards against cycles; schema validation rejects them on save.
		$visibility   = isset( $field['visibility'] ) ? $field['visibility'] : array();
		if ( empty( $visibility['mode'] ) || 'conditional' !== $visibility['mode'] || empty( $visibility['rules'] ) ) {
			return true;
		}
		foreach ( $visibility['rules'] as $rule ) {
			$ref = $rule['field'];
			if ( ! isset( $fields[ $ref ] ) ) {
				$memo[ $key ] = false;
				return false;
			}
			$actual = '';
			if ( self::is_visible( $fields[ $ref ], $fields, $values, $memo ) && isset( $values[ $ref ] ) ) {
				$actual = $values[ $ref ];
			}
			if ( ! self::test( $rule['op'], $actual, isset( $rule['value'] ) ? $rule['value'] : '' ) ) {
				$memo[ $key ] = false;
				return false;
			}
		}
		$memo[ $key ] = true;
		return true;
	}

	/**
	 * Keys of the fields a field's visibility depends on.
	 *
	 * @param array $field Field definition.
	 * @return string[]
	 */
	public static function dependencies( array $field ) {
		$out = array();
		if ( ! empty( $field['visibility']['rules'] ) ) {
			foreach ( $field['visibility']['rules'] as $rule ) {
				$out[] = $rule['field'];
			}
		}
		return array_values( array_unique( $out ) );
	}

	/**
	 * Find a dependency cycle (A depends on B depends on A, or on itself).
	 *
	 * @param array $fields All fields keyed by field key.
	 * @return string[]|null Keys forming the cycle, or null when there is none.
	 */
	public static function find_cycle( array $fields ) {
		$state = array();
		$path  = array();
		$visit = static function ( $key ) use ( &$visit, &$state, &$path, $fields ) {
			if ( isset( $state[ $key ] ) ) {
				if ( 1 === $state[ $key ] ) {
					return array_slice( $path, (int) array_search( $key, $path, true ) );
				}
				return null;
			}
			$state[ $key ] = 1;
			$path[]        = $key;
			foreach ( self::dependencies( $fields[ $key ] ) as $dep ) {
				if ( isset( $fields[ $dep ] ) ) {
					$cycle = $visit( $dep );
					if ( $cycle ) {
						return $cycle;
					}
				}
			}
			array_pop( $path );
			$state[ $key ] = 2;
			return null;
		};
		foreach ( array_keys( $fields ) as $key ) {
			$cycle = $visit( $key );
			if ( $cycle ) {
				return $cycle;
			}
		}
		return null;
	}
}
