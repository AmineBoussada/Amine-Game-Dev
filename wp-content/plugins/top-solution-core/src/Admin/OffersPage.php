<?php
/**
 * Top Solution > Offers: list, editor and price simulator. All writes are
 * POST + nonce + ts_manage_offers, then redirect. An offer is stored only if
 * OfferSchema accepts it; otherwise nothing is saved and the typed values are
 * kept so they can be corrected.
 *
 * @package TopSolution
 */

namespace TopSolution\Admin;

use TopSolution\Core\Capabilities;
use TopSolution\Delivery\DeliveryService;
use TopSolution\Delivery\DeliverySettings;
use TopSolution\Delivery\GeoRepository;
use TopSolution\Delivery\Money;
use TopSolution\Offers\EngineInputs;
use TopSolution\Offers\OfferConflicts;
use TopSolution\Offers\OfferForm;
use TopSolution\Offers\OfferRepository;
use TopSolution\Offers\OfferSchema;
use TopSolution\Offers\OfferText;

defined( 'ABSPATH' ) || exit;

/**
 * Offers admin.
 */
class OffersPage {

	const SLUG  = 'top-solution-offers';
	const NONCE = 'ts_offers';

	/**
	 * Add the sub-menu and its POST handler.
	 *
	 * @param string $parent_slug Parent menu slug.
	 * @return void
	 */
	public static function register_menu( $parent_slug ) {
		$hook = add_submenu_page(
			$parent_slug,
			__( 'العروض', 'top-solution-core' ),
			__( 'العروض', 'top-solution-core' ),
			Capabilities::MANAGE_OFFERS,
			self::SLUG,
			array( __CLASS__, 'render' )
		);
		if ( $hook ) {
			add_action( 'load-' . $hook, array( __CLASS__, 'handle_post' ) );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		}
	}

	/**
	 * Editor script.
	 *
	 * @param string $hook_suffix Page hook.
	 * @return void
	 */
	public static function enqueue( $hook_suffix ) {
		if ( false !== strpos( (string) $hook_suffix, self::SLUG ) ) {
			wp_enqueue_script( 'ts-core-offers-admin', TS_CORE_URL . 'assets/offers-admin.js', array(), TS_CORE_VERSION, array( 'in_footer' => true ) );
		}
	}

	/**
	 * Per-user transient key.
	 *
	 * @param string $what Name.
	 * @return string
	 */
	private static function key( $what ) {
		return 'ts_offers_' . $what . '_' . get_current_user_id();
	}

	/**
	 * Queue a notice.
	 *
	 * @param string $type success | error | warning.
	 * @param string $text Message.
	 * @return void
	 */
	private static function notice( $type, $text ) {
		$notices   = get_transient( self::key( 'notice' ) );
		$notices   = is_array( $notices ) ? $notices : array();
		$notices[] = array( $type, $text );
		set_transient( self::key( 'notice' ), $notices, 5 * MINUTE_IN_SECONDS );
	}

	/**
	 * Page URL.
	 *
	 * @param array $args Query args.
	 * @return string
	 */
	private static function url( array $args = array() ) {
		return add_query_arg( array_merge( array( 'page' => self::SLUG ), $args ), admin_url( 'admin.php' ) );
	}

	/**
	 * Handle a POST, then redirect.
	 *
	 * @return void
	 */
	public static function handle_post() {
		if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Compared to a constant.
			return;
		}
		if ( ! current_user_can( Capabilities::MANAGE_OFFERS ) ) {
			wp_die( esc_html__( 'ليست لديك صلاحية تعديل العروض.', 'top-solution-core' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( self::NONCE );
		$action = isset( $_POST['ts_action'] ) ? sanitize_key( wp_unslash( $_POST['ts_action'] ) ) : '';
		$id     = isset( $_POST['offer_id'] ) ? absint( $_POST['offer_id'] ) : 0;
		$args   = array();

		switch ( $action ) {
			case 'save_offer':
				$args = self::save_offer( $id );
				break;
			case 'duplicate_offer':
				$new = OfferRepository::duplicate( $id );
				self::notice( $new ? 'success' : 'error', $new ? __( 'تم نسخ العرض كمسودة.', 'top-solution-core' ) : __( 'تعذّر النسخ.', 'top-solution-core' ) );
				break;
			case 'status_offer':
				$status = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';
				$done   = OfferRepository::set_status( $id, $status );
				self::notice( $done ? 'success' : 'error', $done ? __( 'تم تغيير حالة العرض.', 'top-solution-core' ) : __( 'تعذّر تغيير الحالة (العرض غير صالح؟ افتحه وصحّحه).', 'top-solution-core' ) );
				break;
			case 'delete_offer':
				$done = OfferRepository::delete( $id );
				self::notice( $done ? 'success' : 'error', $done ? __( 'تم حذف العرض.', 'top-solution-core' ) : __( 'العرض غير موجود.', 'top-solution-core' ) );
				break;
		}
		wp_safe_redirect( self::url( $args ) );
		exit;
	}

	/**
	 * Save from the editor.
	 *
	 * @param int $id Offer id (0 = new).
	 * @return array Redirect args.
	 */
	private static function save_offer( $id ) {
		$return = array( 'offer' => $id ? $id : 'new' );
		if ( $id && ! OfferRepository::get( $id ) ) {
			self::notice( 'error', __( 'العرض غير موجود.', 'top-solution-core' ) );
			return array();
		}
		$post = array();
		foreach ( wp_unslash( $_POST ) as $key => $value ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce checked in handle_post(); every value is validated by OfferForm/OfferSchema.
			if ( is_string( $key ) && ( is_string( $value ) || is_array( $value ) ) ) {
				$post[ $key ] = $value;
			}
		}
		$result = OfferSchema::normalize( OfferForm::canonical( $post, DeliveryService::decimals(), wp_timezone() ) );
		if ( $result['errors'] ) {
			set_transient(
				self::key( 'draft' ),
				array(
					'id'   => $id,
					'post' => $post,
				),
				15 * MINUTE_IN_SECONDS
			);
			$lines = array_unique( array_map( array( OfferText::class, 'error' ), $result['errors'] ) );
			self::notice( 'error', __( 'لم يُحفظ العرض. صحّح ما يلي:', 'top-solution-core' ) . ' ' . implode( ' · ', array_slice( $lines, 0, 8 ) ) );
			return $return;
		}
		delete_transient( self::key( 'draft' ) );
		if ( $id ) {
			OfferRepository::update( $id, $result['definition'] );
		} else {
			$id = OfferRepository::create( $result['definition'] );
		}
		self::notice( 'success', __( 'تم حفظ العرض.', 'top-solution-core' ) );
		if ( $id && 'active' === $result['definition']['status'] ) {
			$others = array_filter(
				OfferRepository::all(),
				static function ( $o ) {
					return 'active' === $o['status'];
				}
			);
			foreach ( OfferConflicts::find( $others ) as $pair ) {
				if ( in_array( $id, $pair, true ) ) {
					self::notice( 'warning', __( 'تنبيه: يوجد عرض مفعّل آخر بنفس الأولوية على نفس المنتجات. سيُختار الأكبر خصماً ثم الأقدم، ويُفضَّل تحديد أولوية مختلفة. جرّب «المحاكي».', 'top-solution-core' ) );
					break;
				}
			}
		}
		return array();
	}

	/**
	 * Render the page.
	 *
	 * @return void
	 */
	public static function render() {
		if ( ! current_user_can( Capabilities::MANAGE_OFFERS ) ) {
			wp_die( esc_html__( 'ليست لديك صلاحية عرض هذه الصفحة.', 'top-solution-core' ), '', array( 'response' => 403 ) );
		}
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only page selection.
		$offer_arg = isset( $_GET['offer'] ) ? sanitize_text_field( wp_unslash( $_GET['offer'] ) ) : '';
		$tab       = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'list';
		// phpcs:enable
		echo '<div class="wrap ts-admin"><h1>' . esc_html__( 'Top Solution — العروض', 'top-solution-core' ) . '</h1>';
		$notices = get_transient( self::key( 'notice' ) );
		delete_transient( self::key( 'notice' ) );
		foreach ( is_array( $notices ) ? $notices : array() as $notice ) {
			echo '<div class="notice notice-' . esc_attr( $notice[0] ) . ' is-dismissible"><p>' . esc_html( $notice[1] ) . '</p></div>';
		}
		echo '<nav class="nav-tab-wrapper" aria-label="' . esc_attr__( 'أقسام العروض', 'top-solution-core' ) . '">';
		foreach ( array(
			'list'      => __( 'العروض', 'top-solution-core' ),
			'simulator' => __( 'المحاكي', 'top-solution-core' ),
		) as $key => $label ) {
			$active = ( 'simulator' === $tab ) === ( 'simulator' === $key ) && '' === $offer_arg;
			echo '<a class="nav-tab' . ( $active ? ' nav-tab-active' : '' ) . '" href="' . esc_url( self::url( 'list' === $key ? array() : array( 'tab' => $key ) ) ) . '"' . ( $active ? ' aria-current="page"' : '' ) . '>' . esc_html( $label ) . '</a>';
		}
		echo '</nav>';
		if ( 'new' === $offer_arg || ( ctype_digit( $offer_arg ) && OfferRepository::get( (int) $offer_arg ) ) ) {
			self::render_editor( 'new' === $offer_arg ? 0 : (int) $offer_arg );
		} elseif ( 'simulator' === $tab ) {
			self::render_simulator();
		} else {
			self::render_list();
		}
		echo '</div>';
	}

	/**
	 * Nonce and action fields.
	 *
	 * @param string $action Action id.
	 * @return void
	 */
	private static function form_fields( $action ) {
		wp_nonce_field( self::NONCE );
		echo '<input type="hidden" name="ts_action" value="' . esc_attr( $action ) . '">';
	}

	/**
	 * Human description of what an offer does.
	 *
	 * @param array $o Offer.
	 * @return string
	 */
	private static function summary( array $o ) {
		$d = DeliveryService::decimals();
		switch ( $o['type'] ) {
			case 'qty_discount':
				$parts = array();
				foreach ( $o['tiers'] as $t ) {
					$val     = 'percent' === $t['kind'] ? OfferForm::bp_to_percent( $t['value'] ) . '%' : Money::to_decimal( $t['value'], $d );
					$parts[] = $t['min_qty'] . '+ → ' . ( 'unit_price' === $t['kind'] ? '= ' : '− ' ) . $val;
				}
				return implode( ' ، ', $parts );
			case 'bundle':
				return count( $o['items'] ) . ' ' . __( 'منتجات', 'top-solution-core' ) . ' → ' . ( 'percent' === $o['bundle_price']['kind'] ? '−' . OfferForm::bp_to_percent( $o['bundle_price']['value'] ) . '%' : Money::to_decimal( $o['bundle_price']['value'], $d ) );
			case 'free_gift':
				return '#' . $o['gift']['product_id'] . ' × ' . $o['gift']['qty'];
			default:
				$a = $o['action'];
				return OfferText::actions()[ $a['type'] ] . ( $a['value'] ? ' (' . ( in_array( $a['type'], array( 'percent_off', 'delivery_percent_off' ), true ) ? OfferForm::bp_to_percent( $a['value'] ) . '%' : Money::to_decimal( $a['value'], $d ) ) . ')' : '' );
		}
	}

	/**
	 * List.
	 *
	 * @return void
	 */
	private static function render_list() {
		$offers    = OfferRepository::all();
		$conflicts = array();
		foreach ( OfferConflicts::find( $offers ) as $pair ) {
			$conflicts[ $pair[0] ] = true;
			$conflicts[ $pair[1] ] = true;
		}
		$types    = OfferText::types();
		$statuses = OfferText::statuses();
		$groups   = OfferText::groups();
		?>
		<p class="ts-lead"><?php esc_html_e( 'محرك الأسعار على الخادم هو المرجع الوحيد للأسعار. لكل منتج عرض واحد على الأكثر يغيّر سعره، وتُضاف الهدايا وتخفيضات التوصيل فوقه. الحصري يلغي كل ما عداه.', 'top-solution-core' ); ?></p>
		<p><a class="button button-primary" href="<?php echo esc_url( self::url( array( 'offer' => 'new' ) ) ); ?>"><?php esc_html_e( 'عرض جديد', 'top-solution-core' ); ?></a></p>
		<?php if ( empty( $offers ) ) : ?>
			<p class="ts-empty"><?php esc_html_e( 'لا توجد عروض بعد. العروض اختيارية: بدونها يُباع كل منتج بسعره في ووكومرس.', 'top-solution-core' ); ?></p>
			<?php
			return;
		endif;
		?>
		<div class="ts-scroll">
		<table class="widefat striped">
			<thead><tr>
				<th scope="col"><?php esc_html_e( 'العرض', 'top-solution-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'النوع', 'top-solution-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'ماذا يفعل', 'top-solution-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'الأولوية', 'top-solution-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'الفترة', 'top-solution-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'الحالة', 'top-solution-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'إجراءات', 'top-solution-core' ); ?></th>
			</tr></thead>
			<tbody>
			<?php foreach ( $offers as $o ) : ?>
				<tr>
					<th scope="row">
						<a href="<?php echo esc_url( self::url( array( 'offer' => $o['id'] ) ) ); ?>"><?php echo esc_html( $o['label'] ); ?></a>
						<?php
						if ( $o['selectable'] ) :
							?>
							<span class="ts-badge"><?php esc_html_e( 'يختاره العميل', 'top-solution-core' ); ?></span><?php endif; ?>
						<?php
						if ( $o['exclusive'] ) :
							?>
							<span class="ts-badge ts-badge--warn"><?php esc_html_e( 'حصري', 'top-solution-core' ); ?></span><?php endif; ?>
						<?php
						if ( ! empty( $o['invalid'] ) ) :
							?>
							<span class="ts-badge ts-badge--fail"><?php esc_html_e( 'غير صالح', 'top-solution-core' ); ?></span><?php endif; ?>
						<?php
						if ( isset( $conflicts[ $o['id'] ] ) ) :
							?>
							<span class="ts-badge ts-badge--warn"><?php esc_html_e( 'تعارض محتمل', 'top-solution-core' ); ?></span><?php endif; ?>
					</th>
					<td><?php echo esc_html( $types[ $o['type'] ] . ' · ' . $groups[ OfferSchema::group( $o ) ] ); ?></td>
					<td><?php echo ! empty( $o['invalid'] ) ? '—' : esc_html( self::summary( $o ) ); ?></td>
					<td><?php echo esc_html( (string) $o['priority'] ); ?></td>
					<td>
						<?php
						$tz = wp_timezone();
						echo esc_html( ( null === $o['starts_at'] ? '…' : OfferForm::date_text( $o['starts_at'], $tz ) ) . ' → ' . ( null === $o['ends_at'] ? '…' : OfferForm::date_text( $o['ends_at'], $tz ) ) );
						?>
					</td>
					<td><?php echo esc_html( $statuses[ $o['status'] ] ); ?></td>
					<td class="ts-row-actions">
						<a class="button button-small" href="<?php echo esc_url( self::url( array( 'offer' => $o['id'] ) ) ); ?>"><?php esc_html_e( 'تعديل', 'top-solution-core' ); ?></a>
						<?php if ( 'active' === $o['status'] ) : ?>
							<?php self::mini_form( 'status_offer', $o['id'], __( 'إيقاف', 'top-solution-core' ), array( 'status' => 'draft' ) ); ?>
						<?php elseif ( empty( $o['invalid'] ) && 'archived' !== $o['status'] ) : ?>
							<?php self::mini_form( 'status_offer', $o['id'], __( 'تفعيل', 'top-solution-core' ), array( 'status' => 'active' ) ); ?>
						<?php endif; ?>
						<?php self::mini_form( 'duplicate_offer', $o['id'], __( 'نسخ', 'top-solution-core' ) ); ?>
						<?php if ( 'archived' !== $o['status'] ) : ?>
							<?php self::mini_form( 'status_offer', $o['id'], __( 'أرشفة', 'top-solution-core' ), array( 'status' => 'archived' ) ); ?>
						<?php endif; ?>
						<?php self::mini_form( 'delete_offer', $o['id'], __( 'حذف', 'top-solution-core' ), array(), 'button-link-delete' ); ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		</div>
		<?php
	}

	/**
	 * One-button POST form.
	 *
	 * @param string $action Action id.
	 * @param int    $id     Offer id.
	 * @param string $label  Button text.
	 * @param array  $extra  Extra hidden fields.
	 * @param string $css    Extra button class.
	 * @return void
	 */
	private static function mini_form( $action, $id, $label, array $extra = array(), $css = '' ) {
		echo '<form method="post" class="ts-inline">';
		self::form_fields( $action );
		echo '<input type="hidden" name="offer_id" value="' . esc_attr( (string) $id ) . '">';
		foreach ( $extra as $k => $v ) {
			echo '<input type="hidden" name="' . esc_attr( $k ) . '" value="' . esc_attr( $v ) . '">';
		}
		echo '<button type="submit" class="button button-small ' . esc_attr( $css ) . '">' . esc_html( $label ) . '</button></form>';
	}

	/**
	 * Values shown in the editor: the typed draft after a refusal, else the saved offer.
	 *
	 * @param int $id Offer id (0 = new).
	 * @return array<string,mixed>
	 */
	private static function editor_values( $id ) {
		$draft = get_transient( self::key( 'draft' ) );
		if ( is_array( $draft ) && (int) $draft['id'] === $id ) {
			return $draft['post'];
		}
		$d  = DeliveryService::decimals();
		$tz = wp_timezone();
		$o  = $id ? OfferRepository::get( $id ) : null;
		if ( ! $o ) {
			return array(
				'type'        => 'qty_discount',
				'status'      => 'draft',
				'priority'    => '0',
				'scope_mode'  => 'all',
				'bundle_kind' => 'price',
				'gift_qty'    => '1',
			);
		}
		$gift   = 'free_gift' === $o['type'] ? $o['gift'] : ( $o['action'] && 'add_gift' === $o['action']['type'] ? $o['action']['gift'] : null );
		$action = $o['action'];
		return array(
			'label'            => $o['label'],
			'type'             => $o['type'],
			'status'           => $o['status'],
			'priority'         => (string) $o['priority'],
			'exclusive'        => $o['exclusive'] ? '1' : '',
			'selectable'       => $o['selectable'] ? '1' : '',
			'starts_at'        => OfferForm::date_text( $o['starts_at'], $tz ),
			'ends_at'          => OfferForm::date_text( $o['ends_at'], $tz ),
			'scope_mode'       => $o['scope']['mode'],
			'scope_products'   => implode( ', ', $o['scope']['product_ids'] ),
			'scope_categories' => implode( ', ', $o['scope']['category_ids'] ),
			'min_qty'          => null === $o['conditions']['min_qty'] ? '' : (string) $o['conditions']['min_qty'],
			'min_subtotal'     => null === $o['conditions']['min_subtotal'] ? '' : Money::to_decimal( $o['conditions']['min_subtotal'], $d ),
			'wilaya_ids'       => array_map( 'strval', $o['conditions']['wilaya_ids'] ),
			'delivery_methods' => $o['conditions']['delivery_methods'],
			'weekdays'         => array_map( 'strval', $o['conditions']['weekdays'] ),
			'tiers'            => OfferForm::tiers_text( $o['tiers'], $d ),
			'items'            => OfferForm::items_text( $o['items'] ),
			'bundle_kind'      => $o['bundle_price'] ? $o['bundle_price']['kind'] : 'price',
			'bundle_value'     => $o['bundle_price'] ? ( 'percent' === $o['bundle_price']['kind'] ? OfferForm::bp_to_percent( $o['bundle_price']['value'] ) : Money::to_decimal( $o['bundle_price']['value'], $d ) ) : '',
			'gift_product'     => $gift ? (string) $gift['product_id'] : '',
			'gift_variation'   => $gift && $gift['variation_id'] ? (string) $gift['variation_id'] : '',
			'gift_qty'         => $gift ? (string) $gift['qty'] : '1',
			'action_type'      => $action ? $action['type'] : 'percent_off',
			'action_value'     => $action && $action['value'] ? ( in_array( $action['type'], array( 'percent_off', 'delivery_percent_off' ), true ) ? OfferForm::bp_to_percent( $action['value'] ) : Money::to_decimal( $action['value'], $d ) ) : '',
		);
	}

	/**
	 * Editor.
	 *
	 * @param int $id Offer id (0 = new).
	 * @return void
	 */
	private static function render_editor( $id ) {
		$v        = self::editor_values( $id );
		$val      = static function ( $k ) use ( $v ) {
			return isset( $v[ $k ] ) && is_string( $v[ $k ] ) ? $v[ $k ] : '';
		};
		$arr      = static function ( $k ) use ( $v ) {
			return isset( $v[ $k ] ) && is_array( $v[ $k ] ) ? array_map( 'strval', $v[ $k ] ) : array();
		};
		$decimals = DeliveryService::decimals();
		$currency = function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '';
		$days     = array(
			0 => __( 'الأحد', 'top-solution-core' ),
			1 => __( 'الاثنين', 'top-solution-core' ),
			2 => __( 'الثلاثاء', 'top-solution-core' ),
			3 => __( 'الأربعاء', 'top-solution-core' ),
			4 => __( 'الخميس', 'top-solution-core' ),
			5 => __( 'الجمعة', 'top-solution-core' ),
			6 => __( 'السبت', 'top-solution-core' ),
		);
		?>
		<p><a href="<?php echo esc_url( self::url() ); ?>">&larr; <?php esc_html_e( 'كل العروض', 'top-solution-core' ); ?></a></p>
		<form method="post" class="ts-offer-form" id="ts-offer-form">
			<?php self::form_fields( 'save_offer' ); ?>
			<input type="hidden" name="offer_id" value="<?php echo esc_attr( (string) $id ); ?>">
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="ts-o-label"><?php esc_html_e( 'اسم العرض (يظهر للعميل)', 'top-solution-core' ); ?></label></th>
					<td><input type="text" id="ts-o-label" name="label" class="regular-text" maxlength="100" value="<?php echo esc_attr( $val( 'label' ) ); ?>" required></td>
				</tr>
				<tr>
					<th scope="row"><label for="ts-o-type"><?php esc_html_e( 'نوع العرض', 'top-solution-core' ); ?></label></th>
					<td>
						<select id="ts-o-type" name="type">
							<?php foreach ( OfferText::types() as $k => $label ) : ?>
								<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $val( 'type' ), $k ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="ts-o-status"><?php esc_html_e( 'الحالة', 'top-solution-core' ); ?></label></th>
					<td>
						<select id="ts-o-status" name="status">
							<?php foreach ( OfferText::statuses() as $k => $label ) : ?>
								<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $val( 'status' ), $k ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'المسودة والمؤرشف لا يُطبَّقان أبداً.', 'top-solution-core' ); ?></p>
					</td>
				</tr>
			</table>

			<fieldset class="ts-offer-section" data-types="qty_discount">
				<legend><?php esc_html_e( 'الشرائح (خصم حسب الكمية)', 'top-solution-core' ); ?></legend>
				<label for="ts-o-tiers" class="screen-reader-text"><?php esc_html_e( 'الشرائح', 'top-solution-core' ); ?></label>
				<textarea id="ts-o-tiers" name="tiers" rows="5" class="large-text" dir="ltr"><?php echo esc_textarea( $val( 'tiers' ) ); ?></textarea>
				<p class="description">
					<?php
					/* translators: %s: currency symbol. */
					printf( esc_html__( 'سطر لكل شريحة: الكمية|النوع|القيمة. النوع: percent (نسبة مئوية)، fixed (مبلغ يُخصم من سعر الوحدة بالعملة %s)، price (سعر وحدة جديد). مثال: 2|percent|10 ثم 3|percent|15 ثم 5|price|900. تُطبَّق أعلى شريحة تبلغها الكمية.', 'top-solution-core' ), esc_html( $currency ) );
					?>
				</p>
			</fieldset>

			<fieldset class="ts-offer-section" data-types="bundle">
				<legend><?php esc_html_e( 'الحزمة', 'top-solution-core' ); ?></legend>
				<p><label for="ts-o-items"><?php esc_html_e( 'منتجات الحزمة', 'top-solution-core' ); ?></label></p>
				<textarea id="ts-o-items" name="items" rows="4" class="large-text" dir="ltr"><?php echo esc_textarea( $val( 'items' ) ); ?></textarea>
				<p class="description"><?php esc_html_e( 'سطر لكل منتج: رقم المنتج|الكمية|رقم النسخة (اختياري). الحزمة تُطبَّق فقط إذا وُجدت كل منتجاتها في الطلب.', 'top-solution-core' ); ?></p>
				<p>
					<label for="ts-o-bundle-kind"><?php esc_html_e( 'التسعير', 'top-solution-core' ); ?></label>
					<select id="ts-o-bundle-kind" name="bundle_kind">
						<option value="price" <?php selected( $val( 'bundle_kind' ), 'price' ); ?>><?php esc_html_e( 'سعر ثابت للحزمة', 'top-solution-core' ); ?></option>
						<option value="percent" <?php selected( $val( 'bundle_kind' ), 'percent' ); ?>><?php esc_html_e( 'نسبة خصم على مجموع الحزمة', 'top-solution-core' ); ?></option>
					</select>
					<label for="ts-o-bundle-value" class="screen-reader-text"><?php esc_html_e( 'قيمة الحزمة', 'top-solution-core' ); ?></label>
					<input type="text" id="ts-o-bundle-value" name="bundle_value" class="small-text" inputmode="decimal" value="<?php echo esc_attr( $val( 'bundle_value' ) ); ?>">
				</p>
			</fieldset>

			<fieldset class="ts-offer-section" data-types="free_gift custom_rule" data-action-types="add_gift">
				<legend><?php esc_html_e( 'الهدية', 'top-solution-core' ); ?></legend>
				<p>
					<label for="ts-o-gift-product"><?php esc_html_e( 'رقم منتج الهدية', 'top-solution-core' ); ?></label>
					<input type="text" id="ts-o-gift-product" name="gift_product" class="small-text" inputmode="numeric" value="<?php echo esc_attr( $val( 'gift_product' ) ); ?>">
					<label for="ts-o-gift-variation"><?php esc_html_e( 'رقم النسخة (اختياري)', 'top-solution-core' ); ?></label>
					<input type="text" id="ts-o-gift-variation" name="gift_variation" class="small-text" inputmode="numeric" value="<?php echo esc_attr( $val( 'gift_variation' ) ); ?>">
					<label for="ts-o-gift-qty"><?php esc_html_e( 'الكمية', 'top-solution-core' ); ?></label>
					<input type="text" id="ts-o-gift-qty" name="gift_qty" class="small-text" inputmode="numeric" value="<?php echo esc_attr( $val( 'gift_qty' ) ); ?>">
				</p>
				<p class="description"><?php esc_html_e( 'تُضاف بسعر صفر. إن لم تتوفر في المخزون لا يُطبَّق العرض.', 'top-solution-core' ); ?></p>
			</fieldset>

			<fieldset class="ts-offer-section" data-types="custom_rule">
				<legend><?php esc_html_e( 'الإجراء (قاعدة مخصصة)', 'top-solution-core' ); ?></legend>
				<p>
					<label for="ts-o-action"><?php esc_html_e( 'الإجراء', 'top-solution-core' ); ?></label>
					<select id="ts-o-action" name="action_type">
						<?php foreach ( OfferText::actions() as $k => $label ) : ?>
							<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $val( 'action_type' ), $k ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
					<label for="ts-o-action-value"><?php esc_html_e( 'القيمة (نسبة % أو مبلغ)', 'top-solution-core' ); ?></label>
					<input type="text" id="ts-o-action-value" name="action_value" class="small-text" inputmode="decimal" value="<?php echo esc_attr( $val( 'action_value' ) ); ?>">
				</p>
				<p class="description"><?php esc_html_e( 'إجراء واحد لكل عرض. لتوصيل مجاني مع خصم أنشئ عرضين.', 'top-solution-core' ); ?></p>
			</fieldset>

			<h2><?php esc_html_e( 'على أي منتجات؟', 'top-solution-core' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr class="ts-offer-section" data-types="qty_discount free_gift custom_rule">
					<th scope="row"><label for="ts-o-scope"><?php esc_html_e( 'النطاق', 'top-solution-core' ); ?></label></th>
					<td>
						<select id="ts-o-scope" name="scope_mode">
							<option value="all" <?php selected( $val( 'scope_mode' ), 'all' ); ?>><?php esc_html_e( 'كل المنتجات', 'top-solution-core' ); ?></option>
							<option value="products" <?php selected( $val( 'scope_mode' ), 'products' ); ?>><?php esc_html_e( 'منتجات محددة', 'top-solution-core' ); ?></option>
							<option value="categories" <?php selected( $val( 'scope_mode' ), 'categories' ); ?>><?php esc_html_e( 'تصنيفات محددة', 'top-solution-core' ); ?></option>
						</select>
						<p><label for="ts-o-products"><?php esc_html_e( 'أرقام المنتجات (مفصولة بفاصلة)', 'top-solution-core' ); ?></label><br>
						<input type="text" id="ts-o-products" name="scope_products" class="regular-text" dir="ltr" value="<?php echo esc_attr( $val( 'scope_products' ) ); ?>"></p>
						<p><label for="ts-o-cats"><?php esc_html_e( 'أرقام التصنيفات (مفصولة بفاصلة)', 'top-solution-core' ); ?></label><br>
						<input type="text" id="ts-o-cats" name="scope_categories" class="regular-text" dir="ltr" value="<?php echo esc_attr( $val( 'scope_categories' ) ); ?>"></p>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'الشروط (اختيارية)', 'top-solution-core' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="ts-o-minqty"><?php esc_html_e( 'أقل كمية', 'top-solution-core' ); ?></label></th>
					<td><input type="text" id="ts-o-minqty" name="min_qty" class="small-text" inputmode="numeric" value="<?php echo esc_attr( $val( 'min_qty' ) ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="ts-o-minsub"><?php esc_html_e( 'أقل مجموع للمنتجات', 'top-solution-core' ); ?></label></th>
					<td><input type="text" id="ts-o-minsub" name="min_subtotal" class="small-text" inputmode="decimal" value="<?php echo esc_attr( $val( 'min_subtotal' ) ); ?>"> <?php echo esc_html( $currency ); ?>
					<p class="description">
						<?php
						/* translators: %d: number of decimals. */
						printf( esc_html__( 'عدد الخانات العشرية في المتجر: %d.', 'top-solution-core' ), (int) $decimals );
						?>
					</p></td>
				</tr>
				<tr>
					<th scope="row"><label for="ts-o-wilayas"><?php esc_html_e( 'الولايات', 'top-solution-core' ); ?></label></th>
					<td>
						<select id="ts-o-wilayas" name="wilaya_ids[]" multiple size="6">
							<?php foreach ( GeoRepository::wilayas() as $w ) : ?>
								<option value="<?php echo esc_attr( (string) $w['id'] ); ?>" <?php selected( in_array( (string) $w['id'], $arr( 'wilaya_ids' ), true ) ); ?>><?php echo esc_html( $w['name_ar'] ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'بلا اختيار = كل الولايات. اضغط Ctrl للاختيار المتعدد.', 'top-solution-core' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'طريقة التوصيل', 'top-solution-core' ); ?></th>
					<td>
						<?php foreach ( DeliverySettings::method_ids() as $m ) : ?>
							<label><input type="checkbox" name="delivery_methods[]" value="<?php echo esc_attr( $m ); ?>" <?php checked( in_array( $m, $arr( 'delivery_methods' ), true ) ); ?>> <?php echo esc_html( DeliverySettings::label( $m ) ); ?></label>
						<?php endforeach; ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'أيام الأسبوع', 'top-solution-core' ); ?></th>
					<td>
						<?php foreach ( $days as $n => $name ) : ?>
							<label><input type="checkbox" name="weekdays[]" value="<?php echo esc_attr( (string) $n ); ?>" <?php checked( in_array( (string) $n, $arr( 'weekdays' ), true ) ); ?>> <?php echo esc_html( $name ); ?></label>
						<?php endforeach; ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="ts-o-start"><?php esc_html_e( 'من', 'top-solution-core' ); ?></label></th>
					<td><input type="datetime-local" id="ts-o-start" name="starts_at" value="<?php echo esc_attr( $val( 'starts_at' ) ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="ts-o-end"><?php esc_html_e( 'إلى (غير شاملة)', 'top-solution-core' ); ?></label></th>
					<td><input type="datetime-local" id="ts-o-end" name="ends_at" value="<?php echo esc_attr( $val( 'ends_at' ) ); ?>">
					<p class="description"><?php esc_html_e( 'بتوقيت المتجر. اتركهما فارغين لعرض بلا تاريخ.', 'top-solution-core' ); ?></p></td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'التعارض بين العروض', 'top-solution-core' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="ts-o-priority"><?php esc_html_e( 'الأولوية', 'top-solution-core' ); ?></label></th>
					<td><input type="number" id="ts-o-priority" name="priority" min="0" max="1000" class="small-text" value="<?php echo esc_attr( $val( 'priority' ) ); ?>">
					<p class="description"><?php esc_html_e( 'الأعلى يفوز. عند التساوي يُختار الأكبر خصماً ثم الأقدم.', 'top-solution-core' ); ?></p></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'خيارات', 'top-solution-core' ); ?></th>
					<td>
						<label><input type="checkbox" name="selectable" value="1" <?php checked( '' !== $val( 'selectable' ) ); ?>> <?php esc_html_e( 'يختاره العميل (بطاقة عرض). لا يُطبَّق إلا إذا اختاره.', 'top-solution-core' ); ?></label><br>
						<label><input type="checkbox" name="exclusive" value="1" <?php checked( '' !== $val( 'exclusive' ) ); ?>> <?php esc_html_e( 'حصري: إذا انطبق يُلغي كل العروض الأخرى.', 'top-solution-core' ); ?></label>
					</td>
				</tr>
			</table>
			<?php submit_button( __( 'حفظ العرض', 'top-solution-core' ) ); ?>
		</form>
		<?php
	}

	/**
	 * Money for display.
	 *
	 * @param int $minor Minor units.
	 * @return string HTML.
	 */
	private static function price( $minor ) {
		return wp_kses_post( wc_price( (float) Money::to_decimal( (int) $minor, DeliveryService::decimals() ) ) );
	}

	/**
	 * Price simulator: shows the engine's full reasoning.
	 *
	 * @return void
	 */
	private static function render_simulator() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only simulation: no data is written.
		$get = static function ( $k ) {
			return isset( $_GET[ $k ] ) ? sanitize_text_field( wp_unslash( $_GET[ $k ] ) ) : '';
		};
		// phpcs:enable
		$product = (int) $get( 'product' );
		$variant = (int) $get( 'variation' );
		$qty     = max( 1, (int) $get( 'qty' ) );
		$wilaya  = (int) $get( 'wilaya' );
		$method  = $get( 'method' );
		$chosen  = (int) $get( 'chosen' );
		?>
		<p class="ts-lead"><?php esc_html_e( 'جرّب أي طلب لترى أي عروض انطبقت ولماذا رُفض غيرها. المحاكي يستعمل نفس المحرك الذي سيحسب الطلبات الحقيقية، ولا يحفظ شيئاً.', 'top-solution-core' ); ?></p>
		<form method="get" class="ts-sim-form">
			<input type="hidden" name="page" value="<?php echo esc_attr( self::SLUG ); ?>">
			<input type="hidden" name="tab" value="simulator">
			<p>
				<label for="ts-s-product"><?php esc_html_e( 'رقم المنتج', 'top-solution-core' ); ?></label>
				<input type="number" id="ts-s-product" name="product" min="1" class="small-text" value="<?php echo esc_attr( $product ? (string) $product : '' ); ?>" required>
				<label for="ts-s-var"><?php esc_html_e( 'رقم النسخة (إن وجد)', 'top-solution-core' ); ?></label>
				<input type="number" id="ts-s-var" name="variation" min="0" class="small-text" value="<?php echo esc_attr( $variant ? (string) $variant : '' ); ?>">
				<label for="ts-s-qty"><?php esc_html_e( 'الكمية', 'top-solution-core' ); ?></label>
				<input type="number" id="ts-s-qty" name="qty" min="1" max="1000" class="small-text" value="<?php echo esc_attr( (string) $qty ); ?>">
			</p>
			<p>
				<label for="ts-s-wilaya"><?php esc_html_e( 'الولاية', 'top-solution-core' ); ?></label>
				<select id="ts-s-wilaya" name="wilaya">
					<option value="0"><?php esc_html_e( '— بدون —', 'top-solution-core' ); ?></option>
					<?php foreach ( GeoRepository::wilayas() as $w ) : ?>
						<option value="<?php echo esc_attr( (string) $w['id'] ); ?>" <?php selected( $wilaya, $w['id'] ); ?>><?php echo esc_html( $w['name_ar'] ); ?></option>
					<?php endforeach; ?>
				</select>
				<label for="ts-s-method"><?php esc_html_e( 'طريقة التوصيل', 'top-solution-core' ); ?></label>
				<select id="ts-s-method" name="method">
					<option value=""><?php esc_html_e( '— بدون —', 'top-solution-core' ); ?></option>
					<?php foreach ( DeliverySettings::method_ids() as $m ) : ?>
						<option value="<?php echo esc_attr( $m ); ?>" <?php selected( $method, $m ); ?>><?php echo esc_html( DeliverySettings::label( $m ) ); ?></option>
					<?php endforeach; ?>
				</select>
				<label for="ts-s-chosen"><?php esc_html_e( 'العرض الذي اختاره العميل', 'top-solution-core' ); ?></label>
				<select id="ts-s-chosen" name="chosen">
					<option value="0"><?php esc_html_e( '— لا شيء —', 'top-solution-core' ); ?></option>
					<?php foreach ( OfferRepository::all() as $o ) : ?>
						<?php if ( $o['selectable'] ) : ?>
							<option value="<?php echo esc_attr( (string) $o['id'] ); ?>" <?php selected( $chosen, $o['id'] ); ?>><?php echo esc_html( $o['label'] ); ?></option>
						<?php endif; ?>
					<?php endforeach; ?>
				</select>
				<?php submit_button( __( 'احسب', 'top-solution-core' ), 'secondary', '', false ); ?>
			</p>
		</form>
		<?php
		if ( ! $product ) {
			return;
		}
		$r = EngineInputs::price_single(
			array(
				'product_id'      => $product,
				'variation_id'    => $variant,
				'qty'             => $qty,
				'wilaya_id'       => $wilaya,
				'method'          => $method,
				'chosen_offer_id' => $chosen,
			)
		);
		if ( 'product_unavailable' === $r['error'] ) {
			echo '<div class="notice notice-error inline"><p>' . esc_html__( 'المنتج غير موجود أو غير قابل للشراء أو بلا سعر.', 'top-solution-core' ) . '</p></div>';
			return;
		}
		if ( ! $r['ok'] ) {
			echo '<div class="notice notice-error inline"><p>' . esc_html( OfferText::reason( $r['error'] ) ) . '</p></div>';
		}
		?>
		<h2><?php esc_html_e( 'النتيجة', 'top-solution-core' ); ?></h2>
		<?php if ( $r['ok'] ) : ?>
		<div class="ts-scroll">
		<table class="widefat striped ts-sim-result">
			<tbody>
			<?php foreach ( $r['lines'] as $line ) : ?>
				<tr><th scope="row"><?php esc_html_e( 'سعر الوحدة', 'top-solution-core' ); ?></th><td><?php echo self::price( $line['base_unit_minor'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in price(). ?> → <strong><?php echo self::price( $line['final_unit_minor'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong> × <?php echo esc_html( (string) $line['qty'] ); ?></td></tr>
				<?php if ( $line['floor_applied'] ) : ?>
					<tr><th scope="row"></th><td><span class="ts-badge ts-badge--warn"><?php esc_html_e( 'طُبّق الحد الأدنى للسعر', 'top-solution-core' ); ?></span></td></tr>
				<?php endif; ?>
			<?php endforeach; ?>
			<?php foreach ( $r['gifts'] as $g ) : ?>
				<tr><th scope="row"><?php esc_html_e( 'هدية', 'top-solution-core' ); ?></th><td>#<?php echo esc_html( (string) $g['product_id'] ); ?> × <?php echo esc_html( (string) $g['qty'] ); ?></td></tr>
			<?php endforeach; ?>
			<tr><th scope="row"><?php esc_html_e( 'مجموع المنتجات', 'top-solution-core' ); ?></th><td><?php echo self::price( $r['lines_total_minor'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td></tr>
			<?php if ( $r['order_discount_minor'] ) : ?>
				<tr><th scope="row"><?php esc_html_e( 'خصم على الطلب', 'top-solution-core' ); ?></th><td>− <?php echo self::price( $r['order_discount_minor'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td></tr>
			<?php endif; ?>
			<tr><th scope="row"><?php esc_html_e( 'التوصيل', 'top-solution-core' ); ?></th><td>
				<?php
				if ( ! $r['delivery']['selected'] ) {
					esc_html_e( 'غير محدد (اختر الولاية والطريقة، أو لا يوجد سعر)', 'top-solution-core' );
				} else {
					echo self::price( $r['delivery']['final_fee_minor'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					if ( $r['delivery']['discount_minor'] ) {
						echo ' <span class="description">(' . esc_html__( 'الأصل', 'top-solution-core' ) . ' ' . self::price( $r['delivery']['base_fee_minor'] ) . ')</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
				}
				?>
			</td></tr>
			<tr><th scope="row"><?php esc_html_e( 'الإجمالي', 'top-solution-core' ); ?></th><td><strong><?php echo self::price( $r['grand_total_minor'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong> <span class="description">(<?php esc_html_e( 'توفير', 'top-solution-core' ); ?> <?php echo self::price( $r['discount_total_minor'] + $r['delivery']['discount_minor'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>)</span></td></tr>
			</tbody>
		</table>
		</div>
		<h3><?php esc_html_e( 'العروض المطبّقة', 'top-solution-core' ); ?></h3>
			<?php if ( empty( $r['applied'] ) ) : ?>
			<p><?php esc_html_e( 'لا شيء. يُباع المنتج بسعره العادي.', 'top-solution-core' ); ?></p>
		<?php else : ?>
			<ul class="ts-sim-list">
				<?php foreach ( $r['applied'] as $a ) : ?>
					<li><?php echo esc_html( $a['label'] . ' — ' . OfferText::groups()[ $a['group'] ] . ' (#' . $a['offer_id'] . ' v' . $a['version'] . ')' ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<?php endif; ?>
		<?php if ( ! empty( $r['rejected'] ) ) : ?>
			<h3><?php esc_html_e( 'عروض لم تُطبَّق ولماذا', 'top-solution-core' ); ?></h3>
			<ul class="ts-sim-list">
				<?php foreach ( $r['rejected'] as $x ) : ?>
					<li><?php echo esc_html( $x['label'] . ' (#' . $x['offer_id'] . ') — ' . OfferText::reason( $x['reason'] ) ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<?php
	}
}
