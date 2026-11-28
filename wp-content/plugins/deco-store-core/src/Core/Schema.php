<?php
/**
 * Custom tables. Each one is justified in docs/DATABASE_SCHEMA.md: data that
 * is looked up on every order (rates by area) or that needs indexes and
 * aggregation. Products, customers and orders stay in WooCommerce.
 *
 * @package DecoStore
 */

namespace DecoStore\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Table names and creation (dbDelta, idempotent).
 */
class Schema {

	/**
	 * Prefixed table name.
	 *
	 * @param string $name geo_units | delivery_rates | pickup_points.
	 * @return string
	 */
	public static function table( $name ) {
		global $wpdb;
		return $wpdb->prefix . 'ds_' . $name;
	}

	/**
	 * Create or update the tables.
	 *
	 * @return void
	 */
	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();
		$geo     = self::table( 'geo_units' );
		$rates   = self::table( 'delivery_rates' );
		$pickups = self::table( 'pickup_points' );

		dbDelta(
			"CREATE TABLE {$geo} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				parent_id bigint(20) unsigned NOT NULL DEFAULT 0,
				level varchar(10) NOT NULL,
				code varchar(20) NOT NULL,
				name_ar varchar(191) NOT NULL,
				name_fr varchar(191) NOT NULL DEFAULT '',
				active tinyint(1) NOT NULL DEFAULT 1,
				source varchar(100) NOT NULL DEFAULT '',
				PRIMARY KEY  (id),
				UNIQUE KEY level_code (level,code),
				KEY parent_level (parent_id,level)
			) {$charset};"
		);
		dbDelta(
			"CREATE TABLE {$rates} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				method varchar(20) NOT NULL,
				geo_id bigint(20) unsigned NOT NULL DEFAULT 0,
				fee decimal(18,4) NOT NULL DEFAULT 0,
				active tinyint(1) NOT NULL DEFAULT 1,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY method_geo (method,geo_id)
			) {$charset};"
		);
		dbDelta(
			"CREATE TABLE {$pickups} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				geo_id bigint(20) unsigned NOT NULL,
				carrier varchar(191) NOT NULL DEFAULT '',
				name varchar(191) NOT NULL,
				address text NOT NULL,
				hours varchar(191) NOT NULL DEFAULT '',
				active tinyint(1) NOT NULL DEFAULT 1,
				PRIMARY KEY  (id),
				KEY geo_active (geo_id,active)
			) {$charset};"
		);
	}

	/**
	 * Drop the tables (uninstall with "delete data" only).
	 *
	 * @return void
	 */
	public static function uninstall() {
		global $wpdb;
		foreach ( array( 'geo_units', 'delivery_rates', 'pickup_points' ) as $name ) {
			$wpdb->query( 'DROP TABLE IF EXISTS ' . self::table( $name ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Fixed, prefixed table names; uninstall only.
		}
	}

	/**
	 * Do all tables exist?
	 *
	 * @return bool
	 */
	public static function tables_exist() {
		global $wpdb;
		foreach ( array( 'geo_units', 'delivery_rates', 'pickup_points' ) as $name ) {
			$table = self::table( $name );
			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				return false;
			}
		}
		return true;
	}
}
