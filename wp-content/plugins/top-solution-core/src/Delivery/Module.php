<?php
/**
 * Delivery module wiring.
 *
 * @package TopSolution
 */

namespace TopSolution\Delivery;

use TopSolution\Admin\DeliveryPage;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the shipping method and the admin screen.
 */
class Module {

	/**
	 * Hook everything.
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'woocommerce_shipping_methods', array( __CLASS__, 'register_shipping_method' ) );
		if ( is_admin() ) {
			add_action( 'ts_core_admin_menu', array( DeliveryPage::class, 'register_menu' ) );
		}
	}

	/**
	 * Add the shipping method to WooCommerce.
	 *
	 * @param array $methods Methods.
	 * @return array
	 */
	public static function register_shipping_method( $methods ) {
		$methods[ ShippingMethod::ID ] = ShippingMethod::class;
		return $methods;
	}
}
