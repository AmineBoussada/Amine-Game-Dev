<?php
/**
 * Arabic texts for offers: type names, reasons, errors.
 *
 * @package TopSolution
 */

namespace TopSolution\Offers;

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
			'qty_discount' => __( 'خصم حسب الكمية', 'top-solution-core' ),
			'bundle'       => __( 'حزمة منتجات', 'top-solution-core' ),
			'free_gift'    => __( 'هدية مجانية', 'top-solution-core' ),
			'custom_rule'  => __( 'قاعدة مخصصة', 'top-solution-core' ),
		);
	}

	/**
	 * Status labels.
	 *
	 * @return array<string,string>
	 */
	public static function statuses() {
		return array(
			'draft'    => __( 'مسودة', 'top-solution-core' ),
			'active'   => __( 'مفعّل', 'top-solution-core' ),
			'archived' => __( 'مؤرشف', 'top-solution-core' ),
		);
	}

	/**
	 * Group labels.
	 *
	 * @return array<string,string>
	 */
	public static function groups() {
		return array(
			'price'    => __( 'سعر المنتج', 'top-solution-core' ),
			'gift'     => __( 'هدية', 'top-solution-core' ),
			'delivery' => __( 'التوصيل', 'top-solution-core' ),
			'order'    => __( 'خصم على الطلب', 'top-solution-core' ),
		);
	}

	/**
	 * Action labels.
	 *
	 * @return array<string,string>
	 */
	public static function actions() {
		return array(
			'percent_off'          => __( 'خصم نسبة من سعر المنتج', 'top-solution-core' ),
			'fixed_off_line'       => __( 'خصم مبلغ ثابت من سعر الوحدة', 'top-solution-core' ),
			'set_unit_price'       => __( 'تحديد سعر الوحدة', 'top-solution-core' ),
			'fixed_off_order'      => __( 'خصم مبلغ ثابت من الطلب', 'top-solution-core' ),
			'free_delivery'        => __( 'توصيل مجاني', 'top-solution-core' ),
			'delivery_percent_off' => __( 'خصم نسبة من سعر التوصيل', 'top-solution-core' ),
			'delivery_fixed_off'   => __( 'خصم مبلغ من سعر التوصيل', 'top-solution-core' ),
			'add_gift'             => __( 'إضافة هدية', 'top-solution-core' ),
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
			'not_started'                  => __( 'لم يبدأ بعد', 'top-solution-core' ),
			'ended'                        => __( 'انتهى', 'top-solution-core' ),
			'scope_mismatch'               => __( 'لا ينطبق على المنتج', 'top-solution-core' ),
			'bundle_incomplete'            => __( 'الحزمة ناقصة (منتج غير موجود أو كميته أقل)', 'top-solution-core' ),
			'below_min_qty'                => __( 'الكمية أقل من الحد الأدنى', 'top-solution-core' ),
			'below_min_subtotal'           => __( 'المجموع أقل من الحد الأدنى', 'top-solution-core' ),
			'needs_wilaya'                 => __( 'يحتاج اختيار الولاية', 'top-solution-core' ),
			'wilaya_not_included'          => __( 'الولاية غير مشمولة', 'top-solution-core' ),
			'delivery_method_not_included' => __( 'طريقة التوصيل غير مشمولة', 'top-solution-core' ),
			'weekday_not_included'         => __( 'اليوم غير مشمول', 'top-solution-core' ),
			'gift_out_of_stock'            => __( 'الهدية غير متوفرة في المخزون', 'top-solution-core' ),
			'not_a_discount'               => __( 'لا يخفّض السعر', 'top-solution-core' ),
			'not_chosen'                   => __( 'عرض للاختيار لم يخترْه العميل', 'top-solution-core' ),
			'exclusive_won'                => __( 'تغلّب عليه عرض حصري', 'top-solution-core' ),
			'outranked'                    => __( 'تغلّب عليه عرض أعلى أولوية أو أفضل', 'top-solution-core' ),
			'gift_limit'                   => __( 'تجاوز الحد الأقصى للهدايا', 'top-solution-core' ),
			'no_delivery_fee'              => __( 'لا يوجد سعر توصيل (لم تُختر الولاية أو الطريقة)', 'top-solution-core' ),
			'chosen_offer_invalid'         => __( 'العرض المختار غير صالح لهذا الطلب', 'top-solution-core' ),
			'bad_lines'                    => __( 'بيانات الطلب غير صالحة', 'top-solution-core' ),
			'bad_quantity'                 => __( 'الكمية غير صالحة', 'top-solution-core' ),
			'bad_price'                    => __( 'سعر المنتج غير صالح', 'top-solution-core' ),
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
			'label_required'         => __( 'اسم العرض مطلوب', 'top-solution-core' ),
			'bad_type'               => __( 'نوع العرض غير معروف', 'top-solution-core' ),
			'bad_priority'           => __( 'الأولوية رقم من 0 إلى 1000', 'top-solution-core' ),
			'bad_date'               => __( 'تاريخ غير صالح', 'top-solution-core' ),
			'end_before_start'       => __( 'تاريخ النهاية يجب أن يكون بعد البداية', 'top-solution-core' ),
			'scope_empty'            => __( 'اختر منتجاً أو تصنيفاً واحداً على الأقل أو اجعل العرض لكل المنتجات', 'top-solution-core' ),
			'bad_condition'          => __( 'شرط غير صالح (أرقام موجبة فقط)', 'top-solution-core' ),
			'bad_tier'               => __( 'سطر شريحة غير صالح. الصيغة: الكمية|percent أو fixed أو price|القيمة', 'top-solution-core' ),
			'duplicate_tier'         => __( 'شريحتان بنفس الكمية', 'top-solution-core' ),
			'tiers_required'         => __( 'أضف شريحة واحدة على الأقل', 'top-solution-core' ),
			'bad_item'               => __( 'سطر منتج في الحزمة غير صالح. الصيغة: رقم المنتج|الكمية|رقم النسخة', 'top-solution-core' ),
			'duplicate_item'         => __( 'منتج مكرر في الحزمة', 'top-solution-core' ),
			'bundle_needs_two_items' => __( 'الحزمة تحتاج منتجين على الأقل', 'top-solution-core' ),
			'bad_bundle_price'       => __( 'سعر الحزمة أو نسبتها غير صالحة', 'top-solution-core' ),
			'bad_gift'               => __( 'منتج الهدية غير صالح', 'top-solution-core' ),
			'bad_action'             => __( 'إجراء غير معروف', 'top-solution-core' ),
			'bad_action_value'       => __( 'قيمة الإجراء غير صالحة', 'top-solution-core' ),
		);
		return isset( $map[ $error['code'] ] ) ? $map[ $error['code'] ] : $error['code'];
	}
}
