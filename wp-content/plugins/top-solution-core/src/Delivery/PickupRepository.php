<?php
/**
 * Pickup points (delivery offices). Entered or imported by the owner.
 *
 * @package TopSolution
 */

namespace TopSolution\Delivery;

use TopSolution\Core\Schema;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.DB.DirectDatabaseQuery -- Custom table (see Schema); values always prepared.

/**
 * Reads and writes ts_pickup_points.
 */
class PickupRepository {

	/**
	 * Table name.
	 *
	 * @return string
	 */
	private static function table() {
		return Schema::table( 'pickup_points' );
	}

	/**
	 * Insert, or update the point with the same area and name.
	 *
	 * @param array $data geo_id, name, carrier, address, hours, active.
	 * @return array{id:int,created:bool}
	 */
	public static function upsert( array $data ) {
		global $wpdb;
		$row = array(
			'geo_id'  => (int) $data['geo_id'],
			'carrier' => CsvImporter::clean_name( isset( $data['carrier'] ) ? $data['carrier'] : '' ),
			'name'    => CsvImporter::clean_name( $data['name'] ),
			'address' => CsvImporter::clean_text( isset( $data['address'] ) ? $data['address'] : '' ),
			'hours'   => CsvImporter::clean_name( isset( $data['hours'] ) ? $data['hours'] : '' ),
			'active'  => ! empty( $data['active'] ) ? 1 : 0,
		);
		$id  = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . self::table() . ' WHERE geo_id = %d AND name = %s', $row['geo_id'], $row['name'] ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( $id ) {
			$wpdb->update( self::table(), $row, array( 'id' => $id ), array( '%d', '%s', '%s', '%s', '%s', '%d' ), array( '%d' ) );
			return array(
				'id'      => $id,
				'created' => false,
			);
		}
		$wpdb->insert( self::table(), $row, array( '%d', '%s', '%s', '%s', '%s', '%d' ) );
		return array(
			'id'      => (int) $wpdb->insert_id,
			'created' => true,
		);
	}

	/**
	 * All points (admin list), newest first.
	 *
	 * @return array[]
	 */
	public static function all() {
		global $wpdb;
		return array_map( array( __CLASS__, 'cast' ), (array) $wpdb->get_results( 'SELECT * FROM ' . self::table() . ' ORDER BY id DESC', ARRAY_A ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- No user input.
	}

	/**
	 * Active points serving an area: points of the commune, then points
	 * registered for the whole wilaya.
	 *
	 * @param int $wilaya_id  Wilaya id.
	 * @param int $commune_id Commune id (0 = none).
	 * @return array[]
	 */
	public static function for_area( $wilaya_id, $commune_id ) {
		global $wpdb;
		$ids = array_filter( array( (int) $commune_id, (int) $wilaya_id ) );
		if ( empty( $ids ) ) {
			return array();
		}
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$sql          = $wpdb->prepare( 'SELECT * FROM ' . self::table() . " WHERE active = 1 AND geo_id IN ({$placeholders})", array_values( $ids ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- %d placeholders are built from the id count.
		return array_map( array( __CLASS__, 'cast' ), (array) $wpdb->get_results( $sql, ARRAY_A ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * One point.
	 *
	 * @param int $id Point id.
	 * @return array|null
	 */
	public static function get( $id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', (int) $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $row ? self::cast( $row ) : null;
	}

	/**
	 * Switch a point on or off.
	 *
	 * @param int  $id     Point id.
	 * @param bool $active New state.
	 * @return void
	 */
	public static function set_active( $id, $active ) {
		global $wpdb;
		$wpdb->update( self::table(), array( 'active' => $active ? 1 : 0 ), array( 'id' => (int) $id ), array( '%d' ), array( '%d' ) );
	}

	/**
	 * Delete a point.
	 *
	 * @param int $id Point id.
	 * @return void
	 */
	public static function delete( $id ) {
		global $wpdb;
		$wpdb->delete( self::table(), array( 'id' => (int) $id ), array( '%d' ) );
	}

	/**
	 * Cast numeric columns.
	 *
	 * @param array $row Raw row.
	 * @return array
	 */
	private static function cast( $row ) {
		$row['id']     = (int) $row['id'];
		$row['geo_id'] = (int) $row['geo_id'];
		$row['active'] = (bool) (int) $row['active'];
		return $row;
	}
}
