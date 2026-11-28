<?php
/**
 * Activation.
 *
 * @package DecoStore
 */

namespace DecoStore\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Runs once when the plugin is activated.
 */
class Activator {

	/**
	 * Install defaults and capabilities. Safe to run repeatedly; it never
	 * overwrites existing settings.
	 *
	 * @return void
	 */
	public static function activate() {
		Migrator::run( (int) get_option( Migrator::OPTION, 0 ) );
		// Always (re)grant so roles created later still receive the defaults.
		Capabilities::grant_defaults();
	}
}
