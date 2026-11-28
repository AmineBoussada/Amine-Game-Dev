<?php
/**
 * Order confirmation page: /?ds_order=<id>&key=<WooCommerce order key>.
 * The order key is long and random (WooCommerce), so knowing an order
 * number is not enough to see an order. Read-only, safe to reload,
 * never cached and never indexed. Shows no internal data.
 *
 * @package DecoStore
 */

namespace DecoStore\Orders;

use DecoStore\Core\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the confirmation page.
 */
class Confirmation {

	/**
	 * Hook.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'maybe_render' ), 1 );
	}

	/**
	 * The order for the current request, or null.
	 *
	 * @return \WC_Order|null
	 */
	public static function current_order() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Access is controlled by the secret order key.
		$id  = isset( $_GET['ds_order'] ) ? absint( $_GET['ds_order'] ) : 0;
		$key = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
		// phpcs:enable
		if ( ! $id || '' === $key ) {
			return null;
		}
		$order = wc_get_order( $id );
		if ( ! $order || ! hash_equals( (string) $order->get_order_key(), $key ) || OrderFactory::SOURCE !== $order->get_meta( '_ds_source' ) ) {
			return null;
		}
		return $order;
	}

	/**
	 * Render when the query asks for it.
	 *
	 * @return void
	 */
	public static function maybe_render() {
		if ( ! isset( $_GET['ds_order'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only routing.
			return;
		}
		nocache_headers();
		header( 'X-Robots-Tag: noindex, nofollow' );
		add_filter( 'wp_robots', 'wp_robots_no_robots' );
		$order = self::current_order();
		if ( ! $order ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			return; // The theme's normal 404 page.
		}
		add_filter(
			'document_title_parts',
			static function ( $parts ) {
				$parts['title'] = __( 'تم استلام طلبك', 'deco-store-core' );
				return $parts;
			}
		);
		wp_enqueue_style( 'ds-core-forms', DS_CORE_URL . 'assets/forms.css', array(), DS_CORE_VERSION );
		status_header( 200 );
		get_header();
		echo self::html( $order ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in html().
		get_footer();
		exit;
	}

	/**
	 * Page body.
	 *
	 * @param \WC_Order $order Order.
	 * @return string
	 */
	public static function html( $order ) {
		$message  = (string) Settings::get( 'confirmation_message' );
		$message  = '' !== $message ? $message : __( 'تم تسجيل طلبك بنجاح. الدفع يكون نقداً عند الاستلام.', 'deco-store-core' );
		$delivery = json_decode( (string) $order->get_meta( '_ds_delivery_snapshot' ), true );
		$delivery = is_array( $delivery ) ? $delivery : array();
		$pricing  = json_decode( (string) $order->get_meta( '_ds_pricing_snapshot' ), true );
		$saved    = is_array( $pricing ) && isset( $pricing['discount_total_minor'], $pricing['decimals'] )
			? (float) \DecoStore\Delivery\Money::to_decimal( (int) $pricing['discount_total_minor'], (int) $pricing['decimals'] )
			: 0.0;
		ob_start();
		?>
		<main id="content" class="ds-main ds-confirmation"><div class="ds-container">
			<div class="ds-confirmation__box">
				<h1><?php esc_html_e( 'تم استلام طلبك', 'deco-store-core' ); ?></h1>
				<p class="ds-confirmation__number">
					<?php
					/* translators: %s: order number. */
					printf( esc_html__( 'رقم الطلب: %s', 'deco-store-core' ), '<strong>' . esc_html( $order->get_order_number() ) . '</strong>' );
					?>
				</p>
				<div class="ds-confirmation__message"><?php echo wp_kses_post( wpautop( esc_html( $message ) ) ); ?></div>

				<h2><?php esc_html_e( 'ملخص الطلب', 'deco-store-core' ); ?></h2>
				<table class="ds-confirmation__table">
					<tbody>
					<?php foreach ( $order->get_items() as $item ) : ?>
						<tr>
							<th scope="row">
								<?php echo esc_html( $item->get_name() ); ?> × <?php echo esc_html( (string) $item->get_quantity() ); ?>
								<?php if ( $item->get_meta( '_ds_is_gift' ) ) : ?>
									<span class="ds-confirmation__gift"><?php esc_html_e( 'هدية', 'deco-store-core' ); ?></span>
								<?php endif; ?>
							</th>
							<td><?php echo $item->get_meta( '_ds_is_gift' ) ? esc_html__( 'مجاناً', 'deco-store-core' ) : wp_kses_post( wc_price( (float) $item->get_total(), array( 'currency' => $order->get_currency() ) ) ); ?></td>
						</tr>
					<?php endforeach; ?>
					<?php if ( $saved > 0 ) : ?>
						<tr><th scope="row"><?php esc_html_e( 'التوفير', 'deco-store-core' ); ?></th><td>− <?php echo wp_kses_post( wc_price( $saved, array( 'currency' => $order->get_currency() ) ) ); ?></td></tr>
					<?php endif; ?>
					<tr>
						<th scope="row">
							<?php echo esc_html( isset( $delivery['method_label'] ) && '' !== $delivery['method_label'] ? $delivery['method_label'] : __( 'التوصيل', 'deco-store-core' ) ); ?>
							<?php if ( ! empty( $delivery['pickup'] ) ) : ?>
								<br><small><?php echo esc_html( $delivery['pickup'] ); ?></small>
							<?php endif; ?>
						</th>
						<td><?php echo (float) $order->get_shipping_total() > 0 ? wp_kses_post( wc_price( (float) $order->get_shipping_total(), array( 'currency' => $order->get_currency() ) ) ) : esc_html__( 'مجاني', 'deco-store-core' ); ?></td>
					</tr>
					<tr class="ds-confirmation__total">
						<th scope="row"><?php esc_html_e( 'المبلغ عند الاستلام', 'deco-store-core' ); ?></th>
						<td><strong><?php echo wp_kses_post( wc_price( (float) $order->get_total(), array( 'currency' => $order->get_currency() ) ) ); ?></strong></td>
					</tr>
					</tbody>
				</table>
				<p><a class="ds-btn" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'العودة إلى المتجر', 'deco-store-core' ); ?></a></p>
			</div>
		</div></main>
		<?php
		return (string) ob_get_clean();
	}
}
