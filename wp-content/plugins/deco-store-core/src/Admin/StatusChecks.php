<?php
/**
 * System status checks shown on the dashboard (read-only, no side effects).
 *
 * @package DecoStore
 */

namespace DecoStore\Admin;

use DecoStore\Core\Requirements;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the list of checks.
 */
class StatusChecks {

	const OK   = 'ok';
	const WARN = 'warn';
	const FAIL = 'fail';

	/**
	 * Run every check.
	 *
	 * @return array<int,array{id:string,label:string,status:string,message:string}>
	 */
	public static function run() {
		$checks = array(
			self::check_versions(),
			self::check_hpos(),
			self::check_cod(),
			self::check_currency(),
			self::check_theme(),
			self::check_rtl(),
			self::check_permalinks(),
			self::check_delivery(),
			self::check_elementor(),
		);
		return array_values( array_filter( $checks ) );
	}

	/**
	 * Build one result row.
	 *
	 * @param string $id      Check id.
	 * @param string $label   Label.
	 * @param string $status  ok | warn | fail.
	 * @param string $message Details.
	 * @return array
	 */
	private static function row( $id, $label, $status, $message ) {
		return array(
			'id'      => $id,
			'label'   => $label,
			'status'  => $status,
			'message' => $message,
		);
	}

	/**
	 * PHP, WordPress and WooCommerce versions.
	 *
	 * @return array
	 */
	private static function check_versions() {
		$env      = Requirements::environment();
		$problems = Requirements::evaluate( $env );
		$label    = __( 'الإصدارات', 'deco-store-core' );
		/* translators: 1: PHP version, 2: WordPress version, 3: WooCommerce version. */
		$detail = sprintf( __( 'PHP %1$s · ووردبريس %2$s · ووكومرس %3$s', 'deco-store-core' ), $env['php'], $env['wp'], $env['wc'] ? $env['wc'] : '—' );
		if ( empty( $problems ) ) {
			return self::row( 'versions', $label, self::OK, $detail );
		}
		$messages = array_map( array( Requirements::class, 'message' ), $problems );
		return self::row( 'versions', $label, self::FAIL, $detail . ' — ' . implode( ' ', $messages ) );
	}

	/**
	 * High-Performance Order Storage compatibility.
	 *
	 * @return array|null
	 */
	private static function check_hpos() {
		$label = __( 'تخزين الطلبات (HPOS)', 'deco-store-core' );
		if ( ! class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) || ! class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) ) {
			return self::row( 'hpos', $label, self::WARN, __( 'تعذّر قراءة حالة HPOS من ووكومرس.', 'deco-store-core' ) );
		}
		$util       = '\Automattic\WooCommerce\Utilities\FeaturesUtil';
		$enabled    = \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
		$plugins    = method_exists( $util, 'get_compatible_plugins_for_feature' ) ? $util::get_compatible_plugins_for_feature( 'custom_order_tables' ) : array();
		$compatible = isset( $plugins['compatible'] ) && in_array( plugin_basename( DS_CORE_FILE ), $plugins['compatible'], true );
		$mode       = $enabled ? __( 'مفعّل', 'deco-store-core' ) : __( 'غير مفعّل (التخزين التقليدي)', 'deco-store-core' );
		if ( $compatible ) {
			/* translators: %s: HPOS state. */
			return self::row( 'hpos', $label, self::OK, sprintf( __( 'الإضافة متوافقة مع HPOS · الحالة: %s', 'deco-store-core' ), $mode ) );
		}
		return self::row( 'hpos', $label, self::FAIL, __( 'الإضافة غير معلنة كمتوافقة مع HPOS.', 'deco-store-core' ) );
	}

	/**
	 * Cash on delivery gateway.
	 *
	 * @return array
	 */
	private static function check_cod() {
		$label = __( 'الدفع عند الاستلام', 'deco-store-core' );
		if ( ! function_exists( 'WC' ) || ! WC()->payment_gateways() ) {
			return self::row( 'cod', $label, self::WARN, __( 'تعذّر قراءة بوابات الدفع.', 'deco-store-core' ) );
		}
		$gateways = WC()->payment_gateways()->payment_gateways();
		if ( isset( $gateways['cod'] ) && 'yes' === $gateways['cod']->enabled ) {
			return self::row( 'cod', $label, self::OK, __( 'بوابة الدفع عند الاستلام مفعّلة.', 'deco-store-core' ) );
		}
		return self::row( 'cod', $label, self::WARN, __( 'فعّل «الدفع عند الاستلام» من ووكومرس ← الإعدادات ← الدفع. الطلبات تستخدمه.', 'deco-store-core' ) );
	}

	/**
	 * Store currency (informational; the owner decides).
	 *
	 * @return array
	 */
	private static function check_currency() {
		$label    = __( 'العملة', 'deco-store-core' );
		$currency = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : '';
		if ( 'DZD' === $currency ) {
			return self::row( 'currency', $label, self::OK, 'DZD' );
		}
		/* translators: %s: currency code. */
		return self::row( 'currency', $label, self::WARN, sprintf( __( 'عملة المتجر الحالية %s. المتجر جزائري، تحقق من أنها مقصودة.', 'deco-store-core' ), $currency ? $currency : '—' ) );
	}

	/**
	 * Active theme.
	 *
	 * @return array
	 */
	private static function check_theme() {
		$label = __( 'القالب', 'deco-store-core' );
		if ( 'deco-store-child' === get_stylesheet() ) {
			return self::row( 'theme', $label, self::OK, __( 'قالب Déco Store Child مفعّل.', 'deco-store-core' ) );
		}
		return self::row( 'theme', $label, self::WARN, __( 'فعّل قالب Déco Store Child (قالب فرعي من Hello Elementor).', 'deco-store-core' ) );
	}

	/**
	 * RTL / Arabic locale.
	 *
	 * @return array
	 */
	private static function check_rtl() {
		$label = __( 'اللغة والاتجاه', 'deco-store-core' );
		if ( is_rtl() ) {
			return self::row( 'rtl', $label, self::OK, get_locale() . ' · RTL' );
		}
		return self::row( 'rtl', $label, self::WARN, __( 'اللغة الحالية ليست RTL. اضبط لغة الموقع على العربية من الإعدادات ← عام.', 'deco-store-core' ) );
	}

	/**
	 * Pretty permalinks.
	 *
	 * @return array
	 */
	private static function check_permalinks() {
		$label = __( 'الروابط الدائمة', 'deco-store-core' );
		if ( '' !== (string) get_option( 'permalink_structure' ) ) {
			return self::row( 'permalinks', $label, self::OK, __( 'روابط مقروءة مفعّلة.', 'deco-store-core' ) );
		}
		return self::row( 'permalinks', $label, self::WARN, __( 'اختر «اسم المقالة» من الإعدادات ← الروابط الدائمة.', 'deco-store-core' ) );
	}

	/**
	 * Delivery data: geography loaded, prices present, WooCommerce zone set up.
	 *
	 * @return array
	 */
	private static function check_delivery() {
		$label = __( 'التوصيل', 'deco-store-core' );
		if ( ! \DecoStore\Core\Schema::tables_exist() ) {
			return self::row( 'delivery', $label, self::FAIL, __( 'جداول التوصيل غير موجودة. أعد تفعيل الإضافة.', 'deco-store-core' ) );
		}
		$wilayas = \DecoStore\Delivery\GeoRepository::count( 'wilaya' );
		if ( 0 === $wilayas ) {
			return self::row( 'delivery', $label, self::WARN, __( 'لم تُستورد الولايات بعد (Déco Store ← التوصيل ← الولايات والبلديات).', 'deco-store-core' ) );
		}
		$matrix = \DecoStore\Delivery\RateRepository::matrix( \DecoStore\Delivery\DeliveryService::decimals() );
		if ( empty( $matrix ) ) {
			return self::row( 'delivery', $label, self::WARN, __( 'لا توجد أسعار توصيل بعد (Déco Store ← التوصيل ← الأسعار).', 'deco-store-core' ) );
		}
		if ( ! \DecoStore\Delivery\ZoneSetup::existing_zone_id() ) {
			return self::row( 'delivery', $label, self::WARN, __( 'أنشئ منطقة الشحن من Déco Store ← التوصيل ← الإعدادات.', 'deco-store-core' ) );
		}
		/* translators: 1: wilayas, 2: communes. */
		return self::row( 'delivery', $label, self::OK, sprintf( __( '%1$d ولاية · %2$d بلدية · الأسعار ومنطقة الشحن جاهزة', 'deco-store-core' ), $wilayas, \DecoStore\Delivery\GeoRepository::count( 'commune' ) ) );
	}

	/**
	 * Elementor is optional. The plugin does not use it at all, and nothing in
	 * the project ever needs a paid Elementor tier.
	 *
	 * @return array
	 */
	private static function check_elementor() {
		$label   = __( 'Elementor', 'deco-store-core' );
		$active  = did_action( 'elementor/loaded' ) > 0;
		$message = $active
			? __( 'Elementor المجاني مفعّل (للمحتوى والصفحات العامة فقط).', 'deco-store-core' )
			: __( 'Elementor غير مفعّل. المتجر يعمل بالكامل بدونه.', 'deco-store-core' );
		return self::row( 'elementor', $label, self::OK, $message );
	}
}
