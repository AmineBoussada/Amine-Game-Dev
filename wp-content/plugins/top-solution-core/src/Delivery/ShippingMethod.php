<?php
/**
 * WooCommerce shipping method backed by DeliveryService, so orders carry a
 * real WooCommerce shipping line and WooCommerce reports include delivery.
 * The landing-page order flow (Phase 5) uses the same service directly.
 *
 * @package TopSolution
 */

namespace TopSolution\Delivery;

defined( 'ABSPATH' ) || exit;

/**
 * Shipping method "ts_delivery".
 */
class ShippingMethod extends \WC_Shipping_Method {

	const ID = 'ts_delivery';

	/**
	 * Constructor.
	 *
	 * @param int $instance_id Shipping zone instance id.
	 */
	public function __construct( $instance_id = 0 ) {
		$this->id                   = self::ID;
		$this->instance_id          = absint( $instance_id );
		$this->method_title         = __( 'Top Solution — التوصيل', 'top-solution-core' );
		$this->method_description   = __( 'أسعار التوصيل حسب الولاية والبلدية من Top Solution ← التوصيل.', 'top-solution-core' );
		$this->supports             = array( 'shipping-zones', 'instance-settings' );
		$this->instance_form_fields = array(
			'title' => array(
				'title'   => __( 'العنوان في لوحة التحكم', 'top-solution-core' ),
				'type'    => 'text',
				'default' => __( 'Top Solution — التوصيل', 'top-solution-core' ),
			),
		);
		$this->title                = $this->get_option( 'title', $this->method_title );
		$this->enabled              = 'yes';
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Add one rate per available delivery method.
	 *
	 * @param array $package Shipping package.
	 * @return void
	 */
	public function calculate_shipping( $package = array() ) {
		$destination = isset( $package['destination'] ) ? $package['destination'] : array();
		if ( empty( $destination['country'] ) || 'DZ' !== $destination['country'] ) {
			return;
		}
		$code   = GeoCode::from_wc_state( isset( $destination['state'] ) ? $destination['state'] : '' );
		$wilaya = null !== $code ? GeoRepository::find_by_code( 'wilaya', $code ) : null;
		if ( ! $wilaya ) {
			return;
		}
		$commune  = GeoRepository::find_commune_by_name( $wilaya['id'], isset( $destination['city'] ) ? $destination['city'] : '' );
		$decimals = DeliveryService::decimals();
		$subtotal = Money::to_minor( wc_format_decimal( isset( $package['contents_cost'] ) ? $package['contents_cost'] : 0, $decimals ), $decimals );

		foreach ( DeliverySettings::enabled_methods() as $method ) {
			$quote = DeliveryService::quote( $method, $wilaya['id'], $commune ? $commune['id'] : 0, (int) $subtotal );
			if ( ! $quote['available'] ) {
				continue;
			}
			$this->add_rate(
				array(
					'id'        => $this->get_rate_id( $method ),
					'label'     => $quote['label'],
					'cost'      => $quote['fee'],
					'package'   => $package,
					'meta_data' => array(
						'ts_method'  => $method,
						'ts_rate_id' => $quote['rate_id'],
						'ts_source'  => $quote['source'],
					),
				)
			);
		}
	}
}
