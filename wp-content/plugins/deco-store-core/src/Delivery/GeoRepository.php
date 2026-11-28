<?php
/**
 * Geography (wilayas and communes). Data comes only from the owner (manual
 * entry or CSV from an approved source); nothing is shipped with the plugin.
 *
 * @package DecoStore
 */

namespace DecoStore\Delivery;

use DecoStore\Core\Schema;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.DB.DirectDatabaseQuery -- Custom table (see Schema); values always prepared.

/**
 * Reads and writes ds_geo_units.
 */
class GeoRepository {

	/**
	 * Table name.
	 *
	 * @return string
	 */
	private static function table() {
		return Schema::table( 'geo_units' );
	}

	/**
	 * Row by id.
	 *
	 * @param int $id Geo id.
	 * @return array|null
	 */
	public static function get( $id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', (int) $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		return $row ? self::cast( $row ) : null;
	}

	/**
	 * Row by level and code.
	 *
	 * @param string $level wilaya | commune.
	 * @param string $code  Code (any zero padding).
	 * @return array|null
	 */
	public static function find_by_code( $level, $code ) {
		global $wpdb;
		$code = GeoCode::normalize( $code );
		if ( null === $code ) {
			return null;
		}
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE level = %s AND code = %s', $level, $code ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $row ? self::cast( $row ) : null;
	}

	/**
	 * A commune of a wilaya by its Arabic or French name (used to match the
	 * free-text "city" of a WooCommerce address).
	 *
	 * @param int    $wilaya_id Wilaya id.
	 * @param string $name      Name as typed.
	 * @return array|null
	 */
	public static function find_commune_by_name( $wilaya_id, $name ) {
		global $wpdb;
		$name = CsvImporter::clean_name( $name );
		if ( '' === $name ) {
			return null;
		}
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . " WHERE level = 'commune' AND parent_id = %d AND ( name_ar = %s OR name_fr = %s ) LIMIT 1", (int) $wilaya_id, $name, $name ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $row ? self::cast( $row ) : null;
	}

	/**
	 * All wilayas, ordered by number.
	 *
	 * @param bool $active_only Only active ones.
	 * @return array[]
	 */
	public static function wilayas( $active_only = false ) {
		global $wpdb;
		$sql  = 'SELECT * FROM ' . self::table() . " WHERE level = 'wilaya'" . ( $active_only ? ' AND active = 1' : '' );
		$rows = array_map( array( __CLASS__, 'cast' ), (array) $wpdb->get_results( $sql, ARRAY_A ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- No user input.
		usort(
			$rows,
			static function ( $a, $b ) {
				return (int) $a['code'] <=> (int) $b['code'];
			}
		);
		return $rows;
	}

	/**
	 * Communes of a wilaya, ordered by name.
	 *
	 * @param int  $wilaya_id   Wilaya id.
	 * @param bool $active_only Only active ones.
	 * @return array[]
	 */
	public static function communes( $wilaya_id, $active_only = false ) {
		global $wpdb;
		$sql  = $wpdb->prepare( 'SELECT * FROM ' . self::table() . " WHERE level = 'commune' AND parent_id = %d" . ( $active_only ? ' AND active = 1' : '' ), (int) $wilaya_id ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$rows = array_map( array( __CLASS__, 'cast' ), (array) $wpdb->get_results( $sql, ARRAY_A ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		usort(
			$rows,
			static function ( $a, $b ) {
				return strcmp( $a['name_ar'], $b['name_ar'] );
			}
		);
		return $rows;
	}

	/**
	 * Number of communes per wilaya id.
	 *
	 * @return array<int,int>
	 */
	public static function commune_counts() {
		global $wpdb;
		$rows = (array) $wpdb->get_results( 'SELECT parent_id, COUNT(*) AS n FROM ' . self::table() . " WHERE level = 'commune' GROUP BY parent_id", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- No user input.
		$out  = array();
		foreach ( $rows as $row ) {
			$out[ (int) $row['parent_id'] ] = (int) $row['n'];
		}
		return $out;
	}

	/**
	 * Count rows of a level.
	 *
	 * @param string $level wilaya | commune.
	 * @return int
	 */
	public static function count( $level ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::table() . ' WHERE level = %s', $level ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Insert or update a wilaya by code.
	 *
	 * @param string $code    Code.
	 * @param string $name_ar Arabic name.
	 * @param string $name_fr French name (optional).
	 * @param string $source  Where the data came from.
	 * @return array{id:int,created:bool}
	 */
	public static function upsert_wilaya( $code, $name_ar, $name_fr = '', $source = '' ) {
		return self::upsert( 'wilaya', 0, $code, $name_ar, $name_fr, $source );
	}

	/**
	 * Insert or update a commune by code.
	 *
	 * @param int    $wilaya_id Parent wilaya id.
	 * @param string $code      Code.
	 * @param string $name_ar   Arabic name.
	 * @param string $name_fr   French name (optional).
	 * @param string $source    Where the data came from.
	 * @return array{id:int,created:bool}
	 */
	public static function upsert_commune( $wilaya_id, $code, $name_ar, $name_fr = '', $source = '' ) {
		return self::upsert( 'commune', (int) $wilaya_id, $code, $name_ar, $name_fr, $source );
	}

	/**
	 * Insert or update by (level, code).
	 *
	 * @param string $level     Level.
	 * @param int    $parent_id Parent id.
	 * @param string $code      Code.
	 * @param string $name_ar   Arabic name.
	 * @param string $name_fr   French name.
	 * @param string $source    Source label.
	 * @return array{id:int,created:bool}
	 */
	private static function upsert( $level, $parent_id, $code, $name_ar, $name_fr, $source ) {
		global $wpdb;
		$code     = GeoCode::normalize( $code );
		$existing = self::find_by_code( $level, $code );
		$data     = array(
			'parent_id' => (int) $parent_id,
			'level'     => $level,
			'code'      => $code,
			'name_ar'   => CsvImporter::clean_name( $name_ar ),
			'name_fr'   => CsvImporter::clean_name( $name_fr ),
			'source'    => CsvImporter::clean_name( $source ),
		);
		if ( $existing ) {
			$wpdb->update( self::table(), $data, array( 'id' => $existing['id'] ), array( '%d', '%s', '%s', '%s', '%s', '%s' ), array( '%d' ) );
			return array(
				'id'      => $existing['id'],
				'created' => false,
			);
		}
		$data['active'] = 1;
		$wpdb->insert( self::table(), $data, array( '%d', '%s', '%s', '%s', '%s', '%s', '%d' ) );
		return array(
			'id'      => (int) $wpdb->insert_id,
			'created' => true,
		);
	}

	/**
	 * Switch an area on or off.
	 *
	 * @param int  $id     Geo id.
	 * @param bool $active New state.
	 * @return bool
	 */
	public static function set_active( $id, $active ) {
		global $wpdb;
		return false !== $wpdb->update( self::table(), array( 'active' => $active ? 1 : 0 ), array( 'id' => (int) $id ), array( '%d' ), array( '%d' ) );
	}

	/**
	 * Cast numeric columns.
	 *
	 * @param array $row Raw row.
	 * @return array
	 */
	private static function cast( $row ) {
		$row['id']        = (int) $row['id'];
		$row['parent_id'] = (int) $row['parent_id'];
		$row['active']    = (bool) (int) $row['active'];
		return $row;
	}
}
