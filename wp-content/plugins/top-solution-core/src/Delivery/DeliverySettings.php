<?php
/**
 * Delivery settings (methods on/off, labels, free-delivery threshold).
 * Stored as one option; validated by a pure sanitiser (unit-tested).
 *
 * @package TopSolution
 */

namespace TopSolution\Delivery;

defined( 'ABSPATH' ) || exit;

/**
 * Settings schema and access.
 */
class DeliverySettings {

	const OPTION = 'ts_delivery_settings';

	/**
	 * Delivery method ids (the business offers home delivery and pickup at a
	 * delivery office).
	 *
	 * @return string[]
	 */
	public static function method_ids() {
		return array( 'home', 'office' );
	}

	/**
	 * Defaults. Labels are neutral UI text; there is no price default and no
	 * free-delivery default.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'methods'        => array(
				'home'   => array(
					'enabled' => true,
					'label'   => __( 'التوصيل إلى المنزل', 'top-solution-core' ),
				),
				'office' => array(
					'enabled' => true,
					'label'   => __( 'الاستلام من مكتب التوصيل', 'top-solution-core' ),
				),
			),
			'free_threshold' => '',
		);
	}

	/**
	 * Validate submitted settings (pure).
	 *
	 * @param mixed $input    Raw input.
	 * @param int   $decimals Store decimals.
	 * @return array{settings:array,errors:string[]}
	 */
	public static function sanitize( $input, $decimals ) {
		$settings = self::defaults();
		$errors   = array();
		$input    = is_array( $input ) ? $input : array();
		foreach ( self::method_ids() as $id ) {
			$row                                   = isset( $input['methods'][ $id ] ) && is_array( $input['methods'][ $id ] ) ? $input['methods'][ $id ] : array();
			$settings['methods'][ $id ]['enabled'] = ! empty( $row['enabled'] ) && '0' !== $row['enabled'];
			$label                                 = isset( $row['label'] ) ? CsvImporter::clean_name( $row['label'] ) : '';
			if ( '' !== $label ) {
				$settings['methods'][ $id ]['label'] = $label;
			}
		}
		$threshold = isset( $input['free_threshold'] ) ? trim( (string) $input['free_threshold'] ) : '';
		if ( '' !== $threshold ) {
			$minor = Money::to_minor( $threshold, $decimals );
			if ( null === $minor || 0 === $minor ) {
				$errors[] = 'bad_free_threshold';
			} else {
				$settings['free_threshold'] = Money::to_decimal( $minor, $decimals );
			}
		}
		return array(
			'settings' => $settings,
			'errors'   => $errors,
		);
	}

	/**
	 * Stored settings merged over defaults.
	 *
	 * @return array
	 */
	public static function all() {
		$stored   = get_option( self::OPTION, array() );
		$defaults = self::defaults();
		if ( ! is_array( $stored ) ) {
			return $defaults;
		}
		foreach ( self::method_ids() as $id ) {
			if ( isset( $stored['methods'][ $id ] ) && is_array( $stored['methods'][ $id ] ) ) {
				$defaults['methods'][ $id ] = array_merge( $defaults['methods'][ $id ], $stored['methods'][ $id ] );
			}
		}
		if ( isset( $stored['free_threshold'] ) ) {
			$defaults['free_threshold'] = (string) $stored['free_threshold'];
		}
		return $defaults;
	}

	/**
	 * Method ids that are switched on.
	 *
	 * @return string[]
	 */
	public static function enabled_methods() {
		$all = self::all();
		return array_values(
			array_filter(
				self::method_ids(),
				static function ( $id ) use ( $all ) {
					return ! empty( $all['methods'][ $id ]['enabled'] );
				}
			)
		);
	}

	/**
	 * Label of a method.
	 *
	 * @param string $id Method id.
	 * @return string
	 */
	public static function label( $id ) {
		$all = self::all();
		return isset( $all['methods'][ $id ]['label'] ) ? (string) $all['methods'][ $id ]['label'] : $id;
	}
}
