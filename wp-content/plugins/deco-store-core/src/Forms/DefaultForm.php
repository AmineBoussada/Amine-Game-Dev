<?php
/**
 * The form created on installation: only the fields an order cannot do
 * without. Labels are neutral UI text; the owner edits everything.
 *
 * @package DecoStore
 */

namespace DecoStore\Forms;

defined( 'ABSPATH' ) || exit;

/**
 * Default form definition.
 */
class DefaultForm {

	/**
	 * Definition (already valid against FormSchema).
	 *
	 * @return array
	 */
	public static function definition() {
		$field = static function ( $key, $type, $label, array $extra = array() ) {
			return array_merge(
				array(
					'key'   => $key,
					'type'  => $type,
					'label' => $label,
				),
				$extra
			);
		};
		return array(
			'name'     => __( 'النموذج الافتراضي', 'deco-store-core' ),
			'settings' => array( 'submit_label' => __( 'تأكيد الطلب', 'deco-store-core' ) ),
			'fields'   => array(
				$field(
					'full_name',
					'text',
					__( 'الاسم الكامل', 'deco-store-core' ),
					array(
						'maps_to'  => 'customer_name',
						'required' => true,
						'min'      => 3,
						'max'      => 80,
					)
				),
				$field( 'phone', 'phone', __( 'رقم الهاتف', 'deco-store-core' ), array( 'required' => true ) ),
				$field(
					'wilaya',
					'wilaya',
					__( 'الولاية', 'deco-store-core' ),
					array(
						'required' => true,
						'width'    => 'half',
					)
				),
				$field(
					'commune',
					'commune',
					__( 'البلدية', 'deco-store-core' ),
					array(
						'required' => true,
						'width'    => 'half',
					)
				),
				$field( 'delivery_method', 'delivery_method', __( 'طريقة التوصيل', 'deco-store-core' ), array( 'required' => true ) ),
				$field(
					'pickup_point',
					'pickup_point',
					__( 'مكتب الاستلام', 'deco-store-core' ),
					array(
						'required'   => true,
						'visibility' => array(
							'mode'  => 'conditional',
							'rules' => array(
								array(
									'field' => 'delivery_method',
									'op'    => 'eq',
									'value' => 'office',
								),
							),
						),
					)
				),
				$field(
					'address',
					'textarea',
					__( 'العنوان', 'deco-store-core' ),
					array(
						'maps_to'    => 'address',
						'required'   => true,
						'max'        => 250,
						'visibility' => array(
							'mode'  => 'conditional',
							'rules' => array(
								array(
									'field' => 'delivery_method',
									'op'    => 'eq',
									'value' => 'home',
								),
							),
						),
					)
				),
				$field(
					'note',
					'textarea',
					__( 'ملاحظات (اختياري)', 'deco-store-core' ),
					array(
						'maps_to' => 'note',
						'max'     => 300,
					)
				),
			),
		);
	}
}
