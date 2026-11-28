<?php
/**
 * System status checks shown on the dashboard (read-only, no side effects).
 *
 * @package TopSolution
 */

namespace TopSolution\Admin;

use TopSolution\Core\Requirements;

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
		$label    = __( 'الإصدارات', 'top-solution-core' );
		/* translators: 1: PHP version, 2: WordPress version, 3: WooCommerce version. */
		$detail = sprintf( __( 'PHP %1$s · ووردبريس %2$s · ووكومرس %3$s', 'top-solution-core' ), $env['php'], $env['wp'], $env['wc'] ? $env['wc'] : '—' );
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
		$label = __( 'تخزين الطلبات (HPOS)', 'top-solution-core' );
		if ( ! class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) || ! class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) ) {
			return self::row( 'hpos', $label, self::WARN, __( 'تعذّر قراءة حالة HPOS من ووكومرس.', 'top-solution-core' ) );
		}
		$util       = '\Automattic\WooCommerce\Utilities\FeaturesUtil';
		$enabled    = \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
		$plugins    = method_exists( $util, 'get_compatible_plugins_for_feature' ) ? $util::get_compatible_plugins_for_feature( 'custom_order_tables' ) : array();
		$compatible = isset( $plugins['compatible'] ) && in_array( plugin_basename( TS_CORE_FILE ), $plugins['compatible'], true );
		$mode       = $enabled ? __( 'مفعّل', 'top-solution-core' ) : __( 'غير مفعّل (التخزين التقليدي)', 'top-solution-core' );
		if ( $compatible ) {
			/* translators: %s: HPOS state. */
			return self::row( 'hpos', $label, self::OK, sprintf( __( 'الإضافة متوافقة مع HPOS · الحالة: %s', 'top-solution-core' ), $mode ) );
		}
		return self::row( 'hpos', $label, self::FAIL, __( 'الإضافة غير معلنة كمتوافقة مع HPOS.', 'top-solution-core' ) );
	}

	/**
	 * Cash on delivery gateway.
	 *
	 * @return array
	 */
	private static function check_cod() {
		$label = __( 'الدفع عند الاستلام', 'top-solution-core' );
		if ( ! function_exists( 'WC' ) || ! WC()->payment_gateways() ) {
			return self::row( 'cod', $label, self::WARN, __( 'تعذّر قراءة بوابات الدفع.', 'top-solution-core' ) );
		}
		$gateways = WC()->payment_gateways()->payment_gateways();
		if ( isset( $gateways['cod'] ) && 'yes' === $gateways['cod']->enabled ) {
			return self::row( 'cod', $label, self::OK, __( 'بوابة الدفع عند الاستلام مفعّلة.', 'top-solution-core' ) );
		}
		return self::row( 'cod', $label, self::WARN, __( 'فعّل «الدفع عند الاستلام» من ووكومرس ← الإعدادات ← الدفع. الطلبات تستخدمه.', 'top-solution-core' ) );
	}

	/**
	 * Store currency (informational; the owner decides).
	 *
	 * @return array
	 */
	private static function check_currency() {
		$label    = __( 'العملة', 'top-solution-core' );
		$currency = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : '';
		if ( 'DZD' === $currency ) {
			return self::row( 'currency', $label, self::OK, 'DZD' );
		}
		/* translators: %s: currency code. */
		return self::row( 'currency', $label, self::WARN, sprintf( __( 'عملة المتجر الحالية %s. المتجر جزائري، تحقق من أنها مقصودة.', 'top-solution-core' ), $currency ? $currency : '—' ) );
	}

	/**
	 * Active theme.
	 *
	 * @return array
	 */
	private static function check_theme() {
		$label = __( 'القالب', 'top-solution-core' );
		if ( 'top-solution-child' === get_stylesheet() ) {
			return self::row( 'theme', $label, self::OK, __( 'قالب Top Solution Child مفعّل.', 'top-solution-core' ) );
		}
		return self::row( 'theme', $label, self::WARN, __( 'فعّل قالب Top Solution Child (قالب فرعي من Hello Elementor).', 'top-solution-core' ) );
	}

	/**
	 * RTL / Arabic locale.
	 *
	 * @return array
	 */
	private static function check_rtl() {
		$label = __( 'اللغة والاتجاه', 'top-solution-core' );
		if ( is_rtl() ) {
			return self::row( 'rtl', $label, self::OK, get_locale() . ' · RTL' );
		}
		return self::row( 'rtl', $label, self::WARN, __( 'اللغة الحالية ليست RTL. اضبط لغة الموقع على العربية من الإعدادات ← عام.', 'top-solution-core' ) );
	}

	/**
	 * Pretty permalinks.
	 *
	 * @return array
	 */
	private static function check_permalinks() {
		$label = __( 'الروابط الدائمة', 'top-solution-core' );
		if ( '' !== (string) get_option( 'permalink_structure' ) ) {
			return self::row( 'permalinks', $label, self::OK, __( 'روابط مقروءة مفعّلة.', 'top-solution-core' ) );
		}
		return self::row( 'permalinks', $label, self::WARN, __( 'اختر «اسم المقالة» من الإعدادات ← الروابط الدائمة.', 'top-solution-core' ) );
	}

	/**
	 * Elementor is optional. The plugin does not use it at all, and nothing in
	 * the project ever needs a paid Elementor tier.
	 *
	 * @return array
	 */
	private static function check_elementor() {
		$label   = __( 'Elementor', 'top-solution-core' );
		$active  = did_action( 'elementor/loaded' ) > 0;
		$message = $active
			? __( 'Elementor المجاني مفعّل (للمحتوى والصفحات العامة فقط).', 'top-solution-core' )
			: __( 'Elementor غير مفعّل. المتجر يعمل بالكامل بدونه.', 'top-solution-core' );
		return self::row( 'elementor', $label, self::OK, $message );
	}
}
