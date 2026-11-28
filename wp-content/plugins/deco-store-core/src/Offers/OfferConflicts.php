<?php
/**
 * Finds active offers that compete with the same priority, so the owner can
 * break the tie on purpose (the engine would still pick deterministically:
 * bigger saving, then lowest id). Pure function, unit-tested.
 *
 * Only groups where a single offer wins are checked (price, order,
 * delivery); gifts stack. Offers the customer picks (selectable) do not
 * compete until picked, so they are skipped.
 *
 * @package DecoStore
 */

namespace DecoStore\Offers;

defined( 'ABSPATH' ) || exit;

/**
 * Overlap warnings.
 */
class OfferConflicts {

	/**
	 * Products a scope is limited to (null = not limited by product).
	 *
	 * @param array $offer Offer.
	 * @return array{mode:string,products:int[],categories:int[]}
	 */
	private static function area( array $offer ) {
		if ( 'bundle' === $offer['type'] ) {
			return array(
				'mode'       => 'products',
				'products'   => array_column( $offer['items'], 'product_id' ),
				'categories' => array(),
			);
		}
		return array(
			'mode'       => $offer['scope']['mode'],
			'products'   => $offer['scope']['product_ids'],
			'categories' => $offer['scope']['category_ids'],
		);
	}

	/**
	 * Can two offers apply to the same line?
	 *
	 * @param array $a Offer.
	 * @param array $b Offer.
	 * @return bool
	 */
	public static function overlap( array $a, array $b ) {
		$x = self::area( $a );
		$y = self::area( $b );
		if ( 'all' === $x['mode'] || 'all' === $y['mode'] ) {
			return true;
		}
		if ( $x['mode'] === $y['mode'] ) {
			$key = 'products' === $x['mode'] ? 'products' : 'categories';
			return (bool) array_intersect( $x[ $key ], $y[ $key ] );
		}
		return true; // A product may belong to a category: possible overlap.
	}

	/**
	 * Conflicting pairs among offers.
	 *
	 * @param array $offers Normalised offers with `id`.
	 * @return array<int,array{0:int,1:int}> Pairs of offer ids.
	 */
	public static function find( array $offers ) {
		$pairs  = array();
		$active = array_values(
			array_filter(
				$offers,
				static function ( $o ) {
					return 'active' === $o['status'] && ! $o['selectable'] && 'gift' !== OfferSchema::group( $o );
				}
			)
		);
		$total  = count( $active );
		foreach ( $active as $i => $a ) {
			for ( $j = $i + 1; $j < $total; $j++ ) {
				$b = $active[ $j ];
				if ( OfferSchema::group( $a ) !== OfferSchema::group( $b ) || $a['priority'] !== $b['priority'] ) {
					continue;
				}
				if ( self::overlap( $a, $b ) && self::windows_overlap( $a, $b ) ) {
					$pairs[] = array( (int) $a['id'], (int) $b['id'] );
				}
			}
		}
		return $pairs;
	}

	/**
	 * Do the date windows intersect?
	 *
	 * @param array $a Offer.
	 * @param array $b Offer.
	 * @return bool
	 */
	private static function windows_overlap( array $a, array $b ) {
		$a_start = null === $a['starts_at'] ? 0 : $a['starts_at'];
		$b_start = null === $b['starts_at'] ? 0 : $b['starts_at'];
		$a_end   = null === $a['ends_at'] ? PHP_INT_MAX : $a['ends_at'];
		$b_end   = null === $b['ends_at'] ? PHP_INT_MAX : $b['ends_at'];
		return $a_start < $b_end && $b_start < $a_end;
	}
}
