<?php
/**
 * Customizer settings. Every business value (phone, address, hours, copy) is
 * entered by the store owner; nothing is hard-coded except neutral UI labels
 * and the three delivery/payment facts of the business model.
 *
 * @package DecoStoreChild
 */

defined( 'ABSPATH' ) || exit;

/**
 * Default values of all theme options.
 *
 * @return array<string,mixed>
 */
function ds_child_defaults() {
	return array(
		'announcement'     => '',
		'show_search'      => true,
		'show_cart'        => false,
		'phone'            => '',
		'whatsapp'         => '',
		'email'            => '',
		'address'          => '',
		'hours'            => '',
		'hero_title'       => '',
		'hero_text'        => '',
		'hero_cta_label'   => __( 'تسوّق الآن', 'deco-store-child' ),
		'hero_cta_url'     => '',
		'hero_image'       => 0,
		'trust_1'          => __( 'الدفع عند الاستلام', 'deco-store-child' ),
		'trust_2'          => __( 'توصيل إلى المنزل', 'deco-store-child' ),
		'trust_3'          => __( 'الاستلام من مكتب التوصيل', 'deco-store-child' ),
		'show_categories'  => true,
		'show_featured'    => true,
		'show_steps'       => true,
		'featured_title'   => __( 'منتجات مختارة', 'deco-store-child' ),
		'featured_count'   => 8,
		'step_1'           => __( 'اختر المنتج واطلبه من الموقع', 'deco-store-child' ),
		'step_2'           => __( 'نوصل طلبك إلى منزلك أو إلى مكتب التوصيل', 'deco-store-child' ),
		'step_3'           => __( 'تدفع عند الاستلام', 'deco-store-child' ),
		'footer_about'     => '',
		'footer_copyright' => '',
	);
}

/**
 * Get a theme option.
 *
 * @param string $key Option key (see ds_child_defaults()).
 * @return mixed
 */
function ds_child_get( $key ) {
	$defaults = ds_child_defaults();
	$default  = isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
	return get_theme_mod( 'ds_' . $key, $default );
}

/**
 * Sanitize a checkbox.
 *
 * @param mixed $value Raw value.
 * @return bool
 */
function ds_child_sanitize_checkbox( $value ) {
	return (bool) $value;
}

/**
 * Sanitize a phone-like value: keep digits, plus sign, spaces, dashes, parentheses.
 *
 * @param string $value Raw value.
 * @return string
 */
function ds_child_sanitize_phone( $value ) {
	return trim( preg_replace( '/[^0-9+\-\s()]/', '', (string) $value ) );
}

/**
 * Sanitize a bounded integer.
 *
 * @param mixed $value Raw value.
 * @return int
 */
function ds_child_sanitize_count( $value ) {
	return max( 2, min( 24, absint( $value ) ) );
}

/**
 * Register Customizer panel, sections, settings and controls.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 * @return void
 */
function ds_child_customize_register( $wp_customize ) {
	$defaults = ds_child_defaults();

	$wp_customize->add_panel(
		'ds_panel',
		array(
			'title'    => __( 'Déco Store', 'deco-store-child' ),
			'priority' => 30,
		)
	);

	$sections = array(
		'ds_contact' => __( 'بيانات التواصل', 'deco-store-child' ),
		'ds_header'  => __( 'الرأس', 'deco-store-child' ),
		'ds_home'    => __( 'الصفحة الرئيسية', 'deco-store-child' ),
		'ds_footer'  => __( 'التذييل', 'deco-store-child' ),
	);
	foreach ( $sections as $id => $title ) {
		$wp_customize->add_section(
			$id,
			array(
				'title' => $title,
				'panel' => 'ds_panel',
			)
		);
	}

	// Field map: key => array( section, label, control type, sanitize callback ).
	$fields = array(
		'announcement'     => array( 'ds_header', __( 'نص شريط الإعلان (اتركه فارغاً للإخفاء)', 'deco-store-child' ), 'text', 'sanitize_text_field' ),
		'show_search'      => array( 'ds_header', __( 'إظهار البحث', 'deco-store-child' ), 'checkbox', 'ds_child_sanitize_checkbox' ),
		'show_cart'        => array( 'ds_header', __( 'إظهار أيقونة السلة', 'deco-store-child' ), 'checkbox', 'ds_child_sanitize_checkbox' ),
		'phone'            => array( 'ds_contact', __( 'رقم الهاتف', 'deco-store-child' ), 'text', 'ds_child_sanitize_phone' ),
		'whatsapp'         => array( 'ds_contact', __( 'رقم واتساب (مع رمز الدولة)', 'deco-store-child' ), 'text', 'ds_child_sanitize_phone' ),
		'email'            => array( 'ds_contact', __( 'البريد الإلكتروني', 'deco-store-child' ), 'text', 'sanitize_email' ),
		'address'          => array( 'ds_contact', __( 'العنوان', 'deco-store-child' ), 'textarea', 'sanitize_textarea_field' ),
		'hours'            => array( 'ds_contact', __( 'ساعات العمل', 'deco-store-child' ), 'text', 'sanitize_text_field' ),
		'hero_title'       => array( 'ds_home', __( 'عنوان الواجهة (افتراضياً اسم الموقع)', 'deco-store-child' ), 'text', 'sanitize_text_field' ),
		'hero_text'        => array( 'ds_home', __( 'نص الواجهة (افتراضياً وصف الموقع)', 'deco-store-child' ), 'textarea', 'sanitize_textarea_field' ),
		'hero_cta_label'   => array( 'ds_home', __( 'نص زر الواجهة', 'deco-store-child' ), 'text', 'sanitize_text_field' ),
		'hero_cta_url'     => array( 'ds_home', __( 'رابط زر الواجهة (افتراضياً صفحة المتجر)', 'deco-store-child' ), 'url', 'esc_url_raw' ),
		'trust_1'          => array( 'ds_home', __( 'شريط الثقة: عنصر 1', 'deco-store-child' ), 'text', 'sanitize_text_field' ),
		'trust_2'          => array( 'ds_home', __( 'شريط الثقة: عنصر 2', 'deco-store-child' ), 'text', 'sanitize_text_field' ),
		'trust_3'          => array( 'ds_home', __( 'شريط الثقة: عنصر 3', 'deco-store-child' ), 'text', 'sanitize_text_field' ),
		'show_categories'  => array( 'ds_home', __( 'إظهار قسم الأقسام', 'deco-store-child' ), 'checkbox', 'ds_child_sanitize_checkbox' ),
		'show_featured'    => array( 'ds_home', __( 'إظهار المنتجات المختارة', 'deco-store-child' ), 'checkbox', 'ds_child_sanitize_checkbox' ),
		'featured_title'   => array( 'ds_home', __( 'عنوان قسم المنتجات المختارة', 'deco-store-child' ), 'text', 'sanitize_text_field' ),
		'featured_count'   => array( 'ds_home', __( 'عدد المنتجات المختارة (2–24)', 'deco-store-child' ), 'number', 'ds_child_sanitize_count' ),
		'show_steps'       => array( 'ds_home', __( 'إظهار خطوات الطلب', 'deco-store-child' ), 'checkbox', 'ds_child_sanitize_checkbox' ),
		'step_1'           => array( 'ds_home', __( 'الخطوة 1', 'deco-store-child' ), 'text', 'sanitize_text_field' ),
		'step_2'           => array( 'ds_home', __( 'الخطوة 2', 'deco-store-child' ), 'text', 'sanitize_text_field' ),
		'step_3'           => array( 'ds_home', __( 'الخطوة 3', 'deco-store-child' ), 'text', 'sanitize_text_field' ),
		'footer_about'     => array( 'ds_footer', __( 'نبذة التذييل (افتراضياً وصف الموقع)', 'deco-store-child' ), 'textarea', 'sanitize_textarea_field' ),
		'footer_copyright' => array( 'ds_footer', __( 'نص الحقوق (افتراضياً © السنة واسم الموقع)', 'deco-store-child' ), 'text', 'sanitize_text_field' ),
	);

	foreach ( $fields as $key => $field ) {
		$wp_customize->add_setting(
			'ds_' . $key,
			array(
				'default'           => $defaults[ $key ],
				'sanitize_callback' => $field[3],
				'transport'         => 'refresh',
			)
		);
		$wp_customize->add_control(
			'ds_' . $key,
			array(
				'section' => $field[0],
				'label'   => $field[1],
				'type'    => $field[2],
			)
		);
	}

	$wp_customize->add_setting(
		'ds_hero_image',
		array(
			'default'           => 0,
			'sanitize_callback' => 'absint',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Media_Control(
			$wp_customize,
			'ds_hero_image',
			array(
				'section'   => 'ds_home',
				'label'     => __( 'صورة الواجهة', 'deco-store-child' ),
				'mime_type' => 'image',
			)
		)
	);
}
add_action( 'customize_register', 'ds_child_customize_register' );
