<?php
/**
 * Data the submission validator needs from the outside world (database,
 * settings). An interface so the validator stays pure and testable; the
 * WordPress implementation is WpValidationContext.
 *
 * @package DecoStore
 */

namespace DecoStore\Forms;

defined( 'ABSPATH' ) || exit;

/**
 * Lookups used during validation.
 */
interface ValidationContext {

	/**
	 * Is this an existing, served wilaya?
	 *
	 * @param int $wilaya_id Wilaya id.
	 * @return bool
	 */
	public function wilaya_is_served( $wilaya_id );

	/**
	 * Number of communes registered for a wilaya (0 = the owner only loaded
	 * wilayas, so no commune can be asked for).
	 *
	 * @param int $wilaya_id Wilaya id.
	 * @return int
	 */
	public function commune_count( $wilaya_id );

	/**
	 * Is this a served commune of that wilaya?
	 *
	 * @param int $commune_id Commune id.
	 * @param int $wilaya_id  Wilaya id.
	 * @return bool
	 */
	public function commune_belongs( $commune_id, $wilaya_id );

	/**
	 * Delivery methods currently switched on.
	 *
	 * @return string[]
	 */
	public function delivery_methods();

	/**
	 * Is this an active pickup office serving the chosen area?
	 *
	 * @param int $pickup_id  Pickup point id.
	 * @param int $wilaya_id  Wilaya id.
	 * @param int $commune_id Commune id (0 = none).
	 * @return bool
	 */
	public function pickup_serves( $pickup_id, $wilaya_id, $commune_id );

	/**
	 * Phone rule id (PhoneRule::GENERIC or PhoneRule::DZ_MOBILE).
	 *
	 * @return string
	 */
	public function phone_rule();
}
