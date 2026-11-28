<?php
/**
 * Creates the WooCommerce order from a validated, priced submission. Uses
 * WooCommerce CRUD objects only (no SQL on orders), so it works with both
 * order storages (HPOS tables and legacy posts).
 *
 * Every amount comes from the pricing engine result. After WooCommerce
 * computes its totals, the order total must equal the engine's grand total
 * to the last minor unit; otherwise the order is deleted and nothing is
 * kept.
 *
 * @package DecoStore
 */

namespace DecoStore\Orders;

use DecoStore\Core\Settings;
use DecoStore\Delivery\DeliveryService;
use DecoStore\Delivery\GeoCode;
use DecoStore\Delivery\GeoRepository;
use DecoStore\Delivery\Money;
use DecoStore\Delivery\PickupRepository;
use DecoStore\Delivery\ShippingMethod;

defined( 'ABSPATH' ) || exit;

/**
 * Order creation.
 */
class OrderFactory {

	const SOURCE = 'landing_page';

	/**
	 * Create the order.
	 *
	 * @param array $in {
	 *     Submission data.
	 *
	 *     @type \WC_Product $product   Product or variation sold.
	 *     @type array       $pricing   Engine result (ok).
	 *     @type array       $mapped    Core values: customer_name, phone, wilaya, commune, address, note, delivery_method, pickup_point.
	 *     @type array       $answers   Extra answers [{key,label,type,value}].
	 *     @type array       $form      Form definition (id, version).
	 *     @type string      $key       Idempotency key.
	 *     @type array       $client    Client meta (ip_hash, submitted_at).
	 *     @type array       $flags     Flags.
	 * }
	 * @return \WC_Order
	 * @throws \RuntimeException When WooCommerce's total differs from the engine's.
	 */
	public static function create( array $in ) {
		$d       = DeliveryService::decimals();
		$money   = static function ( $minor ) use ( $d ) {
			return Money::to_decimal( (int) $minor, $d );
		};
		$pricing = $in['pricing'];
		$mapped  = $in['mapped'];

		$order = wc_create_order(
			array(
				'status'      => 'pending',
				'created_via' => 'ds_landing',
			)
		);
		if ( is_wp_error( $order ) ) {
			throw new \RuntimeException( 'order_create_failed' );
		}

		try {
			// Product line: subtotal = base price, total = price after offers (WooCommerce shows the difference as a discount).
			foreach ( $pricing['lines'] as $line ) {
				$item = new \WC_Order_Item_Product();
				$item->set_product( $in['product'] );
				$item->set_quantity( $line['qty'] );
				$item->set_subtotal( $money( $line['base_total_minor'] ) );
				$item->set_total( $money( $line['final_total_minor'] ) );
				$item->add_meta_data( '_ds_unit_base_price', $money( $line['base_unit_minor'] ), true );
				$item->add_meta_data( '_ds_unit_final_price', $money( $line['final_unit_minor'] ), true );
				if ( $line['offer_id'] ) {
					$item->add_meta_data( '_ds_offer_id', (int) $line['offer_id'], true );
				}
				$order->add_item( $item );
			}

			// Gifts: zero-priced lines.
			$labels = array();
			foreach ( $pricing['applied'] as $a ) {
				$labels[ $a['offer_id'] ] = $a['label'];
			}
			foreach ( $pricing['gifts'] as $gift ) {
				$gift_product = wc_get_product( $gift['variation_id'] ? $gift['variation_id'] : $gift['product_id'] );
				if ( ! $gift_product ) {
					throw new \RuntimeException( 'gift_missing' );
				}
				$item = new \WC_Order_Item_Product();
				$item->set_product( $gift_product );
				$item->set_quantity( $gift['qty'] );
				$item->set_subtotal( '0' );
				$item->set_total( '0' );
				$item->add_meta_data( '_ds_is_gift', '1', true );
				$item->add_meta_data( '_ds_offer_id', (int) $gift['offer_id'], true );
				$item->add_meta_data( __( 'هدية', 'deco-store-core' ), isset( $labels[ $gift['offer_id'] ] ) ? $labels[ $gift['offer_id'] ] : '', true );
				$order->add_item( $item );
			}

			// Order-level discount as a negative fee (visible line with the offer's name).
			if ( $pricing['order_discount_minor'] > 0 ) {
				$label = __( 'خصم', 'deco-store-core' );
				foreach ( $pricing['applied'] as $a ) {
					if ( 'order' === $a['group'] ) {
						$label = $a['label'];
					}
				}
				$fee = new \WC_Order_Item_Fee();
				$fee->set_name( $label );
				$fee->set_tax_status( 'none' );
				$fee->set_amount( $money( -$pricing['order_discount_minor'] ) );
				$fee->set_total( $money( -$pricing['order_discount_minor'] ) );
				$order->add_item( $fee );
			}

			// Delivery line.
			$quote   = $pricing['delivery_quote'];
			$pickup  = $mapped['pickup_point'] ? PickupRepository::get( (int) $mapped['pickup_point'] ) : null;
			$wilaya  = $mapped['wilaya'] ? GeoRepository::get( (int) $mapped['wilaya'] ) : null;
			$commune = $mapped['commune'] ? GeoRepository::get( (int) $mapped['commune'] ) : null;
			if ( $pricing['delivery']['selected'] && $quote ) {
				$ship = new \WC_Order_Item_Shipping();
				$ship->set_method_title( $quote['label'] );
				$ship->set_method_id( ShippingMethod::ID );
				$ship->set_total( $money( $pricing['delivery']['final_fee_minor'] ) );
				$ship->add_meta_data( 'ds_method', $quote['method'], true );
				if ( $pickup ) {
					$ship->add_meta_data( __( 'مكتب الاستلام', 'deco-store-core' ), $pickup['name'], true );
				}
				$order->add_item( $ship );
			}

			// Customer (guest). The whole name goes into the first-name field: no guess about how to split it.
			$address = '' !== (string) $mapped['address'] ? (string) $mapped['address'] : ( $pickup ? $pickup['name'] . ( '' !== $pickup['address'] ? ' — ' . $pickup['address'] : '' ) : '' );
			$state   = $wilaya ? GeoCode::to_wc_state( $wilaya['code'] ) : '';
			$states  = WC()->countries ? WC()->countries->get_states( 'DZ' ) : array();
			$state   = is_array( $states ) && isset( $states[ $state ] ) ? $state : '';
			$city    = $commune ? $commune['name_ar'] : ( $wilaya ? $wilaya['name_ar'] : '' );
			foreach ( array( 'billing', 'shipping' ) as $type ) {
				$order->{"set_{$type}_first_name"}( (string) $mapped['customer_name'] );
				$order->{"set_{$type}_country"}( 'DZ' );
				$order->{"set_{$type}_state"}( $state );
				$order->{"set_{$type}_city"}( $city );
				$order->{"set_{$type}_address_1"}( $address );
				$order->{"set_{$type}_phone"}( (string) $mapped['phone'] );
			}
			if ( '' !== (string) $mapped['note'] ) {
				$order->set_customer_note( (string) $mapped['note'] );
			}

			// Cash on delivery (native gateway).
			$gateways = WC()->payment_gateways() ? WC()->payment_gateways()->payment_gateways() : array();
			$order->set_payment_method( 'cod' );
			$order->set_payment_method_title( isset( $gateways['cod'] ) ? $gateways['cod']->get_title() : __( 'الدفع عند الاستلام', 'deco-store-core' ) );
			$order->set_prices_include_tax( false );
			$order->calculate_totals( false );

			$total = Money::to_minor( (string) $order->get_total(), $d );
			if ( $total !== (int) $pricing['grand_total_minor'] ) {
				throw new \RuntimeException( 'total_mismatch' );
			}

			// Snapshots (underscore meta: hidden from customers and from WooCommerce REST meta_data).
			$order->update_meta_data( '_ds_source', self::SOURCE );
			$order->update_meta_data( '_ds_form_id', (int) $in['form']['id'] );
			$order->update_meta_data( '_ds_form_version', isset( $in['form']['version'] ) ? (int) $in['form']['version'] : 0 );
			$order->update_meta_data( '_ds_form_answers', wp_json_encode( $in['answers'] ) );
			$order->update_meta_data( '_ds_pricing_snapshot', wp_json_encode( self::pricing_snapshot( $pricing, $d ) ) );
			$order->update_meta_data(
				'_ds_delivery_snapshot',
				wp_json_encode(
					array(
						'method'       => $quote ? $quote['method'] : '',
						'method_label' => $quote ? $quote['label'] : '',
						'wilaya_id'    => $wilaya ? $wilaya['id'] : 0,
						'wilaya'       => $wilaya ? $wilaya['code'] . ' ' . $wilaya['name_ar'] : '',
						'commune_id'   => $commune ? $commune['id'] : 0,
						'commune'      => $commune ? $commune['name_ar'] : '',
						'pickup_id'    => $pickup ? $pickup['id'] : 0,
						'pickup'       => $pickup ? $pickup['name'] : '',
						'carrier'      => $pickup ? $pickup['carrier'] : '',
						'rate_id'      => $quote ? (int) $quote['rate_id'] : 0,
						'rate_source'  => $quote ? $quote['source'] : '',
						'base_fee'     => $money( $pricing['delivery']['base_fee_minor'] ),
						'fee'          => $money( $pricing['delivery']['final_fee_minor'] ),
					)
				)
			);
			$order->update_meta_data( '_ds_idempotency_key', (string) $in['key'] );
			$order->update_meta_data( '_ds_client_meta', wp_json_encode( $in['client'] ) );
			$order->update_meta_data( '_ds_flags', wp_json_encode( (object) $in['flags'] ) );
			$order->add_order_note( __( 'طلب من صفحة المنتج (الدفع عند الاستلام).', 'deco-store-core' ) );
			$order->save();

			// Native status change: stock reduction, emails and hooks run as usual.
			$order->update_status( (string) Settings::get( 'initial_order_status' ) );
		} catch ( \Throwable $e ) {
			$order->delete( true );
			throw new \RuntimeException( $e->getMessage() ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Internal code, never printed.
		}
		return $order;
	}

	/**
	 * What the order keeps of the price computation (decimal strings for humans, minor units for audits).
	 *
	 * @param array $p Engine result.
	 * @param int   $d Decimals.
	 * @return array
	 */
	public static function pricing_snapshot( array $p, $d ) {
		return array(
			'engine_version'       => $p['engine_version'],
			'decimals'             => (int) $d,
			'lines'                => $p['lines'],
			'gifts'                => $p['gifts'],
			'subtotal_minor'       => $p['subtotal_minor'],
			'lines_total_minor'    => $p['lines_total_minor'],
			'order_discount_minor' => $p['order_discount_minor'],
			'discount_total_minor' => $p['discount_total_minor'],
			'delivery'             => $p['delivery'],
			'grand_total_minor'    => $p['grand_total_minor'],
			'applied'              => $p['applied'],
			'rejected'             => $p['rejected'],
		);
	}
}
