<?php
/**
 * Converts between the admin form (typed text) and the canonical offer
 * input (minor units, basis points, Unix timestamps). Pure apart from the
 * time zone it is given; unit-tested. Values that cannot be read become
 * null / are left out so OfferSchema reports them; nothing is guessed.
 *
 * @package TopSolution
 */

namespace TopSolution\Offers;

use TopSolution\Core\Text;
use TopSolution\Delivery\Money;

defined( 'ABSPATH' ) || exit;

/**
 * Form <-> canonical.
 */
class OfferForm {

	const KIND_WORDS = array(
		'percent' => 'percent',
		'fixed'   => 'fixed_off',
		'price'   => 'unit_price',
	);

	/**
	 * Percentage typed by a person ("12.5") to basis points.
	 *
	 * @param mixed $text Raw.
	 * @return int|null
	 */
	public static function percent_to_bp( $text ) {
		$text = Text::western_digits( is_string( $text ) ? trim( $text ) : '' );
		$text = str_replace( array( ',', '٫', '%', '٪', ' ' ), array( '.', '.', '', '', '' ), $text );
		if ( ! preg_match( '/^\d{1,3}(\.\d{1,2})?$/', $text ) ) {
			return null;
		}
		return (int) round( (float) $text * 100 );
	}

	/**
	 * Basis points to the text shown in the form.
	 *
	 * @param int $bp Basis points.
	 * @return string
	 */
	public static function bp_to_percent( $bp ) {
		$bp = (int) $bp;
		return rtrim( rtrim( sprintf( '%d.%02d', intdiv( $bp, 100 ), $bp % 100 ), '0' ), '.' );
	}

	/**
	 * Money text to minor units (null when invalid or empty).
	 *
	 * @param mixed $text     Raw.
	 * @param int   $decimals Store decimals.
	 * @return int|null
	 */
	public static function money( $text, $decimals ) {
		return is_string( $text ) && '' !== trim( $text ) ? Money::to_minor( $text, $decimals ) : null;
	}

	/**
	 * Comma-separated ids to an int list (garbage entries are dropped).
	 *
	 * @param mixed $text Raw.
	 * @return int[]
	 */
	public static function id_list( $text ) {
		$out = array();
		foreach ( preg_split( '/[\s,،;]+/', Text::western_digits( is_string( $text ) ? $text : '' ) ) as $part ) {
			if ( preg_match( '/^\d{1,15}$/', $part ) && (int) $part > 0 ) {
				$out[ (int) $part ] = (int) $part;
			}
		}
		return array_values( $out );
	}

	/**
	 * A date-time-local value ("2026-12-01T09:00") in the store time zone to a timestamp.
	 *
	 * @param mixed         $text Raw.
	 * @param \DateTimeZone $tz   Store time zone.
	 * @return int|string|null Timestamp, null when empty, the raw text when invalid (so the schema reports it).
	 */
	public static function date( $text, \DateTimeZone $tz ) {
		$text = is_string( $text ) ? trim( $text ) : '';
		if ( '' === $text ) {
			return null;
		}
		$d = \DateTimeImmutable::createFromFormat( 'Y-m-d\TH:i', $text, $tz );
		if ( ! $d || $d->format( 'Y-m-d\TH:i' ) !== $text ) {
			return $text;
		}
		return $d->getTimestamp();
	}

	/**
	 * Timestamp to a date-time-local value.
	 *
	 * @param int|null      $ts Timestamp.
	 * @param \DateTimeZone $tz Store time zone.
	 * @return string
	 */
	public static function date_text( $ts, \DateTimeZone $tz ) {
		return null === $ts ? '' : ( new \DateTimeImmutable( '@' . (int) $ts ) )->setTimezone( $tz )->format( 'Y-m-d\TH:i' );
	}

	/**
	 * Tier lines "min_qty|percent|10" / "3|fixed|200" / "5|price|900".
	 *
	 * @param mixed $text     Raw.
	 * @param int   $decimals Store decimals.
	 * @return array
	 */
	public static function tiers( $text, $decimals ) {
		$out = array();
		foreach ( preg_split( '/\r\n|\r|\n/', is_string( $text ) ? $text : '' ) as $line ) {
			$line = trim( Text::western_digits( $line ) );
			if ( '' === $line ) {
				continue;
			}
			$p     = array_map( 'trim', explode( '|', $line ) );
			$kind  = isset( $p[1] ) && isset( self::KIND_WORDS[ strtolower( $p[1] ) ] ) ? self::KIND_WORDS[ strtolower( $p[1] ) ] : '';
			$value = isset( $p[2] ) ? ( 'percent' === $kind ? self::percent_to_bp( $p[2] ) : self::money( $p[2], $decimals ) ) : null;
			$out[] = array(
				'min_qty' => isset( $p[0] ) && preg_match( '/^\d{1,5}$/', $p[0] ) ? (int) $p[0] : null,
				'kind'    => $kind,
				'value'   => $value,
			);
		}
		return $out;
	}

	/**
	 * Tiers back to lines.
	 *
	 * @param array $tiers    Tiers.
	 * @param int   $decimals Store decimals.
	 * @return string
	 */
	public static function tiers_text( array $tiers, $decimals ) {
		$words = array_flip( self::KIND_WORDS );
		$lines = array();
		foreach ( $tiers as $t ) {
			$lines[] = $t['min_qty'] . '|' . $words[ $t['kind'] ] . '|' . ( 'percent' === $t['kind'] ? self::bp_to_percent( $t['value'] ) : Money::to_decimal( $t['value'], $decimals ) );
		}
		return implode( "\n", $lines );
	}

	/**
	 * Bundle item lines "product_id|qty|variation_id".
	 *
	 * @param mixed $text Raw.
	 * @return array
	 */
	public static function items( $text ) {
		$out = array();
		foreach ( preg_split( '/\r\n|\r|\n/', is_string( $text ) ? $text : '' ) as $line ) {
			$line = trim( Text::western_digits( $line ) );
			if ( '' === $line ) {
				continue;
			}
			$p     = array_map( 'trim', explode( '|', $line ) );
			$out[] = array(
				'product_id'   => isset( $p[0] ) && preg_match( '/^\d{1,15}$/', $p[0] ) ? (int) $p[0] : null,
				'qty'          => isset( $p[1] ) && '' !== $p[1] ? ( preg_match( '/^\d{1,5}$/', $p[1] ) ? (int) $p[1] : null ) : 1,
				'variation_id' => isset( $p[2] ) && '' !== $p[2] ? ( preg_match( '/^\d{1,15}$/', $p[2] ) ? (int) $p[2] : null ) : 0,
			);
		}
		return $out;
	}

	/**
	 * Items back to lines.
	 *
	 * @param array $items Items.
	 * @return string
	 */
	public static function items_text( array $items ) {
		return implode(
			"\n",
			array_map(
				static function ( $i ) {
					return $i['product_id'] . '|' . $i['qty'] . ( $i['variation_id'] ? '|' . $i['variation_id'] : '' );
				},
				$items
			)
		);
	}

	/**
	 * Gift fields to a gift reference.
	 *
	 * @param mixed $product   Product id text.
	 * @param mixed $variation Variation id text.
	 * @param mixed $qty       Quantity text.
	 * @return array
	 */
	public static function gift( $product, $variation, $qty ) {
		$num = static function ( $v, $fallback ) {
			$v = trim( Text::western_digits( is_string( $v ) ? $v : '' ) );
			return '' === $v ? $fallback : ( preg_match( '/^\d{1,15}$/', $v ) ? (int) $v : null );
		};
		return array(
			'product_id'   => $num( $product, null ),
			'variation_id' => $num( $variation, 0 ),
			'qty'          => $num( $qty, 1 ),
		);
	}

	/**
	 * Build canonical input from the submitted form.
	 *
	 * @param array         $post     Unslashed POST data.
	 * @param int           $decimals Store decimals.
	 * @param \DateTimeZone $tz       Store time zone.
	 * @return array Canonical input for OfferSchema::normalize().
	 */
	public static function canonical( array $post, $decimals, \DateTimeZone $tz ) {
		$get  = static function ( $key ) use ( $post ) {
			return isset( $post[ $key ] ) && is_string( $post[ $key ] ) ? $post[ $key ] : '';
		};
		$type = $get( 'type' );
		$in   = array(
			'label'      => $get( 'label' ),
			'type'       => $type,
			'status'     => $get( 'status' ),
			'priority'   => '' === trim( $get( 'priority' ) ) ? 0 : trim( Text::western_digits( $get( 'priority' ) ) ),
			'exclusive'  => ! empty( $post['exclusive'] ),
			'selectable' => ! empty( $post['selectable'] ),
			'starts_at'  => self::date( $get( 'starts_at' ), $tz ),
			'ends_at'    => self::date( $get( 'ends_at' ), $tz ),
			'scope'      => array(
				'mode'         => $get( 'scope_mode' ),
				'product_ids'  => self::id_list( $get( 'scope_products' ) ),
				'category_ids' => self::id_list( $get( 'scope_categories' ) ),
			),
			'conditions' => array(
				'min_qty'          => '' === trim( $get( 'min_qty' ) ) ? null : trim( Text::western_digits( $get( 'min_qty' ) ) ),
				'min_subtotal'     => self::money( $get( 'min_subtotal' ), $decimals ),
				'wilaya_ids'       => isset( $post['wilaya_ids'] ) && is_array( $post['wilaya_ids'] ) ? $post['wilaya_ids'] : array(),
				'delivery_methods' => isset( $post['delivery_methods'] ) && is_array( $post['delivery_methods'] ) ? $post['delivery_methods'] : array(),
				'weekdays'         => isset( $post['weekdays'] ) && is_array( $post['weekdays'] ) ? $post['weekdays'] : array(),
			),
		);
		if ( '' !== trim( $get( 'min_subtotal' ) ) && null === $in['conditions']['min_subtotal'] ) {
			$in['conditions']['min_subtotal'] = trim( $get( 'min_subtotal' ) ); // Invalid text: let the schema report it.
		}
		if ( 'qty_discount' === $type ) {
			$in['tiers'] = self::tiers( $get( 'tiers' ), $decimals );
		} elseif ( 'bundle' === $type ) {
			$kind               = 'percent' === $get( 'bundle_kind' ) ? 'percent' : 'price';
			$in['items']        = self::items( $get( 'items' ) );
			$in['bundle_price'] = array(
				'kind'  => $kind,
				'value' => 'percent' === $kind ? self::percent_to_bp( $get( 'bundle_value' ) ) : self::money( $get( 'bundle_value' ), $decimals ),
			);
		} elseif ( 'free_gift' === $type ) {
			$in['gift'] = self::gift( $get( 'gift_product' ), $get( 'gift_variation' ), $get( 'gift_qty' ) );
		} elseif ( 'custom_rule' === $type ) {
			$action_type  = $get( 'action_type' );
			$percent      = in_array( $action_type, array( 'percent_off', 'delivery_percent_off' ), true );
			$in['action'] = array(
				'type'  => $action_type,
				'value' => $percent ? self::percent_to_bp( $get( 'action_value' ) ) : self::money( $get( 'action_value' ), $decimals ),
				'gift'  => 'add_gift' === $action_type ? self::gift( $get( 'gift_product' ), $get( 'gift_variation' ), $get( 'gift_qty' ) ) : null,
			);
		}
		return $in;
	}
}
