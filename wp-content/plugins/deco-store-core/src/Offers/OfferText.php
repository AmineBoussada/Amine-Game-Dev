<?php
/**
 * Arabic texts for offers: type names, reasons, errors.
 *
 * @package DecoStore
 */

namespace DecoStore\Offers;

defined( 'ABSPATH' ) || exit;

/**
 * Labels.
 */
class OfferText {

	/**
	 * Type labels.
	 *
	 * @return array<string,string>
	 */
	public static function types() {
		return array(
			'qty_discount' => __( 'خصم حسب الكمية', 'deco-store-core' ),
			'bundle'       => __( 'حزمة منتجات', 'deco-store-core' ),
			'free_gift'    => __( 'هدية مجانية', 'deco-store-core' ),
			'custom_rule'  => __( 'قاعدة مخصصة', 'deco-store-core' ),
		);
	}

	/**
	 * Status labels.
	 *
	 * @return array<string,string>
	 */
	public static function statuses() {
		return array(
			'draft'    => __( 'مسودة', 'deco-store-core' ),
			'active'   => __( 'مفعّل', 'deco-store-core' ),
			'archived' => __( 'مؤرشف', 'deco-store-core' ),
		);
	}

	/**
	 * Group labels.
	 *
	 * @return array<string,string>
	 */
	public static function groups() {
		return array(
			'price'    => __( 'سعر المنتج', 'deco-store-core' ),
			'gift'     => __( 'هدية', 'deco-store-core' ),
			'delivery' => __( 'التوصيل', 'deco-store-core' ),
			'order'    => __( 'خصم على الطلب', 'deco-store-core' ),
		);
	}

	/**
	 * Action labels.
	 *
	 * @return array<string,string>
	 */
	public static function actions() {
		return array(
			'percent_off'          => __( 'خصم نسبة من سعر المنتج', 'deco-store-core' ),
			'fixed_off_line'       => __( 'خصم مبلغ ثابت من سعر الوحدة', 'deco-store-core' ),
			'set_unit_price'       => __( 'تحديد سعر الوحدة', 'deco-store-core' ),
			'fixed_off_order'      => __( 'خصم مبلغ ثابت من الطلب', 'deco-store-core' ),
			'free_delivery'        => __( 'توصيل مجاني', 'deco-store-core' ),
			'delivery_percent_off' => __( 'خصم نسبة من سعر التوصيل', 'deco-store-core' ),
			'delivery_fixed_off'   => __( 'خصم مبلغ من سعر التوصيل', 'deco-store-core' ),
			'add_gift'             => __( 'إضافة هدية', 'deco-store-core' ),
		);
	}

	/**
	 * Reason an offer did not apply (simulator, diagnostics).
	 *
	 * @param string $code Reason code.
	 * @return string
	 */
	public static function reason( $code ) {
		$map = array(
			'not_started'                  => __( 'لم يبدأ بعد', 'deco-store-core' ),
			'ended'                        => __( 'انتهى', 'deco-store-core' ),
			'scope_mismatch'               => __( 'لا ينطبق على المنتج', 'deco-store-core' ),
			'bundle_incomplete'            => __( 'الحزمة ناقصة (منتج غير موجود أو كميته أقل)', 'deco-store-core' ),
			'below_min_qty'                => __( 'الكمية أقل من الحد الأدنى', 'deco-store-core' ),
			'below_min_subtotal'           => __( 'المجموع أقل من الحد الأدنى', 'deco-store-core' ),
			'needs_wilaya'                 => __( 'يحتاج اختيار الولاية', 'deco-store-core' ),
			'wilaya_not_included'          => __( 'الولاية غير مشمولة', 'deco-store-core' ),
			'delivery_method_not_included' => __( 'طريقة التوصيل غير مشمولة', 'deco-store-core' ),
			'weekday_not_included'         => __( 'اليوم غير مشمول', 'deco-store-core' ),
			'gift_out_of_stock'            => __( 'الهدية غير متوفرة في المخزون', 'deco-store-core' ),
			'not_a_discount'               => __( 'لا يخفّض السعر', 'deco-store-core' ),
			'not_chosen'                   => __( 'عرض للاختيار لم يخترْه العميل', 'deco-store-core' ),
			'exclusive_won'                => __( 'تغلّب عليه عرض حصري', 'deco-store-core' ),
			'outranked'                    => __( 'تغلّب عليه عرض أعلى أولوية أو أفضل', 'deco-store-core' ),
			'gift_limit'                   => __( 'تجاوز الحد الأقصى للهدايا', 'deco-store-core' ),
			'no_delivery_fee'              => __( 'لا يوجد سعر توصيل (لم تُختر الولاية أو الطريقة)', 'deco-store-core' ),
			'chosen_offer_invalid'         => __( 'العرض المختار غير صالح لهذا الطلب', 'deco-store-core' ),
			'bad_lines'                    => __( 'بيانات الطلب غير صالحة', 'deco-store-core' ),
			'bad_quantity'                 => __( 'الكمية غير صالحة', 'deco-store-core' ),
			'bad_price'                    => __( 'سعر المنتج غير صالح', 'deco-store-core' ),
		);
		return isset( $map[ $code ] ) ? $map[ $code ] : $code;
	}

	/**
	 * Arabic text of a schema error.
	 *
	 * @param array $error code + field.
	 * @return string
	 */
	public static function error( array $error ) {
		$map = array(
			'label_required'         => __( 'اسم العرض مطلوب', 'deco-store-core' ),
			'bad_type'               => __( 'نوع العرض غير معروف', 'deco-store-core' ),
			'bad_priority'           => __( 'الأولوية رقم من 0 إلى 1000', 'deco-store-core' ),
			'bad_date'               => __( 'تاريخ غير صالح', 'deco-store-core' ),
			'end_before_start'       => __( 'تاريخ النهاية يجب أن يكون بعد البداية', 'deco-store-core' ),
			'scope_empty'            => __( 'اختر منتجاً أو تصنيفاً واحداً على الأقل أو اجعل العرض لكل المنتجات', 'deco-store-core' ),
			'bad_condition'          => __( 'شرط غير صالح (أرقام موجبة فقط)', 'deco-store-core' ),
			'bad_tier'               => __( 'سطر شريحة غير صالح. الصيغة: الكمية|percent أو fixed أو price|القيمة', 'deco-store-core' ),
			'duplicate_tier'         => __( 'شريحتان بنفس الكمية', 'deco-store-core' ),
			'tiers_required'         => __( 'أضف شريحة واحدة على الأقل', 'deco-store-core' ),
			'bad_item'               => __( 'سطر منتج في الحزمة غير صالح. الصيغة: رقم المنتج|الكمية|رقم النسخة', 'deco-store-core' ),
			'duplicate_item'         => __( 'منتج مكرر في الحزمة', 'deco-store-core' ),
			'bundle_needs_two_items' => __( 'الحزمة تحتاج منتجين على الأقل', 'deco-store-core' ),
			'bad_bundle_price'       => __( 'سعر الحزمة أو نسبتها غير صالحة', 'deco-store-core' ),
			'bad_gift'               => __( 'منتج الهدية غير صالح', 'deco-store-core' ),
			'bad_action'             => __( 'إجراء غير معروف', 'deco-store-core' ),
			'bad_action_value'       => __( 'قيمة الإجراء غير صالحة', 'deco-store-core' ),
		);
		return isset( $map[ $error['code'] ] ) ? $map[ $error['code'] ] : $error['code'];
	}
}
