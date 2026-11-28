<?php
/**
 * Field type catalogue and mapping rules.
 *
 * @package TopSolution
 */

namespace TopSolution\Forms;

defined( 'ABSPATH' ) || exit;

/**
 * What each field type is and may do. Pure data, unit-tested.
 */
class FieldTypes {

	/**
	 * Order mappings. Non-"meta" mappings may be used by one field only.
	 * Mappings in `required_mappings()` must exist in every form.
	 *
	 * @return string[]
	 */
	public static function mappings() {
		return array( 'customer_name', 'phone', 'wilaya', 'commune', 'address', 'note', 'delivery_method', 'pickup_point', 'quantity', 'meta' );
	}

	/**
	 * Mappings every order form must contain (the order cannot be created
	 * without them).
	 *
	 * @return string[]
	 */
	public static function required_mappings() {
		return array( 'customer_name', 'phone', 'wilaya', 'delivery_method' );
	}

	/**
	 * Type catalogue.
	 *
	 * Keys: input (collects a value), options (needs an option list),
	 * mappings (allowed mappings), default_mapping, size (min/max applies to
	 * length / value), label_key (UI label).
	 *
	 * @return array<string,array>
	 */
	public static function all() {
		return array(
			'text'            => array(
				'input'           => true,
				'options'         => false,
				'mappings'        => array( 'customer_name', 'address', 'note', 'meta' ),
				'default_mapping' => 'meta',
			),
			'textarea'        => array(
				'input'           => true,
				'options'         => false,
				'mappings'        => array( 'address', 'note', 'meta' ),
				'default_mapping' => 'meta',
			),
			'phone'           => array(
				'input'           => true,
				'options'         => false,
				'mappings'        => array( 'phone', 'meta' ),
				'default_mapping' => 'phone',
			),
			'number'          => array(
				'input'           => true,
				'options'         => false,
				'mappings'        => array( 'meta' ),
				'default_mapping' => 'meta',
			),
			'select'          => array(
				'input'           => true,
				'options'         => true,
				'mappings'        => array( 'meta' ),
				'default_mapping' => 'meta',
			),
			'radio'           => array(
				'input'           => true,
				'options'         => true,
				'mappings'        => array( 'meta' ),
				'default_mapping' => 'meta',
			),
			'checkbox'        => array(
				'input'           => true,
				'options'         => false,
				'mappings'        => array( 'meta' ),
				'default_mapping' => 'meta',
			),
			'terms'           => array(
				'input'           => true,
				'options'         => false,
				'mappings'        => array( 'meta' ),
				'default_mapping' => 'meta',
			),
			'quantity'        => array(
				'input'           => true,
				'options'         => false,
				'mappings'        => array( 'quantity' ),
				'default_mapping' => 'quantity',
			),
			'wilaya'          => array(
				'input'           => true,
				'options'         => false,
				'mappings'        => array( 'wilaya' ),
				'default_mapping' => 'wilaya',
			),
			'commune'         => array(
				'input'           => true,
				'options'         => false,
				'mappings'        => array( 'commune' ),
				'default_mapping' => 'commune',
			),
			'delivery_method' => array(
				'input'           => true,
				'options'         => false,
				'mappings'        => array( 'delivery_method' ),
				'default_mapping' => 'delivery_method',
			),
			'pickup_point'    => array(
				'input'           => true,
				'options'         => false,
				'mappings'        => array( 'pickup_point' ),
				'default_mapping' => 'pickup_point',
			),
			'info'            => array(
				'input'           => false,
				'options'         => false,
				'mappings'        => array(),
				'default_mapping' => '',
			),
		);
	}

	/**
	 * Does the type exist?
	 *
	 * @param string $type Type id.
	 * @return bool
	 */
	public static function exists( $type ) {
		return isset( self::all()[ $type ] );
	}

	/**
	 * Arabic label of a type (for the builder).
	 *
	 * @param string $type Type id.
	 * @return string
	 */
	public static function label( $type ) {
		$labels = array(
			'text'            => __( 'نص قصير', 'top-solution-core' ),
			'textarea'        => __( 'نص طويل', 'top-solution-core' ),
			'phone'           => __( 'رقم هاتف', 'top-solution-core' ),
			'number'          => __( 'رقم', 'top-solution-core' ),
			'select'          => __( 'قائمة منسدلة', 'top-solution-core' ),
			'radio'           => __( 'اختيار واحد', 'top-solution-core' ),
			'checkbox'        => __( 'خانة اختيار', 'top-solution-core' ),
			'terms'           => __( 'الموافقة على الشروط', 'top-solution-core' ),
			'quantity'        => __( 'الكمية', 'top-solution-core' ),
			'wilaya'          => __( 'الولاية', 'top-solution-core' ),
			'commune'         => __( 'البلدية', 'top-solution-core' ),
			'delivery_method' => __( 'طريقة التوصيل', 'top-solution-core' ),
			'pickup_point'    => __( 'مكتب الاستلام', 'top-solution-core' ),
			'info'            => __( 'نص توضيحي', 'top-solution-core' ),
		);
		return isset( $labels[ $type ] ) ? $labels[ $type ] : $type;
	}

	/**
	 * Types that can be added from the library (types tied to a required
	 * mapping are in every form already and cannot be added twice).
	 *
	 * @return string[]
	 */
	public static function addable() {
		return array( 'text', 'textarea', 'number', 'select', 'radio', 'checkbox', 'terms', 'quantity', 'commune', 'pickup_point', 'info' );
	}
}
