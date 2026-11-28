<?php
/**
 * Server-side form rendering: semantic, accessible, RTL-friendly markup.
 * Conditional fields and dependent selects are enhanced by assets/forms.js;
 * the server validator remains the authority (SubmissionValidator).
 *
 * @package DecoStore
 */

namespace DecoStore\Forms;

use DecoStore\Core\Settings;
use DecoStore\Delivery\DeliverySettings;
use DecoStore\Delivery\GeoRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Renders a form definition as HTML.
 */
class FormRenderer {

	/**
	 * Messages used by the browser-side checks (Arabic; same codes as the server).
	 *
	 * @return array<string,string>
	 */
	public static function messages() {
		return array(
			'required'      => __( 'هذا الحقل مطلوب.', 'deco-store-core' ),
			'invalid'       => __( 'القيمة غير صالحة.', 'deco-store-core' ),
			'not_allowed'   => __( 'اختر قيمة من القائمة.', 'deco-store-core' ),
			'too_short'     => __( 'النص أقصر من المطلوب.', 'deco-store-core' ),
			'too_long'      => __( 'النص أطول من المسموح.', 'deco-store-core' ),
			'too_small'     => __( 'القيمة أصغر من المسموح.', 'deco-store-core' ),
			'too_large'     => __( 'القيمة أكبر من المسموح.', 'deco-store-core' ),
			'phone'         => __( 'أدخل رقم هاتف صحيحاً.', 'deco-store-core' ),
			'summary'       => __( 'يرجى تصحيح الحقول التالية:', 'deco-store-core' ),
			'choose_wilaya' => __( 'اختر الولاية أولاً', 'deco-store-core' ),
			'choose'        => __( 'اختر', 'deco-store-core' ),
			'loading'       => __( 'جارٍ التحميل…', 'deco-store-core' ),
			'no_pickup'     => __( 'لا يوجد مكتب استلام لهذه المنطقة.', 'deco-store-core' ),
			'load_failed'   => __( 'تعذّر تحميل القائمة. أعد المحاولة.', 'deco-store-core' ),
			'preview_done'  => __( 'معاينة: البيانات صحيحة ولم يُرسَل أي طلب.', 'deco-store-core' ),
		);
	}

	/**
	 * Render a form.
	 *
	 * @param array $form Normalised definition with `id`.
	 * @param array $args preview (bool): show the form without sending anything;
	 *                    before_submit (string): HTML built and escaped by the caller (order widget);
	 *                    attrs (array): extra attributes for the form element.
	 * @return string HTML (escaped).
	 */
	public static function render( array $form, array $args = array() ) {
		$args    = array_merge(
			array(
				'preview'       => true,
				'before_submit' => '',
				'attrs'         => array(),
			),
			$args
		);
		$form_id = (int) $form['id'];
		$rule    = (string) Settings::get( 'phone_rule' );
		self::enqueue();

		$attrs = array(
			'class'              => 'ds-form-front',
			'id'                 => 'ds-form-' . $form_id,
			'data-ds-form'       => '1',
			'data-form-id'       => (string) $form_id,
			'data-form-version'  => (string) ( isset( $form['version'] ) ? $form['version'] : 0 ),
			'data-rest'          => esc_url_raw( rest_url( 'ds-core/v1/' ) ),
			'data-phone-pattern' => PhoneRule::js_pattern( $rule ),
			'data-phone-prefix'  => PhoneRule::js_prefix( $rule ),
			'data-i18n'          => wp_json_encode( self::messages() ),
			'novalidate'         => 'novalidate',
		);
		if ( ! empty( $args['preview'] ) ) {
			$attrs['data-preview'] = '1';
		}
		$attrs = array_merge( $attrs, (array) $args['attrs'] );
		$html  = '<form' . self::attributes( $attrs ) . '>';
		$html .= '<div class="ds-errors" role="alert" aria-live="assertive" tabindex="-1" hidden></div>';
		$html .= '<div class="ds-fields">';
		foreach ( $form['fields'] as $field ) {
			$html .= self::render_field( $form_id, $field );
		}
		$html .= '</div>';
		$html .= $args['before_submit'];
		$label = '' !== $form['settings']['submit_label'] ? $form['settings']['submit_label'] : __( 'تأكيد الطلب', 'deco-store-core' );
		$html .= '<div class="ds-form-actions"><button type="submit" class="ds-submit">' . esc_html( $label ) . '</button></div>';
		$html .= '<p class="ds-preview-note" role="status" aria-live="polite" hidden></p>';
		$html .= '</form>';
		return $html;
	}

	/**
	 * Enqueue front-end assets (only when a form is rendered).
	 *
	 * @return void
	 */
	private static function enqueue() {
		wp_enqueue_style( 'ds-core-forms', DS_CORE_URL . 'assets/forms.css', array(), DS_CORE_VERSION );
		wp_enqueue_script(
			'ds-core-forms',
			DS_CORE_URL . 'assets/forms.js',
			array(),
			DS_CORE_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	/**
	 * Build an escaped attribute string.
	 *
	 * @param array $attrs name => value ('' / true = boolean attribute).
	 * @return string
	 */
	private static function attributes( array $attrs ) {
		$out = '';
		foreach ( $attrs as $name => $value ) {
			if ( false === $value || null === $value ) {
				continue;
			}
			$out .= ' ' . esc_attr( $name );
			if ( true !== $value ) {
				$out .= '="' . esc_attr( (string) $value ) . '"';
			}
		}
		return $out;
	}

	/**
	 * Render one field.
	 *
	 * @param int   $form_id Form id.
	 * @param array $field   Field definition.
	 * @return string
	 */
	private static function render_field( $form_id, array $field ) {
		$type = $field['type'];
		$id   = 'ds-' . $form_id . '-' . $field['key'];
		$wrap = array(
			'class'         => 'ds-field ds-field--' . $type . ' ds-field--' . $field['width'],
			'data-key'      => $field['key'],
			'data-type'     => $type,
			'data-maps-to'  => $field['maps_to'],
			'data-required' => ! empty( $field['required'] ) ? '1' : false,
			'data-min'      => null !== $field['min'] ? (string) $field['min'] : false,
			'data-max'      => null !== $field['max'] ? (string) $field['max'] : false,
		);
		if ( 'conditional' === $field['visibility']['mode'] ) {
			$wrap['data-visibility'] = wp_json_encode( $field['visibility']['rules'] );
			// Hidden until the script evaluates the rules (the server discards hidden fields anyway).
			$wrap['hidden'] = true;
		}
		$html = '<div' . self::attributes( $wrap ) . '>';

		if ( 'info' === $type ) {
			return $html . '<p class="ds-info">' . nl2br( esc_html( $field['text'] ) ) . '</p></div>';
		}

		$describedby = array();
		$help        = '' !== $field['help'] ? '<p class="ds-help" id="' . esc_attr( $id . '-help' ) . '">' . esc_html( $field['help'] ) . '</p>' : '';
		if ( '' !== $help ) {
			$describedby[] = $id . '-help';
		}
		$describedby[] = $id . '-err';
		$aria          = ' aria-describedby="' . esc_attr( implode( ' ', $describedby ) ) . '"';
		$required      = ! empty( $field['required'] );
		$req_mark      = $required ? ' <span class="ds-req" aria-hidden="true">*</span><span class="screen-reader-text"> (' . esc_html__( 'إلزامي', 'deco-store-core' ) . ')</span>' : '';
		$label         = '<label class="ds-label" for="' . esc_attr( $id ) . '">' . esc_html( $field['label'] ) . $req_mark . '</label>';
		$err           = '<p class="ds-error" id="' . esc_attr( $id . '-err' ) . '" hidden></p>';
		$req_attr      = $required ? ' required aria-required="true"' : '';
		$name          = ' name="' . esc_attr( $field['key'] ) . '"';
		$ph            = '' !== $field['placeholder'] ? ' placeholder="' . esc_attr( $field['placeholder'] ) . '"' : '';

		switch ( $type ) {
			case 'text':
				$autocomplete = 'customer_name' === $field['maps_to'] ? ' autocomplete="name"' : '';
				$maxlength    = null !== $field['max'] ? ' maxlength="' . (int) $field['max'] . '"' : '';
				$html        .= $label . '<input type="text" id="' . esc_attr( $id ) . '"' . $name . $ph . $req_attr . $aria . $autocomplete . $maxlength . '>' . $help . $err;
				break;
			case 'textarea':
				$maxlength = null !== $field['max'] ? ' maxlength="' . (int) $field['max'] . '"' : '';
				$html     .= $label . '<textarea id="' . esc_attr( $id ) . '" rows="3"' . $name . $ph . $req_attr . $aria . $maxlength . '></textarea>' . $help . $err;
				break;
			case 'phone':
				$html .= $label . '<input type="tel" inputmode="tel" dir="ltr" autocomplete="tel" id="' . esc_attr( $id ) . '"' . $name . $ph . $req_attr . $aria . '>' . $help . $err;
				break;
			case 'number':
			case 'quantity':
				$min   = null !== $field['min'] ? ' min="' . (int) $field['min'] . '"' : '';
				$max   = null !== $field['max'] ? ' max="' . (int) $field['max'] . '"' : '';
				$value = 'quantity' === $type ? ' value="' . (int) $field['min'] . '"' : '';
				$html .= $label . '<input type="number" inputmode="numeric" dir="ltr" step="1" id="' . esc_attr( $id ) . '"' . $name . $ph . $min . $max . $value . $req_attr . $aria . '>' . $help . $err;
				break;
			case 'select':
				$html .= $label . '<select id="' . esc_attr( $id ) . '"' . $name . $req_attr . $aria . '><option value="">' . esc_html__( 'اختر', 'deco-store-core' ) . '</option>';
				foreach ( $field['options'] as $option ) {
					$html .= '<option value="' . esc_attr( $option['value'] ) . '">' . esc_html( $option['label'] ) . '</option>';
				}
				$html .= '</select>' . $help . $err;
				break;
			case 'radio':
				$html = rtrim( $html ) . self::choice_group( $id, $field, $field['options'], $help, $err, $required );
				break;
			case 'delivery_method':
				$options = array();
				foreach ( DeliverySettings::enabled_methods() as $method ) {
					$options[] = array(
						'value' => $method,
						'label' => DeliverySettings::label( $method ),
					);
				}
				$html = rtrim( $html ) . self::choice_group( $id, $field, $options, $help, $err, $required );
				break;
			case 'checkbox':
			case 'terms':
				$html .= '<label class="ds-check" for="' . esc_attr( $id ) . '"><input type="checkbox" id="' . esc_attr( $id ) . '"' . $name . ' value="1"' . $req_attr . $aria . '> <span>' . esc_html( $field['label'] ) . $req_mark . '</span></label>' . $help . $err;
				break;
			case 'wilaya':
				$html .= $label . '<select id="' . esc_attr( $id ) . '"' . $name . $req_attr . $aria . ' autocomplete="address-level1"><option value="">' . esc_html__( 'اختر الولاية', 'deco-store-core' ) . '</option>';
				foreach ( GeoRepository::wilayas( true ) as $wilaya ) {
					$html .= '<option value="' . (int) $wilaya['id'] . '">' . esc_html( $wilaya['name_ar'] ) . '</option>';
				}
				$html .= '</select>' . $help . $err;
				break;
			case 'commune':
				$html .= $label . '<select id="' . esc_attr( $id ) . '"' . $name . $req_attr . $aria . ' disabled><option value="">' . esc_html__( 'اختر الولاية أولاً', 'deco-store-core' ) . '</option></select>' . $help . $err;
				break;
			case 'pickup_point':
				$html .= $label . '<select id="' . esc_attr( $id ) . '"' . $name . $req_attr . $aria . ' disabled><option value="">' . esc_html__( 'اختر الولاية أولاً', 'deco-store-core' ) . '</option></select>' . $help . $err;
				break;
		}
		return $html . '</div>';
	}

	/**
	 * Radio card group (fieldset + legend).
	 *
	 * @param string $id       Base id.
	 * @param array  $field    Field.
	 * @param array  $options  Options (value,label).
	 * @param string $help     Help HTML.
	 * @param string $err      Error HTML.
	 * @param bool   $required Required.
	 * @return string HTML that continues inside the open wrapper div.
	 */
	private static function choice_group( $id, array $field, array $options, $help, $err, $required ) {
		$mark = $required ? ' <span class="ds-req" aria-hidden="true">*</span>' : '';
		$html = '<fieldset class="ds-choices" aria-describedby="' . esc_attr( $id . '-err' ) . '"><legend class="ds-label">' . esc_html( $field['label'] ) . $mark . '</legend><div class="ds-choice-list">';
		foreach ( $options as $i => $option ) {
			$oid   = $id . '-' . ( $i + 1 );
			$html .= '<label class="ds-choice" for="' . esc_attr( $oid ) . '"><input type="radio" id="' . esc_attr( $oid ) . '" name="' . esc_attr( $field['key'] ) . '" value="' . esc_attr( $option['value'] ) . '"' . ( $required ? ' required' : '' ) . '> <span>' . esc_html( $option['label'] ) . '</span></label>';
		}
		return $html . '</div>' . $help . $err . '</fieldset>';
	}
}
