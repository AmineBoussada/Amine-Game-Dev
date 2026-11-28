<?php
/**
 * Public order endpoints (ds-core/v1):
 *   GET  /token?product=&form=  form token (never cached)
 *   POST /quote                 price preview for the current choices
 *   POST /orders                submit an order
 *
 * They are public on purpose (guest checkout): protection comes from the
 * signed token, the honeypot, the rate limit, the idempotency gate and the
 * fact that the server recomputes everything. Bodies are JSON and size
 * limited; unknown keys are ignored.
 *
 * @package DecoStore
 */

namespace DecoStore\Rest;

use DecoStore\Forms\FormRepository;
use DecoStore\Orders\Checkout;

defined( 'ABSPATH' ) || exit;

/**
 * REST routes for ordering.
 */
class OrderController {

	const MAX_BODY = 32768;

	/**
	 * Hook route registration.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register' ) );
	}

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public static function register() {
		register_rest_route(
			GeoController::NAMESPACE_ID,
			'/token',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'token' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'product' => array(
						'type'     => 'integer',
						'required' => true,
						'minimum'  => 1,
					),
					'form'    => array(
						'type'     => 'integer',
						'required' => true,
						'minimum'  => 1,
					),
				),
			)
		);
		register_rest_route(
			GeoController::NAMESPACE_ID,
			'/quote',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'quote' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			GeoController::NAMESPACE_ID,
			'/orders',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'orders' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Response that is never cached.
	 *
	 * @param array $data   Body.
	 * @param int   $status Status.
	 * @return \WP_REST_Response
	 */
	private static function respond( array $data, $status = 200 ) {
		$response = new \WP_REST_Response( $data, $status );
		$response->header( 'Cache-Control', 'no-store, private' );
		$response->header( 'X-Robots-Tag', 'noindex' );
		return $response;
	}

	/**
	 * JSON body or an error response.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return array|\WP_REST_Response
	 */
	private static function body( \WP_REST_Request $request ) {
		$raw = (string) $request->get_body();
		if ( strlen( $raw ) > self::MAX_BODY ) {
			return self::respond( array( 'error' => 'too_large' ), 413 );
		}
		if ( false === stripos( (string) $request->get_content_type()['value'], 'json' ) ) {
			return self::respond( array( 'error' => 'bad_request' ), 415 );
		}
		$data = json_decode( $raw, true );
		return is_array( $data ) ? $data : self::respond( array( 'error' => 'bad_request' ), 400 );
	}

	/**
	 * GET /token.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public static function token( \WP_REST_Request $request ) {
		$product = wc_get_product( (int) $request['product'] );
		$form    = FormRepository::get( (int) $request['form'] );
		if ( ! $product || ! Checkout::sellable( $product ) || ! $form ) {
			return self::respond( array( 'error' => 'product_unavailable' ), 404 );
		}
		return self::respond( array( 'token' => Checkout::issue_token( $product->get_id(), $form['id'] ) ) );
	}

	/**
	 * POST /quote.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public static function quote( \WP_REST_Request $request ) {
		$body = self::body( $request );
		if ( $body instanceof \WP_REST_Response ) {
			return $body;
		}
		$result = Checkout::quote( $body );
		return self::respond( $result['body'], $result['status'] );
	}

	/**
	 * POST /orders.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public static function orders( \WP_REST_Request $request ) {
		$body = self::body( $request );
		if ( $body instanceof \WP_REST_Response ) {
			return $body;
		}
		$ip     = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : ''; // Not trusting forwarded headers: they can be forged unless a known proxy sets them.
		$result = Checkout::submit( $body, $ip, time() );
		return self::respond( $result['body'], $result['status'] );
	}
}
