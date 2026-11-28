<?php
/**
 * Minimal PSR-4 autoloader (no Composer needed on the server, so the plugin
 * ZIP installs as-is).
 *
 * @package DecoStore
 */

namespace DecoStore\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Maps a namespace prefix to a directory.
 */
class Autoloader {

	/**
	 * Register the autoloader.
	 *
	 * @param string $prefix   Namespace prefix with trailing backslash.
	 * @param string $base_dir Directory with trailing slash.
	 * @return void
	 */
	public static function register( $prefix, $base_dir ) {
		spl_autoload_register(
			static function ( $class_name ) use ( $prefix, $base_dir ) {
				$file = self::resolve( $class_name, $prefix, $base_dir );
				if ( null !== $file && is_readable( $file ) ) {
					require_once $file;
				}
			}
		);
	}

	/**
	 * Translate a class name to a file path (pure function, unit-tested).
	 *
	 * @param string $class_name Fully-qualified class name.
	 * @param string $prefix     Namespace prefix with trailing backslash.
	 * @param string $base_dir   Directory with trailing slash.
	 * @return string|null Path, or null when the class is outside the prefix.
	 */
	public static function resolve( $class_name, $prefix, $base_dir ) {
		$class_name = ltrim( $class_name, '\\' );
		if ( 0 !== strpos( $class_name, $prefix ) ) {
			return null;
		}
		$relative = substr( $class_name, strlen( $prefix ) );
		return $base_dir . str_replace( '\\', '/', $relative ) . '.php';
	}
}
