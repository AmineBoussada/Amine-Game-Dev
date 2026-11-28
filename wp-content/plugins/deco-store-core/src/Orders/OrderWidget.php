<?php
/**
 * The order form for one product: variation choice, quantity, offer cards
 * the customer may pick, the form itself, a live price summary and the
 * submit button. Used by the [ds_order_form] shortcode now and by the
 * landing-page templates in Phase 6.
 *
 * Prices shown here are previews from the server (quote endpoint). The
 * order total is always recomputed when the order is submitted.
 *
 * @package DecoStore
 */

namespace DecoStore\Orders;

use DecoStore\Forms\FormRenderer;
use DecoStore\Forms\FormRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Order widget.
 */
class OrderWidget {

	/**
	 * Texts used by the script.
	 *
	 * @return array<string,string>
	 */
	public static function messages() {
		return array(
			'unit'             => __( 'السعر', 'deco-store-core' ),
			'subtotal'         => __( 'المجموع', 'deco-store-core' ),
			'discount'         => __( 'التوفير', 'deco-store-core' ),
			'delivery'         => __( 'التوصيل', 'deco-store-core' ),
			'delivery_unknown' => __( 'اختر الولاية وطريقة التوصيل', 'deco-store-core' ),
			'delivery_none'    => __( 'غير متاح لهذه المنطقة بهذه الطريقة', 'deco-store-core' ),
			'free'             => __( 'مجاني', 'deco-store-core' ),
			'gift'             => __( 'هدية', 'deco-store-core' ),
			'total'            => __( 'الإجمالي عند الاستلام', 'deco-store-core' ),
			'choose_variation' => __( 'اختر النوع أولاً.', 'deco-store-core' ),
			'sending'          => __( 'جارٍ إرسال الطلب…', 'deco-store-core' ),
			'price_changed'    => __( 'تغيّر المبلغ الإجمالي. راجع المبلغ الجديد ثم اضغط مرة أخرى للتأكيد.', 'deco-store-core' ),
			'offer_changed'    => __( 'العرض الذي اخترته لم يعد متاحاً. راجع المبلغ ثم اضغط مرة أخرى.', 'deco-store-core' ),
			'out_of_stock'     => __( 'الكمية المطلوبة غير متوفرة حالياً.', 'deco-store-core' ),
			'expired'          => __( 'انتهت صلاحية الصفحة. حدّثنا البيانات، اضغط مرة أخرى للتأكيد.', 'deco-store-core' ),
			'too_many'         => __( 'محاولات كثيرة. انتظر بضع دقائق ثم أعد المحاولة.', 'deco-store-core' ),
			'unavailable'      => __( 'هذا المنتج غير متاح للطلب حالياً.', 'deco-store-core' ),
			'network'          => __( 'تعذّر الاتصال. تحقق من الإنترنت ثم اضغط مرة أخرى (لن يُسجَّل الطلب مرتين).', 'deco-store-core' ),
			'server'           => __( 'حدث خطأ أثناء تسجيل الطلب. أعد المحاولة. رقم المرجع:', 'deco-store-core' ),
			'rejected'         => __( 'تعذّر قبول الطلب. أعد تحميل الصفحة ثم حاول مرة أخرى.', 'deco-store-core' ),
		);
	}

	/**
	 * Render.
	 *
	 * @param int $product_id Product id.
	 * @param int $form_id    Form id (0 = default form).
	 * @return string
	 */
	public static function render( $product_id, $form_id = 0 ) {
		$product = wc_get_product( (int) $product_id );
		$form    = FormRepository::get( $form_id ? (int) $form_id : FormRepository::default_id() );
		if ( ! $product || ! $form || $product->is_type( 'variation' ) ) {
			return '';
		}
		if ( ! Checkout::sellable( $product ) || ! ( $product->is_type( 'simple' ) || $product->is_type( 'variable' ) ) ) {
			return '<div class="ds-order ds-order--unavailable"><p>' . esc_html( self::messages()['unavailable'] ) . '</p></div>';
		}
		wp_enqueue_script(
			'ds-core-order',
			DS_CORE_URL . 'assets/order.js',
			array( 'ds-core-forms' ),
			DS_CORE_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		$has_qty_field = false;
		foreach ( $form['fields'] as $field ) {
			if ( 'quantity' === $field['maps_to'] ) {
				$has_qty_field = true;
			}
		}
		$uid = 'ds-order-' . $product->get_id() . '-' . $form['id'];
		$top = '';
		if ( $product->is_type( 'variable' ) ) {
			$top .= '<div class="ds-field ds-order__variation"><label for="' . esc_attr( $uid ) . '-variation">' . esc_html__( 'النوع', 'deco-store-core' ) . ' <span class="ds-req" aria-hidden="true">*</span></label>';
			$top .= '<select id="' . esc_attr( $uid ) . '-variation" name="ds_variation" required><option value="">' . esc_html__( 'اختر', 'deco-store-core' ) . '</option>';
			foreach ( $product->get_children() as $child_id ) {
				$v = wc_get_product( $child_id );
				if ( ! $v || ! $v->is_purchasable() || ! $v->is_in_stock() || '' === $v->get_price() ) {
					continue;
				}
				$top .= '<option value="' . esc_attr( (string) $v->get_id() ) . '">' . esc_html( wp_strip_all_tags( wc_get_formatted_variation( $v, true, false, false ) ) . ' — ' . html_entity_decode( wp_strip_all_tags( wc_price( (float) $v->get_price() ) ), ENT_QUOTES, 'UTF-8' ) ) . '</option>';
			}
			$top .= '</select></div>';
		}
		if ( ! $has_qty_field ) {
			$max  = $product->managing_stock() && ! $product->backorders_allowed() && null !== $product->get_stock_quantity() ? max( 1, min( Checkout::MAX_QTY, (int) $product->get_stock_quantity() ) ) : Checkout::MAX_QTY;
			$top .= '<div class="ds-field ds-order__qty"><label for="' . esc_attr( $uid ) . '-qty">' . esc_html__( 'الكمية', 'deco-store-core' ) . '</label>';
			$top .= '<input type="number" id="' . esc_attr( $uid ) . '-qty" name="ds_qty" value="1" min="1" max="' . esc_attr( (string) $max ) . '" step="1" inputmode="numeric"></div>';
		}
		$offers = Checkout::selectable_offers( $product );
		if ( $offers ) {
			$top .= '<fieldset class="ds-order__offers"><legend>' . esc_html__( 'اختر العرض', 'deco-store-core' ) . '</legend>';
			$top .= '<label class="ds-choice"><input type="radio" name="ds_offer" value="0" checked> ' . esc_html__( 'بدون عرض', 'deco-store-core' ) . '</label>';
			foreach ( $offers as $o ) {
				$top .= '<label class="ds-choice"><input type="radio" name="ds_offer" value="' . esc_attr( (string) $o['id'] ) . '"> ' . esc_html( $o['label'] ) . '</label>';
			}
			$top .= '</fieldset>';
		}
		$summary  = '<div class="ds-order__summary" aria-live="polite" aria-atomic="true"><p class="ds-order__summary-empty">' . esc_html( self::price_line( $product ) ) . '</p></div>';
		$summary .= '<div class="ds-order__message" role="alert" hidden></div>';
		$summary .= '<input type="text" name="ds_hp" class="ds-hp" tabindex="-1" autocomplete="off" aria-hidden="true">';
		$summary .= '<noscript><p class="ds-order__message">' . esc_html__( 'يتطلب الطلب تفعيل JavaScript في المتصفح.', 'deco-store-core' ) . '</p></noscript>';

		$html  = '<div class="ds-order" id="' . esc_attr( $uid ) . '">';
		$html .= FormRenderer::render(
			$form,
			array(
				'preview'       => false,
				'before_submit' => '<div class="ds-order__top">' . $top . '</div>' . $summary,
				'attrs'         => array(
					'data-ds-order'   => '1',
					'data-product'    => (string) $product->get_id(),
					'data-order-i18n' => wp_json_encode( self::messages() ),
				),
			)
		);
		$html .= '</div>';
		return $html;
	}

	/**
	 * Price text shown before any choice.
	 *
	 * @param \WC_Product $product Product.
	 * @return string
	 */
	private static function price_line( $product ) {
		$price = $product->is_type( 'variable' ) ? $product->get_variation_price( 'min' ) : $product->get_price();
		$text  = html_entity_decode( wp_strip_all_tags( wc_price( (float) $price ) ), ENT_QUOTES, 'UTF-8' );
		return ( $product->is_type( 'variable' ) ? __( 'ابتداءً من', 'deco-store-core' ) . ' ' : __( 'السعر', 'deco-store-core' ) . ': ' ) . $text;
	}
}
