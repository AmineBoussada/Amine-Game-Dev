<?php
/**
 * Server-side validation of a submitted form. This is the authority: the
 * browser checks are only a convenience. Pure apart from the injected
 * ValidationContext; unit-tested. The order system (Phase 5) calls it.
 *
 * Rules: only fields of the form are read; fields hidden by their
 * visibility rules are discarded and never required; every value is
 * validated by its type; nothing is ever taken from the client except raw
 * strings.
 *
 * @package TopSolution
 */

namespace TopSolution\Forms;

use TopSolution\Core\Text;

defined( 'ABSPATH' ) || exit;

/**
 * Validates submissions.
 */
class SubmissionValidator {

	/**
	 * Validate.
	 *
	 * @param array             $form  Normalised definition (FormSchema::normalize()['definition']).
	 * @param array             $input Raw submitted values keyed by field key.
	 * @param ValidationContext $ctx   Lookups.
	 * @return array{values:array<string,mixed>,errors:array<string,string>,visible:string[],ignored:string[]}
	 */
	public static function validate( array $form, array $input, ValidationContext $ctx ) {
		$fields = array();
		foreach ( $form['fields'] as $field ) {
			$fields[ $field['key'] ] = $field;
		}
		$raw = array();
		foreach ( $fields as $key => $field ) {
			if ( ! FieldTypes::all()[ $field['type'] ]['input'] ) {
				continue;
			}
			$value       = array_key_exists( $key, $input ) ? $input[ $key ] : '';
			$raw[ $key ] = is_scalar( $value ) ? ( 'textarea' === $field['type'] ? Text::block( $value, 2000 ) : Text::line( $value, 2000 ) ) : '';
		}

		$memo    = array();
		$visible = array();
		foreach ( $fields as $key => $field ) {
			if ( Visibility::is_visible( $field, $fields, $raw, $memo ) ) {
				$visible[ $key ] = true;
			}
		}

		$by_mapping = array();
		foreach ( $fields as $key => $field ) {
			if ( '' !== $field['maps_to'] && 'meta' !== $field['maps_to'] ) {
				$by_mapping[ $field['maps_to'] ] = $key;
			}
		}

		// Wilaya first, then the other fields, then commune, then pickup office:
		// the last two are validated against the wilaya (and commune) chosen.
		$rank  = array(
			'wilaya'       => 0,
			'commune'      => 2,
			'pickup_point' => 3,
		);
		$order = array_keys( $fields );
		$index = array_flip( $order );
		usort(
			$order,
			static function ( $a, $b ) use ( $fields, $rank, $index ) {
				$ra = isset( $rank[ $fields[ $a ]['maps_to'] ] ) ? $rank[ $fields[ $a ]['maps_to'] ] : 1;
				$rb = isset( $rank[ $fields[ $b ]['maps_to'] ] ) ? $rank[ $fields[ $b ]['maps_to'] ] : 1;
				return $ra === $rb ? $index[ $a ] <=> $index[ $b ] : $ra <=> $rb;
			}
		);

		$values = array();
		$errors = array();
		foreach ( $order as $key ) {
			if ( ! isset( $fields[ $key ] ) || empty( $visible[ $key ] ) ) {
				continue;
			}
			$field = $fields[ $key ];
			if ( ! FieldTypes::all()[ $field['type'] ]['input'] ) {
				continue;
			}
			$result = self::validate_field( $field, $raw[ $key ], $values, $by_mapping, $ctx );
			if ( null !== $result['error'] ) {
				$errors[ $key ] = $result['error'];
			} elseif ( ! $result['skip'] ) {
				$values[ $key ] = $result['value'];
			}
		}

		$ignored = array();
		foreach ( array_keys( $input ) as $key ) {
			if ( ! isset( $fields[ $key ] ) || empty( $visible[ $key ] ) ) {
				$ignored[] = (string) $key;
			}
		}
		// Keep the definition's field order in the result.
		$ordered = array();
		foreach ( array_keys( $fields ) as $key ) {
			if ( array_key_exists( $key, $values ) ) {
				$ordered[ $key ] = $values[ $key ];
			}
		}
		return array(
			'values'  => $ordered,
			'errors'  => $errors,
			'visible' => array_keys( $visible ),
			'ignored' => $ignored,
		);
	}

	/**
	 * Validate one visible field.
	 *
	 * @param array             $field      Field definition.
	 * @param string            $raw        Raw (cleaned single-line) value.
	 * @param array             $values     Values validated so far.
	 * @param array             $by_mapping Mapping => field key.
	 * @param ValidationContext $ctx        Lookups.
	 * @return array{value:mixed,error:?string,skip:bool}
	 */
	private static function validate_field( array $field, $raw, array $values, array $by_mapping, ValidationContext $ctx ) {
		$ok       = static function ( $value ) {
			return array(
				'value' => $value,
				'error' => null,
				'skip'  => false,
			);
		};
		$fail     = static function ( $code ) {
			return array(
				'value' => null,
				'error' => $code,
				'skip'  => false,
			);
		};
		$skip     = array(
			'value' => null,
			'error' => null,
			'skip'  => true,
		);
		$required = ! empty( $field['required'] );
		$type     = $field['type'];
		$empty    = '' === $raw;

		switch ( $type ) {
			case 'checkbox':
			case 'terms':
				$checked = ! $empty && '0' !== $raw && 'false' !== $raw;
				if ( $required && ! $checked ) {
					return $fail( 'required' );
				}
				return $ok( $checked );

			case 'text':
			case 'textarea':
				$text = 'textarea' === $type ? Text::block( $raw, 2000 ) : Text::line( $raw, 2000 );
				if ( '' === $text ) {
					return $required ? $fail( 'required' ) : $skip;
				}
				$min = null === $field['min'] ? 0 : $field['min'];
				$max = null === $field['max'] ? ( 'textarea' === $type ? 1000 : 191 ) : $field['max'];
				if ( Text::length( $text ) < $min ) {
					return $fail( 'too_short' );
				}
				if ( Text::length( $text ) > $max ) {
					return $fail( 'too_long' );
				}
				return $ok( $text );

			case 'phone':
				if ( $empty ) {
					return $required ? $fail( 'required' ) : $skip;
				}
				$phone = PhoneRule::normalize( $raw, $ctx->phone_rule() );
				return null === $phone ? $fail( 'invalid' ) : $ok( $phone );

			case 'number':
			case 'quantity':
				if ( $empty ) {
					return $required || 'quantity' === $type ? $fail( 'required' ) : $skip;
				}
				$digits = Text::western_digits( $raw );
				if ( ! preg_match( '/^\d{1,9}$/', $digits ) ) {
					return $fail( 'invalid' );
				}
				$number = (int) $digits;
				if ( 'quantity' === $type && $number < 1 ) {
					return $fail( 'invalid' );
				}
				if ( null !== $field['min'] && $number < $field['min'] ) {
					return $fail( 'too_small' );
				}
				if ( null !== $field['max'] && $number > $field['max'] ) {
					return $fail( 'too_large' );
				}
				return $ok( $number );

			case 'select':
			case 'radio':
				if ( $empty ) {
					return $required ? $fail( 'required' ) : $skip;
				}
				foreach ( $field['options'] as $option ) {
					if ( $option['value'] === $raw ) {
						return $ok( $raw );
					}
				}
				return $fail( 'not_allowed' );

			case 'wilaya':
				if ( $empty ) {
					return $required ? $fail( 'required' ) : $skip;
				}
				return preg_match( '/^\d{1,9}$/', $raw ) && $ctx->wilaya_is_served( (int) $raw ) ? $ok( (int) $raw ) : $fail( 'not_allowed' );

			case 'commune':
				$wilaya = isset( $by_mapping['wilaya'], $values[ $by_mapping['wilaya'] ] ) ? (int) $values[ $by_mapping['wilaya'] ] : 0;
				if ( ! $wilaya ) {
					return $skip; // The wilaya error is reported on the wilaya field.
				}
				if ( 0 === $ctx->commune_count( $wilaya ) ) {
					return $skip; // No commune list for this wilaya: nothing to ask.
				}
				if ( $empty ) {
					return $required ? $fail( 'required' ) : $skip;
				}
				return preg_match( '/^\d{1,9}$/', $raw ) && $ctx->commune_belongs( (int) $raw, $wilaya ) ? $ok( (int) $raw ) : $fail( 'not_allowed' );

			case 'delivery_method':
				if ( $empty ) {
					return $required ? $fail( 'required' ) : $skip;
				}
				return in_array( $raw, $ctx->delivery_methods(), true ) ? $ok( $raw ) : $fail( 'not_allowed' );

			case 'pickup_point':
				if ( $empty ) {
					return $required ? $fail( 'required' ) : $skip;
				}
				$wilaya  = isset( $by_mapping['wilaya'], $values[ $by_mapping['wilaya'] ] ) ? (int) $values[ $by_mapping['wilaya'] ] : 0;
				$commune = isset( $by_mapping['commune'], $values[ $by_mapping['commune'] ] ) ? (int) $values[ $by_mapping['commune'] ] : 0;
				return $wilaya && preg_match( '/^\d{1,9}$/', $raw ) && $ctx->pickup_serves( (int) $raw, $wilaya, $commune ) ? $ok( (int) $raw ) : $fail( 'not_allowed' );
		}
		return $skip;
	}
}
