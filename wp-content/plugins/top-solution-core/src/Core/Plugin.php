<?php
/**
 * Plugin bootstrap.
 *
 * @package TopSolution
 */

namespace TopSolution\Core;

use TopSolution\Admin\Menu;

defined( 'ABSPATH' ) || exit;

/**
 * Boots the plugin when the environment allows it.
 */
class Plugin {

	/**
	 * Problems found at boot (codes from Requirements::evaluate()).
	 *
	 * @var string[]
	 */
	private static $problems = array();

	/**
	 * Declare compatibility with WooCommerce features.
	 *
	 * HPOS: orders are only ever read and written through WooCommerce CRUD
	 * objects (WC_Order), never through wp_posts / postmeta queries.
	 *
	 * @return void
	 */
	public static function declare_woocommerce_compatibility() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', TS_CORE_FILE, true );
		}
	}

	/**
	 * Check requirements and start the modules.
	 *
	 * @return void
	 */
	public static function boot() {
		add_action( 'init', array( __CLASS__, 'load_textdomain' ) );

		self::$problems = Requirements::evaluate( Requirements::environment() );
		if ( ! empty( self::$problems ) ) {
			add_action( 'admin_notices', array( __CLASS__, 'render_requirement_notice' ) );
			return;
		}

		// Migrations run on init (priority 20) so post types registered on init (5) exist.
		add_action( 'init', array( Migrator::class, 'maybe_run' ), 20 );
		Settings::register_hooks();
		\TopSolution\Delivery\Module::init();
		\TopSolution\Forms\Module::init();

		if ( is_admin() ) {
			Menu::init();
		}

		/**
		 * Fires when Top Solution Core is ready. Later modules (landing pages,
		 * offers, forms, orders, delivery) attach here.
		 */
		do_action( 'ts_core_loaded' );
	}

	/**
	 * Load translations (Arabic is the source language of the strings).
	 *
	 * @return void
	 */
	public static function load_textdomain() {
		load_plugin_textdomain( 'top-solution-core', false, dirname( plugin_basename( TS_CORE_FILE ) ) . '/languages' );
	}

	/**
	 * Admin notice listing unmet requirements.
	 *
	 * @return void
	 */
	public static function render_requirement_notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		echo '<div class="notice notice-error"><p><strong>Top Solution Core</strong></p><ul>';
		foreach ( self::$problems as $code ) {
			echo '<li>' . esc_html( Requirements::message( $code ) ) . '</li>';
		}
		echo '</ul></div>';
	}

	/**
	 * Requirement problems found at boot (empty when the plugin is running).
	 *
	 * @return string[]
	 */
	public static function problems() {
		return self::$problems;
	}
}
