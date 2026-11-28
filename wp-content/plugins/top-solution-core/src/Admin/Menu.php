<?php
/**
 * Admin menu. Items are added by capability, so a user only sees what they
 * may use. Later phases add their screens through the `ts_core_admin_menu`
 * action.
 *
 * @package TopSolution
 */

namespace TopSolution\Admin;

use TopSolution\Core\Capabilities;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the "Top Solution" menu.
 */
class Menu {

	const SLUG_DASHBOARD = 'top-solution';
	const SLUG_SETTINGS  = 'top-solution-settings';

	/**
	 * Hook the menu and assets.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
	}

	/**
	 * Add the top-level menu and its sub-pages.
	 *
	 * @return void
	 */
	public static function register() {
		add_menu_page(
			__( 'Top Solution', 'top-solution-core' ),
			__( 'Top Solution', 'top-solution-core' ),
			Capabilities::VIEW_REPORTS,
			self::SLUG_DASHBOARD,
			array( DashboardPage::class, 'render' ),
			'dashicons-store',
			56
		);
		add_submenu_page(
			self::SLUG_DASHBOARD,
			__( 'لوحة التحكم', 'top-solution-core' ),
			__( 'لوحة التحكم', 'top-solution-core' ),
			Capabilities::VIEW_REPORTS,
			self::SLUG_DASHBOARD,
			array( DashboardPage::class, 'render' )
		);

		/**
		 * Later modules add their sub-pages here (Landing Pages, Offers, Forms,
		 * Inventory, Suppliers, Delivery, Reports), each with its own capability.
		 */
		do_action( 'ts_core_admin_menu', self::SLUG_DASHBOARD );

		add_submenu_page(
			self::SLUG_DASHBOARD,
			__( 'الإعدادات', 'top-solution-core' ),
			__( 'الإعدادات', 'top-solution-core' ),
			Capabilities::MANAGE_SETTINGS,
			self::SLUG_SETTINGS,
			array( SettingsPage::class, 'render' )
		);
	}

	/**
	 * Load the admin stylesheet on this plugin's screens only.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 * @return void
	 */
	public static function enqueue( $hook_suffix ) {
		if ( false === strpos( (string) $hook_suffix, 'top-solution' ) ) {
			return;
		}
		wp_enqueue_style( 'ts-core-admin', TS_CORE_URL . 'assets/admin.css', array(), TS_CORE_VERSION );
	}
}
