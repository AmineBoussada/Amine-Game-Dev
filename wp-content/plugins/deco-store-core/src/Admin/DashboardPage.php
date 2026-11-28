<?php
/**
 * Dashboard: system status and setup checklist.
 *
 * @package DecoStore
 */

namespace DecoStore\Admin;

use DecoStore\Core\Capabilities;
use DecoStore\Core\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the status page.
 */
class DashboardPage {

	/**
	 * Render the page.
	 *
	 * @return void
	 */
	public static function render() {
		if ( ! current_user_can( Capabilities::VIEW_REPORTS ) ) {
			wp_die( esc_html__( 'ليست لديك صلاحية عرض هذه الصفحة.', 'deco-store-core' ), '', array( 'response' => 403 ) );
		}
		$checks = StatusChecks::run();
		$labels = array(
			StatusChecks::OK   => '✓ ' . __( 'سليم', 'deco-store-core' ),
			StatusChecks::WARN => '! ' . __( 'تنبيه', 'deco-store-core' ),
			StatusChecks::FAIL => '✕ ' . __( 'مشكلة', 'deco-store-core' ),
		);
		$status = Settings::get( 'initial_order_status' );
		$fails  = count(
			array_filter(
				$checks,
				static function ( $c ) {
					return StatusChecks::FAIL === $c['status'];
				}
			)
		);
		$warns  = count(
			array_filter(
				$checks,
				static function ( $c ) {
					return StatusChecks::WARN === $c['status'];
				}
			)
		);
		?>
		<div class="wrap ds-admin">
			<h1><?php esc_html_e( 'Déco Store — لوحة التحكم', 'deco-store-core' ); ?></h1>
			<p class="ds-lead"><?php esc_html_e( 'حالة النظام وقائمة الإعداد. لا تغيّر هذه الصفحة أي بيانات.', 'deco-store-core' ); ?></p>

			<div class="ds-summary">
				<span class="ds-pill ds-pill--<?php echo $fails ? 'fail' : 'ok'; ?>">
					<?php
					echo $fails
						/* translators: %d: number of problems. */
						? esc_html( sprintf( _n( '%d مشكلة', '%d مشاكل', $fails, 'deco-store-core' ), $fails ) )
						: esc_html__( 'لا توجد مشاكل', 'deco-store-core' );
					?>
				</span>
				<span class="ds-pill ds-pill--<?php echo $warns ? 'warn' : 'ok'; ?>">
					<?php
					echo $warns
						/* translators: %d: number of warnings. */
						? esc_html( sprintf( _n( '%d تنبيه', '%d تنبيهات', $warns, 'deco-store-core' ), $warns ) )
						: esc_html__( 'لا توجد تنبيهات', 'deco-store-core' );
					?>
				</span>
				<span class="ds-pill">
					<?php
					/* translators: %s: order status label. */
					echo esc_html( sprintf( __( 'حالة الطلب الجديد: %s', 'deco-store-core' ), 'processing' === $status ? __( 'قيد المعالجة', 'deco-store-core' ) : __( 'معلّق', 'deco-store-core' ) ) );
					?>
				</span>
			</div>

			<table class="widefat striped ds-status" role="table">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'البند', 'deco-store-core' ); ?></th>
						<th scope="col"><?php esc_html_e( 'الحالة', 'deco-store-core' ); ?></th>
						<th scope="col"><?php esc_html_e( 'التفاصيل', 'deco-store-core' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $checks as $check ) : ?>
					<tr>
						<th scope="row"><?php echo esc_html( $check['label'] ); ?></th>
						<td><span class="ds-badge ds-badge--<?php echo esc_attr( $check['status'] ); ?>"><?php echo esc_html( $labels[ $check['status'] ] ); ?></span></td>
						<td><?php echo esc_html( $check['message'] ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}
