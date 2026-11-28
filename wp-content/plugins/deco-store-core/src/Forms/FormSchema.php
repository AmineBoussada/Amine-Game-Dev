<?php
/**
 * Validates and normalises a form definition when it is saved. Pure,
 * unit-tested. Unknown keys are dropped, texts cleaned, and structural rules
 * enforced (unique keys, valid types and mappings, required order fields,
 * no visibility cycles).
 *
 * @package DecoStore
 */

namespace DecoStore\Forms;

use DecoStore\Core\Text;

defined( 'ABSPATH' ) || exit;

/**
 * Form definition schema.
 */
class FormSchema {

	const MAX_FIELDS  = 40;
	const MAX_OPTIONS = 50;
	const MAX_RULES   = 5;

	/**
	 * Normalise and validate.
	 *
	 * @param mixed $input Raw definition: array with `name`, `fields`, `settings`.
	 * @return array{definition:array,errors:array<int,array{code:string,field:string}>}
	 */
	public static function normalize( $input ) {
		$errors = array();
		$input  = is_array( $input ) ? $input : array();
		$name   = Text::line( isset( $input['name'] ) ? $input['name'] : '', 100 );
		if ( '' === $name ) {
			$errors[] = array(
				'code'  => 'name_required',
				'field' => '',
			);
		}
		$settings = isset( $input['settings'] ) && is_array( $input['settings'] ) ? $input['settings'] : array();
		$submit   = Text::line( isset( $settings['submit_label'] ) ? $settings['submit_label'] : '', 60 );

		$raw_fields = isset( $input['fields'] ) && is_array( $input['fields'] ) ? array_values( $input['fields'] ) : array();
		if ( count( $raw_fields ) > self::MAX_FIELDS ) {
			$errors[]   = array(
				'code'  => 'too_many_fields',
				'field' => '',
			);
			$raw_fields = array_slice( $raw_fields, 0, self::MAX_FIELDS );
		}

		$fields = array();
		$used   = array();
		foreach ( $raw_fields as $index => $raw ) {
			$field = self::normalize_field( is_array( $raw ) ? $raw : array(), $index, $errors );
			if ( null === $field ) {
				continue;
			}
			if ( isset( $fields[ $field['key'] ] ) ) {
				$errors[] = array(
					'code'  => 'duplicate_key',
					'field' => $field['key'],
				);
				continue;
			}
			if ( 'meta' !== $field['maps_to'] && '' !== $field['maps_to'] ) {
				if ( isset( $used[ $field['maps_to'] ] ) ) {
					$errors[] = array(
						'code'  => 'duplicate_mapping:' . $field['maps_to'],
						'field' => $field['key'],
					);
				}
				$used[ $field['maps_to'] ] = true;
			}
			$fields[ $field['key'] ] = $field;
		}

		foreach ( FieldTypes::required_mappings() as $mapping ) {
			if ( ! isset( $used[ $mapping ] ) ) {
				$errors[] = array(
					'code'  => 'missing_mapping:' . $mapping,
					'field' => '',
				);
			}
		}
		foreach ( $fields as $key => $field ) {
			if ( in_array( $field['maps_to'], FieldTypes::required_mappings(), true ) ) {
				if ( empty( $field['required'] ) ) {
					$fields[ $key ]['required'] = true; // Core fields are always required.
				}
				if ( 'conditional' === $field['visibility']['mode'] ) {
					$errors[] = array(
						'code'  => 'core_field_conditional',
						'field' => $key,
					);
				}
			}
			foreach ( Visibility::dependencies( $field ) as $dep ) {
				if ( ! isset( $fields[ $dep ] ) ) {
					$errors[] = array(
						'code'  => 'rule_unknown_field',
						'field' => $key,
					);
				} elseif ( $dep === $key ) {
					$errors[] = array(
						'code'  => 'rule_on_itself',
						'field' => $key,
					);
				} elseif ( ! FieldTypes::all()[ $fields[ $dep ]['type'] ]['input'] ) {
					$errors[] = array(
						'code'  => 'rule_on_info_field',
						'field' => $key,
					);
				}
			}
		}
		$cycle = Visibility::find_cycle( $fields );
		if ( $cycle ) {
			$errors[] = array(
				'code'  => 'rule_cycle',
				'field' => $cycle[0],
			);
		}

		return array(
			'definition' => array(
				'name'     => $name,
				'settings' => array( 'submit_label' => $submit ),
				'fields'   => array_values( $fields ),
			),
			'errors'     => $errors,
		);
	}

	/**
	 * Normalise one field.
	 *
	 * @param array $raw    Raw field.
	 * @param int   $index  Position (for error reporting).
	 * @param array $errors Errors (by reference).
	 * @return array|null
	 */
	private static function normalize_field( array $raw, $index, array &$errors ) {
		$key = isset( $raw['key'] ) && is_string( $raw['key'] ) ? strtolower( trim( $raw['key'] ) ) : '';
		if ( ! preg_match( '/^[a-z][a-z0-9_]{0,39}$/', $key ) ) {
			$errors[] = array(
				'code'  => 'bad_key',
				'field' => '#' . ( $index + 1 ),
			);
			return null;
		}
		$type = isset( $raw['type'] ) && is_string( $raw['type'] ) ? $raw['type'] : '';
		if ( ! FieldTypes::exists( $type ) ) {
			$errors[] = array(
				'code'  => 'bad_type',
				'field' => $key,
			);
			return null;
		}
		$spec  = FieldTypes::all()[ $type ];
		$label = Text::line( isset( $raw['label'] ) ? $raw['label'] : '', 150 );
		if ( '' === $label && 'info' !== $type ) {
			$errors[] = array(
				'code'  => 'label_required',
				'field' => $key,
			);
		}
		$maps_to = isset( $raw['maps_to'] ) && is_string( $raw['maps_to'] ) ? $raw['maps_to'] : $spec['default_mapping'];
		if ( ! $spec['input'] ) {
			$maps_to = '';
		} elseif ( ! in_array( $maps_to, $spec['mappings'], true ) ) {
			$errors[] = array(
				'code'  => 'bad_mapping',
				'field' => $key,
			);
			$maps_to  = $spec['default_mapping'];
		}

		$field = array(
			'key'         => $key,
			'type'        => $type,
			'label'       => $label,
			'placeholder' => Text::line( isset( $raw['placeholder'] ) ? $raw['placeholder'] : '', 150 ),
			'help'        => Text::line( isset( $raw['help'] ) ? $raw['help'] : '', 300 ),
			'required'    => $spec['input'] && ! empty( $raw['required'] ) && 'false' !== $raw['required'] && '0' !== $raw['required'],
			'width'       => isset( $raw['width'] ) && 'half' === $raw['width'] ? 'half' : 'full',
			'maps_to'     => $maps_to,
			'options'     => array(),
			'min'         => null,
			'max'         => null,
			'text'        => '',
			'visibility'  => array(
				'mode'  => 'always',
				'rules' => array(),
			),
		);
		if ( 'terms' === $type ) {
			$field['required'] = true;
		}
		if ( 'info' === $type ) {
			$field['text'] = Text::block( isset( $raw['text'] ) ? $raw['text'] : '', 600 );
			if ( '' === $field['text'] ) {
				$errors[] = array(
					'code'  => 'text_required',
					'field' => $key,
				);
			}
		}
		foreach ( array( 'min', 'max' ) as $bound ) {
			if ( isset( $raw[ $bound ] ) && '' !== $raw[ $bound ] && is_numeric( $raw[ $bound ] ) && (int) $raw[ $bound ] >= 0 && (int) $raw[ $bound ] <= 100000 ) {
				$field[ $bound ] = (int) $raw[ $bound ];
			}
		}
		if ( null !== $field['min'] && null !== $field['max'] && $field['min'] > $field['max'] ) {
			$errors[]     = array(
				'code'  => 'min_greater_than_max',
				'field' => $key,
			);
			$field['max'] = $field['min'];
		}
		if ( 'quantity' === $type ) {
			$field['min'] = max( 1, null === $field['min'] ? 1 : $field['min'] );
			$field['max'] = null === $field['max'] ? 99 : max( $field['min'], $field['max'] );
		}

		if ( $spec['options'] ) {
			$field['options'] = self::normalize_options( isset( $raw['options'] ) ? $raw['options'] : array(), $key, $errors );
			if ( empty( $field['options'] ) ) {
				$errors[] = array(
					'code'  => 'options_required',
					'field' => $key,
				);
			}
		}

		$visibility = isset( $raw['visibility'] ) && is_array( $raw['visibility'] ) ? $raw['visibility'] : array();
		if ( isset( $visibility['mode'] ) && 'conditional' === $visibility['mode'] ) {
			$rules = array();
			$list  = isset( $visibility['rules'] ) && is_array( $visibility['rules'] ) ? array_values( $visibility['rules'] ) : array();
			foreach ( array_slice( $list, 0, self::MAX_RULES ) as $rule ) {
				$ref = isset( $rule['field'] ) && is_string( $rule['field'] ) ? strtolower( trim( $rule['field'] ) ) : '';
				$op  = isset( $rule['op'] ) && is_string( $rule['op'] ) ? $rule['op'] : '';
				if ( '' === $ref || ! in_array( $op, Visibility::operators(), true ) ) {
					$errors[] = array(
						'code'  => 'bad_rule',
						'field' => $key,
					);
					continue;
				}
				$value = Text::line( isset( $rule['value'] ) ? $rule['value'] : '', 150 );
				if ( in_array( $op, array( 'eq', 'neq', 'in' ), true ) && '' === $value ) {
					$errors[] = array(
						'code'  => 'rule_value_required',
						'field' => $key,
					);
					continue;
				}
				$rules[] = array(
					'field' => $ref,
					'op'    => $op,
					'value' => in_array( $op, array( 'empty', 'not_empty' ), true ) ? '' : $value,
				);
			}
			if ( $rules ) {
				$field['visibility'] = array(
					'mode'  => 'conditional',
					'rules' => $rules,
				);
			} else {
				$errors[] = array(
					'code'  => 'conditional_without_rules',
					'field' => $key,
				);
			}
		}
		return $field;
	}

	/**
	 * Normalise an option list. Accepts [{value,label}] or text lines
	 * ("value|label" or just "label").
	 *
	 * @param mixed  $input  Raw options.
	 * @param string $key    Field key (for errors).
	 * @param array  $errors Errors (by reference).
	 * @return array<int,array{value:string,label:string}>
	 */
	private static function normalize_options( $input, $key, array &$errors ) {
		if ( is_string( $input ) ) {
			$rows = array();
			foreach ( preg_split( '/\r\n|\r|\n/', $input ) as $line ) {
				$line = trim( $line );
				if ( '' === $line ) {
					continue;
				}
				$parts  = explode( '|', $line, 2 );
				$rows[] = 2 === count( $parts ) ? array(
					'value' => $parts[0],
					'label' => $parts[1],
				) : array( 'label' => $parts[0] );
			}
			$input = $rows;
		}
		$out  = array();
		$seen = array();
		foreach ( is_array( $input ) ? array_slice( array_values( $input ), 0, self::MAX_OPTIONS ) : array() as $i => $row ) {
			$label = Text::line( isset( $row['label'] ) ? $row['label'] : '', 150 );
			if ( '' === $label ) {
				continue;
			}
			$value = isset( $row['value'] ) ? Text::line( $row['value'], 80 ) : '';
			if ( '' === $value ) {
				$value = 'o' . ( $i + 1 );
			}
			if ( isset( $seen[ $value ] ) ) {
				$errors[] = array(
					'code'  => 'duplicate_option_value',
					'field' => $key,
				);
				continue;
			}
			$seen[ $value ] = true;
			$out[]          = array(
				'value' => $value,
				'label' => $label,
			);
		}
		return $out;
	}
}
