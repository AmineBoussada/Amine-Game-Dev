<?php
/**
 * Custom capabilities. Every screen and action of the plugin is gated by one
 * of these, never by a role name.
 *
 * @package DecoStore
 */

namespace DecoStore\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Capability list and role mapping.
 */
class Capabilities {

	const MANAGE_LANDING   = 'ds_manage_landing';
	const MANAGE_OFFERS    = 'ds_manage_offers';
	const MANAGE_FORMS     = 'ds_manage_forms';
	const MANAGE_DELIVERY  = 'ds_manage_delivery';
	const MANAGE_INVENTORY = 'ds_manage_inventory';
	const MANAGE_SUPPLIERS = 'ds_manage_suppliers';
	const VIEW_COSTS       = 'ds_view_costs';
	const VIEW_REPORTS     = 'ds_view_reports';
	const MANAGE_SETTINGS  = 'ds_manage_settings';

	/**
	 * Every capability the plugin defines.
	 *
	 * @return string[]
	 */
	public static function all() {
		return array(
			self::MANAGE_LANDING,
			self::MANAGE_OFFERS,
			self::MANAGE_FORMS,
			self::MANAGE_DELIVERY,
			self::MANAGE_INVENTORY,
			self::MANAGE_SUPPLIERS,
			self::VIEW_COSTS,
			self::VIEW_REPORTS,
			self::MANAGE_SETTINGS,
		);
	}

	/**
	 * Default role => capabilities map.
	 *
	 * Purchase costs and suppliers are confidential: `ds_view_costs`,
	 * `ds_manage_suppliers` and `ds_manage_settings` go to administrators
	 * only. Shop managers get day-to-day capabilities. The owner decides
	 * (open question Q10); change with the `ds_core_role_caps` filter.
	 *
	 * @return array<string,string[]>
	 */
	public static function default_role_map() {
		return array(
			'administrator' => self::all(),
			'shop_manager'  => array(
				self::MANAGE_LANDING,
				self::MANAGE_OFFERS,
				self::MANAGE_FORMS,
				self::MANAGE_DELIVERY,
				self::MANAGE_INVENTORY,
				self::VIEW_REPORTS,
			),
		);
	}

	/**
	 * Role map after the `ds_core_role_caps` filter.
	 *
	 * @return array<string,string[]>
	 */
	public static function role_map() {
		$map = apply_filters( 'ds_core_role_caps', self::default_role_map() );
		return is_array( $map ) ? $map : self::default_role_map();
	}

	/**
	 * Grant the default capabilities. Idempotent.
	 *
	 * @return void
	 */
	public static function grant_defaults() {
		foreach ( self::role_map() as $role_name => $caps ) {
			$role = get_role( $role_name );
			if ( ! $role ) {
				continue;
			}
			foreach ( $caps as $cap ) {
				if ( ! $role->has_cap( $cap ) ) {
					$role->add_cap( $cap );
				}
			}
		}
	}

	/**
	 * Remove every plugin capability from every role (used by uninstall).
	 *
	 * @return void
	 */
	public static function revoke_all() {
		$roles = wp_roles();
		foreach ( array_keys( $roles->roles ) as $role_name ) {
			$role = get_role( $role_name );
			if ( ! $role ) {
				continue;
			}
			foreach ( self::all() as $cap ) {
				$role->remove_cap( $cap );
			}
		}
	}
}
