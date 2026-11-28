<?php
/**
 * Versioned, idempotent upgrade steps. Later phases add steps here (custom
 * tables, post types). Nothing is ever deleted by a migration.
 *
 * @package DecoStore
 */

namespace DecoStore\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Runs pending migrations.
 */
class Migrator {

	const OPTION  = 'ds_core_db_version';
	const CURRENT = 3;

	/**
	 * Migrations: target version => callable.
	 *
	 * @return array<int,callable>
	 */
	public static function migrations() {
		return array(
			1 => array( __CLASS__, 'migrate_1' ),
			2 => array( __CLASS__, 'migrate_2' ),
			3 => array( __CLASS__, 'migrate_3' ),
		);
	}

	/**
	 * Run pending migrations if the stored version is behind.
	 *
	 * @return void
	 */
	public static function maybe_run() {
		$from = (int) get_option( self::OPTION, 0 );
		if ( $from < self::CURRENT ) {
			self::run( $from );
		}
	}

	/**
	 * Run every migration newer than $from, in order.
	 *
	 * @param int $from Installed version.
	 * @return int The version reached.
	 */
	public static function run( $from ) {
		$reached = (int) $from;
		$steps   = self::migrations();
		ksort( $steps );
		foreach ( $steps as $version => $callback ) {
			if ( $version <= $from ) {
				continue;
			}
			call_user_func( $callback );
			$reached = $version;
			update_option( self::OPTION, $version, false );
			Logger::info( 'Migration applied', array( 'version' => $version ) );
		}
		return $reached;
	}

	/**
	 * Version 1: default settings and capabilities.
	 *
	 * @return void
	 */
	public static function migrate_1() {
		Settings::ensure_defaults();
		Capabilities::grant_defaults();
	}

	/**
	 * Version 2: delivery tables (geography, rates, pickup points).
	 *
	 * @return void
	 */
	public static function migrate_2() {
		Schema::install();
	}

	/**
	 * Version 3: forms. Creates the default form if the store has none.
	 *
	 * @return void
	 */
	public static function migrate_3() {
		if ( ! post_type_exists( \DecoStore\Forms\FormRepository::POST_TYPE ) ) {
			\DecoStore\Forms\FormRepository::register_post_type();
		}
		\DecoStore\Forms\FormRepository::ensure_default();
	}
}
