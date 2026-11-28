<?php
/**
 * Settings page with tabs (General, Orders). Saved through the WordPress
 * Settings API, validated by Settings::sanitize() and gated by the
 * ds_manage_settings capability.
 *
 * @package DecoStore
 */

namespace DecoStore\Admin;

use DecoStore\Core\Capabilities;
use DecoStore\Core\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the settings page.
 */
class SettingsPage {

	/**
	 * Tab labels.
	 *
	 * @return array<string,string>
	 */
	private static function tab_labels() {
		return array(
			'general' => __( 'عام', 'deco-store-core' ),
			'orders'  => __( 'الطلبات', 'deco-store-core' ),
			'forms'   => __( 'النماذج', 'deco-store-core' ),
			'offers'  => __( 'العروض', 'deco-store-core' ),
		);
	}

	/**
	 * Render the page.
	 *
	 * @return void
	 */
	public static function render() {
		if ( ! current_user_can( Capabilities::MANAGE_SETTINGS ) ) {
			wp_die( esc_html__( 'ليست لديك صلاحية عرض هذه الصفحة.', 'deco-store-core' ), '', array( 'response' => 403 ) );
		}
		$labels = self::tab_labels();
		$tab    = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only tab selection.
		$tab    = isset( $labels[ $tab ] ) ? $tab : 'general';
		$values = Settings::all();
		?>
		<div class="wrap ds-admin">
			<h1><?php esc_html_e( 'Déco Store — الإعدادات', 'deco-store-core' ); ?></h1>
			<?php settings_errors(); ?>
			<nav class="nav-tab-wrapper" aria-label="<?php esc_attr_e( 'أقسام الإعدادات', 'deco-store-core' ); ?>">
				<?php foreach ( $labels as $key => $label ) : ?>
					<a class="nav-tab<?php echo $key === $tab ? ' nav-tab-active' : ''; ?>" href="
					<?php
					echo esc_url(
						add_query_arg(
							array(
								'page' => Menu::SLUG_SETTINGS,
								'tab'  => $key,
							),
							admin_url( 'admin.php' )
						)
					);
					?>
										"<?php echo $key === $tab ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</nav>

			<form method="post" action="options.php" class="ds-form">
				<?php settings_fields( Settings::GROUP ); ?>
				<input type="hidden" name="<?php echo esc_attr( Settings::OPTION ); ?>[__tab]" value="<?php echo esc_attr( $tab ); ?>">
				<table class="form-table" role="presentation">
					<?php
					if ( 'orders' === $tab ) {
						self::render_orders_tab( $values );
					} elseif ( 'forms' === $tab ) {
						self::render_forms_tab( $values );
					} elseif ( 'offers' === $tab ) {
						self::render_offers_tab( $values );
					} else {
						self::render_general_tab( $values );
					}
					?>
				</table>
				<?php submit_button( __( 'حفظ التغييرات', 'deco-store-core' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * General tab.
	 *
	 * @param array $values Current settings.
	 * @return void
	 */
	private static function render_general_tab( array $values ) {
		$name = Settings::OPTION;
		?>
		<tr>
			<th scope="row"><?php esc_html_e( 'سجل التشخيص', 'deco-store-core' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[debug_logging]" value="1" <?php checked( ! empty( $values['debug_logging'] ) ); ?>>
					<?php esc_html_e( 'تسجيل رسائل التشخيص (ووكومرس ← الحالة ← السجلات، المصدر deco-store)', 'deco-store-core' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'لا تُسجَّل أبداً بيانات العملاء (الأسماء والهواتف والعناوين) ولا التكاليف.', 'deco-store-core' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'عند حذف الإضافة', 'deco-store-core' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[delete_data_on_uninstall]" value="1" <?php checked( ! empty( $values['delete_data_on_uninstall'] ) ); ?>>
					<?php esc_html_e( 'احذف إعدادات الإضافة وصلاحياتها نهائياً', 'deco-store-core' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'تعطيل الإضافة لا يحذف شيئاً. الطلبات تبقى دائماً في ووكومرس.', 'deco-store-core' ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Forms tab.
	 *
	 * @param array $values Current settings.
	 * @return void
	 */
	private static function render_forms_tab( array $values ) {
		$name    = Settings::OPTION;
		$current = isset( $values['phone_rule'] ) ? $values['phone_rule'] : 'generic';
		?>
		<tr>
			<th scope="row"><?php esc_html_e( 'قاعدة رقم الهاتف', 'deco-store-core' ); ?></th>
			<td>
				<fieldset>
					<legend class="screen-reader-text"><?php esc_html_e( 'قاعدة رقم الهاتف', 'deco-store-core' ); ?></legend>
					<label>
						<input type="radio" name="<?php echo esc_attr( $name ); ?>[phone_rule]" value="generic" <?php checked( 'generic', $current ); ?>>
						<?php esc_html_e( 'عامة (افتراضي)', 'deco-store-core' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'من 8 إلى 15 رقماً، وعلامة + اختيارية في البداية. تُقبل المسافات والشرطات والأرقام العربية.', 'deco-store-core' ); ?></p>
					<label>
						<input type="radio" name="<?php echo esc_attr( $name ); ?>[phone_rule]" value="dz_mobile" <?php checked( 'dz_mobile', $current ); ?>>
						<?php esc_html_e( 'هاتف جزائري محمول', 'deco-store-core' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'يقبل الأرقام التي تبدأ بـ 05 أو 06 أو 07 (10 أرقام). تُحوَّل البادئات +213 و00213 و213 إلى الصيغة المحلية 0… تلقائياً. لا يقبل الهاتف الثابت. تحقق من أنها تناسب عملاءك قبل التفعيل.', 'deco-store-core' ); ?></p>
				</fieldset>
			</td>
		</tr>
		<?php
	}

	/**
	 * Offers tab.
	 *
	 * @param array $values Current settings.
	 * @return void
	 */
	private static function render_offers_tab( array $values ) {
		$name = Settings::OPTION;
		?>
		<tr>
			<th scope="row"><label for="ds-max-gifts"><?php esc_html_e( 'أقصى عدد هدايا في الطلب', 'deco-store-core' ); ?></label></th>
			<td>
				<input type="number" id="ds-max-gifts" name="<?php echo esc_attr( $name ); ?>[max_gifts_per_order]" min="0" max="10" step="1" class="small-text" value="<?php echo esc_attr( (string) $values['max_gifts_per_order'] ); ?>">
				<p class="description"><?php esc_html_e( 'عند تحقق شروط أكثر من هدية يُطبَّق الأعلى أولوية حتى هذا الحد. اكتب 0 لإيقاف الهدايا.', 'deco-store-core' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="ds-min-price"><?php esc_html_e( 'حد أدنى لسعر السطر (٪ من السعر الأصلي)', 'deco-store-core' ); ?></label></th>
			<td>
				<input type="number" id="ds-min-price" name="<?php echo esc_attr( $name ); ?>[min_price_percent]" min="0" max="100" step="1" class="small-text" value="<?php echo esc_attr( (string) $values['min_price_percent'] ); ?>">
				<p class="description"><?php esc_html_e( 'حماية من خطأ في إعداد العروض: لا ينزل سعر أي سطر تحت هذه النسبة مهما كانت الخصومات. اكتب 0 لإيقاف الحماية.', 'deco-store-core' ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Orders tab.
	 *
	 * @param array $values Current settings.
	 * @return void
	 */
	private static function render_orders_tab( array $values ) {
		$name    = Settings::OPTION;
		$current = isset( $values['initial_order_status'] ) ? $values['initial_order_status'] : 'processing';
		?>
		<tr>
			<th scope="row"><?php esc_html_e( 'حالة الطلب الجديد', 'deco-store-core' ); ?></th>
			<td>
				<fieldset>
					<legend class="screen-reader-text"><?php esc_html_e( 'حالة الطلب الجديد', 'deco-store-core' ); ?></legend>
					<label>
						<input type="radio" name="<?php echo esc_attr( $name ); ?>[initial_order_status]" value="processing" <?php checked( 'processing', $current ); ?>>
						<?php esc_html_e( 'قيد المعالجة (افتراضي)', 'deco-store-core' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'يُقبل الطلب فوراً ويظهر جاهزاً للتجهيز.', 'deco-store-core' ); ?></p>
					<label>
						<input type="radio" name="<?php echo esc_attr( $name ); ?>[initial_order_status]" value="on-hold" <?php checked( 'on-hold', $current ); ?>>
						<?php esc_html_e( 'معلّق', 'deco-store-core' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'ينتظر تأكيدك بالهاتف قبل التجهيز.', 'deco-store-core' ); ?></p>
				</fieldset>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="ds-confirm-msg"><?php esc_html_e( 'رسالة صفحة التأكيد', 'deco-store-core' ); ?></label></th>
			<td>
				<textarea id="ds-confirm-msg" name="<?php echo esc_attr( $name ); ?>[confirmation_message]" rows="3" class="large-text" maxlength="500"><?php echo esc_textarea( (string) $values['confirmation_message'] ); ?></textarea>
				<p class="description"><?php esc_html_e( 'تظهر للعميل بعد الطلب (مثلاً متى ستتصل به). اتركها فارغة لنص محايد: «تم تسجيل طلبك بنجاح. الدفع يكون نقداً عند الاستلام.»', 'deco-store-core' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="ds-dup-window"><?php esc_html_e( 'تنبيه الطلب المكرر (ساعات)', 'deco-store-core' ); ?></label></th>
			<td>
				<input type="number" id="ds-dup-window" name="<?php echo esc_attr( $name ); ?>[duplicate_window_hours]" min="0" max="168" class="small-text" value="<?php echo esc_attr( (string) $values['duplicate_window_hours'] ); ?>">
				<p class="description"><?php esc_html_e( 'إذا طلب نفس رقم الهاتف نفس المنتج خلال هذه المدة يُسجَّل الطلب مع علامة «طلب مكرر محتمل» لتراجعه. لا يُرفض أي طلب. 0 = إيقاف.', 'deco-store-core' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="ds-max-attempts"><?php esc_html_e( 'أقصى محاولات طلب من نفس الشبكة (كل 10 دقائق)', 'deco-store-core' ); ?></label></th>
			<td>
				<input type="number" id="ds-max-attempts" name="<?php echo esc_attr( $name ); ?>[max_attempts_per_ip]" min="1" max="200" class="small-text" value="<?php echo esc_attr( (string) $values['max_attempts_per_ip'] ); ?>">
				<p class="description"><?php esc_html_e( 'حماية من الإرسال الآلي. عدة أشخاص قد يشتركون في نفس الشبكة (مقهى، شبكة الهاتف)، فلا تجعل الرقم صغيراً جداً.', 'deco-store-core' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="ds-token-ttl"><?php esc_html_e( 'صلاحية صفحة الطلب (ساعات)', 'deco-store-core' ); ?></label></th>
			<td>
				<input type="number" id="ds-token-ttl" name="<?php echo esc_attr( $name ); ?>[token_ttl_hours]" min="1" max="72" class="small-text" value="<?php echo esc_attr( (string) $values['token_ttl_hours'] ); ?>">
				<p class="description"><?php esc_html_e( 'بعدها تُجدَّد الصفحة تلقائياً عند الضغط على زر الطلب.', 'deco-store-core' ); ?></p>
			</td>
		</tr>
		<?php
	}
}
