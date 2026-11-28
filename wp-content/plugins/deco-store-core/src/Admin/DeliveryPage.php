<?php
/**
 * Déco Store > Delivery: settings, geography, rates, pickup points and a
 * price preview. All writes: POST + nonce + ds_manage_delivery, then
 * redirect (no resubmission). Imports always show a dry run first; the real
 * import applies exactly the previewed rows, kept on the server.
 *
 * @package DecoStore
 */

namespace DecoStore\Admin;

use DecoStore\Core\Capabilities;
use DecoStore\Delivery\CsvImporter;
use DecoStore\Delivery\DeliveryService;
use DecoStore\Delivery\DeliverySettings;
use DecoStore\Delivery\GeoCode;
use DecoStore\Delivery\GeoRepository;
use DecoStore\Delivery\Importer;
use DecoStore\Delivery\Money;
use DecoStore\Delivery\PickupRepository;
use DecoStore\Delivery\RateRepository;
use DecoStore\Delivery\ZoneSetup;

defined( 'ABSPATH' ) || exit;

/**
 * Delivery admin screen.
 */
class DeliveryPage {

	const SLUG  = 'deco-store-delivery';
	const NONCE = 'ds_delivery';

	/**
	 * Add the sub-menu and its POST handler.
	 *
	 * @param string $parent_slug Parent menu slug.
	 * @return void
	 */
	public static function register_menu( $parent_slug ) {
		$hook = add_submenu_page(
			$parent_slug,
			__( 'التوصيل', 'deco-store-core' ),
			__( 'التوصيل', 'deco-store-core' ),
			Capabilities::MANAGE_DELIVERY,
			self::SLUG,
			array( __CLASS__, 'render' )
		);
		if ( $hook ) {
			add_action( 'load-' . $hook, array( __CLASS__, 'handle_post' ) );
		}
	}

	/**
	 * Tabs.
	 *
	 * @return array<string,string>
	 */
	private static function tabs() {
		return array(
			'settings'  => __( 'الإعدادات', 'deco-store-core' ),
			'geography' => __( 'الولايات والبلديات', 'deco-store-core' ),
			'rates'     => __( 'الأسعار', 'deco-store-core' ),
			'pickups'   => __( 'مكاتب التوصيل', 'deco-store-core' ),
			'preview'   => __( 'معاينة السعر', 'deco-store-core' ),
		);
	}

	/**
	 * URL of a tab.
	 *
	 * @param string $tab  Tab id.
	 * @param array  $args Extra query args.
	 * @return string
	 */
	private static function url( $tab, array $args = array() ) {
		return add_query_arg(
			array_merge(
				array(
					'page' => self::SLUG,
					'tab'  => $tab,
				),
				$args
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Per-user transient key.
	 *
	 * @param string $what notice | import.
	 * @return string
	 */
	private static function key( $what ) {
		return 'ds_delivery_' . $what . '_' . get_current_user_id();
	}

	/**
	 * Queue a notice for the next page view.
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
	 * Handle a POST, then redirect.
	 *
	 * @return void
	 */
	public static function handle_post() {
		if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Compared to a constant.
			return;
		}
		if ( ! current_user_can( Capabilities::MANAGE_DELIVERY ) ) {
			wp_die( esc_html__( 'ليست لديك صلاحية تعديل إعدادات التوصيل.', 'deco-store-core' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( self::NONCE );
		$action = isset( $_POST['ds_action'] ) ? sanitize_key( wp_unslash( $_POST['ds_action'] ) ) : '';
		$tab    = 'settings';

		switch ( $action ) {
			case 'save_settings':
				$tab = 'settings';
				self::save_settings();
				break;
			case 'setup_zone':
				$tab    = 'settings';
				$result = ZoneSetup::ensure();
				self::notice( 'success', $result['created'] ? __( 'تم إنشاء منطقة الشحن «الجزائر» في ووكومرس مع طريقة التوصيل.', 'deco-store-core' ) : __( 'منطقة الشحن موجودة مسبقاً.', 'deco-store-core' ) );
				break;
			case 'save_rates':
				$tab = 'rates';
				self::save_rates();
				break;
			case 'toggle_area':
				$tab = 'geography';
				$id  = isset( $_POST['geo_id'] ) ? absint( $_POST['geo_id'] ) : 0;
				$geo = GeoRepository::get( $id );
				if ( $geo ) {
					GeoRepository::set_active( $id, ! $geo['active'] );
					self::notice( 'success', $geo['active'] ? __( 'تم إيقاف التوصيل لهذه الولاية.', 'deco-store-core' ) : __( 'تم تفعيل الولاية.', 'deco-store-core' ) );
				}
				break;
			case 'toggle_pickup':
			case 'delete_pickup':
				$tab   = 'pickups';
				$id    = isset( $_POST['pickup_id'] ) ? absint( $_POST['pickup_id'] ) : 0;
				$point = PickupRepository::get( $id );
				if ( $point && 'delete_pickup' === $action ) {
					PickupRepository::delete( $id );
					self::notice( 'success', __( 'تم حذف المكتب.', 'deco-store-core' ) );
				} elseif ( $point ) {
					PickupRepository::set_active( $id, ! $point['active'] );
					self::notice( 'success', __( 'تم تحديث المكتب.', 'deco-store-core' ) );
				}
				break;
			case 'import_preview':
				$type = isset( $_POST['import_type'] ) ? sanitize_key( wp_unslash( $_POST['import_type'] ) ) : '';
				$tab  = 'geography' === $type ? 'geography' : ( 'rates' === $type ? 'rates' : 'pickups' );
				self::import_preview( $type );
				break;
			case 'import_commit':
				$pending = get_transient( self::key( 'import' ) );
				$tab     = is_array( $pending ) ? $pending['tab'] : 'settings';
				self::import_commit();
				break;
			case 'import_cancel':
				$pending = get_transient( self::key( 'import' ) );
				$tab     = is_array( $pending ) ? $pending['tab'] : 'settings';
				delete_transient( self::key( 'import' ) );
				break;
		}
		wp_safe_redirect( self::url( $tab ) );
		exit;
	}

	/**
	 * Save the settings tab.
	 *
	 * @return void
	 */
	private static function save_settings() {
		$raw    = isset( $_POST['delivery'] ) ? wp_unslash( $_POST['delivery'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing -- Validated by DeliverySettings::sanitize(); nonce checked in handle_post().
		$result = DeliverySettings::sanitize( $raw, DeliveryService::decimals() );
		update_option( DeliverySettings::OPTION, $result['settings'], false );
		if ( $result['errors'] ) {
			self::notice( 'error', __( 'حد التوصيل المجاني غير صالح: اكتب مبلغاً أكبر من صفر أو اتركه فارغاً. تم حفظ باقي الإعدادات.', 'deco-store-core' ) );
		} else {
			self::notice( 'success', __( 'تم حفظ إعدادات التوصيل.', 'deco-store-core' ) );
		}
	}

	/**
	 * Save the rates matrix (wilaya level and defaults; commune overrides
	 * come from CSV import).
	 *
	 * @return void
	 */
	private static function save_rates() {
		$fees     = isset( $_POST['fee'] ) && is_array( $_POST['fee'] ) ? wp_unslash( $_POST['fee'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing -- Each value is parsed by Money::to_minor(); nonce checked in handle_post().
		$off      = isset( $_POST['off'] ) && is_array( $_POST['off'] ) ? wp_unslash( $_POST['off'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing -- Only keys are read and cast to int; nonce checked in handle_post().
		$decimals = DeliveryService::decimals();
		$allowed  = array( 0 => true );
		foreach ( GeoRepository::wilayas() as $wilaya ) {
			$allowed[ $wilaya['id'] ] = true;
		}
		$bad     = array();
		$ignored = 0;
		foreach ( $allowed as $geo_id => $unused ) {
			foreach ( DeliverySettings::method_ids() as $method ) {
				$raw = isset( $fees[ $geo_id ][ $method ] ) ? trim( (string) $fees[ $geo_id ][ $method ] ) : '';
				if ( ! empty( $off[ $geo_id ][ $method ] ) ) {
					RateRepository::upsert( $method, $geo_id, 0, false, $decimals );
					if ( '' !== $raw ) {
						++$ignored; // "Not served" wins over a typed price; tell the owner.
					}
					continue;
				}
				if ( '' === $raw ) {
					RateRepository::delete( $method, $geo_id );
					continue;
				}
				$minor = Money::to_minor( $raw, $decimals );
				if ( null === $minor ) {
					$bad[] = $raw;
					continue;
				}
				RateRepository::upsert( $method, $geo_id, $minor, true, $decimals );
			}
		}
		if ( $ignored ) {
			/* translators: %d: number of prices. */
			self::notice( 'warning', sprintf( _n( 'تم تجاهل %d سعر لأن خانة «غير مخدومة» محددة بجانبه. أزل التحديد لتفعيل السعر.', 'تم تجاهل %d أسعار لأن خانة «غير مخدومة» محددة بجانبها. أزل التحديد لتفعيل الأسعار.', $ignored, 'deco-store-core' ), $ignored ) );
		}
		if ( $bad ) {
			/* translators: %s: invalid values. */
			self::notice( 'error', sprintf( __( 'لم تُحفظ هذه القيم لأنها ليست مبالغ صالحة: %s', 'deco-store-core' ), implode( '، ', array_map( 'sanitize_text_field', array_slice( $bad, 0, 10 ) ) ) ) );
		}
		self::notice( 'success', __( 'تم حفظ الأسعار.', 'deco-store-core' ) );
	}

	/**
	 * Parse an uploaded or pasted CSV and keep a dry-run report.
	 *
	 * @param string $type Import type.
	 * @return void
	 */
	private static function import_preview( $type ) {
		if ( ! in_array( $type, array( 'geography', 'rates', 'pickups' ), true ) ) {
			return;
		}
		$csv = '';
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce checked in handle_post().
		if ( ! empty( $_FILES['csv_file']['tmp_name'] ) && is_uploaded_file( $_FILES['csv_file']['tmp_name'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$size = isset( $_FILES['csv_file']['size'] ) ? (int) $_FILES['csv_file']['size'] : 0;
			if ( $size > CsvImporter::MAX_BYTES ) {
				self::notice( 'error', self::csv_error_text( 'too_large' ) );
				return;
			}
			$csv = (string) file_get_contents( $_FILES['csv_file']['tmp_name'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local uploaded temp file.
		} elseif ( isset( $_POST['csv_text'] ) ) {
			$csv = (string) wp_unslash( $_POST['csv_text'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Parsed and validated by CsvImporter.
		}
		$source = isset( $_POST['source'] ) ? CsvImporter::clean_name( wp_unslash( $_POST['source'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Cleaned by clean_name().
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		$decimals = DeliveryService::decimals();
		$parsed   = CsvImporter::parse( $type, $csv, $decimals );
		if ( '' !== $parsed['header_error'] ) {
			self::notice( 'error', self::csv_error_text( $parsed['header_error'] ) );
			return;
		}
		$report = Importer::run( $type, $parsed['rows'], true, $decimals, $source );
		set_transient(
			self::key( 'import' ),
			array(
				'type'   => $type,
				'tab'    => 'geography' === $type ? 'geography' : ( 'rates' === $type ? 'rates' : 'pickups' ),
				'rows'   => $parsed['rows'],
				'errors' => $parsed['errors'],
				'report' => $report,
				'source' => $source,
			),
			HOUR_IN_SECONDS
		);
	}

	/**
	 * Apply the previewed import.
	 *
	 * @return void
	 */
	private static function import_commit() {
		$pending = get_transient( self::key( 'import' ) );
		delete_transient( self::key( 'import' ) );
		if ( ! is_array( $pending ) || empty( $pending['rows'] ) ) {
			self::notice( 'error', __( 'لا توجد معاينة جاهزة للاستيراد. ارفع الملف مرة أخرى.', 'deco-store-core' ) );
			return;
		}
		$report = Importer::run( $pending['type'], $pending['rows'], false, DeliveryService::decimals(), $pending['source'] );
		/* translators: 1: created rows, 2: updated rows, 3: skipped rows. */
		self::notice( 'success', sprintf( __( 'تم الاستيراد: %1$d جديد، %2$d محدَّث، %3$d متجاوَز.', 'deco-store-core' ), $report['created'], $report['updated'], count( $report['skipped'] ) ) );
	}

	/**
	 * Arabic text of a CSV error code.
	 *
	 * @param string $code Error code.
	 * @return string
	 */
	public static function csv_error_text( $code ) {
		if ( 0 === strpos( $code, 'missing:' ) ) {
			/* translators: %s: column names. */
			return sprintf( __( 'أعمدة ناقصة في السطر الأول: %s', 'deco-store-core' ), substr( $code, 8 ) );
		}
		if ( 0 === strpos( $code, 'duplicate_of_line:' ) ) {
			/* translators: %s: line number. */
			return sprintf( __( 'مكرر (نفس السطر %s)', 'deco-store-core' ), substr( $code, 18 ) );
		}
		$texts = array(
			'empty'                                  => __( 'الملف فارغ.', 'deco-store-core' ),
			'too_large'                              => __( 'الملف أكبر من 2 ميغابايت.', 'deco-store-core' ),
			'too_many_rows'                          => __( 'الملف يتجاوز 20000 سطر.', 'deco-store-core' ),
			'not_utf8'                               => __( 'الملف ليس بترميز UTF-8. احفظه كـ «CSV UTF-8».', 'deco-store-core' ),
			'unknown_type'                           => __( 'نوع استيراد غير معروف.', 'deco-store-core' ),
			'bad_wilaya_code'                        => __( 'رمز ولاية غير صالح', 'deco-store-core' ),
			'bad_commune_code'                       => __( 'رمز بلدية غير صالح', 'deco-store-core' ),
			'bad_active'                             => __( 'قيمة active غير صالحة (1 أو 0)', 'deco-store-core' ),
			'missing_wilaya_name'                    => __( 'اسم الولاية ناقص', 'deco-store-core' ),
			'commune_code_and_name_go_together'      => __( 'رمز البلدية واسمها يجب أن يُكتبا معاً', 'deco-store-core' ),
			'bad_method'                             => __( 'طريقة غير معروفة (home أو office)', 'deco-store-core' ),
			'bad_fee'                                => __( 'سعر غير صالح', 'deco-store-core' ),
			'missing_name'                           => __( 'اسم المكتب ناقص', 'deco-store-core' ),
			'unknown_wilaya'                         => __( 'الولاية غير موجودة (استورد الولايات أولاً)', 'deco-store-core' ),
			'unknown_commune'                        => __( 'البلدية غير موجودة', 'deco-store-core' ),
			'commune_code_belongs_to_another_wilaya' => __( 'رمز البلدية مسجّل في ولاية أخرى', 'deco-store-core' ),
		);
		return isset( $texts[ $code ] ) ? $texts[ $code ] : $code;
	}

	/**
	 * Render the page.
	 *
	 * @return void
	 */
	public static function render() {
		if ( ! current_user_can( Capabilities::MANAGE_DELIVERY ) ) {
			wp_die( esc_html__( 'ليست لديك صلاحية عرض هذه الصفحة.', 'deco-store-core' ), '', array( 'response' => 403 ) );
		}
		$tabs = self::tabs();
		$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'settings'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only tab selection.
		$tab  = isset( $tabs[ $tab ] ) ? $tab : 'settings';
		echo '<div class="wrap ds-admin"><h1>' . esc_html__( 'Déco Store — التوصيل', 'deco-store-core' ) . '</h1>';

		$notices = get_transient( self::key( 'notice' ) );
		delete_transient( self::key( 'notice' ) );
		foreach ( is_array( $notices ) ? $notices : array() as $notice ) {
			echo '<div class="notice notice-' . esc_attr( $notice[0] ) . ' is-dismissible"><p>' . esc_html( $notice[1] ) . '</p></div>';
		}

		echo '<nav class="nav-tab-wrapper" aria-label="' . esc_attr__( 'أقسام التوصيل', 'deco-store-core' ) . '">';
		foreach ( $tabs as $key => $label ) {
			echo '<a class="nav-tab' . ( $key === $tab ? ' nav-tab-active' : '' ) . '" href="' . esc_url( self::url( $key ) ) . '"' . ( $key === $tab ? ' aria-current="page"' : '' ) . '>' . esc_html( $label ) . '</a>';
		}
		echo '</nav>';

		$pending = get_transient( self::key( 'import' ) );
		if ( is_array( $pending ) && $pending['tab'] === $tab ) {
			self::render_import_preview( $pending );
		}

		switch ( $tab ) {
			case 'geography':
				self::render_geography();
				break;
			case 'rates':
				self::render_rates();
				break;
			case 'pickups':
				self::render_pickups();
				break;
			case 'preview':
				self::render_preview();
				break;
			default:
				self::render_settings();
		}
		echo '</div>';
	}

	/**
	 * Hidden nonce + action fields.
	 *
	 * @param string $action Action id.
	 * @return void
	 */
	private static function form_fields( $action ) {
		wp_nonce_field( self::NONCE );
		echo '<input type="hidden" name="ds_action" value="' . esc_attr( $action ) . '">';
	}

	/**
	 * Settings tab.
	 *
	 * @return void
	 */
	private static function render_settings() {
		$settings = DeliverySettings::all();
		$zone_id  = ZoneSetup::existing_zone_id();
		?>
		<form method="post" class="ds-form">
			<?php self::form_fields( 'save_settings' ); ?>
			<h2><?php esc_html_e( 'طرق التوصيل', 'deco-store-core' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php foreach ( DeliverySettings::method_ids() as $id ) : ?>
					<tr>
						<th scope="row"><?php echo esc_html( 'home' === $id ? __( 'التوصيل إلى المنزل', 'deco-store-core' ) : __( 'الاستلام من مكتب التوصيل', 'deco-store-core' ) ); ?></th>
						<td>
							<label><input type="checkbox" name="delivery[methods][<?php echo esc_attr( $id ); ?>][enabled]" value="1" <?php checked( ! empty( $settings['methods'][ $id ]['enabled'] ) ); ?>> <?php esc_html_e( 'مفعّلة', 'deco-store-core' ); ?></label>
							<p><label for="ds-label-<?php echo esc_attr( $id ); ?>"><?php esc_html_e( 'الاسم الظاهر للعميل', 'deco-store-core' ); ?></label><br>
							<input type="text" class="regular-text" id="ds-label-<?php echo esc_attr( $id ); ?>" name="delivery[methods][<?php echo esc_attr( $id ); ?>][label]" value="<?php echo esc_attr( $settings['methods'][ $id ]['label'] ); ?>"></p>
						</td>
					</tr>
				<?php endforeach; ?>
				<tr>
					<th scope="row"><label for="ds-free"><?php esc_html_e( 'توصيل مجاني ابتداءً من', 'deco-store-core' ); ?></label></th>
					<td>
						<input type="text" inputmode="decimal" id="ds-free" name="delivery[free_threshold]" value="<?php echo esc_attr( $settings['free_threshold'] ); ?>" class="small-text" dir="ltr"> <?php echo esc_html( function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '' ); ?>
						<p class="description"><?php esc_html_e( 'اتركه فارغاً لإيقافه. يُطبَّق على كل الطرق والمناطق المخدومة فقط.', 'deco-store-core' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button( __( 'حفظ الإعدادات', 'deco-store-core' ) ); ?>
		</form>

		<h2><?php esc_html_e( 'ربط ووكومرس', 'deco-store-core' ); ?></h2>
		<?php if ( $zone_id ) : ?>
			<p><span class="ds-badge ds-badge--ok">✓ <?php esc_html_e( 'سليم', 'deco-store-core' ); ?></span> <?php esc_html_e( 'منطقة شحن ووكومرس تستخدم طريقة التوصيل هذه.', 'deco-store-core' ); ?></p>
		<?php else : ?>
			<p><?php esc_html_e( 'تحتاج الطلبات إلى منطقة شحن «الجزائر» في ووكومرس تستخدم طريقة Déco Store.', 'deco-store-core' ); ?></p>
			<form method="post"><?php self::form_fields( 'setup_zone' ); ?><?php submit_button( __( 'إنشاء منطقة الشحن', 'deco-store-core' ), 'secondary', 'submit', false ); ?></form>
		<?php endif; ?>
		<?php
	}

	/**
	 * Import form.
	 *
	 * @param string $type    Import type.
	 * @param string $example Example CSV (header + one fictional line).
	 * @return void
	 */
	private static function render_import_form( $type, $example ) {
		$columns = CsvImporter::columns()[ $type ];
		?>
		<details class="ds-import">
			<summary><?php esc_html_e( 'استيراد من ملف CSV', 'deco-store-core' ); ?></summary>
			<p>
				<?php esc_html_e( 'أعمدة إلزامية:', 'deco-store-core' ); ?> <code dir="ltr"><?php echo esc_html( implode( ', ', $columns['required'] ) ); ?></code>
				· <?php esc_html_e( 'اختيارية:', 'deco-store-core' ); ?> <code dir="ltr"><?php echo esc_html( implode( ', ', $columns['optional'] ) ); ?></code>
			</p>
			<p><?php esc_html_e( 'مثال على الشكل (بيانات وهمية):', 'deco-store-core' ); ?></p>
			<pre class="ds-code" dir="ltr"><?php echo esc_html( $example ); ?></pre>
			<form method="post" enctype="multipart/form-data">
				<?php self::form_fields( 'import_preview' ); ?>
				<input type="hidden" name="import_type" value="<?php echo esc_attr( $type ); ?>">
				<p><label for="ds-file-<?php echo esc_attr( $type ); ?>"><?php esc_html_e( 'ملف CSV (UTF-8، الفاصل , أو ;)', 'deco-store-core' ); ?></label><br>
				<input type="file" id="ds-file-<?php echo esc_attr( $type ); ?>" name="csv_file" accept=".csv,text/csv"></p>
				<p><label for="ds-text-<?php echo esc_attr( $type ); ?>"><?php esc_html_e( 'أو الصق المحتوى هنا', 'deco-store-core' ); ?></label><br>
				<textarea id="ds-text-<?php echo esc_attr( $type ); ?>" name="csv_text" rows="5" class="large-text code" dir="ltr"></textarea></p>
				<?php if ( 'geography' === $type ) : ?>
					<p><label for="ds-source"><?php esc_html_e( 'مصدر القائمة (مثلاً: المرجع الرسمي الذي اعتمدته)', 'deco-store-core' ); ?></label><br>
					<input type="text" id="ds-source" name="source" class="regular-text"></p>
				<?php endif; ?>
				<?php submit_button( __( 'معاينة الاستيراد', 'deco-store-core' ), 'secondary', 'submit', false ); ?>
				<span class="description"><?php esc_html_e( 'لا يُكتب أي شيء قبل تأكيدك بعد المعاينة.', 'deco-store-core' ); ?></span>
			</form>
		</details>
		<?php
	}

	/**
	 * Dry-run report with confirm / cancel.
	 *
	 * @param array $pending Pending import.
	 * @return void
	 */
	private static function render_import_preview( array $pending ) {
		$report = $pending['report'];
		$issues = $pending['errors'] + $report['skipped'];
		ksort( $issues );
		?>
		<div class="ds-card ds-preview" role="region" aria-label="<?php esc_attr_e( 'معاينة الاستيراد', 'deco-store-core' ); ?>">
			<h2><?php esc_html_e( 'معاينة الاستيراد — لم يُحفظ شيء بعد', 'deco-store-core' ); ?></h2>
			<p class="ds-summary">
				<span class="ds-pill ds-pill--ok"><?php /* translators: %d: count. */ echo esc_html( sprintf( __( '%d جديد', 'deco-store-core' ), $report['created'] ) ); ?></span>
				<span class="ds-pill"><?php /* translators: %d: count. */ echo esc_html( sprintf( __( '%d تحديث', 'deco-store-core' ), $report['updated'] ) ); ?></span>
				<span class="ds-pill ds-pill--<?php echo $issues ? 'warn' : 'ok'; ?>"><?php /* translators: %d: count. */ echo esc_html( sprintf( __( '%d سطر مرفوض', 'deco-store-core' ), count( $issues ) ) ); ?></span>
			</p>
			<?php if ( $issues ) : ?>
				<table class="widefat striped ds-issues">
					<thead><tr><th scope="col"><?php esc_html_e( 'السطر', 'deco-store-core' ); ?></th><th scope="col"><?php esc_html_e( 'السبب', 'deco-store-core' ); ?></th></tr></thead>
					<tbody>
					<?php foreach ( array_slice( $issues, 0, 50, true ) as $line => $code ) : ?>
						<tr><td><?php echo esc_html( (string) $line ); ?></td><td><?php echo esc_html( self::csv_error_text( $code ) ); ?></td></tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<?php if ( count( $issues ) > 50 ) : ?>
					<p><?php /* translators: %d: hidden count. */ echo esc_html( sprintf( __( '… و%d سطراً آخر.', 'deco-store-core' ), count( $issues ) - 50 ) ); ?></p>
				<?php endif; ?>
			<?php endif; ?>
			<div class="ds-actions">
				<?php if ( $report['created'] + $report['updated'] > 0 ) : ?>
					<form method="post"><?php self::form_fields( 'import_commit' ); ?><?php submit_button( __( 'تأكيد الاستيراد', 'deco-store-core' ), 'primary', 'submit', false ); ?></form>
				<?php endif; ?>
				<form method="post"><?php self::form_fields( 'import_cancel' ); ?><?php submit_button( __( 'إلغاء', 'deco-store-core' ), 'secondary', 'submit', false ); ?></form>
			</div>
		</div>
		<?php
	}

	/**
	 * Geography tab.
	 *
	 * @return void
	 */
	private static function render_geography() {
		$wilayas = GeoRepository::wilayas();
		$counts  = GeoRepository::commune_counts();
		echo '<p class="ds-lead">' . esc_html__( 'قائمة الولايات والبلديات يضيفها صاحب المتجر من مصدر رسمي يعتمده. لا تأتي الإضافة بأي قائمة جاهزة.', 'deco-store-core' ) . '</p>';
		self::render_import_form( 'geography', "wilaya_code,wilaya_name_ar,commune_code,commune_name_ar\n90,ولاية تجريبية,9001,بلدية تجريبية" );
		if ( empty( $wilayas ) ) {
			echo '<p class="ds-empty">' . esc_html__( 'لا توجد ولايات بعد. استورد القائمة من ملف CSV.', 'deco-store-core' ) . '</p>';
			return;
		}
		/* translators: 1: wilayas, 2: communes. */
		echo '<p>' . esc_html( sprintf( __( '%1$d ولاية · %2$d بلدية', 'deco-store-core' ), count( $wilayas ), GeoRepository::count( 'commune' ) ) ) . '</p>';
		?>
		<div class="ds-scroll">
		<table class="widefat striped">
			<thead><tr>
				<th scope="col"><?php esc_html_e( 'الرمز', 'deco-store-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'الولاية', 'deco-store-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'البلديات', 'deco-store-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'رمز ووكومرس', 'deco-store-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'الحالة', 'deco-store-core' ); ?></th>
			</tr></thead>
			<tbody>
			<?php foreach ( $wilayas as $w ) : ?>
				<?php $state = GeoCode::to_wc_state( $w['code'] ); ?>
				<tr>
					<td dir="ltr"><?php echo esc_html( GeoCode::display_wilaya( $w['code'] ) ); ?></td>
					<td><?php echo esc_html( $w['name_ar'] ); ?></td>
					<td><?php echo esc_html( (string) ( isset( $counts[ $w['id'] ] ) ? $counts[ $w['id'] ] : 0 ) ); ?></td>
					<td dir="ltr"><?php echo $state ? esc_html( $state ) : '<span class="ds-badge ds-badge--warn">! ' . esc_html__( 'لا يوجد', 'deco-store-core' ) . '</span>'; ?></td>
					<td>
						<form method="post" class="ds-inline">
							<?php self::form_fields( 'toggle_area' ); ?>
							<input type="hidden" name="geo_id" value="<?php echo esc_attr( (string) $w['id'] ); ?>">
							<span class="ds-badge ds-badge--<?php echo $w['active'] ? 'ok' : 'fail'; ?>"><?php echo $w['active'] ? '✓ ' . esc_html__( 'مخدومة', 'deco-store-core' ) : '✕ ' . esc_html__( 'موقوفة', 'deco-store-core' ); ?></span>
							<button type="submit" class="button button-small"><?php echo $w['active'] ? esc_html__( 'إيقاف', 'deco-store-core' ) : esc_html__( 'تفعيل', 'deco-store-core' ); ?></button>
						</form>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		</div>
		<p class="description"><?php esc_html_e( 'رمز ووكومرس يُستخدم فقط في السلة القياسية لووكومرس (DZ-01 إلى DZ-58). صفحات الهبوط تستخدم هذه القائمة مباشرة.', 'deco-store-core' ); ?></p>
		<?php
	}

	/**
	 * Areas with no price for an enabled method (no wilaya rate and no default).
	 *
	 * @param array $wilayas Wilayas.
	 * @param array $matrix  Rate matrix.
	 * @return array<string,string[]> method => wilaya names.
	 */
	private static function coverage_gaps( array $wilayas, array $matrix ) {
		$gaps = array();
		foreach ( DeliverySettings::enabled_methods() as $method ) {
			if ( ! empty( $matrix[0][ $method ] ) ) {
				continue; // A default price covers every wilaya without its own rate.
			}
			foreach ( $wilayas as $w ) {
				if ( $w['active'] && empty( $matrix[ $w['id'] ][ $method ] ) ) {
					$gaps[ $method ][] = $w['name_ar'];
				}
			}
		}
		return $gaps;
	}

	/**
	 * Rates tab.
	 *
	 * @return void
	 */
	private static function render_rates() {
		$decimals          = DeliveryService::decimals();
		$wilayas           = GeoRepository::wilayas();
		$matrix            = RateRepository::matrix( $decimals );
		$methods           = DeliverySettings::method_ids();
		$commune_overrides = 0;
		$wilaya_ids        = array_flip( array_column( $wilayas, 'id' ) );
		foreach ( array_keys( $matrix ) as $geo_id ) {
			if ( $geo_id && ! isset( $wilaya_ids[ $geo_id ] ) ) {
				$commune_overrides += count( $matrix[ $geo_id ] );
			}
		}
		echo '<p class="ds-lead">' . esc_html__( 'خانة فارغة = لا سعر على هذا المستوى (يُستخدم السعر الافتراضي إن وُجد). «غير مخدومة» = لا توصيل حتى لو وُجد سعر افتراضي. 0 = توصيل مجاني.', 'deco-store-core' ) . '</p>';

		$gaps = self::coverage_gaps( $wilayas, $matrix );
		if ( $gaps ) {
			echo '<div class="notice notice-warning inline"><p><strong>' . esc_html__( 'ولايات مفعّلة بلا سعر:', 'deco-store-core' ) . '</strong></p><ul>';
			foreach ( $gaps as $method => $names ) {
				echo '<li>' . esc_html( DeliverySettings::label( $method ) . ': ' . implode( '، ', array_slice( $names, 0, 20 ) ) . ( count( $names ) > 20 ? ' …' : '' ) ) . '</li>';
			}
			echo '</ul></div>';
		}
		if ( $commune_overrides ) {
			/* translators: %d: count. */
			echo '<p>' . esc_html( sprintf( __( '%d سعراً خاصاً بالبلديات (تُدار عبر استيراد CSV).', 'deco-store-core' ), $commune_overrides ) ) . '</p>';
		}
		self::render_import_form( 'rates', "method,wilaya_code,commune_code,fee,active\nhome,90,,400,1\noffice,90,9001,250,1" );
		$rows = array_merge(
			array(
				array(
					'id'      => 0,
					'code'    => '',
					'name_ar' => __( 'السعر الافتراضي (كل الولايات بلا سعر خاص)', 'deco-store-core' ),
				),
			),
			$wilayas
		);
		?>
		<form method="post">
			<?php self::form_fields( 'save_rates' ); ?>
			<div class="ds-scroll">
			<table class="widefat striped ds-matrix">
				<thead><tr>
					<th scope="col"><?php esc_html_e( 'المنطقة', 'deco-store-core' ); ?></th>
					<?php foreach ( $methods as $method ) : ?>
						<th scope="col"><?php echo esc_html( DeliverySettings::label( $method ) ); ?></th>
					<?php endforeach; ?>
				</tr></thead>
				<tbody>
				<?php foreach ( $rows as $row ) : ?>
					<tr<?php echo 0 === $row['id'] ? ' class="ds-default-row"' : ''; ?>>
						<th scope="row"><?php echo esc_html( ( '' !== $row['code'] ? GeoCode::display_wilaya( $row['code'] ) . ' · ' : '' ) . $row['name_ar'] ); ?></th>
						<?php foreach ( $methods as $method ) : ?>
							<?php
							$rate  = isset( $matrix[ $row['id'] ][ $method ] ) ? $matrix[ $row['id'] ][ $method ] : null;
							$value = $rate && $rate['active'] ? Money::to_decimal( $rate['fee_minor'], $decimals ) : '';
							$off   = $rate && ! $rate['active'];
							$field = 'ds-fee-' . $row['id'] . '-' . $method;
							?>
							<td>
								<label class="screen-reader-text" for="<?php echo esc_attr( $field ); ?>"><?php echo esc_html( $row['name_ar'] . ' — ' . DeliverySettings::label( $method ) ); ?></label>
								<input type="text" inputmode="decimal" dir="ltr" class="ds-fee" id="<?php echo esc_attr( $field ); ?>" name="fee[<?php echo esc_attr( (string) $row['id'] ); ?>][<?php echo esc_attr( $method ); ?>]" value="<?php echo esc_attr( $value ); ?>">
								<label class="ds-off"><input type="checkbox" name="off[<?php echo esc_attr( (string) $row['id'] ); ?>][<?php echo esc_attr( $method ); ?>]" value="1" <?php checked( $off ); ?>> <?php esc_html_e( 'غير مخدومة', 'deco-store-core' ); ?></label>
							</td>
						<?php endforeach; ?>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			</div>
			<?php submit_button( __( 'حفظ الأسعار', 'deco-store-core' ) ); ?>
		</form>
		<?php
	}

	/**
	 * Pickup points tab.
	 *
	 * @return void
	 */
	private static function render_pickups() {
		$points = PickupRepository::all();
		echo '<p class="ds-lead">' . esc_html__( 'مكاتب التوصيل التي يمكن للعميل الاستلام منها. تُضاف من ملف CSV (اسم المكتب، العنوان، الشركة، الساعات).', 'deco-store-core' ) . '</p>';
		self::render_import_form( 'pickups', "wilaya_code,commune_code,carrier,name,address,hours\n90,9001,شركة تجريبية,مكتب تجريبي,عنوان تجريبي,9:00-17:00" );
		if ( empty( $points ) ) {
			echo '<p class="ds-empty">' . esc_html__( 'لا توجد مكاتب بعد.', 'deco-store-core' ) . '</p>';
			return;
		}
		?>
		<div class="ds-scroll">
		<table class="widefat striped">
			<thead><tr>
				<th scope="col"><?php esc_html_e( 'المكتب', 'deco-store-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'المنطقة', 'deco-store-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'الشركة', 'deco-store-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'العنوان والساعات', 'deco-store-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'الحالة', 'deco-store-core' ); ?></th>
			</tr></thead>
			<tbody>
			<?php foreach ( $points as $p ) : ?>
				<?php $area = GeoRepository::get( $p['geo_id'] ); ?>
				<tr>
					<td><?php echo esc_html( $p['name'] ); ?></td>
					<td><?php echo esc_html( $area ? $area['name_ar'] : '—' ); ?></td>
					<td><?php echo esc_html( $p['carrier'] ); ?></td>
					<td><?php echo esc_html( trim( $p['address'] . ( $p['hours'] ? ' · ' . $p['hours'] : '' ) ) ); ?></td>
					<td class="ds-row-actions">
						<span class="ds-badge ds-badge--<?php echo $p['active'] ? 'ok' : 'fail'; ?>"><?php echo $p['active'] ? '✓ ' . esc_html__( 'مفعّل', 'deco-store-core' ) : '✕ ' . esc_html__( 'موقوف', 'deco-store-core' ); ?></span>
						<form method="post" class="ds-inline"><?php self::form_fields( 'toggle_pickup' ); ?><input type="hidden" name="pickup_id" value="<?php echo esc_attr( (string) $p['id'] ); ?>"><button type="submit" class="button button-small"><?php echo $p['active'] ? esc_html__( 'إيقاف', 'deco-store-core' ) : esc_html__( 'تفعيل', 'deco-store-core' ); ?></button></form>
						<form method="post" class="ds-inline"><?php self::form_fields( 'delete_pickup' ); ?><input type="hidden" name="pickup_id" value="<?php echo esc_attr( (string) $p['id'] ); ?>"><button type="submit" class="button button-small button-link-delete"><?php esc_html_e( 'حذف', 'deco-store-core' ); ?></button></form>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		</div>
		<?php
	}

	/**
	 * Price preview tab (read-only GET form).
	 *
	 * @return void
	 */
	private static function render_preview() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only preview.
		$wilaya_id  = isset( $_GET['wilaya'] ) ? absint( $_GET['wilaya'] ) : 0;
		$commune_id = isset( $_GET['commune'] ) ? absint( $_GET['commune'] ) : 0;
		$subtotal   = isset( $_GET['subtotal'] ) ? sanitize_text_field( wp_unslash( $_GET['subtotal'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$wilayas  = GeoRepository::wilayas();
		$communes = $wilaya_id ? GeoRepository::communes( $wilaya_id ) : array();
		?>
		<p class="ds-lead"><?php esc_html_e( 'اختبر السعر الذي سيظهر للعميل. هذه الصفحة لا تغيّر أي بيانات.', 'deco-store-core' ); ?></p>
		<form method="get" class="ds-preview-form">
			<input type="hidden" name="page" value="<?php echo esc_attr( self::SLUG ); ?>">
			<input type="hidden" name="tab" value="preview">
			<p>
				<label for="ds-pw"><?php esc_html_e( 'الولاية', 'deco-store-core' ); ?></label><br>
				<select id="ds-pw" name="wilaya">
					<option value="0"><?php esc_html_e( '— اختر —', 'deco-store-core' ); ?></option>
					<?php foreach ( $wilayas as $w ) : ?>
						<option value="<?php echo esc_attr( (string) $w['id'] ); ?>" <?php selected( $wilaya_id, $w['id'] ); ?>><?php echo esc_html( GeoCode::display_wilaya( $w['code'] ) . ' · ' . $w['name_ar'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
			<p>
				<label for="ds-pc"><?php esc_html_e( 'البلدية', 'deco-store-core' ); ?></label><br>
				<select id="ds-pc" name="commune">
					<option value="0"><?php echo esc_html( $wilaya_id ? __( '— كل الولاية —', 'deco-store-core' ) : __( 'اختر الولاية ثم اضغط «عرض»', 'deco-store-core' ) ); ?></option>
					<?php foreach ( $communes as $c ) : ?>
						<option value="<?php echo esc_attr( (string) $c['id'] ); ?>" <?php selected( $commune_id, $c['id'] ); ?>><?php echo esc_html( $c['name_ar'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
			<p>
				<label for="ds-ps"><?php esc_html_e( 'مجموع المنتجات', 'deco-store-core' ); ?></label><br>
				<input type="text" inputmode="decimal" dir="ltr" id="ds-ps" name="subtotal" value="<?php echo esc_attr( $subtotal ); ?>" class="small-text">
			</p>
			<?php submit_button( __( 'عرض', 'deco-store-core' ), 'secondary', '', false ); ?>
		</form>
		<?php
		if ( ! $wilaya_id ) {
			return;
		}
		$decimals = DeliveryService::decimals();
		$minor    = '' === $subtotal ? 0 : Money::to_minor( $subtotal, $decimals );
		if ( null === $minor ) {
			echo '<div class="notice notice-error inline"><p>' . esc_html__( 'المجموع ليس مبلغاً صالحاً.', 'deco-store-core' ) . '</p></div>';
			return;
		}
		$symbol = function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '';
		echo '<table class="widefat striped ds-status"><thead><tr><th scope="col">' . esc_html__( 'الطريقة', 'deco-store-core' ) . '</th><th scope="col">' . esc_html__( 'السعر', 'deco-store-core' ) . '</th><th scope="col">' . esc_html__( 'السبب', 'deco-store-core' ) . '</th></tr></thead><tbody>';
		foreach ( DeliveryService::quote_all( $wilaya_id, $commune_id, $minor ) as $quote ) {
			$price = $quote['available'] ? '<span dir="ltr">' . esc_html( $quote['fee'] ) . '</span> ' . esc_html( $symbol ) : '<span class="ds-badge ds-badge--fail">✕ ' . esc_html__( 'غير متاح', 'deco-store-core' ) . '</span>';
			echo '<tr><th scope="row">' . esc_html( $quote['label'] ) . '</th><td>' . $price . '</td><td>' . esc_html( DeliveryService::explain( $quote ) ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $price is built from escaped parts above.
		}
		echo '</tbody></table>';
		$points = PickupRepository::for_area( $wilaya_id, $commune_id );
		echo '<h2>' . esc_html__( 'مكاتب الاستلام لهذه المنطقة', 'deco-store-core' ) . '</h2>';
		if ( empty( $points ) ) {
			echo '<p class="ds-empty">' . esc_html__( 'لا يوجد مكتب مسجّل لهذه المنطقة.', 'deco-store-core' ) . '</p>';
			return;
		}
		echo '<ul class="ds-list">';
		foreach ( $points as $p ) {
			echo '<li>' . esc_html( $p['name'] . ( $p['carrier'] ? ' (' . $p['carrier'] . ')' : '' ) . ( $p['address'] ? ' — ' . $p['address'] : '' ) ) . '</li>';
		}
		echo '</ul>';
	}
}
