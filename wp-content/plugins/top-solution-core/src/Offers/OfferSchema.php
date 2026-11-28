<?php
/**
 * Offer definition: validation and normalisation. Pure function, unit-tested.
 *
 * Input is canonical: money in minor units, percentages in basis points
 * (1 % = 100), dates as Unix timestamps. The admin form converts typed text
 * into this shape (OfferForm). normalize() is idempotent.
 *
 * Offer types (closed vocabulary, no code or expressions):
 * - qty_discount: tiers by quantity.
 * - bundle: a fixed set of items sold at a bundle price.
 * - free_gift: a zero-priced gift line.
 * - custom_rule: conditions + exactly one action.
 *
 * @package TopSolution
 */

namespace TopSolution\Offers;

use TopSolution\Core\Text;

defined( 'ABSPATH' ) || exit;

/**
 * Normalises offer definitions.
 */
class OfferSchema {

	const TYPES        = array( 'qty_discount', 'bundle', 'free_gift', 'custom_rule' );
	const STATUSES     = array( 'draft', 'active', 'archived' );
	const KINDS        = array( 'percent', 'fixed_off', 'unit_price' );
	const ACTIONS      = array( 'percent_off', 'fixed_off_line', 'set_unit_price', 'fixed_off_order', 'free_delivery', 'delivery_percent_off', 'delivery_fixed_off', 'add_gift' );
	const MAX_TIERS    = 20;
	const MAX_ITEMS    = 10;
	const MAX_MONEY    = 1000000000000; // 10^12 minor units.
	const MAX_QTY      = 1000;
	const MAX_PRIORITY = 1000;
	const MAX_IDS      = 100;
	const BASIS_POINTS = 10000;

	/**
	 * Price-affecting action types.
	 *
	 * @return string[]
	 */
	public static function price_actions() {
		return array( 'percent_off', 'fixed_off_line', 'set_unit_price' );
	}

	/**
	 * Stacking group of an action.
	 *
	 * @param string $action Action type.
	 * @return string price | order | delivery | gift | ''.
	 */
	public static function action_group( $action ) {
		if ( in_array( $action, self::price_actions(), true ) ) {
			return 'price';
		}
		$map = array(
			'fixed_off_order'      => 'order',
			'free_delivery'        => 'delivery',
			'delivery_percent_off' => 'delivery',
			'delivery_fixed_off'   => 'delivery',
			'add_gift'             => 'gift',
		);
		return isset( $map[ $action ] ) ? $map[ $action ] : '';
	}

	/**
	 * Stacking group of a normalised offer.
	 *
	 * @param array $offer Normalised offer.
	 * @return string
	 */
	public static function group( array $offer ) {
		switch ( $offer['type'] ) {
			case 'qty_discount':
			case 'bundle':
				return 'price';
			case 'free_gift':
				return 'gift';
			default:
				return self::action_group( isset( $offer['action']['type'] ) ? $offer['action']['type'] : '' );
		}
	}

	/**
	 * Integer within bounds, from an int or a digit string.
	 *
	 * @param mixed $value Raw.
	 * @param int   $min   Minimum.
	 * @param int   $max   Maximum.
	 * @return int|null
	 */
	private static function int_in( $value, $min, $max ) {
		if ( is_string( $value ) && preg_match( '/^-?\d{1,15}$/', trim( $value ) ) ) {
			$value = (int) trim( $value );
		}
		if ( is_float( $value ) && floor( $value ) === $value ) {
			$value = (int) $value;
		}
		return is_int( $value ) && $value >= $min && $value <= $max ? $value : null;
	}

	/**
	 * Id list: unique positive integers.
	 *
	 * @param mixed $value Raw.
	 * @return int[]
	 */
	private static function ids( $value ) {
		$out = array();
		foreach ( is_array( $value ) ? array_slice( $value, 0, self::MAX_IDS ) : array() as $v ) {
			$id = self::int_in( $v, 1, PHP_INT_MAX );
			if ( null !== $id ) {
				$out[ $id ] = $id;
			}
		}
		return array_values( $out );
	}

	/**
	 * Normalise a gift / item reference.
	 *
	 * @param mixed  $raw    Raw.
	 * @param string $code   Error code to use.
	 * @param array  $errors Errors (by reference).
	 * @return array|null
	 */
	private static function product_ref( $raw, $code, array &$errors ) {
		$raw     = is_array( $raw ) ? $raw : array();
		$product = self::int_in( isset( $raw['product_id'] ) ? $raw['product_id'] : null, 1, PHP_INT_MAX );
		$var     = self::int_in( isset( $raw['variation_id'] ) && '' !== $raw['variation_id'] ? $raw['variation_id'] : 0, 0, PHP_INT_MAX );
		$qty     = self::int_in( isset( $raw['qty'] ) ? $raw['qty'] : 1, 1, self::MAX_QTY );
		if ( null === $product || null === $var || null === $qty ) {
			$errors[] = array(
				'code'  => $code,
				'field' => '',
			);
			return null;
		}
		return array(
			'product_id'   => $product,
			'variation_id' => $var,
			'qty'          => $qty,
		);
	}

	/**
	 * Validate a rule {kind,value}.
	 *
	 * @param string $kind  percent | fixed_off | unit_price (or action type).
	 * @param mixed  $value Raw value.
	 * @return int|null Normalised value (basis points or minor units).
	 */
	private static function rule_value( $kind, $value ) {
		if ( 'percent' === $kind || 'percent_off' === $kind || 'delivery_percent_off' === $kind ) {
			return self::int_in( $value, 1, self::BASIS_POINTS );
		}
		if ( 'unit_price' === $kind || 'set_unit_price' === $kind ) {
			return self::int_in( $value, 0, self::MAX_MONEY );
		}
		return self::int_in( $value, 1, self::MAX_MONEY );
	}

	/**
	 * Normalise a definition.
	 *
	 * @param mixed $input Canonical input.
	 * @return array{definition:array,errors:array<int,array{code:string,field:string}>}
	 */
	public static function normalize( $input ) {
		$input  = is_array( $input ) ? $input : array();
		$errors = array();

		$label = Text::line( isset( $input['label'] ) ? $input['label'] : '', 100 );
		if ( '' === $label ) {
			$errors[] = array(
				'code'  => 'label_required',
				'field' => 'label',
			);
		}
		$type = isset( $input['type'] ) && is_string( $input['type'] ) ? $input['type'] : '';
		if ( ! in_array( $type, self::TYPES, true ) ) {
			$errors[] = array(
				'code'  => 'bad_type',
				'field' => 'type',
			);
			$type     = 'custom_rule';
		}
		$status = isset( $input['status'] ) && in_array( $input['status'], self::STATUSES, true ) ? $input['status'] : 'draft';

		$priority = self::int_in( isset( $input['priority'] ) ? $input['priority'] : 0, 0, self::MAX_PRIORITY );
		if ( null === $priority ) {
			$errors[] = array(
				'code'  => 'bad_priority',
				'field' => 'priority',
			);
			$priority = 0;
		}

		$starts = isset( $input['starts_at'] ) && '' !== $input['starts_at'] && null !== $input['starts_at'] ? self::int_in( $input['starts_at'], 0, 4102444800 ) : null;
		$ends   = isset( $input['ends_at'] ) && '' !== $input['ends_at'] && null !== $input['ends_at'] ? self::int_in( $input['ends_at'], 0, 4102444800 ) : null;
		if ( ( isset( $input['starts_at'] ) && '' !== $input['starts_at'] && null !== $input['starts_at'] && null === $starts ) || ( isset( $input['ends_at'] ) && '' !== $input['ends_at'] && null !== $input['ends_at'] && null === $ends ) ) {
			$errors[] = array(
				'code'  => 'bad_date',
				'field' => 'dates',
			);
		}
		if ( null !== $starts && null !== $ends && $ends <= $starts ) {
			$errors[] = array(
				'code'  => 'end_before_start',
				'field' => 'dates',
			);
		}

		$scope_in = isset( $input['scope'] ) && is_array( $input['scope'] ) ? $input['scope'] : array();
		$mode     = isset( $scope_in['mode'] ) && in_array( $scope_in['mode'], array( 'all', 'products', 'categories' ), true ) ? $scope_in['mode'] : 'all';
		$scope    = array(
			'mode'         => $mode,
			'product_ids'  => 'products' === $mode ? self::ids( isset( $scope_in['product_ids'] ) ? $scope_in['product_ids'] : array() ) : array(),
			'category_ids' => 'categories' === $mode ? self::ids( isset( $scope_in['category_ids'] ) ? $scope_in['category_ids'] : array() ) : array(),
		);
		if ( 'bundle' !== $type && ( ( 'products' === $mode && empty( $scope['product_ids'] ) ) || ( 'categories' === $mode && empty( $scope['category_ids'] ) ) ) ) {
			$errors[] = array(
				'code'  => 'scope_empty',
				'field' => 'scope',
			);
		}

		$cond_in    = isset( $input['conditions'] ) && is_array( $input['conditions'] ) ? $input['conditions'] : array();
		$conditions = array(
			'min_qty'          => null,
			'min_subtotal'     => null,
			'wilaya_ids'       => self::ids( isset( $cond_in['wilaya_ids'] ) ? $cond_in['wilaya_ids'] : array() ),
			'delivery_methods' => array(),
			'weekdays'         => array(),
		);
		if ( isset( $cond_in['min_qty'] ) && '' !== $cond_in['min_qty'] && null !== $cond_in['min_qty'] ) {
			$conditions['min_qty'] = self::int_in( $cond_in['min_qty'], 1, self::MAX_QTY );
			if ( null === $conditions['min_qty'] ) {
				$errors[] = array(
					'code'  => 'bad_condition',
					'field' => 'min_qty',
				);
			}
		}
		if ( isset( $cond_in['min_subtotal'] ) && '' !== $cond_in['min_subtotal'] && null !== $cond_in['min_subtotal'] ) {
			$conditions['min_subtotal'] = self::int_in( $cond_in['min_subtotal'], 1, self::MAX_MONEY );
			if ( null === $conditions['min_subtotal'] ) {
				$errors[] = array(
					'code'  => 'bad_condition',
					'field' => 'min_subtotal',
				);
			}
		}
		foreach ( isset( $cond_in['delivery_methods'] ) && is_array( $cond_in['delivery_methods'] ) ? $cond_in['delivery_methods'] : array() as $m ) {
			if ( is_string( $m ) && preg_match( '/^[a-z][a-z0-9_]{0,29}$/', $m ) ) {
				$conditions['delivery_methods'][ $m ] = $m;
			}
		}
		$conditions['delivery_methods'] = array_values( $conditions['delivery_methods'] );
		foreach ( isset( $cond_in['weekdays'] ) && is_array( $cond_in['weekdays'] ) ? $cond_in['weekdays'] : array() as $d ) {
			$day = self::int_in( $d, 0, 6 );
			if ( null !== $day ) {
				$conditions['weekdays'][ $day ] = $day;
			}
		}
		sort( $conditions['weekdays'] );
		$conditions['weekdays'] = array_values( $conditions['weekdays'] );

		$offer = array(
			'label'        => $label,
			'type'         => $type,
			'status'       => $status,
			'priority'     => $priority,
			'exclusive'    => ! empty( $input['exclusive'] ) && 'false' !== $input['exclusive'] && '0' !== $input['exclusive'],
			'selectable'   => ! empty( $input['selectable'] ) && 'false' !== $input['selectable'] && '0' !== $input['selectable'],
			'starts_at'    => $starts,
			'ends_at'      => $ends,
			'scope'        => $scope,
			'conditions'   => $conditions,
			'tiers'        => array(),
			'items'        => array(),
			'bundle_price' => null,
			'gift'         => null,
			'action'       => null,
		);

		switch ( $type ) {
			case 'qty_discount':
				$offer['tiers'] = self::normalize_tiers( isset( $input['tiers'] ) ? $input['tiers'] : array(), $errors );
				break;
			case 'bundle':
				$offer['items'] = self::normalize_items( isset( $input['items'] ) ? $input['items'] : array(), $errors );
				$bp             = isset( $input['bundle_price'] ) && is_array( $input['bundle_price'] ) ? $input['bundle_price'] : array();
				$kind           = isset( $bp['kind'] ) && in_array( $bp['kind'], array( 'price', 'percent' ), true ) ? $bp['kind'] : '';
				$value          = 'percent' === $kind ? self::rule_value( 'percent', isset( $bp['value'] ) ? $bp['value'] : null ) : self::rule_value( 'unit_price', isset( $bp['value'] ) ? $bp['value'] : null );
				if ( '' === $kind || null === $value ) {
					$errors[] = array(
						'code'  => 'bad_bundle_price',
						'field' => 'bundle_price',
					);
				} else {
					$offer['bundle_price'] = array(
						'kind'  => $kind,
						'value' => $value,
					);
				}
				break;
			case 'free_gift':
				$offer['gift'] = self::product_ref( isset( $input['gift'] ) ? $input['gift'] : null, 'bad_gift', $errors );
				break;
			default:
				$offer['action'] = self::normalize_action( isset( $input['action'] ) ? $input['action'] : null, $errors );
		}

		return array(
			'definition' => $offer,
			'errors'     => $errors,
		);
	}

	/**
	 * Quantity tiers.
	 *
	 * @param mixed $input  Raw list.
	 * @param array $errors Errors (by reference).
	 * @return array
	 */
	private static function normalize_tiers( $input, array &$errors ) {
		$tiers = array();
		$seen  = array();
		foreach ( is_array( $input ) ? array_slice( array_values( $input ), 0, self::MAX_TIERS ) : array() as $row ) {
			$row  = is_array( $row ) ? $row : array();
			$min  = self::int_in( isset( $row['min_qty'] ) ? $row['min_qty'] : null, 1, self::MAX_QTY );
			$kind = isset( $row['kind'] ) && in_array( $row['kind'], self::KINDS, true ) ? $row['kind'] : '';
			$val  = '' === $kind ? null : self::rule_value( $kind, isset( $row['value'] ) ? $row['value'] : null );
			if ( null === $min || null === $val ) {
				$errors[] = array(
					'code'  => 'bad_tier',
					'field' => 'tiers',
				);
				continue;
			}
			if ( isset( $seen[ $min ] ) ) {
				$errors[] = array(
					'code'  => 'duplicate_tier',
					'field' => 'tiers',
				);
				continue;
			}
			$seen[ $min ] = true;
			$tiers[]      = array(
				'min_qty' => $min,
				'kind'    => $kind,
				'value'   => $val,
			);
		}
		if ( empty( $tiers ) ) {
			$errors[] = array(
				'code'  => 'tiers_required',
				'field' => 'tiers',
			);
		}
		usort(
			$tiers,
			static function ( $a, $b ) {
				return $a['min_qty'] - $b['min_qty'];
			}
		);
		return $tiers;
	}

	/**
	 * Bundle items.
	 *
	 * @param mixed $input  Raw list.
	 * @param array $errors Errors (by reference).
	 * @return array
	 */
	private static function normalize_items( $input, array &$errors ) {
		$items = array();
		$seen  = array();
		foreach ( is_array( $input ) ? array_slice( array_values( $input ), 0, self::MAX_ITEMS ) : array() as $row ) {
			$item = self::product_ref( $row, 'bad_item', $errors );
			if ( null === $item ) {
				continue;
			}
			$key = $item['product_id'] . ':' . $item['variation_id'];
			if ( isset( $seen[ $key ] ) ) {
				$errors[] = array(
					'code'  => 'duplicate_item',
					'field' => 'items',
				);
				continue;
			}
			$seen[ $key ] = true;
			$items[]      = $item;
		}
		if ( count( $items ) < 2 ) {
			$errors[] = array(
				'code'  => 'bundle_needs_two_items',
				'field' => 'items',
			);
		}
		return $items;
	}

	/**
	 * The single action of a custom rule.
	 *
	 * @param mixed $input  Raw action.
	 * @param array $errors Errors (by reference).
	 * @return array|null
	 */
	private static function normalize_action( $input, array &$errors ) {
		$input = is_array( $input ) ? $input : array();
		$type  = isset( $input['type'] ) && in_array( $input['type'], self::ACTIONS, true ) ? $input['type'] : '';
		if ( '' === $type ) {
			$errors[] = array(
				'code'  => 'bad_action',
				'field' => 'action',
			);
			return null;
		}
		if ( 'free_delivery' === $type ) {
			return array(
				'type'  => $type,
				'value' => 0,
				'gift'  => null,
			);
		}
		if ( 'add_gift' === $type ) {
			$gift = self::product_ref( isset( $input['gift'] ) ? $input['gift'] : null, 'bad_gift', $errors );
			return array(
				'type'  => $type,
				'value' => 0,
				'gift'  => $gift,
			);
		}
		$value = self::rule_value( $type, isset( $input['value'] ) ? $input['value'] : null );
		if ( null === $value ) {
			$errors[] = array(
				'code'  => 'bad_action_value',
				'field' => 'action',
			);
			$value    = 0;
		}
		return array(
			'type'  => $type,
			'value' => $value,
			'gift'  => null,
		);
	}
}
