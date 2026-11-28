<?php
/**
 * Pricing engine: the only authority for prices. Pure function of its
 * inputs (no WordPress, no database, no HTML): same request + same offers +
 * same settings => same result. All amounts are integer minor units;
 * percentages are basis points (1 % = 100) rounded half-up per unit.
 *
 * Request:
 *   lines[]          id, product_id, variation_id, category_ids[], qty, unit_minor
 *   chosen_offer_id  int (0 = none)
 *   delivery         method (string), wilaya_id (int), fee_minor (int|null = none selected)
 *   now              Unix timestamp
 *   weekday          0-6 in the store time zone
 * Offers: normalised definitions (OfferSchema) plus `id`, `version`, and for
 *   gifts `gift_available` (bool, false = out of stock).
 * Settings: max_gifts_per_order (int), min_price_percent (0-100, 0 = off).
 *
 * Resolution (OFFER_ENGINE_SPEC section 5): an exclusive offer wins alone;
 * otherwise at most one price offer per line, gifts up to the limit, the
 * best delivery offer and the best order-level discount. Ties are broken
 * deterministically (priority, then discount, then lowest id).
 *
 * @package TopSolution
 */

namespace TopSolution\Offers;

defined( 'ABSPATH' ) || exit;

/**
 * Computes prices.
 */
class PricingEngine {

	const VERSION   = 1;
	const MAX_LINES = 20;

	/**
	 * Price a request.
	 *
	 * @param array $request  Request.
	 * @param array $offers   Offers.
	 * @param array $settings Settings.
	 * @return array Result (see result()).
	 */
	public static function price( array $request, array $offers, array $settings ) {
		$lines = self::read_lines( $request );
		if ( is_string( $lines ) ) {
			return self::failure( $lines );
		}
		$delivery = isset( $request['delivery'] ) && is_array( $request['delivery'] ) ? $request['delivery'] : array();
		$ctx      = array(
			'now'      => isset( $request['now'] ) ? (int) $request['now'] : 0,
			'weekday'  => isset( $request['weekday'] ) ? (int) $request['weekday'] : -1,
			'method'   => isset( $delivery['method'] ) ? (string) $delivery['method'] : '',
			'wilaya'   => isset( $delivery['wilaya_id'] ) ? (int) $delivery['wilaya_id'] : 0,
			'fee'      => isset( $delivery['fee_minor'] ) && null !== $delivery['fee_minor'] ? max( 0, (int) $delivery['fee_minor'] ) : null,
			'chosen'   => isset( $request['chosen_offer_id'] ) ? (int) $request['chosen_offer_id'] : 0,
			'max_gift' => isset( $settings['max_gifts_per_order'] ) ? max( 0, (int) $settings['max_gifts_per_order'] ) : 1,
			'floor'    => isset( $settings['min_price_percent'] ) ? min( 100, max( 0, (int) $settings['min_price_percent'] ) ) : 0,
		);

		usort(
			$offers,
			static function ( $a, $b ) {
				return (int) $a['id'] - (int) $b['id'];
			}
		);

		$rejected = array();
		$eligible = array();
		foreach ( $offers as $offer ) {
			if ( 'active' !== $offer['status'] ) {
				continue; // Drafts and archived offers are invisible, not "rejected".
			}
			$check = self::check( $offer, $lines, $ctx );
			if ( is_string( $check ) ) {
				$rejected[] = self::reject( $offer, $check );
				continue;
			}
			$offer['_check'] = $check;
			$eligible[]      = $offer;
		}

		// Customer choice: the chosen offer must be eligible and selectable; never silently swapped.
		$chosen = $ctx['chosen'];
		if ( $chosen > 0 ) {
			$found = false;
			foreach ( $eligible as $offer ) {
				if ( (int) $offer['id'] === $chosen && $offer['selectable'] ) {
					$found = true;
				}
			}
			if ( ! $found ) {
				$result             = self::failure( 'chosen_offer_invalid' );
				$result['rejected'] = $rejected;
				return $result;
			}
		}
		$pool = array();
		foreach ( $eligible as $offer ) {
			if ( $offer['selectable'] && (int) $offer['id'] !== $chosen ) {
				$rejected[] = self::reject( $offer, 'not_chosen' );
				continue;
			}
			$pool[] = $offer;
		}

		// An exclusive offer wins alone.
		$exclusive = array_values(
			array_filter(
				$pool,
				static function ( $o ) {
					return $o['exclusive'];
				}
			)
		);
		if ( $exclusive ) {
			usort(
				$exclusive,
				static function ( $a, $b ) {
					return self::first_diff( $b['priority'] - $a['priority'], (int) $a['id'] - (int) $b['id'] );
				}
			);
			$winner = $exclusive[0];
			foreach ( $pool as $offer ) {
				if ( (int) $offer['id'] !== (int) $winner['id'] ) {
					$rejected[] = self::reject( $offer, 'exclusive_won' );
				}
			}
			$pool = array( $winner );
		}

		$state    = array(
			'claimed' => array(),
			'applied' => array(),
		);
		$by_group = array(
			'price'    => array(),
			'gift'     => array(),
			'delivery' => array(),
			'order'    => array(),
		);
		foreach ( $pool as $offer ) {
			$by_group[ OfferSchema::group( $offer ) ][] = $offer;
		}

		// 1. Price offers: chosen first, then priority, then biggest discount, then lowest id.
		$price = $by_group['price'];
		foreach ( $price as $i => $offer ) {
			$price[ $i ]['_saving'] = self::saving( $offer, $lines );
		}
		usort(
			$price,
			static function ( $a, $b ) use ( $chosen ) {
				$ca = (int) $a['id'] === $chosen ? 0 : 1;
				$cb = (int) $b['id'] === $chosen ? 0 : 1;
				return self::first_diff( $ca - $cb, $b['priority'] - $a['priority'], $b['_saving'] <=> $a['_saving'], (int) $a['id'] - (int) $b['id'] );
			}
		);
		foreach ( $price as $offer ) {
			$claim = self::claim( $offer, $lines, $state['claimed'] );
			if ( null === $claim ) {
				$rejected[] = self::reject( $offer, 'outranked' );
				continue;
			}
			foreach ( $claim as $line_id => $total ) {
				$state['claimed'][ $line_id ] = array(
					'total'    => $total,
					'offer_id' => (int) $offer['id'],
				);
			}
			$state['applied'][] = $offer;
		}

		// Result lines, with the floor guard.
		$out_lines   = array();
		$base_sub    = 0;
		$lines_total = 0;
		$floor_sum   = 0;
		foreach ( $lines as $line ) {
			$base_total = $line['unit_minor'] * $line['qty'];
			$final      = $base_total;
			$offer_id   = 0;
			$floored    = false;
			if ( isset( $state['claimed'][ $line['id'] ] ) ) {
				$final    = $state['claimed'][ $line['id'] ]['total'];
				$offer_id = $state['claimed'][ $line['id'] ]['offer_id'];
			}
			$floor_total = $ctx['floor'] > 0 ? intdiv( $base_total * $ctx['floor'] + 99, 100 ) : 0;
			if ( $final < $floor_total ) {
				$final   = $floor_total;
				$floored = true;
			}
			$final        = max( 0, min( $final, $base_total ) );
			$base_sub    += $base_total;
			$lines_total += $final;
			$floor_sum   += $floor_total;
			$out_lines[]  = array(
				'id'                => $line['id'],
				'product_id'        => $line['product_id'],
				'variation_id'      => $line['variation_id'],
				'qty'               => $line['qty'],
				'base_unit_minor'   => $line['unit_minor'],
				'final_unit_minor'  => intdiv( $final, $line['qty'] ),
				'base_total_minor'  => $base_total,
				'final_total_minor' => $final,
				'offer_id'          => $offer_id,
				'floor_applied'     => $floored,
			);
		}

		// 2. Gifts: all eligible up to the limit.
		$gifts       = array();
		$gift_offers = $by_group['gift'];
		usort(
			$gift_offers,
			static function ( $a, $b ) {
				return self::first_diff( $b['priority'] - $a['priority'], (int) $a['id'] - (int) $b['id'] );
			}
		);
		foreach ( $gift_offers as $offer ) {
			if ( count( $gifts ) >= $ctx['max_gift'] ) {
				$rejected[] = self::reject( $offer, 'gift_limit' );
				continue;
			}
			$gift               = 'free_gift' === $offer['type'] ? $offer['gift'] : $offer['action']['gift'];
			$gifts[]            = array(
				'offer_id'     => (int) $offer['id'],
				'product_id'   => $gift['product_id'],
				'variation_id' => $gift['variation_id'],
				'qty'          => $gift['qty'],
			);
			$state['applied'][] = $offer;
		}

		// 3. Order-level fixed discount: the largest wins, capped so lines never go below the floor.
		$order_discount = 0;
		$cap            = max( 0, $lines_total - $floor_sum );
		$order_offers   = $by_group['order'];
		usort(
			$order_offers,
			static function ( $a, $b ) use ( $cap ) {
				$va = min( $cap, $a['action']['value'] );
				$vb = min( $cap, $b['action']['value'] );
				return self::first_diff( $vb <=> $va, $b['priority'] - $a['priority'], (int) $a['id'] - (int) $b['id'] );
			}
		);
		foreach ( $order_offers as $i => $offer ) {
			$amount = min( $cap, $offer['action']['value'] );
			if ( 0 === $i && $amount > 0 ) {
				$order_discount     = $amount;
				$state['applied'][] = $offer;
			} else {
				$rejected[] = self::reject( $offer, 0 === $i ? 'not_a_discount' : 'outranked' );
			}
		}

		// 4. Delivery adjustment: the biggest reduction wins.
		$fee_base = $ctx['fee'];
		$fee_off  = 0;
		$d_offers = $by_group['delivery'];
		foreach ( $d_offers as $i => $offer ) {
			$d_offers[ $i ]['_off'] = null === $fee_base ? 0 : self::delivery_reduction( $offer['action'], $fee_base );
		}
		usort(
			$d_offers,
			static function ( $a, $b ) {
				return self::first_diff( $b['_off'] <=> $a['_off'], $b['priority'] - $a['priority'], (int) $a['id'] - (int) $b['id'] );
			}
		);
		foreach ( $d_offers as $i => $offer ) {
			if ( null === $fee_base ) {
				$rejected[] = self::reject( $offer, 'no_delivery_fee' );
			} elseif ( 0 === $i && $offer['_off'] > 0 ) {
				$fee_off            = $offer['_off'];
				$state['applied'][] = $offer;
			} else {
				$rejected[] = self::reject( $offer, 0 === $i ? 'not_a_discount' : 'outranked' );
			}
		}
		$fee_final = null === $fee_base ? 0 : $fee_base - $fee_off;

		$applied = array();
		foreach ( $state['applied'] as $offer ) {
			$applied[] = array(
				'offer_id' => (int) $offer['id'],
				'version'  => isset( $offer['version'] ) ? (int) $offer['version'] : 1,
				'label'    => $offer['label'],
				'type'     => $offer['type'],
				'group'    => OfferSchema::group( $offer ),
			);
		}
		usort(
			$rejected,
			static function ( $a, $b ) {
				return $a['offer_id'] - $b['offer_id'];
			}
		);

		return array(
			'ok'                   => true,
			'error'                => '',
			'engine_version'       => self::VERSION,
			'lines'                => $out_lines,
			'gifts'                => $gifts,
			'subtotal_minor'       => $base_sub,
			'lines_total_minor'    => $lines_total,
			'order_discount_minor' => $order_discount,
			'discount_total_minor' => $base_sub - $lines_total + $order_discount,
			'delivery'             => array(
				'selected'        => null !== $fee_base,
				'base_fee_minor'  => null === $fee_base ? 0 : $fee_base,
				'discount_minor'  => $fee_off,
				'final_fee_minor' => $fee_final,
			),
			'grand_total_minor'    => $lines_total - $order_discount + $fee_final,
			'applied'              => $applied,
			'rejected'             => $rejected,
		);
	}

	/**
	 * First non-zero difference: a multi-key comparator for usort().
	 *
	 * @param int ...$diffs Differences, most important first.
	 * @return int
	 */
	private static function first_diff( ...$diffs ) {
		foreach ( $diffs as $diff ) {
			if ( 0 !== $diff ) {
				return $diff;
			}
		}
		return 0;
	}

	/**
	 * Failure result.
	 *
	 * @param string $error Error code.
	 * @return array
	 */
	private static function failure( $error ) {
		return array(
			'ok'             => false,
			'error'          => $error,
			'engine_version' => self::VERSION,
			'lines'          => array(),
			'gifts'          => array(),
			'applied'        => array(),
			'rejected'       => array(),
		);
	}

	/**
	 * Rejection record.
	 *
	 * @param array  $offer  Offer.
	 * @param string $reason Reason code.
	 * @return array
	 */
	private static function reject( array $offer, $reason ) {
		return array(
			'offer_id' => (int) $offer['id'],
			'label'    => $offer['label'],
			'reason'   => $reason,
		);
	}

	/**
	 * Validate and normalise the request lines.
	 *
	 * @param array $request Request.
	 * @return array|string Lines, or an error code.
	 */
	private static function read_lines( array $request ) {
		$raw = isset( $request['lines'] ) && is_array( $request['lines'] ) ? array_values( $request['lines'] ) : array();
		if ( empty( $raw ) || count( $raw ) > self::MAX_LINES ) {
			return 'bad_lines';
		}
		$lines = array();
		$seen  = array();
		foreach ( $raw as $i => $line ) {
			$id   = isset( $line['id'] ) ? (string) $line['id'] : (string) $i;
			$qty  = isset( $line['qty'] ) ? $line['qty'] : 0;
			$unit = isset( $line['unit_minor'] ) ? $line['unit_minor'] : -1;
			if ( ! is_int( $qty ) || $qty < 1 || $qty > OfferSchema::MAX_QTY ) {
				return 'bad_quantity';
			}
			if ( ! is_int( $unit ) || $unit < 0 || $unit > OfferSchema::MAX_MONEY ) {
				return 'bad_price';
			}
			if ( isset( $seen[ $id ] ) ) {
				return 'bad_lines';
			}
			$seen[ $id ] = true;
			$lines[]     = array(
				'id'           => $id,
				'product_id'   => isset( $line['product_id'] ) ? (int) $line['product_id'] : 0,
				'variation_id' => isset( $line['variation_id'] ) ? (int) $line['variation_id'] : 0,
				'category_ids' => isset( $line['category_ids'] ) && is_array( $line['category_ids'] ) ? array_map( 'intval', $line['category_ids'] ) : array(),
				'qty'          => $qty,
				'unit_minor'   => $unit,
			);
		}
		return $lines;
	}

	/**
	 * Lines inside an offer's scope.
	 *
	 * @param array $offer Offer.
	 * @param array $lines Lines.
	 * @return array
	 */
	private static function scoped( array $offer, array $lines ) {
		$scope = $offer['scope'];
		return array_values(
			array_filter(
				$lines,
				static function ( $line ) use ( $scope ) {
					if ( 'products' === $scope['mode'] ) {
						return in_array( $line['product_id'], $scope['product_ids'], true );
					}
					if ( 'categories' === $scope['mode'] ) {
						return (bool) array_intersect( $line['category_ids'], $scope['category_ids'] );
					}
					return true;
				}
			)
		);
	}

	/**
	 * Lines that match each bundle item (null when an item is missing or short).
	 *
	 * @param array $offer Bundle offer.
	 * @param array $lines Lines.
	 * @return array|null {sets:int, map:array<item index, line>}
	 */
	private static function bundle_match( array $offer, array $lines ) {
		$sets = PHP_INT_MAX;
		$map  = array();
		$used = array();
		foreach ( $offer['items'] as $i => $item ) {
			$found = null;
			foreach ( $lines as $line ) {
				if ( isset( $used[ $line['id'] ] ) || $line['product_id'] !== $item['product_id'] ) {
					continue;
				}
				if ( $item['variation_id'] > 0 && $line['variation_id'] !== $item['variation_id'] ) {
					continue;
				}
				if ( $line['qty'] >= $item['qty'] ) {
					$found = $line;
					break;
				}
			}
			if ( null === $found ) {
				return null;
			}
			$used[ $found['id'] ] = true;
			$map[ $i ]            = $found;
			$sets                 = min( $sets, intdiv( $found['qty'], $item['qty'] ) );
		}
		return $sets > 0 ? array(
			'sets' => $sets,
			'map'  => $map,
		) : null;
	}

	/**
	 * Eligibility. Returns a reason string when not eligible, else details.
	 *
	 * @param array $offer Offer.
	 * @param array $lines Lines.
	 * @param array $ctx   Context.
	 * @return array|string
	 */
	private static function check( array $offer, array $lines, array $ctx ) {
		if ( null !== $offer['starts_at'] && $ctx['now'] < $offer['starts_at'] ) {
			return 'not_started';
		}
		if ( null !== $offer['ends_at'] && $ctx['now'] >= $offer['ends_at'] ) {
			return 'ended';
		}
		$cond   = $offer['conditions'];
		$result = array();

		if ( 'bundle' === $offer['type'] ) {
			$match = self::bundle_match( $offer, $lines );
			if ( null === $match ) {
				return 'bundle_incomplete';
			}
			$result['bundle'] = $match;
			$in_scope         = array_values(
				array_map(
					static function ( $l ) {
						return $l;
					},
					$match['map']
				)
			);
		} else {
			$in_scope = self::scoped( $offer, $lines );
			if ( empty( $in_scope ) ) {
				return 'scope_mismatch';
			}
		}
		$qty      = 0;
		$subtotal = 0;
		foreach ( $in_scope as $line ) {
			$qty      += $line['qty'];
			$subtotal += $line['qty'] * $line['unit_minor'];
		}
		if ( null !== $cond['min_qty'] && $qty < $cond['min_qty'] ) {
			return 'below_min_qty';
		}
		if ( null !== $cond['min_subtotal'] && $subtotal < $cond['min_subtotal'] ) {
			return 'below_min_subtotal';
		}
		if ( $cond['wilaya_ids'] ) {
			if ( $ctx['wilaya'] <= 0 ) {
				return 'needs_wilaya';
			}
			if ( ! in_array( $ctx['wilaya'], $cond['wilaya_ids'], true ) ) {
				return 'wilaya_not_included';
			}
		}
		if ( $cond['delivery_methods'] && ! in_array( $ctx['method'], $cond['delivery_methods'], true ) ) {
			return 'delivery_method_not_included';
		}
		if ( $cond['weekdays'] && ! in_array( $ctx['weekday'], $cond['weekdays'], true ) ) {
			return 'weekday_not_included';
		}

		if ( 'qty_discount' === $offer['type'] ) {
			$tier = null;
			foreach ( $offer['tiers'] as $candidate ) {
				if ( $candidate['min_qty'] <= $qty ) {
					$tier = $candidate;
				}
			}
			if ( null === $tier ) {
				return 'below_min_qty';
			}
			$result['tier'] = $tier;
		}
		$gift = null;
		if ( 'free_gift' === $offer['type'] ) {
			$gift = $offer['gift'];
		} elseif ( 'custom_rule' === $offer['type'] && 'add_gift' === $offer['action']['type'] ) {
			$gift = $offer['action']['gift'];
		}
		if ( null !== $gift && isset( $offer['gift_available'] ) && ! $offer['gift_available'] ) {
			return 'gift_out_of_stock';
		}
		if ( 'price' === OfferSchema::group( $offer ) && self::saving( $offer + array( '_check' => $result ), $lines ) <= 0 ) {
			return 'not_a_discount';
		}
		return $result;
	}

	/**
	 * Final unit price after a rule.
	 *
	 * @param int    $unit  Base unit.
	 * @param string $kind  percent | fixed_off | unit_price.
	 * @param int    $value Basis points or minor units.
	 * @return int
	 */
	private static function apply_rule( $unit, $kind, $value ) {
		if ( 'percent' === $kind ) {
			return $unit - intdiv( $unit * $value * 2 + OfferSchema::BASIS_POINTS, 2 * OfferSchema::BASIS_POINTS );
		}
		if ( 'fixed_off' === $kind ) {
			return max( 0, $unit - $value );
		}
		return min( $unit, $value ); // unit_price: never raises the price.
	}

	/**
	 * Per-line final totals an offer would produce on given lines.
	 *
	 * @param array $offer Offer (with _check).
	 * @param array $lines Lines the offer may touch.
	 * @return array<string,int>|null line id => final total.
	 */
	private static function effect( array $offer, array $lines ) {
		$totals = array();
		if ( 'bundle' === $offer['type'] ) {
			return self::bundle_effect( $offer, $lines );
		}
		if ( 'qty_discount' === $offer['type'] ) {
			$kind  = $offer['_check']['tier']['kind'];
			$value = $offer['_check']['tier']['value'];
		} else {
			$map   = array(
				'percent_off'    => 'percent',
				'fixed_off_line' => 'fixed_off',
				'set_unit_price' => 'unit_price',
			);
			$kind  = $map[ $offer['action']['type'] ];
			$value = $offer['action']['value'];
		}
		foreach ( self::scoped( $offer, $lines ) as $line ) {
			$totals[ $line['id'] ] = self::apply_rule( $line['unit_minor'], $kind, $value ) * $line['qty'];
		}
		return $totals;
	}

	/**
	 * Bundle: full sets get the bundle price, spread over the covered lines
	 * (largest remainder, so the sum is exact); extra units keep their price.
	 *
	 * @param array $offer Bundle offer.
	 * @param array $lines Lines.
	 * @return array<string,int>|null
	 */
	private static function bundle_effect( array $offer, array $lines ) {
		$match = self::bundle_match( $offer, $lines );
		if ( null === $match ) {
			return null;
		}
		$sets     = $match['sets'];
		$base_cov = 0;
		$cov      = array();
		foreach ( $offer['items'] as $i => $item ) {
			$line      = $match['map'][ $i ];
			$units     = $item['qty'] * $sets;
			$cov[ $i ] = $units * $line['unit_minor'];
			$base_cov += $cov[ $i ];
		}
		$bp = $offer['bundle_price'];
		if ( 'price' === $bp['kind'] ) {
			$target = $bp['value'] * $sets;
		} else {
			$target = $base_cov - intdiv( $base_cov * $bp['value'] * 2 + OfferSchema::BASIS_POINTS, 2 * OfferSchema::BASIS_POINTS );
		}
		if ( $target >= $base_cov ) {
			return array(); // Not a discount.
		}
		// Largest-remainder allocation of $target across covered lines by their base share.
		$alloc = array();
		$rem   = array();
		$given = 0;
		foreach ( $cov as $i => $c ) {
			$exact       = 0 === $base_cov ? 0 : intdiv( $target * $c, $base_cov );
			$alloc[ $i ] = $exact;
			$rem[ $i ]   = 0 === $base_cov ? 0 : ( $target * $c ) % $base_cov;
			$given      += $exact;
		}
		$left = $target - $given;
		arsort( $rem );
		foreach ( array_keys( $rem ) as $i ) {
			if ( $left <= 0 ) {
				break;
			}
			++$alloc[ $i ];
			--$left;
		}
		$totals = array();
		foreach ( $offer['items'] as $i => $item ) {
			$line                  = $match['map'][ $i ];
			$extra                 = $line['qty'] - $item['qty'] * $sets;
			$totals[ $line['id'] ] = $alloc[ $i ] + $extra * $line['unit_minor'];
		}
		return $totals;
	}

	/**
	 * Total saving of a price offer on the whole request.
	 *
	 * @param array $offer Offer.
	 * @param array $lines Lines.
	 * @return int
	 */
	private static function saving( array $offer, array $lines ) {
		$check           = isset( $offer['_check'] ) ? $offer['_check'] : array();
		$offer['_check'] = $check;
		$effect          = self::effect( $offer, $lines );
		if ( empty( $effect ) ) {
			return 0;
		}
		$saving = 0;
		foreach ( $lines as $line ) {
			if ( isset( $effect[ $line['id'] ] ) ) {
				$saving += $line['unit_minor'] * $line['qty'] - $effect[ $line['id'] ];
			}
		}
		return $saving;
	}

	/**
	 * Claim lines for a price offer. A bundle needs all its lines free; other
	 * offers take the free lines of their scope.
	 *
	 * @param array $offer   Offer.
	 * @param array $lines   Lines.
	 * @param array $claimed Already claimed lines.
	 * @return array<string,int>|null
	 */
	private static function claim( array $offer, array $lines, array $claimed ) {
		$free = array_values(
			array_filter(
				$lines,
				static function ( $l ) use ( $claimed ) {
					return ! isset( $claimed[ $l['id'] ] );
				}
			)
		);
		// A bundle needs all its lines free; tiers and thresholds were decided on the whole scope.
		$effect = self::effect( $offer, $free );
		if ( empty( $effect ) ) {
			return null;
		}
		return $effect;
	}

	/**
	 * Delivery reduction of an action on a fee.
	 *
	 * @param array $action Action.
	 * @param int   $fee    Base fee.
	 * @return int
	 */
	private static function delivery_reduction( array $action, $fee ) {
		switch ( $action['type'] ) {
			case 'free_delivery':
				return $fee;
			case 'delivery_percent_off':
				return min( $fee, intdiv( $fee * $action['value'] * 2 + OfferSchema::BASIS_POINTS, 2 * OfferSchema::BASIS_POINTS ) );
			default:
				return min( $fee, $action['value'] );
		}
	}
}
