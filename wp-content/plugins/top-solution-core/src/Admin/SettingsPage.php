<?php
/**
 * Settings page with tabs (General, Orders). Saved through the WordPress
 * Settings API, validated by Settings::sanitize() and gated by the
 * ts_manage_settings capability.
 *
 * @package TopSolution
 */

namespace TopSolution\Admin;

use TopSolution\Core\Capabilities;
use TopSolution\Core\Settings;

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
			'general' => __( 'عام', 'top-solution-core' ),
			'orders'  => __( 'الطلبات', 'top-solution-core' ),
		);
	}

	/**
	 * Render the page.
	 *
	 * @return void
	 */
	public static function render() {
		if ( ! current_user_can( Capabilities::MANAGE_SETTINGS ) ) {
			wp_die( esc_html__( 'ليست لديك صلاحية عرض هذه الصفحة.', 'top-solution-core' ), '', array( 'response' => 403 ) );
		}
		$labels = self::tab_labels();
		$tab    = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only tab selection.
		$tab    = isset( $labels[ $tab ] ) ? $tab : 'general';
		$values = Settings::all();
		?>
		<div class="wrap ts-admin">
			<h1><?php esc_html_e( 'Top Solution — الإعدادات', 'top-solution-core' ); ?></h1>
			<?php settings_errors(); ?>
			<nav class="nav-tab-wrapper" aria-label="<?php esc_attr_e( 'أقسام الإعدادات', 'top-solution-core' ); ?>">
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

			<form method="post" action="options.php" class="ts-form">
				<?php settings_fields( Settings::GROUP ); ?>
				<input type="hidden" name="<?php echo esc_attr( Settings::OPTION ); ?>[__tab]" value="<?php echo esc_attr( $tab ); ?>">
				<table class="form-table" role="presentation">
					<?php
					if ( 'orders' === $tab ) {
						self::render_orders_tab( $values );
					} else {
						self::render_general_tab( $values );
					}
					?>
				</table>
				<?php submit_button( __( 'حفظ التغييرات', 'top-solution-core' ) ); ?>
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
			<th scope="row"><?php esc_html_e( 'سجل التشخيص', 'top-solution-core' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[debug_logging]" value="1" <?php checked( ! empty( $values['debug_logging'] ) ); ?>>
					<?php esc_html_e( 'تسجيل رسائل التشخيص (ووكومرس ← الحالة ← السجلات، المصدر top-solution)', 'top-solution-core' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'لا تُسجَّل أبداً بيانات العملاء (الأسماء والهواتف والعناوين) ولا التكاليف.', 'top-solution-core' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'عند حذف الإضافة', 'top-solution-core' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[delete_data_on_uninstall]" value="1" <?php checked( ! empty( $values['delete_data_on_uninstall'] ) ); ?>>
					<?php esc_html_e( 'احذف إعدادات الإضافة وصلاحياتها نهائياً', 'top-solution-core' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'تعطيل الإضافة لا يحذف شيئاً. الطلبات تبقى دائماً في ووكومرس.', 'top-solution-core' ); ?></p>
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
			<th scope="row"><?php esc_html_e( 'حالة الطلب الجديد', 'top-solution-core' ); ?></th>
			<td>
				<fieldset>
					<legend class="screen-reader-text"><?php esc_html_e( 'حالة الطلب الجديد', 'top-solution-core' ); ?></legend>
					<label>
						<input type="radio" name="<?php echo esc_attr( $name ); ?>[initial_order_status]" value="processing" <?php checked( 'processing', $current ); ?>>
						<?php esc_html_e( 'قيد المعالجة (افتراضي)', 'top-solution-core' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'يُقبل الطلب فوراً ويظهر جاهزاً للتجهيز.', 'top-solution-core' ); ?></p>
					<label>
						<input type="radio" name="<?php echo esc_attr( $name ); ?>[initial_order_status]" value="on-hold" <?php checked( 'on-hold', $current ); ?>>
						<?php esc_html_e( 'معلّق', 'top-solution-core' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'ينتظر تأكيدك بالهاتف قبل التجهيز.', 'top-solution-core' ); ?></p>
				</fieldset>
			</td>
		</tr>
		<?php
	}
}
