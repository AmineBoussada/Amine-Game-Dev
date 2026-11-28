<?php
/**
 * Builds the pure engine's inputs from WooCommerce and delivery data, and
 * runs it. Phase 5's order flow uses the same entry point, so the order
 * total can never differ from what the simulator shows.
 *
 * The browser never supplies a price: only product ids, quantity, the
 * area, the delivery method and the chosen offer id.
 *
 * Free-delivery threshold (Delivery settings) is evaluated on the subtotal
 * before offers; offers then adjust the resulting fee.
 *
 * @package TopSolution
 */

namespace TopSolution\Offers;

use TopSolution\Core\Settings;
use TopSolution\Delivery\DeliveryService;
use TopSolution\Delivery\Money;

defined( 'ABSPATH' ) || exit;

/**
 * WordPress side of pricing.
 */
class EngineInputs {

	/**
	 * Price a single-product order (the landing-page case).
	 *
	 * @param array $args product_id, variation_id, qty, wilaya_id, commune_id, method, chosen_offer_id.
	 * @return array Engine result plus `error_product` when the product cannot be sold.
	 */
	public static function price_single( array $args ) {
		$args    = array_merge(
			array(
				'product_id'      => 0,
				'variation_id'    => 0,
				'qty'             => 1,
				'wilaya_id'       => 0,
				'commune_id'      => 0,
				'method'          => '',
				'chosen_offer_id' => 0,
			),
			$args
		);
		$product = function_exists( 'wc_get_product' ) ? wc_get_product( $args['variation_id'] ? (int) $args['variation_id'] : (int) $args['product_id'] ) : null;
		if ( ! $product || ! $product->is_purchasable() || '' === $product->get_price() ) {
			return array(
				'ok'       => false,
				'error'    => 'product_unavailable',
				'lines'    => array(),
				'gifts'    => array(),
				'applied'  => array(),
				'rejected' => array(),
			);
		}
		$decimals = DeliveryService::decimals();
		$unit     = Money::to_minor( (string) $product->get_price(), $decimals );
		$parent   = $product->get_parent_id() ? wc_get_product( $product->get_parent_id() ) : $product;
		if ( null === $unit ) {
			return array(
				'ok'       => false,
				'error'    => 'bad_price',
				'lines'    => array(),
				'gifts'    => array(),
				'applied'  => array(),
				'rejected' => array(),
			);
		}
		$qty      = (int) $args['qty'];
		$subtotal = $unit * max( 1, $qty );

		$fee = null;
		if ( $args['method'] && $args['wilaya_id'] ) {
			$quote = DeliveryService::quote( (string) $args['method'], (int) $args['wilaya_id'], (int) $args['commune_id'], $subtotal );
			$fee   = $quote['available'] ? $quote['fee_minor'] : null;
		}
		$request = array(
			'lines'           => array(
				array(
					'id'           => '1',
					'product_id'   => $parent ? (int) $parent->get_id() : (int) $args['product_id'],
					'variation_id' => $product->is_type( 'variation' ) ? (int) $product->get_id() : 0,
					'category_ids' => $parent ? array_map( 'intval', $parent->get_category_ids() ) : array(),
					'qty'          => $qty,
					'unit_minor'   => $unit,
				),
			),
			'chosen_offer_id' => (int) $args['chosen_offer_id'],
			'delivery'        => array(
				'method'    => (string) $args['method'],
				'wilaya_id' => (int) $args['wilaya_id'],
				'fee_minor' => $fee,
			),
			'now'             => time(),
			'weekday'         => (int) wp_date( 'w' ),
		);
		return PricingEngine::price( $request, self::active_offers(), self::engine_settings() );
	}

	/**
	 * Engine settings.
	 *
	 * @return array
	 */
	public static function engine_settings() {
		return array(
			'max_gifts_per_order' => (int) Settings::get( 'max_gifts_per_order' ),
			'min_price_percent'   => (int) Settings::get( 'min_price_percent' ),
		);
	}

	/**
	 * Active offers with gift availability resolved from WooCommerce stock.
	 *
	 * @return array[]
	 */
	public static function active_offers() {
		$offers = array();
		foreach ( OfferRepository::all() as $offer ) {
			if ( 'active' !== $offer['status'] ) {
				continue;
			}
			$gift = 'free_gift' === $offer['type'] ? $offer['gift'] : ( 'custom_rule' === $offer['type'] && 'add_gift' === $offer['action']['type'] ? $offer['action']['gift'] : null );
			if ( $gift ) {
				$offer['gift_available'] = self::gift_available( $gift );
			}
			$offers[] = $offer;
		}
		return $offers;
	}

	/**
	 * Is the gift product purchasable and in stock for the needed quantity?
	 *
	 * @param array $gift product_id, variation_id, qty.
	 * @return bool
	 */
	public static function gift_available( array $gift ) {
		$product = wc_get_product( $gift['variation_id'] ? $gift['variation_id'] : $gift['product_id'] );
		if ( ! $product || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
			return false;
		}
		if ( $product->managing_stock() ) {
			$stock = $product->get_stock_quantity();
			return null === $stock || $stock >= $gift['qty'] || $product->backorders_allowed();
		}
		return true;
	}
}
