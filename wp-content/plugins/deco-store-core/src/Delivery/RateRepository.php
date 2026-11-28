<?php
/**
 * Delivery rates per method and area (geo_id 0 = default for the method).
 * Fees are stored as decimals and handled in minor units in PHP.
 *
 * @package DecoStore
 */

namespace DecoStore\Delivery;

use DecoStore\Core\Schema;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.DB.DirectDatabaseQuery -- Custom table (see Schema); values always prepared.

/**
 * Reads and writes ds_delivery_rates.
 */
class RateRepository {

	/**
	 * Table name.
	 *
	 * @return string
	 */
	private static function table() {
		return Schema::table( 'delivery_rates' );
	}

	/**
	 * Insert or update a rate.
	 *
	 * @param string $method    Method id.
	 * @param int    $geo_id    Geo id (0 = default).
	 * @param int    $fee_minor Fee in minor units.
	 * @param bool   $active    Served (false = explicitly not delivered).
	 * @param int    $decimals  Store decimals.
	 * @return array{id:int,created:bool}
	 */
	public static function upsert( $method, $geo_id, $fee_minor, $active, $decimals ) {
		global $wpdb;
		$data = array(
			'method'     => (string) $method,
			'geo_id'     => (int) $geo_id,
			'fee'        => Money::to_decimal( max( 0, (int) $fee_minor ), $decimals ),
			'active'     => $active ? 1 : 0,
			'updated_at' => current_time( 'mysql', true ),
		);
		$id   = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . self::table() . ' WHERE method = %s AND geo_id = %d', $data['method'], $data['geo_id'] ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( $id ) {
			$wpdb->update( self::table(), $data, array( 'id' => $id ), array( '%s', '%d', '%s', '%d', '%s' ), array( '%d' ) );
			return array(
				'id'      => $id,
				'created' => false,
			);
		}
		$wpdb->insert( self::table(), $data, array( '%s', '%d', '%s', '%d', '%s' ) );
		return array(
			'id'      => (int) $wpdb->insert_id,
			'created' => true,
		);
	}

	/**
	 * Remove a rate (the area then falls back to the broader level).
	 *
	 * @param string $method Method id.
	 * @param int    $geo_id Geo id.
	 * @return void
	 */
	public static function delete( $method, $geo_id ) {
		global $wpdb;
		$wpdb->delete(
			self::table(),
			array(
				'method' => (string) $method,
				'geo_id' => (int) $geo_id,
			),
			array( '%s', '%d' )
		);
	}

	/**
	 * Rates for one method and some areas, in the shape RateCalculator expects.
	 *
	 * @param string $method   Method id.
	 * @param int[]  $geo_ids  Geo ids (include 0 for the default).
	 * @param int    $decimals Store decimals.
	 * @return array<string,array{id:int,fee_minor:int,active:bool}>
	 */
	public static function for_lookup( $method, array $geo_ids, $decimals ) {
		global $wpdb;
		$geo_ids = array_values( array_unique( array_map( 'intval', $geo_ids ) ) );
		if ( empty( $geo_ids ) ) {
			return array();
		}
		$placeholders = implode( ',', array_fill( 0, count( $geo_ids ), '%d' ) );
		$sql          = $wpdb->prepare( 'SELECT * FROM ' . self::table() . " WHERE method = %s AND geo_id IN ({$placeholders})", array_merge( array( (string) $method ), $geo_ids ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$out          = array();
		foreach ( (array) $wpdb->get_results( $sql, ARRAY_A ) as $row ) { // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$out[ $row['method'] . ':' . (int) $row['geo_id'] ] = array(
				'id'        => (int) $row['id'],
				'fee_minor' => (int) Money::to_minor( self::plain( $row['fee'] ), $decimals ),
				'active'    => (bool) (int) $row['active'],
			);
		}
		return $out;
	}

	/**
	 * Every rate, keyed geo_id => method => row (for the admin matrix).
	 *
	 * @param int $decimals Store decimals.
	 * @return array<int,array<string,array{id:int,fee_minor:int,active:bool}>>
	 */
	public static function matrix( $decimals ) {
		global $wpdb;
		$out = array();
		foreach ( (array) $wpdb->get_results( 'SELECT * FROM ' . self::table(), ARRAY_A ) as $row ) { // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- No user input.
			$out[ (int) $row['geo_id'] ][ $row['method'] ] = array(
				'id'        => (int) $row['id'],
				'fee_minor' => (int) Money::to_minor( self::plain( $row['fee'] ), $decimals ),
				'active'    => (bool) (int) $row['active'],
			);
		}
		return $out;
	}

	/**
	 * Stored decimal ("400.0000") trimmed to what the store uses, so it
	 * parses with the store's decimals.
	 *
	 * @param string $stored Database value.
	 * @return string
	 */
	private static function plain( $stored ) {
		$stored = (string) $stored;
		return false !== strpos( $stored, '.' ) ? rtrim( rtrim( $stored, '0' ), '.' ) : $stored;
	}
}
