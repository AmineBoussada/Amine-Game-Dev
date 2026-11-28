<?php
/**
 * Plugin settings: one option array, validated on the server.
 *
 * @package TopSolution
 */

namespace TopSolution\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Settings schema, defaults, sanitising and access.
 */
class Settings {

	const OPTION = 'ts_core_settings';
	const GROUP  = 'ts_core_settings_group';

	/**
	 * Order statuses a new landing-page order may start with.
	 *
	 * @return string[]
	 */
	public static function allowed_order_statuses() {
		return array( 'processing', 'on-hold' );
	}

	/**
	 * Fields per settings tab: key => type (`bool` or `enum:<list>`).
	 *
	 * @return array<string,array<string,string>>
	 */
	public static function tabs() {
		return array(
			'general' => array(
				'debug_logging'            => 'bool',
				'delete_data_on_uninstall' => 'bool',
			),
			'orders'  => array(
				'initial_order_status' => 'enum:order_status',
			),
		);
	}

	/**
	 * Default values.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults() {
		return array(
			'debug_logging'            => false,
			'delete_data_on_uninstall' => false,
			// Owner decision (2026-11-28): new COD orders start as "processing".
			'initial_order_status'     => 'processing',
		);
	}

	/**
	 * Validate submitted settings and merge them into the current values.
	 * Pure function: no WordPress calls, unit-tested.
	 *
	 * Rules:
	 * - Only known keys exist in the result; stray keys are dropped.
	 * - Every known key present in the input is validated by its type
	 *   (bool, or an allowed enum value, else the default).
	 * - `__tab` names the form that was submitted. A browser does not send
	 *   unchecked boxes, so a checkbox that belongs to that tab and is absent
	 *   means "off". Fields of other tabs that are absent keep their current
	 *   value, so saving one tab never resets another.
	 * - Idempotent: WordPress runs the sanitize callback more than once on a
	 *   save, so sanitize( sanitize( x ) ) must equal sanitize( x ).
	 *
	 * @param mixed $input   Raw submitted array.
	 * @param array $current Current stored settings.
	 * @return array Sanitised settings (always a complete array of known keys).
	 */
	public static function sanitize( $input, array $current ) {
		$defaults = self::defaults();
		$result   = array_intersect_key( array_merge( $defaults, $current ), $defaults );
		if ( ! is_array( $input ) ) {
			return $result;
		}

		$tabs       = self::tabs();
		$tab        = isset( $input['__tab'] ) ? (string) $input['__tab'] : '';
		$tab_fields = isset( $tabs[ $tab ] ) ? $tabs[ $tab ] : array();
		$fields     = array();
		foreach ( $tabs as $tab_definition ) {
			$fields = array_merge( $fields, $tab_definition );
		}

		foreach ( $fields as $key => $type ) {
			$submitted = array_key_exists( $key, $input );
			if ( ! $submitted && ! isset( $tab_fields[ $key ] ) ) {
				continue; // Not part of this submission: keep the current value.
			}
			$raw = $submitted ? $input[ $key ] : null;
			if ( 'bool' === $type ) {
				$result[ $key ] = ! empty( $raw ) && '0' !== $raw;
			} elseif ( 'enum:order_status' === $type ) {
				$value          = is_string( $raw ) ? $raw : '';
				$result[ $key ] = in_array( $value, self::allowed_order_statuses(), true ) ? $value : $defaults[ $key ];
			}
		}
		return $result;
	}

	/**
	 * Callback for register_setting().
	 *
	 * @param mixed $input Raw submitted value.
	 * @return array
	 */
	public static function sanitize_callback( $input ) {
		return self::sanitize( $input, self::all() );
	}

	/**
	 * All settings merged over the defaults.
	 *
	 * @return array<string,mixed>
	 */
	public static function all() {
		$stored = get_option( self::OPTION, array() );
		return array_merge( self::defaults(), is_array( $stored ) ? $stored : array() );
	}

	/**
	 * One setting.
	 *
	 * @param string $key Setting key.
	 * @return mixed Null for an unknown key.
	 */
	public static function get( $key ) {
		$all = self::all();
		return array_key_exists( $key, $all ) ? $all[ $key ] : null;
	}

	/**
	 * Create the option with defaults if it does not exist.
	 *
	 * @return void
	 */
	public static function ensure_defaults() {
		if ( false === get_option( self::OPTION, false ) ) {
			add_option( self::OPTION, self::defaults(), '', 'yes' );
		}
	}

	/**
	 * Hook the Settings API registration.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
	}

	/**
	 * Register with the Settings API and gate saving by capability.
	 *
	 * @return void
	 */
	public static function register() {
		register_setting(
			self::GROUP,
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_callback' ),
				'default'           => self::defaults(),
				'show_in_rest'      => false,
			)
		);
		add_filter( 'option_page_capability_' . self::GROUP, array( __CLASS__, 'save_capability' ) );
	}

	/**
	 * Capability required to save through options.php.
	 *
	 * @return string
	 */
	public static function save_capability() {
		return Capabilities::MANAGE_SETTINGS;
	}
}
