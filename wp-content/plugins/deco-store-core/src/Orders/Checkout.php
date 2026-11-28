<?php
/**
 * Order submission pipeline (ORDER_WORKFLOW section 4), in this order, failing fast:
 * honeypot → token (signature, age, product) → rate limit → idempotency
 * gate → product → form validation → delivery → pricing → expected total
 * → stock → duplicate check → order creation.
 *
 * The browser sends ids and answers only. Any price it sends is ignored,
 * except `expected_total`, which is only compared: when it differs from the
 * server's total no order is created and the new total is returned so the
 * customer can confirm it.
 *
 * Returns array{status:int, body:array} for the REST controller.
 *
 * @package DecoStore
 */

namespace DecoStore\Orders;

use DecoStore\Core\Logger;
use DecoStore\Core\Settings;
use DecoStore\Delivery\DeliveryService;
use DecoStore\Delivery\Money;
use DecoStore\Forms\FieldTypes;
use DecoStore\Forms\FormRenderer;
use DecoStore\Forms\FormRepository;
use DecoStore\Forms\SubmissionValidator;
use DecoStore\Forms\WpValidationContext;
use DecoStore\Offers\EngineInputs;
use DecoStore\Offers\OfferRepository;
use DecoStore\Offers\OfferSchema;

defined( 'ABSPATH' ) || exit;

/**
 * Checkout service.
 */
class Checkout {

	const MIN_FILL_SECONDS = 2;
	const MAX_QTY          = 99;

	/**
	 * Token signing secret (derived from the site's salts; never stored or sent).
	 *
	 * @return string
	 */
	public static function secret() {
		return hash_hmac( 'sha256', 'ds-order-token', wp_salt( 'auth' ) );
	}

	/**
	 * Issue a token for a product and form.
	 *
	 * @param int $product_id Product id.
	 * @param int $form_id    Form id.
	 * @return string
	 */
	public static function issue_token( $product_id, $form_id ) {
		return Token::issue( $product_id, $form_id, time(), self::secret(), bin2hex( random_bytes( 8 ) ) );
	}

	/**
	 * Can this product be ordered (published, purchasable, in stock or on backorder)?
	 *
	 * @param \WC_Product|null|false $product Product.
	 * @return bool
	 */
	public static function sellable( $product ) {
		if ( ! $product || 'publish' !== get_post_status( $product->get_parent_id() ? $product->get_parent_id() : $product->get_id() ) ) {
			return false;
		}
		if ( $product->is_type( 'variable' ) ) {
			return $product->is_purchasable() && $product->is_in_stock();
		}
		return $product->is_purchasable() && '' !== $product->get_price() && $product->is_in_stock();
	}

	/**
	 * Resolve the product sold: a simple product, or a variation of the product.
	 *
	 * @param int $product_id   Product id.
	 * @param int $variation_id Variation id (0 = none).
	 * @return array{product:?\WC_Product,error:string}
	 */
	public static function resolve_product( $product_id, $variation_id ) {
		$parent = wc_get_product( (int) $product_id );
		if ( ! $parent || ! self::sellable( $parent ) || $parent->is_type( 'variation' ) ) {
			return array(
				'product' => null,
				'error'   => 'product_unavailable',
			);
		}
		if ( $parent->is_type( 'variable' ) ) {
			$variation = $variation_id ? wc_get_product( (int) $variation_id ) : null;
			if ( ! $variation || ! $variation->is_type( 'variation' ) || $variation->get_parent_id() !== $parent->get_id() ) {
				return array(
					'product' => null,
					'error'   => 'choose_variation',
				);
			}
			if ( ! $variation->is_purchasable() || '' === $variation->get_price() || ! $variation->is_in_stock() ) {
				return array(
					'product' => null,
					'error'   => 'variation_unavailable',
				);
			}
			return array(
				'product' => $variation,
				'error'   => '',
			);
		}
		if ( $variation_id ) {
			return array(
				'product' => null,
				'error'   => 'product_unavailable',
			);
		}
		if ( ! $parent->is_type( 'simple' ) ) {
			return array(
				'product' => null,
				'error'   => 'product_unavailable', // Grouped/external products are not sold through this form.
			);
		}
		return array(
			'product' => $parent,
			'error'   => '',
		);
	}

	/**
	 * Active selectable offers whose scope includes the product (offer cards).
	 *
	 * @param \WC_Product $product Product (parent).
	 * @return array[]
	 */
	public static function selectable_offers( $product ) {
		$cats = array_map( 'intval', $product->get_category_ids() );
		$out  = array();
		foreach ( OfferRepository::all() as $o ) {
			if ( 'active' !== $o['status'] || ! $o['selectable'] ) {
				continue;
			}
			$scope = $o['scope'];
			$match = 'bundle' === $o['type']
				? in_array( $product->get_id(), array_column( $o['items'], 'product_id' ), true )
				: ( 'all' === $scope['mode'] || ( 'products' === $scope['mode'] && in_array( $product->get_id(), $scope['product_ids'], true ) ) || ( 'categories' === $scope['mode'] && array_intersect( $cats, $scope['category_ids'] ) ) );
			if ( $match ) {
				$out[] = $o;
			}
		}
		return $out;
	}

	/**
	 * A positive integer from user input (int or digit string), else null.
	 *
	 * @param mixed $value Value.
	 * @param int   $max   Maximum.
	 * @return int|null
	 */
	private static function int_in( $value, $max ) {
		if ( is_int( $value ) ) {
			return $value >= 0 && $value <= $max ? $value : null;
		}
		if ( is_string( $value ) && preg_match( '/^\d{1,9}$/', $value ) ) {
			return (int) $value <= $max ? (int) $value : null;
		}
		return null;
	}

	/**
	 * Price a selection for display (quote endpoint and price_changed answers).
	 *
	 * @param array $body product_id, variation_id, qty, wilaya_id, commune_id, method, offer_id.
	 * @return array{status:int,body:array}
	 */
	public static function quote( array $body ) {
		$resolved = self::resolve_product( self::int_in( isset( $body['product_id'] ) ? $body['product_id'] : null, PHP_INT_MAX ), (int) self::int_in( isset( $body['variation_id'] ) ? $body['variation_id'] : 0, PHP_INT_MAX ) );
		if ( ! $resolved['product'] ) {
			return self::answer( 'choose_variation' === $resolved['error'] ? 200 : 404, array( 'error' => $resolved['error'] ) );
		}
		$qty = self::int_in( isset( $body['qty'] ) ? $body['qty'] : 1, self::MAX_QTY );
		if ( ! $qty ) {
			return self::answer( 400, array( 'error' => 'bad_quantity' ) );
		}
		$pricing = EngineInputs::price_single(
			array(
				'product_id'      => $resolved['product']->get_parent_id() ? $resolved['product']->get_parent_id() : $resolved['product']->get_id(),
				'variation_id'    => $resolved['product']->get_parent_id() ? $resolved['product']->get_id() : 0,
				'qty'             => $qty,
				'wilaya_id'       => (int) self::int_in( isset( $body['wilaya_id'] ) ? $body['wilaya_id'] : 0, PHP_INT_MAX ),
				'commune_id'      => (int) self::int_in( isset( $body['commune_id'] ) ? $body['commune_id'] : 0, PHP_INT_MAX ),
				'method'          => isset( $body['method'] ) && is_string( $body['method'] ) ? sanitize_key( $body['method'] ) : '',
				'chosen_offer_id' => (int) self::int_in( isset( $body['offer_id'] ) ? $body['offer_id'] : 0, PHP_INT_MAX ),
			)
		);
		return self::answer( $pricing['ok'] ? 200 : 409, self::summary( $pricing ) );
	}

	/**
	 * Customer-facing summary of a pricing result (no internal data).
	 *
	 * @param array $p Pricing result.
	 * @return array
	 */
	public static function summary( array $p ) {
		$d   = DeliveryService::decimals();
		$fmt = static function ( $minor ) use ( $d ) {
			return html_entity_decode( wp_strip_all_tags( wc_price( (float) Money::to_decimal( (int) $minor, $d ) ) ), ENT_QUOTES, 'UTF-8' );
		};
		if ( ! $p['ok'] ) {
			return array(
				'error' => $p['error'],
			);
		}
		$quote = isset( $p['delivery_quote'] ) ? $p['delivery_quote'] : null;
		$gifts = array();
		foreach ( $p['gifts'] as $g ) {
			$gp      = wc_get_product( $g['variation_id'] ? $g['variation_id'] : $g['product_id'] );
			$gifts[] = array(
				'name' => $gp ? wp_strip_all_tags( $gp->get_name() ) : '',
				'qty'  => $g['qty'],
			);
		}
		$line = $p['lines'][0];
		return array(
			'error'          => '',
			'qty'            => $line['qty'],
			'unit'           => $fmt( $line['base_unit_minor'] ),
			'unit_final'     => $fmt( $line['final_unit_minor'] ),
			'subtotal'       => $fmt( $p['subtotal_minor'] ),
			'discount'       => $p['discount_total_minor'] > 0 ? $fmt( $p['discount_total_minor'] ) : '',
			'delivery'       => $p['delivery']['selected'] ? $fmt( $p['delivery']['final_fee_minor'] ) : '',
			'delivery_state' => $p['delivery']['selected'] ? ( 0 === $p['delivery']['final_fee_minor'] ? 'free' : 'priced' ) : ( $quote ? 'not_served' : 'unknown' ),
			'gifts'          => $gifts,
			'offers'         => array_values(
				array_map(
					static function ( $a ) {
						return $a['label'];
					},
					$p['applied']
				)
			),
			'total'          => $fmt( $p['grand_total_minor'] ),
			'total_minor'    => (string) $p['grand_total_minor'],
		);
	}

	/**
	 * Build an answer.
	 *
	 * @param int   $status HTTP status.
	 * @param array $body   Body.
	 * @return array{status:int,body:array}
	 */
	private static function answer( $status, array $body ) {
		return array(
			'status' => (int) $status,
			'body'   => $body,
		);
	}

	/**
	 * Confirmation page URL of an order.
	 *
	 * @param \WC_Order $order Order.
	 * @return string
	 */
	public static function confirmation_url( $order ) {
		return add_query_arg(
			array(
				'ds_order' => $order->get_id(),
				'key'      => $order->get_order_key(),
			),
			home_url( '/' )
		);
	}

	/**
	 * Order id carrying an idempotency key (0 if none). Works with both order storages.
	 *
	 * @param string $key Key.
	 * @return int
	 */
	public static function order_with_key( $key ) {
		$ids = wc_get_orders(
			array(
				'limit'      => 1,
				'return'     => 'ids',
				'status'     => array_keys( wc_get_order_statuses() ),
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Rare recovery path only.
					array(
						'key'   => '_ds_idempotency_key',
						'value' => (string) $key,
					),
				),
			)
		);
		return $ids ? (int) $ids[0] : 0;
	}

	/**
	 * Submit an order.
	 *
	 * @param mixed  $body Decoded JSON body.
	 * @param string $ip   Client network address.
	 * @param int    $now  Unix time.
	 * @return array{status:int,body:array}
	 */
	public static function submit( $body, $ip, $now ) {
		if ( ! is_array( $body ) ) {
			return self::answer( 400, array( 'error' => 'bad_request' ) );
		}
		// 1. Honeypot: a field people never see.
		if ( isset( $body['hp'] ) && '' !== $body['hp'] ) {
			return self::answer( 400, array( 'error' => 'rejected' ) );
		}
		// 2. Token.
		$token = Token::verify( isset( $body['token'] ) ? $body['token'] : '', self::secret(), $now, (int) Settings::get( 'token_ttl_hours' ) * HOUR_IN_SECONDS );
		if ( ! $token['ok'] ) {
			return self::answer( 400, array( 'error' => 'expired' === $token['error'] ? 'expired' : 'bad_token' ) );
		}
		$product_id = self::int_in( isset( $body['product_id'] ) ? $body['product_id'] : null, PHP_INT_MAX );
		if ( $product_id !== $token['product_id'] ) {
			return self::answer( 400, array( 'error' => 'bad_token' ) );
		}
		if ( $now - $token['issued_at'] < self::MIN_FILL_SECONDS ) {
			return self::answer( 400, array( 'error' => 'too_fast' ) ); // The page retries by itself a moment later.
		}
		// 3. Rate limit per network address.
		$ip_hash = RateLimiter::ip_hash( $ip );
		if ( ! RateLimiter::hit( 'ip:' . $ip_hash, (int) Settings::get( 'max_attempts_per_ip' ), 10 * MINUTE_IN_SECONDS, $now ) ) {
			return self::answer( 429, array( 'error' => 'too_many' ) );
		}
		// 4. Idempotency gate.
		$key = isset( $body['idempotency_key'] ) ? $body['idempotency_key'] : '';
		if ( ! Idempotency::valid_key( $key ) ) {
			return self::answer( 400, array( 'error' => 'bad_request' ) );
		}
		$claim = Idempotency::claim( $key, $now, array( __CLASS__, 'order_with_key' ) );
		if ( 'done' === $claim['state'] ) {
			$order = wc_get_order( $claim['order_id'] );
			if ( $order ) {
				return self::answer(
					200,
					array(
						'order_id' => $order->get_id(),
						'redirect' => self::confirmation_url( $order ),
						'repeat'   => true,
					)
				);
			}
			Idempotency::release( $key ); // The order was deleted by staff: allow a new one.
			$claim = Idempotency::claim( $key, $now, array( __CLASS__, 'order_with_key' ) );
		}
		if ( 'claimed' !== $claim['state'] ) {
			return self::answer( 409, array( 'error' => 'in_progress' ) );
		}

		$correlation = substr( bin2hex( random_bytes( 6 ) ), 0, 12 );
		try {
			$result = self::process( $body, $token, $key, $ip_hash, $now );
		} catch ( \Throwable $e ) {
			Logger::error(
				'Order submission failed',
				array(
					'ref'    => $correlation,
					'reason' => $e->getMessage(),
				)
			);
			$result = self::answer(
				500,
				array(
					'error' => 'server_error',
					'ref'   => $correlation,
				)
			);
		}
		if ( isset( $result['body']['order_id'] ) ) {
			Idempotency::complete( $key, (int) $result['body']['order_id'], $now );
		} else {
			Idempotency::release( $key ); // Nothing was created: the same key may be retried.
		}
		return $result;
	}

	/**
	 * Steps after the gate (a claimed key is released by the caller on any failure).
	 *
	 * @param array  $body    Body.
	 * @param array  $token   Verified token.
	 * @param string $key     Idempotency key.
	 * @param string $ip_hash Hashed address.
	 * @param int    $now     Unix time.
	 * @return array{status:int,body:array}
	 */
	private static function process( array $body, array $token, $key, $ip_hash, $now ) {
		// 5. Product.
		$resolved = self::resolve_product( $token['product_id'], (int) self::int_in( isset( $body['variation_id'] ) ? $body['variation_id'] : 0, PHP_INT_MAX ) );
		if ( ! $resolved['product'] ) {
			return self::answer( 'choose_variation' === $resolved['error'] ? 422 : 409, array( 'error' => $resolved['error'] ) );
		}
		$product = $resolved['product'];

		// 6. Form (the one the token was issued for).
		$form = FormRepository::get( $token['form_id'] );
		if ( ! $form ) {
			return self::answer( 409, array( 'error' => 'form_changed' ) );
		}
		$fields = isset( $body['fields'] ) && is_array( $body['fields'] ) ? $body['fields'] : array();
		$check  = SubmissionValidator::validate( $form, $fields, new WpValidationContext() );
		if ( $check['errors'] ) {
			$messages = FormRenderer::messages();
			$errors   = array();
			foreach ( $check['errors'] as $field_key => $code ) {
				$errors[ $field_key ] = isset( $messages[ $code ] ) ? $messages[ $code ] : $messages['invalid'];
			}
			return self::answer(
				422,
				array(
					'error'  => 'invalid_fields',
					'fields' => $errors,
				)
			);
		}

		// Core values by function, and extra answers.
		$mapped  = array_fill_keys( array( 'customer_name', 'phone', 'wilaya', 'commune', 'address', 'note', 'delivery_method', 'pickup_point', 'quantity' ), '' );
		$answers = array();
		$keys    = array();
		foreach ( $form['fields'] as $field ) {
			$keys[ $field['maps_to'] ] = $field['key'];
			if ( ! array_key_exists( $field['key'], $check['values'] ) ) {
				continue;
			}
			$value = $check['values'][ $field['key'] ];
			if ( 'meta' === $field['maps_to'] ) {
				$answers[] = array(
					'key'   => $field['key'],
					'label' => $field['label'],
					'type'  => $field['type'],
					'value' => is_bool( $value ) ? ( $value ? __( 'نعم', 'deco-store-core' ) : __( 'لا', 'deco-store-core' ) ) : (string) $value,
				);
			} elseif ( isset( $mapped[ $field['maps_to'] ] ) ) {
				$mapped[ $field['maps_to'] ] = $value;
			}
		}

		// 7. Quantity: the form's quantity field when it has one, else the widget's.
		$qty = '' !== $mapped['quantity'] ? (int) $mapped['quantity'] : self::int_in( isset( $body['qty'] ) ? $body['qty'] : 1, self::MAX_QTY );
		if ( ! $qty || $qty > OfferSchema::MAX_QTY ) {
			return self::answer( 422, array( 'error' => 'bad_quantity' ) );
		}

		// 8. Delivery + 9. pricing.
		$pricing = EngineInputs::price_single(
			array(
				'product_id'      => $token['product_id'],
				'variation_id'    => $product->get_parent_id() ? $product->get_id() : 0,
				'qty'             => $qty,
				'wilaya_id'       => (int) $mapped['wilaya'],
				'commune_id'      => (int) $mapped['commune'],
				'method'          => (string) $mapped['delivery_method'],
				'chosen_offer_id' => (int) self::int_in( isset( $body['offer_id'] ) ? $body['offer_id'] : 0, PHP_INT_MAX ),
			)
		);
		if ( ! $pricing['ok'] ) {
			if ( 'chosen_offer_invalid' === $pricing['error'] ) {
				$fresh = $body;
				unset( $fresh['offer_id'] );
				return self::answer(
					409,
					array(
						'error' => 'offer_changed',
						'quote' => self::quote( self::quote_args( $fresh, $product, $qty, $mapped ) )['body'],
					)
				);
			}
			return self::answer( 422, array( 'error' => $pricing['error'] ) );
		}
		if ( ! $pricing['delivery']['selected'] ) {
			$field = isset( $keys['delivery_method'] ) ? $keys['delivery_method'] : 'delivery_method';
			return self::answer(
				422,
				array(
					'error'  => 'invalid_fields',
					'fields' => array( $field => __( 'التوصيل غير متاح لهذه المنطقة بهذه الطريقة. اختر طريقة أو منطقة أخرى.', 'deco-store-core' ) ),
				)
			);
		}

		// 10. The customer saw a total: if it changed, nothing is created and the new total is shown.
		if ( isset( $body['expected_total'] ) && (string) $body['expected_total'] !== (string) $pricing['grand_total_minor'] ) {
			return self::answer(
				409,
				array(
					'error' => 'price_changed',
					'quote' => self::summary( $pricing ),
				)
			);
		}

		// 11. Stock (WooCommerce's own rules; backorders follow the product setting).
		if ( $product->managing_stock() && ! $product->backorders_allowed() && null !== $product->get_stock_quantity() && $product->get_stock_quantity() < $qty ) {
			return self::answer(
				409,
				array(
					'error'     => 'out_of_stock',
					'available' => max( 0, (int) $product->get_stock_quantity() ),
				)
			);
		}

		// 12. Possible duplicate: same phone and product recently. The order is created but flagged.
		$flags  = array();
		$window = (int) Settings::get( 'duplicate_window_hours' );
		if ( $window > 0 && self::recent_same_order( (string) $mapped['phone'], $token['product_id'], $now - $window * HOUR_IN_SECONDS ) ) {
			$flags['duplicate_suspect'] = true;
		}

		// 13. Create.
		$order = OrderFactory::create(
			array(
				'product' => $product,
				'pricing' => $pricing,
				'mapped'  => $mapped,
				'answers' => $answers,
				'form'    => $form,
				'key'     => $key,
				'client'  => array(
					'ip_hash'      => $ip_hash,
					'submitted_at' => $now,
				),
				'flags'   => $flags,
			)
		);
		self::check_oversell( $order, $product );

		return self::answer(
			201,
			array(
				'order_id' => $order->get_id(),
				'redirect' => self::confirmation_url( $order ),
			)
		);
	}

	/**
	 * Arguments for a fresh quote after a refusal.
	 *
	 * @param array       $body    Body.
	 * @param \WC_Product $product Product.
	 * @param int         $qty     Quantity.
	 * @param array       $mapped  Core values.
	 * @return array
	 */
	private static function quote_args( array $body, $product, $qty, array $mapped ) {
		return array(
			'product_id'   => $product->get_parent_id() ? $product->get_parent_id() : $product->get_id(),
			'variation_id' => $product->get_parent_id() ? $product->get_id() : 0,
			'qty'          => $qty,
			'wilaya_id'    => (int) $mapped['wilaya'],
			'commune_id'   => (int) $mapped['commune'],
			'method'       => (string) $mapped['delivery_method'],
			'offer_id'     => isset( $body['offer_id'] ) ? $body['offer_id'] : 0,
		);
	}

	/**
	 * Is there a recent order with the same phone and product?
	 *
	 * @param string $phone      Normalised phone.
	 * @param int    $product_id Product id (parent).
	 * @param int    $since      Unix time.
	 * @return bool
	 */
	private static function recent_same_order( $phone, $product_id, $since ) {
		if ( '' === $phone ) {
			return false;
		}
		$orders = wc_get_orders(
			array(
				'limit'         => 20,
				'billing_phone' => $phone,
				'date_created'  => '>' . (int) $since,
				'status'        => array_keys( wc_get_order_statuses() ),
			)
		);
		foreach ( $orders as $order ) {
			foreach ( $order->get_items() as $item ) {
				if ( (int) $item->get_product_id() === (int) $product_id && ! $item->get_meta( '_ds_is_gift' ) ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Two customers can pass the stock check at the same moment. If the stock
	 * went below zero for a product that does not allow backorders, the order
	 * is flagged for staff and a private note explains why.
	 *
	 * @param \WC_Order   $order   Order.
	 * @param \WC_Product $product Product.
	 * @return void
	 */
	private static function check_oversell( $order, $product ) {
		$fresh = wc_get_product( $product->get_id() );
		if ( $fresh && $fresh->managing_stock() && ! $fresh->backorders_allowed() && (int) $fresh->get_stock_quantity() < 0 ) {
			$flags                   = json_decode( (string) $order->get_meta( '_ds_flags' ), true );
			$flags                   = is_array( $flags ) ? $flags : array();
			$flags['stock_oversold'] = true;
			$order->update_meta_data( '_ds_flags', wp_json_encode( $flags ) );
			$order->add_order_note( __( 'تنبيه: نفد المخزون أثناء إنشاء هذا الطلب (طلبان في نفس اللحظة). تحقق من التوفر قبل الشحن.', 'deco-store-core' ) );
			$order->save();
		}
	}

	/**
	 * Is a field type an input field? (Used by the widget.)
	 *
	 * @param string $type Type.
	 * @return bool
	 */
	public static function is_input( $type ) {
		return FieldTypes::exists( $type ) && FieldTypes::all()[ $type ]['input'];
	}
}
