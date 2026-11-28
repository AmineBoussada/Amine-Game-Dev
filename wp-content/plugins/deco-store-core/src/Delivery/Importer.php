<?php
/**
 * Applies CSV rows parsed by CsvImporter. Every type has a dry run that
 * reports exactly what would change without writing anything; the admin
 * screen always shows the dry run before the real import.
 *
 * @package DecoStore
 */

namespace DecoStore\Delivery;

defined( 'ABSPATH' ) || exit;

/**
 * Writes imported rows through the repositories.
 */
class Importer {

	/**
	 * Run an import (or its dry run).
	 *
	 * @param string $type     geography | rates | pickups.
	 * @param array  $rows     Rows from CsvImporter::parse().
	 * @param bool   $dry_run  True = report only, write nothing.
	 * @param int    $decimals Store decimals.
	 * @param string $source   Source label stored with geography rows.
	 * @return array{created:int,updated:int,skipped:array<int,string>}
	 */
	public static function run( $type, array $rows, $dry_run, $decimals, $source = '' ) {
		switch ( $type ) {
			case 'geography':
				return self::geography( $rows, $dry_run, $source );
			case 'rates':
				return self::rates( $rows, $dry_run, $decimals );
			case 'pickups':
				return self::pickups( $rows, $dry_run );
		}
		return array(
			'created' => 0,
			'updated' => 0,
			'skipped' => array(),
		);
	}

	/**
	 * Geography rows: wilayas first, then communes.
	 *
	 * @param array  $rows    Rows.
	 * @param bool   $dry_run Report only.
	 * @param string $source  Source label.
	 * @return array
	 */
	private static function geography( array $rows, $dry_run, $source ) {
		$report  = array(
			'created' => 0,
			'updated' => 0,
			'skipped' => array(),
		);
		$wilayas = array();
		foreach ( $rows as $row ) {
			$code = $row['wilaya_code'];
			if ( isset( $wilayas[ $code ] ) ) {
				continue;
			}
			$exists = GeoRepository::find_by_code( 'wilaya', $code );
			if ( $dry_run ) {
				$wilayas[ $code ] = $exists ? $exists['id'] : -1;
				++$report[ $exists ? 'updated' : 'created' ];
				continue;
			}
			$result           = GeoRepository::upsert_wilaya( $code, $row['wilaya_name_ar'], $row['wilaya_name_fr'], $source );
			$wilayas[ $code ] = $result['id'];
			++$report[ $result['created'] ? 'created' : 'updated' ];
		}
		foreach ( $rows as $row ) {
			if ( '' === $row['commune_code'] ) {
				continue;
			}
			$existing = GeoRepository::find_by_code( 'commune', $row['commune_code'] );
			if ( $existing && $wilayas[ $row['wilaya_code'] ] > 0 && $existing['parent_id'] !== $wilayas[ $row['wilaya_code'] ] ) {
				$report['skipped'][ $row['line'] ] = 'commune_code_belongs_to_another_wilaya';
				continue;
			}
			if ( $dry_run ) {
				++$report[ $existing ? 'updated' : 'created' ];
				continue;
			}
			$result = GeoRepository::upsert_commune( $wilayas[ $row['wilaya_code'] ], $row['commune_code'], $row['commune_name_ar'], $row['commune_name_fr'], $source );
			++$report[ $result['created'] ? 'created' : 'updated' ];
		}
		return $report;
	}

	/**
	 * Resolve a (wilaya code, commune code) pair to a geo id.
	 *
	 * @param string $wilaya_code  Wilaya code.
	 * @param string $commune_code Commune code ('' = wilaya level).
	 * @param string $error        Error code (out).
	 * @return int Geo id, or 0 with $error set.
	 */
	private static function resolve_area( $wilaya_code, $commune_code, &$error ) {
		$error  = '';
		$wilaya = GeoRepository::find_by_code( 'wilaya', $wilaya_code );
		if ( ! $wilaya ) {
			$error = 'unknown_wilaya';
			return 0;
		}
		if ( '' === $commune_code ) {
			return $wilaya['id'];
		}
		$commune = GeoRepository::find_by_code( 'commune', $commune_code );
		if ( ! $commune ) {
			$error = 'unknown_commune';
			return 0;
		}
		if ( $commune['parent_id'] !== $wilaya['id'] ) {
			$error = 'commune_code_belongs_to_another_wilaya';
			return 0;
		}
		return $commune['id'];
	}

	/**
	 * Rate rows.
	 *
	 * @param array $rows     Rows.
	 * @param bool  $dry_run  Report only.
	 * @param int   $decimals Store decimals.
	 * @return array
	 */
	private static function rates( array $rows, $dry_run, $decimals ) {
		$report = array(
			'created' => 0,
			'updated' => 0,
			'skipped' => array(),
		);
		foreach ( $rows as $row ) {
			$geo_id = self::resolve_area( $row['wilaya_code'], $row['commune_code'], $error );
			if ( ! $geo_id ) {
				$report['skipped'][ $row['line'] ] = $error;
				continue;
			}
			if ( $dry_run ) {
				$exists = RateRepository::for_lookup( $row['method'], array( $geo_id ), $decimals );
				++$report[ $exists ? 'updated' : 'created' ];
				continue;
			}
			$result = RateRepository::upsert( $row['method'], $geo_id, $row['fee_minor'], $row['active'], $decimals );
			++$report[ $result['created'] ? 'created' : 'updated' ];
		}
		return $report;
	}

	/**
	 * Pickup point rows.
	 *
	 * @param array $rows    Rows.
	 * @param bool  $dry_run Report only.
	 * @return array
	 */
	private static function pickups( array $rows, $dry_run ) {
		$report = array(
			'created' => 0,
			'updated' => 0,
			'skipped' => array(),
		);
		foreach ( $rows as $row ) {
			$geo_id = self::resolve_area( $row['wilaya_code'], $row['commune_code'], $error );
			if ( ! $geo_id ) {
				$report['skipped'][ $row['line'] ] = $error;
				continue;
			}
			if ( $dry_run ) {
				++$report['created'];
				continue;
			}
			$row['geo_id'] = $geo_id;
			$result        = PickupRepository::upsert( $row );
			++$report[ $result['created'] ? 'created' : 'updated' ];
		}
		return $report;
	}
}
