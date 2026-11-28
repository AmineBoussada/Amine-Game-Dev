<?php
/**
 * WooCommerce order screens: a read-only "Déco Store" box on the order
 * edit screen and a column in the orders list. Works with both order
 * storages (HPOS screen and legacy post screen).
 *
 * @package DecoStore
 */

namespace DecoStore\Admin;

use DecoStore\Offers\OfferText;
use DecoStore\Orders\OrderFactory;

defined( 'ABSPATH' ) || exit;

/**
 * Order admin additions.
 */
class OrderAdmin {

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_box' ) );
		add_filter( 'manage_woocommerce_page_wc-orders_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( __CLASS__, 'column_hpos' ), 10, 2 );
		add_filter( 'manage_edit-shop_order_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_shop_order_posts_custom_column', array( __CLASS__, 'column_legacy' ), 10, 2 );
		add_filter( 'woocommerce_hidden_order_itemmeta', array( __CLASS__, 'hidden_item_meta' ) );
	}

	/**
	 * Line-item meta that WooCommerce must not list (nor offer for editing) on the order screen.
	 * The useful parts are shown in the Déco Store box instead.
	 *
	 * @param array $keys Hidden keys.
	 * @return array
	 */
	public static function hidden_item_meta( $keys ) {
		return array_merge( (array) $keys, array( '_ds_unit_base_price', '_ds_unit_final_price', '_ds_offer_id', '_ds_is_gift', '_ds_options', '_ds_unit_cost_snapshot' ) );
	}

	/**
	 * Register the box on the order screen of either storage.
	 *
	 * @return void
	 */
	public static function add_box() {
		$screen = function_exists( 'wc_get_page_screen_id' ) ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
		add_meta_box( 'ds-order-box', 'Déco Store', array( __CLASS__, 'render_box' ), $screen, 'side', 'default' );
	}

	/**
	 * Order from the callback argument (WP_Post on legacy, WC_Order on HPOS).
	 *
	 * @param mixed $subject Post or order.
	 * @return \WC_Order|null
	 */
	private static function order( $subject ) {
		if ( $subject instanceof \WC_Order ) {
			return $subject;
		}
		$order = $subject instanceof \WP_Post ? wc_get_order( $subject->ID ) : null;
		return $order ? $order : null;
	}

	/**
	 * Decoded JSON meta.
	 *
	 * @param \WC_Order $order Order.
	 * @param string    $key   Meta key.
	 * @return array
	 */
	private static function json( $order, $key ) {
		$data = json_decode( (string) $order->get_meta( $key ), true );
		return is_array( $data ) ? $data : array();
	}

	/**
	 * Flag labels.
	 *
	 * @return array<string,string>
	 */
	private static function flag_labels() {
		return array(
			'duplicate_suspect' => __( 'طلب مكرر محتمل (نفس الهاتف والمنتج)', 'deco-store-core' ),
			'stock_oversold'    => __( 'المخزون نفد أثناء الطلب', 'deco-store-core' ),
		);
	}

	/**
	 * The box.
	 *
	 * @param mixed $subject Post or order.
	 * @return void
	 */
	public static function render_box( $subject ) {
		$order = self::order( $subject );
		if ( ! $order || OrderFactory::SOURCE !== $order->get_meta( '_ds_source' ) ) {
			echo '<p>' . esc_html__( 'ليس طلباً من صفحة منتج.', 'deco-store-core' ) . '</p>';
			return;
		}
		$pricing  = self::json( $order, '_ds_pricing_snapshot' );
		$delivery = self::json( $order, '_ds_delivery_snapshot' );
		$answers  = self::json( $order, '_ds_form_answers' );
		$flags    = self::json( $order, '_ds_flags' );
		$labels   = self::flag_labels();
		echo '<div class="ds-order-box">';
		foreach ( $flags as $flag => $on ) {
			if ( $on ) {
				echo '<p><strong>' . esc_html__( 'تنبيه:', 'deco-store-core' ) . '</strong> ' . esc_html( isset( $labels[ $flag ] ) ? $labels[ $flag ] : $flag ) . '</p>';
			}
		}
		echo '<p><strong>' . esc_html__( 'المصدر:', 'deco-store-core' ) . '</strong> ' . esc_html__( 'صفحة المنتج (الدفع عند الاستلام)', 'deco-store-core' ) . '</p>';
		if ( ! empty( $pricing['applied'] ) ) {
			echo '<p><strong>' . esc_html__( 'العروض المطبّقة:', 'deco-store-core' ) . '</strong></p><ul>';
			foreach ( $pricing['applied'] as $a ) {
				echo '<li>' . esc_html( $a['label'] . ' — ' . ( isset( OfferText::groups()[ $a['group'] ] ) ? OfferText::groups()[ $a['group'] ] : $a['group'] ) . ' (#' . $a['offer_id'] . ' v' . $a['version'] . ')' ) . '</li>';
			}
			echo '</ul>';
		}
		if ( $delivery ) {
			echo '<p><strong>' . esc_html__( 'التوصيل:', 'deco-store-core' ) . '</strong> ' . esc_html( $delivery['method_label'] ) . '<br>';
			echo esc_html( trim( $delivery['wilaya'] . ( '' !== $delivery['commune'] ? ' — ' . $delivery['commune'] : '' ) ) );
			if ( '' !== $delivery['pickup'] ) {
				echo '<br>' . esc_html__( 'المكتب:', 'deco-store-core' ) . ' ' . esc_html( $delivery['pickup'] . ( '' !== $delivery['carrier'] ? ' (' . $delivery['carrier'] . ')' : '' ) );
			}
			echo '</p>';
		}
		if ( $answers ) {
			echo '<p><strong>' . esc_html__( 'إجابات إضافية:', 'deco-store-core' ) . '</strong></p><ul>';
			foreach ( $answers as $a ) {
				echo '<li>' . esc_html( $a['label'] . ': ' . $a['value'] ) . '</li>';
			}
			echo '</ul>';
		}
		if ( isset( $pricing['engine_version'] ) ) {
			echo '<p class="description">' . esc_html(
				sprintf(
					/* translators: 1: engine version, 2: form id, 3: form version. */
					__( 'محرك الأسعار v%1$d · النموذج #%2$d v%3$d', 'deco-store-core' ),
					(int) $pricing['engine_version'],
					(int) $order->get_meta( '_ds_form_id' ),
					(int) $order->get_meta( '_ds_form_version' )
				)
			) . '</p>';
		}
		echo '</div>';
	}

	/**
	 * Add the column after the status column.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public static function columns( $columns ) {
		$out = array();
		foreach ( $columns as $key => $label ) {
			$out[ $key ] = $label;
			if ( 'order_status' === $key ) {
				$out['ds_source'] = 'Déco Store';
			}
		}
		if ( ! isset( $out['ds_source'] ) ) {
			$out['ds_source'] = 'Déco Store';
		}
		return $out;
	}

	/**
	 * Column cell (HPOS).
	 *
	 * @param string    $column Column.
	 * @param \WC_Order $order  Order.
	 * @return void
	 */
	public static function column_hpos( $column, $order ) {
		if ( 'ds_source' === $column ) {
			self::cell( $order );
		}
	}

	/**
	 * Column cell (legacy).
	 *
	 * @param string $column  Column.
	 * @param int    $post_id Order id.
	 * @return void
	 */
	public static function column_legacy( $column, $post_id ) {
		if ( 'ds_source' === $column ) {
			self::cell( wc_get_order( $post_id ) );
		}
	}

	/**
	 * Cell content.
	 *
	 * @param \WC_Order|false $order Order.
	 * @return void
	 */
	private static function cell( $order ) {
		if ( ! $order || OrderFactory::SOURCE !== $order->get_meta( '_ds_source' ) ) {
			echo '—';
			return;
		}
		echo esc_html__( 'صفحة المنتج', 'deco-store-core' );
		$labels = self::flag_labels();
		foreach ( self::json( $order, '_ds_flags' ) as $flag => $on ) {
			if ( $on ) {
				echo '<br><strong>' . esc_html__( 'تنبيه:', 'deco-store-core' ) . '</strong> ' . esc_html( isset( $labels[ $flag ] ) ? $labels[ $flag ] : $flag );
			}
		}
	}
}
