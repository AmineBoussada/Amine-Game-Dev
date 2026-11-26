<?php
/**
 * Customizer: agency details, social links, home page and display options.
 *
 * @package Tassili_Immobilier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitize a WhatsApp or phone number to digits.
 *
 * @param string $value Value.
 * @return string
 */
function tassili_sanitize_digits( $value ) {
	return preg_replace( '/\D+/', '', (string) $value );
}

/**
 * Sanitize a checkbox.
 *
 * @param mixed $value Value.
 * @return int
 */
function tassili_sanitize_checkbox( $value ) {
	return $value ? 1 : 0;
}

/**
 * Sanitize a language choice.
 *
 * @param string $value Value.
 * @return string
 */
function tassili_sanitize_language( $value ) {
	return array_key_exists( $value, tassili_supported_languages() ) ? $value : 'fr';
}

/**
 * Sanitize the weekly closing day (0 = Sunday ... 6 = Saturday).
 *
 * @param mixed $value Value.
 * @return int
 */
function tassili_sanitize_weekday( $value ) {
	$value = (int) $value;
	return ( $value >= 0 && $value <= 6 ) ? $value : 5;
}

/**
 * Sanitize a number within limits.
 *
 * @param mixed                $value   Value.
 * @param WP_Customize_Setting $setting Setting (min/max read from its control).
 * @return int
 */
function tassili_sanitize_bounded_int( $value, $setting ) {
	$value   = absint( $value );
	$control = $setting->manager->get_control( $setting->id );
	$min     = ( $control && isset( $control->input_attrs['min'] ) ) ? (int) $control->input_attrs['min'] : 0;
	$max     = ( $control && isset( $control->input_attrs['max'] ) ) ? (int) $control->input_attrs['max'] : PHP_INT_MAX;
	return max( $min, min( $max, $value ) );
}

/**
 * Register Customizer settings.
 *
 * @param WP_Customize_Manager $wp_customize Manager.
 */
function tassili_customize_register( $wp_customize ) {
	$defaults = tassili_defaults();

	$wp_customize->add_panel(
		'tassili',
		array(
			'title'    => __( 'Tassili Immobilier', 'tassili-immobilier' ),
			'priority' => 30,
		)
	);

	/**
	 * Add a setting with its control.
	 *
	 * @param string $section  Section ID.
	 * @param string $key      Setting key (without prefix).
	 * @param string $label    Label.
	 * @param string $type     Control type.
	 * @param string $sanitize Sanitize callback.
	 * @param array  $extra    Extra control arguments.
	 */
	$add = static function ( $section, $key, $label, $type, $sanitize, $extra = array() ) use ( $wp_customize, $defaults ) {
		$wp_customize->add_setting(
			'tassili_' . $key,
			array(
				'default'           => isset( $defaults[ $key ] ) ? $defaults[ $key ] : '',
				'sanitize_callback' => $sanitize,
				'transport'         => 'refresh',
			)
		);
		$wp_customize->add_control(
			'tassili_' . $key,
			array_merge(
				array(
					'section' => $section,
					'label'   => $label,
					'type'    => $type,
				),
				$extra
			)
		);
	};

	/* Agency */
	$wp_customize->add_section(
		'tassili_agency',
		array(
			'title' => __( 'Agency details', 'tassili-immobilier' ),
			'panel' => 'tassili',
		)
	);
	$add( 'tassili_agency', 'phone', __( 'Phone (as displayed)', 'tassili-immobilier' ), 'text', 'sanitize_text_field' );
	$add( 'tassili_agency', 'whatsapp', __( 'WhatsApp number', 'tassili-immobilier' ), 'text', 'tassili_sanitize_digits', array( 'description' => __( 'International format, digits only, for example 213555123456.', 'tassili-immobilier' ) ) );
	$add( 'tassili_agency', 'email', __( 'Public e-mail', 'tassili-immobilier' ), 'email', 'sanitize_email' );
	$add( 'tassili_agency', 'notify_email', __( 'Send requests to', 'tassili-immobilier' ), 'email', 'sanitize_email', array( 'description' => __( 'Contact and visit requests are e-mailed here. Leave empty to use the site administrator’s address.', 'tassili-immobilier' ) ) );
	$add( 'tassili_agency', 'address_fr', __( 'Address (French)', 'tassili-immobilier' ), 'text', 'sanitize_text_field' );
	$add( 'tassili_agency', 'address_ar', __( 'Address (Arabic)', 'tassili-immobilier' ), 'text', 'sanitize_text_field' );
	$add( 'tassili_agency', 'map_query', __( 'Map search text', 'tassili-immobilier' ), 'text', 'sanitize_text_field', array( 'description' => __( 'Used for the “Open in Google Maps” link.', 'tassili-immobilier' ) ) );
	$add( 'tassili_agency', 'hours_fr', __( 'Opening hours (French)', 'tassili-immobilier' ), 'text', 'sanitize_text_field' );
	$add( 'tassili_agency', 'hours_ar', __( 'Opening hours (Arabic)', 'tassili-immobilier' ), 'text', 'sanitize_text_field' );
	$add( 'tassili_agency', 'saturday_fr', __( 'Saturday (French)', 'tassili-immobilier' ), 'text', 'sanitize_text_field' );
	$add( 'tassili_agency', 'saturday_ar', __( 'Saturday (Arabic)', 'tassili-immobilier' ), 'text', 'sanitize_text_field' );
	$add( 'tassili_agency', 'friday_fr', __( 'Closed day note (French)', 'tassili-immobilier' ), 'text', 'sanitize_text_field' );
	$add( 'tassili_agency', 'friday_ar', __( 'Closed day note (Arabic)', 'tassili-immobilier' ), 'text', 'sanitize_text_field' );
	$add(
		'tassili_agency',
		'closed_weekday',
		__( 'Day visits cannot be booked', 'tassili-immobilier' ),
		'select',
		'tassili_sanitize_weekday',
		array(
			'choices' => array(
				0 => __( 'Sunday', 'tassili-immobilier' ),
				1 => __( 'Monday', 'tassili-immobilier' ),
				2 => __( 'Tuesday', 'tassili-immobilier' ),
				3 => __( 'Wednesday', 'tassili-immobilier' ),
				4 => __( 'Thursday', 'tassili-immobilier' ),
				5 => __( 'Friday', 'tassili-immobilier' ),
				6 => __( 'Saturday', 'tassili-immobilier' ),
			),
		)
	);
	$add( 'tassili_agency', 'rc', __( 'Trade register number', 'tassili-immobilier' ), 'text', 'sanitize_text_field' );
	$add( 'tassili_agency', 'nif', __( 'Tax identification number', 'tassili-immobilier' ), 'text', 'sanitize_text_field' );
	$add( 'tassili_agency', 'ref_prefix', __( 'Listing reference prefix', 'tassili-immobilier' ), 'text', 'sanitize_text_field' );

	/* Social */
	$wp_customize->add_section(
		'tassili_social',
		array(
			'title' => __( 'Social links', 'tassili-immobilier' ),
			'panel' => 'tassili',
		)
	);
	$add( 'tassili_social', 'facebook', __( 'Facebook page URL', 'tassili-immobilier' ), 'url', 'esc_url_raw' );
	$add( 'tassili_social', 'instagram', __( 'Instagram URL', 'tassili-immobilier' ), 'url', 'esc_url_raw' );

	/* Home page */
	$wp_customize->add_section(
		'tassili_home',
		array(
			'title' => __( 'Home page', 'tassili-immobilier' ),
			'panel' => 'tassili',
		)
	);
	$wp_customize->add_setting(
		'tassili_hero_image',
		array(
			'default'           => 0,
			'sanitize_callback' => 'absint',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Media_Control(
			$wp_customize,
			'tassili_hero_image',
			array(
				'section'   => 'tassili_home',
				'label'     => __( 'Hero image', 'tassili-immobilier' ),
				'mime_type' => 'image',
			)
		)
	);
	$add( 'tassili_home', 'hero_title_fr', __( 'Hero headline (French)', 'tassili-immobilier' ), 'text', 'sanitize_text_field', array( 'description' => __( 'Leave empty to use the default text.', 'tassili-immobilier' ) ) );
	$add( 'tassili_home', 'hero_title_ar', __( 'Hero headline (Arabic)', 'tassili-immobilier' ), 'text', 'sanitize_text_field' );
	$add(
		'tassili_home',
		'stat_sold',
		__( 'Properties sold', 'tassili-immobilier' ),
		'number',
		'tassili_sanitize_bounded_int',
		array(
			'input_attrs' => array(
				'min' => 0,
				'max' => 999999,
			),
		)
	);
	$add(
		'tassili_home',
		'founded',
		__( 'Year founded', 'tassili-immobilier' ),
		'number',
		'tassili_sanitize_bounded_int',
		array(
			'input_attrs' => array(
				'min' => 1900,
				'max' => 2100,
			),
			'description' => __( 'Used to compute the years of experience.', 'tassili-immobilier' ),
		)
	);
	$add(
		'tassili_home',
		'stat_wilayas',
		__( 'Wilayas covered', 'tassili-immobilier' ),
		'number',
		'tassili_sanitize_bounded_int',
		array(
			'input_attrs' => array(
				'min' => 0,
				'max' => 58,
			),
		)
	);
	$add(
		'tassili_home',
		'stat_satisfaction',
		__( 'Satisfied clients (%)', 'tassili-immobilier' ),
		'number',
		'tassili_sanitize_bounded_int',
		array(
			'input_attrs' => array(
				'min' => 0,
				'max' => 100,
			),
		)
	);
	$add( 'tassili_home', 'hero_lede_fr', __( 'Hero text (French)', 'tassili-immobilier' ), 'textarea', 'sanitize_textarea_field', array( 'description' => __( 'Leave empty to use the default text.', 'tassili-immobilier' ) ) );
	$add( 'tassili_home', 'hero_lede_ar', __( 'Hero text (Arabic)', 'tassili-immobilier' ), 'textarea', 'sanitize_textarea_field' );
	$add( 'tassili_home', 'cta_url', __( 'Header button link', 'tassili-immobilier' ), 'url', 'esc_url_raw', array( 'description' => __( 'The “List my property” button. Leave empty to link to the contact page.', 'tassili-immobilier' ) ) );

	/* Display */
	$wp_customize->add_section(
		'tassili_display',
		array(
			'title' => __( 'Language and display', 'tassili-immobilier' ),
			'panel' => 'tassili',
		)
	);
	$wp_customize->add_setting(
		'tassili_default_language',
		array(
			'default'           => 'fr',
			'sanitize_callback' => 'tassili_sanitize_language',
		)
	);
	$wp_customize->add_control(
		'tassili_default_language',
		array(
			'section'     => 'tassili_display',
			'label'       => __( 'Default language', 'tassili-immobilier' ),
			'description' => __( 'Shown to visitors who have not chosen a language yet.', 'tassili-immobilier' ),
			'type'        => 'select',
			'choices'     => array(
				'fr' => 'Français',
				'ar' => 'العربية',
			),
		)
	);
	$add(
		'tassili_display',
		'show_closed',
		__( 'Show sold and rented properties in the listings', 'tassili-immobilier' ),
		'checkbox',
		'tassili_sanitize_checkbox',
		array( 'description' => __( 'They always keep a “Sold” or “Rented” label. When off, they disappear from searches and the home page.', 'tassili-immobilier' ) )
	);
	$add(
		'tassili_display',
		'per_page',
		__( 'Properties per page', 'tassili-immobilier' ),
		'number',
		'tassili_sanitize_bounded_int',
		array(
			'input_attrs' => array(
				'min'  => 3,
				'max'  => 60,
				'step' => 3,
			),
		)
	);
}
add_action( 'customize_register', 'tassili_customize_register' );
