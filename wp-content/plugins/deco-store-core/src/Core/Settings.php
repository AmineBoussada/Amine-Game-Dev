<?php
/**
 * Plugin settings: one option array, validated on the server.
 *
 * @package DecoStore
 */

namespace DecoStore\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Settings schema, defaults, sanitising and access.
 */
class Settings {

	const OPTION = 'ds_core_settings';
	const GROUP  = 'ds_core_settings_group';

	/**
	 * Order statuses a new landing-page order may start with.
	 *
	 * @return string[]
	 */
	public static function allowed_order_statuses() {
		return array( 'processing', 'on-hold' );
	}

	/**
	 * Allowed values of a list-type setting.
	 *
	 * @param string $name List name (order_status | phone_rule).
	 * @return string[]
	 */
	public static function allowed_values( $name ) {
		if ( 'order_status' === $name ) {
			return self::allowed_order_statuses();
		}
		if ( 'phone_rule' === $name ) {
			return \DecoStore\Forms\PhoneRule::ids();
		}
		return array();
	}

	/**
	 * Fields per settings tab: key => type (`bool`, `enum:<list>`, `int:<min>:<max>` or `text:<max length>`).
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
				'initial_order_status'   => 'enum:order_status',
				'confirmation_message'   => 'text:500',
				'token_ttl_hours'        => 'int:1:72',
				'max_attempts_per_ip'    => 'int:1:200',
				'duplicate_window_hours' => 'int:0:168',
			),
			'forms'   => array(
				'phone_rule' => 'enum:phone_rule',
			),
			'offers'  => array(
				'max_gifts_per_order' => 'int:0:10',
				'min_price_percent'   => 'int:0:100',
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
			// No market rule is assumed: 8-15 digits. The owner may switch to the Algerian mobile rule.
			'phone_rule'               => 'generic',
			// Offers: one gift per order unless the owner decides otherwise; no price floor unless set.
			'max_gifts_per_order'      => 1,
			'min_price_percent'        => 0,
			// Orders: owner text shown after ordering (empty = neutral default text).
			'confirmation_message'     => '',
			// A form left open longer than this must be reloaded before ordering.
			'token_ttl_hours'          => 12,
			// Order attempts accepted from one network address per 10 minutes.
			'max_attempts_per_ip'      => 20,
			// Same phone + same product within this window: the order is created but flagged (0 = off).
			'duplicate_window_hours'   => 24,
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
			} elseif ( 0 === strpos( $type, 'text:' ) ) {
				$result[ $key ] = is_string( $raw ) ? Text::block( $raw, (int) substr( $type, 5 ) ) : $defaults[ $key ];
			} elseif ( 0 === strpos( $type, 'int:' ) ) {
				list( , $min, $max ) = explode( ':', $type );
				$text                = is_int( $raw ) ? (string) $raw : ( is_string( $raw ) ? trim( $raw ) : '' );
				$ok                  = preg_match( '/^\d{1,4}$/', $text ) && (int) $text >= (int) $min && (int) $text <= (int) $max;
				$result[ $key ]      = $ok ? (int) $text : $defaults[ $key ];
			} elseif ( 0 === strpos( $type, 'enum:' ) ) {
				$value          = is_string( $raw ) ? $raw : '';
				$result[ $key ] = in_array( $value, self::allowed_values( substr( $type, 5 ) ), true ) ? $value : $defaults[ $key ];
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
