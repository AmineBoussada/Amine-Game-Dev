<?php
/**
 * CSV parsing and validation for geography, rates and pickup points.
 * Pure functions (no database), unit-tested. Applying the rows is done by
 * the repositories, after a dry-run the owner reviews.
 *
 * @package TopSolution
 */

namespace TopSolution\Delivery;

defined( 'ABSPATH' ) || exit;

/**
 * Parses CSV text into validated rows.
 */
class CsvImporter {

	const MAX_BYTES = 2097152; // 2 MB.
	const MAX_ROWS  = 20000;

	/**
	 * Expected columns per import type (required columns first).
	 *
	 * @return array<string,array{required:string[],optional:string[]}>
	 */
	public static function columns() {
		return array(
			'geography' => array(
				'required' => array( 'wilaya_code', 'wilaya_name_ar' ),
				'optional' => array( 'commune_code', 'commune_name_ar', 'wilaya_name_fr', 'commune_name_fr' ),
			),
			'rates'     => array(
				'required' => array( 'method', 'wilaya_code', 'fee' ),
				'optional' => array( 'commune_code', 'active' ),
			),
			'pickups'   => array(
				'required' => array( 'wilaya_code', 'name' ),
				'optional' => array( 'commune_code', 'carrier', 'address', 'hours', 'active' ),
			),
		);
	}

	/**
	 * Parse and validate a CSV document.
	 *
	 * @param string $type     geography | rates | pickups.
	 * @param string $csv      File contents (UTF-8; a BOM is ignored).
	 * @param int    $decimals Store decimals (for fees).
	 * @return array{rows:array,errors:array<int,string>,header_error:string}
	 */
	public static function parse( $type, $csv, $decimals = 0 ) {
		$out     = array(
			'rows'         => array(),
			'errors'       => array(),
			'header_error' => '',
		);
		$columns = self::columns();
		if ( ! isset( $columns[ $type ] ) ) {
			$out['header_error'] = 'unknown_type';
			return $out;
		}
		if ( ! is_string( $csv ) || '' === trim( $csv ) ) {
			$out['header_error'] = 'empty';
			return $out;
		}
		if ( strlen( $csv ) > self::MAX_BYTES ) {
			$out['header_error'] = 'too_large';
			return $out;
		}
		if ( ! preg_match( '//u', $csv ) ) {
			$out['header_error'] = 'not_utf8';
			return $out;
		}
		$csv   = preg_replace( '/^\xEF\xBB\xBF/', '', $csv );
		$lines = preg_split( '/\r\n|\r|\n/', $csv );
		$lines = array_values(
			array_filter(
				$lines,
				static function ( $line ) {
					return '' !== trim( $line );
				}
			)
		);
		if ( count( $lines ) - 1 > self::MAX_ROWS ) {
			$out['header_error'] = 'too_many_rows';
			return $out;
		}

		$delimiter = substr_count( $lines[0], ';' ) > substr_count( $lines[0], ',' ) ? ';' : ',';
		$header    = array_map( array( __CLASS__, 'clean_header' ), str_getcsv( $lines[0], $delimiter, '"', '' ) );
		$spec      = $columns[ $type ];
		$missing   = array_diff( $spec['required'], $header );
		if ( $missing ) {
			$out['header_error'] = 'missing:' . implode( ',', $missing );
			return $out;
		}
		$known = array_merge( $spec['required'], $spec['optional'] );
		$seen  = array();

		$count = count( $lines );
		for ( $i = 1; $i < $count; $i++ ) {
			$line_no = $i + 1;
			$cells   = str_getcsv( $lines[ $i ], $delimiter, '"', '' );
			$raw     = array();
			foreach ( $header as $index => $name ) {
				if ( in_array( $name, $known, true ) ) {
					$raw[ $name ] = isset( $cells[ $index ] ) ? trim( (string) $cells[ $index ] ) : '';
				}
			}
			$row = self::validate_row( $type, $raw, $decimals, $error );
			if ( null === $row ) {
				$out['errors'][ $line_no ] = $error;
				continue;
			}
			$key = self::row_key( $type, $row );
			if ( isset( $seen[ $key ] ) ) {
				/* translators: %d: line number of the first occurrence. */
				$out['errors'][ $line_no ] = sprintf( 'duplicate_of_line:%d', $seen[ $key ] );
				continue;
			}
			$seen[ $key ]  = $line_no;
			$row['line']   = $line_no;
			$out['rows'][] = $row;
		}
		return $out;
	}

	/**
	 * Lower-case header cell without BOM, quotes or spaces.
	 *
	 * @param string $cell Header cell.
	 * @return string
	 */
	private static function clean_header( $cell ) {
		return strtolower( trim( (string) $cell, " \t\"'" ) );
	}

	/**
	 * Uniqueness key of a row inside one file.
	 *
	 * @param string $type Import type.
	 * @param array  $row  Valid row.
	 * @return string
	 */
	private static function row_key( $type, array $row ) {
		switch ( $type ) {
			case 'geography':
				return $row['wilaya_code'] . '|' . $row['commune_code'];
			case 'rates':
				return $row['method'] . '|' . $row['wilaya_code'] . '|' . $row['commune_code'];
			default:
				return $row['wilaya_code'] . '|' . $row['commune_code'] . '|' . $row['name'];
		}
	}

	/**
	 * Validate one row.
	 *
	 * @param string      $type     Import type.
	 * @param array       $raw      Cells by column name.
	 * @param int         $decimals Store decimals.
	 * @param string|null $error    Error code (out).
	 * @return array|null Normalised row, or null with $error set.
	 */
	private static function validate_row( $type, array $raw, $decimals, &$error ) {
		$error  = '';
		$wilaya = GeoCode::normalize( isset( $raw['wilaya_code'] ) ? $raw['wilaya_code'] : '' );
		if ( null === $wilaya || '0' === $wilaya || strlen( $wilaya ) > 3 ) {
			$error = 'bad_wilaya_code';
			return null;
		}
		$commune_raw = isset( $raw['commune_code'] ) ? $raw['commune_code'] : '';
		$commune     = '' === $commune_raw ? '' : GeoCode::normalize( $commune_raw );
		if ( null === $commune || ( '' !== $commune && ( '0' === $commune || strlen( $commune ) > 6 ) ) ) {
			$error = 'bad_commune_code';
			return null;
		}
		$active = true;
		if ( isset( $raw['active'] ) && '' !== $raw['active'] ) {
			$flag = strtolower( $raw['active'] );
			if ( in_array( $flag, array( '1', 'yes', 'true', 'نعم' ), true ) ) {
				$active = true;
			} elseif ( in_array( $flag, array( '0', 'no', 'false', 'لا' ), true ) ) {
				$active = false;
			} else {
				$error = 'bad_active';
				return null;
			}
		}

		if ( 'geography' === $type ) {
			$wilaya_name  = self::clean_name( isset( $raw['wilaya_name_ar'] ) ? $raw['wilaya_name_ar'] : '' );
			$commune_name = self::clean_name( isset( $raw['commune_name_ar'] ) ? $raw['commune_name_ar'] : '' );
			if ( '' === $wilaya_name ) {
				$error = 'missing_wilaya_name';
				return null;
			}
			if ( ( '' === $commune ) !== ( '' === $commune_name ) ) {
				$error = 'commune_code_and_name_go_together';
				return null;
			}
			return array(
				'wilaya_code'     => $wilaya,
				'wilaya_name_ar'  => $wilaya_name,
				'wilaya_name_fr'  => self::clean_name( isset( $raw['wilaya_name_fr'] ) ? $raw['wilaya_name_fr'] : '' ),
				'commune_code'    => $commune,
				'commune_name_ar' => $commune_name,
				'commune_name_fr' => self::clean_name( isset( $raw['commune_name_fr'] ) ? $raw['commune_name_fr'] : '' ),
			);
		}

		if ( 'rates' === $type ) {
			$method = isset( $raw['method'] ) ? strtolower( $raw['method'] ) : '';
			if ( ! in_array( $method, DeliverySettings::method_ids(), true ) ) {
				$error = 'bad_method';
				return null;
			}
			$fee = Money::to_minor( isset( $raw['fee'] ) ? $raw['fee'] : '', $decimals );
			if ( null === $fee ) {
				$error = 'bad_fee';
				return null;
			}
			return array(
				'method'       => $method,
				'wilaya_code'  => $wilaya,
				'commune_code' => $commune,
				'fee_minor'    => $fee,
				'active'       => $active,
			);
		}

		$name = self::clean_name( isset( $raw['name'] ) ? $raw['name'] : '' );
		if ( '' === $name ) {
			$error = 'missing_name';
			return null;
		}
		return array(
			'wilaya_code'  => $wilaya,
			'commune_code' => $commune,
			'name'         => $name,
			'carrier'      => self::clean_name( isset( $raw['carrier'] ) ? $raw['carrier'] : '' ),
			'address'      => self::clean_text( isset( $raw['address'] ) ? $raw['address'] : '' ),
			'hours'        => self::clean_name( isset( $raw['hours'] ) ? $raw['hours'] : '' ),
			'active'       => $active,
		);
	}

	/**
	 * Single-line text: no tags, no control characters, max 191 characters.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	public static function clean_name( $value ) {
		$value = preg_replace( '/<[^>]*>/', '', (string) $value );
		$value = preg_replace( '/[\x00-\x1F\x7F]+/u', ' ', $value );
		$value = trim( preg_replace( '/\s+/u', ' ', $value ) );
		return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, 191 ) : substr( $value, 0, 191 );
	}

	/**
	 * Longer text (address): same cleaning, max 500 characters.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	public static function clean_text( $value ) {
		$value = preg_replace( '/<[^>]*>/', '', (string) $value );
		$value = preg_replace( '/[\x00-\x1F\x7F]+/u', ' ', $value );
		$value = trim( preg_replace( '/\s+/u', ' ', $value ) );
		return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, 500 ) : substr( $value, 0, 500 );
	}
}
