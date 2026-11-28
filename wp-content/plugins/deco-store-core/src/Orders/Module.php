<?php
/**
 * Orders module wiring.
 *
 * @package DecoStore
 */

namespace DecoStore\Orders;

use DecoStore\Admin\OrderAdmin;
use DecoStore\Rest\OrderController;

defined( 'ABSPATH' ) || exit;

/**
 * Routes, confirmation page, shortcode, admin and daily cleanup.
 */
class Module {

	const CRON = 'ds_core_daily';

	/**
	 * Hook everything.
	 *
	 * @return void
	 */
	public static function init() {
		OrderController::init();
		Confirmation::init();
		RestPrivacy::init();
		add_shortcode( 'ds_order_form', array( __CLASS__, 'shortcode' ) );
		add_action( self::CRON, array( __CLASS__, 'daily' ) );
		add_action( 'init', array( __CLASS__, 'schedule' ) );
		if ( is_admin() ) {
			OrderAdmin::init();
		}
	}

	/**
	 * Schedule the daily cleanup once.
	 *
	 * @return void
	 */
	public static function schedule() {
		if ( ! wp_next_scheduled( self::CRON ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON );
		}
	}

	/**
	 * Remove the schedule (deactivation and uninstall).
	 *
	 * @return void
	 */
	public static function unschedule() {
		wp_clear_scheduled_hook( self::CRON );
	}

	/**
	 * Daily cleanup.
	 *
	 * @return void
	 */
	public static function daily() {
		Idempotency::cleanup( time() );
	}

	/**
	 * [ds_order_form product="123" form="45"] (form optional: default form).
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function shortcode( $atts ) {
		$atts    = shortcode_atts(
			array(
				'product' => 0,
				'form'    => 0,
			),
			$atts,
			'ds_order_form'
		);
		$product = (int) $atts['product'];
		if ( ! $product && is_singular( 'product' ) ) {
			$product = (int) get_the_ID();
		}
		return $product ? OrderWidget::render( $product, (int) $atts['form'] ) : '';
	}
}
