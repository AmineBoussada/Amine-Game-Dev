<?php
/**
 * Admin menu. Items are added by capability, so a user only sees what they
 * may use. Later phases add their screens through the `ds_core_admin_menu`
 * action.
 *
 * @package DecoStore
 */

namespace DecoStore\Admin;

use DecoStore\Core\Capabilities;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the "Déco Store" menu.
 */
class Menu {

	const SLUG_DASHBOARD = 'deco-store';
	const SLUG_SETTINGS  = 'deco-store-settings';

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
			__( 'Déco Store', 'deco-store-core' ),
			__( 'Déco Store', 'deco-store-core' ),
			Capabilities::VIEW_REPORTS,
			self::SLUG_DASHBOARD,
			array( DashboardPage::class, 'render' ),
			'dashicons-store',
			56
		);
		add_submenu_page(
			self::SLUG_DASHBOARD,
			__( 'لوحة التحكم', 'deco-store-core' ),
			__( 'لوحة التحكم', 'deco-store-core' ),
			Capabilities::VIEW_REPORTS,
			self::SLUG_DASHBOARD,
			array( DashboardPage::class, 'render' )
		);

		/**
		 * Later modules add their sub-pages here (Landing Pages, Offers, Forms,
		 * Inventory, Suppliers, Delivery, Reports), each with its own capability.
		 */
		do_action( 'ds_core_admin_menu', self::SLUG_DASHBOARD );

		add_submenu_page(
			self::SLUG_DASHBOARD,
			__( 'الإعدادات', 'deco-store-core' ),
			__( 'الإعدادات', 'deco-store-core' ),
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
		if ( false === strpos( (string) $hook_suffix, 'deco-store' ) ) {
			return;
		}
		wp_enqueue_style( 'ds-core-admin', DS_CORE_URL . 'assets/admin.css', array(), DS_CORE_VERSION );
	}
}
