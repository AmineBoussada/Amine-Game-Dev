<?php
/**
 * Customizer settings. Every business value (phone, address, hours, copy) is
 * entered by the store owner; nothing is hard-coded except neutral UI labels
 * and the three delivery/payment facts of the business model.
 *
 * @package TopSolutionChild
 */

defined( 'ABSPATH' ) || exit;

/**
 * Default values of all theme options.
 *
 * @return array<string,mixed>
 */
function ts_child_defaults() {
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
		'hero_cta_label'   => __( 'تسوّق الآن', 'top-solution-child' ),
		'hero_cta_url'     => '',
		'hero_image'       => 0,
		'trust_1'          => __( 'الدفع عند الاستلام', 'top-solution-child' ),
		'trust_2'          => __( 'توصيل إلى المنزل', 'top-solution-child' ),
		'trust_3'          => __( 'الاستلام من مكتب التوصيل', 'top-solution-child' ),
		'show_categories'  => true,
		'show_featured'    => true,
		'show_steps'       => true,
		'featured_title'   => __( 'منتجات مختارة', 'top-solution-child' ),
		'featured_count'   => 8,
		'step_1'           => __( 'اختر المنتج واطلبه من الموقع', 'top-solution-child' ),
		'step_2'           => __( 'نوصل طلبك إلى منزلك أو إلى مكتب التوصيل', 'top-solution-child' ),
		'step_3'           => __( 'تدفع عند الاستلام', 'top-solution-child' ),
		'footer_about'     => '',
		'footer_copyright' => '',
	);
}

/**
 * Get a theme option.
 *
 * @param string $key Option key (see ts_child_defaults()).
 * @return mixed
 */
function ts_child_get( $key ) {
	$defaults = ts_child_defaults();
	$default  = isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
	return get_theme_mod( 'ts_' . $key, $default );
}

/**
 * Sanitize a checkbox.
 *
 * @param mixed $value Raw value.
 * @return bool
 */
function ts_child_sanitize_checkbox( $value ) {
	return (bool) $value;
}

/**
 * Sanitize a phone-like value: keep digits, plus sign, spaces, dashes, parentheses.
 *
 * @param string $value Raw value.
 * @return string
 */
function ts_child_sanitize_phone( $value ) {
	return trim( preg_replace( '/[^0-9+\-\s()]/', '', (string) $value ) );
}

/**
 * Sanitize a bounded integer.
 *
 * @param mixed $value Raw value.
 * @return int
 */
function ts_child_sanitize_count( $value ) {
	return max( 2, min( 24, absint( $value ) ) );
}

/**
 * Register Customizer panel, sections, settings and controls.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 * @return void
 */
function ts_child_customize_register( $wp_customize ) {
	$defaults = ts_child_defaults();

	$wp_customize->add_panel(
		'ts_panel',
		array(
			'title'    => __( 'Top Solution', 'top-solution-child' ),
			'priority' => 30,
		)
	);

	$sections = array(
		'ts_contact' => __( 'بيانات التواصل', 'top-solution-child' ),
		'ts_header'  => __( 'الرأس', 'top-solution-child' ),
		'ts_home'    => __( 'الصفحة الرئيسية', 'top-solution-child' ),
		'ts_footer'  => __( 'التذييل', 'top-solution-child' ),
	);
	foreach ( $sections as $id => $title ) {
		$wp_customize->add_section(
			$id,
			array(
				'title' => $title,
				'panel' => 'ts_panel',
			)
		);
	}

	// Field map: key => array( section, label, control type, sanitize callback ).
	$fields = array(
		'announcement'     => array( 'ts_header', __( 'نص شريط الإعلان (اتركه فارغاً للإخفاء)', 'top-solution-child' ), 'text', 'sanitize_text_field' ),
		'show_search'      => array( 'ts_header', __( 'إظهار البحث', 'top-solution-child' ), 'checkbox', 'ts_child_sanitize_checkbox' ),
		'show_cart'        => array( 'ts_header', __( 'إظهار أيقونة السلة', 'top-solution-child' ), 'checkbox', 'ts_child_sanitize_checkbox' ),
		'phone'            => array( 'ts_contact', __( 'رقم الهاتف', 'top-solution-child' ), 'text', 'ts_child_sanitize_phone' ),
		'whatsapp'         => array( 'ts_contact', __( 'رقم واتساب (مع رمز الدولة)', 'top-solution-child' ), 'text', 'ts_child_sanitize_phone' ),
		'email'            => array( 'ts_contact', __( 'البريد الإلكتروني', 'top-solution-child' ), 'text', 'sanitize_email' ),
		'address'          => array( 'ts_contact', __( 'العنوان', 'top-solution-child' ), 'textarea', 'sanitize_textarea_field' ),
		'hours'            => array( 'ts_contact', __( 'ساعات العمل', 'top-solution-child' ), 'text', 'sanitize_text_field' ),
		'hero_title'       => array( 'ts_home', __( 'عنوان الواجهة (افتراضياً اسم الموقع)', 'top-solution-child' ), 'text', 'sanitize_text_field' ),
		'hero_text'        => array( 'ts_home', __( 'نص الواجهة (افتراضياً وصف الموقع)', 'top-solution-child' ), 'textarea', 'sanitize_textarea_field' ),
		'hero_cta_label'   => array( 'ts_home', __( 'نص زر الواجهة', 'top-solution-child' ), 'text', 'sanitize_text_field' ),
		'hero_cta_url'     => array( 'ts_home', __( 'رابط زر الواجهة (افتراضياً صفحة المتجر)', 'top-solution-child' ), 'url', 'esc_url_raw' ),
		'trust_1'          => array( 'ts_home', __( 'شريط الثقة: عنصر 1', 'top-solution-child' ), 'text', 'sanitize_text_field' ),
		'trust_2'          => array( 'ts_home', __( 'شريط الثقة: عنصر 2', 'top-solution-child' ), 'text', 'sanitize_text_field' ),
		'trust_3'          => array( 'ts_home', __( 'شريط الثقة: عنصر 3', 'top-solution-child' ), 'text', 'sanitize_text_field' ),
		'show_categories'  => array( 'ts_home', __( 'إظهار قسم الأقسام', 'top-solution-child' ), 'checkbox', 'ts_child_sanitize_checkbox' ),
		'show_featured'    => array( 'ts_home', __( 'إظهار المنتجات المختارة', 'top-solution-child' ), 'checkbox', 'ts_child_sanitize_checkbox' ),
		'featured_title'   => array( 'ts_home', __( 'عنوان قسم المنتجات المختارة', 'top-solution-child' ), 'text', 'sanitize_text_field' ),
		'featured_count'   => array( 'ts_home', __( 'عدد المنتجات المختارة (2–24)', 'top-solution-child' ), 'number', 'ts_child_sanitize_count' ),
		'show_steps'       => array( 'ts_home', __( 'إظهار خطوات الطلب', 'top-solution-child' ), 'checkbox', 'ts_child_sanitize_checkbox' ),
		'step_1'           => array( 'ts_home', __( 'الخطوة 1', 'top-solution-child' ), 'text', 'sanitize_text_field' ),
		'step_2'           => array( 'ts_home', __( 'الخطوة 2', 'top-solution-child' ), 'text', 'sanitize_text_field' ),
		'step_3'           => array( 'ts_home', __( 'الخطوة 3', 'top-solution-child' ), 'text', 'sanitize_text_field' ),
		'footer_about'     => array( 'ts_footer', __( 'نبذة التذييل (افتراضياً وصف الموقع)', 'top-solution-child' ), 'textarea', 'sanitize_textarea_field' ),
		'footer_copyright' => array( 'ts_footer', __( 'نص الحقوق (افتراضياً © السنة واسم الموقع)', 'top-solution-child' ), 'text', 'sanitize_text_field' ),
	);

	foreach ( $fields as $key => $field ) {
		$wp_customize->add_setting(
			'ts_' . $key,
			array(
				'default'           => $defaults[ $key ],
				'sanitize_callback' => $field[3],
				'transport'         => 'refresh',
			)
		);
		$wp_customize->add_control(
			'ts_' . $key,
			array(
				'section' => $field[0],
				'label'   => $field[1],
				'type'    => $field[2],
			)
		);
	}

	$wp_customize->add_setting(
		'ts_hero_image',
		array(
			'default'           => 0,
			'sanitize_callback' => 'absint',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Media_Control(
			$wp_customize,
			'ts_hero_image',
			array(
				'section'   => 'ts_home',
				'label'     => __( 'صورة الواجهة', 'top-solution-child' ),
				'mime_type' => 'image',
			)
		)
	);
}
add_action( 'customize_register', 'ts_child_customize_register' );
